<?php
session_start();
 $required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

 $pageTitle = 'SMS Logs - GxAlert';

// Filters
 $filter_status = trim($_GET['status'] ?? '');
 $filter_date   = trim($_GET['date'] ?? date('Y-m-d'));
 $page = max(1, (int)($_GET['page'] ?? 1));
 $perPage = 8;
 $offset = ($page - 1) * $perPage;

 $where = ["DATE(sl.sent_at) = ?"];
 $params = [$filter_date];
 $types = "s";

if (in_array($filter_status, ['sent', 'delivered', 'failed'])) {
    $where[] = "sl.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

 $where_sql = implode(' AND ', $where);

// Count
 $count_stmt = $conn->prepare("SELECT COUNT(*) FROM sms_logs sl WHERE $where_sql");
 $count_stmt->bind_param($types, ...$params);
 $count_stmt->execute();
 $total = (int)$count_stmt->get_result()->fetch_column();
 $totalPages = max(1, ceil($total / $perPage));

// Fetch
 $log_stmt = $conn->prepare("
    SELECT sl.*, p.full_name, p.patient_code, p.phone AS patient_phone
    FROM sms_logs sl
    LEFT JOIN patients p ON sl.patient_id = p.id
    WHERE $where_sql
    ORDER BY sl.sent_at DESC
    LIMIT $perPage OFFSET $offset
");
 $log_stmt->bind_param($types, ...$params);
 $log_stmt->execute();
 $logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 $log_stmt->close();

// Summary counts
$sum_stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM sms_logs WHERE DATE(sent_at) = ? GROUP BY status");
$sum_stmt->bind_param("s", $filter_date);
$sum_stmt->execute();

// FIX: Fetch the entire result set into a variable first
$sum_result = $sum_stmt->get_result();
$summary = [];

while ($r = $sum_result->fetch_assoc()) {
    $summary[$r['status']] = (int)$r['cnt'];
}

$sum_stmt->close(); // Always close to keep the connection clean
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-4 mt-2">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">SMS Logs</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300"><?= $total ?> messages on <?= $filter_date ?></p>
    </div>
    <div class="flex gap-2">
      <span class="rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
        <?= $summary['sent'] ?? 0 ?> Sent
      </span>
      <span class="rounded-full bg-info/10 px-2.5 py-1 text-xs font-semibold text-info">
        <?= $summary['delivered'] ?? 0 ?> Delivered
      </span>
      <span class="rounded-full bg-error/10 px-2.5 py-1 text-xs font-semibold text-error">
        <?= $summary['failed'] ?? 0 ?> Failed
      </span>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" class="flex flex-wrap gap-2 mb-4 mt-5">
    <input type="date" name="date" value="<?= $filter_date ?>" 
           class="form-input w-40 rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
    <select name="status" class="form-input rounded-lg bg-slate-150 px-3 py-1.5 text-sm dark:bg-navy-900/90">
      <option value="">All Status</option>
      <option value="sent" <?= $filter_status === 'sent' ? 'selected' : '' ?>>Sent</option>
      <option value="delivered" <?= $filter_status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="failed" <?= $filter_status === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
    <button type="submit" class="btn bg-primary px-4 py-1.5 text-sm text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">Filter</button>
    <?php if ($filter_status !== '' || $filter_date !== date('Y-m-d')): ?>
    <a href="sms_logs.php" class="btn bg-error/10 px-3 py-1.5 text-sm text-error hover:bg-error/20">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Table -->
  <div class="card mt-5">
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Time</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patient</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Phone</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Message</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Delivery</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-navy-300">No SMS logs found</td></tr>
          <?php endif; ?>
          <?php foreach ($logs as $l):
              $st_cls = ['sent'=>'bg-success/10 text-success','delivered'=>'bg-info/10 text-info','failed'=>'bg-error/10 text-error'][$l['status']] ?? 'bg-slate-200 text-slate-600';
              $dl_cls = ['delivered'=>'text-success','undelivered'=>'text-error','expired'=>'text-warning','rejected'=>'text-error'][$l['delivery_report']] ?? 'text-slate-400';
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-200"><?= date('H:i A', strtotime($l['sent_at'])) ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($l['patient_id']): ?>
              <a href="viewpatient.php?id=<?= $l['patient_id'] ?>" class="text-sm font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent-light"><?= htmlspecialchars($l['full_name']) ?></a>
              <span class="ml-1 text-[10px] font-mono text-slate-400"><?= $l['patient_code'] ?? '' ?></span>
              <?php else: ?>
              <span class="text-sm text-slate-400">N/A</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs font-mono text-slate-500 dark:text-navy-200"><?= htmlspecialchars($l['phone_number'] ?? '') ?></td>
            <td class="px-4 py-3">
              <p class="text-xs text-slate-600 dark:text-navy-200 max-w-[250px] truncate" title="<?= htmlspecialchars($l['message'] ?? '') ?>"><?= htmlspecialchars(substr($l['message'] ?? '', 0, 80)) ?>...</p>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase <?= $st_cls ?>"><?= $l['status'] ?></span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($l['delivery_report']): ?>
              <span class="text-xs font-medium <?= $dl_cls ?>"><?= ucfirst($l['delivery_report']) ?></span>
              <?php else: ?>
              <span class="text-xs text-slate-400">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between border-t border-slate-150 dark:border-navy-600 px-4 py-3">
      <span class="text-xs text-slate-400 dark:text-navy-300"><?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?></span>
      <div class="flex gap-1">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&date=<?= $filter_date ?>&status=<?= $filter_status ?>" class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm dark:bg-navy-700">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="?page=<?= $i ?>&date=<?= $filter_date ?>&status=<?= $filter_status ?>" class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-navy-700' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&date=<?= $filter_date ?>&status=<?= $filter_status ?>" class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm dark:bg-navy-700">›</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'clinician_footer.php'; ?>