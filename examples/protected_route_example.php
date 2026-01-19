<?php
/**
 * Example: How to protect a route with authentication
 * 
 * This file demonstrates how to add authentication to your controllers
 */

require_once __DIR__ . '/../middleware/auth.php';

class ExampleProtectedController {
    
    /**
     * Example: Protected route - requires any authenticated user
     */
    public function protectedMethod() {
        // This will return 401 if not authenticated
        $user = requireAuth();
        
        // Now you can use $user data
        sendSuccess([
            'message' => 'This is a protected route',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ], 'Access granted');
    }
    
    /**
     * Example: Admin-only route
     */
    public function adminOnlyMethod() {
        // This will return 403 if not admin
        $user = requireAdmin();
        
        sendSuccess([
            'message' => 'This is an admin-only route',
            'admin_id' => $user['id']
        ], 'Admin access granted');
    }
    
    /**
     * Example: Role-based access (admin or teacher)
     */
    public function teacherOrAdminMethod() {
        // This will return 403 if not admin or teacher
        $user = requireRole(['admin', 'teacher']);
        
        sendSuccess([
            'message' => 'This route is for teachers and admins',
            'user_role' => $user['role']
        ], 'Access granted');
    }
    
    /**
     * Example: Optional authentication (doesn't fail if not authenticated)
     */
    public function optionalAuthMethod() {
        $user = getCurrentUser();
        
        if ($user) {
            sendSuccess([
                'message' => 'You are logged in',
                'username' => $user['username']
            ], 'Authenticated');
        } else {
            sendSuccess([
                'message' => 'You are not logged in'
            ], 'Guest access');
        }
    }
}
?>

