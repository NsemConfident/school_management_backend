<?php
/**
 * Timetable Generation Service
 * Automates the creation of conflict-free timetables
 */

require_once __DIR__ . '/../models/TimetableModel.php';
require_once __DIR__ . '/../models/TimetableSlotModel.php';
require_once __DIR__ . '/../models/ClassSubjectModel.php';
require_once __DIR__ . '/../config/database.php';

class TimetableGenerator {
    private $timetableModel;
    private $slotModel;
    private $classSubjectModel;
    private $conn;
    
    // Default time slots (can be customized)
    private $timeSlots = [
        ['start' => '08:00:00', 'end' => '08:50:00'],
        ['start' => '09:00:00', 'end' => '09:50:00'],
        ['start' => '10:00:00', 'end' => '10:50:00'],
        ['start' => '11:00:00', 'end' => '11:50:00'],
        ['start' => '12:00:00', 'end' => '12:50:00'],
        ['start' => '13:00:00', 'end' => '13:50:00'],
        ['start' => '14:00:00', 'end' => '14:50:00'],
        ['start' => '15:00:00', 'end' => '15:50:00'],
    ];
    
    private $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    public function __construct() {
        $this->timetableModel = new TimetableModel();
        $this->slotModel = new TimetableSlotModel();
        $this->classSubjectModel = new ClassSubjectModel();
        $this->conn = getDBConnection();
    }
    
    /**
     * Generate timetable for a class automatically
     */
    public function generateTimetable($classId, $academicYear, $semester = null, $startDate = null, $endDate = null, $createdBy = null) {
        // Get all subjects for the class
        $subjects = $this->classSubjectModel->getSubjectsByClass($classId);
        
        if (empty($subjects)) {
            return [
                'success' => false,
                'message' => 'No subjects found for this class'
            ];
        }
        
        // Create timetable record
        $timetableData = [
            'class_id' => $classId,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'start_date' => $startDate ?: date('Y-m-d'),
            'end_date' => $endDate ?: date('Y-m-d', strtotime('+1 year')),
            'status' => 'draft',
            'created_by' => $createdBy
        ];
        
        $timetableId = $this->timetableModel->create($timetableData);
        
        if (!$timetableId) {
            return [
                'success' => false,
                'message' => 'Failed to create timetable: ' . $this->timetableModel->getLastError()
            ];
        }
        
        // Generate slots using backtracking algorithm
        $slots = $this->generateSlots($timetableId, $subjects);
        
        if (empty($slots)) {
            // Delete timetable if no slots could be generated
            $this->timetableModel->delete($timetableId);
            return [
                'success' => false,
                'message' => 'Could not generate conflict-free timetable. Please check teacher and room availability.'
            ];
        }
        
        // Insert slots
        $insertedSlots = [];
        $conflicts = [];
        
        foreach ($slots as $slot) {
            // Check for conflicts before inserting
            $slotConflicts = $this->timetableModel->checkConflicts(
                $timetableId,
                $slot['day_of_week'],
                $slot['start_time'],
                $slot['end_time'],
                $slot['teacher_id'] ?? null,
                $slot['room_number'] ?? null
            );
            
            if (!empty($slotConflicts)) {
                $conflicts[] = [
                    'slot' => $slot,
                    'conflicts' => $slotConflicts
                ];
                continue; // Skip conflicting slots
            }
            
            $slotId = $this->slotModel->create($slot);
            if ($slotId) {
                $insertedSlots[] = $slotId;
            }
        }
        
        return [
            'success' => true,
            'timetable_id' => $timetableId,
            'slots_created' => count($insertedSlots),
            'conflicts' => $conflicts,
            'message' => count($insertedSlots) . ' slots created successfully' . 
                        (count($conflicts) > 0 ? '. ' . count($conflicts) . ' conflicts detected and skipped.' : '')
        ];
    }
    
    /**
     * Generate slots using a simple scheduling algorithm
     */
    private function generateSlots($timetableId, $subjects) {
        $slots = [];
        $usedSlots = []; // Track used time slots to avoid duplicates
        
        // Calculate required periods per subject (assuming 3-5 periods per week per subject)
        $periodsPerSubject = [];
        foreach ($subjects as $subject) {
            $periodsPerSubject[$subject['id']] = rand(3, 5); // Can be customized
        }
        
        // Distribute subjects across days and time slots
        foreach ($subjects as $subject) {
            $periodsAssigned = 0;
            $requiredPeriods = $periodsPerSubject[$subject['id']];
            
            // Shuffle days and time slots for randomization
            $shuffledDays = $this->daysOfWeek;
            shuffle($shuffledDays);
            $shuffledTimes = $this->timeSlots;
            shuffle($shuffledTimes);
            
            foreach ($shuffledDays as $day) {
                if ($periodsAssigned >= $requiredPeriods) {
                    break;
                }
                
                foreach ($shuffledTimes as $timeSlot) {
                    if ($periodsAssigned >= $requiredPeriods) {
                        break;
                    }
                    
                    $slotKey = $day . '_' . $timeSlot['start'];
                    
                    // Check if this slot is already used for this class
                    if (!isset($usedSlots[$slotKey])) {
                        $slot = [
                            'timetable_id' => $timetableId,
                            'class_subject_id' => $subject['id'],
                            'day_of_week' => $day,
                            'start_time' => $timeSlot['start'],
                            'end_time' => $timeSlot['end'],
                            'teacher_id' => $subject['teacher_id'] ?? null,
                            'room_number' => null // Can be assigned later
                        ];
                        
                        $slots[] = $slot;
                        $usedSlots[$slotKey] = true;
                        $periodsAssigned++;
                    }
                }
            }
        }
        
        return $slots;
    }
    
    /**
     * Set custom time slots
     */
    public function setTimeSlots($timeSlots) {
        $this->timeSlots = $timeSlots;
    }
    
    /**
     * Set custom days of week
     */
    public function setDaysOfWeek($days) {
        $this->daysOfWeek = $days;
    }
}
?>

