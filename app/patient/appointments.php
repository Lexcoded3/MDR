<?php
session_start();
 $required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

 $pageTitle = 'Appointments - GxAlert';

// Upcoming
 $up_stmt = $conn->prepare("
    SELECT a.*, u.name AS assigned_name
    FROM appointments a
    LEFT JOIN users u ON a.assigned_to = u.id
    WHERE a.patient_id = ? AND a.appointment_date >= NOW() AND a.status = 'pending'
    ORDER BY a.appointment_date ASC
");
 $up_stmt->bind_param("i", $patient_id);
 $up_stmt->execute();
 $upcoming = $up_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Past
 $past_stmt = $conn->prepare("
    SELECT a.*, u.name AS assigned_name
    FROM appointments a
    LEFT JOIN users u ON a.assigned_to = u.id
    WHERE a.patient_id = ? AND (a.appointment_date < NOW() OR a.status != 'pending')
    ORDER BY a.appointment_date DESC
    LIMIT 20
");
 $past_stmt->bind_param("i", $patient_id);
 $past_stmt->execute();
 $past = $past_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $appt_type_labels = [
    'clinical_review' => 'Clinical Review',
    'lab_collection'  => 'Lab Collection',
    'drug_pickup'     => 'Drug Pickup',
    'counseling'      => 'Counseling',
    'follow_up'       => 'Follow-up',
];

 $appt_status_colors = [
    'pending'   => 'bg-info/10 text-info',
    'completed' => 'bg-success/10 text-success',
    'missed'    => 'bg-error/10 text-error',
];
?>
<?php require_once 'patient_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-5">Appointments</h1>

  <!-- Upcoming -->
  <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Upcoming</h2>
  <?php if (!empty($upcoming)): ?>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 mb-8">
    <?php foreach ($upcoming as $a):
        $type_label = $appt_type_labels[$a['appointment_type']] ?? ucfirst($a['appointment_type'] ?? 'Visit');
        $days_away = (new DateTime('today'))->diff(new DateTime($a['appointment_date']))->days;
        $urgency = $days_away <= 1 ? 'border-l-warning' : ($days_away <= 3 ? 'border-l-info' : 'border-l-primary');
    ?>
    <div class="card border-l-4 <?= $urgency ?> p-4 mt-5">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs font-semibold uppercase text-primary dark:text-accent-light"><?= $type_label ?></p>
          <p class="mt-1 text-lg font-semibold text-slate-700 dark:text-navy-100">
            <?= date('M d, Y', strtotime($a['appointment_date'])) ?>
          </p>
          <p class="text-sm text-slate-500 dark:text-navy-200"><?= date('h:i A', strtotime($a['appointment_date'])) ?></p>
        </div>
        <?php if ($days_away <= 1): ?>
        <span class="rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-bold text-warning">
          <?= $days_away === 0 ? 'TODAY' : 'TOMORROW' ?>
        </span>
        <?php endif; ?>
      </div>
      <?php if ($a['purpose']): ?>
      <p class="mt-2 text-xs text-slate-400 dark:text-navy-300"><?= htmlspecialchars($a['purpose']) ?></p>
      <?php endif; ?>
      <?php if ($a['assigned_name']): ?>
      <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">
        <svg class="inline size-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        <?= htmlspecialchars($a['assigned_name']) ?>
      </p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="card p-8 text-center mb-8 mt-5">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-10 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
    </svg>
    <p class="mt-3 text-sm text-slate-400 dark:text-navy-300">No upcoming appointments</p>
  </div>
  <?php endif; ?>

  <!-- Past -->
  <?php if (!empty($past)): ?>
  <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Past</h2>
  <div class="card">
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Date</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Type</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">With</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($past as $a):
              $st_cls = $appt_status_colors[$a['status']] ?? 'bg-slate-200 text-slate-600';
              $type_label = $appt_type_labels[$a['appointment_type']] ?? ucfirst($a['appointment_type'] ?? 'Visit');
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-navy-100"><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= $type_label ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= htmlspecialchars($a['assigned_name'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $st_cls ?>"><?= ucfirst($a['status']) ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'patient_footer.php'; ?>