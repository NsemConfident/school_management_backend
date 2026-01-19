<?php
/**
 * Subject Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/SubjectModel.php';

class SubjectController extends BaseController {
    public function __construct() {
        $this->model = new SubjectModel();
    }
    
    protected function getRequiredFields() {
        return ['subject_name', 'subject_code'];
    }
}
?>

