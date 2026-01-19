<?php
/**
 * Authentication Middleware
 */

require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * Require authentication
 */
function requireAuth() {
    $token = getTokenFromRequest();
    
    if (!$token) {
        sendError('Authentication token required', 401);
    }
    
    $user = validateToken($token);
    
    if (!$user) {
        sendError('Invalid or expired token', 401);
    }
    
    return $user;
}

/**
 * Require specific role
 */
function requireRole($allowedRoles) {
    $user = requireAuth();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($user['role'], $allowedRoles)) {
        sendError('Insufficient permissions', 403);
    }
    
    return $user;
}

/**
 * Require admin role
 */
function requireAdmin() {
    return requireRole(['admin']);
}

/**
 * Get current user (optional, doesn't fail if not authenticated)
 */
function getCurrentUser() {
    $token = getTokenFromRequest();
    
    if (!$token) {
        return null;
    }
    
    return validateToken($token);
}
?>

