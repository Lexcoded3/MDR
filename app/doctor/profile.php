<?php
session_start();
 $required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

 $pageTitle = 'Profile - GxAlert';
 $notify_text = '';
 $form_errors = [];
 $form_old = $patient;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $nok       = trim($_POST['next_of_kin'] ?? '');
    $nok_phone = trim($_POST['next_of_kin_contact'] ?? '');

    if (!empty($phone) && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        $form_errors['phone'] = 'Invalid phone format';
    }
    if (!empty($nok_phone) && !preg_match('/^[0-9+\-\s]{8,20}$/', $nok_phone)) {
        $form_errors['next_of_kin_contact'] = 'Invalid phone format';
    }

    if (empty($form_errors)) {
        $upd = $conn->prepare("UPDATE patients SET phone = ?, address = ?, next_of_kin = ?, next_of_kin_contact = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param("ssssi", $phone, $address, $nok, $nok_phone, $patient_id);
        $upd->execute();

        // Refresh patient data
        $stmt = $conn->prepare("SELECT p.*, f.name AS facility_name FROM patients p LEFT JOIN facilities f ON p.facility_id = f.id WHERE p.user_id = ? AND p.is_active = 1");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $form_old = $patient;

        $notify_text = 'profile_updated';
    } else {
        $form_old['phone'] = $phone;
        $form_old['address'] = $address;
        $form_old['next_of_kin'] = $nok;
        $form_old['next_of_kin_contact'] = $nok_phone;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Verify current
    $pw_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $pw_stmt->bind_param("i", $_SESSION['id']);
    $pw_stmt->execute();
    $hash = $pw_stmt->get_result()->fetch_column();

    if (!password_verify($current, $hash)) {
        $form_errors['current_password'] = 'Current password is incorrect';
    } elseif (strlen($new) < 8) {
        $form_errors['new_password'] = 'Password must be at least 8 characters';
    } elseif ($new !== $confirm) {
        $form_errors['confirm_password'] = 'Passwords do not match';
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $pw_upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $pw_upd->bind_param("si", $new_hash, $_SESSION['id']);
        $pw_upd->execute();

        $notify_text = 'password_changed';
    }
}
?>
<?php require_once 'patient_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-5">My Profile</h1>
<div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
  
  <?php if (!empty($form_errors)): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-5 mt-5">
    <ul class="list-disc list-inside text-xs text-error">
      <?php foreach ($form_errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
<div class="col-span-12 grid lg:col-span-5">
  <!-- Read-only Info -->
  <div class="card mb-5 mt-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
      <h2 class="font-medium text-slate-700 dark:text-navy-100">Personal Information</h2>
      <p class="text-xs text-slate-400 dark:text-navy-300">These details are set by your clinician. Contact your facility to update them.</p>
    </div>
    <div class="grid grid-cols-1 gap-y-4 p-5 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-4 text-sm">
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Full Name</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['full_name']) ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Patient Code</p>
        <p class="font-mono font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['patient_code'] ?? 'N/A') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Date of Birth</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= $patient['date_of_birth'] ?> (<?= $age ?> yrs)</p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Gender</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= ucfirst($patient['gender']) ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">HIV Status</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= ucfirst($patient['hiv_status'] ?? 'Unknown') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Facility</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['facility_name'] ?? 'N/A') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Enrollment Date</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= $patient['enrollment_date'] ?? 'N/A' ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400 dark:text-navy-300">Treatment Status</p>
        <p class="font-medium text-slate-700 dark:text-navy-100"><?= $status_labels[$patient['treatment_status']] ?? 'Unknown' ?></p>
      </div>
    </div>
  </div>
</div>
<div class="col-span-12 grid lg:col-span-7">
  <!-- Editable Contact Info -->
  <form method="POST" action="">
    <input type="hidden" name="update_profile" value="1">
    <div class="card mb-5 mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Contact Information</h2>
        <p class="text-xs text-slate-400 dark:text-navy-300">You can update these details yourself.</p>
      </div>
      <div class="grid grid-cols-1 gap-y-4 p-5 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-1">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Phone Number</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($form_old['phone'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['phone']) ? 'ring-2 ring-error' : '' ?>"
                 placeholder="07XXXXXXXX">
          <?php if (isset($form_errors['phone'])): ?><p class="mt-0.5 text-[10px] text-error"><?= $form_errors['phone'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($form_old['address'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Emergency Contact Name</label>
          <input type="text" name="next_of_kin" value="<?= htmlspecialchars($form_old['next_of_kin'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Emergency Contact Phone</label>
          <input type="tel" name="next_of_kin_contact" value="<?= htmlspecialchars($form_old['next_of_kin_contact'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['next_of_kin_contact']) ? 'ring-2 ring-error' : '' ?>"
                 placeholder="07XXXXXXXX">
          <?php if (isset($form_errors['next_of_kin_contact'])): ?><p class="mt-0.5 text-[10px] text-error"><?= $form_errors['next_of_kin_contact'] ?></p><?php endif; ?>
        </div>
      </div>
      <div class="border-t border-slate-150 dark:border-navy-600 px-5 py-3">
        <button type="submit" class="btn h-9 bg-primary px-5 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
          Save Changes
        </button>
      </div>
    </div>
  </form>
  <!-- Change Password -->
  <form method="POST" action="">
    <input type="hidden" name="change_password" value="1">
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Change Password</h2>
        <p class="text-xs text-slate-400 dark:text-navy-300">Update your login password.</p>
      </div>
      <div class="grid grid-cols-1 gap-y-4 p-5 sm:grid-cols-3 sm:gap-x-8 sm:gap-y-4">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Current Password <span class="text-error">*</span></label>
          <input type="password" name="current_password" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['current_password']) ? 'ring-2 ring-error' : '' ?>">
          <?php if (isset($form_errors['current_password'])): ?><p class="mt-0.5 text-[10px] text-error"><?= $form_errors['current_password'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">New Password <span class="text-error">*</span></label>
          <input type="password" name="new_password" required minlength="8"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['new_password']) ? 'ring-2 ring-error' : '' ?>"
                 placeholder="Min 8 characters">
          <?php if (isset($form_errors['new_password'])): ?><p class="mt-0.5 text-[10px] text-error"><?= $form_errors['new_password'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Confirm New Password <span class="text-error">*</span></label>
          <input type="password" name="confirm_password" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['confirm_password']) ? 'ring-2 ring-error' : '' ?>">
          <?php if (isset($form_errors['confirm_password'])): ?><p class="mt-0.5 text-[10px] text-error"><?= $form_errors['confirm_password'] ?></p><?php endif; ?>
        </div>
      </div>
      <div class="border-t border-slate-150 dark:border-navy-600 px-5 py-3">
        <button type="submit" class="btn h-9 bg-warning px-5 text-sm font-medium text-white hover:bg-warning/90">
          Change Password
        </button>
      </div>
    </div>
  </form>
</div>
</div>
</div>

<?php 
 $notify_text = $notify_text ?: '';
 $notify_variant = 'success';
require_once 'patient_footer.php'; ?>