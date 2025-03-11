<?php
/**
 * Basketball Bingo - Auth Class
 * Handles authentication, session management, and security functions
 */
class Auth {
    /**
     * Check if a user is logged in
     * 
     * @return bool True if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user']);
    }
    
    /**
     * Check if the logged-in user is an admin
     * 
     * @return bool True if user is logged in and has admin privileges
     */
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] == 1;
    }
    
    /**
     * Require login for a page, redirect to login if not logged in
     * Saves the current URL for redirect after login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: /login.php");
            exit;
        }
    }
    
    /**
     * Require admin privileges for a page, redirect if not admin
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header("Location: /login.php");
            exit;
        }
    }
    
    /**
     * Log in a user by setting up session data
     * 
     * @param array $user User data from database
     */
    public static function login($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin']
        ];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Update last login timestamp
        $db = Database::getInstance();
        $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    }
    
    /**
     * Log out a user by destroying the session
     */
    public static function logout() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Generate a CSRF token for form protection
     * 
     * @return string The CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify a CSRF token from a form submission
     * 
     * @param string $token The token to verify
     * @return bool True if the token is valid
     * @throws Exception If validation fails
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    /**
     * Create a password hash
     * 
     * @param string $password The password to hash
     * @return string The password hash
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password The password to verify
     * @param string $hash The password hash
     * @return bool True if the password is correct
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate a secure random token
     * 
     * @param int $length The length of the token
     * @return string The generated token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}