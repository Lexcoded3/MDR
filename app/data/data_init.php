<?php
// data/data_init.php
// Call AFTER session_start, auth_check, db.php

 $data_officer_id   = (int)$_SESSION['id'];
 $data_officer_name = $_SESSION['name'] ?? 'Data Officer';

function getDataOverview($conn) {
    $o = [];
    
    // Total patients
    $o['total_patients'] = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE is_active = 1")->fetch_column();
    
    // Currently on treatment
    $o['on_treatment'] = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE is_active = 1 AND treatment_status = 'on_treatment'")->fetch_column();
    
    // Treatment outcomes (completed + cured = success)
    $o['cured'] = (int)$conn->query("SELECT COUNT(*) FROM treatment_outcomes WHERE outcome IN ('cured','treatment_completed')")->fetch_column();
    $o['failed'] = (int)$conn->query("SELECT COUNT(*) FROM treatment_outcomes WHERE outcome = 'treatment_failed'")->fetch_column();
    $o['died'] = (int)$conn->query("SELECT COUNT(*) FROM treatment_outcomes WHERE outcome = 'died'")->fetch_column();
    $o['ltfu'] = (int)$conn->query("SELECT COUNT(*) FROM treatment_outcomes WHERE outcome = 'lost_to_followup'")->fetch_column();
    
    // 30-day adherence averages
    $adh_avg = $conn->query("
        SELECT AVG(adh_pct) AS avg_adh FROM (
            SELECT patient_id,
                   ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS adh_pct
            FROM adherence_logs
            WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY patient_id
        ) t
    ")->fetch_assoc();
    $o['avg_adherence_30d'] = round($adh_avg['avg_adh'] ?? 0, 1);
    
    // Active adverse events
    $o['active_ae'] = (int)$conn->query("SELECT COUNT(*) FROM adverse_events WHERE resolution_date IS NULL")->fetch_column();
    
    // SMS stats this month
    $o['sms_sent_month'] = (int)$conn->query("SELECT COUNT(*) FROM sms_logs WHERE status IN ('sent','delivered') AND sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_column();
    $o['sms_failed_month'] = (int)$conn->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'failed' AND sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_column();
    
    // Facility distribution
    $o['facility_count'] = (int)$conn->query("SELECT COUNT(DISTINCT facility_id) FROM patients WHERE is_active = 1 AND facility_id IS NOT NULL")->fetch_column();
    
    return $o;
}