<?php
/**
 * Base Controller Class
 */

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/request.php';

class BaseController {
    protected $model;
    
    public function getAll() {
        $data = $this->model->getAll();
        sendSuccess($data, 'Records retrieved successfully');
    }
    
    public function getById($id) {
        $data = $this->model->getById($id);
        
        if ($data) {
            sendSuccess($data, 'Record retrieved successfully');
        } else {
            sendError('Record not found', 404);
        }
    }
    
    public function create() {
        $input = getJsonInput();
        validateRequired($input, $this->getRequiredFields());
        
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
    
    public function update($id) {
        $input = getJsonInput();
        $sanitized = sanitizeInput($input);
        
        $result = $this->model->update($id, $sanitized);
        
        if ($result) {
            $data = $this->model->getById($id);
            sendSuccess($data, 'Record updated successfully');
        } else {
            sendError('Failed to update record or record not found', 404);
        }
    }
    
    public function delete($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            sendSuccess(null, 'Record deleted successfully');
        } else {
            sendError('Failed to delete record or record not found', 404);
        }
    }
    
    /**
     * Override this method in child controllers
     */
    protected function getRequiredFields() {
        return [];
    }
}
?>

