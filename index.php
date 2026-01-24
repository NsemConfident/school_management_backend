<?php
/**
 * Main Entry Point for School Management System API
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/request.php';

// Get request method and URI
$method = getRequestMethod();
$segments = getUriSegments();

// Route handling
$route = isset($segments[0]) ? $segments[0] : '';
$resource = isset($segments[1]) ? $segments[1] : '';
$id = isset($segments[2]) ? $segments[2] : '';

// For standard CRUD routes, if segments[1] is numeric, it's the ID, not a resource
// e.g., /students/8 -> route=students, id=8
if ($route !== 'auth' && $resource && is_numeric($resource)) {
    $id = $resource;
    $resource = '';
}

// Route to appropriate controller
switch ($route) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        handleAuthRequest($controller, $method, $resource);
        break;
        
    case 'students':
        require_once __DIR__ . '/controllers/StudentController.php';
        $controller = new StudentController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case 'teachers':
        require_once __DIR__ . '/controllers/TeacherController.php';
        $controller = new TeacherController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case 'classes':
        require_once __DIR__ . '/controllers/ClassController.php';
        $controller = new ClassController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case 'subjects':
        require_once __DIR__ . '/controllers/SubjectController.php';
        $controller = new SubjectController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case 'attendance':
        require_once __DIR__ . '/controllers/AttendanceController.php';
        $controller = new AttendanceController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case 'grades':
        require_once __DIR__ . '/controllers/GradeController.php';
        $controller = new GradeController();
        handleRequest($controller, $method, $resource, $id);
        break;
        
    case '':
    case 'api':
        sendSuccess(null, 'School Management System API is running', 200);
        break;
        
    default:
        sendError('Endpoint not found', 404);
        break;
}

/**
 * Handle authentication request routing
 */
function handleAuthRequest($controller, $method, $resource) {
    switch ($resource) {
        case 'register':
            if ($method === 'POST') {
                $controller->register();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'login':
            if ($method === 'POST') {
                $controller->login();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'profile':
            if ($method === 'GET') {
                $controller->profile();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'logout':
            if ($method === 'POST') {
                $controller->logout();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'change-password':
            if ($method === 'POST') {
                $controller->changePassword();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        default:
            sendError('Auth endpoint not found', 404);
            break;
    }
}

/**
 * Handle request routing to controller methods
 */
function handleRequest($controller, $method, $resource, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $controller->getById($id);
            } else {
                $controller->getAll();
            }
            break;
            
        case 'POST':
            $controller->create();
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                $controller->update($id);
            } else {
                sendError('ID required for update', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $controller->delete($id);
            } else {
                sendError('ID required for delete', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
            break;
    }
}
?>

