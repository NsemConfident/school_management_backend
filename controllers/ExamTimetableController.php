<?php
/**
 * Exam Timetable Controller
 * Handles exam timetable operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ExamTimetableModel.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../config/database.php';

class ExamTimetableController extends BaseController {
    private $examTimetableModel;
    private $notificationModel;
    private $conn;
    
    public function __construct() {
        $this->examTimetableModel = new ExamTimetableModel();
        $this->notificationModel = new NotificationModel();
        $this->conn = getDBConnection();
    }
    
    /**
     * Get all exam timetables
     */
    public function getAll() {
        $input = getQueryParams();
        $academicYear = $input['academic_year'] ?? null;
        $examType = $input['exam_type'] ?? null;
        $status = $input['status'] ?? null;
        
        if ($status === 'published') {
            $data = $this->examTimetableModel->getPublished($academicYear);
        } elseif ($academicYear) {
            $data = $this->examTimetableModel->getByAcademicYear($academicYear, $examType);
        } else {
            $data = $this->examTimetableModel->getAll();
        }
        
        sendSuccess($data, 'Exam timetables retrieved successfully');
    }
    
    /**
     * Get exam timetable by ID with slots
     */
    public function getById($id) {
        $data = $this->examTimetableModel->getExamTimetableWithSlots($id);
        
        if ($data) {
            sendSuccess($data, 'Exam timetable retrieved successfully');
        } else {
            sendError('Exam timetable not found', 404);
        }
    }
    
    /**
     * Get student's exam timetable
     */
    public function getStudentExams() {
        $user = getCurrentUser();
        
        if (!$user || $user['role'] !== 'student') {
            sendError('Unauthorized', 401);
        }
        
        require_once __DIR__ . '/../models/StudentModel.php';
        $studentModel = new StudentModel();
        $student = $studentModel->getById($user['role_id']);
        
        if (!$student || !$student['class_id']) {
            sendError('Student not assigned to a class', 404);
        }
        
        $input = getQueryParams();
        $academicYear = $input['academic_year'] ?? date('Y');
        
        // Get published exam timetables
        $examTimetables = $this->examTimetableModel->getPublished($academicYear);
        
        // Filter slots for student's class
        $studentExams = [];
        foreach ($examTimetables as $timetable) {
            $fullTimetable = $this->examTimetableModel->getExamTimetableWithSlots($timetable['id']);
            if ($fullTimetable && isset($fullTimetable['slots'])) {
                $classSlots = array_filter($fullTimetable['slots'], function($slot) use ($student) {
                    return $slot['class_id'] == $student['class_id'];
                });
                if (!empty($classSlots)) {
                    $fullTimetable['slots'] = array_values($classSlots);
                    $studentExams[] = $fullTimetable;
                }
            }
        }
        
        sendSuccess($studentExams, 'Student exam timetable retrieved successfully');
    }
    
    /**
     * Create exam timetable
     */
    public function create() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['exam_name', 'exam_type', 'academic_year', 'start_date', 'end_date']);
        
        $data = [
            'exam_name' => $input['exam_name'],
            'exam_type' => $input['exam_type'],
            'academic_year' => $input['academic_year'],
            'semester' => $input['semester'] ?? null,
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'status' => $input['status'] ?? 'draft',
            'created_by' => $user['id']
        ];
        
        $id = $this->examTimetableModel->create($data);
        
        if ($id) {
            $timetable = $this->examTimetableModel->getById($id);
            sendSuccess($timetable, 'Exam timetable created successfully', 201);
        } else {
            sendError('Failed to create exam timetable: ' . $this->examTimetableModel->getLastError(), 500);
        }
    }
    
    /**
     * Publish exam timetable
     */
    public function publish($id) {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $timetable = $this->examTimetableModel->getById($id);
        if (!$timetable) {
            sendError('Exam timetable not found', 404);
        }
        
        $result = $this->examTimetableModel->update($id, ['status' => 'published']);
        
        if ($result) {
            // Send notifications to all students
            $this->sendExamNotifications($id);
            
            $timetable = $this->examTimetableModel->getById($id);
            sendSuccess($timetable, 'Exam timetable published successfully');
        } else {
            sendError('Failed to publish exam timetable', 500);
        }
    }
    
    /**
     * Add exam slot
     */
    public function addSlot() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['exam_timetable_id', 'subject_id', 'exam_date', 'start_time', 'end_time']);
        
        // Check for conflicts
        $conflicts = $this->examTimetableModel->checkConflicts(
            $input['exam_timetable_id'],
            $input['exam_date'],
            $input['start_time'],
            $input['end_time'],
            $input['class_id'] ?? null,
            $input['room_number'] ?? null,
            $input['invigilator_id'] ?? null
        );
        
        if (!empty($conflicts)) {
            sendError('Conflicts detected', 400, ['conflicts' => $conflicts]);
        }
        
        $slotData = [
            'exam_timetable_id' => $input['exam_timetable_id'],
            'subject_id' => $input['subject_id'],
            'class_id' => $input['class_id'] ?? null,
            'exam_date' => $input['exam_date'],
            'start_time' => $input['start_time'],
            'end_time' => $input['end_time'],
            'room_number' => $input['room_number'] ?? null,
            'invigilator_id' => $input['invigilator_id'] ?? null,
            'max_students' => $input['max_students'] ?? null,
            'notes' => $input['notes'] ?? null
        ];
        
        $sql = "INSERT INTO exam_timetable_slots 
                (exam_timetable_id, subject_id, class_id, exam_date, start_time, end_time, room_number, invigilator_id, max_students, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiissssiis',
            $slotData['exam_timetable_id'],
            $slotData['subject_id'],
            $slotData['class_id'],
            $slotData['exam_date'],
            $slotData['start_time'],
            $slotData['end_time'],
            $slotData['room_number'],
            $slotData['invigilator_id'],
            $slotData['max_students'],
            $slotData['notes']
        );
        
        if ($stmt->execute()) {
            $slotId = $this->conn->insert_id;
            $slot = $this->getSlotById($slotId);
            sendSuccess($slot, 'Exam slot added successfully', 201);
        } else {
            sendError('Failed to add exam slot: ' . $stmt->error, 500);
        }
    }
    
    /**
     * Check conflicts for exam slot
     */
    public function checkConflicts() {
        $input = getJsonInput();
        validateRequired($input, ['exam_timetable_id', 'exam_date', 'start_time', 'end_time']);
        
        $conflicts = $this->examTimetableModel->checkConflicts(
            $input['exam_timetable_id'],
            $input['exam_date'],
            $input['start_time'],
            $input['end_time'],
            $input['class_id'] ?? null,
            $input['room_number'] ?? null,
            $input['invigilator_id'] ?? null,
            $input['slot_id'] ?? null
        );
        
        sendSuccess([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts
        ], 'Conflict check completed');
    }
    
    /**
     * Send notifications for exam timetable
     */
    private function sendExamNotifications($examTimetableId) {
        $timetable = $this->examTimetableModel->getExamTimetableWithSlots($examTimetableId);
        
        if (!$timetable || !isset($timetable['slots'])) {
            return;
        }
        
        // Group slots by class
        $classSlots = [];
        foreach ($timetable['slots'] as $slot) {
            if ($slot['class_id']) {
                if (!isset($classSlots[$slot['class_id']])) {
                    $classSlots[$slot['class_id']] = [];
                }
                $classSlots[$slot['class_id']][] = $slot;
            }
        }
        
        // Send notification to each class
        foreach ($classSlots as $classId => $slots) {
            $this->notificationModel->createForClass(
                $classId,
                'exam_scheduled',
                'Exam Timetable Published',
                "The {$timetable['exam_name']} timetable has been published. You have " . count($slots) . " exam(s) scheduled.",
                $examTimetableId,
                'exam_timetable'
            );
        }
    }
    
    /**
     * Get slot by ID
     */
    private function getSlotById($id) {
        $sql = "SELECT * FROM exam_timetable_slots WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    protected function getRequiredFields() {
        return ['exam_name', 'exam_type', 'academic_year', 'start_date', 'end_date'];
    }
}
?>

