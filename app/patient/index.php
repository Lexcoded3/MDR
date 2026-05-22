<?php
session_start();

 $required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';

// ============================================================
// GET PATIENT RECORD linked to this user account
// Requires: ALTER TABLE patients ADD COLUMN user_id INT ...
// ============================================================
 $stmt = $conn->prepare("
    SELECT p.* 
    FROM patients p 
    WHERE p.user_id = ? AND p.is_active = 1
");
 $stmt->bind_param("i", $_SESSION['id']);
 $stmt->execute();
 $patient_res = $stmt->get_result();

if ($patient_res->num_rows !== 1) {
    // No patient record linked to this user account
    header("Location: ../auth/?error=no_record");
    exit;
}

 $patient = $patient_res->fetch_assoc();
 $patient_id = $patient['id'];

// ============================================================
// ACTIVE REGIMEN with drugs
// ============================================================
 $regimen = null;
 $reg_stmt = $conn->prepare("
    SELECT tr.*,
           GROUP_CONCAT(
               CONCAT(d.drug_code, ' ', rd.dose_mg, 'mg x', rd.frequency_per_day, '/day')
               SEPARATOR ' | '
           ) AS drug_summary
    FROM treatment_regimens tr
    LEFT JOIN regimen_drugs rd ON tr.id = rd.regimen_id AND rd.is_active = 1
    LEFT JOIN drugs d ON rd.drug_id = d.id
    WHERE tr.patient_id = ? AND tr.status = 'active'
    GROUP BY tr.id
    ORDER BY tr.start_date DESC
    LIMIT 1
");
 $reg_stmt->bind_param("i", $patient_id);
 $reg_stmt->execute();
 $reg_res = $reg_stmt->get_result();
if ($reg_res->num_rows === 1) {
    $regimen = $reg_res->fetch_assoc();
}

// ============================================================
// TODAY'S MEDICATIONS
// ============================================================
 $today_meds = [];
if ($regimen) {
    $med_stmt = $conn->prepare("
        SELECT ms.id AS schedule_id, ms.dose_time, d.drug_name, d.drug_code, rd.dose_mg,
               (SELECT al.status FROM adherence_logs al 
                WHERE al.patient_id = ? AND al.schedule_id = ms.id AND al.dose_date = CURDATE() 
                LIMIT 1) AS today_status
        FROM medication_schedule ms
        JOIN regimen_drugs rd ON ms.regimen_id = rd.regimen_id AND rd.is_active = 1
        JOIN drugs d ON ms.drug_id = d.id
        WHERE ms.regimen_id = ? AND ms.is_active = 1
        ORDER BY ms.dose_time
    ");
    $med_stmt->bind_param("ii", $patient_id, $regimen['id']);
    $med_stmt->execute();
    $today_meds = $med_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ============================================================
// ADHERENCE SUMMARY (last 30 days)
// ============================================================
 $adh_stmt = $conn->prepare("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END), 0) AS taken,
           COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) AS late,
           COALESCE(SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END), 0) AS missed
    FROM adherence_logs 
    WHERE patient_id = ? AND dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
 $adh_stmt->bind_param("i", $patient_id);
 $adh_stmt->execute();
 $adh = $adh_stmt->get_result()->fetch_assoc();

 $adh_total = (int)$adh['total'];
 $adh_taken = (int)$adh['taken'];
 $adh_late  = (int)$adh['late'];
 $adh_missed = (int)$adh['missed'];
 $adh_pct   = $adh_total > 0 ? round((($adh_taken + $adh_late) / $adh_total) * 100, 1) : 0;
 $adh_color = $adh_pct >= 95 ? 'success' : ($adh_pct >= 85 ? 'warning' : 'error');

// ============================================================
// LAST 7 DAYS ADHERENCE (for mini calendar)
// ============================================================
 $week_stmt = $conn->prepare("
    SELECT dose_date, status
    FROM adherence_logs
    WHERE patient_id = ? AND dose_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY dose_date
    ORDER BY dose_date ASC
");
 $week_stmt->bind_param("i", $patient_id);
 $week_stmt->execute();
 $week_data_raw = $week_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build a simple day => worst status map
 $week_map = [];
foreach ($week_data_raw as $row) {
    $d = $row['dose_date'];
    if (!isset($week_map[$d]) || $row['status'] === 'missed') {
        $week_map[$d] = $row['status'];
    }
}

// ============================================================
// NEXT APPOINTMENT
// ============================================================
 $appt_stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE patient_id = ? AND appointment_date >= NOW() AND status = 'pending'
    ORDER BY appointment_date ASC LIMIT 1
");
 $appt_stmt->bind_param("i", $patient_id);
 $appt_stmt->execute();
 $next_appt = $appt_stmt->get_result()->fetch_assoc();

// ============================================================
// ACTIVE ADVERSE EVENTS
// ============================================================
 $ae_stmt = $conn->prepare("
    SELECT ae.*, d.drug_code, d.drug_name
    FROM adverse_events ae
    LEFT JOIN drugs d ON ae.drug_id = d.id
    WHERE ae.patient_id = ? AND ae.resolution_date IS NULL
    ORDER BY ae.onset_date DESC
");
 $ae_stmt->bind_param("i", $patient_id);
 $ae_stmt->execute();
 $active_ae = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============================================================
// DERIVED VALUES
// ============================================================
 $treatment_days = 0;
if ($regimen && $regimen['start_date']) {
    $start = new DateTime($regimen['start_date']);
    $treatment_days = $start->diff(new DateTime('today'))->days;
}

 $age = null;
if ($patient['date_of_birth']) {
    $age = (new DateTime('today'))->diff(new DateTime($patient['date_of_birth']))->y;
}

 $status_labels = [
    'enrolled'         => 'Enrolled',
    'on_treatment'     => 'On Treatment',
    'completed'        => 'Completed',
    'cured'            => 'Cured',
    'failed'           => 'Failed',
    'died'             => 'Died',
    'lost_to_followup' => 'Lost to Follow-up',
    'transferred_out'  => 'Transferred Out',
];
 $status_label = $status_labels[$patient['treatment_status']] ?? 'Unknown';
 $status_colors = [
    'enrolled'         => 'bg-info/10 text-info',
    'on_treatment'     => 'bg-primary/10 text-primary dark:text-accent-light',
    'completed'        => 'bg-success/10 text-success',
    'cured'            => 'bg-success/10 text-success',
    'failed'           => 'bg-error/10 text-error',
    'died'             => 'bg-slate-500/10 text-slate-500',
    'lost_to_followup' => 'bg-warning/10 text-warning',
    'transferred_out'  => 'bg-secondary/10 text-secondary',
];
 $status_class = $status_colors[$patient['treatment_status']] ?? 'bg-slate-200 text-slate-600';

 $greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>TB - Patient Dashboard</title>
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/app.js" defer=""></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
</head>

<body x-data="" class="is-header-blur" x-bind="$store.global.documentBody">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>

    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">

      <!-- Sidebar -->
      <div class="sidebar print:hidden">
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <div class="flex pt-4">
              <a href="index.php">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>
            <?php include 'sidenav.php'; ?>
          </div>
        </div>
        <?php include 'dashboardsider.php'; ?>
      </div>

      <!-- App Header -->
      <?php include 'toprightsidenav.php'; ?>

      <!-- Main Content -->
      <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">

          <!-- ========== LEFT COLUMN ========== -->
          <div class="col-span-12 lg:col-span-7 xl:col-span-8">

            <!-- Welcome Card -->
            <div class="card col-span-12 mt-12 bg-gradient-to-r from-blue-500 to-blue-600 p-5 sm:col-span-8 sm:mt-0 sm:flex-row">
              <div class="flex justify-center sm:order-last">
                <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/doctor.svg" alt="image">
              </div>
              <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
                <h3 class="text-xl">
                  <?= $greeting ?>, <span class="font-semibold"><?= htmlspecialchars($patient['full_name']); ?></span>
                </h3>
                <p class="mt-1 text-sm text-white/80">
                  Code: <span class="font-mono font-semibold"><?= htmlspecialchars($patient['patient_code'] ?? 'N/A'); ?></span>
                </p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                  <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                    <?= $status_label ?>
                  </span>
                  <?php if ($regimen): ?>
                  <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                    Day <?= $treatment_days ?> of treatment
                  </span>
                  <?php endif; ?>
                  <?php if ($patient['hiv_status'] === 'positive'): ?>
                  <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                    HIV+ <?= $patient['on_art'] ? '· On ART' : '' ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php if ($regimen): ?>
                <button onclick="location.href='medications.php'" class="btn mt-5 border border-white/10 bg-white/20 text-white hover:bg-white/30 focus:bg-white/30">
                  View My Medications
                </button>
                <?php endif; ?>
              </div>
            </div>

            <!-- Today's Medications -->
            <?php if (!empty($today_meds)): ?>
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex h-8 items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Today's Medications
                </h2>
                <span class="text-xs text-slate-400 dark:text-navy-300"><?= date('l, F j, Y'); ?></span>
              </div>
              <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                <?php foreach ($today_meds as $med):
                    $dot_color = $med['today_status'] === 'taken' ? 'bg-success'
                               : ($med['today_status'] === 'late' ? 'bg-warning' : 'bg-slate-300 dark:bg-navy-600');
                    $status_text = $med['today_status'] ? ucfirst($med['today_status']) : 'Pending';
                    $card_border = $med['today_status'] === 'taken' ? 'border-l-4 border-l-success'
                                 : ($med['today_status'] === 'missed' ? 'border-l-4 border-l-error' : '');
                ?>
                <div class="card space-y-3 p-5 <?= $card_border ?>">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 31.934 31.934" stroke-width="1.5" stroke="currentColor" class="size-5 rotate-45 text-primary dark:text-accent-light">
                          <path d="M15.966,0c-4.74,0-8.652,3.857-8.652,8.597v14.739c0,4.74,3.912,8.598,8.652,8.598c4.741,0,8.653-3.857,8.653-8.598V8.597 C24.618,3.857,20.707,0,15.966,0z M9.837,8.905c0-3.906,2.515-5.598,3.841-5.948c0.657-0.173,1.333,0.221,1.505,0.875 c0.171,0.651-0.186,1.317-0.832,1.497c-0.219,0.065-1.99,0.697-1.99,3.576v4.606c0,0.678-0.583,1.228-1.262,1.228 c-0.677,0-1.262-0.55-1.262-1.228V8.905z M22.094,23.336c0,3.386-2.742,6.141-6.128,6.141c-3.385,0-6.129-2.755-6.129-6.141 v-5.167c0-0.972,0.788-1.759,1.759-1.759h8.739c0.973,0,1.76,0.787,1.76,1.759V23.336z"></path>
                        </svg>
                        <!-- <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                        </svg> -->
                      </div>
                      <div>
                        <h3 class="font-medium text-slate-700 dark:text-navy-100">
                          <?= htmlspecialchars($med['drug_name']); ?>
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-navy-300">
                          <?= htmlspecialchars($med['drug_code']); ?> · <?= $med['dose_mg'] ?>mg
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-xs text-slate-400 dark:text-navy-300">Scheduled Time</p>
                      <p class="text-xl font-medium text-slate-700 dark:text-navy-100">
                        <?= date('h:i A', strtotime($med['dose_time'])); ?>
                      </p>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="size-2.5 rounded-full <?= $dot_color ?>"></span>
                      <span class="text-xs font-medium <?= $med['today_status'] === 'taken' ? 'text-success' : ($med['today_status'] === 'missed' ? 'text-error' : 'text-slate-400 dark:text-navy-300') ?>">
                        <?= $status_text ?>
                      </span>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php elseif ($regimen): ?>
            <!-- No meds scheduled today -->
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">Today's Medications</h2>
              <div class="card mt-3 p-8 text-center">
                <div class="text-slate-400 dark:text-navy-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                  </svg>
                  <p class="mt-2">No medications scheduled for today</p>
                </div>
              </div>
            </div>
            <?php else: ?>
            <!-- No regimen yet -->
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="card border-l-4 border-l-warning p-6">
                <div class="flex items-start space-x-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                  </svg>
                  <div>
                    <h3 class="font-medium text-slate-700 dark:text-navy-100">No Treatment Regimen Assigned</h3>
                    <p class="mt-1 text-sm text-slate-400 dark:text-navy-300">Your clinician has not yet assigned a treatment regimen. Please contact your facility if this seems incorrect.</p>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- This Week's Adherence -->
            <?php if ($adh_total > 0): ?>
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                This Week
              </h2>
              <div class="card mt-3 p-5">
                <div class="grid grid-cols-7 gap-2 text-center">
                  <?php
                  $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                  for ($i = 6; $i >= 0; $i--):
                      $date = date('Y-m-d', strtotime("-$i days"));
                      $day_name = $days[date('w', strtotime($date))];
                      $day_num = date('j', strtotime($date));
                      $is_today = $date === date('Y-m-d');
                      $status = $week_map[$date] ?? null;

                      if ($status === 'taken') {
                          $bg = 'bg-success text-white';
                      } elseif ($status === 'late') {
                          $bg = 'bg-warning text-white';
                      } elseif ($status === 'missed') {
                          $bg = 'bg-error text-white';
                      } elseif ($is_today) {
                          $bg = 'bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent-light ring-2 ring-primary dark:ring-accent';
                      } else {
                          $bg = 'bg-slate-100 text-slate-500 dark:bg-navy-600 dark:text-navy-300';
                      }
                  ?>
                  <div class="space-y-1">
                    <span class="text-[10px] font-semibold uppercase <?= $is_today ? 'text-primary dark:text-accent-light' : 'text-slate-400 dark:text-navy-300' ?>"><?= $day_name ?></span>
                    <div class="flex size-10 mx-auto items-center justify-center rounded-xl text-sm font-medium <?= $bg ?>">
                      <?= $day_num ?>
                    </div>
                    <?php if ($status): ?>
                    <svg class="mx-auto size-3.5 <?= $status === 'taken' ? 'text-success' : ($status === 'late' ? 'text-warning' : 'text-error') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <?php if ($status === 'taken'): ?>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      <?php elseif ($status === 'late'): ?>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01" />
                      <?php else: ?>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      <?php endif; ?>
                    </svg>
                    <?php endif; ?>
                  </div>
                  <?php endfor; ?>
                </div>
                <div class="mt-4 flex items-center justify-center space-x-5 text-xs text-slate-400 dark:text-navy-300">
                  <span class="flex items-center space-x-1"><span class="size-2.5 rounded-full bg-success"></span> Taken</span>
                  <span class="flex items-center space-x-1"><span class="size-2.5 rounded-full bg-warning"></span> Late</span>
                  <span class="flex items-center space-x-1"><span class="size-2.5 rounded-full bg-error"></span> Missed</span>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Active Side Effects -->
            <?php if (!empty($active_ae)): ?>
            <div class="mt-4 sm:mt-5 lg:mt-6">
              <div class="flex h-8 items-center justify-between">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                  Active Side Effects
                </h2>
                <a href="report_side_effect.php" class="border-b border-dotted border-current pb-0.5 text-xs+ font-medium text-primary outline-none transition-colors duration-300 hover:text-primary/70 focus:text-primary/70 dark:text-accent-light dark:hover:text-accent-light/70 dark:focus:text-accent-light/70">Report New</a>
              </div>
              <div class="mt-3 space-y-3">
                <?php foreach ($active_ae as $ae):
                    $sev_colors = [
                        'mild'             => 'border-l-warning bg-warning/5',
                        'moderate'         => 'border-l-orange-500 bg-orange-500/5',
                        'severe'           => 'border-l-error bg-error/5',
                        'life_threatening' => 'border-l-error bg-error/10',
                    ];
                    $sev_badge = [
                        'mild'             => 'bg-warning/10 text-warning',
                        'moderate'         => 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
                        'severe'           => 'bg-error/10 text-error',
                        'life_threatening' => 'bg-error/20 text-error',
                    ];
                    $card_cls = $sev_colors[$ae['severity']] ?? 'border-l-slate-300';
                    $badge_cls = $sev_badge[$ae['severity']] ?? 'bg-slate-200 text-slate-600';
                ?>
                <div class="card border-l-4 <?= $card_cls ?> p-4">
                  <div class="flex items-start justify-between">
                    <div>
                      <h3 class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']); ?></h3>
                      <p class="mt-0.5 text-xs text-slate-400 dark:text-navy-300">
                        <?= htmlspecialchars($ae['drug_code'] ?? 'Unspecified drug') ?> · Since <?= $ae['onset_date'] ?>
                      </p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase <?= $badge_cls ?>">
                      <?= str_replace('_', ' ', $ae['severity']) ?>
                    </span>
                  </div>
                  <?php if ($ae['action_taken']): ?>
                  <p class="mt-2 text-xs text-slate-500 dark:text-navy-300">
                    <span class="font-medium">Action:</span> <?= str_replace('_', ' ', ucfirst($ae['action_taken'])); ?>
                  </p>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

          </div>

          <!-- ========== RIGHT COLUMN ========== -->
          <div class="col-span-12 lg:col-span-5 xl:col-span-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-1 lg:gap-6">

              <!-- Adherence Rate -->
              <div class="rounded-lg bg-<?= $adh_color ?>/10 px-4 pb-5 dark:bg-navy-800 sm:px-5">
                <div class="flex items-center justify-between py-3">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Adherence Rate
                  </h2>
                  <a href="adherence.php" class="text-xs text-primary dark:text-accent-light">Details →</a>
                </div>
                <div class="text-center">
                  <div class="text-4xl font-bold text-<?= $adh_color ?>"><?= $adh_pct ?>%</div>
                  <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Last 30 days</p>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                  <div class="rounded-lg bg-success/10 px-2 py-2">
                    <div class="text-lg font-semibold text-success"><?= $adh_taken ?></div>
                    <div class="text-slate-400 dark:text-navy-300">Taken</div>
                  </div>
                  <div class="rounded-lg bg-warning/10 px-2 py-2">
                    <div class="text-lg font-semibold text-warning"><?= $adh_late ?></div>
                    <div class="text-slate-400 dark:text-navy-300">Late</div>
                  </div>
                  <div class="rounded-lg bg-error/10 px-2 py-2">
                    <div class="text-lg font-semibold text-error"><?= $adh_missed ?></div>
                    <div class="text-slate-400 dark:text-navy-300">Missed</div>
                  </div>
                </div>
              </div>

              <!-- Next Appointment -->
              <?php if ($next_appt): ?>
              <div class="rounded-lg bg-info/10 px-4 pb-5 dark:bg-navy-800 sm:px-5">
                <div class="flex items-center justify-between py-3">
                  <h2 class="font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    Next Appointment
                  </h2>
                  <a href="appointments.php" class="text-xs text-primary dark:text-accent-light">All →</a>
                </div>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <div>
                      <p class="text-xs text-slate-400 dark:text-navy-300"><?= date('D, M j', strtotime($next_appt['appointment_date'])); ?></p>
                      <p class="text-xl font-medium text-slate-700 dark:text-navy-100"><?= date('h:i A', strtotime($next_appt['appointment_date'])); ?></p>
                    </div>
                    <div class="flex size-12 items-center justify-center rounded-full bg-info/20">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-info" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                      </svg>
                    </div>
                  </div>
                  <?php if ($next_appt['purpose']): ?>
                  <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($next_appt['purpose']); ?></p>
                    <?php if ($next_appt['appointment_type']): ?>
                    <p class="text-xs text-slate-400 dark:text-navy-300"><?= str_replace('_', ' ', ucfirst($next_appt['appointment_type'])); ?></p>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php else: ?>
              <div class="card p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-8 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <p class="mt-2 text-sm text-slate-400 dark:text-navy-300">No upcoming appointments</p>
              </div>
              <?php endif; ?>

              <!-- My Details -->
              <div class="card p-4">
                <div class="flex items-center justify-between px-2 pb-3">
                  <p class="font-medium text-slate-700 dark:text-navy-100">My Details</p>
                  <a href="profile.php" class="text-xs text-primary dark:text-accent-light">Edit →</a>
                </div>
                <div class="space-y-2.5 text-xs+">
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Age / Gender</p>
                    <p><?= $age ? $age . ' yrs' : 'N/A' ?> / <?= ucfirst($patient['gender']); ?></p>
                  </div>
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Weight</p>
                    <p><?= $patient['weight_kg'] ? $patient['weight_kg'] . ' kg' : 'N/A' ?></p>
                  </div>
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Phone</p>
                    <p><?= htmlspecialchars($patient['phone'] ?? 'N/A'); ?></p>
                  </div>
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Facility</p>
                    <p class="text-right max-w-[60%]"><?= htmlspecialchars($patient['facility_name'] ?? 'N/A'); ?></p>
                  </div>
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Enrolled</p>
                    <p><?= $patient['enrollment_date'] ?? 'N/A' ?></p>
                  </div>
                  <div class="flex justify-between px-2">
                    <p class="text-slate-400 dark:text-navy-300">Emergency Contact</p>
                    <p class="text-right max-w-[60%]"><?= htmlspecialchars($patient['next_of_kin'] ?? 'N/A'); ?></p>
                  </div>
                </div>
              </div>

              <!-- Quick: No Side Effects -->
              <?php if (empty($active_ae)): ?>
              <div class="card border-l-4 border-l-success p-4">
                <div class="flex items-center space-x-2 text-sm text-success">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="font-medium">No active side effects reported</span>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>

        </div>
      </main>
    </div>

    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
    <div x-data
     x-init="
        const params = new URLSearchParams(window.location.search);
        if(params.get('status') === 'success') {
            $notification({text:'Logged In Successfully', variant:'success', position:'right-top'});
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname);
        }
     "></div>
  </body>
</html>