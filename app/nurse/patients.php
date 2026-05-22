<?php
session_start();
 $required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

 $pageTitle = 'My Patients - GxAlert';

 $search  = trim($_GET['q'] ?? '');
 $status  = trim($_GET['status'] ?? '');

 $patients = getNursePatients($conn, $nurse_id);

// Apply client-side filters (list is small enough)
if ($search !== '') {
    $patients = array_filter($patients, function($p) use ($search) {
        return stripos($p['full_name'], $search) !== false 
            || stripos($p['patient_code'], $search) !== false;
    });
}
if ($status !== '') {
    $patients = array_filter($patients, fn($p) => $p['treatment_status'] === $status);
}

 $status_labels = [
    'enrolled' => 'Enrolled', 'on_treatment' => 'On Treatment',
    'completed' => 'Completed', 'cured' => 'Cured',
    'failed' => 'Failed', 'died' => 'Died',
    'lost_to_followup' => 'Lost to FU', 'transferred_out' => 'Transferred Out',
];
 $status_colors = [
    'enrolled' => 'bg-info/10 text-info', 'on_treatment' => 'bg-success/10 text-success',
    'completed' => 'bg-primary/10 text-primary', 'cured' => 'bg-success text-white',
    'failed' => 'bg-error/10 text-error', 'died' => 'bg-slate-500 text-white',
    'lost_to_followup' => 'bg-warning/10 text-warning', 'transferred_out' => 'bg-secondary/10 text-secondary',
];
?>
<?php require_once 'nurse_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">My Patients (<?= count($patients) ?>)</h1>
  </div>

  <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1 max-w-md">
      <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or code..."
             class="form-input w-full rounded-lg bg-slate-150 py-2 pl-9 pr-3 text-sm dark:bg-navy-900/90">
    </div>
    <select name="status" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90 min-w-[160px]">
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
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">FACILITY</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ENROLLED</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">STATUS</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($patients as $p): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex items-center space-x-3">
                <div class="avatar size-9">
                  <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
                    <?= strtoupper(substr($p['full_name'], 0, 2)) ?>
                  </div>
                </div>
                <div>
                  <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($p['full_name']) ?></span>
                  <p class="text-xs text-slate-400"><?= htmlspecialchars($p['patient_code']) ?> · <?= ucfirst($p['gender']) ?></p>
                </div>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($p['facility_name'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-300"><?= $p['enrollment_date'] ? date('M j, Y', strtotime($p['enrollment_date'])) : '-' ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?= $status_colors[$p['treatment_status']] ?? 'bg-slate-100 text-slate-500' ?>">
                <?= $status_labels[$p['treatment_status']] ?? $p['treatment_status'] ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <a href="log_adherence.php?patient_id=<?= $p['id'] ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20" title="Log adherence">
                <svg class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
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

<?php require_once 'nurse_footer.php'; ?>