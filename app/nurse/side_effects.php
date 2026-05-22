<?php
session_start();
 $required_role = 'nurse';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'nurse_init.php';

 $pageTitle = 'Side Effects - GxAlert';

 $severity_colors = [
    'mild'            => 'bg-info/10 text-info',
    'moderate'        => 'bg-warning/10 text-warning',
    'severe'          => 'bg-error/10 text-error',
    'life_threatening'=> 'bg-error text-white',
];

 $severity_labels = [
    'mild' => 'Mild', 'moderate' => 'Moderate', 'severe' => 'Severe', 'life_threatening' => 'Life-threatening',
];

 $nurse_facility = $conn->prepare("SELECT location FROM users WHERE id = ?");
 $nurse_facility->bind_param("i", $nurse_id);
 $nurse_facility->execute();
 $nurse_loc = $nurse_facility->get_result()->fetch_column();
 $like = "%$nurse_loc%";

 $ae_stmt = $conn->prepare("
    SELECT ae.*, p.full_name, p.patient_code,
           d.drug_name AS suspected_drug_name,
           u.name AS reported_by_name
    FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    LEFT JOIN drugs d ON ae.suspected_drug_id = d.id
    LEFT JOIN users u ON ae.reported_by = u.id
    WHERE p.is_active = 1
    AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY ae.resolution_date IS NOT NULL, ae.onset_date DESC
");
 $ae_stmt->bind_param("s", $like);
 $ae_stmt->execute();
 $all_aes = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $active_aes = array_filter($all_aes, fn($a) => $a['resolution_date'] === null);
 $resolved_aes = array_filter($all_aes, fn($a) => $a['resolution_date'] !== null);
?>
<?php require_once 'nurse_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Side Effects</h1>
    <div class="flex gap-2 text-xs">
      <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-error"></span> <?= count($active_aes) ?> active</span>
      <span class="flex items-center gap-1"><span class="size-2 rounded-full bg-success"></span> <?= count($resolved_aes) ?> resolved</span>
    </div>
  </div>

  <?php if (empty($all_aes)): ?>
  <div class="card mt-6 p-12 text-center text-slate-400">
    <svg class="mx-auto size-12 mb-3 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-lg font-medium">No side effects reported</p>
    <p class="mt-1 text-sm">Adverse events for your facility patients will appear here.</p>
  </div>
  <?php else: ?>

  <?php if (!empty($active_aes)): ?>
  <h2 class="mt-6 mb-3 text-sm font-semibold uppercase tracking-wide text-error">Active (<?= count($active_aes) ?>)</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($active_aes as $ae): ?>
    <div class="card p-4 border-l-4 <?= $severity_colors[$ae['severity']] ?? 'border-l-slate-400' ?>">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']) ?></h3>
          <p class="text-xs text-slate-400"><?= htmlspecialchars($ae['full_name']) ?> — <?= $ae['patient_code'] ?></p>
        </div>
        <span class="rounded-full px-1.5 py-0.5 text-xs font-medium <?= $severity_colors[$ae['severity']] ?? '' ?>">
          <?= $severity_labels[$ae['severity']] ?? ucfirst($ae['severity']) ?>
        </span>
      </div>
      <?php if ($ae['suspected_drug_name']): ?>
      <p class="mt-2 text-xs"><span class="text-slate-400">Suspected drug:</span> <span class="font-medium text-error"><?= htmlspecialchars($ae['suspected_drug_name']) ?></span></p>
      <?php endif; ?>
      <div class="mt-2 flex gap-4 text-xs text-slate-400">
        <span>Since: <?= date('M j, Y', strtotime($ae['onset_date'])) ?></span>
        <?php if ($ae['reported_by_name']): ?>
        <span>By: <?= htmlspecialchars($ae['reported_by_name']) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($ae['notes']): ?>
      <p class="mt-2 text-xs text-slate-500 line-clamp-2"><?= htmlspecialchars($ae['notes']) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($resolved_aes)): ?>
  <h2 class="mt-8 mb-3 text-sm font-semibold uppercase tracking-wide text-success">Resolved (<?= count($resolved_aes) ?>)</h2>
  <div class="card">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-2.5 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENT</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-2.5 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">EVENT</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-2.5 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">SEVERITY</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-2.5 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ONSET</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-2.5 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">RESOLVED</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resolved_aes as $ae): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500 opacity-70">
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($ae['full_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($ae['event_type']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5">
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $severity_colors[$ae['severity']] ?? '' ?>">
                <?= $severity_labels[$ae['severity']] ?? ucfirst($ae['severity']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500"><?= date('M j', strtotime($ae['onset_date'])) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-success"><?= date('M j, Y', strtotime($ae['resolution_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</main>

<?php require_once 'nurse_footer.php'; ?>