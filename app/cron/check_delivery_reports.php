<?php
/**
 * Check SMS Delivery Reports
 * 
 * Run every 15 minutes:
 * *
 15 * * * * /usr/bin/php /path/to/TB/cron/check_delivery_reports.php >> /path/to/TB/logs/cron.log 2>&1
 */

if (php_sapi_name() !== 'cli' && !defined('RUNNING_VIA_CRON')) {
    http_response_code(403);
    exit('CLI only');
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';
 $sms_config = require BASE_PATH . '/config/sms_config.php';

 $log_file = BASE_PATH . '/logs/cron.log';

function log_msg(string $message, bool $is_error = false): void {
    global $log_file;
    $line = "[" . date('Y-m-d H:i:s') . "] [" . ($is_error ? 'ERROR' : 'INFO') . "] $message" . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
}

log_msg('=== Delivery report check started ===');

 $updated = 0;

try {

    if ($sms_config['provider'] !== 'africas_talking') {
        log_msg('Delivery reports only supported for Africa\'s Talking provider');
        exit(0);
    }

    // Find SMS logs sent in last 24 hours without a delivery report
    $stmt = $conn->prepare("
        SELECT sl.id, sl.reminder_id, sl.message_id, sl.patient_id
        FROM sms_logs sl
        WHERE sl.status IN ('sent', 'delivered')
          AND sl.message_id IS NOT NULL
          AND sl.delivery_report IS NULL
          AND sl.sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 100
    ");
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($pending)) {
        log_msg('No pending delivery reports to check');
        exit(0);
    }

    log_msg("Checking " . count($pending) . " message delivery statuses");

    // Africa's Talking delivery report endpoint
    $url = $sms_config['at_sandbox']
        ? 'https://api.sandbox.africastalking.com/v1/messaging'
        : 'https://api.africastalking.com/v1/messaging';

    $update_sms = $conn->prepare("
        UPDATE sms_logs SET delivery_report = ? WHERE id = ?
    ");

    // Check each message
    foreach ($pending as $sms) {
        $ch = curl_init("$url/{$sms['message_id']}");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'apikey: ' . $sms_config['at_api_key'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            $status = $result['status'] ?? $result['SMSMessageData']['Recipients'][0]['status'] ?? 'unknown';
            
            $update_sms->bind_param("si", $status, $sms['id']);
            $update_sms->execute();
            $updated++;

            if ($status === 'Failed') {
                log_msg("Delivery FAILED for message ID {$sms['message_id']}");
            }
        }
    }

} catch (Exception $e) {
    log_msg("FATAL: " . $e->getMessage(), true);
}

log_msg("=== Delivery report check finished: $updated updated ===" . PHP_EOL);
exit(0);