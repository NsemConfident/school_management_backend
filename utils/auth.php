<?php
/**
 * Authentication Utilities
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Create user token and store in database
 */
function createUserToken($userId) {
    $conn = getDBConnection();
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days')); // Token expires in 7 days
    
    // Delete old tokens for this user
    $deleteSql = "DELETE FROM user_tokens WHERE user_id = ? OR expires_at < NOW()";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param('i', $userId);
    $deleteStmt->execute();
    
    // Insert new token
    $sql = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $userId, $token, $expiresAt);
    
    if ($stmt->execute()) {
        return $token;
    }
    
    return false;
}

/**
 * Validate token and get user
 */
function validateToken($token) {
    if (empty($token)) {
        return null;
    }
    
    $conn = getDBConnection();
    $sql = "SELECT ut.user_id, u.id, u.username, u.email, u.role, u.role_id, u.is_active 
            FROM user_tokens ut 
            INNER JOIN users u ON ut.user_id = u.id 
            WHERE ut.token = ? AND ut.expires_at > NOW() AND u.is_active = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Delete token (logout)
 */
function deleteToken($token) {
    $conn = getDBConnection();
    $sql = "DELETE FROM user_tokens WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    return $stmt->execute();
}

/**
 * Get token from request headers
 */
function getTokenFromRequest() {
    // getallheaders() might not be available in all PHP environments
    // Fallback to $_SERVER if needed
    $headers = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        // Fallback for environments where getallheaders() is not available
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }
    
    // Check Authorization header
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
    }
    
    // Check custom header (case-insensitive)
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-auth-token') {
            return $value;
        }
    }
    
    // Check query parameter (for testing)
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }
    
    return null;
}

/**
 * Update last login time
 */
function updateLastLogin($userId) {
    $conn = getDBConnection();
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}
?>

