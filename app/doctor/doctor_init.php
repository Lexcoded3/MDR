<?php
// doctor/doctor_init.php
$doctor_id   = (int)$_SESSION['id'];
$doctor_name = $_SESSION['name'] ?? 'Doctor';
$doctor_loc  = $_SESSION['location'] ?? '';

function getDoctorStats($conn, $doctor_id) {
    $stats = [];

    $loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
    $loc_stmt->bind_param("i", $doctor_id);
    $loc_stmt->execute();
    $doc_loc = $loc_stmt->get_result()->fetch_column();
    $loc_stmt->close();
    $like = "%$doc_loc%";

    $stats['location'] = $doc_loc;

    // Total patients at facility
    $s = $conn->prepare("
        SELECT COUNT(*) FROM patients p
        WHERE p.is_active = 1
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['total_patients'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // On treatment
    $s = $conn->prepare("
        SELECT COUNT(*) FROM patients p
        WHERE p.is_active = 1 AND p.treatment_status = 'on_treatment'
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['on_treatment'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Pending regimen reviews
    $s = $conn->prepare("
        SELECT COUNT(*) FROM treatment_regimens tr
        JOIN patients p ON tr.patient_id = p.id
        WHERE tr.status = 'pending_review'
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['pending_reviews'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Critical adverse events
    $s = $conn->prepare("
        SELECT COUNT(*) FROM adverse_events ae
        JOIN patients p ON ae.patient_id = p.id
        WHERE ae.resolution_date IS NULL AND ae.severity IN ('severe', 'life_threatening')
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['critical_ae'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // All active adverse events
    $s = $conn->prepare("
        SELECT COUNT(*) FROM adverse_events ae
        JOIN patients p ON ae.patient_id = p.id
        WHERE ae.resolution_date IS NULL
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['active_ae'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Low adherence patients
    $s = $conn->prepare("
        SELECT COUNT(DISTINCT patient_id) FROM (
            SELECT patient_id,
                   ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS adh_pct
            FROM adherence_logs
            WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY patient_id
            HAVING adh_pct < 85
        ) low_adh
        JOIN patients p ON low_adh.patient_id = p.id
        WHERE p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['low_adherence'] = (int)$s->get_result()->fetch_column();
    $s->close();

    // Pending lab results
    $s = $conn->prepare("
        SELECT COUNT(*) FROM lab_results lr
        JOIN patients p ON lr.patient_id = p.id
        WHERE lr.is_final = 0
        AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ");
    $s->bind_param("s", $like); $s->execute();
    $stats['pending_labs'] = (int)$s->get_result()->fetch_column();
    $s->close();

    return $stats;
}