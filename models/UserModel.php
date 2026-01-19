<?php
/**
 * User Model
 */

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel {
    public function __construct() {
        parent::__construct('users');
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE username = ?";
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param('si', $username, $excludeId);
        } else {
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Get user with role details
     */
    public function getUserWithRoleDetails($userId) {
        $user = $this->getById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Get role-specific details
        if ($user['role'] === 'student' && $user['role_id']) {
            $sql = "SELECT s.*, c.class_name, c.grade_level 
                    FROM students s 
                    LEFT JOIN classes c ON s.class_id = c.id 
                    WHERE s.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $user['role_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user['student_details'] = $result->fetch_assoc();
        } elseif ($user['role'] === 'teacher' && $user['role_id']) {
            $sql = "SELECT t.*, s.subject_name 
                    FROM teachers t 
                    LEFT JOIN subjects s ON t.subject_id = s.id 
                    WHERE t.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $user['role_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user['teacher_details'] = $result->fetch_assoc();
        }
        
        // Remove password from response
        unset($user['password']);
        
        return $user;
    }
}
?>

