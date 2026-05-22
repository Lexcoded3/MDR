<?php
session_start();
$required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

$pageTitle   = 'Add User - GxAlert';
$form_errors = [];
$old         = [];

$facilities = $conn->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['name','email','role','location','phone'];
    foreach ($fields as $f) $old[$f] = trim($_POST[$f] ?? '');
    $old['facility_id'] = (int)($_POST['facility_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($old['name']))    $form_errors['name']  = 'Full name is required';
    if (empty($old['email']))   $form_errors['email'] = 'Email is required';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $form_errors['email'] = 'Invalid email format';
    else {
        $ck = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $ck->bind_param("s", $old['email']);
        $ck->execute();
        if ($ck->get_result()->num_rows > 0) $form_errors['email'] = 'Email already registered';
    }
    if (!isset($role_labels[$old['role']])) $form_errors['role'] = 'Please select a valid role';
    if (strlen($password) < 8)  $form_errors['password'] = 'Minimum 8 characters required';
    if ($password !== $confirm)  $form_errors['confirm_password'] = 'Passwords do not match';

    if (empty($form_errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // CORRECT
        $ins = $conn->prepare("INSERT INTO users (name, email, password, role, location, phone, facility_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $ins->bind_param("ssssssi", $old['name'], $old['email'], $hashed, $old['role'], $old['location'], $old['phone'], $old['facility_id']);
        $ins->execute();
        $ins->close();
        require_once '../config/notify_helper.php';
        notify_user_created($conn, $old['name'], $role_labels[$old['role']] ?? $old['role']);
        header("Location: users.php?status=created");
        exit;
    }
}

$role_icons = [
    'admin'        => 'fa-shield-halved',
    'doctor'       => 'fa-user-doctor',
    'nurse'        => 'fa-hand-holding-medical',
    'clinician'    => 'fa-stethoscope',
    'lab_personnel'=> 'fa-flask',
    'data_officer' => 'fa-database',
    'patient'      => 'fa-user',
];
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">

  <!-- Breadcrumb -->
  <div class="mt-4 flex items-center gap-2 text-xs text-slate-400 dark:text-navy-300">
    <a href="users.php" class="hover:text-primary dark:hover:text-accent">Users</a>
    <i class="fa fa-chevron-right text-[10px]"></i>
    <span class="text-slate-600 dark:text-navy-100">Add New User</span>
  </div>

  <div class="mt-4 flex items-center gap-3 mb-6">
    <a href="users.php" class="btn size-9 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600" title="Back">
      <i class="fa fa-arrow-left text-sm"></i>
    </a>
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Add New User</h1>
      <p class="text-xs text-slate-400 mt-0.5">Create a staff or patient account with role-based access</p>
    </div>
  </div>

  <?php if (!empty($form_errors)): ?>
  <div class="mb-5 flex gap-3 rounded-xl border border-error/30 bg-error/5 p-4 dark:bg-error/10">
    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-error/15">
      <i class="fa fa-triangle-exclamation text-error text-sm"></i>
    </div>
    <div>
      <p class="text-sm font-medium text-error mb-1">Please fix the following errors:</p>
      <ul class="space-y-0.5">
        <?php foreach ($form_errors as $err): ?>
        <li class="text-xs text-error/80 flex items-center gap-1.5">
          <i class="fa fa-circle text-error text-[5px]"></i>
          <?= htmlspecialchars($err) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">

    <!-- LEFT: Main fields (2/3 width) -->
    <div class="lg:col-span-2 space-y-5">

      <!-- Personal Info Card -->
      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-navy-600">
          <div class="flex size-7 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
            <i class="fa fa-user text-primary dark:text-accent text-xs"></i>
          </div>
          <h3 class="text-sm font-semibold text-slate-700 dark:text-navy-100">Personal Information</h3>
        </div>

        <div class="space-y-4">
          <!-- Full Name -->
          <div>
            <label class="block">
                  <span>Full Name</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent <?= isset($form_errors['name']) ? 'border border-error' : '' ?>"  type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                     placeholder="e.g. Dr. Jane Wanjiku" required>
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-user-pen text-base"></i>
                    </span>
                  </span>
                </label>
            <?php if (isset($form_errors['name'])): ?>
            <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['name']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div>
            <label class="block">
                  <span>Email Address</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent <?= isset($form_errors['email']) ? 'border border-error' : '' ?>" type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                     placeholder="user@facility.org" required>
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-envelope text-base"></i>
                    </span>
                  </span>
                </label>
            <?php if (isset($form_errors['name'])): ?>
            <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['name']) ?></p>
            <?php endif; ?>
          </div>
          <!-- Phone + Location -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
            <label class="block">
                  <span>Phone Number</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                       placeholder="+256 700 000 000">
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-phone text-base"></i>
                    </span>
                  </span>
                </label>
          </div>
          <div>
            <label class="block">
                  <span>Location</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"  type="text" name="location" value="<?= htmlspecialchars($old['location'] ?? '') ?>"
                       placeholder="e.g. Kampala">
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-location-dot text-base"></i>
                    </span>
                  </span>
                </label>
          </div>
          </div>

          <!-- Facility -->
          <?php if (!empty($facilities)): ?>
            <div>
            <label class="block">
                  <span>Assigned Facility</span>
                  <span class="relative mt-1.5 flex">
                   <select name="facility_id"
                      class="form-select w-full rounded-lg bg-slate-100 py-2.5 pl-9 pr-3 text-sm dark:bg-navy-900/90">
                <option value="">— No Facility —</option>
                <?php foreach ($facilities as $f): ?>
                <option value="<?= $f['id'] ?>" <?= ($old['facility_id'] ?? 0) == $f['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($f['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-hospital text-base"></i>
                    </span>
                  </span>
                </label>
            
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Password Card -->
      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-navy-600">
          <div class="flex size-7 items-center justify-center rounded-lg bg-warning/10">
            <i class="fa fa-lock text-warning text-xs"></i>
          </div>
          <h3 class="text-sm font-semibold text-slate-700 dark:text-navy-100">Set Password</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block">
                  <span>Password</span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent <?= isset($form_errors['password']) ? 'border border-error' : '' ?>" type="password" name="password" id="pw1" required minlength="8"
                     placeholder="Min. 8 characters">
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-key text-base"></i>
                    </span>
                    <button type="button" onclick="togglePw('pw1','eye1')"
                      class="absolute right-3 flex top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <i id="eye1" class="fa fa-eye text-sm"></i>
              </button>
                  </span>
                </label>
                <?php if (isset($form_errors['password'])): ?>
            <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['password']) ?></p>
            <?php endif; ?>
            <!-- Strength bar -->
            <div class="mt-2 flex gap-1" id="strengthBars">
              <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar1"></div>
              <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar2"></div>
              <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar3"></div>
              <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-navy-600" id="bar4"></div>
            </div>
            <p class="mt-1 text-xs text-slate-400" id="strengthLabel">Enter a password</p>
          </div>
          <div>
            <label class="block">
                  <span> Confirm Password </span>
                  <span class="relative mt-1.5 flex">
                    <input class="form-input peer w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 pl-9 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent<?= isset($form_errors['confirm_password']) ? 'border border-error' : '' ?>" type="password" name="confirm_password" id="pw2" required
                     placeholder="Repeat password">
                    <span class="pointer-events-none absolute flex h-full w-10 items-center justify-center text-slate-400 peer-focus:text-primary dark:text-navy-300 dark:peer-focus:text-accent">
                      <i class="fa fa-key text-base"></i>
                    </span>
                    <button type="button" onclick="togglePw('pw2','eye2')"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <i id="eye2" class="fa fa-eye text-sm"></i>
              </button>
                  </span>
                </label>
                <?php if (isset($form_errors['confirm_password'])): ?>
            <p class="mt-1 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['confirm_password']) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs" id="matchLabel"></p>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT: Role selector (1/3 width) -->
    <div class="space-y-5 lg:mt-0 mt-2"> 

      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-navy-600">
          <div class="flex size-7 items-center justify-center rounded-lg bg-success/10">
            <i class="fa fa-id-badge text-success text-xs"></i>
          </div>
          <h3 class="text-sm font-semibold text-slate-700 dark:text-navy-100">User Role</h3>
        </div>

        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($old['role'] ?? '') ?>">

        <div class="space-y-2" id="roleList">
          <?php foreach ($role_labels as $k => $v):
            $icon    = $role_icons[$k] ?? 'fa-user';
            $selected = ($old['role'] ?? '') === $k;
          ?>
          <button type="button"
                  onclick="selectRole('<?= $k ?>')"
                  id="role_<?= $k ?>"
                  class="role-btn w-full flex items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition-all
                         <?= $selected
                           ? 'border-primary bg-primary/5 dark:border-accent dark:bg-accent/5'
                           : 'border-slate-200 hover:border-primary/40 hover:bg-slate-50 dark:border-navy-500 dark:hover:border-accent/40 dark:hover:bg-navy-600' ?>">
            <div class="flex size-7 shrink-0 items-center justify-center rounded-lg
                        <?= $selected ? 'bg-primary/15 dark:bg-accent/15' : 'bg-slate-100 dark:bg-navy-700' ?>">
              <i class="fa <?= $icon ?> text-xs <?= $selected ? 'text-primary dark:text-accent' : 'text-slate-400' ?>"></i>
            </div>
            <span class="text-sm font-medium <?= $selected ? 'text-primary dark:text-accent' : 'text-slate-600 dark:text-navy-200' ?>">
              <?= $v ?>
            </span>
            <?php if ($selected): ?>
            <i class="fa fa-check ml-auto text-xs text-primary dark:text-accent"></i>
            <?php else: ?>
            <i class="fa fa-check ml-auto text-xs text-transparent" aria-hidden="true"></i>
            <?php endif; ?>
          </button>
          <?php endforeach; ?>
        </div>

        <?php if (isset($form_errors['role'])): ?>
        <p class="mt-2 text-xs text-error"><i class="fa fa-circle-exclamation mr-1"></i><?= htmlspecialchars($form_errors['role']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Submit -->
      <div class="card p-4 bg-slate-50 dark:bg-navy-700/50">
        <button type="submit"
                class="btn w-full h-10 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus font-medium gap-2">
          <i class="fa fa-user-plus text-sm"></i>
          Create User Account
        </button>
        <a href="users.php"
           class="btn mt-2 w-full h-10 border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600">
          Cancel
        </a>
        <p class="mt-3 text-center text-xs text-slate-400">
          User will receive access based on selected role
        </p>
      </div>

    </div>
  </form>
</main>

<script>
// Role selector
function selectRole(key) {
  document.getElementById('roleInput').value = key;
  document.querySelectorAll('.role-btn').forEach(btn => {
    const isSelected = btn.id === 'role_' + key;
    btn.className = 'role-btn w-full flex items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition-all ' +
      (isSelected
        ? 'border-primary bg-primary/5 dark:border-accent dark:bg-accent/5'
        : 'border-slate-200 hover:border-primary/40 hover:bg-slate-50 dark:border-navy-500 dark:hover:border-accent/40 dark:hover:bg-navy-600');
    const iconDiv = btn.querySelector('div');
    const iconEl  = btn.querySelector('div i');
    const label   = btn.querySelector('span');
    const check   = btn.querySelector('i.fa-check');
    iconDiv.className = 'flex size-7 shrink-0 items-center justify-center rounded-lg ' +
      (isSelected ? 'bg-primary/15 dark:bg-accent/15' : 'bg-slate-100 dark:bg-navy-700');
    iconEl.className  = iconEl.className.replace(/text-\S+$/, '') +
      (isSelected ? ' text-primary dark:text-accent' : ' text-slate-400');
    label.className   = 'text-sm font-medium ' +
      (isSelected ? 'text-primary dark:text-accent' : 'text-slate-600 dark:text-navy-200');
    if (check) check.className = 'fa fa-check ml-auto text-xs ' +
      (isSelected ? 'text-primary dark:text-accent' : 'text-transparent');
  });
}

// Password toggle
function togglePw(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa fa-eye-slash text-sm';
  } else {
    input.type = 'password';
    icon.className = 'fa fa-eye text-sm';
  }
}

// Password strength
document.getElementById('pw1').addEventListener('input', function() {
  const pw   = this.value;
  const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
                document.getElementById('bar3'), document.getElementById('bar4')];
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (pw.length >= 8)              score++;
  if (/[A-Z]/.test(pw))           score++;
  if (/[0-9]/.test(pw))           score++;
  if (/[^A-Za-z0-9]/.test(pw))   score++;

  const colors  = ['bg-error', 'bg-warning', 'bg-info', 'bg-success'];
  const labels  = ['Too weak', 'Fair', 'Good', 'Strong'];
  bars.forEach((b, i) => {
    b.className = 'h-1 flex-1 rounded-full ' + (i < score ? colors[score - 1] : 'bg-slate-200 dark:bg-navy-600');
  });
  label.textContent = pw.length === 0 ? 'Enter a password' : labels[score - 1] ?? 'Too weak';
  label.className   = 'mt-1 text-xs ' + (score >= 3 ? 'text-success' : score === 2 ? 'text-warning' : 'text-error');
});

// Password match
document.getElementById('pw2').addEventListener('input', function() {
  const match  = this.value === document.getElementById('pw1').value;
  const label  = document.getElementById('matchLabel');
  label.textContent = this.value.length === 0 ? '' : match ? '✔ Passwords match' : '✘ Passwords do not match';
  label.className   = 'mt-1 text-xs ' + (match ? 'text-success' : 'text-error');
});
</script>

<?php require_once 'admin_footer.php'; ?>