<?php
/**
 * Exam Timetable Model
 * Handles exam timetable operations
 */

require_once __DIR__ . '/BaseModel.php';

class ExamTimetableModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('exam_timetables');
    }
    
    /**
     * Get exam timetable with slots
     */
    public function getExamTimetableWithSlots($id) {
        $timetable = $this->getById($id);
        if (!$timetable) {
            return null;
        }
        
        $conn = $this->getConnection();
        
        $sql = "SELECT ets.*, 
                s.subject_name, s.subject_code,
                c.class_name, c.grade_level,
                t.first_name as invigilator_first_name, t.last_name as invigilator_last_name
                FROM exam_timetable_slots ets
                JOIN subjects s ON ets.subject_id = s.id
                LEFT JOIN classes c ON ets.class_id = c.id
                LEFT JOIN teachers t ON ets.invigilator_id = t.id
                WHERE ets.exam_timetable_id = ?
                ORDER BY ets.exam_date, ets.start_time";
        
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
     * Get exam timetables by academic year
     */
    public function getByAcademicYear($academicYear, $examType = null) {
        $conditions = ['academic_year' => $academicYear];
        if ($examType) {
            $conditions['exam_type'] = $examType;
        }
        return $this->getAll($conditions, 'start_date DESC');
    }
    
    /**
     * Get published exam timetables
     */
    public function getPublished($academicYear = null) {
        $conditions = ['status' => 'published'];
        if ($academicYear) {
            $conditions['academic_year'] = $academicYear;
        }
        return $this->getAll($conditions, 'start_date ASC');
    }
    
    /**
     * Check for conflicts in exam slots
     */
    public function checkConflicts($examTimetableId, $examDate, $startTime, $endTime, $classId = null, $roomNumber = null, $invigilatorId = null, $excludeSlotId = null) {
        $conn = $this->getConnection();
        $conflicts = [];
        
        // Check class conflicts (same class having multiple exams at same time)
        if ($classId) {
            $sql = "SELECT ets.*, s.subject_name
                    FROM exam_timetable_slots ets
                    JOIN subjects s ON ets.subject_id = s.id
                    WHERE ets.class_id = ? 
                    AND ets.exam_date = ?
                    AND ets.exam_timetable_id != ?
                    AND (
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time >= ? AND ets.end_time <= ?)
                    )";
            
            if ($excludeSlotId) {
                $sql .= " AND ets.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($excludeSlotId) {
                $stmt->bind_param('issstttttti', $classId, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeSlotId);
            } else {
                $stmt->bind_param('issstttttt', $classId, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'type' => 'class',
                    'message' => "Class already has exam for {$row['subject_name']} at this time",
                    'data' => $row
                ];
            }
        }
        
        // Check room conflicts
        if ($roomNumber) {
            $sql = "SELECT ets.*, s.subject_name, c.class_name
                    FROM exam_timetable_slots ets
                    JOIN subjects s ON ets.subject_id = s.id
                    LEFT JOIN classes c ON ets.class_id = c.id
                    WHERE ets.room_number = ? 
                    AND ets.exam_date = ?
                    AND ets.exam_timetable_id != ?
                    AND (
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time >= ? AND ets.end_time <= ?)
                    )";
            
            if ($excludeSlotId) {
                $sql .= " AND ets.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($excludeSlotId) {
                $stmt->bind_param('sssstttttti', $roomNumber, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeSlotId);
            } else {
                $stmt->bind_param('sssstttttt', $roomNumber, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'type' => 'room',
                    'message' => "Room is already booked for {$row['subject_name']} ({$row['class_name']}) at this time",
                    'data' => $row
                ];
            }
        }
        
        // Check invigilator conflicts
        if ($invigilatorId) {
            $sql = "SELECT ets.*, s.subject_name, c.class_name
                    FROM exam_timetable_slots ets
                    JOIN subjects s ON ets.subject_id = s.id
                    LEFT JOIN classes c ON ets.class_id = c.id
                    WHERE ets.invigilator_id = ? 
                    AND ets.exam_date = ?
                    AND ets.exam_timetable_id != ?
                    AND (
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time < ? AND ets.end_time > ?) OR
                        (ets.start_time >= ? AND ets.end_time <= ?)
                    )";
            
            if ($excludeSlotId) {
                $sql .= " AND ets.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($excludeSlotId) {
                $stmt->bind_param('issstttttti', $invigilatorId, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeSlotId);
            } else {
                $stmt->bind_param('issstttttt', $invigilatorId, $examDate, $examTimetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'type' => 'invigilator',
                    'message' => "Invigilator is already assigned to {$row['subject_name']} ({$row['class_name']}) at this time",
                    'data' => $row
                ];
            }
        }
        
        return $conflicts;
    }
}
?>

