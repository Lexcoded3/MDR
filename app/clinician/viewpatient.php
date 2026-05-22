<?php
session_start();
$required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

$pageTitle = 'Patient View - GxAlert';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header("Location: patients.php"); exit; }

// 1. Fetch patient
$stmt = $conn->prepare("SELECT p.*, f.name AS facility_name FROM patients p LEFT JOIN facilities f ON p.facility_id = f.id WHERE p.id = ? AND p.is_active = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) { header("Location: patients.php"); exit; }

// 2. Active regimen — no correlated subquery to avoid connection state issues
$regimen = null;
$regimen_drugs_list = [];
$r_stmt = $conn->prepare("SELECT * FROM treatment_regimens WHERE patient_id = ? AND status = 'active' LIMIT 1");
$r_stmt->bind_param("i", $id);
$r_stmt->execute();
$regimen = $r_stmt->get_result()->fetch_assoc() ?: null;
$r_stmt->close();

// 3. Regimen drugs
if ($regimen) {
    $d_stmt = $conn->prepare("SELECT d.drug_code, d.drug_group FROM regimen_drugs rd JOIN drugs d ON rd.drug_id = d.id WHERE rd.regimen_id = ? AND rd.is_active = 1");
    $d_stmt->bind_param("i", $regimen['id']);
    $d_stmt->execute();
    $regimen_drugs_list = $d_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $d_stmt->close();
}

// 4. Adherence
$adh_total = $adh_taken = $adh_late = $adh_missed = 0;
$a_stmt = $conn->prepare("SELECT COUNT(*) AS t, COALESCE(SUM(status='taken'),0) AS taken, COALESCE(SUM(status='late'),0) AS late, COALESCE(SUM(status='missed'),0) AS missed FROM adherence_logs WHERE patient_id = ? AND dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($a_stmt) {
    $a_stmt->bind_param("i", $id);
    $a_stmt->execute();
    $adh = $a_stmt->get_result()->fetch_assoc();
    $a_stmt->close();
    $adh_total  = (int)($adh['t'] ?? 0);
    $adh_taken  = (int)($adh['taken'] ?? 0);
    $adh_late   = (int)($adh['late'] ?? 0);
    $adh_missed = (int)($adh['missed'] ?? 0);
}

$adh_pct   = $adh_total > 0 ? round((($adh_taken + $adh_late) / $adh_total) * 100, 1) : 0;
$adh_color = $adh_pct >= 95 ? 'success' : ($adh_pct >= 85 ? 'warning' : 'error');

$treatment_days = 0;
if (!empty($regimen['start_date'])) {
    try {
        $treatment_days = (new DateTime($regimen['start_date']))->diff(new DateTime('today'))->days;
    } catch (Exception $e) { $treatment_days = 0; }
}

// 5. Active AEs
$active_ae = [];
$ae_stmt = $conn->prepare("SELECT ae.*, d.drug_code FROM adverse_events ae LEFT JOIN drugs d ON ae.drug_id = d.id WHERE ae.patient_id = ? AND ae.resolution_date IS NULL ORDER BY ae.onset_date DESC");
if ($ae_stmt) {
    $ae_stmt->bind_param("i", $id);
    $ae_stmt->execute();
    $active_ae = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ae_stmt->close();
}

// 6. Upcoming appointments
$upcoming = [];
$appt_stmt = $conn->prepare("SELECT * FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status = 'pending' ORDER BY appointment_date ASC LIMIT 5");
if ($appt_stmt) {
    $appt_stmt->bind_param("i", $id);
    $appt_stmt->execute();
    $upcoming = $appt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $appt_stmt->close();
}

$age = $patient['date_of_birth'] ? (new DateTime('today'))->diff(new DateTime($patient['date_of_birth']))->y : null;
$temp_creds = $_SESSION['temp_credentials'] ?? null;
unset($_SESSION['temp_credentials']);

function statusBadge($s) {
    $m = ['enrolled'=>'bg-info/10 text-info','on_treatment'=>'bg-primary/10 text-primary dark:text-accent-light','completed'=>'bg-success/10 text-success','cured'=>'bg-success/10 text-success','failed'=>'bg-error/10 text-error','died'=>'bg-slate-400/10 text-slate-500 dark:text-navy-300','lost_to_followup'=>'bg-warning/10 text-warning','transferred_out'=>'bg-secondary/10 text-secondary'];
    return "<span class=\"rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase " . ($m[$s] ?? 'bg-slate-200 text-slate-600') . "\">" . str_replace('_',' ',ucfirst($s)) . "</span>";
}
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <!-- <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="patients.php" class="hover:text-primary dark:hover:text-accent-light">Patients</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100"></span>
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
              <a class="text-primary transition-colors hover:text-primary-focus dark:text-accent-light dark:hover:text-accent" href="#"><?= htmlspecialchars($patient['full_name']) ?></a>
              <svg x-ignore="" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </li>
          </ul>
        </div>

  <!-- Temp Credentials Alert -->
  <?php if ($temp_creds): ?>
  <div class="card border-l-4 border-l-warning bg-warning/5 p-4 mb-4">
    <p class="text-sm font-semibold text-warning mb-1">Patient Login Credentials Generated</p>
    <p class="text-xs text-slate-600 dark:text-navy-200">
      <strong>Email:</strong> <?= htmlspecialchars($temp_creds['email']) ?><br>
      <strong>Password:</strong> <code class="bg-slate-200 dark:bg-navy-700 px-1.5 py-0.5 rounded"><?= htmlspecialchars($temp_creds['password']) ?></code>
    </p>
    <p class="text-[10px] text-slate-400 dark:text-navy-300 mt-1">Share this securely with the patient. This password will not be shown again.</p>
  </div>
  <?php endif; ?>

  <!-- Patient Header -->
  <div class="card mb-4 mt-5">
    <div class="card-body p-5">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start space-x-4">
          <div class="flex size-14 items-center justify-center rounded-full bg-primary/10 dark:bg-accent/10">
            <svg class="size-7 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['full_name']) ?></h3>
            <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-slate-400 dark:text-navy-300">
              <code><?= htmlspecialchars($patient['patient_code']) ?></code>
              <span><?= $age ? "$age yrs" : '' ?>/<?= ucfirst($patient['gender']) ?></span>
              <span><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></span>
              <span><?= htmlspecialchars($patient['facility_name'] ?? 'N/A') ?></span>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
              <?= statusBadge($patient['treatment_status'] ?? 'enrolled') ?>
              <?php if ($patient['hiv_status'] === 'positive'): ?><span class="rounded-full bg-warning/10 px-2.5 py-0.5 text-[10px] font-semibold text-warning">HIV+ <?= $patient['on_art'] ? '· On ART' : '' ?></span><?php endif; ?>
              <?php if ($patient['mdr_confirmation'] === 'confirmed'): ?><span class="rounded-full bg-error/10 px-2.5 py-0.5 text-[10px] font-semibold text-error">TB Confirmed</span><?php else: ?><span class="rounded-full bg-warning/10 px-2.5 py-0.5 text-[10px] font-semibold text-warning">TB Presumed</span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="flex gap-2">
          <a href="assign_regimen.php?id=<?= $id ?>" class="btn bg-warning px-3 py-2 text-xs font-medium text-white hover:bg-warning/90">
            <svg class="inline size-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5" /></svg>
            Manage Regimen
          </a>
          <a href="adherence_log.php?patient_id=<?= $id ?>" class="btn bg-success px-3 py-2 text-xs font-medium text-white hover:bg-success/90">
            <svg class="inline size-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            Log Adherence
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-5 mt-5">
    <div class="card border-t-4 border-t-<?= $adh_color ?> p-4 text-center">
      <div class="text-2xl font-bold text-<?= $adh_color ?>"><?= $adh_pct ?>%</div>
      <p class="text-xs text-slate-400 dark:text-navy-300">30-Day Adherence</p>
      <div class="flex justify-around mt-2 text-[10px]">
        <span class="text-success"><?= $adh_taken ?> ✓</span>
        <span class="text-warning"><?= $adh_late ?> ⏰</span>
        <span class="text-error"><?= $adh_missed ?> ✗</span>
      </div>
    </div>
    <div class="card border-t-4 border-t-primary p-4 text-center">
      <div class="text-2xl font-bold text-primary dark:text-accent-light"><?= $treatment_days ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">Days on Treatment</p>
    </div>
    <div class="card border-t-4 border-t-<?= count($active_ae) > 0 ? 'error' : 'success' ?> p-4 text-center">
      <div class="text-2xl font-bold text-<?= count($active_ae) > 0 ? 'error' : 'success' ?>"><?= count($active_ae) ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">Active AEs</p>
    </div>
    <div class="card border-t-4 border-t-info p-4 text-center">
      <div class="text-2xl font-bold text-info"><?= count($upcoming) ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">Upcoming Visits</p>
    </div>
  </div>

  <div class="grid grid-cols-12 gap-4 lg:gap-6">
    <!-- Left: Clinical Details -->
    <div class="col-span-12 lg:col-span-5">
      <div class="card mb-4 mt-5">
        <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
          <h2 class="font-medium text-sm text-slate-700 dark:text-navy-100">Clinical Details</h2>
        </div>
        <div class="divide-y divide-slate-150 dark:divide-navy-600">
          <?php
          $rows = [
              ['Case Classification', str_replace('_',' ',ucfirst($patient['tb_case_classification'] ?? 'N/A'))],
              ['TB Confirmation', ucfirst($patient['mdr_confirmation'] ?? 'N/A')],
              ['HIV Status', ucfirst($patient['hiv_status'] ?? 'N/A')],
              ['Date of Diagnosis', $patient['date_of_diagnosis'] ?? 'N/A'],
              ['Enrollment Date', $patient['enrollment_date'] ?? 'N/A'],
              ['Weight', $patient['weight_kg'] ? $patient['weight_kg'] . ' kg' : 'N/A'],
              ['National ID', htmlspecialchars($patient['national_id'] ?? 'N/A')],
              ['Address', htmlspecialchars($patient['address'] ?? 'N/A')],
              ['Next of Kin', htmlspecialchars($patient['next_of_kin'] ?? 'N/A') . ($patient['next_of_kin_contact'] ? ' (' . htmlspecialchars($patient['next_of_kin_contact']) . ')' : '')],
          ];
          foreach ($rows as $r): ?>
          <div class="flex justify-between px-5 py-2.5 text-xs">
            <span class="text-slate-400 dark:text-navy-300"><?= $r[0] ?></span>
            <span class="text-right text-slate-700 dark:text-navy-100 max-w-[60%]"><?= $r[1] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Right: Active AEs + Appointments -->
    <div class="col-span-12 lg:col-span-4">
      <?php if (!empty($active_ae)): ?>
      <div class="card mb-4 mt-5">
        <div class="border-b border-error/20 bg-error/5 px-5 py-3 flex items-center justify-between">
          <h2 class="font-medium text-sm text-error">Active Adverse Events (<?= count($active_ae) ?>)</h2>
        </div>
        <div class="divide-y divide-slate-150 dark:divide-navy-600">
          <?php foreach ($active_ae as $ae):
              $sev_cls = ['mild'=>'text-warning','moderate'=>'text-orange-500','severe'=>'text-error','life_threatening'=>'text-error font-bold'][$ae['severity']] ?? 'text-slate-500';
          ?>
          <div class="flex items-start justify-between px-5 py-3">
            <div>
              <p class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']) ?></p>
              <p class="text-[10px] text-slate-400 dark:text-navy-300"><?= $ae['drug_code'] ?? 'Unspecified' ?> · Since <?= $ae['onset_date'] ?></p>
            </div>
            <div class="text-right">
              <span class="text-xs font-semibold uppercase <?= $sev_cls ?>"><?= str_replace('_',' ',$ae['severity']) ?></span>
              <?php if ($ae['action_taken']): ?>
              <p class="text-[10px] text-slate-400 dark:text-navy-300 mt-0.5"><?= str_replace('_', ' ', ucfirst($ae['action_taken'])) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      
    </div>
    <div class="col-span-12 lg:col-span-3">
      <!-- Current Regimen -->
      <div class="card mt-5">
        <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3 flex items-center justify-between">
          <h2 class="font-medium text-sm text-slate-700 dark:text-navy-100">Current Regimen</h2>
          <a href="assign_regimen.php?id=<?= $id ?>" class="text-xs text-primary dark:text-accent-light">Manage →</a>
        </div>
        <div class="p-5">
          <?php if ($regimen): ?>
          <p class="font-semibold text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($regimen['regimen_name'] ?? 'Individualized Regimen') ?></p>
          <div class="flex flex-wrap gap-1 mt-2">
            <?php foreach ($regimen_drugs_list as $d): 
                $gc = [
                    'group_a' => 'bg-error/10 text-error',
                    'group_b' => 'bg-warning/10 text-warning',
                    'group_c' => 'bg-info/10 text-info',
                    'group_d1'=> 'bg-secondary/10 text-secondary',
                    'group_d2'=> 'bg-slate-300/10 text-slate-500 dark:text-navy-300'
                ][$d['drug_group']] ?? 'bg-slate-200 text-slate-600';
            ?>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?= $gc ?>"><?= htmlspecialchars($d['drug_code']) ?></span>
            <?php endforeach; ?>
        </div>
          <p class="text-[10px] text-slate-400 dark:text-navy-300 mt-2">Started <?= $regimen['start_date'] ?> <?= $regimen['end_date'] ? '· End ' . $regimen['end_date'] : '' ?></p>
          <?php else: ?>
          <div class="text-center py-4 text-sm text-slate-400 dark:text-navy-300">
            <p>No active regimen</p>
            <a href="assign_regimen.php?id=<?= $id ?>" class="text-primary dark:text-accent-light text-xs mt-1 inline-block">Assign regimen →</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Appointments -->
      <div class="card mt-5">
        <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3 flex items-center justify-between">
          <h2 class="font-medium text-sm text-slate-700 dark:text-navy-100">Appointments</h2>
        </div>
        <?php if (!empty($upcoming)): ?>
        <div class="divide-y divide-slate-150 dark:divide-navy-600">
          <?php foreach ($upcoming as $a): ?>
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= date('M d, Y — h:i A', strtotime($a['appointment_date'])) ?></p>
              <p class="text-[10px] text-slate-400 dark:text-navy-300"><?= str_replace('_',' ',ucfirst($a['appointment_type'] ?? 'Visit')) ?> <?= $a['purpose'] ? '· ' . htmlspecialchars($a['purpose']) : '' ?></p>
            </div>
            <span class="rounded-full bg-info/10 px-2.5 py-0.5 text-[10px] font-semibold text-info">Pending</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-sm text-slate-400 dark:text-navy-300">No upcoming appointments</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
 $notify_text = 'registered';
 $notify_variant = 'success';
require_once 'clinician_footer.php'; ?>