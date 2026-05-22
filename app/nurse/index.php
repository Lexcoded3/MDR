<?php
session_start();
$required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

$pageTitle = 'Nurse Dashboard - GxAlert';
$stats = getNurseStats($conn, $nurse_id);

// Today's dose schedule for all facility patients
$nurse_facility = $conn->prepare("SELECT location FROM users WHERE id = ?");
$nurse_facility->bind_param("i", $nurse_id);
$nurse_facility->execute();
$nurse_loc = $nurse_facility->get_result()->fetch_column();
$like = "%$nurse_loc%";

$today_doses = $conn->prepare("
    SELECT ms.*, p.full_name, p.patient_code, p.id AS patient_id, 
           d.drug_name, d.drug_code, d.default_dose_mg, d.unit,
           al.status AS log_status, al.actual_time_taken, al.notes AS log_notes
    FROM medication_schedule ms
    JOIN treatment_regimens tr ON ms.regimen_id = tr.id AND tr.status = 'active'
    JOIN patients p ON tr.patient_id = p.id
    JOIN drugs d ON ms.drug_id = d.id
    LEFT JOIN adherence_logs al ON al.patient_id = p.id
        AND al.schedule_id = ms.id
        AND al.dose_date = CURDATE()
    WHERE ms.is_active = 1
    AND p.is_active = 1 AND p.treatment_status = 'on_treatment'
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY ms.dose_time, p.full_name, d.drug_name
");
$today_doses->bind_param("s", $like);
$today_doses->execute();
$all_doses = $today_doses->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by time slot
$doses_by_time = [];
foreach ($all_doses as $dose) {
    $t = $dose['dose_time'];
    if (!isset($doses_by_time[$t])) $doses_by_time[$t] = [];
    $doses_by_time[$t][] = $dose;
}

// Active adverse events
// Active adverse events
$ae_stmt = $conn->prepare("
    SELECT 
        ae.*, 
        p.full_name, 
        p.patient_code,
        d.drug_name AS suspected_drug -- This creates the missing key
    FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    -- JOIN the drugs table to get the name
    LEFT JOIN drugs d ON ae.drug_id = d.id 
    WHERE ae.resolution_date IS NULL AND p.is_active = 1
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY ae.onset_date DESC
");
$ae_stmt->bind_param("s", $like);
$ae_stmt->execute();
$active_aes = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_badge = [
    'taken'  => 'bg-success/10 text-success',
    'late'   => 'bg-warning/10 text-warning',
    'missed' => 'bg-error/10 text-error',
];

$severity_colors = [
    'mild'   => 'bg-info/10 text-info',
    'moderate'=> 'bg-warning/10 text-warning',
    'severe' => 'bg-error/10 text-error',
    'life_threatening' => 'bg-error text-white animate-pulse',
];
?>
<?php require_once 'nurse_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">

    <!-- Welcome -->
    <div class="col-span-12 lg:col-span-8 xl:col-span-9">
      <div class="card bg-gradient-to-r from-success to-emerald-600 p-5 overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center justify-between">
          
          <div class="flex-1 pt-2 text-center text-white sm:text-left z-10">
            <h3 class="text-xl text-white">Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <span class="font-semibold"><?= htmlspecialchars($nurse_name) ?></span></h3>
            <p class="mt-2 leading-relaxed text-emerald-100"><?= $stats['my_facility'] ?> — Daily DOT Logging</p>
            <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-4 text-sm">
              <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-white/60"></span> <?= $stats['logged_today'] ?> logged</span>
              <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-warning"></span> <?= $stats['due_now'] ?> due</span>
              <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-error"></span> <?= $stats['missed_today'] ?> missed</span>
            </div>
            <a href="log_adherence.php" class="btn mt-6 border border-white/10 bg-white/20 text-white hover:bg-white/30 transition-all">
              Start DOT Logging
            </a>
          </div>

          <div class="hidden sm:block">
            <img 
              class="h-40 w-auto object-contain translate-x-4 translate-y-4 opacity-90" 
              src="../images/illustrations/doctor.svg" 
              alt="doctor illustration"
            >
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-span-12 lg:col-span-4 xl:col-span-3 grid grid-cols-2 lg:grid-cols-1 gap-4">
      <div class="card p-4 flex items-center justify-between">
        <div>
          <p class="text-xs text-slate-400">Active Patients</p>
          <p class="mt-1 text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= $stats['active_patients'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
          <svg class="size-5 text-primary dark:text-accent" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        </div>
      </div>
      <div class="card p-4 flex items-center justify-between">
        <div>
          <p class="text-xs text-slate-400">Logged Today</p>
          <p class="mt-1 text-2xl font-semibold text-success"><?= $stats['logged_today'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
          <svg class="size-5 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </div>
      </div>
      <div class="card p-4 flex items-center justify-between">
        <div>
          <p class="text-xs text-slate-400">Due Now</p>
          <p class="mt-1 text-2xl font-semibold text-warning"><?= $stats['due_now'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
          <svg class="size-5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
      </div>
      <div class="card p-4 flex items-center justify-between">
        <div>
          <p class="text-xs text-slate-400">Active Side Effects</p>
          <p class="mt-1 text-2xl font-semibold <?= $stats['active_ae'] > 0 ? 'text-error' : 'text-slate-700 dark:text-navy-100' ?>"><?= $stats['active_ae'] ?></p>
        </div>
        <div class="flex size-10 items-center justify-center rounded-lg bg-error/10">
          <svg class="size-5 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        </div>
      </div>
    </div>

    <!-- Today's Doses by Time Slot -->
    <div class="col-span-12 lg:col-span-8 xl:col-span-9">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">
          Today's Doses — <?= date('l, F j, Y') ?>
        </h2>
        <a href="log_dose.php?patient_id=<?= $dose['patient_id'] ?>&schedule_id=<?= $dose['id'] ?>&dose_time=<?= urlencode($dose['dose_time']) ?>&date=<?= date('Y-m-d') ?>" 
   class="btn size-7 rounded-full bg-success/10 p-0 text-success hover:bg-success/20 transition-colors">
   <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
     <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
   </svg>
</a>
      </div>

      <?php if (empty($doses_by_time)): ?>
      <div class="card p-12 text-center border-2 border-dashed border-slate-200">
          <div class="flex justify-center mb-4 text-slate-300">
              <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
          </div>
          <p class="text-slate-500 font-medium">All clear for today!</p>
          <p class="text-xs text-slate-400 mt-1">No scheduled doses found for your current facility.</p>
      </div>
      <?php else: ?>
      <?php foreach ($doses_by_time as $time => $doses): 
        $logged = count(array_filter($doses, fn($d) => $d['log_status'] !== null));
        $total = count($doses);
        $pct = $total > 0 ? round(($logged / $total) * 100) : 0;
        $is_past = $time < date('H:i:s');
        $is_current = !$is_past && strtotime($time) <= strtotime('+30 minutes');
      ?>
      <div class="card mt-3">
        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 dark:bg-navy-800 rounded-t-lg">
          <div class="flex items-center gap-3">
            <span class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= $time ?></span>
            <?php if ($is_current): ?>
            <span class="rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">NOW</span>
            <?php elseif ($is_past && $logged < $total): ?>
            <span class="rounded-full bg-error/10 px-2 py-0.5 text-xs font-medium text-error">OVERDUE</span>
            <?php elseif ($logged === $total): ?>
            <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success">COMPLETE</span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500"><?= $logged ?>/<?= $total ?></span>
            <div class="h-1.5 w-20 rounded-full bg-slate-200 dark:bg-navy-600">
              <div class="h-full rounded-full <?= $pct === 100 ? 'bg-success' : ($is_past ? 'bg-error' : 'bg-primary dark:bg-accent') ?>" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
        </div>
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-2 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">PATIENT</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-2 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">DRUG</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-2 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">DOSE</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-2 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">STATUS</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-2 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($doses as $d): ?>
              <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                <td class="whitespace-nowrap px-4 py-2.5">
                  <div>
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($d['full_name']) ?></span>
                    <p class="text-xs text-slate-400"><?= htmlspecialchars($d['patient_code']) ?></p>
                  </div>
                </td>
                <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($d['drug_name']) ?></td>
                <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= $d['default_dose_mg'] ?> <?= $d['unit'] ?></td>
                <td class="whitespace-nowrap px-4 py-2.5">
                  <?php if ($d['log_status']): ?>
                  <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $status_badge[$d['log_status']] ?? 'bg-slate-100 text-slate-500' ?>">
                    <?= ucfirst($d['log_status']) ?>
                    <?php if ($d['log_status'] === 'late' && $d['actual_time']): ?>
                    (<?= $d['actual_time'] ?>)
                    <?php endif; ?>
                  </span>
                  <?php else: ?>
                  <span class="text-xs text-slate-400">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="whitespace-nowrap px-4 py-2.5">
                  <?php if (!$d['log_status']): ?>
                  <a href="log_dose.php?patient_id=<?= $d['patient_id'] ?>&drug_id=<?= $d['drug_id'] ?>&dose_time=<?= urlencode($d['dose_time']) ?>&date=<?= date('Y-m-d') ?>" 
                     class="btn size-7 rounded-full bg-success/10 p-0 text-success hover:bg-success/20">
                    <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                  </a>
                  <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Side Effects Alerts -->
    <div class="col-span-12 lg:col-span-4 xl:col-span-3">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Side Effects</h2>
        <a href="side_effects.php" class="text-xs text-primary dark:text-accent-light hover:underline">View All</a>
      </div>
      <div class="space-y-3">
        <?php if (empty($active_aes)): ?>
        <div class="card p-6 text-center text-slate-400">
          <svg class="mx-auto size-8 mb-2 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p class="text-sm">No active side effects</p>
        </div>
        <?php else: ?>
        <?php foreach (array_slice($active_aes, 0, 5) as $ae): ?>
        <div class="card p-4 border-l-4 <?= ($severity_colors[$ae['severity']] ?? 'bg-slate-100 text-slate-500') ?>">
          <div class="flex items-start justify-between">
            <div>
              <h3 class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']) ?></h3>
              <p class="text-xs text-slate-400"><?= htmlspecialchars($ae['full_name']) ?> — <?= $ae['patient_code'] ?></p>
            </div>
            <span class="rounded-full px-1.5 py-0.5 text-xs font-medium <?= $severity_colors[$ae['severity']] ?? '' ?>">
              <?= ucfirst($ae['severity']) ?>
            </span>
          </div>
          <p class="mt-1 text-xs text-slate-500">Since: <?= date('M j', strtotime($ae['onset_date'])) ?></p>
          <?php if ($ae['suspected_drug']): ?>
          <p class="text-xs text-error">Drug: <?= htmlspecialchars($ae['suspected_drug']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>
<?php require_once 'nurse_footer.php'; ?>