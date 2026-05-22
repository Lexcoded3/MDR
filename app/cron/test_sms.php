<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/config/db.php';
$config = require BASE_PATH . '/config/sms_config.php';

$phone = $argv[1] ?? $_GET['phone'] ?? '';
if (empty($phone)) {
    die("Usage: php test_sms.php <phone_number>\n");
}

// ✅ Normalize phone to E.164 format
if (!str_starts_with($phone, '+')) {
    $phone = '+' . $phone;
}

echo "--- GxAlert Africa's Talking Test ---\n";
echo "Target: $phone\n";

function sendAT_Direct($phone, $message, $config) {
    $username = $config['at_username'];          // 'sandbox'
    $apiKey   = $config['at_api_key'];           // 'atsk_e876918e...'
    $senderId = $config['at_sender_id'] ?? null; // 'GxAlert'
    $isSandbox = ($username === 'sandbox') || ($config['at_sandbox'] ?? false);
    $env      = $isSandbox ? 'sandbox.' : '';
    $url      = "https://api.{$env}africastalking.com/version1/messaging";

    // ✅ Debug output — remove after fix confirmed
    echo "\n--- DEBUG ---\n";
    echo "  Endpoint: $url\n";
    echo "  Username: $username\n";
    echo "  API Key:  " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -6) . "\n";
    echo "  Key Len:  " . strlen($apiKey) . " chars\n";
    echo "  Sender:   " . ($isSandbox ? '(none — sandbox)' : $senderId) . "\n";
    echo "-------------\n\n";

    $data = [
        'username' => $username,
        'to'       => $phone,
        'message'  => $message,
    ];

    // ✅ Only add sender ID on production — sandbox ignores/rejects it
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
            "apiKey: $apiKey",          // ✅ Correct header name
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // Keep false for XAMPP for now
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $res     = curl_exec($ch);
    $info    = curl_getinfo($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    echo "  HTTP Code: " . $info['http_code'] . "\n\n";

    if ($res === false || empty($res)) {
        return [
            'success'    => false,
            'status'     => 'connection_failed',
            'message_id' => 'N/A',
            'raw'        => "CURL_ERROR: $curlErr",
            'error'      => $curlErr ?: "HTTP {$info['http_code']}"
        ];
    }

    $result          = json_decode($res, true);
    $recipient       = $result['SMSMessageData']['Recipients'][0] ?? null;
    $recipientStatus = $recipient['status'] ?? 'failed';

    return [
        'success'    => in_array($recipientStatus, ['Success', 'Sent']),
        'status'     => $recipientStatus,
        'message_id' => $recipient['messageId'] ?? 'N/A',
        'raw'        => $res,
        'error'      => in_array($recipientStatus, ['Success', 'Sent'])
                            ? 'None'
                            : ($result['errorMessage'] ?? $recipientStatus)
    ];
}

$message = "GxAlert Test: Integration successful! Timestamp: " . date('H:i:s');
$result  = sendAT_Direct($phone, $message, $config);

echo "Result:\n";
echo "  Success: " . ($result['success'] ? 'YES ✔' : 'NO ✘') . "\n";
echo "  Status:  " . $result['status'] . "\n";
echo "  Msg ID:  " . $result['message_id'] . "\n";
echo "  Error:   " . $result['error'] . "\n";
echo "\n--- RAW RESPONSE FROM AT ---\n";
echo $result['raw'] . "\n";

// Log to DB
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $ins = $conn->prepare("INSERT INTO sms_logs (patient_id, message, status, api_response, phone_number, sent_at) VALUES (NULL, ?, ?, ?, ?, NOW())");
    $ins->bind_param("ssss", $message, $result['status'], $result['raw'], $phone);
    $ins->execute();
    echo "\n✔ Logged to database (ID: {$conn->insert_id})\n";
} catch (Exception $e) {
    echo "\n✘ DB log failed: " . $e->getMessage() . "\n";
}