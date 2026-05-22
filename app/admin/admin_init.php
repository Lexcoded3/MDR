<?php
// admin/admin_init.php
// Call AFTER session_start, auth_check, db.php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__)); // points to /app
}

$admin_id   = (int)$_SESSION['id'];
$admin_name = $_SESSION['name'] ?? 'Administrator';

function getSystemStats($conn) {
    $s = [];
    
    // Users by role
    $roles = $conn->query("
        SELECT role, COUNT(*) AS cnt FROM users GROUP BY role
    ")->fetch_all(MYSQLI_ASSOC);
    $s['users_by_role'] = [];
    foreach ($roles as $r) {
        $s['users_by_role'][$r['role']] = (int)$r['cnt'];
    }
    $s['total_users'] = array_sum($s['users_by_role']);
    
    // Total patients
    $s['total_patients'] = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE is_active = 1")->fetch_column();
    
    // Active facilities
    $s['active_facilities'] = (int)$conn->query("SELECT COUNT(*) FROM facilities WHERE is_active = 1")->fetch_column();
    
    // Drugs in catalog
    $s['total_drugs'] = (int)$conn->query("SELECT COUNT(*) FROM drugs WHERE is_active = 1")->fetch_column();
    
    // SMS sent today
    $s['sms_today'] = (int)$conn->query("SELECT COUNT(*) FROM sms_logs WHERE DATE(sent_at) = CURDATE() AND status IN ('sent','delivered')")->fetch_column();
    
    // SMS failed today
    $s['sms_failed_today'] = (int)$conn->query("SELECT COUNT(*) FROM sms_logs WHERE DATE(sent_at) = CURDATE() AND status = 'failed'")->fetch_column();
    
    // Patients on treatment
    $s['on_treatment'] = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE is_active = 1 AND treatment_status = 'on_treatment'")->fetch_column();
    
    // Active adverse events
    $s['active_ae'] = (int)$conn->query("SELECT COUNT(*) FROM adverse_events WHERE resolution_date IS NULL")->fetch_column();
    
    // System uptime indicator (last cron run)
    $last_cron = $conn->query("SELECT MAX(sent_at) FROM sms_logs")->fetch_column();
    $s['last_cron_activity'] = $last_cron;
    $s['cron_healthy'] = $last_cron ? (time() - strtotime($last_cron)) < 3600 : false;
    
    return $s;
}

// Role labels for display
 $role_labels = [
    'patient'       => 'Patient',
    'doctor'        => 'Doctor',
    'nurse'         => 'Nurse',
    'clinician'     => 'Clinician',
    'lab_personnel' => 'Lab Personnel',
    'data_officer'  => 'Data Officer',
    'admin'         => 'Administrator',
];

 $role_colors = [
    'patient'       => 'bg-info/10 text-info',
    'doctor'        => 'bg-primary/10 text-primary',
    'nurse'         => 'bg-success/10 text-success',
    'clinician'     => 'bg-accent/10 text-accent',
    'lab_personnel' => 'bg-warning/10 text-warning',
    'data_officer'  => 'bg-secondary/10 text-secondary',
    'admin'         => 'bg-error/10 text-error',
];