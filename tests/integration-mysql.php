<?php
declare(strict_types=1);

// Optional integration test. Point it ONLY at a disposable test database.
$dsn = getenv('IZI_GUARD_TEST_DSN') ?: '';
$user = getenv('IZI_GUARD_TEST_USER') ?: '';
$pass = getenv('IZI_GUARD_TEST_PASSWORD') ?: '';
if ($dsn === '' || !extension_loaded('pdo_mysql')) {
    echo "SKIP: set IZI_GUARD_TEST_DSN and enable pdo_mysql to run MySQL/MariaDB integration tests.\n";
    exit(0);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'TwoIzi\\Guard\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require_once $path;
});

use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Risk\ReasonCode;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Security\OriginValidator;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Support\Encoding;
use TwoIzi\Guard\Token\TokenRepository;

function makeConfig(string $dsn, string $user, string $pass, string $key): Config {
    return new Config([
        'origin' => 'https://example.test',
        'database' => ['dsn' => $dsn, 'user' => $user, 'password' => $pass, 'options' => []],
        'keys' => ['hmac' => $key, 'privacy' => $key],
        'actions' => ['contact' => []],
    ]);
}

$key = 'base64url:' . Encoding::base64UrlEncode(random_bytes(32));
$config = makeConfig($dsn, $user, $pass, $key);
$db = new Database($config);
$sql = file_get_contents(dirname(__DIR__) . '/database/install.sql');
foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)$sql))) as $statement) {
    $db->pdo()->exec($statement);
}
foreach (['guard_submissions','guard_tokens','guard_challenges','guard_sessions','guard_rate_limits','guard_events'] as $table) {
    $db->pdo()->exec("DELETE FROM {$table}");
}
$session = Crypto::sha256('session');
$db->pdo()->prepare('INSERT INTO guard_sessions(session_hash,created_at,last_seen_at,expires_at) VALUES(?,NOW(6),NOW(6),DATE_ADD(NOW(6),INTERVAL 1 HOUR))')->execute([$session]);
$db->pdo()->prepare("INSERT INTO guard_challenges(public_id,session_hash,action,challenge_type,salt,difficulty,status,issued_at,expires_at,risk_score,policy_version,attempt_count) VALUES('ch_test',?,'contact','pow','salt',8,'token_issued',NOW(6),DATE_ADD(NOW(6),INTERVAL 1 MINUTE),0,2,1)")->execute([$session]);
$challengeId = (int)$db->pdo()->lastInsertId();
$originValidator = new OriginValidator($config);
$repo = new TokenRepository($db, $config, $originValidator);
$origin = 'https://example.test';

$token = Crypto::randomToken();
$repo->create($token, $session, $challengeId, 'contact', $origin, 120, 0);
if (!$repo->consumeDetailed($token, $session, 'contact', $origin)->valid()) { fwrite(STDERR, "FAIL first consume\n"); exit(1); }
$replay = $repo->consumeDetailed($token, $session, 'contact', $origin);
if ($replay->valid() || $replay->reason() !== ReasonCode::TOKEN_REPLAY) { fwrite(STDERR, "FAIL replay classification\n"); exit(1); }
echo "OK: sequential replay rejected and classified.\n";

if (!function_exists('pcntl_fork')) {
    echo "SKIP: pcntl unavailable; concurrency race test not run.\n";
    exit(0);
}

$raceToken = Crypto::randomToken();
$repo->create($raceToken, $session, $challengeId, 'contact', $origin, 120, 0);
$tmp = sys_get_temp_dir() . '/izi-guard-race-' . bin2hex(random_bytes(6));
mkdir($tmp, 0700, true);
$children = [];
for ($i = 0; $i < 8; $i++) {
    $pid = pcntl_fork();
    if ($pid === -1) { fwrite(STDERR, "FAIL fork\n"); exit(1); }
    if ($pid === 0) {
        // New connection in each child; never use the inherited PDO handle.
        $childConfig = makeConfig($dsn, $user, $pass, $key);
        $childRepo = new TokenRepository(new Database($childConfig), $childConfig, new OriginValidator($childConfig));
        $ok = $childRepo->consumeDetailed($raceToken, $session, 'contact', $origin)->valid();
        file_put_contents($tmp . '/' . getmypid(), $ok ? '1' : '0');
        exit(0);
    }
    $children[] = $pid;
}
foreach ($children as $pid) pcntl_waitpid($pid, $status);
$wins = 0;
foreach (glob($tmp . '/*') ?: [] as $file) { $wins += trim((string)file_get_contents($file)) === '1' ? 1 : 0; @unlink($file); }
@rmdir($tmp);
if ($wins !== 1) { fwrite(STDERR, "FAIL atomic race: expected 1 winner, got {$wins}\n"); exit(1); }
echo "OK: concurrent replay race has exactly one winner.\n";
