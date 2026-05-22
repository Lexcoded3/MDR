<?php
session_start();
$required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

$pageTitle = 'Lab Results - GxAlert';

// Lab results
$lab_stmt = $conn->prepare("
    SELECT lr.*, u.name AS uploaded_by_name
    FROM lab_results lr
    LEFT JOIN users u ON lr.uploaded_by = u.id
    WHERE lr.patient_id = ?
    ORDER BY lr.result_date DESC, lr.created_at DESC
");
$lab_stmt->bind_param("i", $patient_id);
$lab_stmt->execute();
$lab_results = $lab_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lab_stmt->close();

// Drug susceptibility
$ds_stmt = $conn->prepare("
    SELECT ds.*, d.drug_name, d.drug_code, d.drug_group, u.name AS performed_by_name
    FROM drug_susceptibility ds
    JOIN drugs d ON ds.drug_id = d.id
    LEFT JOIN users u ON ds.performed_by = u.id
    WHERE ds.patient_id = ?
    ORDER BY ds.result_date DESC, FIELD(d.drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other')
");
$ds_stmt->bind_param("i", $patient_id);
$ds_stmt->execute();
$ds_results = $ds_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ds_stmt->close(); 

// Group susceptibility by test date
$ds_grouped = [];
foreach ($ds_results as $ds) {
    $key = $ds['result_date'] ?? 'unknown';
    if (!isset($ds_grouped[$key])) {
        $ds_grouped[$key] = [
            'date'      => $key,
            'method'    => $ds['test_method'],
            'facility'  => $ds['lab_facility'],
            'results'   => [],
        ];
    }
    $ds_grouped[$key]['results'][] = $ds;
}
$ds_grouped = array_values($ds_grouped);

$ds_status_colors = [
    'sensitive'    => 'bg-success/10 text-success',
    'resistant'    => 'bg-error/10 text-error',
    'indeterminate'=> 'bg-warning/10 text-warning',
    'not_done'     => 'bg-slate-200 text-slate-500 dark:bg-navy-600 dark:text-navy-300',
];
?>
<?php require_once 'patient_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-1">My Lab Results</h1>
  <p class="text-sm text-slate-400 dark:text-navy-300 mb-5"><?= count($lab_results) ?> results · <?= count($ds_results) ?> susceptibility tests</p>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5">
    
    <!-- Left Column: Drug Susceptibility Results -->
    <div>
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Drug Susceptibility</h2>
      
      <?php if (!empty($ds_grouped)): ?>
      <div class="space-y-4">
        <?php foreach ($ds_grouped as $group): ?>
        <div class="card mt-5">
          <div class="flex flex-wrap items-center justify-between border-b border-slate-150 dark:border-navy-600 px-5 py-3">
            <div class="flex items-center space-x-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
              </svg>
              <div>
                <p class="font-medium text-slate-700 dark:text-navy-100"><?= $group['date'] ?></p>
                <p class="text-xs text-slate-400 dark:text-navy-300">
                  <?= str_replace('_', ' ', strtoupper($group['method'])) ?>
                  <?php if ($group['facility']): ?> · <?= htmlspecialchars($group['facility']) ?><?php endif; ?>
                </p>
              </div>
            </div>
          </div>
          <div class="is-scrollbar-hidden overflow-x-auto">
            <table class="is-hoverable w-full text-left">
              <thead>
                <tr>
                  <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Drug</th>
                  <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Code</th>
                  <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Result</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($group['results'] as $r):
                    $badge = $ds_status_colors[$r['result']] ?? $ds_status_colors['not_done'];
                ?>
                <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
                  <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100">
                    <?= htmlspecialchars($r['drug_name']) ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-400 dark:text-navy-300">
                    <?= $r['drug_code'] ?>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase <?= $badge ?>">
                      <?= str_replace('_', ' ', $r['result']) ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="card p-8 text-center mt-5">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-12 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
        </svg>
        <h3 class="mt-3 text-sm font-medium text-slate-600 dark:text-navy-200">No Susceptibility Tests</h3>
        <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Results will appear here once available.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: General Lab Results -->
    <div>
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Test Results</h2>
      
      <?php if (!empty($lab_results)): ?>
      <div class="space-y-3">
        <?php foreach ($lab_results as $lr): ?>
        <div class="card p-4 mt-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start space-x-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($lr['test_type']) ?></h3>
                <p class="text-xs text-slate-400 dark:text-navy-300 mt-0.5">
                  <?= $lr['result_date'] ?> <?php if ($lr['specimen_type']): ?> · <?= ucfirst($lr['specimen_type']) ?><?php endif; ?>
                </p>
              </div>
            </div>
            <?php if ($lr['is_final']): ?>
            <span class="rounded-full bg-success/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-success">Final</span>
            <?php else: ?>
            <span class="rounded-full bg-warning/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-warning">Preliminary</span>
            <?php endif; ?>
          </div>
          <?php if ($lr['result']): ?>
          <div class="mt-3 rounded-lg bg-slate-100 dark:bg-navy-800 p-3">
            <p class="text-sm text-slate-600 dark:text-navy-200 whitespace-pre-wrap"><?= htmlspecialchars($lr['result']) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($lr['uploaded_by_name']): ?>
          <p class="mt-2 text-[10px] text-slate-400 dark:text-navy-300">Uploaded by <?= htmlspecialchars($lr['uploaded_by_name']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="card p-8 text-center mt-5">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-12 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
        <h3 class="mt-3 text-sm font-medium text-slate-600 dark:text-navy-200">No Lab Results Yet</h3>
        <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Results will appear here once uploaded.</p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once 'patient_footer.php'; ?>