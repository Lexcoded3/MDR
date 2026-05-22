<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'User Management - GxAlert';

 $search     = trim($_GET['q'] ?? '');
 $filter_role = trim($_GET['role'] ?? '');
 $filter_status = trim($_GET['status'] ?? '');
 $page       = max(1, (int)($_GET['page'] ?? 1));
 $perPage    = 6;
 $offset     = ($page - 1) * $perPage;

 $where   = ["1=1"];
 $params  = [];
 $types   = "";

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.location LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    $types .= "sss";
}
if ($filter_role !== '' && isset($role_labels[$filter_role])) {
    $where[] = "u.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}
if ($filter_status === 'active') {
    $where[] = "u.is_active = 1";
} elseif ($filter_status === 'inactive') {
    $where[] = "u.is_active = 0";
}

 $where_sql = implode(' AND ', $where);
// Handle activate/deactivate
if (isset($_GET['action']) && isset($_GET['id'])) {
    $act_id = (int)$_GET['id'];
    if ($act_id === $admin_id) { header("Location: users.php"); exit; }
    
    if ($_GET['action'] === 'deactivate') {
        $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->bind_param("i", $act_id); 
        // Rebind and execute properly:
        $s = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $s->bind_param("i", $act_id);
        $s->execute();
        header("Location: users.php?status=deactivated");
        exit;
    } elseif ($_GET['action'] === 'activate') {
        $s = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $s->bind_param("i", $act_id);
        $s->execute();
        header("Location: users.php?status=activated");
        exit;
    }
}
// Count
 $count_stmt = $conn->prepare("SELECT COUNT(*) FROM users u WHERE $where_sql");
if ($params) $count_stmt->bind_param($types, ...$params);
 $count_stmt->execute();
 $total = (int)$count_stmt->get_result()->fetch_column();
 $totalPages = max(1, ceil($total / $perPage));

// Fetch
 $sql = "SELECT u.*,
               (SELECT COUNT(*) FROM patients WHERE created_by = u.id AND is_active = 1) AS patient_count
        FROM users u WHERE $where_sql ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset";
 $fetch_stmt = $conn->prepare($sql);
if ($params) $fetch_stmt->bind_param($types, ...$params);
 $fetch_stmt->execute();
 $users = $fetch_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">User Management</h1>
    <a href="adduser.php" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="mr-1 size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Add User
    </a>
  </div>

  <!-- Filters -->
  <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
      <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, location..."
             class="form-input w-full rounded-lg bg-slate-150 py-2 pl-9 pr-3 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
    </div>
    <select name="role" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90 min-w-[150px]">
      <option value="">All Roles</option>
      <?php foreach ($role_labels as $k => $v): ?>
      <option value="<?= $k ?>" <?= $filter_role === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90 min-w-[130px]">
      <option value="">All Status</option>
      <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Filter</button>
    <?php if ($search || $filter_role || $filter_status): ?>
    <a href="users.php" class="btn h-9 border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600 px-4">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Results count -->
  <p class="mt-3 text-sm text-slate-500 dark:text-navy-300"><?= number_format($total) ?> user(s) found</p>

  <!-- Table -->
  <div class="card mt-3">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">USER</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ROLE</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LOCATION</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENTS</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LAST LOGIN</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">STATUS</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex items-center space-x-3">
                <div class="avatar size-9">
                  <?php if ($u['image_paths'] && file_exists('../../' . $u['image_paths'])): ?>
                  <img class="rounded-full" src="../../<?= htmlspecialchars($u['image_paths']) ?>" alt="">
                  <?php else: ?>
                  <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
                    <?= strtoupper(substr($u['name'], 0, 2)) ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div>
                  <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($u['name']) ?></span>
                  <p class="text-xs text-slate-400 dark:text-navy-300"><?= htmlspecialchars($u['email']) ?></p>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?= $role_colors[$u['role']] ?? '' ?>">
                <?= $role_labels[$u['role']] ?? $u['role'] ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($u['location'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= $u['patient_count'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-300"><?= $u['last_login'] ? date('M j, H:i', strtotime($u['last_login'])) : 'Never' ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($u['is_active']): ?>
              <span class="inline-flex items-center text-xs font-medium text-success"><span class="mr-1 size-1.5 rounded-full bg-success"></span>Active</span>
              <?php else: ?>
              <span class="inline-flex items-center text-xs font-medium text-error"><span class="mr-1 size-1.5 rounded-full bg-error"></span>Inactive</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex space-x-1">
                <a href="edituser.php?id=<?= $u['id'] ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20" x-tooltip="'Edit'" title="Edit">
                  <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                </a>
                <?php if ($u['id'] !== $admin_id): ?>
                <?php if ($u['is_active']): ?>
                <a href="users.php?action=deactivate&id=<?= $u['id'] ?>" onclick="return confirm('Deactivate this user?')" class="btn size-8 rounded-full p-0 text-warning hover:bg-warning/10" x-tooltip.warning="'Deactivate'">
                  <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </a>
                <?php else: ?>
                <a href="users.php?action=activate&id=<?= $u['id'] ?>" class="btn size-8 rounded-full p-0 text-success hover:bg-success/10" x-tooltip.success="'Activate'">
                  <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </a>
                <?php endif; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No users found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="mt-4 flex items-center justify-between">
    <p class="text-sm text-slate-500">Page <?= $page ?> of <?= $totalPages ?></p>
    <div class="flex space-x-1">
      <?php for ($i = 1; $i <= $totalPages; $i++): 
        $qp = http_build_query(array_merge($_GET, ['page' => $i]));
      ?>
      <a href="?<?= $qp ?>" class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-200 dark:hover:bg-navy-600' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php require_once 'admin_footer.php'; ?>