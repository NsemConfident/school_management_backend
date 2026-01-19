# School Management System - Backend API

A RESTful API backend for a School Management System built with PHP and MySQL (XAMPP).

## 📁 Project Structure

```
student_backend/
├── config/
│   ├── config.php          # Application configuration
│   └── database.php        # Database connection settings
├── controllers/
│   ├── BaseController.php  # Base controller with common CRUD operations
│   ├── AuthController.php  # Authentication controller
│   ├── StudentController.php
│   ├── TeacherController.php
│   ├── ClassController.php
│   ├── SubjectController.php
│   ├── AttendanceController.php
│   └── GradeController.php
├── models/
│   ├── BaseModel.php       # Base model with database operations
│   ├── UserModel.php       # User model for authentication
│   ├── StudentModel.php
│   ├── TeacherModel.php
│   ├── ClassModel.php
│   ├── SubjectModel.php
│   ├── AttendanceModel.php
│   └── GradeModel.php
├── middleware/
│   └── auth.php            # Authentication middleware
├── utils/
│   ├── response.php        # Response helper functions
│   ├── request.php         # Request helper functions
│   └── auth.php            # Authentication utilities
├── database/
│   └── schema.sql          # Database schema and sample data
├── .htaccess              # URL rewriting rules
├── index.php              # Main entry point
└── README.md              # This file
```

## 🚀 Setup Instructions

### 1. Install XAMPP
- Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
- Make sure Apache and MySQL services are running

### 2. Database Setup
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Import the database schema:
   - Click on "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"
   
   OR manually run the SQL commands from `database/schema.sql`

### 3. Configure Database Connection
Edit `config/database.php` and update these values if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password (default is empty for XAMPP)
define('DB_NAME', 'school_management');
```

### 4. Place Project in XAMPP
- Copy the `student_backend` folder to `C:\xampp\htdocs\`
- Your API will be accessible at: `http://localhost/student_backend/`

### 5. Enable mod_rewrite (if needed)
- Open `C:\xampp\apache\conf\httpd.conf`
- Find `#LoadModule rewrite_module modules/mod_rewrite.so`
- Remove the `#` to uncomment it
- Restart Apache

## 📡 API Endpoints

### Base URL
```
http://localhost/student_backend/
```

### Available Endpoints

#### Authentication
- `POST /auth/register` - Register a new user (admin/student/teacher)
- `POST /auth/login` - Login and get authentication token
- `GET /auth/profile` - Get current user profile (requires authentication)
- `POST /auth/logout` - Logout and invalidate token
- `POST /auth/change-password` - Change password (requires authentication)

#### Students
- `GET /students` - Get all students
- `GET /students/{id}` - Get student by ID
- `POST /students` - Create new student
- `PUT /students/{id}` - Update student
- `DELETE /students/{id}` - Delete student

#### Teachers
- `GET /teachers` - Get all teachers
- `GET /teachers/{id}` - Get teacher by ID
- `POST /teachers` - Create new teacher
- `PUT /teachers/{id}` - Update teacher
- `DELETE /teachers/{id}` - Delete teacher

#### Classes
- `GET /classes` - Get all classes
- `GET /classes/{id}` - Get class by ID
- `POST /classes` - Create new class
- `PUT /classes/{id}` - Update class
- `DELETE /classes/{id}` - Delete class

#### Subjects
- `GET /subjects` - Get all subjects
- `GET /subjects/{id}` - Get subject by ID
- `POST /subjects` - Create new subject
- `PUT /subjects/{id}` - Update subject
- `DELETE /subjects/{id}` - Delete subject

#### Attendance
- `GET /attendance` - Get all attendance records
- `GET /attendance/{id}` - Get attendance by ID
- `POST /attendance` - Create new attendance record
- `PUT /attendance/{id}` - Update attendance record
- `DELETE /attendance/{id}` - Delete attendance record

#### Grades
- `GET /grades` - Get all grades
- `GET /grades/{id}` - Get grade by ID
- `POST /grades` - Create new grade
- `PUT /grades/{id}` - Update grade
- `DELETE /grades/{id}` - Delete grade

## 🔐 Authentication

The API uses token-based authentication. After registering or logging in, you'll receive a token that must be included in subsequent requests.

### Default Admin Account
After running the database schema, a default admin account is created:
- **Username:** `admin`
- **Email:** `admin@school.com`
- **Password:** `admin123`

### How Authentication Works

1. **Register or Login** to get an authentication token
2. **Include the token** in your requests using one of these methods:
   - Header: `Authorization: Bearer {token}`
   - Header: `X-Auth-Token: {token}`
   - Query parameter: `?token={token}` (for testing only)

3. **Token expires** after 7 days of inactivity

### User Roles

- **admin**: Full access to all endpoints
- **student**: Access to student-specific data
- **teacher**: Access to teacher-specific data

## 🧪 Testing with Postman

### Example: Register a New User

**Request:**
- Method: `POST`
- URL: `http://localhost/student_backend/auth/register`
- Headers:
  - `Content-Type: application/json`
- Body (raw JSON):
```json
{
  "username": "johndoe",
  "email": "john.doe@example.com",
  "password": "password123",
  "role": "student",
  "role_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "username": "johndoe",
      "email": "john.doe@example.com",
      "role": "student",
      "role_id": 1,
      "student_details": { ... }
    },
    "token": "a1b2c3d4e5f6..."
  }
}
```

### Example: Login

**Request:**
- Method: `POST`
- URL: `http://localhost/student_backend/auth/login`
- Headers:
  - `Content-Type: application/json`
- Body (raw JSON):
```json
{
  "email": "admin@school.com",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@school.com",
      "role": "admin"
    },
    "token": "a1b2c3d4e5f6..."
  }
}
```

### Example: Get Profile (Authenticated Request)

**Request:**
- Method: `GET`
- URL: `http://localhost/student_backend/auth/profile`
- Headers:
  - `Authorization: Bearer a1b2c3d4e5f6...`
  - OR `X-Auth-Token: a1b2c3d4e5f6...`

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@school.com",
    "role": "admin",
    ...
  }
}
```

### Example: Create a Student (with Authentication)

**Request:**
- Method: `POST`
- URL: `http://localhost/student_backend/students`
- Headers:
  - `Content-Type: application/json`
  - `Authorization: Bearer {your_token_here}`
- Body (raw JSON):
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "1234567890",
  "date_of_birth": "2010-05-15",
  "address": "123 Main St",
  "class_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Record created successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    ...
  }
}
```

### Example: Get All Students

**Request:**
- Method: `GET`
- URL: `http://localhost/student_backend/students`

**Response:**
```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      ...
    }
  ]
}
```

## 📝 Notes

- All responses are in JSON format
- CORS is enabled for API access
- Input validation is handled automatically
- All inputs are sanitized for security
- Error handling is built-in
- **Passwords are hashed** using bcrypt before storage
- **Tokens expire** after 7 days
- **Role-based access control** is available via middleware

## 🔧 Troubleshooting

1. **404 Not Found**: Make sure mod_rewrite is enabled and .htaccess is working
2. **Database Connection Error**: Check database credentials in `config/database.php`
3. **500 Internal Server Error**: Check Apache error logs in `C:\xampp\apache\logs\error.log`

## 🛡️ Protecting Routes with Authentication

To protect a route, use the authentication middleware in your controller:

```php
require_once __DIR__ . '/../middleware/auth.php';

// Require any authenticated user
$user = requireAuth();

// Require specific role(s)
$user = requireRole(['admin', 'teacher']);

// Require admin only
$user = requireAdmin();
```

## 📚 Next Steps

- Implement pagination for list endpoints
- Add filtering and searching
- Implement file uploads for student photos
- Add more complex queries and relationships
- Add email verification
- Add password reset functionality

