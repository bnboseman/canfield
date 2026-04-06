<?php

/**
 * Singleton Database class responsible for managing single PDO connection and providing basic
 * CRUD functionality
 */
class Database {
    /**
     * Singleton instance
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * PDO Database connection
     * @var PDO
     */
    private PDO $connection;

    /**
     * Private __construct for proper Singleton
     * Initializes PDO connection using $config values
     *
     * @throws PDOException If connection failes
     */
    private function __construct() {
        $config = require __DIR__ . '/../config.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        try {

            $this->connection = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
        } catch (PDOException $e) {
            die("Connection Failed.");
        }
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Returns singleton Database instance
     * @return Database
     */
    public static function  instance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inserts record into the table
     *
     * @param string $table  Name of table
     * @param array $data  Array column => value of values to insert
     * @return false|string Last inserted value on success, false on failure
     *
     * @throws InvalidArgumentException if table or columns names are invalid
     * @throws PDOException If query fails
     */
    public function insert($table, $data)
    {
        $this->validateData($table, $data);

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);

        return $this->connection->lastInsertId();
    }

    /**
     * Gets records from table with optional conditions
     *
     * @param string $table Table Name
     * @param array $conditions Optional WHERE conditions (column => value)
     * @param string $orderBy Column name to sort by
     * @param string $direction Either ASC or DESC
     * @return array Result set as associative arrays
     *
     * @throws InvalidArgumentException if table, columns, or orderBy names are invalid
     * @throws PDOException If query fails
     */
    public function select(string $table, array $conditions = [], ?string $orderBy = null, string $direction = 'ASC'): array
    {
        $this->validateData($table, $conditions);
        if ($orderBy !== null && !$this->isValidTableName($orderBy)) {
            throw new InvalidArgumentException('Invalid orderBy column');
        }

        $sql = "SELECT * FROM {$table}";
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $key => $value) {
                $clauses[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }

        if ($orderBy !== null) {
            $direction = strtoupper($direction);
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw new InvalidArgumentException('Invalid direction for orderBy');
            }
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Updates records in a table based on conditions.
     *
     * @param string $table Name of the table
     * @param array $data Columns and values to update
     * @param array $conditions WHERE conditions to match records
     * @return bool True on success, false on failure
     *
     * @throws @throws InvalidArgumentException if table or columns names are invalid
     * @throws PDOException If query fails
     */
    public function update(string $table, array $data, array $conditions): bool
    {
        $this->validateData($table, $data, $conditions);

        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :set_{$key}";
            $params["set_{$key}"] = $value;
        }

        $whereClauses = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "{$key} = :where_{$key}";
            $params["where_{$key}"] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(", ", $setClauses)
            . " WHERE " . implode(" AND ", $whereClauses);

        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deletes from table
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(string $table, array $conditions): bool
    {
        if (empty($conditions)) {
            throw new InvalidArgumentException("Delete requires conditions.");
        }

        foreach ($conditions as $value) {
            if ($value === null) {
                throw new InvalidArgumentException("Null condition not allowed");
            }
        }

        $this->validateData($table, $conditions);

        $clauses = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $clauses[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "DELETE FROM {$table} WHERE " . implode(" AND ", $clauses);

        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Validates a table name
     *
     * @param string $table Table name
     * @return bool True if valid, false otherwise
     */
    public function isValidTableName(string $table): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $table) === 1;
    }

    /**
     * Validates column names in an associative array.
     *
     * @param array $columns Column => value pairs
     * @return bool True if all column names are valid
     */
    public function hasValidColumnNames(array $columns): bool
    {
        foreach ($columns as $key => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates table name and one or more sets of column arrays.
     *
     * @param string $table Table name
     * @param array ...$columnSets One or more arrays of column => value pairs
     * @return bool True if all validations pass
     *
     * @throws InvalidArgumentException If table or any column name is invalid
     */
    public function validateData(string $table, array ...$columnSets)
    {
        if (!$this->isValidTableName($table)) {
            throw new InvalidArgumentException("Invalid table name");
        }

        // validate all column arrays passed in
        foreach ($columnSets as $columns) {
            if (!$this->hasValidColumnNames($columns)) {
                throw new InvalidArgumentException("Invalid column name");
            }
        }

        return true;
    }

    /**
     * Execute a write query (INSERT/UPDATE/DELETE with expressions)
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function getConnection() {
        return $this->connection;
    }
}