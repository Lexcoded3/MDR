<?php
session_start();
$required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

// ── AJAX patient search — must be FIRST before any output ──
if (!empty($_GET['q']) && strlen(trim($_GET['q'])) >= 2) {
    header('Content-Type: application/json');
    $q = trim($_GET['q']);
    $stmt = $conn->prepare("SELECT id, full_name, patient_code, phone FROM patients WHERE is_active = 1 AND (full_name LIKE ? OR patient_code LIKE ?) ORDER BY full_name LIMIT 10");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

$pageTitle = 'Upload Result - GxAlert';
$form_errors = [];
$old = [];
$patient_name = ''; // initialize here

$specimen_types = ['sputum','culture_isolate','blood','urine','csf','pleural_fluid','other'];
$pre_test = trim($_GET['test'] ?? '');

// If patient_id passed from find_patient, pre-populate
$pre_patient = null;
if (!empty($_GET['patient_id'])) {
    $pid = (int)$_GET['patient_id'];
    $pp = $conn->prepare("SELECT id, full_name, patient_code FROM patients WHERE id = ? AND is_active = 1");
    $pp->bind_param("i", $pid);
    $pp->execute();
    
    // FIX: Get the result ONCE and store it in a variable
    $res = $pp->get_result(); 
    if ($res->num_rows === 1) {
        $pre_patient = $res->fetch_assoc();
    }
    $pp->close(); // Always close to clear the buffer for subsequent queries
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $patient_id    = (int)($_POST['patient_id'] ?? 0);
    $test_type     = trim($_POST['test_type'] ?? '');
    $specimen_type = $_POST['specimen_type'] ?? '';
    $result        = trim($_POST['result'] ?? '');
    $result_date   = $_POST['result_date'] ?? '';
    $specimen_date = !empty($_POST['specimen_date']) ? $_POST['specimen_date'] : null;
    $is_final      = isset($_POST['is_final']) && $_POST['is_final'] === '1' ? 1 : 0;
    $lab_facility  = trim($_POST['lab_facility'] ?? '');
    $edit_id       = (int)($_POST['edit_id'] ?? 0);

    // Validations
    if ($patient_id === 0) $form_errors['patient_id'] = 'Select a patient';
    if (empty($test_type)) $form_errors['test_type'] = 'Test type is required';
    if (!in_array($specimen_type, $specimen_types)) $form_errors['specimen_type'] = 'Select specimen type';
    if (empty($result)) $form_errors['result'] = 'Result is required';
    if (empty($result_date)) $form_errors['result_date'] = 'Result date is required';
    if (strtotime($result_date) > time()) $form_errors['result_date'] = 'Date cannot be in the future';

    if ($patient_id > 0) {
    $vp = $conn->prepare("SELECT id, full_name FROM patients WHERE id = ? AND is_active = 1");
    $vp->bind_param("i", $patient_id);
    $vp->execute();
    $v_res = $vp->get_result();
    if ($v_res->num_rows !== 1) {
        $form_errors['patient_id'] = 'Patient not found';
    } else {
        $patient_row = $v_res->fetch_assoc();
        $patient_name = $patient_row['full_name']; // ← captured here
    }
    $vp->close();
}

    if (empty($form_errors)) {
        try {
            if ($edit_id > 0) {
                // Update existing
                $upd = $conn->prepare("
                    UPDATE lab_results 
                    SET patient_id=?, test_type=?, specimen_type=?, result=?, result_date=?, specimen_date=?, uploaded_by=?, lab_facility=?, is_final=?, updated_at=NOW()
                    WHERE id=?
                ");
                $upd->bind_param("isssssssii", $patient_id, $test_type, $specimen_type, $result, $result_date, $specimen_date, $lab_id, $lab_facility, $is_final, $edit_id);
                $upd->execute();
                $upd->close();

                // Audit Update
                $log_id = $edit_id;
                $action = 'UPDATE';
            } else {
                // Insert new
                $ins = $conn->prepare("
                    INSERT INTO lab_results (patient_id, test_type, specimen_type, result, result_date, specimen_date, uploaded_by, lab_facility, is_final)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->bind_param("isssssssi", $patient_id, $test_type, $specimen_type, $result, $result_date, $specimen_date, $lab_id, $lab_facility, $is_final);
                $ins->execute();
                
                $log_id = $conn->insert_id;
                $action = 'INSERT';
                $ins->close();
            }
            require_once '../config/notify_helper.php';
            notify_lab_result($conn, $patient_id, $patient_name, $test_type, $is_final);
            if (!$is_final) {
    notify_preliminary_pending($conn, $lab_id, $patient_name, $test_type);
}
            // Centralized Audit Logic
            $aud = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, ?, 'lab_results', ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $val_json = json_encode($old);
            $aud->bind_param("issis", $lab_id, $action, $log_id, $val_json, $ip);
            $aud->execute();
            $aud->close();

            $redirect_param = ($edit_id > 0) ? "updated" : "uploaded";
            header("Location: upload_result.php?status=$redirect_param");
            exit;

        } catch (Exception $e) {
            error_log("Lab result error: " . $e->getMessage());
            $form_errors['db'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once 'lab_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-3xl">
  <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="index.php" class="hover:text-primary dark:hover:text-accent-light">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100">Upload Result</span>
  </nav>

  <?php if (isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4"><p class="text-sm text-error"><?= $form_errors['db'] ?></p></div>
  <?php endif; ?>
  <?php if (!empty($form_errors) && !isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4">
    <p class="text-sm font-medium text-error mb-1">Fix the following:</p>
    <ul class="list-disc list-inside text-xs text-error/80"><?php foreach ($form_errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
  </div>
  <?php endif; ?>

  <form method="POST" action="" class="space-y-5">
    <input type="hidden" name="edit_id" value="<?= (int)($_GET['edit_id'] ?? 0) ?>">
    <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
<div class="col-span-12 lg:col-span-8">
    <!-- Patient Search -->
    <div class="card"
     x-data="{ selectedPatient: null }"
     x-init="if(<?= $pre_patient ? 'true' : 'false' ?>) { selectedPatient = {id: <?= $pre_patient['id'] ?? 0 ?>, full_name: '<?= addslashes($pre_patient['full_name'] ?? '') ?>', patient_code: '<?= $pre_patient['patient_code'] ?? '' ?>' } }">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Patient</h2>
      </div>
      <div class="p-5">
        <input type="hidden" name="patient_id" :value="selectedPatient ? selectedPatient.id : ''" x-ref="patientInput">
        
        <!-- Selected patient display -->
        <div x-show="selectedPatient" class="mb-3 flex items-center space-x-3 rounded-lg bg-primary/5 p-3">
          <div class="flex size-8 items-center justify-center rounded-full bg-primary/10">
            <svg class="size-4 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-700 dark:text-navy-100" x-text="selectedPatient.full_name"></p>
<p class="text-[10px] font-mono text-slate-400 dark:text-navy-300" x-text="selectedPatient.patient_code"></p>
          </div>
          <button type="button" @click="selectedPatient = null; $refs.patientInput.value = ''" class="ml-auto text-slate-400 hover:text-error">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Search input -->
        <div x-show="!selectedPatient">
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Search patient by name or code</label>
          <input type="text" id="patientSearch" placeholder="Start typing to search..." autocomplete="off"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"
                 x-ref="searchInput"
                 @input.debounce.300ms="fetchPatients($el.value)"
                 <?= isset($form_errors['patient_id']) ? 'ring-2 ring-error' : '' ?>>
          <?php if (isset($form_errors['patient_id'])): ?>
          <p class="mt-0.5 text-[10px] text-error"><?= $form_errors['patient_id'] ?></p>
          <?php endif; ?>

          <!-- Dropdown results -->
          <div x-show="$store.searchResults && $store.searchResults.length > 0" 
               class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-700 shadow-lg"
               x-data="{ open: false }"
               @click.outside="open = false">
            <template x-for="p in $store.searchResults" :key="p.id">
              <button type="button" 
                      @click="selectedPatient = p; $refs.patientInput.value = p.id; $refs.searchInput.value = ''; $store.searchResults = null"
                      class="flex items-center space-x-3 w-full px-4 py-2.5 text-left hover:bg-slate-100 dark:hover:bg-navy-600 transition-colors">
                <div>
                  <p class="text-sm font-medium text-slate-700 dark:text-navy-100" x-text="p.full_name"></p>
                  <p class="text-[10px] font-mono text-slate-400" x-text="p.patient_code + ' · ' + (p.phone || '')"></p>
                </div>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Details -->
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Test Details</h2>
      </div>
      <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Test Type <span class="text-error">*</span></label>
          <input type="text" name="test_type" value="<?= htmlspecialchars($old['test_type'] ?? $pre_test) ?>" placeholder="e.g. Sputum for GeneXpert" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['test_type']) ? 'ring-2 ring-error' : '' ?>">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Specimen Type <span class="text-error">*</span></label>
          <select name="specimen_type" required class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['specimen_type']) ? 'ring-2 ring-error' : '' ?>">
            <option value="">Select...</option>
            <?php foreach ($specimen_types as $st): ?>
            <option value="<?= $st ?>" <?= ($old['specimen_type'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Specimen Date</label>
          <input type="date" name="specimen_date" value="<?= $old['specimen_date'] ?? '' ?>" max="<?= date('Y-m-d') ?>"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Result Date <span class="text-error">*</span></label>
          <input type="date" name="result_date" value="<?= $old['result_date'] ?? date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['result_date']) ? 'ring-2 ring-error' : '' ?>">
        </div>
        <div>
          <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Lab Facility</label>
          <input type="text" name="lab_facility" value="<?= htmlspecialchars($old['lab_facility'] ?? $_SESSION['location'] ?? '') ?>" placeholder="e.g. National Reference Lab"
                 class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>
        <div class="flex items-end">
          <label class="flex items-center space-x-2 cursor-pointer pb-2">
            <input type="checkbox" name="is_final" value="1" class="form-checkbox size-4 rounded border-slate-400 bg-slate-100 before:bg-primary checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent" id="finalCheck">
            <span class="text-sm text-slate-700 dark:text-navy-100">Final Result</span>
            <span class="text-[10px] text-slate-400 dark:text-navy-300">(uncheck for preliminary)</span>
          </label>
        </div>
      </div>
    </div>
</div>
<div class="col-span-12 lg:col-span-4">
    <!-- Result -->
    <div class="card">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Result <span class="text-error">*</span></h2>
      </div>
      <div class="p-5">
        <textarea name="result" rows="5" required placeholder="Enter the test result..."
                  class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 font-mono whitespace-pre-wrap <?= isset($form_errors['result']) ? 'ring-2 ring-error' : '' ?>"><?= htmlspecialchars($old['result'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="flex gap-3 mt-4">
      <button type="submit" class="btn h-10 bg-primary px-6 font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
        <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Upload Result
      </button>
      <a href="index.php" class="btn h-10 bg-slate-100 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Cancel</a>
    </div>
 
</div>
  </form>
 </div>
</div>

<!-- Patient search endpoint via Alpine -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('searchResults', []);
});

function fetchPatients(query) {
    if (query.length < 2) {
        Alpine.store('searchResults', []);
        return;
    }
    fetch(`upload_result.php?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            Alpine.store('searchResults', data);
        });
}
</script>

<?php require_once 'lab_footer.php'; ?>