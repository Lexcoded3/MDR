<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

$pageTitle   = 'Edit User - GxAlert';
$form_errors = [];
$old         = [];
$notify_text = '';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header("Location: users.php"); exit; }

$u_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$u_stmt->bind_param("i", $id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
if ($u_res->num_rows !== 1) { header("Location: users.php"); exit; }
$user = $u_res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['name', 'email', 'role', 'location'];
    foreach ($fields as $f) $old[$f] = trim($_POST[$f] ?? '');
    $old['facility_id'] = (int)($_POST['facility_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($old['name']))  $form_errors['name']  = 'Name is required';
    if (empty($old['email'])) $form_errors['email'] = 'Email is required';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $form_errors['email'] = 'Invalid email';
    else {
        $ck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $ck->bind_param("si", $old['email'], $id);
        $ck->execute();
        if ($ck->get_result()->num_rows > 0) $form_errors['email'] = 'Email already in use';
    }
    if (!isset($role_labels[$old['role']])) $form_errors['role'] = 'Invalid role';
    if (!empty($password) && strlen($password) < 8)      $form_errors['password'] = 'Minimum 8 characters';
    if (!empty($password) && $password !== $confirm)      $form_errors['confirm_password'] = 'Passwords do not match';

    if (empty($form_errors)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET name=?, email=?, role=?, location=?, facility_id=?, password=? WHERE id=?");
            $upd->bind_param("ssssiis", $old['name'], $old['email'], $old['role'], $old['location'], $old['facility_id'], $hashed, $id);
        } else {
            $upd = $conn->prepare("UPDATE users SET name=?, email=?, role=?, location=?, facility_id=? WHERE id=?");
            $upd->bind_param("ssssii", $old['name'], $old['email'], $old['role'], $old['location'], $old['facility_id'], $id);
        }
        $upd->execute();
        $upd->close();

        $audit = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (?, 'update', 'user', ?, ?, NOW())");
        $audit->bind_param("iis", $admin_id, $id, json_encode($old));
        $audit->execute();
        $audit->close();

        $notify_text = 'updated';

        $u_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $u_stmt->bind_param("i", $id);
        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc();
        $old  = $user;
    }
} else {
    $old = $user;
}

$facilities = $conn->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$role_icons = [
    'admin'         => 'fa-shield-halved',
    'doctor'        => 'fa-user-doctor',
    'nurse'         => 'fa-hand-holding-medical',
    'clinician'     => 'fa-stethoscope',
    'lab_personnel' => 'fa-flask',
    'data_officer'  => 'fa-database',
    'patient'       => 'fa-user',
];

$role_grad = [
    'admin'         => 'from-violet-500 to-purple-600',
    'doctor'        => 'from-blue-500 to-cyan-500',
    'nurse'         => 'from-pink-500 to-rose-500',
    'clinician'     => 'from-emerald-500 to-teal-600',
    'lab_personnel' => 'from-amber-500 to-orange-500',
    'data_officer'  => 'from-slate-500 to-slate-700',
    'patient'       => 'from-green-500 to-emerald-600',
];

$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $user['name']), 0, 2))));
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <!-- Top bar -->
  <div class="mt-5 flex items-center gap-3 animate-in">
    <a href="users.php"
       class="btn size-9 rounded-full p-0 border border-slate-200 hover:bg-slate-100 dark:border-navy-500 dark:hover:bg-navy-600 shrink-0">
      <i class="fa fa-arrow-left text-sm"></i>
    </a>
    <div class="min-w-0">
      <h1 class="text-lg font-bold text-slate-800 dark:text-navy-50 leading-tight">Edit User Account</h1>
      <p class="text-xs text-slate-400 mt-0.5">Update profile, role and credentials</p>
    </div>
    <?php if ($notify_text): ?>
    <span class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
      <i class="fa fa-circle-check"></i> Saved
    </span>
    <?php endif; ?>
  </div>


  <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- System Info -->
    <div class="space-y-4">
      <div class="card p-5">
        <h3 class="font-medium text-slate-700 dark:text-navy-100 mb-4">User Information</h3>
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

    
  </div>
<?php $notify_variant = 'success'; require_once 'admin_footer.php'; ?>