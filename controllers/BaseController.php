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
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            sendError('Invalid ID', 400);
        }
        
        $input = getJsonInput();
        
        // Check if record exists first
        $existing = $this->model->getById($id);
        if (!$existing) {
            sendError('Record not found', 404);
        }
        
        // Check if there's any data to update
        if (empty($input)) {
            sendError('No data provided for update', 400);
        }
        
        $sanitized = sanitizeInput($input);
        
        // Filter out null values but keep empty strings and zeros
        // This allows clearing fields or setting numeric values to 0
        $sanitized = array_filter($sanitized, function($value) {
            return $value !== null;
        });
        
        if (empty($sanitized)) {
            sendError('No valid data provided for update', 400);
        }
        
        $result = $this->model->update($id, $sanitized);
        
        if ($result) {
            $data = $this->model->getById($id);
            sendSuccess($data, 'Record updated successfully');
        } else {
            // Get the actual database error if available
            $errorMsg = 'Failed to update record';
            $dbError = $this->model->getLastError();
            if ($dbError) {
                $errorMsg .= ': ' . $dbError;
            }
            sendError($errorMsg, 500);
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

