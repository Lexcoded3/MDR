<?php
session_start();
$required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

$pageTitle   = 'Lab Review - GxAlert';
$notify_text = '';

$loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
$loc_stmt->bind_param("i", $doctor_id);
$loc_stmt->execute();
$doc_loc = $loc_stmt->get_result()->fetch_column();
$loc_stmt->close();
$like = "%$doc_loc%";

// Handle approve final
if (isset($_GET['approve'])) {
    $lid  = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE lab_results SET is_final = 1, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND is_final = 0");
    $stmt->bind_param("ii", $doctor_id, $lid);
    $stmt->execute();
    $stmt->close();
    $notify_text = 'Result approved as final';
    header("Location: lab_review.php?status=$notify_text");
    exit;
}

// Preliminary results needing review
$lab_stmt = $conn->prepare("
    SELECT lr.*, p.full_name, p.patient_code, u.name AS uploaded_by_name
    FROM lab_results lr
    JOIN patients p ON lr.patient_id = p.id
    LEFT JOIN users u ON lr.uploaded_by = u.id
    WHERE lr.is_final = 0
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY lr.result_date DESC
");
$lab_stmt->bind_param("s", $like);
$lab_stmt->execute();
$preliminary = $lab_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lab_stmt->close();

// Recent final results
$final_stmt = $conn->prepare("
    SELECT lr.*, p.full_name, p.patient_code
    FROM lab_results lr
    JOIN patients p ON lr.patient_id = p.id
    WHERE lr.is_final = 1
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY lr.reviewed_at DESC
    LIMIT 15
");
$final_stmt->bind_param("s", $like);
$final_stmt->execute();
$final = $final_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$final_stmt->close();
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4"><h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Lab Results Review</h1></div>

  <h2 class="mt-6 mb-3 text-sm font-semibold uppercase tracking-wide text-warning">Preliminary — Needs Review (<?= count($preliminary) ?>)</h2>
  <?php if (empty($preliminary)): ?>
  <div class="card p-6 text-center text-slate-400">No preliminary results pending review.</div>
  <?php else: ?>
  <div class="space-y-3">
    <?php foreach ($preliminary as $l): ?>
    <div class="card p-4 flex flex-col sm:flex-row sm:items-center gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($l['full_name']) ?></span>
          <span class="text-xs text-slate-400"><?= $l['patient_code'] ?></span>
        </div>
        <p class="text-sm text-slate-600 dark:text-navy-200 mt-0.5"><?= htmlspecialchars($l['test_type']) ?> — <?= date('M j, Y', strtotime($l['result_date'])) ?></p>
        <p class="text-sm text-slate-700 dark:text-navy-100 mt-1"><?= htmlspecialchars($l['result']) ?></p>
        <p class="text-xs text-slate-400 mt-0.5">Uploaded by <?= htmlspecialchars($l['uploaded_by_name'] ?? '') ?> on <?= $l['created_at'] ?></p>
      </div>
      <a href="lab_review.php?approve=<?= $l['id'] ?>" class="btn h-9 bg-success text-white hover:bg-success/90 px-5 text-xs self-start">Approve Final</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($final)): ?>
  <h2 class="mt-8 mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Final Results (Recent)</h2>
  <div class="card">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead><tr>
          <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">PATIENT</th>
          <th class="whitespace-nowrap bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">TEST</th>
          <th class="whitespace-nowrap bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">RESULT</th>
          <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">DATE</th>
        </tr></thead>
        <tbody>
          <?php foreach ($final as $l): ?>
          <tr class="border-y border-transparent border-b-slate-100 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($l['full_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($l['test_type']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars(mb_substr($l['result'], 0, 50)) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500"><?= date('M j, Y', strtotime($l['result_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php $notify_variant = 'success'; require_once 'doctor_footer.php'; ?>