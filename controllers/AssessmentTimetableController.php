<?php
/**
 * Assessment Timetable Controller
 * Handles continuous assessment timetable operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/AssessmentTimetableModel.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../config/database.php';

class AssessmentTimetableController extends BaseController {
    private $assessmentTimetableModel;
    private $notificationModel;
    private $conn;
    
    public function __construct() {
        $this->assessmentTimetableModel = new AssessmentTimetableModel();
        $this->notificationModel = new NotificationModel();
        $this->conn = getDBConnection();
    }
    
    /**
     * Get all assessment timetables
     */
    public function getAll() {
        $input = getQueryParams();
        $academicYear = $input['academic_year'] ?? null;
        $assessmentType = $input['assessment_type'] ?? null;
        $status = $input['status'] ?? null;
        
        if ($status === 'published') {
            $data = $this->assessmentTimetableModel->getPublished($academicYear);
        } elseif ($academicYear) {
            $data = $this->assessmentTimetableModel->getByAcademicYear($academicYear, $assessmentType);
        } else {
            $data = $this->assessmentTimetableModel->getAll();
        }
        
        sendSuccess($data, 'Assessment timetables retrieved successfully');
    }
    
    /**
     * Get assessment timetable by ID with slots
     */
    public function getById($id) {
        $data = $this->assessmentTimetableModel->getAssessmentTimetableWithSlots($id);
        
        if ($data) {
            sendSuccess($data, 'Assessment timetable retrieved successfully');
        } else {
            sendError('Assessment timetable not found', 404);
        }
    }
    
    /**
     * Get student's upcoming assessments
     */
    public function getStudentAssessments() {
        $user = getCurrentUser();
        
        if (!$user || $user['role'] !== 'student') {
            sendError('Unauthorized', 401);
        }
        
        $input = getQueryParams();
        $limit = $input['limit'] ?? 10;
        
        $assessments = $this->assessmentTimetableModel->getUpcomingForStudent($user['role_id'], $limit);
        
        sendSuccess($assessments, 'Upcoming assessments retrieved successfully');
    }
    
    /**
     * Create assessment timetable
     */
    public function create() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['assessment_name', 'assessment_type', 'academic_year', 'start_date', 'end_date']);
        
        $data = [
            'assessment_name' => $input['assessment_name'],
            'assessment_type' => $input['assessment_type'],
            'academic_year' => $input['academic_year'],
            'semester' => $input['semester'] ?? null,
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'status' => $input['status'] ?? 'draft',
            'created_by' => $user['id']
        ];
        
        $id = $this->assessmentTimetableModel->create($data);
        
        if ($id) {
            $timetable = $this->assessmentTimetableModel->getById($id);
            sendSuccess($timetable, 'Assessment timetable created successfully', 201);
        } else {
            sendError('Failed to create assessment timetable: ' . $this->assessmentTimetableModel->getLastError(), 500);
        }
    }
    
    /**
     * Publish assessment timetable
     */
    public function publish($id) {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $timetable = $this->assessmentTimetableModel->getById($id);
        if (!$timetable) {
            sendError('Assessment timetable not found', 404);
        }
        
        $result = $this->assessmentTimetableModel->update($id, ['status' => 'published']);
        
        if ($result) {
            // Send notifications
            $this->sendAssessmentNotifications($id);
            
            $timetable = $this->assessmentTimetableModel->getById($id);
            sendSuccess($timetable, 'Assessment timetable published successfully');
        } else {
            sendError('Failed to publish assessment timetable', 500);
        }
    }
    
    /**
     * Add assessment slot
     */
    public function addSlot() {
        $user = getCurrentUser();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'teacher')) {
            sendError('Unauthorized', 401);
        }
        
        $input = getJsonInput();
        validateRequired($input, ['assessment_timetable_id', 'subject_id', 'assessment_date']);
        
        $slotData = [
            'assessment_timetable_id' => $input['assessment_timetable_id'],
            'subject_id' => $input['subject_id'],
            'class_id' => $input['class_id'] ?? null,
            'assessment_date' => $input['assessment_date'],
            'due_date' => $input['due_date'] ?? null,
            'start_time' => $input['start_time'] ?? null,
            'end_time' => $input['end_time'] ?? null,
            'room_number' => $input['room_number'] ?? null,
            'teacher_id' => $input['teacher_id'] ?? null,
            'max_students' => $input['max_students'] ?? null,
            'instructions' => $input['instructions'] ?? null
        ];
        
        $sql = "INSERT INTO assessment_timetable_slots 
                (assessment_timetable_id, subject_id, class_id, assessment_date, due_date, start_time, end_time, room_number, teacher_id, max_students, instructions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiisssssiis',
            $slotData['assessment_timetable_id'],
            $slotData['subject_id'],
            $slotData['class_id'],
            $slotData['assessment_date'],
            $slotData['due_date'],
            $slotData['start_time'],
            $slotData['end_time'],
            $slotData['room_number'],
            $slotData['teacher_id'],
            $slotData['max_students'],
            $slotData['instructions']
        );
        
        if ($stmt->execute()) {
            $slotId = $this->conn->insert_id;
            $slot = $this->getSlotById($slotId);
            
            // Send notification if due date is set
            if ($slotData['due_date'] && $slotData['class_id']) {
                $this->notificationModel->createForClass(
                    $slotData['class_id'],
                    'assessment_due',
                    'New Assessment Assigned',
                    "A new assessment has been assigned. Due date: " . $slotData['due_date'],
                    $slotData['assessment_timetable_id'],
                    'assessment_timetable'
                );
            }
            
            sendSuccess($slot, 'Assessment slot added successfully', 201);
        } else {
            sendError('Failed to add assessment slot: ' . $stmt->error, 500);
        }
    }
    
    /**
     * Send notifications for assessment timetable
     */
    private function sendAssessmentNotifications($assessmentTimetableId) {
        $timetable = $this->assessmentTimetableModel->getAssessmentTimetableWithSlots($assessmentTimetableId);
        
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
                'assessment_due',
                'Assessment Timetable Published',
                "The {$timetable['assessment_name']} timetable has been published. You have " . count($slots) . " assessment(s) assigned.",
                $assessmentTimetableId,
                'assessment_timetable'
            );
        }
    }
    
    /**
     * Get slot by ID
     */
    private function getSlotById($id) {
        $sql = "SELECT * FROM assessment_timetable_slots WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    protected function getRequiredFields() {
        return ['assessment_name', 'assessment_type', 'academic_year', 'start_date', 'end_date'];
    }
}
?>

