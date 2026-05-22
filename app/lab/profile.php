<?php
session_start();
$required_role = 'lab';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

$pageTitle = 'Profile - GxAlert';
$notify_text = '';
$notify_variant = 'success'; // Default to success
$form_errors = [];

$user_id = (int)$_SESSION['id'];
$u_stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user = $u_stmt->get_result()->fetch_assoc();

$old = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';
    
    if (empty($name)) $form_errors[] = 'Name is required';
    
    if (!empty($new_password)) {
        if (empty($old_password)) $form_errors[] = 'Enter current password to change it';
        elseif (!password_verify($old_password, $user['password'])) $form_errors[] = 'Current password is incorrect';
        elseif (strlen($new_password) < 8) $form_errors[] = 'New password must be at least 8 characters';
        elseif ($new_password !== $confirm) $form_errors[] = 'New passwords do not match';
    }
    
    if (empty($form_errors)) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE staff SET name = ?, phone = ?, password = ? WHERE id = ?");
            $upd->bind_param("sssi", $name, $phone, $hashed, $user_id);
        } else {
            $upd = $conn->prepare("UPDATE staff SET name = ?, phone = ? WHERE id = ?");
            $upd->bind_param("ssi", $name, $phone, $user_id);
        }
        $upd->execute();
        
        $_SESSION['name'] = $name;
        
        // Success: Redirect to trigger the URL search param logic in footer
        header("Location: profile.php?status=Profile updated");
        exit;
    } else {
        // Error: Set variables for immediate footer trigger
        $notify_text = implode(' | ', $form_errors);
        $notify_variant = 'error';
    }
}

// Handle the "Profile updated" success state if redirected
if (isset($_GET['status']) && $_GET['status'] === 'Profile updated') {
    $notify_text = 'Profile updated';
    $notify_variant = 'success';
}
?>
<?php require_once 'lab_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="flex items-center space-x-4 py-5 lg:py-6">
    <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">My Profile</h2>
    <div class="hidden h-full py-1 sm:flex">
      <div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div>
    </div>
    <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
      <li class="flex items-center space-x-2">
        <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="index.php">Dashboard</a>
        <svg x-ignore xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </li>
      <li>Profile Settings</li>
    </ul>
  </div>

  <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
    <div class="col-span-12 lg:col-span-4">
      <div class="card p-4 sm:p-5 text-center">
        <div class="avatar mx-auto size-20">
          <div class="rounded-full bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light flex items-center justify-center">
            <span class="text-2xl font-semibold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
          </div>
        </div>
        <h3 class="mt-4 text-lg font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($user['name']) ?></h3>
        <p class="text-xs text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
        <div class="mt-6 flex flex-col space-y-2">
          <div class="flex justify-between text-xs">
            <span class="text-slate-400">Status</span>
            <span class="font-medium text-success text-xs">Active</span>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-slate-400">Role</span>
            <span class="font-medium text-slate-700 dark:text-navy-100">Nurse</span>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-slate-400">Location</span>
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($user['location'] ?? 'N/A') ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-8">
      <form method="POST" class="card p-4 sm:p-5">
        <input type="hidden" name="update_profile" value="1">
        
        <div class="flex items-center space-x-2 mb-4">
            <svg class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <h4 class="font-medium text-slate-700 dark:text-navy-100">Personal Information</h4>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="block">
            <span class="text-xs font-medium text-slate-500 uppercase">Full Name</span>
            <input name="name" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" value="<?= htmlspecialchars($old['name'] ?? '') ?>" type="text" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-slate-500 uppercase">Email</span>
            <input disabled name="phone" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" not-allowed type="email" value="<?= htmlspecialchars($user['email']) ?>"/>
          </label>
        </div>

        <div class="mt-8 flex items-center space-x-2 mb-4">
            <svg class="size-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h4 class="font-medium text-slate-700 dark:text-navy-100">Security & Password</h4>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <label class="block">
            <span class="text-xs font-medium text-slate-500 uppercase">Current Password</span>
            <input name="old_password" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" type="password" placeholder="••••••••" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-slate-500 uppercase">New Password</span>
            <input name="new_password" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" type="password" placeholder="Min 8 chars" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-slate-500 uppercase">Confirm New</span>
            <input name="confirm_password" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450" type="password" placeholder="••••••••" />
          </label>
        </div>

        <div class="mt-6 flex justify-between items-center border-t border-slate-200 pt-6 dark:border-navy-500">
          <p class="text-[11px] text-slate-400">Last login: <span class="font-medium"><?= $user['last_login'] ?? 'Just now' ?></span></p>
          <button type="submit" class="btn h-9 bg-primary font-medium text-white shadow-lg shadow-primary/20 hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:shadow-accent/20 dark:hover:bg-accent-focus px-8">
            Update Profile
          </button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php $notify_variant = 'success'; require_once 'lab_footer.php'; ?>