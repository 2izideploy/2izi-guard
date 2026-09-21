<?php
declare(strict_types=1);

use TwoIzi\Guard\Migrations\MigrationManager;
use TwoIzi\Guard\Storage\Database;

/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require dirname(__DIR__) . '/bootstrap.php';
$db = new Database($guard->config());
$manager = new MigrationManager($db, dirname(__DIR__) . '/database/migrations');
$done = $manager->migrate();
if (!$done) {
    echo 'No migrations needed. Schema version: ' . $manager->currentVersion() . PHP_EOL;
    exit(0);
}
foreach ($done as $migration) {
    echo 'Applied ' . $migration['version'] . ': ' . $migration['file'] . PHP_EOL;
}
echo 'Schema version: ' . $manager->currentVersion() . PHP_EOL;
