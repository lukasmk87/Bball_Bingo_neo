<?php
/**
 * Basketball Bingo - Configuration File
 * Central location for all application configuration settings
 */

// Error handling in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'd042065d');
define('DB_USER', 'd042065d');
define('DB_PASS', 'RTorVqT7jUabWdDWVEbd');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Basketball Bingo');
define('APP_VERSION', '1.1.0');
define('APP_URL', 'https://example.com'); // Change to your domain

// Session configuration
define('SESSION_LIFETIME', 86400); // 24 hours

// Game settings
define('QUARTERS_DEFAULT', 4);
define('HISTORY_EXPIRY_HOURS', 3); // Games older than 3 hours aren't selectable

// Path settings
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Default SMTP settings (can be overridden in database)
define('SMTP_HOST_DEFAULT', 'smtp.example.com');
define('SMTP_PORT_DEFAULT', 587);
define('SMTP_SECURE_DEFAULT', 'tls');
define('SMTP_USER_DEFAULT', 'noreply@example.com');
define('SMTP_PASS_DEFAULT', 'password');