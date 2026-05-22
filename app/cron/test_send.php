<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);  // ← add this
error_reporting(0);             // ← and this
/**
 * test_send.php — Send a one-off SMS to any number, no schedule/reminder needed
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';
$config = require BASE_PATH . '/config/sms_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$phone   = trim($_POST['phone']   ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

// Normalize to E.164
if (!str_starts_with($phone, '+')) {
    $phone = '+' . $phone;
}

if (!preg_match('/^\+\d{7,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use e.g. 256701234567']);
    exit;
}

// ── Send via Africa's Talking ─────────────────────────────────────────────
$username  = $config['at_username'];
$apiKey    = $config['at_api_key'];
$isSandbox = ($username === 'sandbox') || ($config['at_sandbox'] ?? false);
$env       = $isSandbox ? 'sandbox.' : '';
$url       = "https://api.{$env}africastalking.com/version1/messaging";

$postData = [
    'username' => $username,
    'to'       => $phone,
    'message'  => $message,
];
if (!$isSandbox && !empty($config['at_sender_id'])) {
    $postData['from'] = $config['at_sender_id'];
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($postData),
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
    echo json_encode(['success' => false, 'message' => 'cURL error: ' . ($curlErr ?: "HTTP {$info['http_code']}")]);
    exit;
}

$result          = json_decode($res, true);
$recipient       = $result['SMSMessageData']['Recipients'][0] ?? null;
$recipientStatus = $recipient['status'] ?? 'failed';
$success         = in_array($recipientStatus, ['Success', 'Sent']);
$messageId       = $recipient['messageId'] ?? null;
$cost            = $recipient['cost'] ?? null;

// Extract numeric cost value e.g. "UGX 35.0000" → 35.0000
$costDecimal = null;
if ($cost && preg_match('/[\d.]+/', $cost, $m)) {
    $costDecimal = (float)$m[0];
}

// ── Log to sms_logs ───────────────────────────────────────────────────────
$status = $success ? 'sent' : 'failed';

$ins = $conn->prepare("
    INSERT INTO sms_logs 
        (patient_id, reminder_id, phone_number, message, status, message_id, cost, api_response, sent_at)
    VALUES 
        (NULL, NULL, ?, ?, ?, ?, ?, ?, NOW())
");
$ins->bind_param("ssssds", $phone, $message, $status, $messageId, $costDecimal, $res);
$ins->execute();
$logId = $conn->insert_id;
// fix bind — cost is decimal, rest strings
$ins = $conn->prepare("
    INSERT INTO sms_logs (patient_id, reminder_id, phone_number, message, status, message_id, cost, api_response, sent_at)
    VALUES (NULL, NULL, ?, ?, ?, ?, ?, ?, NOW())
");
$ins->bind_param("ssssds", $phone, $message, $status, $messageId, $costDecimal, $res);
$ins->execute();
$logId = $conn->insert_id;

echo json_encode([
    'success'    => $success,
    'message'    => $success
        ? "SMS sent to $phone successfully!"
        : "SMS failed: " . ($result['SMSMessageData']['Message'] ?? $recipientStatus),
    'message_id' => $messageId,
    'cost'       => $cost,
    'log_id'     => $logId,
    'status'     => $recipientStatus,
]);