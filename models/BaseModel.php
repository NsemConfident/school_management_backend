<?php
/**
 * Base Model Class
 * Provides common database operations
 */

class BaseModel {
    protected $table;
    protected $conn;
    protected $lastError;
    
    public function __construct($table) {
        $this->table = $table;
        $this->conn = getDBConnection();
        $this->lastError = null;
    }
    
    /**
     * Get all records
     */
    public function getAll($conditions = [], $orderBy = 'id DESC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = ?";
            }
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY $orderBy";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($conditions)) {
            $types = str_repeat('s', count($conditions));
            $values = array_values($conditions);
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        // Remove null or empty string values to use database defaults
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        if (empty($data)) {
            return false;
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return false;
        }
        
        // Determine types for bind_param
        $types = '';
        $values = [];
        foreach ($data as $key => $value) {
            if (is_int($value) || (is_string($value) && is_numeric($value) && strpos($value, '.') === false)) {
                $types .= 'i'; // integer
                $values[] = (int)$value;
            } elseif (is_float($value) || (is_string($value) && is_numeric($value) && strpos($value, '.') !== false)) {
                $types .= 'd'; // double/float
                $values[] = (float)$value;
            } else {
                $types .= 's'; // string
                $values[] = $value;
            }
        }
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            $this->lastError = $stmt->error ?: $this->conn->error;
            error_log("SQL Execute Error: " . $this->lastError);
            error_log("SQL: " . $sql);
            error_log("Data: " . print_r($data, true));
            return false;
        }
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        if (empty($data)) {
            return false;
        }
        
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "$field = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->lastError = $this->conn->error;
            error_log("SQL Prepare Error: " . $this->lastError);
            return false;
        }
        
        // Determine types for bind_param
        $types = '';
        $values = [];
        foreach ($data as $key => $value) {
            if (is_int($value) || (is_string($value) && is_numeric($value) && strpos($value, '.') === false)) {
                $types .= 'i'; // integer
                $values[] = (int)$value;
            } elseif (is_float($value) || (is_string($value) && is_numeric($value) && strpos($value, '.') !== false)) {
                $types .= 'd'; // double/float
                $values[] = (float)$value;
            } else {
                $types .= 's'; // string
                $values[] = $value;
            }
        }
        $types .= 'i'; // for id
        $values[] = $id;
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return true;
        } else {
            $this->lastError = $stmt->error ?: $this->conn->error;
            error_log("SQL Execute Error: " . $this->lastError);
            error_log("SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get last database error
     */
    public function getLastError() {
        return $this->lastError ?: ($this->conn->error ?: null);
    }
    
    /**
     * Get connection (for error reporting)
     */
    public function getConnection() {
        return $this->conn;
    }
}
?>

