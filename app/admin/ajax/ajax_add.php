<?php
session_start();
$required_role = 'admin';
require_once '../../config/auth_check.php';
require_once '../../config/db.php';
require_once '../admin_init.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit;
}

$action = $_POST['action'] ?? '';

// ── ADD FACILITY ──────────────────────────────────────────────────────────
if ($action === 'add_facility') {
    $name         = trim($_POST['name'] ?? '');
    $code         = trim($_POST['facility_code'] ?? '') ?: null;
    $type         = $_POST['facility_type'] ?? null;
    $contact      = trim($_POST['contact_person'] ?? '') ?: null;
    $phone        = trim($_POST['phone'] ?? '') ?: null;
    $email        = trim($_POST['email'] ?? '') ?: null;
    $contact_phone= trim($_POST['contact_phone'] ?? '') ?: null;
    $address      = trim($_POST['address'] ?? '') ?: null;

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Facility name is required']); exit;
    }

    // Check duplicate code
    if ($code) {
        $ck = $conn->prepare("SELECT id FROM facilities WHERE facility_code = ?");
        $ck->bind_param("s", $code);
        $ck->execute();
        if ($ck->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => "Facility code '$code' already exists"]); exit;
        }
    }

    $ins = $conn->prepare("
        INSERT INTO facilities (name, facility_code, facility_type, contact_person, phone, email, contact_phone, address, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $ins->bind_param("ssssssss", $name, $code, $type, $contact, $phone, $email, $contact_phone, $address);
    $ins->execute();

    echo json_encode([
        'success' => true,
        'message' => "Facility \"$name\" added successfully!",
        'id'      => $conn->insert_id
    ]);
    exit;
}

// ── ADD DRUG ──────────────────────────────────────────────────────────────
if ($action === 'add_drug') {
    $name  = trim($_POST['drug_name'] ?? '');
    $code  = trim($_POST['drug_code'] ?? '');
    $group = $_POST['drug_group'] ?? 'other';
    $dose  = (int)($_POST['default_dose_mg'] ?? 0) ?: null;
    $unit  = trim($_POST['unit'] ?? 'mg');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    if (empty($name) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Drug name and code are required']); exit;
    }

    // Check duplicate code
    $ck = $conn->prepare("SELECT id FROM drugs WHERE drug_code = ?");
    $ck->bind_param("s", $code);
    $ck->execute();
    if ($ck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Drug code '$code' already exists"]); exit;
    }

    $ins = $conn->prepare("
        INSERT INTO drugs (drug_name, drug_code, drug_group, default_dose_mg, unit, notes, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $ins->bind_param("sssiss", $name, $code, $group, $dose, $unit, $notes);
    $ins->execute();

    echo json_encode([
        'success' => true,
        'message' => "Drug \"$name\" ($code) added successfully!",
        'id'      => $conn->insert_id
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);