<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/request.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $userModel;
    private $conn;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->conn = getDBConnection();
    }
    
    /**
     * User Registration
     */
    public function register() {
        $input = getJsonInput();
        
        // Validate required fields
        validateRequired($input, ['username', 'email', 'password', 'role']);
        
        // Validate role
        $allowedRoles = ['admin', 'student', 'teacher'];
        if (!in_array($input['role'], $allowedRoles)) {
            sendError('Invalid role. Allowed roles: ' . implode(', ', $allowedRoles), 400);
        }
        
        // Validate password strength
        if (strlen($input['password']) < 6) {
            sendError('Password must be at least 6 characters long', 400);
        }
        
        // Check if username exists
        if ($this->userModel->usernameExists($input['username'])) {
            sendError('Username already exists', 409);
        }
        
        // Check if email exists
        if ($this->userModel->emailExists($input['email'])) {
            sendError('Email already exists', 409);
        }
        
        // Validate role_id if provided (must exist in respective table)
        $roleId = null;
        if (isset($input['role_id']) && !empty($input['role_id'])) {
            $roleId = (int)$input['role_id'];
            
            // Only validate if role_id is provided and role is not admin
            if ($input['role'] !== 'admin' && $roleId > 0) {
                if ($input['role'] === 'student') {
                    $checkSql = "SELECT id FROM students WHERE id = ?";
                    $entityName = 'student';
                } elseif ($input['role'] === 'teacher') {
                    $checkSql = "SELECT id FROM teachers WHERE id = ?";
                    $entityName = 'teacher';
                }
                
                if (isset($checkSql)) {
                    $checkStmt = $this->conn->prepare($checkSql);
                    $checkStmt->bind_param('i', $roleId);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    if ($result->num_rows === 0) {
                        sendError("Invalid role_id. {$entityName} with ID {$roleId} not found. Please create the {$entityName} record first or omit role_id to register without linking.", 400);
                    }
                }
            } else {
                // Admin doesn't need role_id, set to null
                $roleId = null;
            }
        }
        
        // Hash password
        $hashedPassword = hashPassword($input['password']);
        
        // Create user
        $userData = [
            'username' => sanitizeInput($input['username']),
            'email' => sanitizeInput($input['email']),
            'password' => $hashedPassword,
            'role' => $input['role'],
            'role_id' => $roleId
        ];
        
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            // Generate token
            $token = createUserToken($userId);
            
            // Get user details
            $user = $this->userModel->getUserWithRoleDetails($userId);
            
            sendSuccess([
                'user' => $user,
                'token' => $token
            ], 'User registered successfully', 201);
        } else {
            sendError('Failed to create user', 500);
        }
    }
    
    /**
     * User Login
     */
    public function login() {
        $input = getJsonInput();
        
        // Validate required fields
        validateRequired($input, ['email', 'password']);
        
        // Find user by email
        $user = $this->userModel->findByEmail($input['email']);
        
        if (!$user) {
            sendError('Invalid email or password', 401);
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            sendError('Account is deactivated', 403);
        }
        
        // Verify password
        if (!verifyPassword($input['password'], $user['password'])) {
            sendError('Invalid email or password', 401);
        }
        
        // Generate token
        $token = createUserToken($user['id']);
        
        // Update last login
        updateLastLogin($user['id']);
        
        // Get user details with role information
        $userDetails = $this->userModel->getUserWithRoleDetails($user['id']);
        
        sendSuccess([
            'user' => $userDetails,
            'token' => $token
        ], 'Login successful');
    }
    
    /**
     * Get current user profile
     */
    public function profile() {
        require_once __DIR__ . '/../middleware/auth.php';
        $user = requireAuth();
        
        $userDetails = $this->userModel->getUserWithRoleDetails($user['id']);
        sendSuccess($userDetails, 'Profile retrieved successfully');
    }
    
    /**
     * Logout
     */
    public function logout() {
        $token = getTokenFromRequest();
        
        if (!$token) {
            sendError('Token required', 400);
        }
        
        if (deleteToken($token)) {
            sendSuccess(null, 'Logged out successfully');
        } else {
            sendError('Failed to logout', 500);
        }
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        require_once __DIR__ . '/../middleware/auth.php';
        $user = requireAuth();
        
        $input = getJsonInput();
        validateRequired($input, ['current_password', 'new_password']);
        
        // Get current user with password
        $currentUser = $this->userModel->getById($user['id']);
        
        // Verify current password
        if (!verifyPassword($input['current_password'], $currentUser['password'])) {
            sendError('Current password is incorrect', 400);
        }
        
        // Validate new password
        if (strlen($input['new_password']) < 6) {
            sendError('New password must be at least 6 characters long', 400);
        }
        
        // Update password
        $hashedPassword = hashPassword($input['new_password']);
        $result = $this->userModel->update($user['id'], ['password' => $hashedPassword]);
        
        if ($result) {
            sendSuccess(null, 'Password changed successfully');
        } else {
            sendError('Failed to change password', 500);
        }
    }
}
?>

