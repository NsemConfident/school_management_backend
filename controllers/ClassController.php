<?php
/**
 * Class Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ClassModel.php';

class ClassController extends BaseController {
    public function __construct() {
        $this->model = new ClassModel();
    }
    
    protected function getRequiredFields() {
        return ['class_name', 'grade_level'];
    }
}
?>

