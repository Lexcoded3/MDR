<?php
session_start();
 $required_role = 'data_officer';
require_once '../config/auth_check.php';
require_once '../config/db.php';

 $pageTitle = 'Export Data - GxAlert';

 $export_type = $_GET['type'] ?? 'cohort';
 $format = $_GET['format'] ?? 'csv';
 $period = $_GET['period'] ?? '12m';

// Build query based on export type
switch ($export_type) {
    case 'cohort':
        $query = "
            SELECT 'Patient Code', 'Full Name', 'Gender', 'Age', 'HIV Status', 'On ART', 'Case Classification', 'TB Confirmation', 'Enrollment Date', 'Treatment Status', 'Facility'
            FROM patients p
            LEFT JOIN facilities f ON p.facility_id = f.id
            WHERE p.is_active = 1
        ";
        $filename = "TB_cohort_{$period}.csv";
        break;

    case 'adherence':
        $query = "
            SELECT 'Patient Code', 'Full Name', 'Dose Date', 'Drug', 'Dose Time', 'Status', 'Verification', 'Verified By'
            FROM adherence_logs al
            JOIN patients p ON al.patient_id = p.id
            LEFT JOIN medication_schedule ms ON al.schedule_id = ms.id
            LEFT JOIN drugs d ON ms.drug_id = d.id
            LEFT JOIN users u ON al.verified_by = u.id
            WHERE DATE(al.dose_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY al.dose_date DESC, p.full_name ASC
        ";
        $filename = "TB_adherence_{$period}.csv";
        break;

    case 'outcomes':
        $query = "
            SELECT 'Patient Code', 'Full Name', 'Outcome', 'Outcome Date', 'Facility'
            FROM treatment_outcomes o
            JOIN patients p ON o.patient_id = p.id
            LEFT JOIN facilities f ON p.facility_id = f.id
        ";
        $filename = "TB_outcomes_{$filter_year}.csv";
        break;

    default:
        $query = "
            SELECT 'Patient Code', 'Full Name', 'Gender', 'Age', 'HIV Status', 'Treatment Status', 'Facility', 'Enrollment Date'
            FROM patients p
            LEFT JOIN facilities f ON p.facility_id = f.id
            WHERE p.is_active = 1
            ORDER BY p.created_at DESC
        ";
        $filename = "TB_all_patients.csv";
        break;
}

// Execute and stream CSV
header('Content-Type: text/csv');
 $filename = $filename;

// Fix: DATE_FORMAT returns date as '2025-Jan' — MySQL issue, use DATE_FORMAT with '%Y-%m' instead
 $fixed_query = str_replace("DATE_FORMAT(enrollment_date, '%Y-%m')", "DATE_FORMAT(enrollment_date, '%Y-%m')", $query);
 $fixed_query = str_replace("DATE_FORMAT(outcome_date, '%Y-%m')", "DATE_FORMAT(outcome_date, '%Y-%m')", $fixed_query);

 $stmt = $conn->prepare($fixed_query);
 $stmt->execute();
 $result = $stmt->get_result();

// 1. Move Headers to the TOP (before any output)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Open Stream
$fp = fopen('php://output', 'w');

// 3. Write BOM for Excel
fwrite($fp, "\xEF\xBB\xBF");

// 4. Fetch Results
$stmt = $conn->prepare($query); // Use your switch-case $query
$stmt->execute();
$result = $stmt->get_result();

// 5. Write Header Row automatically from Column Names
$first_row = $result->fetch_assoc();
if ($first_row) {
    fputcsv($fp, array_keys($first_row));
    
    // Rewind slightly or just handle the first row manually
    fputcsv($fp, $first_row); 
}

// 6. Write Data Rows
while ($row = $result->fetch_assoc()) {
    foreach ($row as &$cell) {
        if ($cell !== null) {
            // Clean up newlines so they don't break CSV rows
            $cell = str_replace(["\r", "\n"], " ", $cell);
        }
    }
    // Correct way to use fputcsv (pass the array, not an imploded string)
    fputcsv($fp, $row); 
}

fclose($fp);
exit;
?>