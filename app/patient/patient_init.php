<?php
// patient/patient_init.php
// Call this AFTER session_start, auth_check, and db.php are loaded

 $stmt = $conn->prepare("
    SELECT p.*, f.name AS facility_name 
    FROM patients p 
    LEFT JOIN facilities f ON p.facility_id = f.id 
    WHERE p.user_id = ? AND p.is_active = 1
");
 $stmt->bind_param("i", $_SESSION['id']);
 $stmt->execute();
 $patient_res = $stmt->get_result();

if ($patient_res->num_rows !== 1) {
    header("Location: ../auth/?error=no_record");
    exit;
}

 $patient   = $patient_res->fetch_assoc();
 $patient_id = (int)$patient['id'];

// Derived values used across multiple pages
 $age = null;
if ($patient['date_of_birth']) {
    $age = (new DateTime('today'))->diff(new DateTime($patient['date_of_birth']))->y;
}

 $status_labels = [
    'enrolled'         => 'Enrolled',
    'on_treatment'     => 'On Treatment',
    'completed'        => 'Completed',
    'cured'            => 'Cured',
    'failed'           => 'Failed',
    'died'             => 'Died',
    'lost_to_followup' => 'Lost to Follow-up',
    'transferred_out'  => 'Transferred Out',
];

// Get active regimen (used by medications, adherence, dashboard)
 $regimen = null;
 $reg_stmt = $conn->prepare("
    SELECT tr.*,
           GROUP_CONCAT(
               CONCAT(d.drug_code, ' ', rd.dose_mg, 'mg x', rd.frequency_per_day, '/day')
               SEPARATOR ' | '
           ) AS drug_summary
    FROM treatment_regimens tr
    LEFT JOIN regimen_drugs rd ON tr.id = rd.regimen_id AND rd.is_active = 1
    LEFT JOIN drugs d ON rd.drug_id = d.id
    WHERE tr.patient_id = ? AND tr.status = 'active'
    GROUP BY tr.id
    ORDER BY tr.start_date DESC
    LIMIT 1
");
 $reg_stmt->bind_param("i", $patient_id);
 $reg_stmt->execute();
 $reg_res = $reg_stmt->get_result();
if ($reg_res->num_rows === 1) {
    $regimen = $reg_res->fetch_assoc();
}

 $treatment_days = 0;
if ($regimen && $regimen['start_date']) {
    $treatment_days = (new DateTime($regimen['start_date']))->diff(new DateTime('today'))->days;
}