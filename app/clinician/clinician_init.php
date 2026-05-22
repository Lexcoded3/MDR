<?php
// clinician/clinician_init.php
// Call AFTER session_start, auth_check, db.php

 $clinician_id = (int)$_SESSION['id'];
 $clinician_name = $_SESSION['name'] ?? 'Clinician';

// Count stats for dashboard reuse
function getClinicianStats($conn, $clinician_id) {
    $stats = [];

    // Total patients enrolled by this clinician (active)
    $s = $conn->prepare("SELECT COUNT(*) FROM patients WHERE created_by = ? AND is_active = 1");
    $s->bind_param("i", $clinician_id);
    $s->execute();
    $stats['total_patients'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // On treatment
    $s = $conn->prepare("SELECT COUNT(*) FROM patients WHERE created_by = ? AND is_active = 1 AND treatment_status = 'on_treatment'");
    $s->bind_param("i", $clinician_id);
    $s->execute();
    $stats['on_treatment'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Lost to follow-up
    $s = $conn->prepare("SELECT COUNT(*) FROM patients WHERE created_by = ? AND is_active = 1 AND treatment_status = 'lost_to_followup'");
    $s->bind_param("i", $clinician_id);
    $s->execute();
    $stats['ltfu'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Active adverse events among their patients
    $s = $conn->prepare("
        SELECT COUNT(*) FROM adverse_events ae
        JOIN patients p ON ae.patient_id = p.id
        WHERE p.created_by = ? AND ae.resolution_date IS NULL AND p.is_active = 1
    ");
    $s->bind_param("i", $clinician_id);
    $s->execute();
    $stats['active_ae'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Pending appointments today
    $s = $conn->prepare("
        SELECT COUNT(*) FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE p.created_by = ? AND a.appointment_date >= CURDATE() AND a.appointment_date < CURDATE() + INTERVAL 1 DAY AND a.status = 'pending'
    ");
    $s->bind_param("i", $clinician_id);
    $s->execute();
    $stats['today_appts'] = (int)$s->get_result()->fetch_column();
    $s->close();

    return $stats;
}