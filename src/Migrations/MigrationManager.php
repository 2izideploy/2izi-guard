<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Migrations;

use PDO;
use TwoIzi\Guard\Storage\Database;

final class MigrationManager
{
    public function __construct(private Database $db, private string $directory) {}

    public function migrate(): array
    {
        $pdo = $this->db->pdo();
        $this->ensureTable($pdo);
        $this->bootstrapLegacyBaseline($pdo);
        $applied = $this->applied($pdo);
        $current = $applied ? max(array_keys($applied)) : 0;
        $done = [];
        foreach ($this->files() as $version => $file) {
            if ($version <= $current || isset($applied[$version])) {
                continue;
            }
            $sql = trim((string)file_get_contents($file));
            if ($sql === '') {
                continue;
            }
            $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO guard_schema_migrations (version, filename, applied_at) VALUES (?, ?, NOW(6))');
            $stmt->execute([$version, basename($file)]);
            $done[] = ['version' => $version, 'file' => basename($file)];
        }
        return $done;
    }

    public function currentVersion(): int
    {
        $pdo = $this->db->pdo();
        $this->ensureTable($pdo);
        return (int)$pdo->query('SELECT COALESCE(MAX(version), 0) FROM guard_schema_migrations')->fetchColumn();
    }

    private function ensureTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS guard_schema_migrations (version INT UNSIGNED NOT NULL, filename VARCHAR(255) NOT NULL, applied_at DATETIME(6) NOT NULL, PRIMARY KEY(version)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function bootstrapLegacyBaseline(PDO $pdo): void
    {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM guard_schema_migrations')->fetchColumn();
        if ($count > 0) return;
        try {
            $cols = $pdo->query('SHOW COLUMNS FROM guard_challenges')->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (\Throwable) {
            return;
        }
        // Detect the newest legacy baseline without trusting a version string.
        if (in_array('interactive_required', $cols, true)) $baseline = 3;
        elseif (in_array('reason_codes', $cols, true)) $baseline = 2;
        else $baseline = 1;
        $stmt = $pdo->prepare('INSERT IGNORE INTO guard_schema_migrations (version, filename, applied_at) VALUES (?, ?, NOW(6))');
        $stmt->execute([$baseline, 'legacy-baseline']);
    }

    private function applied(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT version FROM guard_schema_migrations')->fetchAll(PDO::FETCH_COLUMN, 0);
        return array_fill_keys(array_map('intval', $rows), true);
    }

    private function files(): array
    {
        $out = [];
        foreach (glob(rtrim($this->directory, '/') . '/*.sql') ?: [] as $file) {
            if (preg_match('/^(\d+)_/', basename($file), $m)) {
                $out[(int)$m[1]] = $file;
            }
        }
        ksort($out, SORT_NUMERIC);
        return $out;
    }
}
