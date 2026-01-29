<?php
/**
 * Timetable Slot Model
 * Handles individual time slot operations
 */

require_once __DIR__ . '/BaseModel.php';

class TimetableSlotModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('timetable_slots');
    }
    
    /**
     * Get slots by timetable ID
     */
    public function getByTimetableId($timetableId) {
        return $this->getAll(['timetable_id' => $timetableId], 
            "FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
    }
    
    /**
     * Get slots by day of week
     */
    public function getByDay($timetableId, $dayOfWeek) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE timetable_id = ? AND day_of_week = ?
                ORDER BY start_time";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('is', $timetableId, $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $slots = [];
        while ($row = $result->fetch_assoc()) {
            $slots[] = $row;
        }
        return $slots;
    }
    
    /**
     * Delete all slots for a timetable
     */
    public function deleteByTimetableId($timetableId) {
        $sql = "DELETE FROM {$this->table} WHERE timetable_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bind_param('i', $timetableId);
        return $stmt->execute();
    }
}
?>

