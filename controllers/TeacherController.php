<?php
/**
 * Teacher Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/TeacherModel.php';

class TeacherController extends BaseController {
    public function __construct() {
        $this->model = new TeacherModel();
    }
    
    protected function getRequiredFields() {
        return ['first_name', 'last_name', 'email'];
    }
}
?>
