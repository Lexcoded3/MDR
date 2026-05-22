<?php
session_start();
 $required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

 $patient_id = (int)($_GET['patient_id'] ?? 0);
 $drug_id    = (int)($_GET['drug_id'] ?? 0);
 $dose_time  = $_GET['dose_time'] ?? '';
 $log_date   = $_GET['date'] ?? date('Y-m-d');

$schedule_id = (int)($_GET['schedule_id'] ?? 0); 

if ($patient_id > 0 && $schedule_id > 0) { // Check for schedule_id instead
    $actual_time = date('H:i:s');
    
    // Updated query using schedule_id and correcting column names
    $up = $conn->prepare("
        INSERT INTO adherence_logs (patient_id, schedule_id, dose_date,  status, actual_time_taken, logged_by, logged_at)
        VALUES (?, ?, ?, ?, 'taken', ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            status = 'taken', 
            actual_time_taken = VALUES(actual_time_taken), 
            logged_by = VALUES(logged_by), 
            logged_at = NOW()
    ");
    
    // "actual_time_taken" matches the column name we saw in your earlier queries
    $up->bind_param("iisssi", $patient_id, $schedule_id, $log_date, $actual_time, $nurse_id);
    $up->execute();
}

// Go back to wherever they came from
 $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit;