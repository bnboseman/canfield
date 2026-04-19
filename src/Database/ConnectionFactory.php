<?php

namespace Database;

use PDO;
use RuntimeException;
use PDOException;
use InvalidArgumentException;

class ConnectionFactory {
    /**
     * @param string $name
     * @return \Database\Database
     */
    public static function create(string $name): Database
    {
        $config = require __DIR__ . '/../../config/config.php';
        if (empty($config['database'][$name])) {
            throw new InvalidArgumentException("Database connection not found!");
        }

        $dsn = "mysql:host={$config['database'][$name]['host']};dbname={$config['database'][$name]['dbname']};charset=utf8mb4";
        try {
            $connection = new PDO(
                $dsn,
                $config['database'][$name]['user'],
                $config['database'][$name]['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
            return new Database($connection);
        } catch (PDOException $e) {
            throw new RuntimeException("Connection failed: " . $e->getMessage());
        }
    }

}