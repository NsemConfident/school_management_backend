<?php
/**
 * Class Subject Model
 * Handles class-subject relationships
 */

require_once __DIR__ . '/BaseModel.php';

class ClassSubjectModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('class_subjects');
    }
    
    /**
     * Get subjects for a class
     */
    public function getSubjectsByClass($classId) {
        $conn = $this->getConnection();
        
        $sql = "SELECT cs.*, 
                s.subject_name, s.subject_code, s.description,
                t.id as teacher_id, t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM {$this->table} cs
                JOIN subjects s ON cs.subject_id = s.id
                LEFT JOIN teachers t ON cs.teacher_id = t.id
                WHERE cs.class_id = ?
                ORDER BY s.subject_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        return $subjects;
    }
    
    /**
     * Get classes for a subject
     */
    public function getClassesBySubject($subjectId) {
        $conn = $this->getConnection();
        
        $sql = "SELECT cs.*, 
                c.class_name, c.grade_level, c.room_number,
                t.id as teacher_id, t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM {$this->table} cs
                JOIN classes c ON cs.class_id = c.id
                LEFT JOIN teachers t ON cs.teacher_id = t.id
                WHERE cs.subject_id = ?
                ORDER BY c.grade_level, c.class_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        
        return $classes;
    }
}
?>

