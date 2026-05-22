<?php
// nurse/nurse_init.php
// Call AFTER session_start, auth_check, db.php

$nurse_id   = (int)$_SESSION['id'];
$nurse_name = $_SESSION['name'] ?? 'Nurse';

function getNurseStats($conn, $nurse_id) {
    $stats = [];
    
    // Patients assigned to this nurse (via facility matching or direct assignment)
    // Nurses see patients at their facility who are on treatment
    $nurse_facility = $conn->prepare("SELECT location FROM users WHERE id = ?");
    $nurse_facility->bind_param("i", $nurse_id);
    $nurse_facility->execute();
    $nurse_loc = $nurse_facility->get_result()->fetch_column();
    
    // All patients on treatment at this nurse's location
    $stats['my_facility'] = $nurse_loc;
    
    $s = $conn->prepare("
        SELECT COUNT(*) FROM patients p
        JOIN treatment_regimens tr ON p.id = tr.patient_id AND tr.status = 'active'
        WHERE p.is_active = 1 AND p.treatment_status = 'on_treatment' AND p.facility_id IN (
            SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%')
        )
    ");
    $like = "%$nurse_loc%";
    $s->bind_param("s", $like);
    $s->execute();
    $stats['active_patients'] = (int)$s->get_result()->fetch_column();
    
    // Doses logged today by this nurse
    $s = $conn->prepare("
        SELECT COUNT(*) FROM adherence_logs al
        WHERE al.verified_by = ? AND DATE(al.created_at) = CURDATE()
    ");
    $s->bind_param("i", $nurse_id);
    $s->execute();
    $stats['logged_today'] = (int)$s->get_result()->fetch_column();
    
    // Doses due today (not yet logged)
    $s = $conn->prepare("
        SELECT COUNT(DISTINCT ms.id) FROM medication_schedule ms
        JOIN treatment_regimens tr ON ms.regimen_id = tr.id
        JOIN patients p ON tr.patient_id = p.id
        WHERE ms.is_active = 1
        AND tr.status = 'active'
        AND p.is_active = 1 AND p.treatment_status = 'on_treatment'
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
        AND ms.dose_time <= DATE_ADD(CURTIME(), INTERVAL 30 MINUTE)
        AND NOT EXISTS (
            SELECT 1 FROM adherence_logs al
            WHERE al.patient_id = p.id
            AND al.schedule_id = ms.id
            AND al.dose_date = CURDATE()
        )
    ");
    $s->bind_param("s", $like);
    $s->execute();
    $stats['due_now'] = (int)$s->get_result()->fetch_column();
    
    // Missed doses today (past dose_time, no log)
    $s = $conn->prepare("
        SELECT COUNT(DISTINCT ms.id) FROM medication_schedule ms
        JOIN treatment_regimens tr ON ms.regimen_id = tr.id
        JOIN patients p ON tr.patient_id = p.id
        WHERE ms.is_active = 1
        AND tr.status = 'active'
        AND p.is_active = 1 AND p.treatment_status = 'on_treatment'
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
        AND ms.dose_time < CURTIME()
        AND NOT EXISTS (
            SELECT 1 FROM adherence_logs al
            WHERE al.patient_id = p.id
            AND al.schedule_id = ms.id
            AND al.dose_date = CURDATE()
        )
    ");
    $s->bind_param("s", $like);
    $s->execute();
    $stats['missed_today'] = (int)$s->get_result()->fetch_column();
    
    // Active adverse events among facility patients
    $s = $conn->prepare("
        SELECT COUNT(*) FROM adverse_events ae
        JOIN patients p ON ae.patient_id = p.id
        WHERE ae.resolution_date IS NULL AND p.is_active = 1
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like);
    $s->execute();
    $stats['active_ae'] = (int)$s->get_result()->fetch_column();
    
    return $stats;
}

// Helper: get patient list for this nurse's facility
function getNursePatients($conn, $nurse_id) {
    $nurse_facility = $conn->prepare("SELECT location FROM users WHERE id = ?");
    $nurse_facility->bind_param("i", $nurse_id);
    $nurse_facility->execute();
    $nurse_loc = $nurse_facility->get_result()->fetch_column();
    
    $stmt = $conn->prepare("
        SELECT p.id, p.full_name, p.patient_code, p.gender, p.treatment_status,
               p.enrollment_date, p.weight_kg,
               f.name AS facility_name
        FROM patients p
        LEFT JOIN facilities f ON p.facility_id = f.id
        WHERE p.is_active = 1
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
        ORDER BY p.full_name
    ");
    $like = "%$nurse_loc%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}