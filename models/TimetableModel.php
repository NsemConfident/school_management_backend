<?php
/**
 * Timetable Model
 * Handles class timetable operations
 */

require_once __DIR__ . '/BaseModel.php';

class TimetableModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('timetables');
    }
    
    /**
     * Get timetable with slots and related data
     */
    public function getTimetableWithSlots($id) {
        $timetable = $this->getById($id);
        if (!$timetable) {
            return null;
        }
        
        $conn = $this->getConnection();
        
        // Get all slots for this timetable
        $sql = "SELECT ts.*, 
                cs.class_id, cs.subject_id, cs.teacher_id as assigned_teacher_id,
                s.subject_name, s.subject_code,
                c.class_name, c.grade_level,
                t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM timetable_slots ts
                JOIN class_subjects cs ON ts.class_subject_id = cs.id
                JOIN subjects s ON cs.subject_id = s.id
                JOIN classes c ON cs.class_id = c.id
                LEFT JOIN teachers t ON ts.teacher_id = t.id
                WHERE ts.timetable_id = ?
                ORDER BY 
                    FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    ts.start_time";
        
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
     * Get timetables by class
     */
    public function getByClass($classId, $academicYear = null) {
        $conditions = ['class_id' => $classId];
        if ($academicYear) {
            $conditions['academic_year'] = $academicYear;
        }
        return $this->getAll($conditions, 'academic_year DESC, start_date DESC');
    }
    
    /**
     * Get active timetable for a class
     */
    public function getActiveTimetable($classId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE class_id = ? AND status = 'active'
                ORDER BY start_date DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Activate a timetable (deactivate others for the same class)
     */
    public function activateTimetable($id) {
        $timetable = $this->getById($id);
        if (!$timetable) {
            return false;
        }
        
        $conn = $this->getConnection();
        
        // Deactivate other timetables for the same class
        $sql = "UPDATE {$this->table} SET status = 'archived' 
                WHERE class_id = ? AND id != ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $timetable['class_id'], $id);
        $stmt->execute();
        
        // Activate this timetable
        return $this->update($id, ['status' => 'active']);
    }
    
    /**
     * Check for conflicts in timetable slots
     */
    public function checkConflicts($timetableId, $dayOfWeek, $startTime, $endTime, $teacherId = null, $roomNumber = null, $excludeSlotId = null) {
        $conn = $this->getConnection();
        $conflicts = [];
        
        // Get timetable to find class_id
        $timetable = $this->getById($timetableId);
        if (!$timetable) {
            return $conflicts;
        }
        
        // Check teacher conflicts
        if ($teacherId) {
            $sql = "SELECT ts.*, t.class_id, c.class_name
                    FROM timetable_slots ts
                    JOIN timetables t ON ts.timetable_id = t.id
                    JOIN classes c ON t.class_id = c.id
                    WHERE ts.teacher_id = ? 
                    AND ts.day_of_week = ?
                    AND ts.timetable_id != ?
                    AND (
                        (ts.start_time < ? AND ts.end_time > ?) OR
                        (ts.start_time < ? AND ts.end_time > ?) OR
                        (ts.start_time >= ? AND ts.end_time <= ?)
                    )";
            
            if ($excludeSlotId) {
                $sql .= " AND ts.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($excludeSlotId) {
                $stmt->bind_param('issstttttti', $teacherId, $dayOfWeek, $timetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeSlotId);
            } else {
                $stmt->bind_param('issstttttt', $teacherId, $dayOfWeek, $timetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'type' => 'teacher',
                    'message' => "Teacher is already assigned to {$row['class_name']} at this time",
                    'data' => $row
                ];
            }
        }
        
        // Check room conflicts
        if ($roomNumber) {
            $sql = "SELECT ts.*, t.class_id, c.class_name
                    FROM timetable_slots ts
                    JOIN timetables t ON ts.timetable_id = t.id
                    JOIN classes c ON t.class_id = c.id
                    WHERE ts.room_number = ? 
                    AND ts.day_of_week = ?
                    AND ts.timetable_id != ?
                    AND (
                        (ts.start_time < ? AND ts.end_time > ?) OR
                        (ts.start_time < ? AND ts.end_time > ?) OR
                        (ts.start_time >= ? AND ts.end_time <= ?)
                    )";
            
            if ($excludeSlotId) {
                $sql .= " AND ts.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($excludeSlotId) {
                $stmt->bind_param('sssstttttti', $roomNumber, $dayOfWeek, $timetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeSlotId);
            } else {
                $stmt->bind_param('sssstttttt', $roomNumber, $dayOfWeek, $timetableId, 
                    $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'type' => 'room',
                    'message' => "Room is already booked by {$row['class_name']} at this time",
                    'data' => $row
                ];
            }
        }
        
        return $conflicts;
    }
}
?>

