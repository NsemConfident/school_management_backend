<?php
/**
 * Grade Model
 */

require_once __DIR__ . '/BaseModel.php';

class GradeModel extends BaseModel {
    public function __construct() {
        parent::__construct('grades');
    }
    
    /**
     * Get grades with student and subject information
     */
    public function getAllWithDetails() {
        $sql = "SELECT g.*, s.first_name, s.last_name, s.email as student_email,
                sub.subject_name, sub.subject_code
                FROM grades g 
                LEFT JOIN students s ON g.student_id = s.id 
                LEFT JOIN subjects sub ON g.subject_id = sub.id 
                ORDER BY g.exam_date DESC, g.id DESC";
        
        $result = $this->conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
}
?>

