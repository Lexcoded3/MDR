<?php
session_start();
 $required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

 $pageTitle = 'Lab Dashboard - GxAlert';
 $stats = getLabStats($conn, $lab_id);

// Recent results uploaded
 $recent_stmt = $conn->prepare("
    SELECT lr.*, p.full_name, p.patient_code
    FROM lab_results lr
    LEFT JOIN patients p ON lr.patient_id = p.id
    WHERE lr.uploaded_by = ?
    ORDER BY lr.created_at DESC LIMIT 8
");
 $recent_stmt->bind_param("i", $lab_id);
 $recent_stmt->execute();
 $recent_results = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent DST results with resistance findings
 $resist_stmt = $conn->prepare("
    SELECT ds.result_date, d.drug_name, d.drug_code, d.drug_group, p.full_name, p.patient_code,
           ds.test_method, ds.result
    FROM drug_susceptibility ds
    JOIN drugs d ON ds.drug_id = d.id
    JOIN patients p ON ds.patient_id = p.id
    WHERE ds.performed_by = ? AND ds.result = 'resistant'
    ORDER BY ds.created_at DESC LIMIT 10
");
 $resist_stmt->bind_param("i", $lab_id);
 $resist_stmt->execute();
 $resistant_findings = $resist_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Common test types for quick-upload buttons
 $common_tests = [
    'Sputum for AFB Smear',
    'Sputum for GeneXpert MTB/RIF',
    'Sputum for Culture (MGIT)',
    'Sputum for LPA',
    'Liver Function Tests',
    'Renal Function Tests',
    'Chest X-Ray',
    'CD4 Count',
    'Viral Load',
    'Hemoglobin',
];
?>
<?php require_once 'lab_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">

  <!-- Welcome -->
  <div class="card col-span-12 mt-12 bg-gradient-to-r from-info to-cyan-600 p-5 sm:col-span-8 sm:mt-0 sm:flex-row">
    <div class="flex justify-center sm:order-last">
      <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/doctor.svg" alt="image">
    </div>
    <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
      <h3 class="text-xl">Good morning, <span class="font-semibold"><?= htmlspecialchars($lab_name); ?></span></h3>
      <p class="mt-2 leading-relaxed text-white/80">You have uploaded <?= $stats['today_results'] ?> result<?= $stats['today_results'] !== 1 ? 's' : '' ?> and <?= $stats['today_dst'] ?> DST test<?= $stats['today_dst'] !== 1 ? 's' : '' ?> today.</p>
      <?php if ($stats['preliminary'] > 0): ?>
      <p class="mt-1 text-sm text-yellow-200">⚠ You have <?= $stats['preliminary'] ?> preliminary result<?= $stats['preliminary'] !== 1 ? 's' : '' ?> pending finalization.</p>
      <?php endif; ?>
      <div class="mt-5 flex flex-wrap gap-2 justify-center sm:justify-start">
        <button onclick="location.href='upload_result.php'" class="btn border border-white/10 bg-white/20 text-white hover:bg-white/30">Upload Result</button>
        <button onclick="location.href='drug_susceptibility.php'" class="btn border border-white/10 bg-white/20 text-white hover:bg-white/30">Drug Susceptibility</button>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 sm:mt-5">
    <div class="card border-t-4 border-t-info p-4 text-center">
      <div class="text-3xl font-bold text-info"><?= $stats['today_results'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Results Today</p>
    </div>
    <div class="card border-t-4 border-t-warning p-4 text-center">
      <div class="text-3xl font-bold text-warning"><?= $stats['preliminary'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Preliminary</p>
    </div>
    <div class="card border-t-4 border-t-primary p-4 text-center">
      <div class="text-3xl font-bold text-primary dark:text-accent-light"><?= $stats['today_dst'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">DST Tests Today</p>
    </div>
    <div class="card border-t-4 border-t-error p-4 text-center">
      <div class="text-3xl font-bold text-error"><?= $stats['resistant_30d'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Resistant (30d)</p>
    </div>
  </div>

  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
    <!-- Left Column -->
    <div class="col-span-12 lg:col-span-8">

      <!-- Quick Upload -->
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Quick Upload</h2>
      <div class="card mb-5">
        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3 lg:grid-cols-5">
          <?php foreach ($common_tests as $test): ?>
          <a href="upload_result.php?test=<?= urlencode($test) ?>" 
             class="flex items-center space-x-2 rounded-lg border border-slate-200 dark:border-navy-600 px-3 py-2 text-xs text-slate-600 hover:bg-primary/5 hover:border-primary hover:text-primary dark:text-navy-200 dark:hover:bg-accent/5 dark:hover:border-accent dark:hover:text-accent-light transition-colors">
            <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span class="truncate"><?= htmlspecialchars($test) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Results -->
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Recent Results</h2>
      <div class="card">
        <div class="is-scrollbar-hidden overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patient</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Test</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Specimen</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Date</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_results)): ?>
              <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-navy-300">No results uploaded yet</td></tr>
              <?php endif; ?>
              <?php foreach ($recent_results as $r): ?>
              <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($r['full_name'] ?? 'N/A') ?></span>
                  <span class="ml-1 text-[10px] font-mono text-slate-400"><?= $r['patient_code'] ?? '' ?></span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($r['test_type']) ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-300"><?= ucfirst($r['specimen_type'] ?? '-') ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-200"><?= $r['result_date'] ?? '-' ?></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <?php if ($r['is_final']): ?>
                  <span class="rounded-full bg-success/10 px-2.5 py-0.5 text-[10px] font-semibold text-success">Final</span>
                  <?php else: ?>
                  <span class="rounded-full bg-warning/10 px-2.5 py-0.5 text-[10px] font-semibold text-warning">Preliminary</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column: Resistance Alerts -->
    <div class="col-span-12 lg:col-span-4">
      <h2 class="text-base font-medium text-error mb-3">Resistance Findings</h2>
      <?php if (empty($resistant_findings)): ?>
      <div class="card p-6 text-center">
        <svg class="mx-auto size-10 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <p class="mt-2 text-sm text-slate-400 dark:text-navy-300">No recent resistance findings</p>
      </div>
      <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($resistant_findings as $rf):
            $grp_cls = ['group_a'=>'border-l-error bg-error/5','group_b'=>'border-l-warning bg-warning/5','group_c'=>'border-l-info bg-info/5','group_d1'=>'border-l-secondary bg-secondary/5','group_d2'=>'border-l-slate-400 bg-slate-100 dark:bg-navy-700'][($rf['drug_group'] ?? '')] ?? 'border-l-slate-300 bg-slate-50 dark:bg-navy-800';
        ?>
        <div class="card border-l-4 <?= $grp_cls ?> p-3">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-error"><?= htmlspecialchars($rf['drug_name']) ?></p>
              <p class="text-[10px] text-slate-400 dark:text-navy-300"><?= $rf['drug_code'] ?> · <?= $rf['test_method'] ?></p>
            </div>
          </div>
          <p class="mt-1 text-xs text-slate-500 dark:text-navy-200"><?= htmlspecialchars($rf['full_name']) ?> · <?= $rf['patient_code'] ?></p>
          <p class="text-[10px] text-slate-400 dark:text-navy-300"><?= $rf['result_date'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
 $notify_text = 'success';
require_once 'lab_footer.php'; ?>