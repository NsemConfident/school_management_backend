<?php
/**
 * Teacher Model
 */

require_once __DIR__ . '/BaseModel.php';

class TeacherModel extends BaseModel {
    public function __construct() {
        parent::__construct('teachers');
    }
    
    /**
     * Get teachers with subject information
     */
    public function getAllWithSubject() {
        $sql = "SELECT t.*, s.subject_name 
                FROM teachers t 
                LEFT JOIN subjects s ON t.subject_id = s.id 
                ORDER BY t.id DESC";
        
        $result = $this->conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
}
?>

