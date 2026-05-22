<?php
session_start();
 $required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';

 $pageTitle = 'Clinician Dashboard - GxAlert';
 $stats = getClinicianStats($conn, $clinician_id);

// Recent patients enrolled (last 5)
$recent_stmt = $conn->prepare("
    SELECT p.*, f.name AS facility_name
    FROM patients p 
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE p.created_by = ? AND p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 5
");
$recent_stmt->bind_param("i", $clinician_id);
$recent_stmt->execute();

// USE THIS INSTEAD OF BIND_RESULT
$result = $recent_stmt->get_result();
$recent_patients = [];
while ($row = $result->fetch_assoc()) {
    $recent_patients[] = $row;
}

// Patients with active adverse events
 $ae_stmt = $conn->prepare("
    SELECT p.id, p.full_name, p.patient_code, ae.event_type, ae.severity, ae.onset_date
    FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    WHERE p.created_by = ? AND ae.resolution_date IS NULL AND p.is_active = 1
    ORDER BY ae.onset_date DESC LIMIT 3
");
 $ae_stmt->bind_param("i", $clinician_id);
 $ae_stmt->execute();
 $ae_alerts = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Today's appointments
 $appt_stmt = $conn->prepare("
    SELECT a.*, p.full_name, p.patient_code
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE p.created_by = ? AND a.appointment_date >= CURDATE() AND a.appointment_date < CURDATE() + INTERVAL 1 DAY AND a.status = 'pending'
    ORDER BY a.appointment_date ASC
");
 $appt_stmt->bind_param("i", $clinician_id);
 $appt_stmt->execute();
 $today_appts = $appt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status helper
function statusBadge($status) {
    $map = [
        'enrolled'         => 'bg-info/10 text-info',
        'on_treatment'     => 'bg-primary/10 text-primary dark:text-accent-light',
        'completed'        => 'bg-success/10 text-success',
        'cured'            => 'bg-success/10 text-success',
        'failed'           => 'bg-error/10 text-error',
        'died'             => 'bg-slate-400/10 text-slate-500 dark:text-navy-300',
        'lost_to_followup' => 'bg-warning/10 text-warning',
        'transferred_out'  => 'bg-secondary/10 text-secondary',
    ];
    $cls = $map[$status] ?? 'bg-slate-200 text-slate-600';
    $label = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase $cls\">$label</span>";
}

function calcAge($dob) {
    if (!$dob) return '-';
    return (new DateTime('today'))->diff(new DateTime($dob))->y;
}

 $greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <!-- Welcome -->
  <div class="card col-span-12 mt-12 bg-gradient-to-r from-primary to-blue-600 p-5 sm:col-span-8 sm:mt-0 sm:flex-row">
    <div class="flex justify-center sm:order-last">
      <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/doctor.svg" alt="image">
    </div>
    <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
      <h3 class="text-xl"><?= $greeting ?>, <span class="font-semibold"><?= htmlspecialchars($clinician_name); ?></span></h3>
      <p class="mt-2 leading-relaxed text-white/80">You have <?= $stats['today_appts'] ?> appointment<?= $stats['today_appts'] !== 1 ? 's' : '' ?> today and <?= $stats['active_ae'] ?> active adverse event<?= $stats['active_ae'] !== 1 ? 's' : '' ?> to review.</p>
      <button onclick="location.href='patients.php'" class="btn mt-5 border border-white/10 bg-white/20 text-white hover:bg-white/30">Manage Patients</button>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 sm:mt-5 lg:gap-6">
    <div class="card border-t-4 border-t-primary p-4 text-center">
      <div class="text-3xl font-bold text-primary dark:text-accent-light"><?= $stats['total_patients'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Total Patients</p>
    </div>
    <div class="card border-t-4 border-t-success p-4 text-center">
      <div class="text-3xl font-bold text-success"><?= $stats['on_treatment'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">On Treatment</p>
    </div>
    <div class="card border-t-4 border-t-warning p-4 text-center">
      <div class="text-3xl font-bold text-warning"><?= $stats['ltfu'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Lost to Follow-up</p>
    </div>
    <div class="card border-t-4 border-t-error p-4 text-center">
      <div class="text-3xl font-bold text-error"><?= $stats['active_ae'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Active AEs</p>
    </div>
  </div>

  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
    <!-- Left Column -->
    <div class="col-span-12 lg:col-span-8">

      <!-- Today's Appointments -->
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Today's Appointments</h2>
        <a href="#" class="text-xs text-primary dark:text-accent-light">View All →</a>
      </div>
      <?php if (!empty($today_appts)): ?>
      <div class="card mb-5 mt-5">
        <div class="is-scrollbar-hidden overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patient</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Time</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Type</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($today_appts as $a): ?>
              <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="viewpatient.php?id=<?= $a['patient_id'] ?>" class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent-light"><?= htmlspecialchars($a['full_name']) ?></a>
                  <span class="ml-2 text-[10px] font-mono text-slate-400"><?= $a['patient_code'] ?></span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= date('h:i A', strtotime($a['appointment_date'])) ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= str_replace('_', ' ', ucfirst($a['appointment_type'] ?? 'Visit')) ?></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="viewpatient.php?id=<?= $a['patient_id'] ?>" class="btn size-7 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php else: ?>
      <div class="card mb-5 mt-5 p-6 text-center text-sm text-slate-400 dark:text-navy-300">No appointments scheduled for today</div>
      <?php endif; ?>

      <!-- Recent Patients -->
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Recently Enrolled</h2>
        <a href="patients.php" class="text-xs text-primary dark:text-accent-light">View All →</a>
      </div>
      <div class="card mt-5">
        <div class="is-scrollbar-hidden overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Code</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Name</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Age/Sex</th>
                <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Status</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_patients as $rp): ?>
              <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
                <td class="whitespace-nowrap px-4 py-3"><code class="text-xs"><?= htmlspecialchars($rp['patient_code']) ?></code></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="viewpatient.php?id=<?= $rp['id'] ?>" class="font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent-light"><?= htmlspecialchars($rp['full_name']) ?></a>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= calcAge($rp['date_of_birth']) ?>/<?= substr($rp['gender'], 0, 1) ?></td>
                <td class="whitespace-nowrap px-4 py-3"><?= statusBadge($rp['treatment_status']) ?></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="viewpatient.php?id=<?= $rp['id'] ?>" class="btn size-7 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="col-span-12 lg:col-span-4">

      <!-- AE Alerts -->
      <?php if (!empty($ae_alerts)): ?>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-error">AE Alerts</h2>
      </div>
      <div class="space-y-3 mb-5">
        <?php foreach ($ae_alerts as $ae):
            $sev_cls = ['mild'=>'border-l-warning','moderate'=>'border-l-orange-500','severe'=>'border-l-error','life_threatening'=>'border-l-error'][($ae['severity'] ?? 'mild')] ?? 'border-l-slate-400';
        ?>
        <div class="card mt-5 border-l-4 <?= $sev_cls ?> p-3">
          <a href="viewpatient.php?id=<?= $ae['id'] ?>" class="font-medium text-sm text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent-light"><?= htmlspecialchars($ae['full_name']) ?></a>
          <p class="text-xs text-slate-400 dark:text-navy-300"><?= htmlspecialchars($ae['event_type']) ?> · <span class="uppercase font-semibold"><?= $ae['severity'] ?></span></p>
          <p class="text-[10px] text-slate-400 dark:text-navy-300 mt-0.5">Since <?= $ae['onset_date'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="mt-5">
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Quick Actions</h2>
      <div class="space-y-2 mt-5">
        <a href="addpatient.php" class="card flex items-center space-x-3 p-4 hover:bg-primary/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
            <svg class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Register New Patient</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">Enroll a new GxAlert patient</p>
          </div>
        </a>
        <a href="adherence_log.php" class="card flex items-center space-x-3 p-4 hover:bg-success/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
            <svg class="size-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Log Adherence</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">Record daily medication intake</p>
          </div>
        </a>
        <a href="treatment.php" class="card flex items-center space-x-3 p-4 hover:bg-warning/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
            <svg class="size-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Manage Regimens</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">Assign or modify treatment</p>
          </div>
        </a>
      </div>
    </div>

    </div>
  </div>
</div>

<?php
 $notify_text = 'success';
require_once 'clinician_footer.php'; ?>