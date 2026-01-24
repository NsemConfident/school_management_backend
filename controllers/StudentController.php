<?php
/**
 * Student Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/StudentModel.php';
require_once __DIR__ . '/../models/ClassModel.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/request.php';

class StudentController extends BaseController {
    private $classModel;
    
    public function __construct() {
        $this->model = new StudentModel();
        $this->classModel = new ClassModel();
    }
    
    protected function getRequiredFields() {
        return ['first_name', 'last_name', 'email', 'date_of_birth'];
    }
    
    /**
     * Override create to validate class_id exists if provided
     */
    public function create() {
        $input = getJsonInput();
        validateRequired($input, $this->getRequiredFields());
        
        // Validate class_id exists if provided (it's optional in the database)
        if (isset($input['class_id']) && $input['class_id'] !== null && $input['class_id'] !== '') {
            $classId = (int)$input['class_id'];
            
            // Only validate if class_id is greater than 0 (valid ID)
            if ($classId > 0) {
                $class = $this->classModel->getById($classId);
                
                if (!$class) {
                    sendError("Invalid class_id. Class with ID {$classId} does not exist. Please create the class first using POST /classes, or omit class_id to create a student without a class assignment.", 400);
                }
            }
        }
        
        $sanitized = sanitizeInput($input);
        
        $id = $this->model->create($sanitized);
        
        if ($id) {
            $data = $this->model->getById($id);
            sendSuccess($data, 'Record created successfully', 201);
        } else {
            // Get the actual database error if available
            $errorMsg = 'Failed to create record';
            $dbError = $this->model->getLastError();
            if ($dbError) {
                $errorMsg .= ': ' . $dbError;
            }
            sendError($errorMsg, 500);
        }
    }
}
?>

