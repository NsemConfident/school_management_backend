<?php
/**
 * Request Helper Functions
 */

/**
 * Get input from request body (supports JSON, form-data, and x-www-form-urlencoded)
 */
function getJsonInput() {
    // Check Content-Type header
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    // Handle form-data or x-www-form-urlencoded
    if (strpos($contentType, 'multipart/form-data') !== false || 
        strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        // Return $_POST data (form-data and urlencoded are automatically parsed into $_POST)
        return $_POST;
    }
    
    // Handle JSON
    if (strpos($contentType, 'application/json') !== false || empty($contentType)) {
        $json = file_get_contents('php://input');
        
        // If JSON is empty, try $_POST as fallback
        if (empty($json) && !empty($_POST)) {
            return $_POST;
        }
        
        $data = json_decode($json, true);
        
        // If JSON decode fails but we have $_POST, use $_POST
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (!empty($_POST)) {
                return $_POST;
            }
            sendError('Invalid JSON format', 400);
        }
        
        return $data;
    }
    
    // Default: try $_POST first, then JSON
    if (!empty($_POST)) {
        return $_POST;
    }
    
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    
    // Return empty array if no input
    return [];
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get request URI segments
 */
function getUriSegments() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/'));
    
    // Remove 'student_backend' or project folder name if present
    if (isset($uri[0]) && ($uri[0] === 'student_backend' || $uri[0] === 'api')) {
        array_shift($uri);
    }
    
    return $uri;
}
?>

