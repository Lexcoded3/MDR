<?php
session_start();
 $required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

 $pageTitle = 'Adherence Summary - GxAlert';

 $period = $_GET['period'] ?? '7d';
 $filter_patient = (int)($_GET['patient_id'] ?? 0);

// Parse period
if (preg_match('/^(\d+)(d)$/', $period, $m)) {
    $days = (int)$m[1];
} else {
    $days = 7;
}
 $date_from = date('Y-m-d', strtotime("-$days days"));

// Facility patients
 $patients = getNursePatients($conn, $nurse_id);
 $on_treatment = array_filter($patients, fn($p) => $p['treatment_status'] === 'on_treatment');

// Adherence per patient
 $summary = [];
foreach ($on_treatment as $p) {
    $s = $conn->prepare("
        SELECT COUNT(*) AS total,
               SUM(status = 'taken') AS taken,
               SUM(status = 'late') AS late,
               SUM(status = 'missed') AS missed
        FROM adherence_logs
        WHERE patient_id = ? AND dose_date >= ?
    ");
    $s->bind_param("is", $p['id'], $date_from);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    
    $total = (int)$row['total'];
    $taken = (int)$row['taken'];
    $late  = (int)$row['late'];
    $missed= (int)$row['missed'];
    $pct   = $total > 0 ? round((($taken + $late) / $total) * 100, 1) : null;
    
    $summary[] = [
        'id'     => $p['id'],
        'name'   => $p['full_name'],
        'code'   => $p['patient_code'],
        'total'  => $total,
        'taken'  => $taken,
        'late'   => $late,
        'missed' => $missed,
        'pct'    => $pct,
    ];
}

// Sort by adherence percentage
usort($summary, fn($a, $b) => ($a['pct'] ?? 999) - ($b['pct'] ?? 999));

// Facility averages
 $fac_total = array_sum(array_column($summary, 'total'));
 $fac_taken = array_sum(array_column($summary, 'taken'));
 $fac_late  = array_sum(array_column($summary, 'late'));
 $fac_missed= array_sum(array_column($summary, 'missed'));
 $fac_pct   = $fac_total > 0 ? round((($fac_taken + $fac_late) / $fac_total) * 100, 1) : 0;

function pctClass($pct) {
    if ($pct === null) return 'bg-slate-100 text-slate-400';
    if ($pct >= 95) return 'bg-success/10 text-success';
    if ($pct >= 85) return 'bg-warning/10 text-warning';
    return 'bg-error/10 text-error';
}
?>
<?php require_once 'nurse_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Adherence Summary</h1>
    <div class="flex gap-2">
      <?php foreach (['7d' => '7 Days', '14d' => '14 Days', '30d' => '30 Days'] as $k => $v): ?>
      <a href="?period=<?= $k ?>" class="btn h-8 px-3 text-xs <?= $period === $k ? 'bg-primary text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200' ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Facility Average -->
  <div class="card mt-4 p-5">
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
      <div>
        <p class="text-xs text-slate-400">Period</p>
        <p class="mt-1 text-sm font-medium text-slate-700 dark:text-navy-100"><?= $date_from ?> — <?= date('Y-m-d') ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400">Total Doses</p>
        <p class="mt-1 text-lg font-semibold text-slate-700 dark:text-navy-100"><?= number_format($fac_total) ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400">Taken / Late</p>
        <p class="mt-1 text-lg font-semibold text-success"><?= $fac_taken ?> / <span class="text-warning"><?= $fac_late ?></span></p>
      </div>
      <div>
        <p class="text-xs text-slate-400">Missed</p>
        <p class="mt-1 text-lg font-semibold text-error"><?= $fac_missed ?></p>
      </div>
      <div>
        <p class="text-xs text-slate-400">Facility Adherence</p>
        <p class="mt-1 text-lg font-semibold <?= $fac_pct >= 95 ? 'text-success' : ($fac_pct >= 85 ? 'text-warning' : 'text-error') ?>"><?= $fac_pct ?>%</p>
      </div>
    </div>
  </div>

  <!-- Per-Patient Table -->
  <div class="card mt-4">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENT</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TOTAL</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TAKEN</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LATE</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">MISSED</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ADHERENCE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $s): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-3">
              <a href="log_adherence.php?patient_id=<?= $s['id'] ?>" class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent">
                <?= htmlspecialchars($s['name']) ?>
              </a>
              <p class="text-xs text-slate-400"><?= $s['code'] ?></p>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= $s['total'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-success"><?= $s['taken'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-warning"><?= $s['late'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-error"><?= $s['missed'] ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= pctClass($s['pct']) ?>">
                <?= $s['pct'] !== null ? $s['pct'] . '%' : 'N/A' ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($summary)): ?>
          <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No adherence data for this period</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php require_once 'nurse_footer.php'; ?>