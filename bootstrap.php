<?php
declare(strict_types=1);

use TwoIzi\Guard\Core\Guard;

spl_autoload_register(static function (string $class): void {
    $prefix = 'TwoIzi\\Guard\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

$configFile = getenv('IZI_GUARD_CONFIG') ?: (__DIR__ . '/config/guard.php');
if (!is_file($configFile)) {
    throw new RuntimeException('2IZI Guard config not found. Copy config/guard.example.php to a protected guard.php and configure it.');
}

$config = require $configFile;
if (!is_array($config)) {
    throw new RuntimeException('2IZI Guard config must return an array.');
}

Guard::boot($config);
return Guard::instance();
