<?php
/**
 * GxAlert Results Ready Notifier (Africa's Talking)
 * Triggers: when a new lab result is uploaded (is_final = 0)
 * Recipient: Patient via SMS
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI access only');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/db.php';
$config = require BASE_PATH . '/config/sms_config.php';

// ── Logging ──────────────────────────────────────────────────
$log_file = BASE_PATH . '/logs/results_notify.log';
if (!is_dir(dirname($log_file))) mkdir(dirname($log_file), 0755, true);

function log_msg($msg, $is_err = false) {
    global $log_file;
    $line = "[" . date('Y-m-d H:i:s') . "] [" . ($is_err ? 'ERROR' : 'INFO') . "] $msg" . PHP_EOL;
    echo $line;
    file_put_contents($log_file, $line, FILE_APPEND);
    if ($is_err) fwrite(STDERR, $line);
}

// ── Africa's Talking SMS ──────────────────────────────────────
function sendAT($phone, $message, $config) {
    $username = $config['at_username'];
    $apiKey   = $config['at_api_key'];
    $env      = ($username === 'sandbox') ? 'sandbox.' : '';
    $url      = "https://api.{$env}africastalking.com/version1/messaging";

    $data = [
        'username' => $username,
        'to'       => $phone,
        'message'  => $message,
        'from'     => $username === 'sandbox' ? null : $config['at_sender_id']
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => [
            "Accept: application/json",
            "Content-Type: application/x-www-form-urlencoded",
            "apikey: $apiKey"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result    = json_decode($res, true);
    $recipient = $result['SMSMessageData']['Recipients'][0] ?? null;
    $success   = ($code == 201 && $recipient && $recipient['status'] === 'Success');

    return [
        'success' => $success,
        'status'  => $recipient['status'] ?? 'failed',
        'error'   => $success ? null : ($recipient['status'] ?? "HTTP $code — $res"),
        'raw'     => $res
    ];
}

// ── MAIN ─────────────────────────────────────────────────────
log_msg("--- Results Notify Cron Start ---");

try {
    // Check quiet hours
    $now      = date('H:i');
    $is_quiet = ($config['quiet_start'] > $config['quiet_end'])
        ? ($now >= $config['quiet_start'] || $now < $config['quiet_end'])
        : ($now >= $config['quiet_start'] && $now < $config['quiet_end']);

    if ($is_quiet) {
        log_msg("Quiet hours active. Skipping.");
        exit(0);
    }

    // Fetch new unnotified preliminary results
    // We track sent notifications via sms_logs to avoid duplicates
    // Fetch new unnotified preliminary results
$fetch = $conn->prepare("
    SELECT 
        lr.id AS result_id,
        lr.test_type,
        lr.result,
        lr.result_date,
        p.id AS patient_id,
        p.full_name,
        p.phone,
        p.patient_code
    FROM lab_results lr
    JOIN patients p ON lr.patient_id = p.id
    WHERE lr.is_final = 0
      AND p.is_active = 1
      AND p.phone IS NOT NULL
      AND p.phone != ''
      AND NOT EXISTS (
          SELECT 1 FROM result_notifications rn
          WHERE rn.result_id = lr.id
      )
    ORDER BY lr.created_at ASC
    LIMIT 20
");
$fetch->execute();
$results = $fetch->get_result()->fetch_all(MYSQLI_ASSOC);
$fetch->close();

if (empty($results)) {
    log_msg("No new results to notify.");
}

foreach ($results as $row) {
    $test_label  = strtoupper($row['test_type'] ?? 'Lab');
    $result_date = date('d M Y', strtotime($row['result_date']));

    $msg = "Dear {$row['full_name']}, your {$test_label} result from {$result_date} "
         . "is ready and under review by your doctor at the facility. "
         . "Ref: {$row['patient_code']}. Do not reply to this message.";

    $res = sendAT($row['phone'], $msg, $config);

    // Log to result_notifications regardless of success/fail
    $log = $conn->prepare("
        INSERT INTO result_notifications 
            (result_id, patient_id, phone, message, status, api_response)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $status = $res['success'] ? 'sent' : 'failed';
    $log->bind_param("iissss",
        $row['result_id'],
        $row['patient_id'],
        $row['phone'],
        $msg,
        $status,
        $res['raw']
    );
    $log->execute();
    $log->close();

    if ($res['success']) {
        log_msg("Notified {$row['phone']} ({$row['patient_code']}) — {$test_label}");
    } else {
        log_msg("Failed {$row['phone']} ({$row['patient_code']}): " . $res['error'], true);
    }
}

} catch (Exception $e) {
    log_msg("Fatal Error: " . $e->getMessage(), true);
}

log_msg("--- Results Notify Cron End ---");