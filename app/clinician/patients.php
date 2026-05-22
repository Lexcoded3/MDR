<?php
session_start();
$required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

$pageTitle = 'Patients - GxAlert';

// Search & filter
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Build query
$where = ["p.created_by = ?", "p.is_active = 1"];
$params = [$clinician_id];
$types  = "i";

if ($search !== '') {
    $where[] = "(p.full_name LIKE ? OR p.patient_code LIKE ? OR p.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}
if ($status !== '') {
    $where[] = "p.treatment_status = ?";
    $params[] = $status;
    $types .= "s";
}

$where_sql = implode(' AND ', $where);

// --- 1. Get Total Count for Pagination ---
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM patients p WHERE $where_sql");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = (int)$count_stmt->get_result()->fetch_column();
$count_stmt->close(); // Connection freed

$totalPages = max(1, ceil($total / $perPage));

// --- 2. Fetch Main Patient Data ---
$data_stmt = $conn->prepare("
    SELECT p.*, f.name AS facility_name
    FROM patients p 
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE $where_sql 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");

// Add pagination types and params
$list_types = $types . "ii";
$list_params = array_merge($params, [$perPage, $offset]);

$data_stmt->bind_param($list_types, ...$list_params);
$data_stmt->execute();
// Using fetch_all is "the better approach" - it gets everything as an associative array
$patients = $data_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$data_stmt->close(); // Connection freed

// --- 3. Get Status Counts for the Sidebar/Tabs ---
$sc_stmt = $conn->prepare("
    SELECT treatment_status, COUNT(*) AS cnt 
    FROM patients 
    WHERE created_by = ? AND is_active = 1 
    GROUP BY treatment_status
");
$sc_stmt->bind_param("i", $clinician_id);
$sc_stmt->execute();
$status_results = $sc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sc_stmt->close(); // Connection freed

// Format status counts for easy display
$status_counts = [];
foreach ($status_results as $row) {
    $status_counts[$row['treatment_status']] = (int)$row['cnt'];
}

// Helpers
function statusBadge($s) {
    $m = [
        'enrolled'          => 'bg-info/10 text-info',
        'on_treatment'      => 'bg-primary/10 text-primary dark:text-accent-light',
        'completed'         => 'bg-success/10 text-success',
        'cured'             => 'bg-success/10 text-success',
        'failed'            => 'bg-error/10 text-error',
        'died'              => 'bg-slate-400/10 text-slate-500 dark:text-navy-300',
        'lost_to_followup'  => 'bg-warning/10 text-warning',
        'transferred_out'   => 'bg-secondary/10 text-secondary',
    ];
    $c = $m[$s] ?? 'bg-slate-200 text-slate-600';
    return "<span class=\"rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase $c\">" . str_replace('_',' ',ucfirst($s)) . "</span>";
}

function calcAge($d) { 
    return $d ? (new DateTime('today'))->diff(new DateTime($d))->y : '-'; 
}
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Patient Registry</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300"><?= $total ?> patient<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <a href="addpatient.php" class="btn bg-primary px-4 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
      Register Patient
    </a>
  </div>

  <!-- Status Filters -->
  <div class="flex flex-wrap gap-2 mb-3 mt-4">
    <a href="patients.php" class="btn rounded-full px-3 py-1 text-xs <?= $status === '' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200' ?>">All (<?= array_sum($status_counts) ?>)</a>
    <?php foreach (['on_treatment','enrolled','completed','cured','lost_to_followup','failed'] as $s):
        $cnt = $status_counts[$s] ?? 0;
        if ($cnt === 0) continue;
    ?>
    <a href="patients.php?status=<?= $s ?>" class="btn rounded-full px-3 py-1 text-xs <?= $status === $s ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200' ?>">
      <?= str_replace('_',' ',ucfirst($s)) ?> (<?= $cnt ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <form method="GET" class="mb-4 mt-5">
    <div class="flex gap-2">
      <div class="relative flex-1 max-w-md">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="size-4 text-slate-400 transition-colors duration-200" 
             fill="none" 
             viewBox="0 0 24 24" 
             stroke="currentColor" 
             stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </span>
    
    <input 
        type="text" 
        name="q" 
        value="<?= htmlspecialchars($search) ?>" 
        placeholder="Search name, code, phone..."
        class="form-input w-full rounded-lg bg-slate-100 py-2 pl-10 pr-3 text-sm transition-all duration-200
               border border-transparent
               hover:bg-slate-200
               focus:bg-white focus:border-primary focus:ring-4 focus:ring-primary/10
               dark:bg-navy-900/90 dark:hover:bg-navy-800 dark:focus:bg-navy-700 dark:focus:border-accent dark:focus:ring-accent/10
               placeholder:text-slate-400 dark:placeholder:text-navy-300 shadow-sm"
    >
</div>
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= $status ?>"><?php endif; ?>
      <button type="submit" class="btn bg-slate-200 px-4 text-sm text-slate-600 hover:bg-slate-300 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Search</button>
      <?php if ($search !== '' || $status !== ''): ?>
      <a href="patients.php" class="btn bg-error/10 px-3 text-sm text-error hover:bg-error/20">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="card mt-5">
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Code</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Name</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Age/Sex</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Phone</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">HIV</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Enrolled</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patients)): ?>
          <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400 dark:text-navy-300">No patients found</td></tr>
          <?php endif; ?>
          <?php foreach ($patients as $p): ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600 hover:cursor-pointer" onclick="location.href='viewpatient.php?id=<?= $p['id'] ?>'">
            <td class="whitespace-nowrap px-4 py-3"><code class="text-xs"><?= htmlspecialchars($p['patient_code']) ?></code></td>
            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($p['full_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= calcAge($p['date_of_birth']) ?>/<?= substr($p['gender'],0,1) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($p['hiv_status'] === 'positive'): ?>
              <span class="rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-semibold text-warning">HIV+</span>
              <?php else: ?>
              <span class="text-xs text-slate-400"><?= ucfirst($p['hiv_status'] ?? '-') ?></span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-3"><?= statusBadge($p['treatment_status']) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 dark:text-navy-300"><?= $p['enrollment_date'] ?? '-' ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex gap-1" onclick="event.stopPropagation()">
                <a href="viewpatient.php?id=<?= $p['id'] ?>" class="btn size-7 rounded-full p-0 hover:bg-primary/10" title="View">
                  <svg class="size-4 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </a>
                <a href="assign_regimen.php?id=<?= $p['id'] ?>" class="btn size-7 rounded-full p-0 hover:bg-warning/10" title="Regimen">
                  <svg class="size-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3" /></svg>
                </a>
              </div>
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
        <a href="patients.php?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>" class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600">‹</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="patients.php?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>" class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="patients.php?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= $status ?>" class="btn size-8 rounded-lg p-0 bg-slate-100 text-sm hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600">›</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'clinician_footer.php'; ?>