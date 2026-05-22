<?php
session_start();
$required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(null); exit; }

$stmt = $conn->prepare("
    SELECT 
        p.id, p.full_name, p.patient_code, p.national_id,
        p.gender, p.date_of_birth, p.phone, p.address,
        p.enrollment_date, p.date_of_diagnosis,
        p.tb_case_classification, p.mdr_confirmation,
        p.hiv_status, p.on_art, p.weight_kg,
        p.treatment_status, p.next_of_kin, p.next_of_kin_contact,
        f.name AS facility_name
    FROM patients p
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode($patient ?: null);