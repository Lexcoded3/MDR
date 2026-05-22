<?php
// lab/lab_init.php
// Call AFTER session_start, auth_check, db.php

 $lab_id   = (int)$_SESSION['id'];
 $lab_name = $_SESSION['name'] ?? 'Lab Technician';

function getLabStats($conn, $lab_id) {
    $stats = [];

    $s = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE uploaded_by = ? AND DATE(created_at) = CURDATE()");
    $s->bind_param("i", $lab_id);
    $s->execute();
    $stats['today_results'] = (int)$s->get_result()->fetch_column();

    $s = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE uploaded_by = ? AND is_final = 0");
    $s->bind_param("i", $lab_id);
    $s->execute();
    $stats['preliminary'] = (int)$s->get_result()->fetch_column();

    $s = $conn->prepare("SELECT COUNT(*) FROM drug_susceptibility WHERE performed_by = ? AND DATE(created_at) = CURDATE()");
    $s->bind_param("i", $lab_id);
    $s->execute();
    $stats['today_dst'] = (int)$s->get_result()->fetch_column();

    // Resistant results in last 30 days (critical finding)
    $s = $conn->prepare("
        SELECT COUNT(*) FROM drug_susceptibility 
        WHERE performed_by = ? AND result = 'resistant' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $s->bind_param("i", $lab_id);
    $s->execute();
    $stats['resistant_30d'] = (int)$s->get_result()->fetch_column();

    return $stats;
}