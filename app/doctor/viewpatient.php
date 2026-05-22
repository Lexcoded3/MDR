<?php
session_start();
$required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

$pageTitle = 'Patient View - GxAlert';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header("Location: patients.php"); exit; }

// FIX: store result once — calling get_result() twice on the same statement
// causes "Commands out of sync". Fetch into variable, then check it.
$p_stmt = $conn->prepare("
    SELECT p.*, f.name AS facility_name
    FROM patients p
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE p.id = ? AND p.is_active = 1
");
$p_stmt->bind_param("i", $id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();
$p_stmt->close();

if (!$patient) { header("Location: patients.php"); exit; }

$age = $patient['date_of_birth']
    ? (new DateTime('today'))->diff(new DateTime($patient['date_of_birth']))->y
    : null;

// Active regimen
$reg_stmt = $conn->prepare("
    SELECT tr.*, u.name AS assigned_by_name, rv.name AS reviewer_name,
           GROUP_CONCAT(CONCAT(d.drug_code, ' ', rd.dose_mg, 'mg') SEPARATOR ', ') AS drug_summary
    FROM treatment_regimens tr
    LEFT JOIN users u  ON tr.prescribed_by  = u.id
    LEFT JOIN users rv ON tr.reviewed_by  = rv.id
    LEFT JOIN regimen_drugs rd ON tr.id = rd.regimen_id AND rd.is_active = 1
    LEFT JOIN drugs d ON rd.drug_id = d.id
    WHERE tr.patient_id = ?
    GROUP BY tr.id
    ORDER BY tr.created_at DESC
    LIMIT 1
");
$reg_stmt->bind_param("i", $id);
$reg_stmt->execute();
$reg = $reg_stmt->get_result()->fetch_assoc();
$reg_stmt->close();

// Adherence trend — last 30 days
$adh_stmt = $conn->prepare("
    SELECT dose_date,
           COUNT(*) AS total,
           SUM(status = 'taken')  AS taken,
           SUM(status = 'late')   AS late,
           SUM(status = 'missed') AS missed,
           ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS pct
    FROM adherence_logs
    WHERE patient_id = ?
      AND dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY dose_date
    ORDER BY dose_date
");
$adh_stmt->bind_param("i", $id);
$adh_stmt->execute();
$trend = $adh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$adh_stmt->close();

// Active adverse events
$ae_stmt = $conn->prepare("
    SELECT ae.*, d.drug_name AS suspected_drug_name
    FROM adverse_events ae
    LEFT JOIN drugs d ON ae.suspected_drug_id = d.id
    WHERE ae.patient_id = ? AND ae.resolution_date IS NULL
    ORDER BY ae.onset_date DESC
");
$ae_stmt->bind_param("i", $id);
$ae_stmt->execute();
$aes = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ae_stmt->close();

// Latest lab results
$lab_stmt = $conn->prepare("
    SELECT * FROM lab_results
    WHERE patient_id = ?
    ORDER BY result_date DESC
    LIMIT 5
");
$lab_stmt->bind_param("i", $id);
$lab_stmt->execute();
$labs = $lab_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lab_stmt->close();

$severity_colors = [
    'mild'             => 'bg-info/10 text-info',
    'moderate'         => 'bg-warning/10 text-warning',
    'severe'           => 'bg-error/10 text-error',
    'life_threatening' => 'bg-error text-white',
];
$status_labels = [
    'pending_review' => 'Pending Review',
    'active'         => 'Active',
    'rejected'       => 'Rejected',
    'discontinued'   => 'Discontinued',
    'completed'      => 'Completed',
];
$status_colors = [
    'pending_review' => 'bg-warning/10 text-warning',
    'active'         => 'bg-success/10 text-success',
    'rejected'       => 'bg-error/10 text-error',
    'discontinued'   => 'bg-slate-100 text-slate-500',
    'completed'      => 'bg-primary/10 text-primary',
];
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">

  <!-- Page Header -->
  <div class="mt-4 flex items-center gap-3">
    <a href="patients.php" class="btn size-9 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
      <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['full_name']) ?></h1>
      <p class="text-sm text-slate-400 dark:text-navy-300">
        <?= htmlspecialchars($patient['patient_code']) ?>
        · <?= ucfirst(htmlspecialchars($patient['gender'])) ?>
        <?= $age ? "· {$age} yrs" : '' ?>
        · <?= htmlspecialchars($patient['weight_kg']) ?>kg
        · <?= htmlspecialchars($patient['facility_name'] ?? '') ?>
      </p>
    </div>
    <?php if ($patient['hiv_status'] === 'positive'): ?>
    <span class="rounded-full bg-error/10 px-2.5 py-0.5 text-xs font-medium text-error">HIV+</span>
    <?php endif; ?>
  </div>

  <div class="mt-6 grid grid-cols-12 gap-6">

    <!-- Left Column -->
    <div class="col-span-12 lg:col-span-8 space-y-6">

      <!-- Current Regimen -->
      <?php if ($reg): ?>
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-slate-700 dark:text-navy-100">Current Regimen</h2>
          <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?= $status_colors[$reg['status']] ?? '' ?>">
            <?= $status_labels[$reg['status']] ?? ucfirst(htmlspecialchars($reg['status'])) ?>
          </span>
        </div>
        <p class="text-sm text-slate-600 dark:text-navy-200">
          <span class="font-medium"><?= htmlspecialchars($reg['regimen_name']) ?></span>
          <?php if ($reg['drug_summary']): ?> — <?= htmlspecialchars($reg['drug_summary']) ?><?php endif; ?>
        </p>
        <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">
          Start: <?= htmlspecialchars($reg['start_date']) ?>
          · By <?= htmlspecialchars($reg['assigned_by_name'] ?? '') ?>
          · Reviewed by <?= htmlspecialchars($reg['reviewer_name'] ?? 'Pending') ?>
        </p>
        <?php if ($reg['notes']): ?>
        <p class="mt-2 text-xs text-slate-500 dark:text-navy-300 bg-slate-50 dark:bg-navy-800 rounded p-2">
          <?= nl2br(htmlspecialchars($reg['notes'])) ?>
        </p>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="card p-5 text-center text-slate-400 dark:text-navy-300">No active regimen assigned</div>
      <?php endif; ?>

      <!-- 30-Day Adherence Trend -->
      <div class="card p-5">
        <h2 class="font-semibold text-slate-700 dark:text-navy-100 mb-4">30-Day Adherence</h2>
        <?php if (empty($trend)): ?>
        <p class="text-sm text-slate-400 dark:text-navy-300">No adherence data for this period</p>
        <?php else: ?>
        <div class="flex items-end gap-0.5 h-24">
          <?php foreach ($trend as $t):
            $bar_color = $t['pct'] >= 95 ? 'bg-success' : ($t['pct'] >= 85 ? 'bg-warning' : 'bg-error');
          ?>
          <div class="flex-1 flex flex-col items-center gap-1">
            <div class="w-full rounded-t <?= $bar_color ?>" style="height:<?= max((float)$t['pct'], 5) ?>%"></div>
            <span class="text-[10px] text-slate-400 dark:text-navy-300"><?= date('j', strtotime($t['dose_date'])) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 flex gap-4 text-xs text-slate-400 dark:text-navy-300">
          <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-success"></span> ≥95%</span>
          <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-warning"></span> 85–94%</span>
          <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-error"></span> &lt;85%</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Lab Results -->
      <?php if (!empty($labs)): ?>
      <div class="card p-5">
        <h2 class="font-semibold text-slate-700 dark:text-navy-100 mb-3">Recent Lab Results</h2>
        <div class="space-y-2">
          <?php foreach ($labs as $l): ?>
          <div class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-navy-800 px-4 py-2.5">
            <div>
              <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($l['test_type']) ?></span>
              <span class="text-xs text-slate-400 dark:text-navy-300 ml-2"><?= date('M j, Y', strtotime($l['result_date'])) ?></span>
            </div>
            <div class="text-right">
              <p class="text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars(mb_substr($l['result'], 0, 50)) ?></p>
              <?php if (!$l['is_final']): ?>
              <span class="text-xs text-warning">Preliminary</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- Right Column -->
    <div class="col-span-12 lg:col-span-4 space-y-4">

      <!-- Quick Info -->
      <div class="card p-4 space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">Enrolled</span>
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['enrollment_date'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">Diagnosed</span>
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($patient['date_of_diagnosis'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">Classification</span>
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= ucfirst(htmlspecialchars($patient['tb_case_classification'] ?? '-')) ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">TB Confirm</span>
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= ucfirst(htmlspecialchars($patient['TB_confirmation'] ?? '-')) ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">HIV</span>
          <span class="font-medium <?= $patient['hiv_status'] === 'positive' ? 'text-error' : 'text-slate-700 dark:text-navy-100' ?>">
            <?= ucfirst(htmlspecialchars($patient['hiv_status'] ?? '-')) ?>
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400 dark:text-navy-300">On ART</span>
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= $patient['on_art'] ? 'Yes' : 'No' ?></span>
        </div>
      </div>

      <!-- Active Adverse Events -->
      <div class="card p-4">
        <h3 class="font-medium text-slate-700 dark:text-navy-100 mb-3">
          Active Side Effects <span class="text-slate-400 dark:text-navy-300 font-normal">(<?= count($aes) ?>)</span>
        </h3>
        <?php if (empty($aes)): ?>
        <p class="text-sm text-slate-400 dark:text-navy-300">None reported</p>
        <?php else: ?>
        <div class="space-y-2">
          <?php foreach ($aes as $ae): ?>
          <a href="adverse_events.php?manage=<?= (int)$ae['id'] ?>"
             class="block rounded-lg border border-slate-200 dark:border-navy-600 p-3 hover:border-primary dark:hover:border-accent transition-colors">
            <div class="flex items-start justify-between gap-2">
              <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']) ?></span>
              <span class="rounded-full px-1.5 py-0.5 text-xs whitespace-nowrap <?= $severity_colors[$ae['severity']] ?? '' ?>">
                <?= ucfirst(str_replace('_', ' ', $ae['severity'])) ?>
              </span>
            </div>
            <?php if ($ae['suspected_drug_name']): ?>
            <p class="text-xs text-error mt-1"><?= htmlspecialchars($ae['suspected_drug_name']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-400 dark:text-navy-300 mt-0.5">Since <?= date('M j', strtotime($ae['onset_date'])) ?></p>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php require_once 'doctor_footer.php'; ?>