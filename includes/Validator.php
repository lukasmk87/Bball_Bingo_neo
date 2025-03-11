<?php
/**
 * Basketball Bingo - Validator Class
 * Provides validation and sanitization functions
 */
class Validator {
    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            // Skip if field not present and not required
            if (!isset($data[$field]) && strpos($rule, 'required') === false) {
                continue;
            }
            
            // Required validation
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || trim($data[$field]) === '')) {
                $errors[$field] = 'Dieses Feld ist erforderlich.';
                continue;
            }
            
            // Skip other validations if field is empty
            if (!isset($data[$field]) || $data[$field] === '') {
                continue;
            }
            
            $value = $data[$field];
            
            // Email validation
            if (strpos($rule, 'email') !== false && !self::isValid($value)) {
                $errors[$field] = 'Ungültige E-Mail-Adresse.';
            }
            
            // Username validation
            if (strpos($rule, 'username') !== false && !self::isValidUsername($value)) {
                $errors[$field] = 'Benutzername darf nur Buchstaben, Zahlen und Unterstriche enthalten.';
            }
            
            // Minimum length validation
            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = (int) $matches[1];
                if (strlen($value) < $min) {
                    $errors[$field] = "Muss mindestens $min Zeichen lang sein.";
                }
            }
            
            // Maximum length validation
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = (int) $matches[1];
                if (strlen($value) > $max) {
                    $errors[$field] = "Darf höchstens $max Zeichen lang sein.";
                }
            }
            
            // Numeric validation
            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $errors[$field] = 'Muss eine Zahl sein.';
            }
            
            // Integer validation
            if (strpos($rule, 'integer') !== false && !filter_var($value, FILTER_VALIDATE_INT)) {
                $errors[$field] = 'Muss eine ganze Zahl sein.';
            }
            
            // Date validation
            if (strpos($rule, 'date') !== false) {
                $date = date_parse($value);
                if ($date['error_count'] > 0) {
                    $errors[$field] = 'Ungültiges Datumsformat.';
                }
            }
            
            // URL validation
            if (strpos($rule, 'url') !== false && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[$field] = 'Ungültige URL.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email address
     * @return bool Valid or not
     */
    public static function isValid($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username (letters, numbers, underscores)
     * 
     * @param string $username Username
     * @return bool Valid or not
     */
    public static function isValidUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $username) === 1;
    }
    
    /**
     * Sanitize input (prevent XSS)
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public static function sanitize($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize array of inputs
     * 
     * @param array $inputs Array of inputs
     * @return array Sanitized inputs
     */
    public static function sanitizeArray($inputs) {
        $sanitized = [];
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitize($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Create a safe filename
     * 
     * @param string $filename Original filename
     * @return string Safe filename
     */
    public static function safeFilename($filename) {
        // Remove special characters
        $filename = preg_replace('/[^\w\-\.]+/u', '-', $filename);
        // Remove multiple dashes
        $filename = preg_replace('/-+/', '-', $filename);
        // Trim dashes from start and end
        $filename = trim($filename, '-');
        return $filename;
    }
}