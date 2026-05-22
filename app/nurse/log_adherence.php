<?php
session_start();
 $required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

 $pageTitle = 'Log Adherence (DOT) - GxAlert';
 $notify_text = '';
 $form_errors = [];

 $pre_patient_id = (int)($_GET['patient_id'] ?? 0);
 $log_date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $log_date)) $log_date = date('Y-m-d');
if ($log_date > date('Y-m-d')) $log_date = date('Y-m-d');

// Get facility patients on treatment
 $all_treatment_patients = [];
 $nurse_facility = $conn->prepare("SELECT location FROM users WHERE id = ?");
 $nurse_facility->bind_param("i", $nurse_id);
 $nurse_facility->execute();
 $nurse_loc = $nurse_facility->get_result()->fetch_column();

 $tp_stmt = $conn->prepare("
    SELECT p.id, p.full_name, p.patient_code
    FROM patients p
    JOIN treatment_regimens tr ON p.id = tr.patient_id AND tr.status = 'active'
    WHERE p.is_active = 1 AND p.treatment_status = 'on_treatment'
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY p.full_name
");
 $like = "%$nurse_loc%";
 $tp_stmt->bind_param("s", $like);
 $tp_stmt->execute();
 $all_treatment_patients = $tp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle single dose log (from dashboard quick action)
if (isset($_GET['drug_id']) && isset($_GET['dose_time']) && $pre_patient_id > 0) {
    $drug_id = (int)$_GET['drug_id'];
    $dose_time = $_GET['dose_time'];
    $status = 'taken';
    $actual_time = date('H:i:s');
    $notes = '';

    // Upsert
    $up = $conn->prepare("
        INSERT INTO adherence_logs (patient_id, drug_id, dose_date, dose_time, status, actual_time, notes, logged_by, logged_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), actual_time = VALUES(actual_time), notes = VALUES(notes), logged_by = VALUES(logged_by), logged_at = NOW()
    ");
    $up->bind_param("iisssssi", $pre_patient_id, $drug_id, $log_date, $dose_time, $status, $actual_time, $notes, $nurse_id);
    $up->execute();
    
    header("Location: log_adherence.php?patient_id=$pre_patient_id&date=$log_date&status=logged");
    exit;
}

// Handle batch form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_log'])) {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $doses = $_POST['doses'] ?? []; // [schedule_id => ['status' => ..., 'actual_time' => ..., 'notes' => ...]]
    
    if ($pid === 0) {
        $form_errors[] = 'Select a patient';
    } else {
        $logged = 0;
        foreach ($doses as $schedule_id => $data) {
            $sid = (int)$schedule_id;
            $status = in_array($data['status'] ?? '', ['taken','late','missed']) ? $data['status'] : 'missed';
            $actual_time = ($status === 'late') ? ($data['actual_time'] ?? date('H:i:s')) : ($status === 'taken' ? date('H:i:s') : null);
            $notes = trim($data['notes'] ?? '');
            
            // Get drug_id and dose_time from schedule
            $sch = $conn->prepare("SELECT patient_id, drug_id, dose_time FROM medication_schedule WHERE id = ?");
            $sch->bind_param("i", $sid);
            $sch->execute();
            $sch_data = $sch->get_result()->fetch_assoc();
            
            if ($sch_data) {
                $up = $conn->prepare("
                    INSERT INTO adherence_logs (patient_id, drug_id, dose_date, dose_time, status, actual_time, notes, logged_by, logged_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status), actual_time = VALUES(actual_time), notes = VALUES(notes), logged_by = VALUES(logged_by), logged_at = NOW()
                ");
                $up->bind_param("iisssssi", $sch_data['patient_id'], $sch_data['drug_id'], $log_date, $sch_data['dose_time'], $status, $actual_time, $notes, $nurse_id);
                $up->execute();
                $logged++;
            }
        }
        
        // Audit
        $audit = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (?, 'create', 'adherence', ?, ?, NOW())");
        $audit->bind_param("iis", $nurse_id, $pid, json_encode(['date' => $log_date, 'logged' => $logged]));
        $audit->execute();
        
        $notify_text = "Logged $logged dose(s)";
        header("Location: log_adherence.php?patient_id=$pid&date=$log_date&status=logged");
        exit;
    }
}

// Fetch selected patient's doses
 $doses = [];
 $patient_info = null;
if ($pre_patient_id > 0) {
    // Verify patient belongs to facility
    $valid = false;
    foreach ($all_treatment_patients as $tp) {
        if ($tp['id'] === $pre_patient_id) { $valid = true; $patient_info = $tp; break; }
    }
    
    if ($valid) {
        $d_stmt = $conn->prepare("
    SELECT 
        ms.*, 
        d.drug_name, 
        d.drug_code, 
        d.default_dose_mg, 
        d.unit,
        al.status AS log_status, 
        al.actual_time_taken, 
        al.notes AS log_notes
    FROM medication_schedule ms
    JOIN drugs d ON ms.drug_id = d.id
    /* Bridge: Schedule -> Treatment Regimens */
    JOIN treatment_regimens tr ON ms.regimen_id = tr.id
    /* Bridge: Treatment Regimens -> Patient */
    JOIN patients p ON p.regimen_id = tr.id
    /* Left Join the logs for the specific date and patient */
    LEFT JOIN adherence_logs al ON al.schedule_id = ms.id 
        AND al.patient_id = p.id 
        AND al.dose_date = ?
    WHERE p.id = ? 
    AND ms.is_active = 1
    ORDER BY ms.dose_time, d.drug_name
");

$d_stmt->bind_param("si", $log_date, $pre_patient_id);
$d_stmt->execute();
$doses = $d_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<?php require_once 'nurse_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">DOT Adherence Logging</h1>
    <div class="flex items-center gap-2">
      <label class="text-sm text-slate-500">Date:</label>
      <input type="date" id="datePicker" value="<?= $log_date ?>" 
             class="form-input rounded-lg bg-slate-150 py-1.5 px-3 text-sm dark:bg-navy-900/90"
             onchange="window.location.href='log_adherence.php?patient_id=<?= $pre_patient_id ?>&date='+this.value">
    </div>
  </div>

  <?php if (!empty($form_errors)): ?>
  <div class="alert mt-4 flex overflow-hidden rounded-lg bg-error/10 text-error dark:bg-error/15">
    <div class="flex flex-1 items-center p-4 text-sm"><?= implode('<br>', $form_errors) ?></div>
    <div class="w-1.5 bg-error"></div>
  </div>
  <?php endif; ?>

  <!-- Patient Selector -->
  <?php if (!$patient_info): ?>
  <div class="card mt-5 p-6">
    <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">Select Patient</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <?php foreach ($all_treatment_patients as $tp): ?>
      <a href="log_adherence.php?patient_id=<?= $tp['id'] ?>&date=<?= $log_date ?>"
         class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 transition-colors hover:border-primary hover:bg-primary/5 dark:border-navy-600 dark:hover:border-accent dark:hover:bg-accent/5">
        <div class="avatar size-10">
          <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
            <?= strtoupper(substr($tp['full_name'], 0, 2)) ?>
          </div>
        </div>
        <div>
          <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($tp['full_name']) ?></span>
          <p class="text-xs text-slate-400"><?= htmlspecialchars($tp['patient_code']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($all_treatment_patients)): ?>
      <div class="col-span-full text-center text-slate-400 py-4">No patients on treatment at your facility.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <!-- Dose Logging Form -->
  <div class="mt-4 flex items-center gap-3">
    <a href="log_adherence.php?date=<?= $log_date ?>" class="btn size-9 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
      <svg class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
      <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient_info['full_name']) ?></h2>
      <p class="text-xs text-slate-400"><?= htmlspecialchars($patient_info['patient_code']) ?> — <?= $log_date ?></p>
    </div>
  </div>

  <?php if (empty($doses)): ?>
  <div class="card mt-4 p-8 text-center text-slate-400">No scheduled doses for this date.</div>
  <?php else: ?>
  <form method="POST" class="mt-4 space-y-3">
    <input type="hidden" name="batch_log" value="1">
    <input type="hidden" name="patient_id" value="<?= $pre_patient_id ?>">

    <?php 
    $current_time_slot = null;
    foreach ($doses as $i => $dose):
      if ($dose['dose_time'] !== $current_time_slot):
        $current_time_slot = $dose['dose_time'];
        $slot_doses = array_filter($doses, fn($d) => $d['dose_time'] === $current_time_slot);
        $slot_logged = count(array_filter($slot_doses, fn($d) => $d['log_status'] !== null));
        $slot_total = count($slot_doses);
    ?>
    <div class="card">
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 dark:bg-navy-800 rounded-t-lg">
        <span class="text-sm font-semibold text-slate-700 dark:text-navy-100"><?= $current_time_slot ?></span>
        <span class="text-xs text-slate-500"><?= $slot_logged ?>/<?= $slot_total ?> logged</span>
      </div>
      <div class="divide-y divide-slate-100 dark:divide-navy-600">
    <?php endif; ?>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
          <!-- Drug Info -->
          <div class="flex-1 min-w-0">
            <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($dose['drug_name']) ?></span>
            <span class="ml-2 text-xs text-slate-400"><?= $dose['drug_code'] ?> — <?= $dose['default_dose_mg'] ?> <?= $dose['unit'] ?></span>
          </div>

          <?php if ($dose['log_status']): ?>
          <!-- Already Logged -->
          <div class="flex items-center gap-3">
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium 
              <?= $dose['log_status'] === 'taken' ? 'bg-success/10 text-success' : ($dose['log_status'] === 'late' ? 'bg-warning/10 text-warning' : 'bg-error/10 text-error') ?>">
              <?= ucfirst($dose['log_status']) ?>
              <?php if ($dose['actual_time']): ?> (<?= $dose['actual_time'] ?>)<?php endif; ?>
            </span>
            <?php if ($dose['log_notes']): ?>
            <span class="text-xs text-slate-400" title="<?= htmlspecialchars($dose['log_notes']) ?>"><?= htmlspecialchars(mb_substr($dose['log_notes'], 0, 30)) ?>...</span>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <!-- Logging Controls -->
          <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-slate-200 dark:border-navy-600 overflow-hidden">
              <label class="flex items-center gap-1 px-3 py-1.5 text-xs cursor-pointer hover:bg-success/10 transition-colors
                <?= isset($_POST['doses'][$dose['id']]['status']) && $_POST['doses'][$dose['id']]['status'] === 'taken' ? 'bg-success/10 text-success' : 'text-slate-500' ?>">
                <input type="radio" name="doses[<?= $dose['id'] ?>][status]" value="taken" checked class="hidden">
                <svg class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Taken
              </label>
              <label class="flex items-center gap-1 px-3 py-1.5 text-xs cursor-pointer hover:bg-warning/10 transition-colors border-l border-slate-200 dark:border-navy-600
                <?= (isset($_POST['doses'][$dose['id']]['status']) ? $_POST['doses'][$dose['id']]['status'] === 'late' : false) ? 'bg-warning/10 text-warning' : 'text-slate-500' ?>">
                <input type="radio" name="doses[<?= $dose['id'] ?>][status]" value="late" class="hidden">
                <svg class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Late
              </label>
              <label class="flex items-center gap-1 px-3 py-1.5 text-xs cursor-pointer hover:bg-error/10 transition-colors border-l border-slate-200 dark:border-navy-600
                <?= (isset($_POST['doses'][$dose['id']]['status']) ? $_POST['doses'][$dose['id']]['status'] === 'missed' : false) ? 'bg-error/10 text-error' : 'text-slate-500' ?>">
                <input type="radio" name="doses[<?= $dose['id'] ?>][status]" value="missed" class="hidden">
                <svg class="size-3.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Missed
              </label>
            </div>
            <input type="time" name="doses[<?= $dose['id'] ?>][actual_time]" value="<?= date('H:i') ?>" 
                   class="form-input rounded-lg bg-slate-150 py-1 px-2 text-xs dark:bg-navy-900/90 w-24"
                   title="Actual time taken">
            <input type="text" name="doses[<?= $dose['id'] ?>][notes]" placeholder="Notes..."
                   class="form-input rounded-lg bg-slate-150 py-1 px-2 text-xs dark:bg-navy-900/90 w-32"
                   title="Optional notes">
          </div>
          <?php endif; ?>
        </div>

    <?php 
    $next_dose = $doses[$i + 1] ?? null;
    if (!$next_dose || $next_dose['dose_time'] !== $current_time_slot):
    ?>
      </div>
    </div>
    <?php endif; endforeach; ?>

    <?php 
    $unlogged = count(array_filter($doses, fn($d) => !$d['log_status']));
    if ($unlogged > 0):
    ?>
    <div class="flex justify-end pt-2">
      <button type="submit" class="btn h-10 bg-success text-white hover:bg-success/90 px-8">
        <svg class="mr-1 size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        Save All (<?= $unlogged ?> dose<?= $unlogged > 1 ? 's' : '' ?>)
      </button>
    </div>
    <?php endif; ?>
  </form>
  <?php endif; ?>
  <?php endif; ?>
</main>

<?php $notify_variant = 'success'; require_once 'nurse_footer.php'; ?>