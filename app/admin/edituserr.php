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

        $audit = $conn->prepare("INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, created_at) VALUES (?, 'update', 'user', ?, ?, NOW())");
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

<style>
  .field-group { position: relative; }
  .field-group input,
  .field-group select {
    transition: border-color .2s, box-shadow .2s;
  }
  .field-group input:focus,
  .field-group select:focus {
    box-shadow: 0 0 0 3px rgba(29,158,117,.15);
  }
  .role-pill {
    transition: all .18s cubic-bezier(.4,0,.2,1);
  }
  .role-pill.active {
    background: linear-gradient(135deg,#1D9E75,#0e7a5a);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 14px rgba(29,158,117,.35);
    transform: translateY(-1px);
  }
  .save-btn {
    background: linear-gradient(135deg,#1D9E75,#0e7a5a);
    transition: opacity .2s, transform .15s;
  }
  .save-btn:hover { opacity:.9; transform:translateY(-1px); }
  .save-btn:active { transform:translateY(0); }
  .avatar-ring {
    background: linear-gradient(135deg,#1D9E75,#0a6e4f,#3dd9a4);
    padding: 3px;
    border-radius: 50%;
  }
  .section-label {
    font-size:.65rem;
    font-weight:700;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:#94a3b8;
  }
  @keyframes slideIn {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
  }
  .animate-in { animation: slideIn .35s ease both; }
  .animate-in-2 { animation: slideIn .35s .08s ease both; }
  .animate-in-3 { animation: slideIn .35s .16s ease both; }
  .animate-in-4 { animation: slideIn .35s .24s ease both; }
</style>

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

    <!-- ── LEFT COLUMN ── -->

      <!-- Avatar card -->
      <div class="space-y-4">
      <div class="card p-5 flex flex-col items-center text-center animate-in-2">
        <div class="avatar-ring mb-4">
          <div class="flex size-20 items-center justify-center rounded-full bg-gradient-to-br <?= $role_grad[$user['role']] ?? 'from-slate-400 to-slate-600' ?> text-2xl font-bold text-white">
            <?= $initials ?>
          </div>
        </div>
        <p class="font-semibold text-slate-800 dark:text-navy-50 text-base"><?= htmlspecialchars($user['name']) ?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($user['email']) ?></p>
        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
                     <?= $role_colors[$user['role']] ?? 'bg-slate-100 text-slate-600' ?>">
          <i class="fa <?= $role_icons[$user['role']] ?? 'fa-user' ?> text-[10px]"></i>
          <?= $role_labels[$user['role']] ?? $user['role'] ?>
        </span>

        <div class="mt-5 w-full space-y-2 text-left">
          <div class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-navy-700 px-3 py-2">
            <span class="text-xs text-slate-400">User ID</span>
            <span class="font-mono text-xs font-semibold text-slate-600 dark:text-navy-200">#<?= $user['id'] ?></span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-navy-700 px-3 py-2">
            <span class="text-xs text-slate-400">Created</span>
            <span class="text-xs font-medium text-slate-600 dark:text-navy-200"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-navy-700 px-3 py-2">
            <span class="text-xs text-slate-400">Last Login</span>
            <span class="text-xs font-medium <?= $user['last_login'] ? 'text-slate-600 dark:text-navy-200' : 'text-slate-300' ?>">
              <?= $user['last_login'] ? date('d M Y', strtotime($user['last_login'])) : 'Never' ?>
            </span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-navy-700 px-3 py-2">
            <span class="text-xs text-slate-400">Status</span>
            <?php if ($user['is_active']): ?>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-success">
              <span class="size-1.5 rounded-full bg-success"></span> Active
            </span>
            <?php else: ?>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-error">
              <span class="size-1.5 rounded-full bg-error"></span> Inactive
            </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Role selector -->
      <div class="card p-5 animate-in-3">
        <p class="section-label mb-3">Assign Role</p>
        <input type="hidden" name="role" id="roleInput" form="editForm" value="<?= htmlspecialchars($old['role'] ?? '') ?>">
        <div class="grid grid-cols-1 gap-2">
          <?php foreach ($role_labels as $k => $v):
            $icon     = $role_icons[$k] ?? 'fa-user';
            $selected = ($old['role'] ?? '') === $k;
          ?>
          <button type="button" onclick="selectRole('<?= $k ?>')" id="role_<?= $k ?>"
                  class="role-pill flex items-center gap-3 rounded-xl border border-slate-200 dark:border-navy-500 px-3 py-2.5 text-left
                         <?= $selected ? 'active' : 'hover:border-primary/40 hover:bg-slate-50 dark:hover:bg-navy-600' ?>">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg
                        <?= $selected ? 'bg-white/20' : 'bg-slate-100 dark:bg-navy-700' ?>">
              <i class="fa <?= $icon ?> text-xs <?= $selected ? 'text-white' : 'text-slate-400' ?>"></i>
            </div>
            <span class="text-sm font-medium <?= $selected ? 'text-white' : 'text-slate-600 dark:text-navy-200' ?>"><?= $v ?></span>
            <i class="fa fa-check ml-auto text-xs <?= $selected ? 'text-white' : 'text-transparent' ?>"></i>
          </button>
          <?php endforeach; ?>
        </div>
        <?php if (isset($form_errors['role'])): ?>
        <p class="mt-2 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['role']) ?></p>
        <?php endif; ?>
      </div>

    </div>


 <div class="lg:col-span-2">
    <!-- ── RIGHT COLUMN ── -->
    <form id="editForm" method="POST" class="card p-5 animate-in-4">

      <?php if (!empty($form_errors)): ?>
      <div class="flex gap-3 rounded-xl border border-error/30 bg-error/5 p-4 dark:bg-error/10">
        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-error/15">
          <i class="fa fa-triangle-exclamation text-error text-sm"></i>
        </div>
        <div>
          <p class="text-sm font-semibold text-error mb-1">Fix the following:</p>
          <?php foreach ($form_errors as $err): ?>
          <p class="text-xs text-error/80 flex items-center gap-1.5 mt-0.5">
            <i class="fa fa-circle text-[5px]"></i><?= htmlspecialchars($err) ?>
          </p>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Personal Info -->
      <div class="card p-5">
        <p class="section-label mb-4">Personal Information</p>
        <div class="space-y-4">

          <!-- Name -->
<div class="field-group">
  <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">Full Name *</label>
  <div class="relative flex items-center">
    <i class="fa fa-user-pen text-sm text-slate-400 absolute left-3 z-10 pointer-events-none"></i>
    <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required
           placeholder="e.g. Dr. Jane Wanjiku"
           class="form-input w-full rounded-xl border <?= isset($form_errors['name']) ? 'border-error' : 'border-slate-200 dark:border-navy-500' ?> bg-transparent py-2.5 pl-9 pr-4 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent">
  </div>
  <?php if (isset($form_errors['name'])): ?>
  <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['name']) ?></p>
  <?php endif; ?>
</div>

<!-- Email -->
<div class="field-group">
  <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">Email Address *</label>
  <div class="relative flex items-center">
    <i class="fa fa-envelope text-sm text-slate-400 absolute left-5 z-10 pointer-events-none"></i>
    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required
           placeholder="user@facility.org"
           class="form-input w-full rounded-xl border <?= isset($form_errors['email']) ? 'border-error' : 'border-slate-200 dark:border-navy-500' ?> bg-transparent py-2.5 pl-9 pr-4 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent">
  </div>
  <?php if (isset($form_errors['email'])): ?>
  <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['email']) ?></p>
  <?php endif; ?>
</div>

<!-- Location + Facility -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
  <div class="field-group">
    <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">Location</label>
    <div class="relative flex items-center">
      <i class="fa fa-location-dot text-sm text-slate-400 absolute left-3 z-10 pointer-events-none"></i>
      <input type="text" name="location" value="<?= htmlspecialchars($old['location'] ?? '') ?>"
             placeholder="e.g. Kampala"
             class="form-input w-full rounded-xl border border-slate-200 dark:border-navy-500 bg-transparent py-2.5 pl-9 pr-4 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent">
    </div>
  </div>
  <div class="field-group">
    <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">Facility</label>
    <div class="relative flex items-center">
      <i class="fa fa-hospital text-sm text-slate-400 absolute left-3 z-10 pointer-events-none"></i>
      <select name="facility_id"
              class="form-select w-full rounded-xl border border-slate-200 dark:border-navy-500 bg-transparent py-2.5 pl-9 pr-4 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent appearance-none">
        <option value="0">— No Facility —</option>
        <?php foreach ($facilities as $f): ?>
        <option value="<?= $f['id'] ?>" <?= ($old['facility_id'] ?? 0) == $f['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($f['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

        </div>
      </div>

      <!-- Password -->
     <div class="card p-5">
  <div class="flex items-center justify-between mb-4">
    <p class="section-label">Change Password</p>
    <span class="text-xs text-slate-400">Leave blank to keep current</span>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

    <!-- New Password -->
    <div class="field-group">
      <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">New Password</label>
      <div class="relative flex items-center">
        <i class="fa fa-key text-sm text-slate-400 absolute left-4 z-10 pointer-events-none"></i>
        <input type="password" name="password" id="pw1" minlength="8"
               placeholder="Min. 8 characters"
               class="form-input w-full rounded-xl border <?= isset($form_errors['password']) ? 'border-error' : 'border-slate-200 dark:border-navy-500' ?> bg-transparent py-2.5 pl-9 pr-10 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent">
        <button type="button" onclick="togglePw('pw1','eye1')"
                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 hover:text-slate-600">
          <i id="eye1" class="fa fa-eye text-sm"></i>
        </button>
      </div>
      <div class="mt-2 flex gap-1">
        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar1"></div>
        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar2"></div>
        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar3"></div>
        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar4"></div>
      </div>
      <p class="mt-1 text-xs text-slate-400" id="strengthLabel"></p>
      <?php if (isset($form_errors['password'])): ?>
      <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['password']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Confirm Password -->
    <div class="field-group">
      <label class="block text-xs font-semibold text-slate-600 dark:text-navy-200 mb-1.5">Confirm Password</label>
      <div class="relative flex items-center">
        <i class="fa fa-key text-sm text-slate-400 absolute left-4 z-10 pointer-events-none"></i>
        <input type="password" name="confirm_password" id="pw2"
               placeholder="Repeat password"
               class="form-input w-full rounded-xl border <?= isset($form_errors['confirm_password']) ? 'border-error' : 'border-slate-200 dark:border-navy-500' ?> bg-transparent py-2.5 pl-9 pr-10 text-sm hover:border-primary/50 focus:border-primary dark:hover:border-accent/50 dark:focus:border-accent">
        <button type="button" onclick="togglePw('pw2','eye2')"
                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 hover:text-slate-600">
          <i id="eye2" class="fa fa-eye text-sm"></i>
        </button>
      </div>
      <p class="mt-1.5 text-xs" id="matchLabel"></p>
      <?php if (isset($form_errors['confirm_password'])): ?>
      <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['confirm_password']) ?></p>
      <?php endif; ?>
    </div>

  </div>
</div>

      <!-- Actions -->
      <div class="flex items-center justify-between gap-4 pt-1">
        <a href="users.php"
           class="btn h-10 rounded-xl border border-slate-200 px-6 text-sm text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
          Cancel
        </a>
        <button type="submit"
                class="save-btn btn h-10 rounded-xl px-8 text-sm font-semibold text-white shadow-lg shadow-primary/25 gap-2">
          <i class="fa fa-floppy-disk text-sm"></i>
          Save Changes
        </button>
      </div>

    </form>
  </div>
</main>

<script>
function selectRole(key) {
  document.getElementById('roleInput').value = key;
  document.querySelectorAll('.role-pill').forEach(btn => {
    const isSelected = btn.id === 'role_' + key;
    btn.classList.toggle('active', isSelected);
    const iconDiv = btn.querySelector('div');
    const iconEl  = btn.querySelector('div i');
    const label   = btn.querySelector('span');
    const check   = btn.querySelector('i.fa-check');
    if (iconDiv) iconDiv.className = 'flex size-8 shrink-0 items-center justify-center rounded-lg ' + (isSelected ? 'bg-white/20' : 'bg-slate-100 dark:bg-navy-700');
    if (iconEl)  iconEl.className  = iconEl.className.replace(/text-(white|slate-400)/, '') + ' ' + (isSelected ? 'text-white' : 'text-slate-400');
    if (label)   label.className   = 'text-sm font-medium ' + (isSelected ? 'text-white' : 'text-slate-600 dark:text-navy-200');
    if (check)   check.className   = 'fa fa-check ml-auto text-xs ' + (isSelected ? 'text-white' : 'text-transparent');
  });
}

function togglePw(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'text' ? 'fa fa-eye-slash text-sm' : 'fa fa-eye text-sm';
}

document.getElementById('pw1').addEventListener('input', function () {
  const pw    = this.value;
  const bars  = [1,2,3,4].map(i => document.getElementById('bar' + i));
  const label = document.getElementById('strengthLabel');
  let score   = 0;
  if (pw.length >= 8)            score++;
  if (/[A-Z]/.test(pw))         score++;
  if (/[0-9]/.test(pw))         score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const colors = ['bg-error','bg-warning','bg-info','bg-success'];
  const labels = ['Too weak','Fair','Good','Strong'];
  bars.forEach((b, i) => {
    b.className = 'h-1 flex-1 rounded-full ' + (i < score ? colors[score - 1] : 'bg-slate-200 dark:bg-navy-600');
  });
  label.textContent = pw.length === 0 ? '' : (labels[score - 1] ?? 'Too weak');
  label.className   = 'mt-1 text-xs ' + (score >= 3 ? 'text-success' : score === 2 ? 'text-info' : 'text-error');
});

document.getElementById('pw2').addEventListener('input', function () {
  const match = this.value === document.getElementById('pw1').value;
  const label = document.getElementById('matchLabel');
  label.textContent = this.value.length === 0 ? '' : (match ? '✔ Passwords match' : '✘ Does not match');
  label.className   = 'mt-1.5 text-xs ' + (match ? 'text-success' : 'text-error');
});
</script>

<?php $notify_variant = 'success'; require_once 'admin_footer.php'; ?>