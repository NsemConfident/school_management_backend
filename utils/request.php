<?php
/**
 * Request Helper Functions
 */

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON format', 400);
    }
    
    return $data;
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

