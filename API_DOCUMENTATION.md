# School Management System API Documentation

## Table of Contents

1. [Overview](#overview)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)
6. [Endpoints](#endpoints)
   - [Authentication](#authentication-endpoints)
   - [Students](#students-endpoints)
   - [Teachers](#teachers-endpoints)
   - [Classes](#classes-endpoints)
   - [Subjects](#subjects-endpoints)
   - [Attendance](#attendance-endpoints)
   - [Grades](#grades-endpoints)
7. [Data Models](#data-models)
8. [Examples](#examples)

---

## Overview

The School Management System API is a RESTful API built with PHP that provides endpoints for managing students, teachers, classes, subjects, attendance records, and grades in a school management system.

### Features

- Token-based authentication
- Role-based access control (Admin, Teacher, Student)
- CRUD operations for all major resources
- RESTful API design
- JSON request/response format

---

## Base URL

```
http://localhost/student_backend/
```

For production, replace `localhost/student_backend` with your actual domain.

---

## Request Formats

The API supports **both JSON and form-data** request formats. You can use either format in Postman or any HTTP client.

### JSON Format (Default)

**Content-Type:** `application/json`

**Postman Setup:**
1. Select **Body** tab
2. Choose **raw**
3. Select **JSON** from dropdown
4. Enter your JSON data

**Example:**
```json
{
  "username": "johndoe",
  "email": "john.doe@example.com",
  "password": "password123"
}
```

### Form-Data Format

**Content-Type:** `multipart/form-data` (automatically set by Postman)

**Postman Setup:**
1. Select **Body** tab
2. Choose **form-data**
3. Add key-value pairs for each field

**Example:**
| Key | Value |
|-----|-------|
| username | johndoe |
| email | john.doe@example.com |
| password | password123 |

**⚠️ Important:** Form-data works best with **POST** requests. For **PUT** and **PATCH** requests, it's recommended to use **JSON** or **x-www-form-urlencoded** instead, as PHP doesn't automatically parse form-data for PUT/PATCH requests.

### URL-Encoded Format

**Content-Type:** `application/x-www-form-urlencoded`

**Postman Setup:**
1. Select **Body** tab
2. Choose **x-www-form-urlencoded**
3. Add key-value pairs

**Note:** Works well with all HTTP methods including PUT and PATCH.

**Summary:**
- **POST requests:** JSON, form-data, or x-www-form-urlencoded all work
- **PUT/PATCH requests:** Use **JSON** (recommended) or **x-www-form-urlencoded** for best compatibility

---

## Authentication

The API uses **token-based authentication**. After successful registration or login, you'll receive an authentication token that must be included in subsequent requests.

### Getting a Token

1. Register a new user via `POST /auth/register`
2. Or login via `POST /auth/login`

Both endpoints return a `token` in the response.

### Using the Token

Include the token in your requests using one of these methods:

**Option 1: Authorization Header (Recommended)**
```
Authorization: Bearer {token}
```

**Option 2: X-Auth-Token Header**
```
X-Auth-Token: {token}
```

**Option 3: Query Parameter (For testing only)**
```
?token={token}
```

### Token Expiration

- Tokens expire after **7 days** of inactivity
- Upon expiration, you'll receive a `401 Unauthorized` error
- To continue, login again to get a new token

### Default Admin Account

After running the database schema, a default admin account is created:
- **Username:** `admin`
- **Email:** `admin@school.com`
- **Password:** `admin123`

⚠️ **Important:** Change the default admin password in production!

### User Roles

- **admin**: Full access to all endpoints
- **teacher**: Access to teacher-specific data
- **student**: Access to student-specific data

---

## Response Format

All API responses follow a consistent JSON structure.

### Success Response

```json
{
  "success": true,
  "message": "Success message",
  "data": {
    // Response data here
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": [
    // Optional: Array of validation errors
  ]
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters or body |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | HTTP method not allowed for endpoint |
| 409 | Conflict | Resource already exists (e.g., duplicate email) |
| 500 | Internal Server Error | Server-side error |

### Common Error Messages

- `Authentication token required` - Missing token in request
- `Invalid or expired token` - Token is invalid or expired
- `Insufficient permissions` - User role doesn't have access
- `Missing required fields: {fields}` - Required fields not provided
- `Record not found` - Requested resource doesn't exist
- `Username already exists` - Username is taken
- `Email already exists` - Email is already registered

---

## Endpoints

## Authentication Endpoints

### Register User

Register a new user account.

**Endpoint:** `POST /auth/register`

**Authentication:** Not required

**Request Body:**

```json
{
  "username": "johndoe",
  "email": "john.doe@example.com",
  "password": "password123",
  "role": "student",
  "role_id": 1
}
```

**Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| username | string | Yes | Unique username (min 1 character) |
| email | string | Yes | Unique email address |
| password | string | Yes | Password (min 6 characters) |
| role | string | Yes | User role: `admin`, `student`, or `teacher` |
| role_id | integer | No | ID from students or teachers table based on role. If provided, the record must exist. If omitted, user can be linked to a student/teacher record later. Admin role doesn't use role_id. |

**Notes:**
- `role_id` is **optional** for all roles
- If you provide `role_id`, the student/teacher record must exist in the database first
- If you omit `role_id`, you can register successfully and link the account later
- For `admin` role, `role_id` is ignored even if provided

**Response:** `201 Created`

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
      "is_active": 1,
      "created_at": "2024-01-01 12:00:00",
      "student_details": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        // ... other student fields
      }
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Error Responses:**

- `400` - Missing required fields or invalid role
- `409` - Username or email already exists
- `400` - Invalid role_id (record not found). If you provide a role_id, the corresponding student or teacher record must exist in the database. You can either create the student/teacher record first, or omit role_id to register without linking.

---

### Login

Authenticate and receive an access token.

**Endpoint:** `POST /auth/login`

**Authentication:** Not required

**Request Body:**

```json
{
  "email": "john.doe@example.com",
  "password": "password123"
}
```

**Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | User email address |
| password | string | Yes | User password |

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "username": "johndoe",
      "email": "john.doe@example.com",
      "role": "student",
      "role_id": 1,
      "is_active": 1,
      "last_login": "2024-01-01 12:00:00",
      "created_at": "2024-01-01 10:00:00",
      "student_details": {
        // Student information if role is student
      }
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Error Responses:**

- `400` - Missing email or password
- `401` - Invalid email or password
- `403` - Account is deactivated

---

### Get User Profile

Get the authenticated user's profile information.

**Endpoint:** `GET /auth/profile`

**Authentication:** Required

**Request Headers:**

```
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "username": "johndoe",
    "email": "john.doe@example.com",
    "role": "student",
    "role_id": 1,
    "is_active": 1,
    "last_login": "2024-01-01 12:00:00",
    "created_at": "2024-01-01 10:00:00",
    "student_details": {
      // Student information if role is student
    }
  }
}
```

**Error Responses:**

- `401` - Missing or invalid token

---

### Logout

Invalidate the current authentication token.

**Endpoint:** `POST /auth/logout`

**Authentication:** Required (token in request)

**Request Headers:**

```
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

**Error Responses:**

- `400` - Token required

---

### Change Password

Change the authenticated user's password.

**Endpoint:** `POST /auth/change-password`

**Authentication:** Required

**Request Headers:**

```
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "current_password": "oldpassword123",
  "new_password": "newpassword123"
}
```

**Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| current_password | string | Yes | Current password |
| new_password | string | Yes | New password (min 6 characters) |

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Password changed successfully",
  "data": null
}
```

**Error Responses:**

- `400` - Missing required fields, incorrect current password, or new password too short
- `401` - Missing or invalid token

---

## Students Endpoints

### Get All Students

Retrieve a list of all students.

**Endpoint:** `GET /students`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "phone": "123-456-7890",
      "date_of_birth": "2010-05-15",
      "address": "123 Main St",
      "class_id": 1,
      "enrollment_date": "2024-01-01",
      "status": "active",
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    }
    // ... more students
  ]
}
```

---

### Get Student by ID

Retrieve a specific student by ID.

**Endpoint:** `GET /students/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Student ID |

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Record retrieved successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "123-456-7890",
    "date_of_birth": "2010-05-15",
    "address": "123 Main St",
    "class_id": 1,
    "enrollment_date": "2024-01-01",
    "status": "active",
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 10:00:00"
  }
}
```

**Error Responses:**

- `404` - Student not found

---

### Create Student

Create a new student record.

**Endpoint:** `POST /students`

**Authentication:** Required

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "123-456-7890",
  "date_of_birth": "2010-05-15",
  "address": "123 Main St",
  "class_id": 1,
  "enrollment_date": "2024-01-01",
  "status": "active"
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| first_name | string | Yes | Student's first name |
| last_name | string | Yes | Student's last name |
| email | string | Yes | Student's email (must be unique) |
| date_of_birth | date | Yes | Student's date of birth (YYYY-MM-DD) |
| class_id | integer | No | ID of the class the student belongs to. If provided, the class must exist in the database. If omitted, student will be created without a class assignment. |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| class_id | integer | ID of the class the student belongs to. If provided, the class must exist. If omitted, student is created without a class assignment. |
| phone | string | Student's phone number |
| address | string | Student's address |
| enrollment_date | date | Enrollment date (defaults to current date) |
| status | string | Status: `active`, `inactive`, or `graduated` (defaults to `active`) |

**Response:** `201 Created`

```json
{
  "success": true,
  "message": "Record created successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "123-456-7890",
    "date_of_birth": "2010-05-15",
    "address": "123 Main St",
    "class_id": 1,
    "enrollment_date": "2024-01-01",
    "status": "active",
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 10:00:00"
  }
}
```

**Error Responses:**

- `400` - Missing required fields
- `400` - Invalid class_id (class does not exist). Create the class first using `POST /classes` or omit class_id.
- `409` - Email already exists (duplicate email)
- `500` - Failed to create record (database error)

---

### Update Student

Update an existing student record.

**Endpoint:** `PUT /students/{id}` or `PATCH /students/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Student ID |

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Smith",
  "email": "john.smith@example.com",
  "phone": "123-456-7890",
  "status": "active"
}
```

**Fields:** All fields are optional. Only include fields you want to update.

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Record updated successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john.smith@example.com",
    // ... other fields
  }
}
```

**Error Responses:**

- `404` - Student not found or failed to update
- `500` - Server error

---

### Delete Student

Delete a student record.

**Endpoint:** `DELETE /students/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Student ID |

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Record deleted successfully",
  "data": null
}
```

**Error Responses:**

- `404` - Student not found or failed to delete

---

## Teachers Endpoints

### Get All Teachers

Retrieve a list of all teachers.

**Endpoint:** `GET /teachers`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "first_name": "Jane",
      "last_name": "Smith",
      "email": "jane.smith@example.com",
      "phone": "123-456-7890",
      "subject_id": 1,
      "address": "456 Oak Ave",
      "hire_date": "2020-01-15",
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    }
    // ... more teachers
  ]
}
```

---

### Get Teacher by ID

Retrieve a specific teacher by ID.

**Endpoint:** `GET /teachers/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Teacher ID |

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Record retrieved successfully",
  "data": {
    "id": 1,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane.smith@example.com",
    "phone": "123-456-7890",
    "subject_id": 1,
    "address": "456 Oak Ave",
    "hire_date": "2020-01-15",
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 10:00:00"
  }
}
```

---

### Create Teacher

Create a new teacher record.

**Endpoint:** `POST /teachers`

**Authentication:** Required

**Request Body:**

```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane.smith@example.com",
  "phone": "123-456-7890",
  "subject_id": 1,
  "address": "456 Oak Ave",
  "hire_date": "2020-01-15"
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| first_name | string | Yes | Teacher's first name |
| last_name | string | Yes | Teacher's last name |
| email | string | Yes | Teacher's email (must be unique) |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| phone | string | Teacher's phone number |
| subject_id | integer | ID of the subject the teacher teaches |
| address | string | Teacher's address |
| hire_date | date | Date when teacher was hired (YYYY-MM-DD) |

**Response:** `201 Created`

---

### Update Teacher

Update an existing teacher record.

**Endpoint:** `PUT /teachers/{id}` or `PATCH /teachers/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Teacher ID |

**Request Body:** (All fields optional)

```json
{
  "first_name": "Jane",
  "last_name": "Johnson",
  "email": "jane.johnson@example.com"
}
```

**Response:** `200 OK`

---

### Delete Teacher

Delete a teacher record.

**Endpoint:** `DELETE /teachers/{id}`

**Authentication:** Required

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Teacher ID |

**Response:** `200 OK`

---

## Classes Endpoints

### Get All Classes

Retrieve a list of all classes.

**Endpoint:** `GET /classes`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "class_name": "Grade 10-A",
      "grade_level": "10",
      "teacher_id": 1,
      "room_number": "101",
      "capacity": 30,
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    }
    // ... more classes
  ]
}
```

---

### Get Class by ID

Retrieve a specific class by ID.

**Endpoint:** `GET /classes/{id}`

**Authentication:** Required

---

### Create Class

Create a new class record.

**Endpoint:** `POST /classes`

**Authentication:** Required

**Request Body:**

```json
{
  "class_name": "Grade 10-A",
  "grade_level": "10",
  "teacher_id": 1,
  "room_number": "101",
  "capacity": 30
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| class_name | string | Yes | Name of the class |
| grade_level | string | Yes | Grade level (e.g., "10", "11", "12") |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| teacher_id | integer | ID of the teacher assigned to the class |
| room_number | string | Room number where the class meets |
| capacity | integer | Maximum number of students (default: 30) |

**Response:** `201 Created`

---

### Update Class

Update an existing class record.

**Endpoint:** `PUT /classes/{id}` or `PATCH /classes/{id}`

**Authentication:** Required

---

### Delete Class

Delete a class record.

**Endpoint:** `DELETE /classes/{id}`

**Authentication:** Required

---

## Subjects Endpoints

### Get All Subjects

Retrieve a list of all subjects.

**Endpoint:** `GET /subjects`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "subject_name": "Mathematics",
      "subject_code": "MATH101",
      "description": "Introduction to Mathematics",
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    }
    // ... more subjects
  ]
}
```

---

### Get Subject by ID

Retrieve a specific subject by ID.

**Endpoint:** `GET /subjects/{id}`

**Authentication:** Required

---

### Create Subject

Create a new subject record.

**Endpoint:** `POST /subjects`

**Authentication:** Required

**Request Body:**

```json
{
  "subject_name": "Mathematics",
  "subject_code": "MATH101",
  "description": "Introduction to Mathematics"
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| subject_name | string | Yes | Name of the subject |
| subject_code | string | Yes | Unique subject code |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| description | string | Description of the subject |

**Response:** `201 Created`

---

### Update Subject

Update an existing subject record.

**Endpoint:** `PUT /subjects/{id}` or `PATCH /subjects/{id}`

**Authentication:** Required

---

### Delete Subject

Delete a subject record.

**Endpoint:** `DELETE /subjects/{id}`

**Authentication:** Required

---

## Attendance Endpoints

### Get All Attendance Records

Retrieve a list of all attendance records.

**Endpoint:** `GET /attendance`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "student_id": 1,
      "date": "2024-01-15",
      "status": "present",
      "notes": "On time",
      "created_at": "2024-01-15 08:00:00",
      "updated_at": "2024-01-15 08:00:00"
    }
    // ... more attendance records
  ]
}
```

---

### Get Attendance by ID

Retrieve a specific attendance record by ID.

**Endpoint:** `GET /attendance/{id}`

**Authentication:** Required

---

### Create Attendance Record

Create a new attendance record.

**Endpoint:** `POST /attendance`

**Authentication:** Required

**Request Body:**

```json
{
  "student_id": 1,
  "date": "2024-01-15",
  "status": "present",
  "notes": "On time"
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| student_id | integer | Yes | ID of the student |
| date | date | Yes | Date of attendance (YYYY-MM-DD) |
| status | string | Yes | Attendance status: `present`, `absent`, `late`, or `excused` |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| notes | string | Additional notes about the attendance |

**Note:** Each student can only have one attendance record per date (unique constraint).

**Response:** `201 Created`

---

### Update Attendance Record

Update an existing attendance record.

**Endpoint:** `PUT /attendance/{id}` or `PATCH /attendance/{id}`

**Authentication:** Required

---

### Delete Attendance Record

Delete an attendance record.

**Endpoint:** `DELETE /attendance/{id}`

**Authentication:** Required

---

## Grades Endpoints

### Get All Grades

Retrieve a list of all grades.

**Endpoint:** `GET /grades`

**Authentication:** Required

**Response:** `200 OK`

```json
{
  "success": true,
  "message": "Records retrieved successfully",
  "data": [
    {
      "id": 1,
      "student_id": 1,
      "subject_id": 1,
      "grade": 85.50,
      "exam_type": "midterm",
      "exam_date": "2024-01-15",
      "notes": "Good performance",
      "created_at": "2024-01-15 10:00:00",
      "updated_at": "2024-01-15 10:00:00"
    }
    // ... more grades
  ]
}
```

---

### Get Grade by ID

Retrieve a specific grade by ID.

**Endpoint:** `GET /grades/{id}`

**Authentication:** Required

---

### Create Grade

Create a new grade record.

**Endpoint:** `POST /grades`

**Authentication:** Required

**Request Body:**

```json
{
  "student_id": 1,
  "subject_id": 1,
  "grade": 85.50,
  "exam_type": "midterm",
  "exam_date": "2024-01-15",
  "notes": "Good performance"
}
```

**Required Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| student_id | integer | Yes | ID of the student |
| subject_id | integer | Yes | ID of the subject |
| grade | decimal | Yes | Grade score (e.g., 85.50) |
| exam_type | string | Yes | Type of exam: `quiz`, `midterm`, `final`, `assignment`, or `project` |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| exam_date | date | Date of the exam (YYYY-MM-DD) |
| notes | string | Additional notes about the grade |

**Response:** `201 Created`

---

### Update Grade

Update an existing grade record.

**Endpoint:** `PUT /grades/{id}` or `PATCH /grades/{id}`

**Authentication:** Required

---

### Delete Grade

Delete a grade record.

**Endpoint:** `DELETE /grades/{id}`

**Authentication:** Required

---

## Data Models

### User Model

```json
{
  "id": 1,
  "username": "johndoe",
  "email": "john.doe@example.com",
  "role": "student",
  "role_id": 1,
  "is_active": 1,
  "last_login": "2024-01-01 12:00:00",
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00",
  "student_details": {
    // If role is "student"
  },
  "teacher_details": {
    // If role is "teacher"
  }
}
```

### Student Model

```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "123-456-7890",
  "date_of_birth": "2010-05-15",
  "address": "123 Main St",
  "class_id": 1,
  "enrollment_date": "2024-01-01",
  "status": "active",
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00"
}
```

### Teacher Model

```json
{
  "id": 1,
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane.smith@example.com",
  "phone": "123-456-7890",
  "subject_id": 1,
  "address": "456 Oak Ave",
  "hire_date": "2020-01-15",
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00"
}
```

### Class Model

```json
{
  "id": 1,
  "class_name": "Grade 10-A",
  "grade_level": "10",
  "teacher_id": 1,
  "room_number": "101",
  "capacity": 30,
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00"
}
```

### Subject Model

```json
{
  "id": 1,
  "subject_name": "Mathematics",
  "subject_code": "MATH101",
  "description": "Introduction to Mathematics",
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00"
}
```

### Attendance Model

```json
{
  "id": 1,
  "student_id": 1,
  "date": "2024-01-15",
  "status": "present",
  "notes": "On time",
  "created_at": "2024-01-15 08:00:00",
  "updated_at": "2024-01-15 08:00:00"
}
```

### Grade Model

```json
{
  "id": 1,
  "student_id": 1,
  "subject_id": 1,
  "grade": 85.50,
  "exam_type": "midterm",
  "exam_date": "2024-01-15",
  "notes": "Good performance",
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

---

## Examples

### Example: Complete Authentication Flow

**1. Register a new user:**

```bash
curl -X POST http://localhost/student_backend/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john.doe@example.com",
    "password": "password123",
    "role": "student",
    "role_id": 1
  }'
```

**2. Login:**

```bash
curl -X POST http://localhost/student_backend/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**3. Use the token to access protected endpoints:**

```bash
curl -X GET http://localhost/student_backend/auth/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

### Example: Create a Student

```bash
curl -X POST http://localhost/student_backend/students \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "123-456-7890",
    "date_of_birth": "2010-05-15",
    "address": "123 Main St",
    "class_id": 1,
    "status": "active"
  }'
```

---

### Example: Update a Student

```bash
curl -X PUT http://localhost/student_backend/students/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "first_name": "John",
    "last_name": "Smith",
    "phone": "987-654-3210"
  }'
```

---

### Example: Get All Students

```bash
curl -X GET http://localhost/student_backend/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

### Example: Create an Attendance Record

```bash
curl -X POST http://localhost/student_backend/attendance \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "student_id": 1,
    "date": "2024-01-15",
    "status": "present",
    "notes": "On time"
  }'
```

---

### Example: Create a Grade

```bash
curl -X POST http://localhost/student_backend/grades \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "student_id": 1,
    "subject_id": 1,
    "grade": 85.50,
    "exam_type": "midterm",
    "exam_date": "2024-01-15",
    "notes": "Good performance"
  }'
```

---

## Postman Form-Data Examples

The API supports form-data format, which is convenient for testing in Postman. Here are examples using form-data:

### Example: Register User (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/auth/register`
3. Body tab → Select **form-data**
4. Add the following key-value pairs:

| Key | Value | Type |
|-----|-------|------|
| username | johndoe | Text |
| email | john.doe@example.com | Text |
| password | password123 | Text |
| role | student | Text |
| role_id | 1 | Text (Optional) |

**Notes:**
- All values should be set as **Text** type (not File)
- `role_id` is **optional** - you can omit it to register without linking to a student/teacher record
- If you include `role_id`, make sure the student or teacher record exists in the database first
- For `admin` role, `role_id` is not needed

---

### Example: Login (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/auth/login`
3. Body tab → Select **form-data**
4. Add the following key-value pairs:

| Key | Value |
|-----|-------|
| email | john.doe@example.com |
| password | password123 |

---

### Example: Create Student (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/students`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
4. Body tab → Select **form-data**
5. Add the following key-value pairs:

| Key | Value |
|-----|-------|
| first_name | John |
| last_name | Doe |
| email | john.doe@example.com |
| phone | 123-456-7890 |
| date_of_birth | 2010-05-15 |
| address | 123 Main St |
| class_id | 1 |
| status | active |

---

### Example: Update Student (JSON - Recommended for PUT/PATCH)

**Postman Setup:**
1. Method: `PUT` or `PATCH`
2. URL: `http://localhost/student_backend/students/1`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: application/json`
4. Body tab → Select **raw** → Choose **JSON**
5. Add only the fields you want to update:

```json
{
  "first_name": "John",
  "last_name": "Smith",
  "phone": "987-654-3210"
}
```

**Alternative: Update Student (x-www-form-urlencoded)**

**Postman Setup:**
1. Method: `PUT` or `PATCH`
2. URL: `http://localhost/student_backend/students/1`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
4. Body tab → Select **x-www-form-urlencoded**
5. Add only the fields you want to update:

| Key | Value |
|-----|-------|
| first_name | John |
| last_name | Smith |
| phone | 987-654-3210 |

**Note:** For PUT/PATCH requests, use JSON or x-www-form-urlencoded. Form-data may not work properly with PUT/PATCH due to PHP limitations.

---

### Example: Create Attendance (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/attendance`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
4. Body tab → Select **form-data**
5. Add the following key-value pairs:

| Key | Value |
|-----|-------|
| student_id | 1 |
| date | 2024-01-15 |
| status | present |
| notes | On time |

---

### Example: Create Grade (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/grades`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
4. Body tab → Select **form-data**
5. Add the following key-value pairs:

| Key | Value |
|-----|-------|
| student_id | 1 |
| subject_id | 1 |
| grade | 85.50 |
| exam_type | midterm |
| exam_date | 2024-01-15 |
| notes | Good performance |

---

### Example: Change Password (Form-Data)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/student_backend/auth/change-password`
3. Headers:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
4. Body tab → Select **form-data**
5. Add the following key-value pairs:

| Key | Value |
|-----|-------|
| current_password | oldpassword123 |
| new_password | newpassword123 |

---

**Tips for Using Form-Data in Postman:**

1. **Select form-data**: In the Body tab, make sure you select **form-data** (not raw or x-www-form-urlencoded)
2. **Text vs File**: For regular form fields, keep the type as **Text**. Only use **File** if you're uploading actual files
3. **Numbers**: Enter numbers as text (e.g., "1" for class_id, "85.50" for grade)
4. **Dates**: Use YYYY-MM-DD format (e.g., "2024-01-15")
5. **Authentication**: Don't forget to add the Authorization header with your token

---

## Rate Limiting

Currently, there are no rate limits implemented. However, it is recommended to implement rate limiting in production environments.

## Versioning

The current API version is **v1**. Future versions will be indicated in the URL path (e.g., `/v2/students`).

## Support

For issues, questions, or contributions, please refer to the project repository or contact the development team.

---

**Last Updated:** January 2024

