<?php
session_start();
 $required_role = 'data_officer';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'data_init.php';

 $pageTitle = 'Cohort Report - GxAlert';

// Filters
 $filter_period = $_GET['period'] ?? '12m';
 $filter_status = $_GET['status'] ?? '';
 $filter_gender = $_GET['gender'] ?? '';
 $filter_hiv = $_GET['hiv'] ?? '';
 $filter_facility = (int)($_GET['facility_id'] ?? 0);

// Build WHERE
 $where = ["p.is_active = 1"];
 $params = [];
 $types = "";

if ($filter_period !== 'all') {
    switch ($filter_period) {
        case '3m':  $where[] = "p.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)"; break;
        case '6m':  $where[] = "p.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"; break;
        case '12m': $where[] = "p.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)"; break;
        case 'all': break;
    }
}
if ($filter_status !== '') { $where[] = "p.treatment_status = ?"; $params[] = $filter_status; $types .= "s"; }
if ($filter_gender !== '') { $where[] = "p.gender = ?"; $params[] = $filter_gender; $types .= "s"; }
if ($filter_hiv !== '') { $where[] = "p.hiv_status = ?"; $params[] = $filter_hiv; $types .= "s"; }
if ($filter_facility > 0) { $where[] = "p.facility_id = ?"; $params[] = $filter_facility; $types .= "i"; }

 $where_sql = implode(' AND ', $where);

// Cohort summary
 $cohort_sql = "
    SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN p.gender = 'male' THEN 1 ELSE 0 END), 0) AS male,
        COALESCE(SUM(CASE WHEN p.gender = 'female' THEN 1 ELSE 0 END), 0) AS female,
        COALESCE(SUM(CASE WHEN p.hiv_status = 'positive' THEN 1 ELSE 0 END), 0) AS hiv_positive,
        COALESCE(SUM(CASE WHEN p.hiv_status = 'positive' AND p.on_art = 1 THEN 1 ELSE 0 END), 0) as hiv_on_art,
        COALESCE(AVG(DATEDIFF(CURDATE(), p.date_of_birth)/365), 0) AS avg_age
    FROM patients p
    WHERE $where_sql
";
 $cohort_stmt = $conn->prepare($cohort_sql);
if ($params) $cohort_stmt->bind_param($types, ...$params);
 $cohort_stmt->execute();
 $cohort = $cohort_stmt->get_result()->fetch_assoc();
 $cohort['avg_age'] = round($cohort['avg_age'], 1);

// Status distribution for this cohort
 $status_sql = "
    SELECT p.treatment_status, COUNT(*) AS cnt
    FROM patients p WHERE $where_sql GROUP BY p.treatment_status ORDER BY cnt DESC
";
 $status_stmt = $conn->prepare($status_sql);
if ($params) $status_stmt->bind_param($types, ...$params);
 $status_stmt->execute();
 $status_dist = $status_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Case classification breakdown
 $class_sql = "
    SELECT p.tb_case_classification, COUNT(*) AS cnt
    FROM patients p WHERE $where_sql GROUP BY p.tb_case_classification ORDER BY cnt DESC
";
 $class_stmt = $conn->prepare($class_sql);
if ($params) $class_stmt->bind_param($types, ...$params);
 $class_stmt->execute();
 $class_dist = $class_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Facility distribution
 $fac_sql = "
    SELECT f.name, f.facility_type, COUNT(p.id) AS cnt
    FROM patients p
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE $where_sql AND p.facility_id IS NOT NULL
    GROUP BY p.facility_id
    ORDER BY cnt DESC
";
 $fac_stmt = $conn->prepare($fac_sql);
if ($params) $fac_stmt->bind_param($types, ...$params);
 $fac_stmt->execute();
 $fac_dist = $fac_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Facility dropdown for filter
 $facilities = $conn->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Period labels
 $period_labels = ['3m'=>'3 Months', '6m'=>'6 Months', '12m'=>'12 Months', 'all'=>'All Time'];

function pct($num, $total) {
    return $total > 0 ? round(($num / $total) * 100, 1) : 0;
}
?>
<?php require_once 'data_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">

  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Cohort Report</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300"><?= $cohort['total'] ?> patients in selected period</p>
    </div>
    <a href="export.php?type=cohort&period=<?= $filter_period ?>&status=<?= $filter_status ?>&gender=<?= $filter_gender ?>&hiv=<?= $filter_hiv ?>&facility_id=<?= $filter_facility ?>" 
     class="btn bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"  /></svg>
      Export CSV
    </a>
  </div>

  <!-- Filters -->
  <form method="GET" class="card mb-5 mt-5">
    <div class="flex flex-wrap gap-2 p-3">
      <select name="period" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <?php foreach ($period_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $filter_period === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Status</option>
        <?php 
        $all_statuses = ['enrolled','on_treatment','completed','cured','failed','died','lost_to_followup','transferred_out'];
        foreach ($all_statuses as $s): ?>
        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="gender" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Gender</option>
        <option value="male" <?= $filter_gender === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $filter_gender === 'female' ? 'selected' : '' ?>>Female</option>
      </select>
      <select name="hiv" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">HIV Status</option>
        <option value="positive" <?= $filter_hiv === 'positive' ? 'selected' : '' ?>>HIV Positive</option>
        <option value="negative" <?= $filter_hiv === 'negative' ? 'selected' : '' ?>>HIV Negative</option>
        <option value="unknown" <?= $filter_hiv === 'unknown' ? 'selected' : '' ?>>Unknown</option>
      </select>
      <select name="facility_id" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
        <option value="">All Facilities</option>
        <?php foreach ($facilities as $f): ?>
        <option value="<?= $f['id'] ?>" <?= $filter_facility == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn bg-slate-200 px-4 py-1.5 text-sm text-slate-600 hover:bg-slate-300 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Apply</button>
      <?php if ($filter_period !== '12m' || $filter_status !== '' || $filter_gender !== '' || $filter_hiv !== '' || $filter_facility > 0): ?>
      <a href="cohort_report.php" class="btn bg-error/10 px-3 py-1.5 text-sm text-error hover:bg-error/20">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Summary Cards -->
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-5 mt-5">
    <div class="card p-4 text-center">
      <div class="text-2xl font-bold text-primary dark:text-accent-light"><?= $cohort['total'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">Total Patients</p>
    </div>
    <div class="card p-4 text-center">
      <div class="text-2xl font-bold text-slate-700 dark:text-navy-100"><?= $cohort['avg_age'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">Avg Age (years)</p>
    </div>
    <div class="card p-4 text-center">
      <div class="text-2xl font-bold text-warning"><?= $cohort['hiv_positive'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">HIV Positive</p>
    </div>
    <div class="card p-4 text-center">
      <div class="text-2xl font-bold text-info"><?= $cohort['hiv_on_art'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300">On ART</p>
    </div>
  </div>

  <!-- Gender Ratio -->
  <div class="mb-5 mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
        <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">Gender Distribution</h3>
      </div>
      <div class="p-6">
        <div class="flex h-6 rounded-full bg-slate-100 dark:bg-navy-700 overflow-hidden">
          <?php 
          $male_pct = pct($cohort['male'], $cohort['total']);
          $female_pct = pct($cohort['female'], $cohort['total']);
          $other_pct = 100 - $male_pct - $female_pct;
          ?>
          <div class="flex h-full">
            <div class="bg-primary dark:bg-accent rounded-l-full transition-all" style="width:<?= $male_pct ?>%"></div>
            <div class="bg-danger rounded-r-full transition-all" style="width: <?= $female_pct ?>%"></div>
          </div>
        </div>
        <div class="flex justify-center gap-6 mt-3 text-xs">
          <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-primary dark:bg-accent"></span> Male <?= $male_pct ?>%</span>
          <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-danger"></span> Female <?= $female_pct ?>%</span>
        </div>
      </div>
    </div>

    <!-- Case Classification -->
    <div class="card mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
        <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">Case Classification</h3>
      </div>
      <div class="divide-y divide-slate-150 dark:divide-navy-600">
        <?php foreach ($class_dist as $c): ?>
        <div class="flex items-center justify-between px-4 py-4">
          <span class="text-sm text-slate-600 dark:text-navy-200"><?= str_replace('_', ' ', ucfirst($c['tb_case_classification'])) ?></span>
          <div class="flex items-center gap-2">
            <div class="w-32 h-2 rounded-full bg-slate-200 dark:bg-navy-700">
              <div class="h-full rounded-full bg-primary" style="width: <?= pct($c['cnt'], $cohort['total']) ?>%"></div>
            </div>
            <span class="text-xs font-mono w-16 text-right text-slate-600 dark:text-navy-200"><?= $c['cnt'] ?> (<?= pct($c['cnt'], $cohort['total']) ?>%)</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Status Distribution -->
  <div class="card mt-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
      <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">Treatment Outcome Distribution</h3>
    </div>
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Count</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Percentage</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Bar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($status_dist as $s):
              $pct = pct($s['cnt'], $cohort['total']);
              $bar_color = ['on_treatment'=>'bg-primary','enrolled'=>'bg-info','completed'=>'bg-success','cured'=>'bg-success','failed'=>'bg-error','died'=>'bg-slate-400 dark:bg-navy-600','lost_to_followup'=>'bg-warning','transferred_out'=>'bg-secondary'][$s['treatment_status']] ?? 'bg-slate-200';
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3">
              <span class="text-sm text-slate-700 dark:text-navy-100"><?= str_replace('_', ' ', ucfirst($s['treatment_status'])) ?></span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600 dark:text-navy-200"><?= $s['cnt'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600 dark:text-navy-200"><?= $pct ?>%</td>
            <td class="whitespace-nowrap px-4 py-3">
              <div class="w-full max-w-[120px] h-5 rounded-full bg-slate-100 dark:bg-navy-700">
                <div class="h-full rounded-full <?= $bar_color ?> transition-all" style="width: <?= $pct ?>%"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Facility Distribution -->
  <?php if (!empty($fac_dist)): ?>
  <div class="card mt-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Facility Distribution</h2>
    </div>
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Facility</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Type</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patients</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Percentage</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Bar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fac_dist as $f):
              $pct = pct($f['cnt'], $cohort['total']);
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($f['name']) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-300"><?= ucfirst($f['facility_type'] ?? 'N/A') ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600 dark:text-navy-200"><?= $f['cnt'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-600 dark:text-navy-200"><?= $pct ?>%</td>
            <td class="whitespace-nowrap px-4 py-3">
              <div class="w-full max-w-[120px] h-5 rounded-full bg-slate-100 dark:bg-navy-700">
                <div class="h-full rounded-full bg-primary transition-all" style="width: <?= $pct ?>%"></div>
              </div>
            </td>
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