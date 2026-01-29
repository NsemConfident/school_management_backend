# Timetable Generation System (TGS) API Documentation

## Overview

The Timetable Generation System (TGS) is a comprehensive API system that automates the creation of class, exam, and continuous assessment timetables for schools. It ensures conflict-free scheduling, student-specific timetable access, real-time notifications, and automated updates.

## Features

- **Automated Timetable Generation**: Automatically creates conflict-free class timetables
- **Conflict Detection**: Checks for teacher, room, and time slot conflicts
- **Student-Specific Access**: Students can view their personalized timetables
- **Real-Time Notifications**: Automated notifications for timetable updates, exams, and assessments
- **Multi-User Support**: Designed for administrators, teachers, and students

## Base URL

```
http://localhost/student_backend/
```

## Authentication

Most endpoints require authentication. Include the token in the request header:

```
Authorization: Bearer <token>
```

Or use the custom header:
```
X-Auth-Token: <token>
```

## API Endpoints

### Class Timetables

#### Get All Timetables
```
GET /timetables
GET /timetables?class_id=1&academic_year=2024
```

**Response:**
```json
{
  "success": true,
  "message": "Timetables retrieved successfully",
  "data": [...]
}
```

#### Get Timetable by ID
```
GET /timetables/{id}
```

Returns timetable with all slots included.

#### Get Active Timetable for Class
```
GET /timetables/active?class_id=1
```

#### Get Student's Timetable
```
GET /timetables/student
```

Requires student authentication. Returns the active timetable for the logged-in student's class.

#### Create Timetable
```
POST /timetables
```

**Request Body:**
```json
{
  "class_id": 1,
  "academic_year": "2024",
  "semester": "Fall",
  "start_date": "2024-09-01",
  "end_date": "2024-12-31",
  "status": "draft"
}
```

#### Generate Timetable Automatically
```
POST /timetables/generate
```

**Request Body:**
```json
{
  "class_id": 1,
  "academic_year": "2024",
  "semester": "Fall",
  "start_date": "2024-09-01",
  "end_date": "2024-12-31"
}
```

**Response:**
```json
{
  "success": true,
  "message": "10 slots created successfully",
  "data": {
    "timetable": {...},
    "slots_created": 10,
    "conflicts": []
  }
}
```

#### Activate Timetable
```
POST /timetables/activate/{id}
```

Activates a timetable and deactivates others for the same class.

#### Check Conflicts
```
POST /timetables/check-conflicts
```

**Request Body:**
```json
{
  "timetable_id": 1,
  "day_of_week": "Monday",
  "start_time": "09:00:00",
  "end_time": "09:50:00",
  "teacher_id": 1,
  "room_number": "A101"
}
```

#### Add Timetable Slot
```
POST /timetables/slots
```

**Request Body:**
```json
{
  "timetable_id": 1,
  "class_subject_id": 1,
  "day_of_week": "Monday",
  "start_time": "09:00:00",
  "end_time": "09:50:00",
  "room_number": "A101",
  "teacher_id": 1,
  "notes": "Optional notes"
}
```

#### Update Timetable Slot
```
PUT /timetables/slots/{slot_id}
```

#### Delete Timetable Slot
```
DELETE /timetables/slots/{slot_id}
```

### Exam Timetables

#### Get All Exam Timetables
```
GET /exam-timetables
GET /exam-timetables?academic_year=2024&exam_type=final&status=published
```

#### Get Exam Timetable by ID
```
GET /exam-timetables/{id}
```

Returns exam timetable with all slots.

#### Get Student's Exam Timetable
```
GET /exam-timetables/student
GET /exam-timetables/student?academic_year=2024
```

Requires student authentication.

#### Create Exam Timetable
```
POST /exam-timetables
```

**Request Body:**
```json
{
  "exam_name": "Final Examinations",
  "exam_type": "final",
  "academic_year": "2024",
  "semester": "Fall",
  "start_date": "2024-12-15",
  "end_date": "2024-12-20",
  "status": "draft"
}
```

#### Publish Exam Timetable
```
POST /exam-timetables/publish/{id}
```

Publishes the exam timetable and sends notifications to all affected students.

#### Add Exam Slot
```
POST /exam-timetables/slots
```

**Request Body:**
```json
{
  "exam_timetable_id": 1,
  "subject_id": 1,
  "class_id": 1,
  "exam_date": "2024-12-15",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "room_number": "A101",
  "invigilator_id": 1,
  "max_students": 30,
  "notes": "Bring calculator"
}
```

#### Check Exam Conflicts
```
POST /exam-timetables/check-conflicts
```

**Request Body:**
```json
{
  "exam_timetable_id": 1,
  "exam_date": "2024-12-15",
  "start_time": "09:00:00",
  "end_time": "11:00:00",
  "class_id": 1,
  "room_number": "A101",
  "invigilator_id": 1
}
```

### Assessment Timetables

#### Get All Assessment Timetables
```
GET /assessment-timetables
GET /assessment-timetables?academic_year=2024&assessment_type=quiz&status=published
```

#### Get Assessment Timetable by ID
```
GET /assessment-timetables/{id}
```

#### Get Student's Upcoming Assessments
```
GET /assessment-timetables/student
GET /assessment-timetables/student?limit=10
```

Requires student authentication. Returns upcoming assessments for the logged-in student.

#### Create Assessment Timetable
```
POST /assessment-timetables
```

**Request Body:**
```json
{
  "assessment_name": "Weekly Quizzes",
  "assessment_type": "quiz",
  "academic_year": "2024",
  "semester": "Fall",
  "start_date": "2024-09-01",
  "end_date": "2024-12-31",
  "status": "draft"
}
```

#### Publish Assessment Timetable
```
POST /assessment-timetables/publish/{id}
```

#### Add Assessment Slot
```
POST /assessment-timetables/slots
```

**Request Body:**
```json
{
  "assessment_timetable_id": 1,
  "subject_id": 1,
  "class_id": 1,
  "assessment_date": "2024-10-15",
  "due_date": "2024-10-20",
  "start_time": "10:00:00",
  "end_time": "11:00:00",
  "room_number": "A101",
  "teacher_id": 1,
  "instructions": "Complete all questions"
}
```

### Notifications

#### Get My Notifications
```
GET /notifications/my
GET /notifications/my?unread_only=1
```

Requires authentication. Returns notifications for the logged-in user.

#### Get Unread Count
```
GET /notifications/unread-count
```

#### Mark Notification as Read
```
POST /notifications/mark-read/{id}
```

#### Mark All Notifications as Read
```
POST /notifications/mark-all-read
```

#### Create Notification (Admin/Teacher)
```
POST /notifications
```

**Request Body:**
```json
{
  "user_id": 1,
  "user_role": "student",
  "class_id": 1,
  "notification_type": "reminder",
  "title": "Reminder",
  "message": "Don't forget your exam tomorrow",
  "related_id": 1,
  "related_type": "exam_timetable"
}
```

#### Get Notifications by Role (Admin Only)
```
GET /notifications/by-role?role=student&unread_only=1
```

### Class Subjects

#### Get Subjects for a Class
```
GET /class-subjects/class/{class_id}
```

#### Get Classes for a Subject
```
GET /class-subjects/subject/{subject_id}
```

#### Create Class-Subject Relationship
```
POST /class-subjects
```

**Request Body:**
```json
{
  "class_id": 1,
  "subject_id": 1,
  "teacher_id": 1
}
```

## Notification Types

- `timetable_created`: New timetable created
- `timetable_updated`: Timetable updated
- `timetable_conflict`: Conflict detected in timetable
- `exam_scheduled`: Exam scheduled
- `assessment_due`: Assessment due
- `reminder`: General reminder
- `system`: System notification

## Conflict Detection

The system automatically detects conflicts for:
- **Teacher Conflicts**: Same teacher assigned to multiple classes at the same time
- **Room Conflicts**: Same room booked by multiple classes at the same time
- **Class Conflicts**: Same class having multiple exams/assessments at the same time
- **Time Overlaps**: Overlapping time slots

## Status Values

### Timetable Status
- `draft`: Timetable is being created/edited
- `active`: Timetable is currently active
- `archived`: Timetable is no longer active

### Exam/Assessment Timetable Status
- `draft`: Timetable is being created/edited
- `published`: Timetable is published and visible to students
- `ongoing`: Exams/assessments are currently in progress
- `completed`: All exams/assessments are completed
- `archived`: Timetable is archived

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

Common HTTP status codes:
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `404`: Not Found
- `405`: Method Not Allowed
- `500`: Internal Server Error

## Example Usage

### 1. Generate a Timetable for a Class

```bash
POST /timetables/generate
Authorization: Bearer <admin_token>

{
  "class_id": 1,
  "academic_year": "2024",
  "start_date": "2024-09-01",
  "end_date": "2024-12-31"
}
```

### 2. Get Student's Timetable

```bash
GET /timetables/student
Authorization: Bearer <student_token>
```

### 3. Create and Publish Exam Timetable

```bash
# Create exam timetable
POST /exam-timetables
Authorization: Bearer <admin_token>

{
  "exam_name": "Midterm Examinations",
  "exam_type": "midterm",
  "academic_year": "2024",
  "start_date": "2024-10-15",
  "end_date": "2024-10-20"
}

# Add exam slots
POST /exam-timetables/slots
Authorization: Bearer <admin_token>

{
  "exam_timetable_id": 1,
  "subject_id": 1,
  "class_id": 1,
  "exam_date": "2024-10-15",
  "start_time": "09:00:00",
  "end_time": "11:00:00"
}

# Publish
POST /exam-timetables/publish/1
Authorization: Bearer <admin_token>
```

### 4. Get Student's Notifications

```bash
GET /notifications/my?unread_only=1
Authorization: Bearer <student_token>
```

## Database Setup

Run the SQL script in `database/schema.sql` to create all necessary tables:

```sql
mysql -u root -p school_management < database/schema.sql
```

Or import it through phpMyAdmin.

## Notes

- All dates should be in `YYYY-MM-DD` format
- All times should be in `HH:MM:SS` format (24-hour format)
- Day of week values: `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `Sunday`
- Exam types: `midterm`, `final`, `quiz`, `test`
- Assessment types: `quiz`, `assignment`, `project`, `presentation`, `lab`, `other`

