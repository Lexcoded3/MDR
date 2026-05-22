<?php
session_start();
 $required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

 $pageTitle = 'Low Adherence - GxAlert';

 $loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
 $loc_stmt->bind_param("i", $doctor_id);
 $loc_stmt->execute();
 $doc_loc = $loc_stmt->get_result()->fetch_column();
 $like = "%$doc_loc%";

 $adh_stmt = $conn->prepare("
    SELECT t.*, p.full_name, p.patient_code, p.phone, p.treatment_status,
           tr.regimen_name
    FROM (
        SELECT patient_id,
               ROUND((SUM(status IN ('taken','late')) / COUNT(*)) * 100, 1) AS adh_pct,
               SUM(status = 'taken') AS taken,
               SUM(status = 'late') AS late,
               SUM(status = 'missed') AS missed,
               COUNT(*) AS total
        FROM adherence_logs
        WHERE dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY patient_id
        HAVING adh_pct < 85
    ) t
    JOIN patients p ON t.patient_id = p.id
    LEFT JOIN treatment_regimens tr ON p.id = tr.patient_id AND tr.status = 'active'
    WHERE p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY t.adh_pct ASC
");
 $adh_stmt->bind_param("s", $like);
 $adh_stmt->execute();
 $patients = $adh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Low Adherence Patients</h1>
    <p class="mt-1 text-sm text-slate-500">Patients below 85% adherence in the last 30 days — require clinical intervention.</p>
  </div>

  <?php if (empty($patients)): ?>
  <div class="card mt-6 p-12 text-center text-slate-400">
    <svg class="mx-auto size-12 mb-3 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-lg font-medium">All patients have good adherence</p>
  </div>
  <?php else: ?>
  <div class="card mt-4">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead><tr>
          <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENT</th>
          <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">REGIMEN</th>
          <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TOTAL</th>
          <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TAKEN</th>
          <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LATE</th>
          <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">MISSED</th>
          <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ADHERENCE</th>
        </tr></thead>
        <tbody>
          <?php foreach ($patients as $p): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-3">
              <a href="viewpatient.php?id=<?= $p['patient_id'] ?>" class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent"><?= htmlspecialchars($p['full_name']) ?></a>
              <p class="text-xs text-slate-400"><?= $p['patient_code'] ?> · <?= $p['phone'] ?></p>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($p['regimen_name'] ?? 'None') ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= $p['total'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-success"><?= $p['taken'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-warning"><?= $p['late'] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-error"><?= $p['missed'] ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-error/10 text-error"><?= $p['adh_pct'] ?>%</span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php require_once 'doctor_footer.php'; ?>