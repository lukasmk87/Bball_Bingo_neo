<?php
/**
 * Basketball Bingo - Database Class
 * Provides centralized database connection management and query execution
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Private constructor to prevent direct instantiation
     * Creates a PDO database connection with consistent settings
     */
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ];
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Get singleton instance of the Database class
     * 
     * @return Database The Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the PDO connection object
     * 
     * @return PDO The PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return the PDOStatement
     * 
     * @param string $sql The SQL query to execute
     * @param array $params Parameters for the prepared statement
     * @return PDOStatement The PDO statement object
     * @throws Exception If the query fails
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Execute a query and fetch a single row
     * 
     * @param string $sql The SQL query to execute
     * @param array $params Parameters for the prepared statement
     * @return array|false The result row or false if no rows
     * @throws Exception If the query fails
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Execute a query and fetch all rows
     * 
     * @param string $sql The SQL query to execute
     * @param array $params Parameters for the prepared statement
     * @return array The result rows
     * @throws Exception If the query fails
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Execute a query and return the number of affected rows
     * 
     * @param string $sql The SQL query to execute
     * @param array $params Parameters for the prepared statement
     * @return int The number of affected rows
     * @throws Exception If the query fails
     */
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Roll back a transaction
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Check if a transaction is active
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Get the ID of the last inserted row
     * 
     * @return string The last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}