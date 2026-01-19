<?php
/**
 * Class Model
 */

require_once __DIR__ . '/BaseModel.php';

class ClassModel extends BaseModel {
    public function __construct() {
        parent::__construct('classes');
    }
    
    /**
     * Get classes with teacher information
     */
    public function getAllWithTeacher() {
        $sql = "SELECT c.*, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
                FROM classes c 
                LEFT JOIN teachers t ON c.teacher_id = t.id 
                ORDER BY c.id DESC";
        
        $result = $this->conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
}
?>

