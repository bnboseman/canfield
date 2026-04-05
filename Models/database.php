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
     * Private __construct to ensure Singleton usage
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
     * @param string $table Table Name
     * @param array $conditions Optional WHERE conditions (column => value)
     * @return array Result set as associative arrays
     *
     * @throws InvalidArgumentException if table or columns names are invalid
     * @throws PDOException If query fails
     */
    public function select(string $table, array $conditions = []): array
    {
        $this->validateData($table, $conditions);

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

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Updates records in a table based on conditions.
     *
     * @param string $table Name of the table
     * @param array<string, mixed> $data Columns and values to update
     * @param array<string, mixed> $conditions WHERE conditions to match records
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
     * @param string $table
     * @param array $conditions
     * @return bool
     * @throws Exception
     */
    public function delete(string $table, array $conditions): bool
    {
        if (empty($conditions)) {
            throw new Exception("Delete requires conditions.");
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
     * Executes a raw SQL query with optional parameters.
     *
     * Note: This bypasses table/column validation and should be used carefully. Currently restricted to Select
     *
     * @param string $sql Raw SQL query
     * @param array<string, mixed> $params Optional bound parameters
     * @return array<int, array<string, mixed>> Result set
     *
     * @throws PDOException If query execution fails
     * @throws InvalidArgumentException If multiple statements are trying to be executed or sql contains comments
     * @throws InvalidArgumentException If query other than select is run
     * @throws InvalidArgumentException If params are provided but no placeholders exist
     */
    public function query(string $sql, array $params = []): array
    {
        $this->guardRawSql($sql);
        $this->ensureSelectOnly($sql);
        $this->ensureParamsUsed($sql, $params);
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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
     * @param array<string, mixed> $columns Column => value pairs
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
     * @param array<string, mixed> ...$columnSets One or more arrays of column => value pairs
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
     * Applies basic safety checks to a raw SQL string to prevents common misuse patterns
     *
     * Note:
     * This is not full SQL validation! Use Carefully
     *
     * @param string $sql Raw SQL query string
     * @return void
     *
     * @throws InvalidArgumentException If multiple statements or comments are detected
     */
    private function guardRawSql(string $sql): void
    {
        $trimmed = trim($sql);

        // Block multiple statements
        if (str_contains($trimmed, ';')) {
            throw new InvalidArgumentException("Multiple statements are not allowed.");
        }

        // Block SQL comments
        if (preg_match('/(--|#|\/\*)/', $trimmed)) {
            throw new InvalidArgumentException("SQL comments are not allowed.");
        }
    }

    /**
     * Ensures that the provided SQL query is a SELECT statement to restrict raw queries to read-only operations.
     *
     * @param string $sql Raw SQL query string
     * @return void
     *
     * @throws InvalidArgumentException If the query is not a SELECT statement
     */
    private function ensureSelectOnly(string $sql): void
    {
        if (!preg_match('/^\s*SELECT\b/i', $sql)) {
            throw new InvalidArgumentException("Only SELECT queries are allowed.");
        }
    }

    /**
     * Ensures that named parameters are used when parameters are provided to help enforce the use of prepared statements
     *
     * @param string $sql Raw SQL query string
     * @param array<string, mixed> $params Bound parameters
     * @return void
     *
     * @throws InvalidArgumentException If parameters are provided but no placeholders exist
     */
    private function ensureParamsUsed(string $sql, array $params): void
    {
        // Check if params are provided but no placeholders exist
        if (!empty($params) && !preg_match('/:\w+/', $sql)) {
            throw new InvalidArgumentException("Query must use named parameters.");
        }
    }

    /**
     * Execute a write query (INSERT/UPDATE/DELETE with expressions)
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}