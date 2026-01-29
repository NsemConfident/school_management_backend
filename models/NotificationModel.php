<?php
/**
 * Notification Model
 * Handles notification operations
 */

require_once __DIR__ . '/BaseModel.php';

class NotificationModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('notifications');
    }
    
    /**
     * Get notifications for a user
     */
    public function getByUserId($userId, $unreadOnly = false) {
        $conditions = ['user_id' => $userId];
        if ($unreadOnly) {
            $conditions['is_read'] = 0;
        }
        return $this->getAll($conditions, 'created_at DESC');
    }
    
    /**
     * Get notifications by role
     */
    public function getByRole($role, $unreadOnly = false) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_role = ?";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        return $notifications;
    }
    
    /**
     * Get notifications for a class
     */
    public function getByClassId($classId, $unreadOnly = false) {
        $conditions = ['class_id' => $classId];
        if ($unreadOnly) {
            $conditions['is_read'] = 0;
        }
        return $this->getAll($conditions, 'created_at DESC');
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id, $userId = null) {
        $data = [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        $conditions = ['id' => $id];
        if ($userId) {
            $conditions['user_id'] = $userId;
        }
        
        $sql = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE id = ?";
        if ($userId) {
            $sql .= " AND user_id = ?";
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        if ($userId) {
            $stmt->bind_param('ii', $id, $userId);
        } else {
            $stmt->bind_param('i', $id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }
    
    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = ? AND is_read = 0";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }
    
    /**
     * Create notification for all users in a class
     */
    public function createForClass($classId, $notificationType, $title, $message, $relatedId = null, $relatedType = null) {
        $conn = $this->getConnection();
        
        // Get all students in the class
        $sql = "SELECT u.id FROM users u
                JOIN students s ON u.role_id = s.id
                WHERE s.class_id = ? AND u.role = 'student'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'user_id' => $row['id'],
                'class_id' => $classId,
                'notification_type' => $notificationType,
                'title' => $title,
                'message' => $message,
                'related_id' => $relatedId,
                'related_type' => $relatedType
            ];
        }
        
        // Insert all notifications
        if (!empty($notifications)) {
            $sql = "INSERT INTO {$this->table} (user_id, class_id, notification_type, title, message, related_id, related_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($notifications as $notif) {
                $stmt->bind_param('iisssis', 
                    $notif['user_id'], 
                    $notif['class_id'], 
                    $notif['notification_type'], 
                    $notif['title'], 
                    $notif['message'],
                    $notif['related_id'],
                    $notif['related_type']
                );
                $stmt->execute();
            }
        }
        
        return count($notifications);
    }
}
?>

