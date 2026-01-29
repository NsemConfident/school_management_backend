<?php
/**
 * Assessment Timetable Model
 * Handles continuous assessment timetable operations
 */

require_once __DIR__ . '/BaseModel.php';

class AssessmentTimetableModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('assessment_timetables');
    }
    
    /**
     * Get assessment timetable with slots
     */
    public function getAssessmentTimetableWithSlots($id) {
        $timetable = $this->getById($id);
        if (!$timetable) {
            return null;
        }
        
        $conn = $this->getConnection();
        
        $sql = "SELECT ats.*, 
                s.subject_name, s.subject_code,
                c.class_name, c.grade_level,
                t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM assessment_timetable_slots ats
                JOIN subjects s ON ats.subject_id = s.id
                LEFT JOIN classes c ON ats.class_id = c.id
                LEFT JOIN teachers t ON ats.teacher_id = t.id
                WHERE ats.assessment_timetable_id = ?
                ORDER BY ats.assessment_date, ats.due_date";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $slots = [];
        while ($row = $result->fetch_assoc()) {
            $slots[] = $row;
        }
        
        $timetable['slots'] = $slots;
        return $timetable;
    }
    
    /**
     * Get assessment timetables by academic year
     */
    public function getByAcademicYear($academicYear, $assessmentType = null) {
        $conditions = ['academic_year' => $academicYear];
        if ($assessmentType) {
            $conditions['assessment_type'] = $assessmentType;
        }
        return $this->getAll($conditions, 'start_date DESC');
    }
    
    /**
     * Get published assessment timetables
     */
    public function getPublished($academicYear = null) {
        $conditions = ['status' => 'published'];
        if ($academicYear) {
            $conditions['academic_year'] = $academicYear;
        }
        return $this->getAll($conditions, 'start_date ASC');
    }
    
    /**
     * Get upcoming assessments for a student
     */
    public function getUpcomingForStudent($studentId, $limit = 10) {
        $conn = $this->getConnection();
        
        // Get student's class
        $sql = "SELECT class_id FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        if (!$student || !$student['class_id']) {
            return [];
        }
        
        $today = date('Y-m-d');
        
        $sql = "SELECT ats.*, 
                at.assessment_name, at.assessment_type,
                s.subject_name, s.subject_code,
                c.class_name
                FROM assessment_timetable_slots ats
                JOIN assessment_timetables at ON ats.assessment_timetable_id = at.id
                JOIN subjects s ON ats.subject_id = s.id
                JOIN classes c ON ats.class_id = c.id
                WHERE ats.class_id = ?
                AND at.status = 'published'
                AND (ats.due_date >= ? OR ats.assessment_date >= ?)
                ORDER BY COALESCE(ats.due_date, ats.assessment_date) ASC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issi', $student['class_id'], $today, $today, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assessments = [];
        while ($row = $result->fetch_assoc()) {
            $assessments[] = $row;
        }
        
        return $assessments;
    }
}
?>

