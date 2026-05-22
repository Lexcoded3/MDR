<?php
/**
 * test_fire.php — Manual reminder trigger for demo/testing
 * Inserts a reminder then sends SMS directly (same method as test_sms.php)
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';
$config = require BASE_PATH . '/config/sms_config.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$patient_id = (int)($_POST['patient_id'] ?? 0);
if ($patient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No patient selected']);
    exit;
}

// ── Proven sendAT_Direct lifted from test_sms.php ────────────────────────
function sendAT_Direct($phone, $message, $config) {
    $username  = $config['at_username'];
    $apiKey    = $config['at_api_key'];
    $senderId  = $config['at_sender_id'] ?? null;
    $isSandbox = ($username === 'sandbox') || ($config['at_sandbox'] ?? false);
    $env       = $isSandbox ? 'sandbox.' : '';
    $url       = "https://api.{$env}africastalking.com/version1/messaging";

    $data = [
        'username' => $username,
        'to'       => $phone,
        'message'  => $message,
    ];

    if (!$isSandbox && !empty($senderId)) {
        $data['from'] = $senderId;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => [
            "Accept: application/json",
            "Content-Type: application/x-www-form-urlencoded",
            "apiKey: $apiKey",
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $res     = curl_exec($ch);
    $info    = curl_getinfo($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($res === false || empty($res)) {
        return [
            'success'    => false,
            'status'     => 'failed',
            'message_id' => null,
            'raw'        => "CURL_ERROR: $curlErr",
            'error'      => $curlErr ?: "HTTP {$info['http_code']}"
        ];
    }

    $result          = json_decode($res, true);
    $recipient       = $result['SMSMessageData']['Recipients'][0] ?? null;
    $recipientStatus = $recipient['status'] ?? 'failed';
    $success         = in_array($recipientStatus, ['Success', 'Sent']);

    return [
        'success'    => $success,
        'status'     => $recipientStatus,
        'message_id' => $recipient['messageId'] ?? null,
        'cost'       => $recipient['cost']      ?? null,
        'raw'        => $res,
        'error'      => $success ? null : ($result['errorMessage'] ?? $recipientStatus)
    ];
}

try {
    // 1. Fetch patient + active schedule + drug name for a real message
    $check = $conn->prepare("
        SELECT 
            ms.id        AS schedule_id,
            ms.dose_time,
            tr.patient_id,
            p.full_name,
            p.phone,
            d.drug_name,
            d.default_dose_mg
        FROM medication_schedule ms
        JOIN treatment_regimens tr ON ms.regimen_id = tr.id
        JOIN patients p            ON tr.patient_id = p.id
        LEFT JOIN regimen_drugs rd ON rd.regimen_id = tr.id AND rd.is_active = 1
        LEFT JOIN drugs d          ON rd.drug_id = d.id
        WHERE tr.patient_id = ?
          AND ms.is_active  = 1
          AND tr.status     = 'active'
        LIMIT 1
    ");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'No active regimen or schedule found for this patient'
        ]);
        exit;
    }

    if (empty($row['phone'])) {
        echo json_encode([
            'success' => false,
            'message' => "Patient {$row['full_name']} has no phone number on record"
        ]);
        exit;
    }

    // 2. Normalize phone
    $phone = $row['phone'];
    if (!str_starts_with($phone, '+')) {
        $phone = '+' . $phone;
    }

    // 3. Build a realistic reminder message
    $drug    = $row['drug_name']       ?? 'your medication';
    $dose    = $row['default_dose_mg'] ?? '';
    $time    = $row['dose_time']       ? date('H:i', strtotime($row['dose_time'])) : date('H:i');
    $message = "GxAlert Reminder: It's time to take your $drug"
             . ($dose ? " ({$dose}mg)" : '')
             . ". Please take your dose at $time. Reply HELP for support.";

    // 4. Insert reminder record
    $ins = $conn->prepare("
        INSERT INTO reminders (patient_id, schedule_id, reminder_datetime, channel, status, retry_count)
        VALUES (?, ?, NOW(), 'sms', 'pending', 0)
    ");
    $ins->bind_param("ii", $row['patient_id'], $row['schedule_id']);
    $ins->execute();
    $reminder_id = $conn->insert_id;

    if (!$reminder_id) {
        echo json_encode(['success' => false, 'message' => 'Failed to insert reminder']);
        exit;
    }

    // 5. Send SMS directly — same proven method as test_sms.php
    $result = sendAT_Direct($phone, $message, $config);

    // 6. Extract cost
    $costDecimal = null;
    if ($result['cost'] && preg_match('/[\d.]+/', $result['cost'], $m)) {
        $costDecimal = (float)$m[0];
    }

    // 7. Log to sms_logs
    $smsStatus = $result['success'] ? 'sent' : 'failed';
    $log = $conn->prepare("
        INSERT INTO sms_logs 
            (reminder_id, patient_id, phone_number, message, status, message_id, cost, api_response, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $log->bind_param(
        "iissssds",
        $reminder_id,
        $row['patient_id'],
        $phone,
        $message,
        $smsStatus,
        $result['message_id'],
        $costDecimal,
        $result['raw']
    );
    $log->execute();
    $log_id = $conn->insert_id;

    // 8. Update reminder status
    $upd = $conn->prepare("
        UPDATE reminders SET status = ?, sent_at = NOW() WHERE id = ?
    ");
    $upd->bind_param("si", $smsStatus, $reminder_id);
    $upd->execute();

    if ($result['success']) {
        echo json_encode([
            'success'     => true,
            'reminder_id' => $reminder_id,
            'log_id'      => $log_id,
            'patient'     => $row['full_name'],
            'phone'       => $phone,
            'message'     => "✔ SMS sent to {$row['full_name']} ({$phone}) — Reminder #{$reminder_id} logged!",
            'cost'        => $result['cost'] ?? 'N/A',
            'message_id'  => $result['message_id'],
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Reminder #{$reminder_id} inserted but SMS failed: " . $result['error']
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}