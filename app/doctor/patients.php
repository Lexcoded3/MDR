<?php
session_start();
 $required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

 $pageTitle = 'Patients - GxAlert';

 $search = trim($_GET['q'] ?? '');
 $status = trim($_GET['status'] ?? '');

 $loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
 $loc_stmt->bind_param("i", $doctor_id);
 $loc_stmt->execute();
 $doc_loc = $loc_stmt->get_result()->fetch_column();
 $like = "%$doc_loc%";

 $where = ["p.is_active = 1", "p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))"];
 $params = [$like]; $types = "s";

if ($search !== '') {
    $where[] = "(p.full_name LIKE ? OR p.patient_code LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $types .= "ss";
}
if ($status !== '') {
    $where[] = "p.treatment_status = ?";
    $params[] = $status; $types .= "s";
}
 $where_sql = implode(' AND ', $where);

 $patients_stmt = $conn->prepare("
    SELECT p.*, f.name AS facility_name,
           (SELECT COUNT(*) FROM adverse_events ae WHERE ae.patient_id = p.id AND ae.resolution_date IS NULL) AS active_ae_count,
           (SELECT ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) 
            FROM adherence_logs WHERE patient_id = p.id AND dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS adherence_30d
    FROM patients p
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE $where_sql ORDER BY p.created_at DESC
");
 $patients_stmt->bind_param($types, ...$params);
 $patients_stmt->execute();
 $patients = $patients_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $status_labels = [
    'enrolled' => 'Enrolled', 'on_treatment' => 'On Treatment',
    'completed' => 'Completed', 'cured' => 'Cured',
    'failed' => 'Failed', 'died' => 'Died',
    'lost_to_followup' => 'Lost to FU', 'transferred_out' => 'Transferred',
];
 $status_colors = [
    'enrolled' => 'bg-info/10 text-info', 'on_treatment' => 'bg-success/10 text-success',
    'completed' => 'bg-primary/10 text-primary', 'cured' => 'bg-success text-white',
    'failed' => 'bg-error/10 text-error', 'died' => 'bg-slate-500 text-white',
    'lost_to_followup' => 'bg-warning/10 text-warning', 'transferred_out' => 'bg-secondary/10 text-secondary',
];
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Patients (<?= count($patients) ?>)</h1>
  </div>

  <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1 max-w-md">
      <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search..."
             class="form-input w-full rounded-lg bg-slate-150 py-2 pl-9 pr-3 text-sm dark:bg-navy-900/90">
    </div>
    <select name="status" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      <option value="">All Status</option>
      <?php foreach ($status_labels as $k => $v): ?>
      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Filter</button>
    <?php if ($search || $status): ?>
    <a href="patients.php" class="btn h-9 border border-slate-300 text-slate-600 dark:border-navy-500 dark:text-navy-200 px-4">Clear</a>
    <?php endif; ?>
  </form>

  <div class="card mt-4">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENT</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">STATUS</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ADHERENCE</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">AE</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($patients as $p): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="avatar size-9">
                  <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
                    <?= strtoupper(substr($p['full_name'], 0, 2)) ?>
                  </div>
                </div>
                <div>
                  <a href="viewpatient.php?id=<?= $p['id'] ?>" class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent">
                    <?= htmlspecialchars($p['full_name']) ?>
                  </a>
                  <p class="text-xs text-slate-400"><?= $p['patient_code'] ?></p>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?= $status_colors[$p['treatment_status']] ?? '' ?>">
                <?= $status_labels[$p['treatment_status']] ?? $p['treatment_status'] ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($p['adherence_30d'] !== null): ?>
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                <?= $p['adherence_30d'] >= 95 ? 'bg-success/10 text-success' : ($p['adherence_30d'] >= 85 ? 'bg-warning/10 text-warning' : 'bg-error/10 text-error') ?>">
                <?= $p['adherence_30d'] ?>%
              </span>
              <?php else: ?>
              <span class="text-xs text-slate-400">N/A</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <?php if ($p['active_ae_count'] > 0): ?>
              <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-error/10 text-error">
                <?= $p['active_ae_count'] ?>
              </span>
              <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <a href="viewpatient.php?id=<?= $p['id'] ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20">
                <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($patients)): ?>
          <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No patients found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php require_once 'doctor_footer.php'; ?>