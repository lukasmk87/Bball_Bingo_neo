<?php
/**
 * Basketball Bingo - Settings Manager
 * Handles retrieving and updating application settings from the database
 */
require_once __DIR__ . '/db.php';

/**
 * Get a setting from the database
 * 
 * @param PDO $pdo - Database connection
 * @param string $name - Setting name
 * @param mixed $default - Default value if setting not found
 * @return mixed - Setting value or default if not found
 */
function getSetting(PDO $pdo, string $name, $default = null) {
    static $settingsCache = [];
    
    // Return from cache if available
    if (array_key_exists($name, $settingsCache)) {
        return $settingsCache[$name];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store in cache and return result or default
        $value = $result ? $result['value'] : $default;
        $settingsCache[$name] = $value;
        return $value;
    } catch (PDOException $e) {
        error_log("Error getting setting '$name': " . $e->getMessage());
        return $default;
    }
}

/**
 * Set a setting in the database
 * 
 * @param PDO $pdo - Database connection
 * @param string $name - Setting name
 * @param mixed $value - Setting value
 * @return bool - Success status
 */
function setSetting(PDO $pdo, string $name, $value) {
    static $settingsCache = [];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (:name, :value)
                              ON DUPLICATE KEY UPDATE value = :value");
        $result = $stmt->execute([':name' => $name, ':value' => $value]);
        
        // Update cache on success
        if ($result) {
            $settingsCache[$name] = $value;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error setting '$name': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings as an associative array
 * 
 * @param PDO $pdo - Database connection
 * @return array - All settings
 */
function getAllSettings(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT name, value FROM settings");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['name']] = $row['value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Error getting all settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if debug mode is enabled
 * 
 * @param PDO $pdo - Database connection
 * @return bool - Whether debug mode is enabled
 */
function isDebugMode(PDO $pdo) {
    return getSetting($pdo, 'debug_mode') === '1';
}

/**
 * Get SMTP configuration from settings
 * 
 * @param PDO $pdo - Database connection
 * @return array - SMTP configuration
 */
function getSmtpConfig(PDO $pdo) {
    return [
        'host' => getSetting($pdo, 'smtp_host', SMTP_HOST_DEFAULT),
        'port' => getSetting($pdo, 'smtp_port', SMTP_PORT_DEFAULT),
        'username' => getSetting($pdo, 'smtp_username', SMTP_USER_DEFAULT),
        'password' => getSetting($pdo, 'smtp_password', SMTP_PASS_DEFAULT),
        'secure' => getSetting($pdo, 'smtp_secure', SMTP_SECURE_DEFAULT)
    ];
}