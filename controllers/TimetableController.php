<?php
/**
 * Timetable Controller
 * Handles class timetable operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/TimetableModel.php';
require_once __DIR__ . '/../models/TimetableSlotModel.php';
require_once __DIR__ . '/../models/ClassSubjectModel.php';
require_once __DIR__ . '/../utils/timetable_generator.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class TimetableController extends BaseController {
    private $timetableModel;
    private $slotModel;
    private $notificationModel;
    
    public function __construct() {
        $this->timetableModel = new TimetableModel();
        $this->slotModel = new TimetableSlotModel();
        $this->notificationModel = new NotificationModel();
    }
    
    /**
     * Get all timetables
     */
    public function getAll() {
        $input = getQueryParams();
        $classId = $input['class_id'] ?? null;
        $academicYear = $input['academic_year'] ?? null;
        
        if ($classId) {
            $data = $this->timetableModel->getByClass($classId, $academicYear);
        } else {
            $data = $this->timetableModel->getAll();
        }
        
        sendSuccess($data, 'Timetables retrieved successfully');
    }
    
    /**
     * Get timetable by ID with slots
     */
    public function getById($id) {
        $data = $this->timetableModel->getTimetableWithSlots($id);
        
        if ($data) {
            sendSuccess($data, 'Timetable retrieved successfully');
        } else {
            sendError('Timetable not found', 404);
        }
    }
    
    /**
     * Get active timetable for a class
     */
    public function getActive() {
        $input = getQueryParams();
        $classId = $input['class_id'] ?? null;
        
        if (!$classId) {
            sendError('Class ID is required', 400);
        }
        
        $data = $this->timetableModel->getActiveTimetable($classId);
        
        if ($data) {
            $data = $this->timetableModel->getTimetableWithSlots($data['id']);
            sendSuccess($data, 'Active timetable retrieved successfully');
        } else {
            sendError('No active timetable found for this class', 404);
        }
    }
    
    /**
     * Get student's timetable
     */
    public function getStudentTimetable() {
        $user = getCurrentUser();
        
        if (!$user || $user['role'] !== 'student') {
            sendError('Unauthorized', 401);
        }
        
        // Get student's class
        require_once __DIR__ . '/../models/StudentModel.php';
        $studentModel = new StudentModel();
        $student = $studentModel->getById($user['role_id']);
        
        if (!$student || !$student['class_id']) {
            sendError('Student not assigned to a class', 404);
        }
        
        $timetable = $this->timetableModel->getActiveTimetable($student['class_id']);
        
        if ($timetable) {
            $data = $this->timetableModel->getTimetableWithSlots($timetable['id']);
            sendSuccess($data, 'Timetable retrieved successfully');
        } else {
            sendError('No active timetable found', 404);
        }
    }
    
    /**
     * Create timetable
     */
    public function create() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['class_id', 'academic_year', 'start_date', 'end_date']);
        
        $data = [
            'class_id' => $input['class_id'],
            'academic_year' => $input['academic_year'],
            'semester' => $input['semester'] ?? null,
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'status' => $input['status'] ?? 'draft',
            'created_by' => $user['id']
        ];
        
        $id = $this->timetableModel->create($data);
        
        if ($id) {
            $timetable = $this->timetableModel->getById($id);
            
            // Send notification to class
            $this->notificationModel->createForClass(
                $data['class_id'],
                'timetable_created',
                'New Timetable Created',
                "A new timetable has been created for your class",
                $id,
                'timetable'
            );
            
            sendSuccess($timetable, 'Timetable created successfully', 201);
        } else {
            sendError('Failed to create timetable: ' . $this->timetableModel->getLastError(), 500);
        }
    }
    
    /**
     * Generate timetable automatically
     */
    public function generate() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['class_id', 'academic_year']);
        
        $generator = new TimetableGenerator();
        $result = $generator->generateTimetable(
            $input['class_id'],
            $input['academic_year'],
            $input['semester'] ?? null,
            $input['start_date'] ?? null,
            $input['end_date'] ?? null,
            $user['id']
        );
        
        if ($result['success']) {
            $timetable = $this->timetableModel->getTimetableWithSlots($result['timetable_id']);
            
            // Send notification
            $this->notificationModel->createForClass(
                $input['class_id'],
                'timetable_created',
                'Timetable Generated',
                "A new timetable has been automatically generated for your class",
                $result['timetable_id'],
                'timetable'
            );
            
            sendSuccess([
                'timetable' => $timetable,
                'slots_created' => $result['slots_created'],
                'conflicts' => $result['conflicts']
            ], $result['message'], 201);
        } else {
            sendError($result['message'], 400);
        }
    }
    
    /**
     * Activate timetable
     */
    public function activate($id) {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $timetable = $this->timetableModel->getById($id);
        if (!$timetable) {
            sendError('Timetable not found', 404);
        }
        
        $result = $this->timetableModel->activateTimetable($id);
        
        if ($result) {
            $timetable = $this->timetableModel->getById($id);
            
            // Send notification
            $this->notificationModel->createForClass(
                $timetable['class_id'],
                'timetable_updated',
                'Timetable Activated',
                "The timetable for your class has been activated",
                $id,
                'timetable'
            );
            
            sendSuccess($timetable, 'Timetable activated successfully');
        } else {
            sendError('Failed to activate timetable', 500);
        }
    }
    
    /**
     * Check conflicts for a slot
     */
    public function checkConflicts() {
        $input = getJsonInput();
        validateRequired($input, ['timetable_id', 'day_of_week', 'start_time', 'end_time']);
        
        $conflicts = $this->timetableModel->checkConflicts(
            $input['timetable_id'],
            $input['day_of_week'],
            $input['start_time'],
            $input['end_time'],
            $input['teacher_id'] ?? null,
            $input['room_number'] ?? null,
            $input['slot_id'] ?? null
        );
        
        sendSuccess([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts
        ], 'Conflict check completed');
    }
    
    /**
     * Add slot to timetable
     */
    public function addSlot() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['timetable_id', 'class_subject_id', 'day_of_week', 'start_time', 'end_time']);
        
        // Check for conflicts
        $conflicts = $this->timetableModel->checkConflicts(
            $input['timetable_id'],
            $input['day_of_week'],
            $input['start_time'],
            $input['end_time'],
            $input['teacher_id'] ?? null,
            $input['room_number'] ?? null
        );
        
        if (!empty($conflicts)) {
            sendError('Conflicts detected', 400, ['conflicts' => $conflicts]);
        }
        
        $slotData = [
            'timetable_id' => $input['timetable_id'],
            'class_subject_id' => $input['class_subject_id'],
            'day_of_week' => $input['day_of_week'],
            'start_time' => $input['start_time'],
            'end_time' => $input['end_time'],
            'room_number' => $input['room_number'] ?? null,
            'teacher_id' => $input['teacher_id'] ?? null,
            'notes' => $input['notes'] ?? null
        ];
        
        $slotId = $this->slotModel->create($slotData);
        
        if ($slotId) {
            $slot = $this->slotModel->getById($slotId);
            
            // Get timetable and send notification
            $timetable = $this->timetableModel->getById($input['timetable_id']);
            $this->notificationModel->createForClass(
                $timetable['class_id'],
                'timetable_updated',
                'Timetable Updated',
                "A new slot has been added to your class timetable",
                $input['timetable_id'],
                'timetable'
            );
            
            sendSuccess($slot, 'Slot added successfully', 201);
        } else {
            sendError('Failed to add slot: ' . $this->slotModel->getLastError(), 500);
        }
    }
    
    /**
     * Update slot
     */
    public function updateSlot($slotId) {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        $slot = $this->slotModel->getById($slotId);
        
        if (!$slot) {
            sendError('Slot not found', 404);
        }
        
        // Check for conflicts if time/teacher/room changed
        if (isset($input['day_of_week']) || isset($input['start_time']) || isset($input['end_time']) || 
            isset($input['teacher_id']) || isset($input['room_number'])) {
            
            $dayOfWeek = $input['day_of_week'] ?? $slot['day_of_week'];
            $startTime = $input['start_time'] ?? $slot['start_time'];
            $endTime = $input['end_time'] ?? $slot['end_time'];
            $teacherId = $input['teacher_id'] ?? $slot['teacher_id'];
            $roomNumber = $input['room_number'] ?? $slot['room_number'];
            
            $conflicts = $this->timetableModel->checkConflicts(
                $slot['timetable_id'],
                $dayOfWeek,
                $startTime,
                $endTime,
                $teacherId,
                $roomNumber,
                $slotId
            );
            
            if (!empty($conflicts)) {
                sendError('Conflicts detected', 400, ['conflicts' => $conflicts]);
            }
        }
        
        $result = $this->slotModel->update($slotId, $input);
        
        if ($result) {
            $updatedSlot = $this->slotModel->getById($slotId);
            
            // Send notification
            $timetable = $this->timetableModel->getById($slot['timetable_id']);
            $this->notificationModel->createForClass(
                $timetable['class_id'],
                'timetable_updated',
                'Timetable Updated',
                "A timetable slot has been updated",
                $slot['timetable_id'],
                'timetable'
            );
            
            sendSuccess($updatedSlot, 'Slot updated successfully');
        } else {
            sendError('Failed to update slot', 500);
        }
    }
    
    /**
     * Delete slot
     */
    public function deleteSlot($slotId) {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $slot = $this->slotModel->getById($slotId);
        if (!$slot) {
            sendError('Slot not found', 404);
        }
        
        $result = $this->slotModel->delete($slotId);
        
        if ($result) {
            // Send notification
            $timetable = $this->timetableModel->getById($slot['timetable_id']);
            $this->notificationModel->createForClass(
                $timetable['class_id'],
                'timetable_updated',
                'Timetable Updated',
                "A timetable slot has been removed",
                $slot['timetable_id'],
                'timetable'
            );
            
            sendSuccess(null, 'Slot deleted successfully');
        } else {
            sendError('Failed to delete slot', 500);
        }
    }
    
    protected function getRequiredFields() {
        return ['class_id', 'academic_year', 'start_date', 'end_date'];
    }
}
?>

