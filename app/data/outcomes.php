<?php
session_start();
$required_role = 'data_officer';
require_once '../config/auth_check.php';
require_once '../config/db.php';

$pageTitle = 'Treatment Outcomes - GxAlert';

$outcome_labels = [
    'cured'               => 'Cured',
    'treatment_completed' => 'Treatment Completed',
    'treatment_failed'    => 'Treatment Failed',
    'died'                => 'Died',
    'lost_to_followup'    => 'Lost to Follow-up',
    'not_evaluated'       => 'Not Evaluated',
];

$outcome_colors = [
    'cured'               => 'bg-success text-white',
    'treatment_completed' => 'bg-success text-white',
    'treatment_failed'    => 'bg-error text-white',
    'died'                => 'bg-slate-500 text-white',
    'lost_to_followup'    => 'bg-warning text-white',
    'not_evaluated'       => 'bg-slate-400 text-white',
];

// Filters
$filter_outcome = $_GET['outcome'] ?? '';
$filter_year = $_GET['year'] ?? date('Y');
$filter_facility = (int)($_GET['facility_id'] ?? 0);

$where = ["o.id IS NOT NULL"];
$params = [];
$types = "";

if ($filter_outcome !== '') {
    $outcome_array = explode(',', $filter_outcome);
    $count = count($outcome_array);
    $placeholders = implode(',', array_fill(0, $count, '?'));
    $where[] = "o.outcome IN ($placeholders)";
    foreach($outcome_array as $val) {
        $params[] = $val;
        $types .= "s";
    }
}

if ($filter_year) {
    $where[] = "YEAR(o.outcome_date) = ?";
    $params[] = $filter_year;
    $types .= "i";
}

$where_sql = implode(' AND ', $where);

// Outcome counts
$outcome_counts_sql = "
    SELECT o.outcome, COUNT(*) AS cnt,
           MIN(o.outcome_date) AS first_date,
           MAX(o.outcome_date) AS last_date
    FROM treatment_outcomes o
    WHERE $where_sql
    GROUP BY o.outcome
    ORDER BY FIELD(o.outcome, 'cured','treatment_completed','treatment_failed','died','lost_to_followup','not_evaluated')
";
$outcome_counts_stmt = $conn->prepare($outcome_counts_sql);
if ($params) $outcome_counts_stmt->bind_param($types, ...$params);
$outcome_counts_stmt->execute();
$outcome_counts = $outcome_counts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total outcomes
$total_outcomes = array_sum(array_column($outcome_counts, 'cnt'));

// Monthly trend
$trend_sql = "
    SELECT DATE_FORMAT(o.outcome_date, '%Y-%m') AS month, o.outcome, COUNT(*) AS cnt
    FROM treatment_outcomes o
    WHERE $where_sql
    GROUP BY month, o.outcome
    ORDER BY month DESC, FIELD(o.outcome, 'cured','treatment_completed','treatment_failed','died','lost_to_followup','not_evaluated')
";
$trend_stmt = $conn->prepare($trend_sql);
if ($params) $trend_stmt->bind_param($types, ...$params);
$trend_stmt->execute();
$trend = $trend_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Grouped by month
$trend_grouped = [];
foreach ($trend as $t) {
    if (!isset($trend_grouped[$t['month']])) $trend_grouped[$t['month']] = [];
    $trend_grouped[$t['month']][$t['outcome']] = (int)$t['cnt'];
}

// Facility-level outcome breakdown
$fac_outcome_sql = "
    SELECT f.name, f.id AS facility_id, o.outcome, COUNT(*) AS cnt
    FROM treatment_outcomes o
    JOIN patients p ON o.patient_id = p.id
    JOIN facilities f ON p.facility_id = f.id
    WHERE $where_sql
    GROUP BY f.id, o.outcome
    HAVING cnt > 0
    ORDER BY f.name, FIELD(o.outcome, 'cured','treatment_completed','treatment_failed','died','lost_to_followup','not_evaluated')
";
$fac_outcome_stmt = $conn->prepare($fac_outcome_sql);
if ($params) $fac_outcome_stmt->bind_param($types, ...$params);
$fac_outcome_stmt->execute();
$fac_outcomes = $fac_outcome_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Grouped by facility
$fac_grouped = [];
foreach ($fac_outcomes as $fo) {
    if (!isset($fac_grouped[$fo['name']])) $fac_grouped[$fo['name']] = [];
    $fac_grouped[$fo['name']][$fo['outcome']] = (int)$fo['cnt'];
}

// Facility dropdown
$facilities = $conn->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Year filter options
$years = $conn->query("SELECT DISTINCT YEAR(outcome_date) AS yr FROM treatment_outcomes WHERE outcome_date IS NOT NULL ORDER BY yr DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

function array_column_custom(array $arr, string $col): array {
    return array_map(function($row) use ($col) { return (int)($row[$col] ?? 0); }, $arr);
}
?>
<?php require_once 'data_header.php'; ?>

<div class="mt-5 sm:mt-5 lg:mt-6">

  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-600">Treatment Outcomes</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300"><?= $total_outcomes ?> recorded outcome<?= $total_outcomes !== 1 ? 's' : '' ?> total</p>
    </div>
    <a href="export.php?type=outcomes&outcome=<?= $filter_outcome ?>&year=<?= $filter_year ?>&facility_id=<?= $filter_facility ?>"
       class="btn bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0l3.375 3.375M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
      Export CSV
    </a>
  </div>

  <!-- Filters -->
  <form method="GET" class="card mb-5 mt-5">
    <div class="flex flex-wrap gap-2 p-3">
      <select name="year" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
        <option value="<?= $y['yr'] ?>" <?= $filter_year == $y['yr'] ? 'selected' : '' ?>><?= $y['yr'] ?></option>
        <?php endforeach; ?>
      </select>
      <select name="outcome" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Outcomes</option>
        <?php foreach ($outcome_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $filter_outcome === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select name="facility_id" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Facilities</option>
        <?php foreach ($facilities as $f): ?>
        <option value="<?= $f['id'] ?>" <?= $filter_facility == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn bg-slate-200 px-4 py-1.5 text-sm text-slate-600 hover:bg-slate-300 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Apply</button>
      <?php if ($filter_outcome !== '' || $filter_year !== '' || $filter_facility > 0): ?>
      <a href="outcomes.php" class="btn bg-error/10 px-3 py-1.5 text-sm text-error hover:bg-error/20">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Outcome Summary Cards -->
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-5">
    <?php foreach ($outcome_labels as $val => $label): ?>
<td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-slate-600 dark:text-navy-200">
    <?php 
        // Standard PHP tag: Semicolon IS required here
        $cnt = $month_data[$val] ?? 0; 
    ?>
    <?php if ($cnt > 0): ?>
        <span class="text-slate-700 dark:text-navy-100"><?= $cnt ?></span>
    <?php else: ?>
        <span class="text-slate-300 dark:text-navy-300">-</span>
    <?php endif; ?>
</td>
<?php endforeach; ?>
  </div>

  <!-- Monthly Trend Table -->
  <div class="card mb-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
      <h2 class="text-sm font-medium text-slate-700 dark:text-navy-100">Monthly Trend <?= $filter_year ?: 'All Years' ?></h2>
    </div>
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Month</th>
            <?php foreach ($outcome_labels as $val => $label): ?>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200"><?= $label ?></th>
            <?php endforeach; ?>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($trend_grouped)): ?>
          <tr><td colspan="<?= count($outcome_labels) + 2 ?>" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-navy-300">No outcome data for this period</td></tr>
          <?php else: ?>
          <?php foreach ($trend_grouped as $month => $month_data): ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700 dark:text-navy-100"><?= date('M Y', strtotime($month . '-01')) ?></td>
            <?php foreach ($outcome_labels as $val => $label): ?>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-slate-600 dark:text-navy-200">
              <?php $cnt = $month_data[$val] ?? 0; ?>
              <?php if ($cnt > 0): ?>
              <span class="text-slate-700 dark:text-navy-100"><?= $cnt ?></span>
              <?php else: ?>
              <span class="text-slate-300 dark:text-navy-300">-</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-slate-600 dark:text-navy-200">
              <?php $total_month = array_sum($month_data); ?>
              <?php echo $total_month > 0 ? $total_month : '-'; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Facility Outcome Breakdown -->
  <?php if (!empty($fac_grouped)): ?>
  <div class="card">
    <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
      <h2 class="text-sm font-medium text-slate-700 dark:text-navy-100">Outcome by Facility</h2>
    </div>
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Facility</th>
            <?php foreach ($outcome_labels as $val => $label): ?>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200"><?= $label ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fac_grouped as $fname => $fdata): ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($fname) ?></td>
            <?php foreach ($outcome_labels as $val => $label): ?>
            <td class="whitespace-nowrap px-4 py-3">
              <?php $cnt = $fdata[$val] ?? 0; ?>
              <?php if ($cnt > 0): ?>
              <span class="text-xs font-semibold text-slate-700 dark:text-navy-100"><?= $cnt ?></span>
              <?php else: ?>
              <span class="text-slate-300 dark:text-navy-300">-</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
 $notify_text = '';
require_once 'data_footer.php'; ?>