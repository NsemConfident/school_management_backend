# Timetable Generation System (TGS)

## Overview

The Timetable Generation System (TGS) is a comprehensive backend API system designed to automate the creation and management of class, exam, and continuous assessment timetables for schools. The system ensures conflict-free scheduling, provides student-specific timetable access, sends real-time notifications, and supports automated updates.

## Key Features

### 1. Automated Timetable Generation
- Automatically generates conflict-free class timetables
- Intelligent scheduling algorithm that distributes subjects across days and time slots
- Configurable time slots and days of week
- Automatic conflict detection and resolution

### 2. Conflict-Free Scheduling
- **Teacher Conflicts**: Prevents same teacher from being assigned to multiple classes simultaneously
- **Room Conflicts**: Ensures no room double-booking
- **Class Conflicts**: Prevents overlapping exams/assessments for the same class
- **Time Overlaps**: Detects and prevents time slot conflicts

### 3. Student-Specific Access
- Students can view their personalized class timetable
- Access to exam timetables for their class
- View upcoming assessments and due dates
- Real-time updates when timetables change

### 4. Real-Time Notifications
- Automatic notifications when timetables are created/updated
- Exam schedule notifications
- Assessment due date reminders
- Conflict alerts for administrators
- Notification system supports multiple notification types

### 5. Multi-User Support
- **Administrators**: Full access to create, edit, and manage all timetables
- **Teachers**: Can create and manage timetables for their classes
- **Students**: View-only access to their personal timetables

## System Architecture

### Database Tables

1. **timetables**: Main class timetable records
2. **timetable_slots**: Individual time slots for class timetables
3. **exam_timetables**: Exam timetable records
4. **exam_timetable_slots**: Individual exam slots
5. **assessment_timetables**: Continuous assessment timetable records
6. **assessment_timetable_slots**: Individual assessment slots
7. **notifications**: User notifications
8. **class_subjects**: Many-to-many relationship between classes and subjects
9. **teacher_subjects**: Many-to-many relationship between teachers and subjects
10. **timetable_conflicts**: Conflict tracking and resolution

### Models

- `TimetableModel`: Handles class timetable operations
- `TimetableSlotModel`: Manages individual timetable slots
- `ExamTimetableModel`: Handles exam timetable operations
- `AssessmentTimetableModel`: Handles assessment timetable operations
- `NotificationModel`: Manages notifications
- `ClassSubjectModel`: Manages class-subject relationships

### Controllers

- `TimetableController`: API endpoints for class timetables
- `ExamTimetableController`: API endpoints for exam timetables
- `AssessmentTimetableController`: API endpoints for assessment timetables
- `NotificationController`: API endpoints for notifications

### Services

- `TimetableGenerator`: Automated timetable generation service with conflict detection

## Installation

1. **Database Setup**
   ```sql
   mysql -u root -p school_management < database/schema.sql
   ```
   Or import `database/schema.sql` through phpMyAdmin.

2. **Configuration**
   - Update `config/database.php` with your database credentials if needed
   - Default settings work with XAMPP (localhost, root, no password)

3. **Server Requirements**
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Apache/Nginx web server
   - mod_rewrite enabled (for clean URLs)

## API Endpoints Summary

### Class Timetables
- `GET /timetables` - Get all timetables
- `GET /timetables/{id}` - Get timetable by ID
- `GET /timetables/active?class_id=1` - Get active timetable for class
- `GET /timetables/student` - Get student's timetable
- `POST /timetables` - Create timetable
- `POST /timetables/generate` - Auto-generate timetable
- `POST /timetables/activate/{id}` - Activate timetable
- `POST /timetables/check-conflicts` - Check for conflicts
- `POST /timetables/slots` - Add slot
- `PUT /timetables/slots/{id}` - Update slot
- `DELETE /timetables/slots/{id}` - Delete slot

### Exam Timetables
- `GET /exam-timetables` - Get all exam timetables
- `GET /exam-timetables/{id}` - Get exam timetable by ID
- `GET /exam-timetables/student` - Get student's exam timetable
- `POST /exam-timetables` - Create exam timetable
- `POST /exam-timetables/publish/{id}` - Publish exam timetable
- `POST /exam-timetables/slots` - Add exam slot
- `POST /exam-timetables/check-conflicts` - Check for conflicts

### Assessment Timetables
- `GET /assessment-timetables` - Get all assessment timetables
- `GET /assessment-timetables/{id}` - Get assessment timetable by ID
- `GET /assessment-timetables/student` - Get student's upcoming assessments
- `POST /assessment-timetables` - Create assessment timetable
- `POST /assessment-timetables/publish/{id}` - Publish assessment timetable
- `POST /assessment-timetables/slots` - Add assessment slot

### Notifications
- `GET /notifications/my` - Get my notifications
- `GET /notifications/unread-count` - Get unread count
- `POST /notifications/mark-read/{id}` - Mark notification as read
- `POST /notifications/mark-all-read` - Mark all as read

### Class Subjects
- `GET /class-subjects/class/{class_id}` - Get subjects for a class
- `GET /class-subjects/subject/{subject_id}` - Get classes for a subject
- `POST /class-subjects` - Create class-subject relationship

## Usage Examples

### Generate a Timetable for a Class

```bash
POST /timetables/generate
Content-Type: application/json
Authorization: Bearer <admin_token>

{
  "class_id": 1,
  "academic_year": "2024",
  "start_date": "2024-09-01",
  "end_date": "2024-12-31"
}
```

### Get Student's Timetable

```bash
GET /timetables/student
Authorization: Bearer <student_token>
```

### Create and Publish Exam Timetable

```bash
# 1. Create exam timetable
POST /exam-timetables
{
  "exam_name": "Final Examinations",
  "exam_type": "final",
  "academic_year": "2024",
  "start_date": "2024-12-15",
  "end_date": "2024-12-20"
}

# 2. Add exam slots
POST /exam-timetables/slots
{
  "exam_timetable_id": 1,
  "subject_id": 1,
  "class_id": 1,
  "exam_date": "2024-12-15",
  "start_time": "09:00:00",
  "end_time": "11:00:00"
}

# 3. Publish (sends notifications to students)
POST /exam-timetables/publish/1
```

## Notification System

The system automatically sends notifications for:
- Timetable creation/updates
- Exam schedule publication
- Assessment assignments
- Conflict detection
- Reminders

Notifications are sent to:
- All students in a class (for class-specific events)
- Individual users (for personal notifications)
- All users with a specific role (for role-based notifications)

## Conflict Detection

The system automatically detects:
1. **Teacher Conflicts**: Same teacher in multiple classes at the same time
2. **Room Conflicts**: Same room booked by multiple classes simultaneously
3. **Class Conflicts**: Same class having multiple exams/assessments at once
4. **Time Overlaps**: Overlapping time slots

Conflicts are detected before saving and can be checked manually using the check-conflicts endpoints.

## Security

- Token-based authentication required for most endpoints
- Role-based access control (admin, teacher, student)
- Input validation and sanitization
- SQL injection prevention using prepared statements

## Testing

Use tools like Postman or cURL to test the API:

```bash
# Login to get token
curl -X POST http://localhost/student_backend/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Use token for authenticated requests
curl -X GET http://localhost/student_backend/timetables \
  -H "Authorization: Bearer <token>"
```

## Documentation

For detailed API documentation, see `TGS_API_DOCUMENTATION.md`.

## Support

For issues or questions, refer to the API documentation or check the code comments in the respective controller/model files.

