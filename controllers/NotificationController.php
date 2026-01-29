<?php
/**
 * Notification Controller
 * Handles notification operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../utils/auth.php';

class NotificationController extends BaseController {
    private $notificationModel;
    
    public function __construct() {
        $this->notificationModel = new NotificationModel();
    }
    
    /**
     * Get user's notifications
     */
    public function getMyNotifications() {
        $user = getCurrentUser();
        
        if (!$user) {
            sendError('Unauthorized', 401);
        }
        
        $input = getQueryParams();
        $unreadOnly = isset($input['unread_only']) && $input['unread_only'] == '1';
        
        $notifications = $this->notificationModel->getByUserId($user['id'], $unreadOnly);
        
        sendSuccess($notifications, 'Notifications retrieved successfully');
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount() {
        $user = getCurrentUser();
        
        if (!$user) {
            sendError('Unauthorized', 401);
        }
        
        $count = $this->notificationModel->getUnreadCount($user['id']);
        
        sendSuccess(['count' => $count], 'Unread count retrieved successfully');
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id) {
        $user = getCurrentUser();
        
        if (!$user) {
            sendError('Unauthorized', 401);
        }
        
        $result = $this->notificationModel->markAsRead($id, $user['id']);
        
        if ($result) {
            sendSuccess(null, 'Notification marked as read');
        } else {
            sendError('Failed to mark notification as read', 500);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        $user = getCurrentUser();
        
        if (!$user) {
            sendError('Unauthorized', 401);
        }
        
        $result = $this->notificationModel->markAllAsRead($user['id']);
        
        if ($result) {
            sendSuccess(null, 'All notifications marked as read');
        } else {
            sendError('Failed to mark notifications as read', 500);
        }
    }
    
    /**
     * Get notifications by role (admin only)
     */
    public function getByRole() {
        $user = getCurrentUser();
        
        if (!$user || $user['role'] !== 'admin') {
            sendError('Unauthorized', 401);
        }
        
        $input = getQueryParams();
        $role = $input['role'] ?? null;
        $unreadOnly = isset($input['unread_only']) && $input['unread_only'] == '1';
        
        if (!$role) {
            sendError('Role is required', 400);
        }
        
        $notifications = $this->notificationModel->getByRole($role, $unreadOnly);
        
        sendSuccess($notifications, 'Notifications retrieved successfully');
    }
    
    /**
     * Create notification (admin/teacher only)
     */
    public function create() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['notification_type', 'title', 'message']);
        
        $data = [
            'user_id' => $input['user_id'] ?? null,
            'user_role' => $input['user_role'] ?? 'all',
            'class_id' => $input['class_id'] ?? null,
            'notification_type' => $input['notification_type'],
            'title' => $input['title'],
            'message' => $input['message'],
            'related_id' => $input['related_id'] ?? null,
            'related_type' => $input['related_type'] ?? null
        ];
        
        $id = $this->notificationModel->create($data);
        
        if ($id) {
            $notification = $this->notificationModel->getById($id);
            sendSuccess($notification, 'Notification created successfully', 201);
        } else {
            sendError('Failed to create notification: ' . $this->notificationModel->getLastError(), 500);
        }
    }
    
    protected function getRequiredFields() {
        return ['notification_type', 'title', 'message'];
    }
}
?>

