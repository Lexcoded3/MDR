<?php
session_start();
$required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

$pageTitle = 'Register Patient - GxAlert';
$form_errors = [];
$old = [];

// 1. Facilities dropdown
$facilities = $conn->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// 2. Generate next patient code
$year = date('Y');
$prefix = "TB-$year-";
$lc = $conn->prepare("SELECT patient_code FROM patients WHERE patient_code LIKE ? ORDER BY id DESC LIMIT 1");
$search_prefix = $prefix . "%";
$lc->bind_param("s", $search_prefix);
$lc->execute();
$last = $lc->get_result()->fetch_column();
$lc->close(); // Free connection

$nextNum = $last ? (int)substr($last, -4) + 1 : 1;
$nextCode = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

// 3. Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'patient_code','full_name','gender','date_of_birth','national_id','phone','address',
        'facility_id','next_of_kin','next_of_kin_contact','enrollment_date','date_of_diagnosis',
        'tb_case_classification','mdr_confirmation','hiv_status','on_art','weight_kg','create_user_account'
    ];
    foreach ($fields as $f) $old[$f] = $_POST[$f] ?? '';

    // Validation
    if (empty(trim($old['full_name']))) $form_errors['full_name'] = 'Name is required';
    if (!in_array($old['gender'], ['male','female','other'])) $form_errors['gender'] = 'Select gender';
    
    if (empty($old['date_of_birth'])) { 
        $form_errors['date_of_birth'] = 'DOB required'; 
    } else {
        $age = (new DateTime('today'))->diff(new DateTime($old['date_of_birth']))->y;
        if ($age < 0 || $age > 120) $form_errors['date_of_birth'] = 'Invalid DOB';
    }

    if (empty($old['facility_id']) || !is_numeric($old['facility_id'])) $form_errors['facility_id'] = 'Select facility';
    
    // Duplicate code check
    $dc = $conn->prepare("SELECT id FROM patients WHERE patient_code = ?");
    $dc->bind_param("s", $old['patient_code']);
    $dc->execute();
    if ($dc->get_result()->num_rows > 0) $form_errors['patient_code'] = 'Code already exists';
    $dc->close(); // Free connection

    // Account creation logic
    $create_account = ($old['create_user_account'] ?? '') === '1';
    $patient_email = trim($_POST['patient_email'] ?? '');
    if ($create_account) {
        if (empty($patient_email)) $form_errors['patient_email'] = 'Email required for patient login';
        elseif (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) $form_errors['patient_email'] = 'Invalid email format';
    }

    if (empty($form_errors)) {
        $conn->begin_transaction();
        try {
            // Prepare all variables for pass-by-reference (Fixes Argument #6 Error)
            $f_name   = trim($old['full_name']);
            $nat_id   = $old['national_id'] ?: null;
            $phone    = $old['phone'] ?: null;
            $addr     = $old['address'] ?: null;
            $fac_id   = (int)$old['facility_id'];
            $nok      = $old['next_of_kin'] ?: null;
            $nok_c    = $old['next_of_kin_contact'] ?: null;
            $diag_d   = $old['date_of_diagnosis'] ?: null;
            $art_stat = ($old['hiv_status'] === 'positive' && ($old['on_art'] ?? '') === '1') ? 1 : 0;
            $weight   = !empty($old['weight_kg']) ? (float)$old['weight_kg'] : null;
            $status   = 'enrolled';

            $ins = $conn->prepare("
                INSERT INTO patients (
                    patient_code, full_name, gender, date_of_birth, national_id,
                    phone, address, facility_id, next_of_kin, next_of_kin_contact, 
                    created_by, enrollment_date, date_of_diagnosis, tb_case_classification, 
                    mdr_confirmation, hiv_status, on_art, weight_kg, treatment_status
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $ins->bind_param("ssssssssssisssssids",
                $old['patient_code'], $f_name, $old['gender'], $old['date_of_birth'],
                $nat_id, $phone, $addr, $fac_id, $nok, $nok_c,
                $clinician_id, $old['enrollment_date'], $diag_d,
                $old['tb_case_classification'], $old['mdr_confirmation'], 
                $old['hiv_status'], $art_stat, $weight, $status
            );
            $ins->execute();
            $patient_id = $conn->insert_id;
            $ins->close();
            require_once '../config/notify_helper.php';
            notify_patient_enrolled($conn, $f_name, $old['patient_code']);
            // 4. Create User Account
            if ($create_account && !empty($patient_email)) {
                $temp_pw = substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
                $hashed  = password_hash($temp_pw, PASSWORD_DEFAULT);
                $loc     = $_SESSION['location'] ?? '';
                $p_role  = 'patient';

                $usr = $conn->prepare("INSERT INTO users (name, email, password, role, location) VALUES (?, ?, ?, ?, ?)");
                $usr->bind_param("sssss", $f_name, $patient_email, $hashed, $p_role, $loc);
                $usr->execute();
                $user_id = $conn->insert_id;
                $usr->close();

                $link = $conn->prepare("UPDATE patients SET user_id = ? WHERE id = ?");
                $link->bind_param("ii", $user_id, $patient_id);
                $link->execute();
                $link->close();

                $_SESSION['temp_credentials'] = ['email' => $patient_email, 'password' => $temp_pw];
            }

            // 5. Audit Logging
            $audit_action = 'INSERT';
            $audit_table  = 'patients';
            $audit_values = json_encode($old);
            $ip_addr      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $aud = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $aud->bind_param("ississ", $clinician_id, $audit_action, $audit_table, $patient_id, $audit_values, $ip_addr);
            $aud->execute();
            $aud->close();

            $conn->commit();
            header("Location: viewpatient.php?id=$patient_id&status=registered");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Patient registration error: " . $e->getMessage());
            $form_errors['db'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl">
  <!-- <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="patients.php" class="hover:text-primary dark:hover:text-accent-light">Patients</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100">Register</span>
  </nav> -->
  <div class="flex items-center space-x-4 py-5 lg:py-2">
          <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50 lg:text-2xl">
            Patients
          </h2>
          <div class="hidden h-full py-1 sm:flex">
            <div class="h-full w-px bg-slate-300 dark:bg-navy-600"></div>
          </div>
          <ul class="hidden flex-wrap items-center space-x-2 sm:flex">
            <li class="flex items-center space-x-2">
              <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="addpatient.php">Register</a>
              <svg x-ignore="" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </li>
            <li>Add</li>
          </ul>
        </div>

  <?php if (isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4"><p class="text-sm text-error"><?= $form_errors['db'] ?></p></div>
  <?php endif; ?>
  <?php if (!empty($form_errors) && !isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4">
    <p class="text-sm font-medium text-error mb-1">Fix the following:</p>
    <ul class="list-disc list-inside text-xs text-error/80"><?php foreach ($form_errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
  </div>
  <?php endif; ?>
<div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
  <div class="col-span-12 grid lg:col-span-6">
  <form method="POST" action="" class="space-y-6">

    <!-- Demographics -->
    <div class="card">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Demographics</h2>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 sm:gap-x-6">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Patient Code <span class="text-error">*</span></label>
          <input type="text" name="patient_code" value="<?= htmlspecialchars($old['patient_code'] ?? $nextCode) ?>" readonly
                 class="form-input w-full rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-500 dark:bg-navy-800 dark:text-navy-300">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Full Name <span class="text-error">*</span></label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['full_name']) ? 'ring-2 ring-error' : '' ?>"
                 placeholder="Surname First Middle">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Gender <span class="text-error">*</span></label>
          <select name="gender" required class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['gender']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select...</option>
            <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Date of Birth <span class="text-error">*</span></label>
          <input type="date" name="date_of_birth" value="<?= $old['date_of_birth'] ?? '' ?>" max="<?= date('Y-m-d') ?>" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['date_of_birth']) ? 'ring-2 ring-error' : '' ?>">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">National ID</label>
          <input type="text" name="national_id" value="<?= htmlspecialchars($old['national_id'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" placeholder="07XXXXXXXX"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['phone']) ? 'ring-2 ring-error' : '' ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Address</label>
          <textarea name="address" rows="2" class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Facility <span class="text-error">*</span></label>
          <select name="facility_id" required class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['facility_id']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select facility...</option>
            <?php foreach ($facilities as $f): ?>
            <option value="<?= $f['id'] ?>" <?= ($old['facility_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Weight (kg)</label>
          <input type="number" name="weight_kg" value="<?= $old['weight_kg'] ?? '' ?>" min="20" max="200" step="0.1"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['weight_kg']) ? 'ring-2 ring-error' : '' ?>">
        </div>
      </div>
    </div>

    <!-- Contact Person -->
    <div class="card">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Next of Kin</h2>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 sm:gap-x-6">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Name</label>
          <input type="text" name="next_of_kin" value="<?= htmlspecialchars($old['next_of_kin'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Phone</label>
          <input type="tel" name="next_of_kin_contact" value="<?= htmlspecialchars($old['next_of_kin_contact'] ?? '') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
      </div>
    </div>
</div>
<div class="col-span-12 grid lg:col-span-6">
    <!-- Clinical -->
    <div class="card">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Clinical Information</h2>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 sm:grid-cols-3 sm:gap-x-6">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Enrollment Date <span class="text-error">*</span></label>
          <input type="date" name="enrollment_date" value="<?= $old['enrollment_date'] ?? date('Y-m-d') ?>" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['enrollment_date']) ? 'ring-2 ring-error' : '' ?>">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Date of Diagnosis</label>
          <input type="date" name="date_of_diagnosis" value="<?= $old['date_of_diagnosis'] ?? '' ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Case Classification <span class="text-error">*</span></label>
          <select name="tb_case_classification" required class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['tb_case_classification']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select...</option>
            <?php foreach (['new'=>'New','previously_treated'=>'Previously Treated','relapse'=>'Relapse','failure'=>'Failure','return_after_default'=>'Return After Default','transfer_in'=>'Transfer In'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($old['tb_case_classification'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">TB Confirmation <span class="text-error">*</span></label>
          <select name="mdr_confirmation" required class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['mdr_confirmation']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select...</option>
            <option value="confirmed" <?= ($old['mdr_confirmation'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="presumed" <?= ($old['mdr_confirmation'] ?? '') === 'presumed' ? 'selected' : '' ?>>Presumed</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">HIV Status <span class="text-error">*</span></label>
          <select name="hiv_status" required id="hivSelect" class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['hiv_status']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select...</option>
            <option value="positive" <?= ($old['hiv_status'] ?? '') === 'positive' ? 'selected' : '' ?>>Positive</option>
            <option value="negative" <?= ($old['hiv_status'] ?? '') === 'negative' ? 'selected' : '' ?>>Negative</option>
            <option value="unknown" <?= ($old['hiv_status'] ?? '') === 'unknown' ? 'selected' : '' ?>>Unknown</option>
          </select>
        </div>
        <div id="artField" style="display:<?= ($old['hiv_status'] ?? '') === 'positive' ? '' : 'none' ?>">
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">On ART?</label>
          <select name="on_art" class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
            <option value="">Select...</option>
            <option value="1" <?= ($old['on_art'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= ($old['on_art'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Patient Login Account -->
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <div class="flex items-center justify-between">
          <h2 class="font-medium text-slate-700 dark:text-navy-100">Patient Login Account</h2>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="create_user_account" value="1" class="form-checkbox size-4 rounded border-slate-400 bg-slate-100 before:bg-primary checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent" id="createAcctToggle">
            <span class="text-xs text-slate-500 dark:text-navy-300">Enable patient portal access</span>
          </label>
        </div>
        <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">If enabled, the patient will be able to log in and view their medications, lab results, and adherence.</p>
      </div>
      <div id="acctFields" class="hidden p-5">
        <div class="max-w-sm">
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Patient Email <span class="text-error">*</span></label>
          <input type="email" name="patient_email" value="<?= htmlspecialchars($_POST['patient_email'] ?? '') ?>" placeholder="patient@email.com"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['patient_email']) ? 'ring-2 ring-error' : '' ?>">
          <p class="mt-1 text-[10px] text-slate-400 dark:text-navy-300">A temporary password will be generated. Share it securely with the patient.</p>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3 mt-5">
      <button type="submit" class="btn h-10 bg-primary px-6 font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
        Register Patient
      </button>
      <a href="patients.php" class="btn h-10 bg-slate-100 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Cancel</a>
    </div>
  </form>
</div>
</div>
</div>

<script>
document.getElementById('hivSelect').addEventListener('change', function() {
    document.getElementById('artField').style.display = this.value === 'positive' ? '' : 'none';
});
document.getElementById('createAcctToggle').addEventListener('change', function() {
    document.getElementById('acctFields').classList.toggle('hidden', !this.checked);
});
</script>

<?php
 $notify_text = '';
require_once 'clinician_footer.php'; ?>