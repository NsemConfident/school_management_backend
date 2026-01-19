<?php
/**
 * Grade Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/GradeModel.php';

class GradeController extends BaseController {
    public function __construct() {
        $this->model = new GradeModel();
    }
    
    protected function getRequiredFields() {
        return ['student_id', 'subject_id', 'grade', 'exam_type'];
    }
}
?>

