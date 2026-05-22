<?php
session_start();
 $required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

 $pageTitle = 'Doctor Dashboard - GxAlert';


 $loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
 $loc_stmt->bind_param("i", $doctor_id);
 $loc_stmt->execute();
 $doc_loc = $loc_stmt->get_result()->fetch_column();
 $like = "%$doc_loc%";
 $stats = getDoctorStats($conn, $doctor_id);
// Pending regimen reviews (detailed)
$reg_stmt = $conn->prepare("
    SELECT 
        tr.id, tr.patient_id, tr.regimen_name, tr.status, tr.notes,
        tr.start_date, tr.end_date, tr.created_at, tr.prescribed_by,
        p.full_name, p.patient_code, p.gender, p.date_of_birth,
        u.name AS prescribed_by_name,
        GROUP_CONCAT(CONCAT(d.drug_code, ' ', rd.dose_mg, 'mg') SEPARATOR ', ') AS drug_summary
    FROM treatment_regimens tr
    JOIN patients p ON tr.patient_id = p.id
    LEFT JOIN users u ON tr.prescribed_by = u.id
    LEFT JOIN regimen_drugs rd ON tr.id = rd.regimen_id AND rd.is_active = 1
    LEFT JOIN drugs d ON rd.drug_id = d.id
    WHERE tr.status = 'pending_review'
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    GROUP BY 
        tr.id, tr.patient_id, tr.regimen_name, tr.status, tr.notes,
        tr.start_date, tr.end_date, tr.created_at, tr.prescribed_by,
        p.full_name, p.patient_code, p.gender, p.date_of_birth,
        u.name
    ORDER BY tr.created_at DESC
    LIMIT 5
");
$reg_stmt->bind_param("s", $doc_loc);
$reg_stmt->execute();
$pending_regimens = $reg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Critical adverse events
$ae_stmt = $conn->prepare("
    SELECT ae.*, 
           p.full_name, p.patient_code,
           d.drug_name AS suspected_drug_name
    FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    LEFT JOIN drugs d ON ae.suspected_drug_id = d.id
    WHERE ae.resolution_date IS NULL 
    AND ae.severity IN ('severe', 'life_threatening')
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY ae.onset_date DESC
");
 $ae_stmt->bind_param("s", $like);
 $ae_stmt->execute();
 $critical_aes = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Low adherence patients
 $adh_stmt = $conn->prepare("
    SELECT t.patient_id, p.full_name, p.patient_code, t.adh_pct, t.taken, t.total
    FROM (
        SELECT patient_id,
               ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS adh_pct,
               SUM(status = 'taken') AS taken,
               COUNT(*) AS total
        FROM adherence_logs
        WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY patient_id
        HAVING adh_pct < 85
    ) t
    JOIN patients p ON t.patient_id = p.id
    WHERE p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY t.adh_pct ASC 
    LIMIT 10
");
 $adh_stmt->bind_param("s", $like);
 $adh_stmt->execute();
 $low_adh_patients = $adh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $severity_colors = [
    'mild' => 'bg-info/10 text-info',
    'moderate' => 'bg-warning/10 text-warning',
    'severe' => 'bg-error/10 text-error',
    'life_threatening' => 'bg-error text-white',
];
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">

    <!-- ── Row 1: Welcome + Stat Cards ── -->
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">

      <!-- Welcome Banner -->
      <div class="col-span-12 lg:col-span-8">
        <div class="card bg-gradient-to-r from-primary to-blue-600 p-6 flex flex-col sm:flex-row items-center gap-6">
          <img class="h-24 shrink-0" src="../images/illustrations/doctor.svg" alt="doctor">
          <div class="flex-1 text-center sm:text-left text-white">
            <h3 class="text-xl font-light">
              Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>,
              <span class="font-semibold">Dr. <?= htmlspecialchars($doctor_name) ?></span>
            </h3>
            <p class="mt-1 text-sm text-blue-100"><?= $stats['location'] ?> &mdash; Clinical Oversight</p>
            <?php if ($stats['pending_reviews'] > 0 || $stats['critical_ae'] > 0): ?>
            <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-2">
              <?php if ($stats['pending_reviews'] > 0): ?>
              <a href="regimen_reviews.php"
                 class="btn border border-white/20 bg-white/20 text-white hover:bg-white/30 text-sm">
                <i class="fa fa-clock mr-1.5 text-xs"></i><?= $stats['pending_reviews'] ?> Regimen(s) Pending Review
              </a>
              <?php endif; ?>
              <?php if ($stats['critical_ae'] > 0): ?>
              <a href="adverse_events.php"
                 class="btn border border-white/10 bg-error/30 text-white hover:bg-error/40 text-sm">
                <i class="fa fa-triangle-exclamation mr-1.5 text-xs"></i><?= $stats['critical_ae'] ?> Critical AE(s)
              </a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Stat Cards (2×2) -->
      <div class="col-span-12 lg:col-span-4 grid grid-cols-2 gap-4">

        <div class="card p-4 flex items-center gap-3">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success/10">
            <svg class="size-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-slate-400">On Treatment</p>
            <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= $stats['on_treatment'] ?></p>
          </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/10">
            <svg class="size-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-slate-400">Pending Reviews</p>
            <p class="text-2xl font-semibold <?= $stats['pending_reviews'] > 0 ? 'text-warning' : 'text-slate-700 dark:text-navy-100' ?>">
              <?= $stats['pending_reviews'] ?>
            </p>
          </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-error/10">
            <svg class="size-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-slate-400">Low Adherence</p>
            <p class="text-2xl font-semibold <?= $stats['low_adherence'] > 0 ? 'text-error' : 'text-slate-700 dark:text-navy-100' ?>">
              <?= $stats['low_adherence'] ?>
            </p>
          </div>
        </div>

        <div class="card p-4 flex items-center gap-3">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-info/10">
            <svg class="size-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-slate-400">Pending Labs</p>
            <p class="text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= $stats['pending_labs'] ?></p>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Row 2: Pending Regimen Reviews + Critical AEs ── -->
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">

      <!-- Pending Regimen Reviews -->
      <div class="col-span-12 lg:col-span-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">
            Pending Regimen Reviews
            <?php if ($stats['pending_reviews'] > 0): ?>
            <span class="ml-2 inline-flex rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">
              <?= $stats['pending_reviews'] ?>
            </span>
            <?php endif; ?>
          </h2>
          <a href="regimen_reviews.php" class="text-xs text-primary dark:text-accent-light hover:underline">View All →</a>
        </div>

        <?php if (empty($pending_regimens)): ?>
        <div class="card p-10 text-center text-slate-400">
          <svg class="mx-auto size-10 mb-2 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm">No pending reviews — all clear!</p>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($pending_regimens as $r): ?>
          <div class="card p-4 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="avatar size-10 shrink-0">
                <div class="is-initial rounded-full bg-warning/10 text-xs uppercase text-warning">
                  <?= strtoupper(substr($r['full_name'] ?? 'NA', 0, 2)) ?>
                </div>
              </div>
              <div class="min-w-0">
                <p class="font-medium text-slate-700 dark:text-navy-100 truncate">
                  <?= htmlspecialchars($r['full_name'] ?? 'Unknown Patient') ?>
                </p>
                <p class="text-xs text-slate-400">
                  <?= htmlspecialchars($r['patient_code'] ?? 'N/A') ?> · <?= htmlspecialchars($r['regimen_name'] ?? 'No Regimen') ?>
                </p>
                <p class="text-xs text-slate-500 mt-0.5 truncate">
                  Drugs: <?= htmlspecialchars($r['drug_summary'] ?? 'None') ?>
                </p>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs text-slate-400">
                By <?= htmlspecialchars($r['prescribed_by_name'] ?? 'System') ?>
              </p>
              <p class="text-xs text-slate-400">
                <?= date('M j, H:i', strtotime($r['created_at'])) ?>
              </p>
            </div>
            <div class="flex gap-2 shrink-0">
              <a href="regimen_reviews.php?action=approve&id=<?= $r['id'] ?>"
                 class="btn h-8 bg-success/10 text-success hover:bg-success/20 px-4 text-xs">Approve</a>
              <a href="regimen_reviews.php?action=reject&id=<?= $r['id'] ?>"
                 class="btn h-8 bg-error/10 text-error hover:bg-error/20 px-4 text-xs">Reject</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Critical AEs -->
      <div class="col-span-12 lg:col-span-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">
            Critical AEs
            <?php if ($stats['critical_ae'] > 0): ?>
            <span class="ml-2 inline-flex rounded-full bg-error/10 px-2 py-0.5 text-xs font-medium text-error">
              <?= $stats['critical_ae'] ?>
            </span>
            <?php endif; ?>
          </h2>
          <a href="adverse_events.php" class="text-xs text-primary dark:text-accent-light hover:underline">All →</a>
        </div>

        <div class="space-y-3">
          <?php if (empty($critical_aes)): ?>
          <div class="card p-8 text-center text-slate-400">
            <svg class="mx-auto size-8 mb-1 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">No critical events</p>
          </div>
          <?php else: ?>
          <?php foreach (array_slice($critical_aes, 0, 5) as $ae): ?>
          <div class="card p-4 border-l-4 <?= $severity_colors[$ae['severity']] ?? '' ?>">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-700 dark:text-navy-100 truncate"><?= htmlspecialchars($ae['event_type']) ?></p>
                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($ae['full_name']) ?></p>
                <?php if ($ae['suspected_drug_name']): ?>
                <p class="text-xs text-error mt-1">Drug: <?= htmlspecialchars($ae['suspected_drug_name']) ?></p>
                <?php endif; ?>
              </div>
              <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium <?= $severity_colors[$ae['severity']] ?? '' ?>">
                <?= ucfirst(str_replace('_', ' ', $ae['severity'])) ?>
              </span>
            </div>
            <a href="adverse_events.php?manage=<?= $ae['id'] ?>"
               class="mt-2 inline-flex text-xs text-primary dark:text-accent-light hover:underline">Manage →</a>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- ── Row 3: Low Adherence Table ── -->
    <?php if (!empty($low_adh_patients)): ?>
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">
          Low Adherence
          <span class="text-slate-400 font-normal text-sm ml-1">(&lt;85% last 30 days)</span>
          <span class="ml-2 inline-flex rounded-full bg-error/10 px-2 py-0.5 text-xs font-medium text-error">
            <?= count($low_adh_patients) ?>
          </span>
        </h2>
        <a href="low_adherence.php" class="text-xs text-primary dark:text-accent-light hover:underline">Full Report →</a>
      </div>
      <div class="card">
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">Patient</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">Total Doses</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">Taken</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">Adherence</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($low_adh_patients as $p): ?>
              <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="viewpatient.php?id=<?= $p['patient_id'] ?>"
                     class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent">
                    <?= htmlspecialchars($p['full_name']) ?>
                  </a>
                  <p class="text-xs text-slate-400"><?= $p['patient_code'] ?></p>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= $p['total'] ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= $p['taken'] ?></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="h-1.5 w-16 rounded-full bg-slate-200 dark:bg-navy-500">
                      <div class="h-1.5 rounded-full bg-error" style="width:<?= $p['adh_pct'] ?>%"></div>
                    </div>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-error/10 text-error">
                      <?= $p['adh_pct'] ?>%
                    </span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once 'doctor_footer.php'; ?>