<?php
namespace Database;

use PDO;

class ConnectionManager
{
    private static array $connections = [];

    /**
     * @param string $name
     * @return Database
     */
    public static function get(string $name): Database
    {
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = ConnectionFactory::create($name);
        }

        return self::$connections[$name];
    }
}