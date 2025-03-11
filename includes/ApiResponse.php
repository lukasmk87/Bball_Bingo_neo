<?php
/**
 * Basketball Bingo - ApiResponse Class
 * Standardizes API responses and provides input validation
 */
class ApiResponse {
    /**
     * Send a success response
     * 
     * @param array $data Response data
     * @param string $message Success message
     */
    public static function success($data = [], $message = 'Success') {
        return self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        return self::send([
            'status' => 'error',
            'message' => $message
        ]);
    }
    
    /**
     * Send the response as JSON
     * 
     * @param array $response Response data
     */
    private static function send($response) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Validate input data against rules
     * 
     * @param array $rules Validation rules
     * @param array $data Input data
     * @return bool|array True if valid, array of errors if invalid
     */
    public static function validateInput($rules, $data) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            // Required check
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || trim($data[$field]) === '')) {
                $errors[$field] = 'Field is required';
                continue;
            }
            
            // Skip other validations if field is empty and not required
            if (!isset($data[$field]) || $data[$field] === '') {
                continue;
            }
            
            // Email validation
            if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Invalid email format';
            }
            
            // Integer validation
            if (strpos($rule, 'integer') !== false && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                $errors[$field] = 'Must be an integer';
            }
            
            // Min length validation
            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = (int) $matches[1];
                if (strlen($data[$field]) < $min) {
                    $errors[$field] = "Must be at least $min characters";
                }
            }
            
            // Max length validation
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = (int) $matches[1];
                if (strlen($data[$field]) > $max) {
                    $errors[$field] = "Must be at most $max characters";
                }
            }
            
            // Date validation
            if (strpos($rule, 'date') !== false) {
                $date = date_parse($data[$field]);
                if ($date['error_count'] > 0 || $date['warning_count'] > 0) {
                    $errors[$field] = 'Invalid date format';
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Get a single parameter from request
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @param string $type Parameter type (GET, POST, REQUEST)
     * @return mixed Parameter value
     */
    public static function getParam($name, $default = null, $type = 'REQUEST') {
        switch (strtoupper($type)) {
            case 'GET':
                return isset($_GET[$name]) ? $_GET[$name] : $default;
            case 'POST':
                return isset($_POST[$name]) ? $_POST[$name] : $default;
            case 'REQUEST':
            default:
                return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
        }
    }
}