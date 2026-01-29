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
        
    case 'timetables':
        require_once __DIR__ . '/controllers/TimetableController.php';
        $controller = new TimetableController();
        handleTimetableRequest($controller, $method, $resource, $id);
        break;
        
    case 'exam-timetables':
        require_once __DIR__ . '/controllers/ExamTimetableController.php';
        $controller = new ExamTimetableController();
        handleExamTimetableRequest($controller, $method, $resource, $id);
        break;
        
    case 'assessment-timetables':
        require_once __DIR__ . '/controllers/AssessmentTimetableController.php';
        $controller = new AssessmentTimetableController();
        handleAssessmentTimetableRequest($controller, $method, $resource, $id);
        break;
        
    case 'notifications':
        require_once __DIR__ . '/controllers/NotificationController.php';
        $controller = new NotificationController();
        handleNotificationRequest($controller, $method, $resource, $id);
        break;
        
    case 'class-subjects':
        require_once __DIR__ . '/models/ClassSubjectModel.php';
        $model = new ClassSubjectModel();
        handleClassSubjectRequest($model, $method, $resource, $id);
        break;
        
    case '':
    case 'api':
        sendSuccess(null, 'Timetable Generation System (TGS) API is running', 200);
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

/**
 * Handle timetable request routing
 */
function handleTimetableRequest($controller, $method, $resource, $id) {
    switch ($resource) {
        case 'generate':
            if ($method === 'POST') {
                $controller->generate();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'active':
            if ($method === 'GET') {
                $controller->getActive();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'student':
            if ($method === 'GET') {
                $controller->getStudentTimetable();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'check-conflicts':
            if ($method === 'POST') {
                $controller->checkConflicts();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'slots':
            $slotId = $id;
            if ($method === 'POST') {
                $controller->addSlot();
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                if ($slotId) {
                    $controller->updateSlot($slotId);
                } else {
                    sendError('Slot ID required', 400);
                }
            } elseif ($method === 'DELETE') {
                if ($slotId) {
                    $controller->deleteSlot($slotId);
                } else {
                    sendError('Slot ID required', 400);
                }
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'activate':
            if ($method === 'POST' && $id) {
                $controller->activate($id);
            } else {
                sendError('Method not allowed or ID required', 405);
            }
            break;
            
        default:
            // Standard CRUD operations
            handleRequest($controller, $method, $resource, $id);
            break;
    }
}

/**
 * Handle exam timetable request routing
 */
function handleExamTimetableRequest($controller, $method, $resource, $id) {
    switch ($resource) {
        case 'student':
            if ($method === 'GET') {
                $controller->getStudentExams();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'publish':
            if ($method === 'POST' && $id) {
                $controller->publish($id);
            } else {
                sendError('Method not allowed or ID required', 405);
            }
            break;
            
        case 'check-conflicts':
            if ($method === 'POST') {
                $controller->checkConflicts();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'slots':
            if ($method === 'POST') {
                $controller->addSlot();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        default:
            // Standard CRUD operations
            handleRequest($controller, $method, $resource, $id);
            break;
    }
}

/**
 * Handle assessment timetable request routing
 */
function handleAssessmentTimetableRequest($controller, $method, $resource, $id) {
    switch ($resource) {
        case 'student':
            if ($method === 'GET') {
                $controller->getStudentAssessments();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'publish':
            if ($method === 'POST' && $id) {
                $controller->publish($id);
            } else {
                sendError('Method not allowed or ID required', 405);
            }
            break;
            
        case 'slots':
            if ($method === 'POST') {
                $controller->addSlot();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        default:
            // Standard CRUD operations
            handleRequest($controller, $method, $resource, $id);
            break;
    }
}

/**
 * Handle notification request routing
 */
function handleNotificationRequest($controller, $method, $resource, $id) {
    switch ($resource) {
        case 'my':
            if ($method === 'GET') {
                $controller->getMyNotifications();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'unread-count':
            if ($method === 'GET') {
                $controller->getUnreadCount();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'mark-all-read':
            if ($method === 'POST') {
                $controller->markAllAsRead();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case 'mark-read':
            if ($method === 'POST' && $id) {
                $controller->markAsRead($id);
            } else {
                sendError('Method not allowed or ID required', 405);
            }
            break;
            
        case 'by-role':
            if ($method === 'GET') {
                $controller->getByRole();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        default:
            // Standard CRUD operations
            if ($method === 'GET' && $id) {
                // Get notification by ID - not implemented in controller, use standard
                sendError('Endpoint not found', 404);
            } elseif ($method === 'POST') {
                $controller->create();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
    }
}

/**
 * Handle class-subject request routing
 */
function handleClassSubjectRequest($model, $method, $resource, $id) {
    require_once __DIR__ . '/utils/response.php';
    
    switch ($resource) {
        case 'class':
            if ($method === 'GET' && $id) {
                $subjects = $model->getSubjectsByClass($id);
                sendSuccess($subjects, 'Subjects retrieved successfully');
            } else {
                sendError('Class ID required', 400);
            }
            break;
            
        case 'subject':
            if ($method === 'GET' && $id) {
                $classes = $model->getClassesBySubject($id);
                sendSuccess($classes, 'Classes retrieved successfully');
            } else {
                sendError('Subject ID required', 400);
            }
            break;
            
        default:
            // Standard CRUD operations
            if ($method === 'GET') {
                if ($id) {
                    $data = $model->getById($id);
                    if ($data) {
                        sendSuccess($data, 'Record retrieved successfully');
                    } else {
                        sendError('Record not found', 404);
                    }
                } else {
                    $data = $model->getAll();
                    sendSuccess($data, 'Records retrieved successfully');
                }
            } elseif ($method === 'POST') {
                require_once __DIR__ . '/utils/request.php';
                $input = getJsonInput();
                $id = $model->create($input);
                if ($id) {
                    $data = $model->getById($id);
                    sendSuccess($data, 'Record created successfully', 201);
                } else {
                    sendError('Failed to create record', 500);
                }
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                if ($id) {
                    require_once __DIR__ . '/utils/request.php';
                    $input = getJsonInput();
                    $result = $model->update($id, $input);
                    if ($result) {
                        $data = $model->getById($id);
                        sendSuccess($data, 'Record updated successfully');
                    } else {
                        sendError('Failed to update record', 500);
                    }
                } else {
                    sendError('ID required', 400);
                }
            } elseif ($method === 'DELETE') {
                if ($id) {
                    $result = $model->delete($id);
                    if ($result) {
                        sendSuccess(null, 'Record deleted successfully');
                    } else {
                        sendError('Failed to delete record', 500);
                    }
                } else {
                    sendError('ID required', 400);
                }
            } else {
                sendError('Method not allowed', 405);
            }
            break;
    }
}
?>

