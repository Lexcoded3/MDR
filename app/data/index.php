<?php
session_start();
 $required_role = 'data_officer';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'data_init.php';

 $pageTitle = 'Data Officer Dashboard - GxAlert';
 $overview = getDataOverview($conn);

// Monthly enrollment trend (last 12 months)
 $monthly = $conn->query("
    SELECT DATE_FORMAT(enrollment_date, '%Y-%m') AS month, COUNT(*) AS enrolled
    FROM patients WHERE is_active = 1 AND enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Treatment status distribution
 $status_dist = $conn->query("
    SELECT treatment_status, COUNT(*) AS cnt 
    FROM patients WHERE is_active = 1 
    GROUP BY treatment_status
")->fetch_all(MYSQLI_ASSOC);

// HIV breakdown among patients
 $hiv_breakdown = $conn->query("
    SELECT hiv_status, COUNT(*) AS cnt 
    FROM patients WHERE is_active = 1 
    GROUP BY hiv_status
")->fetch_all(MYSQLI_ASSOC);

// Top facilities by patient count
 $top_facilities = $conn->query("
    SELECT f.name, COUNT(p.id) AS patient_count
    FROM patients p
    JOIN facilities f ON p.facility_id = f.id
    WHERE p.is_active = 1
    GROUP BY p.facility_id
    ORDER BY patient_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// TB confirmation breakdown
 $mdr_breakdown = $conn->query("
    SELECT mdr_confirmation, COUNT(*) AS cnt
    FROM patients WHERE is_active = 1
    GROUP BY mdr_confirmation
")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'data_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">

  <!-- Welcome -->
  <div class="card col-span-12 mt-12 bg-gradient-to-r from-secondary to-slate-700 p-5 sm:col-span-8 sm:mt-0 sm:flex-row">
    <div class="flex justify-center sm:order-last">
      <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/doctor.svg" alt="image">
    </div>
    <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
      <h3 class="text-xl">Welcome, <span class="font-semibold"><?= htmlspecialchars($data_officer_name); ?></span></h3>
      <p class="mt-2 leading-relaxed text-white/80">Monitoring <?= $overview['on_treatment'] ?> active patients across <?= $overview['facility_count'] ?> facilities.</p>
      <div class="mt-4 flex flex-wrap gap-4 justify-center sm:justify-start text-white/80 text-sm">
        <span>Adherence: <strong class="text-white"><?= $overview['avg_adherence_30d'] ?>%</strong></span>
        <span>Active AEs: <strong class="text-white"><?= $overview['active_ae'] ?></strong></span>
        <span>SMS Sent (30d): <strong class="text-white"><?= $overview['sms_sent_month'] ?></strong></span>
      </div>
    </div>
  </div>

  <!-- Key Metrics -->
  <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 sm:mt-5">
    <div class="card border-t-4 border-t-primary p-4 text-center">
      <div class="text-3xl font-bold text-primary dark:text-accent-light"><?= $overview['total_patients'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Total Patients</p>
    </div>
    <div class="card border-t-4 border-t-success p-4 text-center">
      <div class="text-3xl font-bold text-success"><?= $overview['cured'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Cured / Completed</p>
    </div>
    <div class="card border-t-4 border-t-error p-4 text-center">
      <div class="text-3xl font-bold text-error"><?= $overview['failed'] + $overview['died'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Failed / Died</p>
    </div>
    <div class="card border-t-4 border-t-warning p-4 text-center">
      <div class="text-3xl font-bold text-warning"><?= $overview['ltfu'] ?></div>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">Lost to Follow-up</p>
    </div>
  </div>

  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
    <div class="col-span-12 lg:col-span-9">

      <!-- Monthly Enrollment Trend -->
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3">Monthly Enrollment (12 Months)</h2>
      <div class="card">
        <div class="p-5">
          <?php if (!empty($monthly)):
          $max_enrolled = max(array_column($monthly, 'enrolled'));
          ?>
          <div class="flex items-end gap-2 h-40">
            <?php foreach ($monthly as $m): 
                $height = $max_enrolled > 0 ? ($m['enrolled'] / $max_enrolled) * 100 : 0;
                $display_month = date('M y', strtotime($m['month'] . '-01'));
            ?>
            <div class="flex-1 flex flex-col items-center">
              <div class="w-full rounded-t bg-primary/20 hover:bg-primary/30 transition-colors relative" style="height: <?= max($height, 2) ?>%">
                <div class="absolute -top-5 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-slate-500 dark:text-navy-300"><?= $m['enrolled'] ?></div>
              </div>
              <span class="mt-1 text-[10px] text-slate-400 dark:text-navy-300"><?= $display_month ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-sm text-slate-400 dark:text-navy-300 text-center py-8">No data yet</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Treatment Status + HIV + TB Breakdown -->
      <!-- change gap-5 to gap-6 -->
<div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Treatment Status -->
        <div class="card">
          <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
            <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">Treatment Status</h3>
          </div>
          <div class="divide-y divide-slate-150 dark:divide-navy-600">
            <?php 
            $status_order = ['on_treatment','enrolled','cured','completed','failed','lost_to_followup','died','transferred_out'];
            $status_colors = ['on_treatment'=>'text-primary dark:text-accent-light','enrolled'=>'text-info','cured'=>'text-success','completed'=>'text-success','failed'=>'text-error','lost_to_followup'=>'text-warning','died'=>'text-slate-500 dark:text-navy-300','transferred_out'=>'text-secondary'];
            foreach ($status_order as $s):
                $cnt = 0;
                foreach ($status_dist as $sd) { if ($sd['treatment_status'] === $s) { $cnt = (int)$sd['cnt']; break; } }
                $color = $status_colors[$s] ?? 'text-slate-500';
                $label = str_replace('_', ' ', ucfirst($s));
                $pct = $overview['total_patients'] > 0 ? round(($cnt / $overview['total_patients']) * 100, 1) : 0;
            ?>
            <div class="flex items-center justify-between px-4 py-2.5">
              <span class="text-sm text-slate-600 dark:text-navy-200"><?= $label ?></span>
              <div class="flex items-center space-x-2">
                <div class="w-24 h-1.5 rounded-full bg-slate-200 dark:bg-navy-700">
                  <div class="h-full rounded-full bg-<?= str_replace('dark:text-accent-light','accent',str_replace('text-primary','primary',$color)) ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-xs font-mono w-10 text-right <?= $color ?>"><?= $cnt ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- HIV Status -->
        <div class="card">
          <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
            <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">HIV Status</h3>
          </div>
          <div class="divide-y divide-slate-150 dark:divide-navy-600">
            <?php 
            $hiv_colors = ['positive'=>'text-warning','negative'=>'text-success','unknown'=>'text-slate-400 dark:text-navy-300'];
            $hiv_order = ['positive','negative','unknown'];
            foreach ($hiv_order as $h):
                $cnt = 0;
                foreach ($hiv_breakdown as $hb) { if ($hb['hiv_status'] === $h) { $cnt = (int)$hb['cnt']; break; } }
                $color = $hiv_colors[$h] ?? 'text-slate-500';
                $pct = $overview['total_patients'] > 0 ? round(($cnt / $overview['total_patients']) * 100, 1) : 0;
            ?>
            <div class="flex items-center justify-between px-4 py-2.5">
              <span class="text-sm text-slate-600 dark:text-navy-200">HIV <?= ucfirst($h) ?></span>
              <div class="flex items-center space-x-2">
                <div class="w-24 h-1.5 rounded-full bg-slate-200 dark:bg-navy-700">
                  <div class="h-full rounded-full bg-<?= str_replace('text-warning','warning',str_replace('text-success','success',$color)) ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-xs font-mono w-10 text-right <?= $color ?>"><?= $cnt ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TB Confirmation -->
        <div class="card">
          <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
            <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">TB Confirmation</h3>
          </div>
          <div class="divide-y divide-slate-150 dark:divide-navy-600">
            <?php 
            $mdr_colors = ['confirmed'=>'text-error','presumed'=>'text-warning'];
            foreach ($mdr_breakdown as $mb):
                $color = $mdr_colors[$mb['mdr_confirmation']] ?? 'text-slate-500';
                $pct = $overview['total_patients'] > 0 ? round(($mb['cnt'] / $overview['total_patients']) * 100, 1) : 0;
            ?>
            <div class="flex items-center justify-between px-4 py-2.5">
              <span class="text-sm text-slate-600 dark:text-navy-200"><?= ucfirst($mb['mdr_confirmation']) ?></span>
              <div class="flex items-center space-x-2">
                <div class="w-24 h-1.5 rounded-full bg-slate-200 dark:bg-navy-700">
                  <div class="h-full rounded-full bg-<?= str_replace('text-error','error',str_replace('text-warning','warning',$color)) ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-xs font-mono w-10 text-right <?= $color ?>"><?= (int)$mb['cnt'] ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Top Facilities -->
      <h2 class="text-base font-medium text-slate-700 dark:text-navy-100 mb-3 mt-5">Top Facilities by Patient Load</h2>
      <div class="card">
        <div class="divide-y divide-slate-150 dark:divide-navy-600">
          <?php foreach ($top_facilities as $idx => $tf):
              $bar_pct = $overview['total_patients'] > 0 ? round(($tf['patient_count'] / $overview['total_patients']) * 100, 1) : 0;
          ?>
          <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 dark:hover:bg-navy-800 transition-colors">
            <span class="text-sm font-medium text-slate-500 dark:text-navy-400 w-6"><?= $idx + 1 ?></span>
            <div class="flex-1">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($tf['name']) ?></span>
                <span class="text-xs font-mono text-slate-400 dark:text-navy-300"><?= $tf['patient_count'] ?> patients</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-navy-700">
                <div class="h-full rounded-full bg-primary transition-all" style="width: <?= $bar_pct ?>%"></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Right Column: Quick Links -->
    <div class="col-span-12 lg:col-span-3">
      <div class="space-y-4 mt-5">
        <a href="cohort_report.php" class="card flex items-center space-x-3 p-4 hover:bg-primary/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
            <svg class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round"  d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"  /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Cohort Reports</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">Enrollment trends, demographics, outcomes</p>
          </div>
        </a>
        <a href="adherence_report.php" class="card flex items-center space-x-3 p-4 hover:bg-success/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
            <svg class="size-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Adherence Analytics</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">By patient, period, facility</p>
          </div>
        </a>
        <a href="outcomes.php" class="card flex items-center space-x-3 p-4 hover:bg-error/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-error/10">
            <svg class="size-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"  /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Treatment Outcomes</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">WHO-standardized reporting</p>
          </div>
        </a>
        <a href="export.php" class="card flex items-center space-x-3 p-4 hover:bg-info/5 transition-colors">
          <div class="flex size-10 items-center justify-center rounded-lg bg-info/10">
            <svg class="size-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
          </div>
          <div>
            <p class="font-medium text-sm text-slate-700 dark:text-navy-100">Export Data</p>
            <p class="text-xs text-slate-400 dark:text-navy-300">CSV downloads</p>
          </div>
        </a>

        <!-- SMS Summary -->
        <div class="card">
          <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
            <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">SMS Summary (30 Days)</h3>
          </div>
          <div class="p-4 space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-600 dark:text-navy-200">Sent / Delivered</span>
              <span class="font-mono text-success"><?= $overview['sms_sent_month'] ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-600 dark:text-navy-200">Failed</span>
              <span class="font-mono text-error"><?= $overview['sms_failed_month'] ?></span>
            </div>
            <?php if ($overview['sms_sent_month'] > 0): ?>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-navy-300 text-xs">Delivery Rate</span>
              <span class="font-mono text-xs text-slate-700 dark:text-navy-100">
                <?= round((1 - $overview['sms_failed_month'] / max(1, $overview['sms_sent_month'])) * 100, 1) ?>%
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Adherence Rate Card -->
        <div class="card">
          <div class="border-b border-slate-150 dark:border-navy-600 px-4 py-3">
            <h3 class="text-xs font-semibold uppercase text-slate-500 dark:text-navy-300">Average Adherence (30 Days)</h3>
          </div>
          <div class="p-5 text-center">
    <div class="text-4xl font-bold <?= $overview['avg_adherence_30d'] >= 95 ? 'text-success' : ($overview['avg_adherence_30d'] >= 85 ? 'text-warning' : 'text-error') ?>">
      <?= $overview['avg_adherence_30d'] ?>%
    </div>
    <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">
      <?= $overview['avg_adherence_30d'] >= 95 ? 'On Target' : ($overview['avg_adherence_30d'] >= 85 ? 'Needs Attention' : 'Critical') ?>
    </p>
</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
 $notify_text = 'success';
require_once 'data_footer.php'; ?>