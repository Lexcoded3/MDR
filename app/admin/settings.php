<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

$pageTitle = 'Settings - GxAlert';
$notify_text = $_GET['status'] ?? '';
$form_errors = [];
$stats = getSystemStats($conn);

$sms_config_path = BASE_PATH . '/config/sms_config.php';
$sms_config = file_exists($sms_config_path) ? require $sms_config_path : [
    'provider'        => 'africas_talking',
    'at_username'     => '',
    'at_api_key'      => '',
    'at_sender_id'    => 'GxAlert',
    'at_sandbox'      => false,
    'advance_minutes' => 15,
    'quiet_start'     => '21:00',
    'quiet_end'       => '06:00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    
    if ($section === 'sms') {
        $provider   = $_POST['provider'] ?? 'africas_talking';
        $at_user    = trim($_POST['at_username'] ?? '');
        $at_key     = trim($_POST['at_api_key'] ?? '');
        $at_sender  = trim($_POST['at_sender_id'] ?? 'GxAlert');
        $at_sandbox = isset($_POST['at_sandbox']) ? 1 : 0;
        $advance    = (int)($_POST['advance_minutes'] ?? 15);
        $quiet_s    = $_POST['quiet_start'] ?? '21:00';
        $quiet_e    = $_POST['quiet_end'] ?? '06:00';
        
        // Build config file content
        $config_content = "<?php\nreturn [\n";
        $config_content .= "    'provider' => '$provider',\n";
        $config_content .= "    'at_username' => '$at_user',\n";
        $config_content .= "    'at_api_key' => '$at_key',\n";
        $config_content .= "    'at_sender_id' => '$at_sender',\n";
        $config_content .= "    'at_sandbox' => " . ($at_sandbox ? 'true' : 'false') . ",\n";
        $config_content .= "    'advance_minutes' => $advance,\n";
        $config_content .= "    'retry_max' => 3,\n";
        $config_content .= "    'retry_delay_min' => 5,\n";
        $config_content .= "    'quiet_start' => '$quiet_s',\n";
        $config_content .= "    'quiet_end' => '$quiet_e',\n";
        $config_content .= "    'templates' => [\n";
        $config_content .= "        'reminder' => 'GxAlert Reminder: It\\'s time to take your {drug_name} ({dose_mg}mg). Please take your dose at {dose_time}. Reply HELP for support.',\n";
        $config_content .= "        'missed' => 'GxAlert: Your {drug_name} dose for {dose_date} was not recorded as taken. Please contact your facility.',\n";
        $config_content .= "    ],\n";
        $config_content .= "];\n";
        
        file_put_contents(BASE_PATH . '/config/sms_config.php', $config_content);
header("Location: settings.php?status=SMS+settings+saved");
exit;
    }
}

// System info
 $db_size = $conn->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
    FROM information_schema.tables WHERE table_schema = DATABASE()
")->fetch_column();

 $table_count = $conn->query("
    SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
")->fetch_column();
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">System Settings</h1>
  </div>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- SMS Configuration -->
    <div class="lg:col-span-2">
      <form method="POST" class="card p-5">
        <input type="hidden" name="section" value="sms">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100">SMS Configuration</h2>
          <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Save</button>
        </div>

        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Provider</label>
            <select name="provider" class="form-select w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
              <option value="africas_talking" <?= $sms_config['provider'] === 'africas_talking' ? 'selected' : '' ?>>Africa's Talking</option>
              <option value="twilio" <?= $sms_config['provider'] === 'twilio' ? 'selected' : '' ?>>Twilio</option>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Username</label>
              <input type="text" name="at_username" value="<?= htmlspecialchars($sms_config['at_username'] ?? '') ?>" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">API Key</label>
              <input type="password" name="at_api_key" value="<?= htmlspecialchars($sms_config['at_api_key'] ?? '') ?>" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Sender ID</label>
              <input type="text" name="at_sender_id" value="<?= htmlspecialchars($sms_config['at_sender_id'] ?? 'GxAlert') ?>" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Advance (min)</label>
              <input type="number" name="advance_minutes" value="<?= $sms_config['advance_minutes'] ?? 15 ?>" min="5" max="120" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
            <div class="flex items-end pb-1">
              <label class="inline-flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="at_sandbox" value="1" <?= ($sms_config['at_sandbox'] ?? false) ? 'checked' : '' ?> class="form-checkbox size-5 rounded border-slate-400 bg-slate-100 checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:checked:border-accent">
                <span class="text-sm text-slate-600 dark:text-navy-200">Sandbox Mode</span>
              </label>
            </div>
          </div>

          <div class="my-3 h-px bg-slate-200 dark:bg-navy-500"></div>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100">Quiet Hours (no SMS sent)</p>
          <div class="grid grid-cols-2 gap-4 max-w-xs">
            <div>
              <label class="block text-xs text-slate-400 mb-1">Start</label>
              <input type="time" name="quiet_start" value="<?= $sms_config['quiet_start'] ?? '21:00' ?>" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
            <div>
              <label class="block text-xs text-slate-400 mb-1">End</label>
              <input type="time" name="quiet_end" value="<?= $sms_config['quiet_end'] ?? '06:00' ?>" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- System Info -->
    <div class="space-y-4">
      <div class="card p-5">
        <h3 class="font-medium text-slate-700 dark:text-navy-100 mb-4">System Information</h3>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">PHP Version</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= PHP_VERSION ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">MySQL</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $conn->get_server_info() ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">DB Size</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $db_size ?> MB</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">Tables</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $table_count ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">Timezone</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= date_default_timezone_get() ?></span>
          </div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="font-medium text-slate-700 dark:text-navy-100 mb-4">Quick Stats</h3>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">Users</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $stats['total_users'] ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">Patients</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $stats['total_patients'] ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">Drugs</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= $stats['total_drugs'] ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-navy-300">SMS Sent (total)</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= (int)$conn->query("SELECT COUNT(*) FROM sms_logs WHERE status IN ('sent','delivered')")->fetch_column() ?></span>
          </div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="font-medium text-slate-700 dark:text-navy-100 mb-3">Danger Zone</h3>
        <p class="text-xs text-slate-400 mb-3">These actions are irreversible.</p>
        <div class="space-y-2">
          <button disabled class="btn w-full h-8 border border-error/30 text-error opacity-50 cursor-not-allowed text-xs">Clear Audit Logs</button>
          <button disabled class="btn w-full h-8 border border-error/30 text-error opacity-50 cursor-not-allowed text-xs">Reset System</button>
        </div>
      </div>
    </div>
  </div>
</main>

<?php $notify_variant = 'success'; require_once 'admin_footer.php'; ?>