<?php
session_start();
 $required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

 $pageTitle = 'Log Adherence - GxAlert';
 $form_errors = [];
 $old = [];

$patient_id = (int)($_GET['patient_id'] ?? 0);
$log_date = $_GET['date'] ?? date('Y-m-d');

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $log_date)) { $log_date = date('Y-m-d'); }
if ($log_date > date('Y-m-d')) { $log_date = date('Y-m-d'); }

// If no patient_id, show patient selector
if ($patient_id === 0) {
    // 1. Fetch patients (Cleaned up with close)
    $pt_stmt = $conn->prepare("
        SELECT p.id, p.full_name, p.patient_code 
        FROM patients p 
        JOIN treatment_regimens tr ON p.id = tr.patient_id AND tr.status = 'active'
        WHERE p.created_by = ? AND p.is_active = 1 AND p.treatment_status = 'on_treatment'
        ORDER BY p.full_name
    ");
    $pt_stmt->bind_param("i", $clinician_id);
    $pt_stmt->execute();
    $treatment_patients = $pt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pt_stmt->close();
} else {
    // 2. Verify patient (Cleaned up with close)
    $p_check = $conn->prepare("SELECT id, full_name, patient_code FROM patients WHERE id = ? AND created_by = ? AND is_active = 1");
    $p_check->bind_param("ii", $patient_id, $clinician_id);
    $p_check->execute();
    $p_res = $p_check->get_result();
    if ($p_res->num_rows !== 1) { $p_check->close(); header("Location: adherence_log.php"); exit; }
    $patient = $p_res->fetch_assoc();
    $p_check->close();

    // 3. Fetch scheduled doses for this date
    $sched_stmt = $conn->prepare("
        SELECT 
            ms.id AS schedule_id, 
            ms.dose_time, 
            d.drug_name, 
            d.drug_code,
            (SELECT dose_mg FROM regimen_drugs WHERE regimen_id = ms.regimen_id AND drug_id = ms.drug_id LIMIT 1) as dose_mg,
            (SELECT al.status FROM adherence_logs al 
             WHERE al.patient_id = ? AND al.schedule_id = ms.id AND al.dose_date = ?
             LIMIT 1) AS existing_status
        FROM medication_schedule ms
        JOIN drugs d ON ms.drug_id = d.id
        JOIN treatment_regimens tr ON ms.regimen_id = tr.id
        WHERE tr.patient_id = ? 
          AND tr.status = 'active' 
          AND ms.is_active = 1
        ORDER BY ms.dose_time ASC
    ");
    
    // We only need 3 parameters now: patient_id (for subquery), log_date, and patient_id (for WHERE)
    $sched_stmt->bind_param("isi", $patient_id, $log_date, $patient_id);
    $sched_stmt->execute();
    $schedules = $sched_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $sched_stmt->close();

    $missed_reasons = [
        'forgot' => 'Patient forgot', 'side_effects' => 'Side effects', 'stockout' => 'Stockout',
        'away_from_home' => 'Traveling', 'too_ill' => 'Too ill', 'refused' => 'Refused', 'other' => 'Other',
    ];
}


// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id > 0) {
    $old = $_POST;
    $statuses = $_POST['status'] ?? [];
    $times    = $_POST['actual_time'] ?? [];
    $reasons  = $_POST['missed_reason'] ?? [];
    $schedule_ids = $_POST['schedule_ids'] ?? [];

    $conn->begin_transaction();
    try {
        $ins_log = $conn->prepare("
            INSERT INTO adherence_logs (patient_id, reminder_id, schedule_id, dose_date, status, actual_time_taken, verification_method, missed_reason, verified_by, created_at)
            VALUES (?, NULL, ?, ?, ?, ?, 'dot', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                actual_time_taken = VALUES(actual_time_taken),
                verification_method = 'dot',
                missed_reason = VALUES(missed_reason),
                verified_by = VALUES(verified_by)
        ");

        foreach ($schedule_ids as $sid) {
            $sid = (int)$sid;
            $status = $statuses[$sid] ?? null;
            
            if (!in_array($status, ['taken', 'late', 'missed'])) continue;

            $actual_time = ($status === 'taken' || $status === 'late') ? ($times[$sid] ?? null) : null;
            $reason = ($status === 'missed') ? ($reasons[$sid] ?? null) : null;

            $ins_log->bind_param("iissssii",
                $patient_id,
                $sid,
                $log_date,
                $status,
                $actual_time,
                $reason,
                $clinician_id
            );
            $ins_log->execute();
        }

        // Audit summary
        $aud = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, 'INSERT', 'adherence_logs', ?, ?, ?)");
        $aud->bind_param("iisis", $clinician_id, $patient_id, json_encode($_POST), $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $aud->execute();
        $aud->close();

        $conn->commit();
        header("Location: adherence_log.php?patient_id=$patient_id&date=$log_date&status=adherence_saved");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Adherence log error: " . $e->getMessage());
        $form_errors['db'] = 'Failed to save adherence records.';
    }
}

function statusRadio($name, $value, $current, $color) {
    $checked = $current === $value ? 'checked' : '';
    $bg = $current === $value ? "bg-$color/10 border-$color text-$color" : "border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-300 dark:hover:bg-navy-700";
    return "<label class=\"flex-1 flex items-center justify-center rounded-lg border-2 py-2 cursor-pointer text-xs font-semibold transition-colors $bg\">
        <input type=\"radio\" name=\"status[$name]\" value=\"$value\" $checked class=\"sr-only peer\">
        " . ucfirst($value) . "
    </label>";
}
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl">

  <?php if ($patient_id === 0): ?>
  <!-- Patient Selector View -->
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-5">Log Adherence</h1>
  
  <?php if (empty($treatment_patients)): ?>
  <div class="card p-12 text-center mt-5">
    <svg class="mx-auto size-16 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
    <h3 class="mt-4 text-lg font-medium text-slate-600 dark:text-navy-200">No Patients on Treatment</h3>
    <p class="mt-2 text-sm text-slate-400 dark:text-navy-300">You don't have any patients with active treatment regimens.</p>
  </div>
  <?php else: ?>
  <div class="card mt-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
      <h2 class="font-medium text-sm text-slate-700 dark:text-navy-100">Select Patient</h2>
      <p class="text-xs text-slate-400 dark:text-navy-300">Choose a patient to record their daily medication intake.</p>
    </div>
    <div class="divide-y divide-slate-450 dark:divide-navy-600">
      <?php foreach ($treatment_patients as $tp): ?>
      <a href="adherence_log.php?patient_id=<?= $tp['id'] ?>&date=<?= date('Y-m-d') ?>" 
         class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-navy-800 transition-colors">
        <div>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($tp['full_name']) ?></p>
          <p class="text-[10px] font-mono text-slate-400 dark:text-navy-300"><?= $tp['patient_code'] ?></p>
        </div>
        <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <!-- Adherence Logging View -->
  
  <?php if (isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4 mt-5"><p class="text-sm text-error"><?= $form_errors['db'] ?></p></div>
  <?php endif; ?>

  <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="adherence_log.php" class="hover:text-primary dark:hover:text-accent-light">Log Adherence</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['full_name']) ?></span>
  </nav>

  <!-- Header with Date Picker -->
  <div class="card mb-5 mt-5">
    <div class="flex flex-wrap items-center justify-between gap-3 p-4">
      <div>
        <h1 class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['full_name']) ?></h1>
        <p class="text-xs text-slate-400 dark:text-navy-300"><?= $patient['patient_code'] ?></p>
      </div>
      <div class="flex items-center space-x-2">
        <a href="?patient_id=<?= $patient_id ?>&date=<?= date('Y-m-d', strtotime($log_date . ' -1 day')) ?>" 
           class="btn size-8 rounded-lg p-0 bg-slate-100 hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <input type="date" name="log_date" value="<?= $log_date ?>" max="<?= date('Y-m-d') ?>"
               class="form-input w-40 rounded-lg bg-slate-150 px-3 py-1.5 text-sm font-medium text-center ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"
               onchange="window.location.href='?patient_id=<?= $patient_id ?>&date='+this.value">
        <a href="?patient_id=<?= $patient_id ?>&date=<?= date('Y-m-d', strtotime($log_date . ' +1 day')) ?>"
           class="btn size-8 rounded-lg p-0 bg-slate-100 hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600 <?= $log_date >= date('Y-m-d') ? 'opacity-30 pointer-events-none' : '' ?>">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
        </a>
      </div>
    </div>
  </div>

  <form method="POST" action="">
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    
    <?php if (empty($schedules)): ?>
    <div class="card p-8 text-center mt-5">
      <svg class="mx-auto size-12 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
      <h3 class="mt-3 font-medium text-slate-600 dark:text-navy-200">No Medications Scheduled</h3>
      <p class="mt-1 text-sm text-slate-400 dark:text-navy-300">No doses are scheduled for <?= date('l, F j, Y', strtotime($log_date)) ?>.</p>
    </div>

    <?php else: ?>
    
    <div class="space-y-3">

      <?php foreach ($schedules as $idx => $sched):
          $sid = $sched['schedule_id'];
          $existing = $sched['existing_status'];
          $is_past = $log_date < date('Y-m-d');
          
          // Group doses by time
          $time_label = date('h:i A', strtotime($sched['dose_time']));
      ?>
      
      <div class="card p-4 border-l-4 mt-5 
        <?= $existing === 'taken' ? 'border-l-success' : ($existing === 'late' ? 'border-l-warning' : ($existing === 'missed' ? 'border-l-error' : 'border-l-slate-300 dark:border-navy-600')) ?>">
        
        <input type="hidden" name="schedule_ids[]" value="<?= $sid ?>">
        
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
          <div class="flex items-center space-x-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
              <svg class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($sched['drug_name']) ?></p>
              <p class="text-xs text-slate-400 dark:text-navy-300"><?= $sched['drug_code'] ?> · <?= $sched['dose_mg'] ?>mg · Scheduled: <?= $time_label ?></p>
            </div>
          </div>
          
          <?php if ($existing): ?>
          <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase 
            <?= $existing === 'taken' ? 'bg-success/10 text-success' : ($existing === 'late' ? 'bg-warning/10 text-warning' : 'bg-error/10 text-error') ?>">
            Already Logged: <?= ucfirst($existing) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Status Radios -->
        <div class="flex gap-2 mb-3" x-data="{ status: '<?= $existing ?? '' ?>' }">
          <label class="flex-1 flex items-center justify-center rounded-lg border-2 py-2 cursor-pointer text-xs font-semibold transition-colors
            <?= ($old['status'][$sid] ?? $existing) === 'taken' ? 'border-success bg-success/10 text-success' : 'border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-300 dark:hover:bg-navy-700' ?>">
            <input type="radio" name="status[<?= $sid ?>]" value="taken" class="sr-only" @click="status='taken'" <?= ($old['status'][$sid] ?? $existing) === 'taken' ? 'checked' : '' ?>>
            <svg class="size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg> Taken
          </label>
          <label class="flex-1 flex items-center justify-center rounded-lg border-2 py-2 cursor-pointer text-xs font-semibold transition-colors
            <?= ($old['status'][$sid] ?? $existing) === 'late' ? 'border-warning bg-warning/10 text-warning' : 'border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-300 dark:hover:bg-navy-700' ?>">
            <input type="radio" name="status[<?= $sid ?>]" value="late" class="sr-only" @click="status='late'" <?= ($old['status'][$sid] ?? $existing) === 'late' ? 'checked' : '' ?>>
            <svg class="size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01" /></svg> Late
          </label>
          <label class="flex-1 flex items-center justify-center rounded-lg border-2 py-2 cursor-pointer text-xs font-semibold transition-colors
            <?= ($old['status'][$sid] ?? $existing) === 'missed' ? 'border-error bg-error/10 text-error' : 'border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-navy-600 dark:text-navy-300 dark:hover:bg-navy-700' ?>">
            <input type="radio" name="status[<?= $sid ?>]" value="missed" class="sr-only" @click="status='missed'" <?= ($old['status'][$sid] ?? $existing) === 'missed' ? 'checked' : '' ?>>
            <svg class="size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg> Missed
          </label>
        </div>

        <!-- Conditional Fields -->
        <div class="pl-2 hidden" :class="(status === 'late' || status === 'missed') ? '!block' : ''">
          
          <!-- Time taken (if late) -->
          <div x-show="status === 'late'" class="mb-2 max-w-xs">
            <label class="block text-[10px] text-slate-400 dark:text-navy-300 mb-0.5">Actual Time Taken</label>
            <input type="time" name="actual_time[<?= $sid ?>]" value="<?= $old['actual_time'][$sid] ?? date('H:i') ?>"
                   class="form-input w-full rounded bg-slate-100 px-2 py-1 text-xs font-mono dark:bg-navy-800">
          </div>

          <!-- Missed reason (if missed) -->
          <div x-show="status === 'missed'" class="max-w-xs">
            <label class="block text-[10px] text-slate-400 dark:text-navy-300 mb-0.5">Reason for Missing Dose</label>
            <select name="missed_reason[<?= $sid ?>]" class="form-input w-full rounded bg-slate-100 px-2 py-1 text-xs dark:bg-navy-800">
              <option value="">Select reason...</option>
              <?php foreach ($missed_reasons as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($old['missed_reason'][$sid] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

    <!-- Submit -->
    <div class="mt-5 flex gap-3">
      <button type="submit" class="btn h-10 bg-success px-6 font-medium text-white hover:bg-success/90">
        <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
        Save Adherence for <?= date('M j', strtotime($log_date)) ?>
      </button>
      <a href="viewpatient.php?id=<?= $patient_id ?>" class="btn h-10 bg-slate-100 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Back to Patient</a>
    </div>

    <?php endif; ?>
  </form>

  <?php endif; ?>
</div>

<?php
 $notify_text = 'adherence_saved';
 $notify_variant = 'success';
require_once 'clinician_footer.php'; ?>