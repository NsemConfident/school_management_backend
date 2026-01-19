<?php
/**
 * Student Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/StudentModel.php';

class StudentController extends BaseController {
    public function __construct() {
        $this->model = new StudentModel();
    }
    
    protected function getRequiredFields() {
        return ['first_name', 'last_name', 'email', 'date_of_birth', 'class_id'];
    }
}
?>

