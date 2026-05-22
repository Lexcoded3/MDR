<?php
session_start();
$required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

$pageTitle = 'My Medications - GxAlert';

// Fetch individual drugs in regimen with full details
$drugs = [];
if ($regimen) {
    $d_stmt = $conn->prepare("
    SELECT rd.*, d.drug_name, d.drug_code, d.drug_group, d.default_dose_mg, d.unit,
           -- Group the times together
           GROUP_CONCAT(ms.dose_time ORDER BY ms.dose_time SEPARATOR ', ') as all_times,
           -- Pull the frequency (since it's the same for all doses of this drug)
           MAX(ms.frequency) as sched_frequency,
           MAX(ms.day_of_week) as day_of_week
    FROM regimen_drugs rd
    JOIN drugs d ON rd.drug_id = d.id
    LEFT JOIN medication_schedule ms ON ms.regimen_id = rd.regimen_id 
         AND ms.drug_id = rd.drug_id 
         AND ms.is_active = 1
    WHERE rd.regimen_id = ? AND rd.is_active = 1
    GROUP BY rd.id, d.id
    ORDER BY 
        FIELD(d.drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other'),
        d.drug_name
");
    $d_stmt->bind_param("i", $regimen['id']);
    $d_stmt->execute();
    $drugs = $d_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $d_stmt->close();
}

// Build today's schedule
$today_doses = [];
$current_time = new DateTime();
$current_day = (int)$current_time->format('N'); // 1=Mon, 7=Sun

foreach ($drugs as $drug) {
    // Check if drug is scheduled for today
    $include_today = true;
    if ($drug['sched_frequency'] === 'weekly' && $drug['day_of_week']) {
        $include_today = ((int)$drug['day_of_week'] === $current_day);
    }
    
    if ($include_today && $drug['all_times']) {
        $times_array = explode(', ', $drug['all_times']);
        foreach ($times_array as $time_str) {
            $dose_time = DateTime::createFromFormat('H:i:s', $time_str);
            $today_doses[] = [
                'drug_name' => $drug['drug_name'],
                'drug_code' => $drug['drug_code'],
                'dose_mg' => $drug['dose_mg'],
                'time' => $dose_time,
                'time_str' => $time_str,
                'drug_group' => $drug['drug_group'],
                'is_past' => ($dose_time < $current_time)
            ];
        }
    }
}

// Sort by time
usort($today_doses, function($a, $b) {
    return $a['time'] <=> $b['time'];
});

// Find next upcoming dose
$next_dose = null;
foreach ($today_doses as $dose) {
    if (!$dose['is_past']) {
        $next_dose = $dose;
        break;
    }
}

// Group styling
$group_colors = [
    'group_a' => 'border-l-error bg-error/10',
    'group_b' => 'border-l-warning bg-warning/5',
    'group_c' => 'border-l-info bg-info/5',
    'group_d1' => 'border-l-secondary bg-secondary/5',
    'group_d2' => 'border-l-slate-400 bg-slate-100 dark:bg-navy-700',
    'other'   => 'border-l-slate-300 bg-slate-50 dark:bg-navy-800',
];
$group_badge = [
    'group_a' => 'bg-error/10 text-error',
    'group_b' => 'bg-warning/10 text-warning',
    'group_c' => 'bg-info/10 text-info',
    'group_d1' => 'bg-secondary/10 text-secondary',
    'group_d2' => 'bg-slate-400/10 text-slate-500 dark:text-navy-300',
    'other'   => 'bg-slate-200 text-slate-600 dark:text-navy-300',
];
$group_labels = [
    'group_a' => 'Group A',
    'group_b' => 'Group B',
    'group_c' => 'Group C',
    'group_d1' => 'Group D1',
    'group_d2' => 'Group D2',
    'other'   => 'Other',
];

// Active adverse events per drug
$ae_by_drug = [];
$ae_stmt = $conn->prepare("
    SELECT drug_id, event_type, severity, onset_date 
    FROM adverse_events 
    WHERE patient_id = ? AND resolution_date IS NULL
");
$ae_stmt->bind_param("i", $patient_id);
$ae_stmt->execute();
$ae_res = $ae_stmt->get_result();
$ae_stmt->close();
while ($ae = $ae_res->fetch_assoc()) {
    $did = $ae['drug_id'];
    if (!isset($ae_by_drug[$did])) $ae_by_drug[$did] = [];
    $ae_by_drug[$did][] = $ae;
}
?>
<?php require_once 'patient_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">
  <!-- Page Header -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
      <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">My Medications</h1>
      <p class="text-sm text-slate-400 dark:text-navy-300 mt-1">
        <?php if ($regimen): ?>
          Started <?= $regimen['start_date'] ?>
          <?php if ($regimen['end_date']): ?> · Expected end <?= $regimen['end_date'] ?><?php endif; ?>
          · <span class="font-medium text-slate-600 dark:text-navy-200">Day <?= $treatment_days ?></span>
        <?php else: ?>
          No active treatment regimen
        <?php endif; ?>
      </p>
    </div>
    <?php if ($regimen && $regimen['notes']): ?>
    <div class="card border-l-4 border-l-warning p-3 max-w-md">
      <p class="text-xs text-slate-500 dark:text-navy-300">
        <span class="font-semibold text-warning">Clinician Note:</span> 
        <?= htmlspecialchars($regimen['notes']); ?>
      </p>
    </div>
    <?php endif; ?>
  </div>

  <?php if (empty($drugs)): ?>
  <!-- No regimen -->
  <div class="card p-12 text-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-16 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
    </svg>
    <h3 class="mt-4 text-lg font-medium text-slate-600 dark:text-navy-200">No Medications Assigned</h3>
    <p class="mt-2 text-sm text-slate-400 dark:text-navy-300 max-w-md mx-auto">
      Your clinician has not yet assigned a treatment regimen. Please contact your facility if you believe this is an error.
    </p>
  </div>

  <?php else: ?>
  
  <!-- Today's Doses Card -->
  <?php if (!empty($today_doses)): ?>
  <div class="card border-l-4 border-l-primary bg-primary/5 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100">Today's Schedule</h2>
          <p class="text-xs text-slate-500 dark:text-navy-300"><?= date('l, F j, Y') ?></p>
        </div>
      </div>
      <?php if ($next_dose): 
        $interval = $current_time->diff($next_dose['time']);
        $hours = $interval->h;
        $minutes = $interval->i;
      ?>
      <div class="text-right">
        <p class="text-xs text-slate-500 dark:text-navy-300">Next dose in</p>
        <p class="text-lg font-bold text-primary"><?= $hours ?>h <?= $minutes ?>m</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Horizontal scrolling dose timeline -->
    <div class="overflow-x-auto -mx-5 px-5 pb-2 mt-6">
      <div class="flex space-x-4 min-w-max">
        <?php foreach ($today_doses as $dose): 
          $is_next = ($next_dose && $dose['time_str'] === $next_dose['time_str'] && $dose['drug_name'] === $next_dose['drug_name']);
          $card_cls = $dose['is_past'] ? 'opacity-50' : ($is_next ? 'ring-2 ring-primary shadow-lg' : '');
          $badge_color = $group_badge[$dose['drug_group']] ?? 'bg-slate-200 text-slate-600';
        ?>
        <div class="card bg-white dark:bg-navy-800 p-4 min-w-[200px] <?= $card_cls ?>">
          <div class="flex items-center justify-between mb-2">
            <span class="text-2xl font-bold text-slate-700 dark:text-navy-100">
              <?= $dose['time']->format('g:i') ?>
            </span>
            <span class="text-xs font-medium text-slate-400 dark:text-navy-300">
              <?= $dose['time']->format('A') ?>
            </span>
          </div>
          
          <div class="space-y-1">
            <h3 class="font-semibold text-sm text-slate-700 dark:text-navy-100">
              <?= htmlspecialchars($dose['drug_name']) ?>
            </h3>
            <p class="text-xs font-mono text-slate-400 dark:text-navy-300"><?= $dose['drug_code'] ?></p>
            <div class="flex items-center justify-between mt-2">
              <span class="text-xs font-medium text-slate-600 dark:text-navy-200"><?= $dose['dose_mg'] ?>mg</span>
              <?php if ($dose['is_past']): ?>
              <span class="text-[10px] text-slate-400 dark:text-navy-300">Completed</span>
              <?php elseif ($is_next): ?>
              <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">NEXT</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- All Medications - Single Horizontal Row -->
  <div class="mb-5 mt-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100">All Medications</h2>
      <span class="text-sm text-slate-400 dark:text-navy-300"><?= count($drugs) ?> total</span>
    </div>
    
    <!-- Single horizontal scrolling row with ALL medications -->
    <div class="overflow-x-auto -mx-4 px-4 pb-3">
      <div class="flex space-x-4">
        <?php foreach ($drugs as $drug):
          $drug_ae = $ae_by_drug[$drug['drug_id']] ?? [];
          $has_ae = !empty($drug_ae);
          
          $formatted_times = [];
          if ($drug['all_times']) {
              $times_array = explode(', ', $drug['all_times']);
              foreach($times_array as $t) {
                  $formatted_times[] = date('h:i A', strtotime($t));
              }
          }

          $day_label = 'Daily';
          if ($drug['sched_frequency'] === 'weekly' && $drug['day_of_week']) {
              $days = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
              $day_label = $days[(int)$drug['day_of_week']] ?? 'Weekly';
          }
          
          $group_key = $drug['drug_group'] ?? 'other';
          $card_cls = $group_colors[$group_key] ?? '';
          $badge_cls = $group_badge[$group_key] ?? '';
          $group_name = $group_labels[$group_key] ?? 'Other';
        ?>
        <div class="card border-l-4 <?= $card_cls ?> p-4 min-w-[340px] flex-shrink-0 <?= $has_ae ? 'ring-1 ring-error/30' : '' ?>">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-1">
                <span class="rounded px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider <?= $badge_cls ?>">
                  <?= $group_name ?>
                </span>
              </div>
              <h3 class="font-semibold text-slate-700 dark:text-navy-100">
                <?= htmlspecialchars($drug['drug_name']); ?>
              </h3>
              <p class="text-xs font-mono text-slate-400 dark:text-navy-300"><?= $drug['drug_code'] ?></p>
            </div>
            <?php if ($has_ae): ?>
            <span class="flex size-6 items-center justify-center rounded-full bg-error/10 flex-shrink-0" x-tooltip.placement.top="'Active side effect reported'">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-error" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
            </span>
            <?php endif; ?>
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-400 dark:text-navy-300">Dose</span>
              <span class="font-medium text-slate-700 dark:text-navy-100"><?= $drug['dose_mg'] ?>mg</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400 dark:text-navy-300">Frequency</span>
              <span class="font-medium text-slate-700 dark:text-navy-100"><?= $drug['frequency_per_day'] ?>x/day (<?= $day_label ?>)</span>
            </div>
            <?php if (!empty($formatted_times)): ?>
            <div class="flex justify-between">
              <span class="text-slate-400 dark:text-navy-300">Times</span>
              <span class="font-medium text-slate-700 dark:text-navy-100 text-right"><?= implode(', ', $formatted_times) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($drug['duration_weeks']): ?>
            <div class="flex justify-between">
              <span class="text-slate-400 dark:text-navy-300">Duration</span>
              <span class="font-medium text-slate-700 dark:text-navy-100">
                <?= $drug['duration_weeks'] ?>w
                <?php if ($drug['start_week'] > 0): ?>(from wk <?= $drug['start_week'] ?>)<?php endif; ?>
              </span>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($has_ae): ?>
          <div class="mt-3 border-t border-error/20 pt-2">
            <?php foreach ($drug_ae as $ae): ?>
            <p class="text-xs text-error">
              <span class="font-semibold"><?= htmlspecialchars($ae['event_type']); ?></span>
              <span class="text-error/70"> — <?= ucfirst($ae['severity']) ?></span>
            </p>
            <?php endforeach; ?>
            <a href="report_side_effect.php" class="mt-1 inline-block text-[10px] font-medium text-error underline">Update report</a>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Important Notes -->
  <div class="card border-l-4 border-l-warning bg-warning/5 p-5 mt-6">
    <div class="flex items-start space-x-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
      </svg>
      <div class="text-sm text-slate-600 dark:text-navy-200">
        <p class="font-semibold mb-1">Important Reminders</p>
        <ul class="list-disc list-inside space-y-1 text-xs text-slate-500 dark:text-navy-300">
          <li>Take your medications at the exact times prescribed by your clinician.</li>
          <li>Do not skip doses or stop taking medication without consulting your clinician.</li>
          <li>If you experience any side effects, report them immediately using the "Report Side Effect" option in the menu.</li>
          <li>Keep all appointments for monitoring and lab tests.</li>
        </ul>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'patient_footer.php'; ?>