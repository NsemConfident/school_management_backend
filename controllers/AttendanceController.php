<?php
/**
 * Attendance Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/AttendanceModel.php';

class AttendanceController extends BaseController {
    public function __construct() {
        $this->model = new AttendanceModel();
    }
    
    protected function getRequiredFields() {
        return ['student_id', 'date', 'status'];
    }
}
?>

