<?php
/**
 * Student Model
 */

require_once __DIR__ . '/BaseModel.php';

class StudentModel extends BaseModel {
    public function __construct() {
        parent::__construct('students');
    }
    
    /**
     * Get students with class information
     */
    public function getAllWithClass() {
        $sql = "SELECT s.*, c.class_name, c.grade_level 
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                ORDER BY s.id DESC";
        
        $result = $this->conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
}
?>

