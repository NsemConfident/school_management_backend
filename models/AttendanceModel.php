<?php
/**
 * Attendance Model
 */

require_once __DIR__ . '/BaseModel.php';

class AttendanceModel extends BaseModel {
    public function __construct() {
        parent::__construct('attendance');
    }
    
    /**
     * Get attendance with student information
     */
    public function getAllWithStudent() {
        $sql = "SELECT a.*, s.first_name, s.last_name, s.email 
                FROM attendance a 
                LEFT JOIN students s ON a.student_id = s.id 
                ORDER BY a.date DESC, a.id DESC";
        
        $result = $this->conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
}
?>

