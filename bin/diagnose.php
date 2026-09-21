<?php
declare(strict_types=1);

/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require dirname(__DIR__) . '/bootstrap.php';
$result = $guard->diagnostics();
echo '2IZI Guard diagnostics v' . $result['version'] . PHP_EOL;
foreach ($result['checks'] as $check) {
    echo ($check['ok'] ? '[ OK ] ' : '[FAIL] ') . $check['name'] . ': ' . $check['message'] . PHP_EOL;
}
exit($result['ok'] ? 0 : 1);
