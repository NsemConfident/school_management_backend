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
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    
    // For POST requests, $_POST is automatically populated with form-data
    if ($method === 'POST' && !empty($_POST)) {
        return $_POST;
    }
    
    // Handle x-www-form-urlencoded (works for PUT/PATCH)
    if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        // For PUT/PATCH, parse from php://input
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                parse_str($input, $parsed);
                return $parsed ?: [];
            }
        }
        // For POST, use $_POST
        return $_POST ?: [];
    }
    
    // Handle multipart/form-data
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // For POST, $_POST is automatically populated
        if ($method === 'POST' && !empty($_POST)) {
            return $_POST;
        }
        
        // For PUT/PATCH with multipart/form-data, PHP doesn't populate $_POST automatically
        // This is a limitation - recommend using JSON or x-www-form-urlencoded for PUT/PATCH
        // But we'll try to use $_POST if available (some servers might populate it)
        if (!empty($_POST)) {
            return $_POST;
        }
        
        // Note: Parsing multipart/form-data manually from php://input is complex
        // For PUT/PATCH, it's better to use JSON or x-www-form-urlencoded
        return [];
    }
    
    // Handle JSON (works for all methods)
    if (strpos($contentType, 'application/json') !== false || empty($contentType)) {
        $json = file_get_contents('php://input');
        
        // If JSON is empty, try $_POST as fallback (for POST requests)
        if (empty($json) && !empty($_POST)) {
            return $_POST;
        }
        
        if (empty($json)) {
            return [];
        }
        
        $data = json_decode($json, true);
        
        // If JSON decode fails but we have $_POST, use $_POST
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (!empty($_POST)) {
                return $_POST;
            }
            // Don't error on empty input - let controller handle it
            if (empty($json)) {
                return [];
            }
            sendError('Invalid JSON format', 400);
        }
        
        return $data ?: [];
    }
    
    // Default: try $_POST first, then JSON
    if (!empty($_POST)) {
        return $_POST;
    }
    
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data ?: [];
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

/**
 * Get query parameters
 */
function getQueryParams() {
    return $_GET;
}
?>

