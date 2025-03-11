<?php
/**
 * Basketball Bingo - Settings Class
 * Manages application settings with efficient caching
 */
class Settings {
    private static $settings = null;
    
    /**
     * Initialize settings if not already loaded
     */
    public static function init() {
        if (self::$settings === null) {
            self::loadSettings();
        }
    }
    
    /**
     * Load all settings from database
     */
    private static function loadSettings() {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT name, value FROM settings");
        
        self::$settings = [];
        foreach ($rows as $row) {
            self::$settings[$row['name']] = $row['value'];
        }
    }
    
    /**
     * Get a setting value
     * 
     * @param string $name Setting name
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value or default
     */
    public static function get($name, $default = null) {
        self::init();
        return isset(self::$settings[$name]) ? self::$settings[$name] : $default;
    }
    
    /**
     * Set a setting value
     * 
     * @param string $name Setting name
     * @param mixed $value Setting value
     * @return bool Success status
     */
    public static function set($name, $value) {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?",
            [$name, $value, $value]
        );
        
        // Update cache
        self::init();
        self::$settings[$name] = $value;
        return true;
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool Debug mode status
     */
    public static function isDebugMode() {
        return self::get('debug_mode') === '1';
    }
    
    /**
     * Get SMTP configuration
     * 
     * @return array SMTP configuration
     */
    public static function getSmtpConfig() {
        return [
            'host' => self::get('smtp_host', SMTP_HOST_DEFAULT),
            'port' => self::get('smtp_port', SMTP_PORT_DEFAULT),
            'username' => self::get('smtp_username', SMTP_USER_DEFAULT),
            'password' => self::get('smtp_password', SMTP_PASS_DEFAULT),
            'secure' => self::get('smtp_secure', SMTP_SECURE_DEFAULT)
        ];
    }
    
    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public static function getAll() {
        self::init();
        return self::$settings;
    }
    
    /**
     * Delete a setting
     * 
     * @param string $name Setting name
     * @return bool Success status
     */
    public static function delete($name) {
        $db = Database::getInstance();
        $result = $db->execute("DELETE FROM settings WHERE name = ?", [$name]);
        
        // Update cache
        if ($result && isset(self::$settings[$name])) {
            self::init();
            unset(self::$settings[$name]);
        }
        
        return $result > 0;
    }
}