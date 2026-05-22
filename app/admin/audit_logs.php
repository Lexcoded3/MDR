<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'Audit Logs - GxAlert';

 $filter_action = trim($_GET['action'] ?? '');
 $filter_entity = trim($_GET['entity'] ?? '');
 $filter_date   = trim($_GET['date'] ?? date('Y-m-d'));
 $page = max(1, (int)($_GET['page'] ?? 1));
 $perPage = 10;
 $offset = ($page - 1) * $perPage;

 $where = ["DATE(al.created_at) = ?"];
 $params = [$filter_date];
 $types = "s";

if ($filter_action !== '') { $where[] = "al.action = ?"; $params[] = $filter_action; $types .= "s"; }
if ($filter_entity !== '') { $where[] = "al.entity_type = ?"; $params[] = $filter_entity; $types .= "s"; }

 $where_sql = implode(' AND ', $where);

 $count_stmt = $conn->prepare("SELECT COUNT(*) FROM audit_log al WHERE $where_sql");
 $count_stmt->bind_param($types, ...$params);
 $count_stmt->execute();
 $total = (int)$count_stmt->get_result()->fetch_column();
 $totalPages = max(1, ceil($total / $perPage));

 $fetch_stmt = $conn->prepare("
    SELECT al.*, u.name AS user_name
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE $where_sql ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset
");
 $fetch_stmt->bind_param($types, ...$params);
 $fetch_stmt->execute();
 $logs = $fetch_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $action_colors = [
    'create'  => 'bg-success/10 text-success',
    'update'  => 'bg-info/10 text-info',
    'delete'  => 'bg-error/10 text-error',
    'login'   => 'bg-primary/10 text-primary',
    'logout'  => 'bg-slate-100 text-slate-500',
];

 $entity_labels = [
    'user' => 'User', 'patient' => 'Patient', 'regimen' => 'Regimen',
    'facility' => 'Facility', 'drug' => 'Drug', 'lab_result' => 'Lab Result',
    'adherence' => 'Adherence', 'appointment' => 'Appointment',
];
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Audit Logs</h1>
    <span class="text-sm text-slate-500 dark:text-navy-300"><?= number_format($total) ?> entries</span>
  </div>

  <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
    <input type="date" name="date" value="<?= $filter_date ?>" class="form-input rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
    <select name="action" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      <option value="">All Actions</option>
      <?php foreach (['create','update','delete','login','logout'] as $a): ?>
      <option value="<?= $a ?>" <?= $filter_action === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="entity" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      <option value="">All Entities</option>
      <?php foreach ($entity_labels as $k => $v): ?>
      <option value="<?= $k ?>" <?= $filter_entity === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Filter</button>
    <a href="audit_logs.php" class="btn h-9 border border-slate-300 text-slate-600 dark:border-navy-500 dark:text-navy-200 px-4">Clear</a>
  </form>

  <div class="card mt-3">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TIME</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">USER</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ACTION</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ENTITY</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ID</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">DETAILS</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-navy-300"><?= date('H:i:s', strtotime($l['created_at'])) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($l['user_name'] ?? 'System') ?></td>
            <td class="whitespace-nowrap px-4 py-2.5">
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $action_colors[$l['action']] ?? 'bg-slate-100 text-slate-500' ?>">
                <?= ucfirst($l['action']) ?>
              </span>
            </td>
            <?php $entity_type = $l['entity_type'] ?? ''; ?>
<td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= $entity_labels[$entity_type] ?? ($entity_type ?: '-') ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs font-mono text-slate-400"><?= $l['entity_id'] ?? '-' ?></td>
            <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-navy-300 max-w-xs truncate" title="<?= htmlspecialchars($l['details'] ?? '') ?>">
              <?= htmlspecialchars(mb_substr($l['details'] ?? '', 0, 80)) ?><?= strlen($l['details'] ?? '') > 80 ? '...' : '' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No logs for this date</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-4 flex items-center justify-between">
    <p class="text-sm text-slate-500">Page <?= $page ?> of <?= $totalPages ?></p>
    <div class="flex space-x-1">
      <?php for ($i = 1; $i <= min($totalPages, 10); $i++):
        $qp = http_build_query(array_merge($_GET, ['page' => $i]));
      ?>
      <a href="?<?= $qp ?>" class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-200 dark:hover:bg-navy-600' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php require_once 'admin_footer.php'; ?>