<?php
/**
 * Basketball Bingo - Database Connection
 * Creates and manages the PDO database connection
 */
require_once __DIR__ . '/config.php';

/**
 * Get a PDO database connection with consistent configuration
 * @return PDO - The database connection
 * @throws PDOException - If connection fails
 */
function getDbConnection() {
    static $pdo = null;
    
    // Return existing connection if available
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // DSN with charset specification
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Connection options for better security and consistency
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ];
        
        // Create connection
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
    } catch (PDOException $e) {
        // Log error instead of exposing details (in production)
        error_log("Database connection error: " . $e->getMessage());
        
        // In development, you might want to see the error
        // Uncomment for development, comment out for production:
        throw new PDOException("Database connection failed: " . $e->getMessage());
        
        // For production, use a generic message
        // throw new PDOException("Database connection failed. Please try again later.");
    }
}

// Initialize the database connection
try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    // Handle the error appropriately based on context
    if (php_sapi_name() === 'cli') {
        // Command line
        echo "Database error: " . $e->getMessage() . PHP_EOL;
        exit(1);
    } else {
        // Web request
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            // Admin area - show detailed error
            die("Database error: " . htmlspecialchars($e->getMessage()));
        } else {
            // Public area - show friendly message
            die("Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.");
        }
    }
}