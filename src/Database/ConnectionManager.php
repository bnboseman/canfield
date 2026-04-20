<?php
namespace Database;

use PDO;

/**
 * Manages multiple database connections
 */
class ConnectionManager
{
    /**
     * Array to store open connections
     * @var array 
     */
    private static array $connections = [];

    /**
     * Retrieve database. 
     * @param string $name The name of connection you want to get
     * @return Database
     */
    public static function get(string $name): Database
    {
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::create($name);
        }

        return self::$connections[$name];
    }

    protected static function create(string $name): Database
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