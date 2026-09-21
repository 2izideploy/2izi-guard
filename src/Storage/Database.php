<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Storage;

use PDO;
use TwoIzi\Guard\Config\Config;

final class Database
{
    private PDO $pdo;

    public function __construct(Config $config)
    {
        $db = $config->get('database');
        $options = $db['options'] ?? [];
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_EMULATE_PREPARES] = false;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $this->pdo = new PDO($db['dsn'], $db['user'], $db['password'], $options);
        // Guard timestamps are normalized to UTC at the DB connection level.
        $this->pdo->exec("SET time_zone = '+00:00'");
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
