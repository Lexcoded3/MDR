<?php
session_start();
$required_role = 'data_officer';
require_once '../config/auth_check.php';
require_once '../config/db.php';

$pageTitle = 'Adherence Report - GxAlert';

// Sort options defined early so $sort whitelist works below
$sort_options = [
    'pct'    => 'Adherence %',
    'taken'  => 'Taken',
    'missed' => 'Missed',
    'late'   => 'Late',
    'name'   => 'Patient Name',
];

$period          = $_GET['period'] ?? '30d';
$sort            = $_GET['sort']   ?? 'pct';
$sort_dir        = strtolower($_GET['dir'] ?? 'asc');
$filter_facility = (int)($_GET['facility_id'] ?? 0);
$view            = $_GET['view'] ?? 'summary';

// Whitelist sort and direction to prevent SQL injection
$sort_dir = in_array($sort_dir, ['asc', 'desc']) ? $sort_dir : 'asc';
$sort     = in_array($sort, array_keys($sort_options)) ? $sort : 'pct';

// Parse period — whitelist interval to prevent SQL injection
if (preg_match('/^(\d+)([dwm])$/', $period, $m)) {
    $num      = min(max((int)$m[1], 1), 999);
    $unit     = $m[2];
    $interval = ['d' => 'DAY', 'w' => 'WEEK', 'm' => 'MONTH'][$unit] ?? 'DAY';
} else {
    $num      = 30;
    $unit     = 'd';
    $interval = 'DAY';
}

// Whitelist interval value (belt-and-suspenders)
$interval = in_array($interval, ['DAY', 'WEEK', 'MONTH']) ? $interval : 'DAY';

$period_labels = [
    '7d'  => 'Last 7 Days',
    '30d' => 'Last 30 Days',
    '90d' => 'Last 90 Days',
    '6m'  => 'Last 6 Months',
    '12m' => 'Last 12 Months',
];
$date_label = $period_labels[$period] ?? "Last $num $interval";

function pct($n, $t) { return $t > 0 ? round(($n / $t) * 100, 1) : 0; }
?>
<?php require_once 'data_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">

  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Adherence Report</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300"><?= htmlspecialchars($date_label) ?></p>
    </div>
  </div>

  <!-- Period Selector -->
  <div class="card mb-5">
    <div class="flex flex-wrap gap-2 p-3">
      <?php
      $periods = ['7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days', '6m' => '6 Months', '12m' => '12 Months'];
      foreach ($periods as $val => $label): ?>
      <a href="?period=<?= urlencode($val) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&view=<?= urlencode($view) ?>&facility_id=<?= $filter_facility ?>"
         class="btn rounded-full px-3 py-1.5 text-xs <?= $period === $val ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200' ?>">
        <?= htmlspecialchars($label) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- View Toggle -->
  <div class="mb-4 mt-5 flex flex-wrap items-center gap-2">
    <a href="?period=<?= urlencode($period) ?>&view=summary&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&facility_id=<?= $filter_facility ?>"
       class="btn rounded-full px-3 py-1.5 text-xs <?= $view === 'summary' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200' ?>">Summary</a>
    <a href="?period=<?= urlencode($period) ?>&view=detail&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&facility_id=<?= $filter_facility ?>"
       class="btn rounded-full px-3 py-1.5 text-xs <?= $view === 'detail' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600' ?>">Detailed View</a>
  </div>

  <?php if ($view === 'summary'): ?>

  <?php
  // --- Summary View Queries ---
  // Using $num as a bound parameter; $interval is whitelisted above so safe to interpolate
  $sum_sql = "
      SELECT COUNT(*) AS total,
             COALESCE(SUM(status = 'taken'), 0) AS taken,
             COALESCE(SUM(status = 'late'), 0)   AS late,
             COALESCE(SUM(status = 'missed'), 0)  AS missed
      FROM adherence_logs
      WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL ? $interval)
  ";
  $sum_stmt = $conn->prepare($sum_sql);
  $sum_stmt->bind_param('i', $num);
  $sum_stmt->execute();
  $sum = $sum_stmt->get_result()->fetch_assoc();
  $sum_stmt->close();

  $total     = (int)$sum['total'];
  $taken     = (int)$sum['taken'];
  $late      = (int)$sum['late'];
  $missed    = (int)$sum['missed'];
  $adh_pct   = pct($taken + $late, $total);
  $pct_color = $adh_pct >= 95 ? 'success' : ($adh_pct >= 85 ? 'warning' : 'error');

  // Adherence distribution buckets
  $buck_sql = "
      SELECT
          CASE
              WHEN adherence_pct >= 95 THEN '>= 95%'
              WHEN adherence_pct >= 85 THEN '85-94%'
              WHEN adherence_pct >= 70 THEN '70-84%'
              WHEN adherence_pct >= 50 THEN '50-69%'
              ELSE '< 50%'
          END AS bucket,
          COUNT(*) AS cnt
      FROM (
          SELECT patient_id,
                 ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS adherence_pct
          FROM adherence_logs
          WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL ? $interval)
          GROUP BY patient_id
      ) t
      GROUP BY bucket
      ORDER BY MIN(adherence_pct) DESC
  ";
  $buck_stmt = $conn->prepare($buck_sql);
  $buck_stmt->bind_param('i', $num);
  $buck_stmt->execute();
  $buckets = $buck_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $buck_stmt->close();

  $bucket_colors = [
      '>= 95%' => 'success',
      '85-94%' => 'success',
      '70-84%' => 'warning',
      '50-69%' => 'warning',
      '< 50%'  => 'error',
  ];

  // Facility adherence comparison
  $fac_adh_sql = "
      SELECT f.name,
             COUNT(DISTINCT t.patient_id) AS total_patients,
             ROUND(AVG(t.adh_pct), 1) AS avg_adh
      FROM (
          SELECT al.patient_id,
                 ROUND((SUM(CASE WHEN al.status IN ('taken','late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) AS adh_pct
          FROM adherence_logs al
          JOIN patients p ON al.patient_id = p.id
          WHERE al.dose_date >= DATE_SUB(CURDATE(), INTERVAL ? $interval)
            AND p.is_active = 1
          GROUP BY al.patient_id
      ) t
      JOIN patients p ON t.patient_id = p.id
      JOIN facilities f ON p.facility_id = f.id
      GROUP BY p.facility_id
      HAVING total_patients >= 3
      ORDER BY avg_adh DESC
  ";
  $fac_adh_stmt = $conn->prepare($fac_adh_sql);
  $fac_adh_stmt->bind_param('i', $num);
  $fac_adh_stmt->execute();
  $fac_adh = $fac_adh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $fac_adh_stmt->close();
  ?>

  <!-- Overview Cards -->
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6 mt-5">
    <div class="card border-t-4 border-t-<?= $pct_color ?> p-4 text-center">
      <div class="text-3xl font-bold text-<?= $pct_color ?>"><?= $adh_pct ?>%</div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Overall Adherence</p>
    </div>
    <div class="card border-t-4 border-t-success p-4 text-center">
      <div class="text-3xl font-bold text-success"><?= $taken ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Doses Taken</p>
    </div>
    <div class="card border-t-4 border-t-warning p-4 text-center">
      <div class="text-3xl font-bold text-warning"><?= $late ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Doses Late</p>
    </div>
    <div class="card border-t-4 border-t-error p-4 text-center">
      <div class="text-3xl font-bold text-error"><?= $missed ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Doses Missed</p>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

    <!-- Adherence Distribution -->
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="text-sm font-medium text-slate-700 dark:text-navy-100">Patient Adherence Distribution</h2>
      </div>
      <div class="p-5">
        <?php if (empty($buckets)): ?>
        <p class="text-sm text-center text-slate-400 dark:text-navy-300 py-4">No adherence data for this period</p>
        <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($buckets as $b): ?>
          <div class="flex items-center justify-between rounded-lg bg-slate-100 dark:bg-navy-800 p-3">
            <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($b['bucket']) ?></span>
            <div class="flex items-center gap-3">
              <div class="w-32 h-3 rounded-full bg-slate-200 dark:bg-navy-700">
                <div class="h-full rounded-full bg-<?= $bucket_colors[$b['bucket']] ?? 'slate-400' ?>" style="width: <?= pct($b['cnt'], $total) ?>%"></div>
              </div>
              <span class="text-xs font-mono w-16 text-right text-slate-600 dark:text-navy-200"><?= $b['cnt'] ?> (<?= pct($b['cnt'], $total) ?>%)</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Facility Comparison -->
    <?php if (!empty($fac_adh)): ?>
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="text-sm font-medium text-slate-700 dark:text-navy-100">Facility Adherence Comparison</h2>
        <p class="text-xs text-slate-400 dark:text-navy-300 mt-0.5">Only facilities with 3+ patients with adherence data</p>
      </div>
      <div class="is-scrollbar-hidden overflow-x-auto">
        <table class="is-hoverable w-full text-left">
          <thead>
            <tr>
              <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Facility</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patients</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Avg Adherence</th>
              <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Bar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fac_adh as $fa):
                $f_color = $fa['avg_adh'] >= 95 ? 'success' : ($fa['avg_adh'] >= 85 ? 'warning' : 'error');
            ?>
            <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($fa['name']) ?></td>
              <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600 dark:text-navy-200"><?= (int)$fa['total_patients'] ?></td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="text-sm font-mono text-<?= $f_color ?>"><?= $fa['avg_adh'] ?>%</span>
              </td>
              <td class="whitespace-nowrap px-4 py-3">
                <div class="w-full max-w-[120px] h-5 rounded-full bg-slate-100 dark:bg-navy-700">
                  <div class="h-full rounded-full bg-<?= $f_color ?> transition-all" style="width: <?= $fa['avg_adh'] ?>%"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- end summary grid -->

  <?php else: ?>
  <!-- Detailed View -->

  <?php
  // Sort column map — whitelisted above, safe to interpolate
  $sort_col_map = [
      'pct'    => 'adh_pct',
      'taken'  => 'taken',
      'missed' => 'missed',
      'late'   => 'late',
      'name'   => 'p.full_name',
  ];
  $order_col = $sort_col_map[$sort] ?? 'adh_pct';

  // Count total patients for pagination
  $count_sql = "
      SELECT COUNT(DISTINCT al.patient_id)
      FROM adherence_logs al
      JOIN patients p ON al.patient_id = p.id
      WHERE al.dose_date >= DATE_SUB(CURDATE(), INTERVAL ? $interval)
        AND p.is_active = 1
  ";
  $count_stmt = $conn->prepare($count_sql);
  $count_stmt->bind_param('i', $num);
  $count_stmt->execute();
  $total_patients = (int)$count_stmt->get_result()->fetch_column();
  $count_stmt->close();

  $limit      = 100;
  $totalPages = max(1, ceil($total_patients / $limit));
  $page       = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
  $offset     = ($page - 1) * $limit;

  // Patient-level detail query — $order_col and $sort_dir are both whitelisted
  $detail_sql = "
      SELECT
          p.id, p.patient_code, p.full_name, p.gender, p.date_of_birth,
          COUNT(*) AS total_doses,
          COALESCE(SUM(CASE WHEN al.status = 'taken'  THEN 1 ELSE 0 END), 0) AS taken,
          COALESCE(SUM(CASE WHEN al.status = 'late'   THEN 1 ELSE 0 END), 0) AS late,
          COALESCE(SUM(CASE WHEN al.status = 'missed' THEN 1 ELSE 0 END), 0) AS missed,
          ROUND((SUM(CASE WHEN al.status IN ('taken','late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) AS adh_pct
      FROM adherence_logs al
      JOIN patients p ON al.patient_id = p.id
      WHERE al.dose_date >= DATE_SUB(CURDATE(), INTERVAL ? $interval)
        AND p.is_active = 1
      GROUP BY al.patient_id, p.id, p.patient_code, p.full_name, p.gender, p.date_of_birth
      ORDER BY $order_col $sort_dir
      LIMIT ? OFFSET ?
  ";
  $detail_stmt = $conn->prepare($detail_sql);
  $detail_stmt->bind_param('iii', $num, $limit, $offset);
  $detail_stmt->execute();
  $patients = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $detail_stmt->close();
  ?>

  <!-- Sort Controls -->
  <div class="card mb-4 mt-5">
    <div class="flex flex-wrap gap-2 p-3 bg-slate-50 dark:bg-navy-800 rounded-lg">
      <span class="text-xs text-slate-500 dark:text-navy-300 mr-2 self-center">Sort by:</span>
      <?php foreach ($sort_options as $val => $label):
          $is_active = $sort === $val;
          $next_dir  = ($is_active && $sort_dir === 'asc') ? 'desc' : 'asc';
      ?>
      <a href="?period=<?= urlencode($period) ?>&view=detail&sort=<?= urlencode($val) ?>&dir=<?= $next_dir ?>&facility_id=<?= $filter_facility ?>"
         class="btn rounded-full px-3 py-1.5 text-xs <?= $is_active ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-100 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600' ?>">
        <?= htmlspecialchars($label) ?> <?= $is_active ? ($sort_dir === 'asc' ? '↑' : '↓') : '↓' ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Detail Table -->
  <div class="card mt-5">
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Code</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patient</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Age/Sex</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Total</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Taken</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Late</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Missed</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Adherence %</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patients)): ?>
          <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-navy-300">No adherence data for this period</td></tr>
          <?php endif; ?>
          <?php foreach ($patients as $p):
              $age       = $p['date_of_birth'] ? (new DateTime('today'))->diff(new DateTime($p['date_of_birth']))->y : '-';
              $row_pct   = (int)$p['adh_pct'];
              $adh_color = $row_pct >= 95 ? 'text-success' : ($row_pct >= 85 ? 'text-warning' : 'text-error');
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3"><code class="text-xs"><?= htmlspecialchars($p['patient_code']) ?></code></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="text-sm font-medium text-slate-700 hover:text-primary dark:text-navy-100"><?= htmlspecialchars($p['full_name']) ?></span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-200"><?= $age ?>/<?= htmlspecialchars(strtoupper(substr($p['gender'], 0, 1))) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-slate-500 dark:text-navy-200"><?= (int)$p['total_doses'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-success"><?= (int)$p['taken'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-warning"><?= (int)$p['late'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-error"><?= (int)$p['missed'] ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="text-xs font-bold <?= $adh_color ?>"><?= $row_pct ?>%</span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between border-t border-slate-150 dark:border-navy-600 px-4 py-3">
      <span class="text-xs text-slate-400 dark:text-navy-300">
        <?= $offset + 1 ?>–<?= min($offset + $limit, $total_patients) ?> of <?= $total_patients ?>
      </span>
      <div class="flex gap-1">
        <?php if ($page > 1): ?>
        <a href="?period=<?= urlencode($period) ?>&view=detail&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&page=<?= $page - 1 ?>&facility_id=<?= $filter_facility ?>"
           class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm dark:bg-navy-700">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?period=<?= urlencode($period) ?>&view=detail&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&page=<?= $i ?>&facility_id=<?= $filter_facility ?>"
           class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-navy-700 dark:text-navy-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?period=<?= urlencode($period) ?>&view=detail&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($sort_dir) ?>&page=<?= $page + 1 ?>&facility_id=<?= $filter_facility ?>"
           class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm dark:bg-navy-700 dark:hover:bg-navy-600">›</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<?php
$notify_text = '';
require_once 'data_footer.php';
?>