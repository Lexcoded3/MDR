<?php
session_start();
$required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

$pageTitle = 'My Adherence - GxAlert';

// Filter params
$filter_month = clean($_GET['month'] ?? date('Y-m'));
$filter_status = clean($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Build where
$where = ["al.patient_id = ?"];
$params = [$patient_id];
$types = "i";

if (preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
    $where[] = "DATE_FORMAT(al.dose_date, '%Y-%m') = ?";
    $params[] = $filter_month;
    $types .= "s";
}

if (in_array($filter_status, ['taken', 'late', 'missed'])) {
    $where[] = "al.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_sql = implode(' AND ', $where);

// Monthly summary
$sum_stmt = $conn->prepare("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(CASE WHEN al.status = 'taken' THEN 1 ELSE 0 END), 0) AS taken,
           COALESCE(SUM(CASE WHEN al.status = 'late' THEN 1 ELSE 0 END), 0) AS late,
           COALESCE(SUM(CASE WHEN al.status = 'missed' THEN 1 ELSE 0 END), 0) AS missed
    FROM adherence_logs al
    WHERE $where_sql
");
$sum_stmt->bind_param($types, ...$params);
$sum_stmt->execute();
$sum = $sum_stmt->get_result()->fetch_assoc();

$total  = (int)$sum['total'];
$taken  = (int)$sum['taken'];
$late   = (int)$sum['late'];
$missed = (int)$sum['missed'];
$pct    = $total > 0 ? round((($taken + $late) / $total) * 100, 1) : 0;
$color  = $pct >= 95 ? 'success' : ($pct >= 85 ? 'warning' : 'error');

// Count total for pagination
$count_stmt = $conn->prepare("
    SELECT COUNT(*) FROM adherence_logs al WHERE $where_sql
");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = (int)$count_stmt->get_result()->fetch_column();
$total_pages = ceil($total_records / $per_page);

// Detailed logs with pagination
$log_stmt = $conn->prepare("
    SELECT al.*, d.drug_name, d.drug_code, ms.dose_time
    FROM adherence_logs al
    LEFT JOIN medication_schedule ms ON al.schedule_id = ms.id
    LEFT JOIN drugs d ON ms.drug_id = d.id
    WHERE $where_sql
    ORDER BY al.dose_date DESC, al.id DESC
    LIMIT ? OFFSET ?
");
$types_paginated = $types . "ii";
$params_paginated = array_merge($params, [$per_page, $offset]);
$log_stmt->bind_param($types_paginated, ...$params_paginated);
$log_stmt->execute();
$logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Month navigation
$current_dt = new DateTime($filter_month . '-01');
$prev_month = $current_dt->modify('-1 month')->format('Y-m');
$next_month = (new DateTime($filter_month . '-01'))->modify('+1 month')->format('Y-m');
$display_month = (new DateTime($filter_month . '-01'))->format('F Y');

// Cannot go beyond current month
$is_current_month = $filter_month === date('Y-m');

function clean($val) {
    return trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
}

// Build query string for pagination
function build_query($params) {
    return http_build_query(array_filter($params));
}
?>
<?php require_once 'patient_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">My Adherence</h1>
    <div class="flex items-center space-x-2">
      <a href="?month=<?= $prev_month ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
      </a>
      <span class="text-sm font-medium text-slate-700 dark:text-navy-100 min-w-[120px] text-center"><?= $display_month ?></span>
      <?php if (!$is_current_month): ?>
      <a href="?month=<?= $next_month ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
      </a>
      <?php else: ?>
      <span class="btn size-8 rounded-full p-0 opacity-30">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
      </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6 mt-5">
    <div class="card text-center p-4">
      <div class="text-3xl font-bold text-<?= $color ?>"><?= $pct ?>%</div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Adherence Rate</p>
    </div>
    <div class="card text-center p-4">
      <div class="text-3xl font-bold text-success"><?= $taken ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Taken</p>
    </div>
    <div class="card text-center p-4">
      <div class="text-3xl font-bold text-warning"><?= $late ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Late</p>
    </div>
    <div class="card text-center p-4">
      <div class="text-3xl font-bold text-error"><?= $missed ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Missed</p>
    </div>
  </div>

  <!-- Status Filter -->
  <div class="flex flex-wrap gap-2 mb-4 mt-5">
    <a href="?<?= build_query(['month' => $filter_month]) ?>" class="btn rounded px-3 py-1 text-xs <?= $filter_status === '' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600' ?>">All (<?= $total ?>)</a>
    <a href="?<?= build_query(['month' => $filter_month, 'status' => 'taken']) ?>" class="btn rounded px-3 py-1 text-xs <?= $filter_status === 'taken' ? 'bg-success text-white' : 'bg-success/10 text-success hover:bg-success/20' ?>">Taken (<?= $taken ?>)</a>
    <a href="?<?= build_query(['month' => $filter_month, 'status' => 'late']) ?>" class="btn rounded px-3 py-1 text-xs <?= $filter_status === 'late' ? 'bg-warning text-white' : 'bg-warning/10 text-warning hover:bg-warning/20' ?>">Late (<?= $late ?>)</a>
    <a href="?<?= build_query(['month' => $filter_month, 'status' => 'missed']) ?>" class="btn rounded px-3 py-1 text-xs <?= $filter_status === 'missed' ? 'bg-error text-white' : 'bg-error/10 text-error hover:bg-error/20' ?>">Missed (<?= $missed ?>)</a>
  </div>

  <!-- Logs Table -->
  <?php if (!empty($logs)): ?>
  <div class="mt-5">
    <div class="card">
      <div class="is-scrollbar-hidden overflow-x-auto">
        <table class="is-hoverable w-full text-left">
          <thead>
            <tr>
              <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Date</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Drug</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Scheduled</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
              <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Verified By</th>
              <?php if ($filter_status === 'missed'): ?>
              <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Reason</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log):
                $st_colors = [
                    'taken'  => 'bg-success/10 text-success',
                    'late'   => 'bg-warning/10 text-warning',
                    'missed' => 'bg-error/10 text-error',
                ];
                $st_cls = $st_colors[$log['status']] ?? 'bg-slate-200 text-slate-600';
                $icon = $log['status'] === 'taken' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />'
                    : ($log['status'] === 'late'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01" />'
                        : '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />');
            ?>
            <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
              <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700 dark:text-navy-100"><?= $log['dose_date'] ?></td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($log['drug_name'] ?? 'N/A') ?></span>
                <?php if ($log['drug_code']): ?><span class="ml-1 text-[10px] font-mono text-slate-400 dark:text-navy-300"><?= $log['drug_code'] ?></span><?php endif; ?>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= $log['dose_time'] ? date('h:i A', strtotime($log['dose_time'])) : '-' ?></td>
              <td class="whitespace-nowrap px-4 py-3">
                <span class="inline-flex items-center space-x-1 rounded-full px-2.5 py-1 text-xs font-semibold <?= $st_cls ?>">
                  <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><?= $icon ?></svg>
                  <?= ucfirst($log['status']) ?>
                </span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 dark:text-navy-300">
                <?= str_replace('_', ' ', ucfirst($log['verification_method'] ?? 'self_report')) ?>
              </td>
              <?php if ($filter_status === 'missed'): ?>
              <td class="whitespace-nowrap px-4 py-3 text-xs text-error"><?= htmlspecialchars($log['missed_reason'] ?? '-') ?></td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="flex items-center justify-between border-t border-slate-150 dark:border-navy-600 px-5 py-3">
        <div class="text-xs text-slate-400 dark:text-navy-300">
          Showing <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_records) ?> of <?= $total_records ?>
        </div>
        <div class="flex space-x-1">
          <?php if ($page > 1): ?>
          <a href="?<?= build_query(['month' => $filter_month, 'status' => $filter_status, 'page' => $page - 1]) ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </a>
          <?php else: ?>
          <span class="btn size-8 rounded-full p-0 opacity-30">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </span>
          <?php endif; ?>

          <?php 
          $start = max(1, $page - 2);
          $end = min($total_pages, $page + 2);
          for ($i = $start; $i <= $end; $i++): 
          ?>
          <a href="?<?= build_query(['month' => $filter_month, 'status' => $filter_status, 'page' => $i]) ?>" class="btn size-8 rounded-full p-0 <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-200 dark:hover:bg-navy-600' ?>">
            <?= $i ?>
          </a>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
          <a href="?<?= build_query(['month' => $filter_month, 'status' => $filter_status, 'page' => $page + 1]) ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </a>
          <?php else: ?>
          <span class="btn size-8 rounded-full p-0 opacity-30">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <div class="card p-12 text-center mt-5">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-16 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
    </svg>
    <h3 class="mt-4 text-lg font-medium text-slate-600 dark:text-navy-200">No Adherence Records</h3>
    <p class="mt-2 text-sm text-slate-400 dark:text-navy-300">No records found for <?= $display_month ?></p>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'patient_footer.php'; ?>