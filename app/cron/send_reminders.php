<?php
/**
 * GxAlert Reminder Cron Job (Africa's Talking)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI access only');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/vendor/autoload.php'; // Required for Africa's Talking SDK
require_once BASE_PATH . '/config/db.php';
$config = require BASE_PATH . '/config/sms_config.php';

use AfricasTalking\SDK\AfricasTalking;

// Setup Logging
$log_file = BASE_PATH . '/logs/cron.log';
if (!is_dir(dirname($log_file))) mkdir(dirname($log_file), 0755, true);

function log_msg($msg, $is_err = false) {
    global $log_file;
    // Create the formatted line first
    $line = "[" . date('Y-m-d H:i:s') . "] [" . ($is_err ? 'ERROR' : 'INFO') . "] $msg" . PHP_EOL;
    
    // Print to terminal so you can see it live
    echo $line; 
    
    // Write to the actual log file
    file_put_contents($log_file, $line, FILE_APPEND);
    
    if ($is_err) {
        fwrite(STDERR, $line);
    }
}
/**
 * Africa's Talking API Caller
 */
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
        CURLOPT_SSL_VERIFYPEER => false, // Keep this false for XAMPP/VPN compatibility
        CURLOPT_TIMEOUT        => 30,
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($res, true);
    $recipient = $result['SMSMessageData']['Recipients'][0] ?? null;
    $success = ($code == 201 && $recipient && ($recipient['status'] === 'Success'));

    return [
        'success' => $success,
        'status'  => $recipient['status'] ?? 'failed',
        'error'   => $success ? null : ($recipient['status'] ?? "HTTP $code — $res"),
        'raw'     => $res
    ];
}
// ============================================================
// MAIN EXECUTION
// ============================================================
log_msg("--- Cron Start ---");

try {
    // 1. GENERATE NEW REMINDERS
    $window_start = date('Y-m-d H:i:s');
    $window_end   = date('Y-m-d H:i:s', strtotime("+{$config['advance_minutes']} minutes"));

    $gen_sql = "INSERT INTO reminders (patient_id, schedule_id, reminder_datetime, channel, status)
            SELECT p.id, ms.id, NOW(), 'sms', 'pending'
            FROM medication_schedule ms
            JOIN treatment_regimens tr ON ms.regimen_id = tr.id
            JOIN patients p ON tr.patient_id = p.id
            WHERE ms.is_active = 1 
            AND tr.status = 'active' 
            AND p.is_active = 1
            AND CONCAT(CURDATE(), ' ', ms.dose_time) BETWEEN ? AND ?
            AND NOT EXISTS (SELECT 1 FROM reminders r WHERE r.schedule_id = ms.id AND DATE(r.reminder_datetime) = CURDATE())
            AND NOT EXISTS (SELECT 1 FROM adherence_logs al WHERE al.schedule_id = ms.id AND al.dose_date = CURDATE())";

$gen_stmt = $conn->prepare($gen_sql);
$gen_stmt->bind_param("ss", $window_start, $window_end);
$gen_stmt->execute();
$generated = $gen_stmt->affected_rows;
$gen_stmt->close();
log_msg("Generated $generated new reminder(s) for window $window_start → $window_end");
    // 2. CHECK QUIET HOURS
    $now = date('H:i');
    $is_quiet = ($config['quiet_start'] > $config['quiet_end']) 
        ? ($now >= $config['quiet_start'] || $now < $config['quiet_end'])
        : ($now >= $config['quiet_start'] && $now < $config['quiet_end']);

    if ($is_quiet) {
        log_msg("Quiet hours active. Skipping transmission.");
    } else {
        // 3. SEND PENDING
        $pending_sql = "SELECT r.id as r_id, p.phone, p.full_name, d.drug_name, rd.dose_mg, ms.dose_time, p.id as p_id
                        FROM reminders r
                        JOIN medication_schedule ms ON r.schedule_id = ms.id
                        JOIN patients p ON r.patient_id = p.id
                        JOIN drugs d ON ms.drug_id = d.id
                        JOIN regimen_drugs rd ON ms.regimen_id = rd.regimen_id AND rd.drug_id = ms.drug_id
                        WHERE r.status = 'pending' AND r.retry_count < ? LIMIT 20";
        
        $p_stmt = $conn->prepare($pending_sql);
        $p_stmt->bind_param("i", $config['retry_max']);
        $p_stmt->execute();
        $queue = $p_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($queue as $item) {
            $msg = str_replace(
                ['{drug_name}', '{dose_mg}', '{dose_time}', '{patient_name}'],
                [$item['drug_name'], $item['dose_mg'], date('h:i A', strtotime($item['dose_time'])), $item['full_name']],
                $config['templates']['reminder']
            );
            
            // Switch to the Africa's Talking function
            $res = sendAT($item['phone'], $msg, $config);

            if ($res['success']) {
                $upd = $conn->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $upd->bind_param("i", $item['r_id']);
                $upd->execute();
                
                $log = $conn->prepare("INSERT INTO sms_logs (patient_id, message, status, api_response, phone_number, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $log->bind_param("issss", $item['p_id'], $msg, $res['status'], $res['raw'], $item['phone']);
                $log->execute();
                log_msg("Sent via AT to " . $item['phone']);
            } else {
                $fail = $conn->prepare("UPDATE reminders SET retry_count = retry_count + 1, error_message = ? WHERE id = ?");
                $fail->bind_param("si", $res['error'], $item['r_id']);
                $fail->execute();
                log_msg("Failed via AT to " . $item['phone'] . ": " . $res['error'], true);
            }
        }
    }

    // 4. AUTO-MARK MISSED
    $missed_sql = "INSERT IGNORE INTO adherence_logs (patient_id, schedule_id, dose_date, status, verification_method)
                   SELECT p.id, ms.id, CURDATE(), 'missed', 'auto'
                   FROM medication_schedule ms
                   JOIN treatment_regimens tr ON ms.regimen_id = tr.id
                   JOIN patients p ON tr.patient_id = p.id
                   WHERE ms.is_active = 1 AND ADDTIME(CURDATE(), ms.dose_time) < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                   AND NOT EXISTS (SELECT 1 FROM adherence_logs al WHERE al.schedule_id = ms.id AND al.dose_date = CURDATE())";
    $conn->query($missed_sql);

} catch (Exception $e) {
    log_msg("Fatal Error: " . $e->getMessage(), true);
}

log_msg("--- Cron End ---");