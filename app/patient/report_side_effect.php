<?php
session_start();
 $required_role = 'patient';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'patient_init.php';

 $pageTitle = 'Report Side Effect - GxAlert';
 $notify_text = '';
 $form_errors = [];
 $form_old = [];

// Fetch patient's current regimen drugs for dropdown
 $my_drugs = [];
if ($regimen) {
    $md_stmt = $conn->prepare("
        SELECT d.id, d.drug_name, d.drug_code
        FROM regimen_drugs rd
        JOIN drugs d ON rd.drug_id = d.id
        WHERE rd.regimen_id = ? AND rd.is_active = 1
        ORDER BY d.drug_name
    ");
    $md_stmt->bind_param("i", $regimen['id']);
    $md_stmt->execute();
    $my_drugs = $md_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Common GxAlert side effect types
 $common_ae_types = [
    'Nausea / Vomiting',
    'Headache',
    'Dizziness',
    'Joint pain / Arthralgia',
    'Peripheral neuropathy (numbness/tingling)',
    'Hepatotoxicity (liver problems)',
    'QTc prolongation',
    'Visual disturbances',
    'Skin rash',
    'Hearing loss / Tinnitus',
    'Depression / Anxiety',
    'Insomnia',
    'Loss of appetite',
    'Diarrhea',
    'Other',
];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $drug_id     = $_POST['drug_id'] ?? '';
    $event_type  = trim($_POST['event_type'] ?? '');
    $severity    = $_POST['severity'] ?? '';
    $onset_date  = $_POST['onset_date'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $form_old = $_POST;

    // Validate
    if (empty($event_type)) $form_errors['event_type'] = 'Select or type the side effect';
    if (!in_array($severity, ['mild', 'moderate', 'severe', 'life_threatening'])) $form_errors['severity'] = 'Select severity';
    if (empty($onset_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $onset_date)) $form_errors['onset_date'] = 'Enter valid date';
    if (strtotime($onset_date) > strtotime('today')) $form_errors['onset_date'] = 'Date cannot be in the future';

    if (empty($form_errors)) {
        // Corrected INSERT statement
$ins = $conn->prepare("
    INSERT INTO adverse_events (
        patient_id, 
        drug_id, 
        event_type, 
        severity, 
        onset_date, 
        notes,         -- Changed 'description' to 'notes' to match your DB
        reported_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
");

// Bind parameters - Ensure drug_id is null if not provided
$actual_drug = !empty($drug_id) ? $drug_id : null;
$actual_description = !empty($description) ? $description : null;

$ins->bind_param("iissssi", 
    $patient_id, 
    $actual_drug, 
    $event_type, 
    $severity, 
    $onset_date, 
    $actual_description, 
    $_SESSION['id']
);
        $ins->execute();
        require_once '../config/notify_helper.php';
        notify_adverse_event($conn, $patient['full_name'], $event_type, $severity);
        // 1. Move insert_id into a variable
        $new_ae_id = $conn->insert_id;

        // 2. Pre-process the JSON and IP address into variables
        $post_json = json_encode($_POST);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // 3. Prepare the audit statement
        $audit = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, 'INSERT', 'adverse_events', ?, ?, ?)");

        // 4. Bind using only the clean variables
        // Mapping: user_id(i), record_id(i), new_values(s), ip_address(s)
        $audit->bind_param("iiss", $_SESSION['id'], $new_ae_id, $post_json, $ip_address);

        $audit->execute();

        $notify_text = 'Side Effect Reported';
        header("Location: report_side_effect.php?status=side_effect_reported");
        exit;
    }
}
?>
<?php require_once 'patient_header.php'; ?>


<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-1">Report a Side Effect</h1>
  <p class="text-sm text-slate-400 dark:text-navy-300 mb-6">Help your care team monitor your treatment. Reports are reviewed by your clinician.</p>
  <?php if (!empty($form_errors)): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-5">
    <p class="text-sm font-medium text-error">Please fix the following:</p>
    <ul class="mt-1 list-disc list-inside text-xs text-error/80">
      <?php foreach ($form_errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <?php if (empty($my_drugs)): ?>
  <div class="card border-l-4 border-l-warning bg-warning/5 p-4 mb-5 mt-5">
    <p class="text-sm text-slate-600 dark:text-navy-200">
      <span class="font-semibold text-warning">Note:</span> 
      You don't have an active regimen assigned. You can still report a side effect, but drug-specific tracking won't be available.
    </p>
  </div>
  <?php endif; ?>
  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
        <div class="col-span-12 grid lg:col-span-6">
            <div class="card mt-5">
              <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    General
                  </h4>
                </div>
              </div>
              <div class="space-y-4 p-4 sm:p-5">
                <form method="POST" action="" class="space-y-5">
                <!-- Which Drug -->
                <?php if (!empty($my_drugs)): ?>
                <label class="block">
                  <span>Which drug is causing this?</span>
                  <span class="text-slate-400 dark:text-navy-300">(optional)</span>
                </label>
                  <select name="drug_id" class="mt-1.5 w-full" x-init="$el._x_tom = new Tom($el,{create: true,sortField: {field: 'text',direction: 'asc'}})">
                      <option value="">-- Not sure / Other --</option>
                      <?php foreach ($my_drugs as $d): ?>
                      <option value="<?= $d['id'] ?>" <?= ($form_old['drug_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['drug_name']) ?> (<?= $d['drug_code'] ?>)
                      </option>
                      <?php endforeach; ?>
                    </select>
        <?php endif; ?>
                  <label class="block">
                  <span>Type of Side Effect</span>
                  <span class="text-slate-400 dark:text-navy-300">(optional)</span></label>
                  <input list="ae-types" name="event_type" 
             class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300 <?= isset($form_errors['event_type']) ? 'ring-2 ring-error' : '' ?>"
             placeholder="Type or select from list..." 
             value="<?= htmlspecialchars($form_old['event_type'] ?? '') ?>" required>
             <datalist id="ae-types">
                <?php foreach ($common_ae_types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>">
                <?php endforeach; ?>
              </datalist>
              <?php if (isset($form_errors['event_type'])): ?>
              <p class="mt-1 text-xs text-error"><?= $form_errors['event_type'] ?></p>
              <?php endif; ?>
                   </div>

                  <div class="space-y-4 p-4 sm:p-5">
                <label class="block">
                  <span>How severe is it?</span>
                  <span class="text-error dark:text-navy-300">(MUST)</span>
                </label>
                 <div class="grid grid-cols-1 gap-3 sm:grid-cols-1">
                      <?php 
                      $severities = [
                          'mild'             => ['Mild', 'bg-success/10 text-success border-success/30', 'Minimal discomfort, does not affect daily activities'],
                          'moderate'         => ['Moderate', 'bg-warning/10 text-warning border-warning/30', 'Affects some daily activities'],
                          'severe'           => ['Severe', 'bg-error/10 text-error border-error/30', 'Cannot do normal activities, needs medical attention'],
                          'life_threatening' => ['Life-threatening', 'bg-error/20 text-error border-error/50 font-semibold', 'Emergency — seek immediate medical help'],
                      ];
                      foreach ($severities as $val => $info): ?>
                      <label class="block cursor-pointer">
                        <input type="radio" name="severity" value="<?= $val ?>" class="sr-only peer" <?= ($form_old['severity'] ?? '') === $val ? 'checked' : '' ?> required>
                        <div class="rounded-lg border-2 border-slate-200 p-3 text-center transition-all peer-checked:border-current peer-checked:ring-2 peer-checked:ring-current/20 <?= $info[1] ?>">
                          <p class="text-sm"><?= $info[0] ?></p>
                          <p class="mt-1 text-[10px] text-slate-400 dark:text-navy-300 leading-tight"><?= $info[2] ?></p>
                        </div>
                      </label>
                      <?php endforeach; ?>
                    </div>
                    <?php if (isset($form_errors['severity'])): ?>
                    <p class="mt-1 text-xs text-error"><?= $form_errors['severity'] ?></p>
                    <?php endif; ?>
                   </div>
                </div>
            </div>
          <div class="col-span-12 grid lg:col-span-6">
            <div class="card mt-5">
              <div class="border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div class="flex items-center space-x-2">
                  <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 p-1 text-primary dark:bg-accent-light/10 dark:text-accent-light">
                    <i class="fa-solid fa-layer-group"></i>
                  </div>
                  <h4 class="text-lg font-medium text-slate-700 dark:text-navy-100">
                    Date and Description
                  </h4>
                </div>
              </div>
              <div class="space-y-4 p-4 sm:p-5">
                <label class="block">
                  <span>When did it start? </span><span class="text-error">*</span>

                  <input type="date" name="onset_date" max="<?= date('Y-m-d') ?>" class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['onset_date']) ? 'ring-2 ring-error' : '' ?>"
                  value="<?= $form_old['onset_date'] ?? date('Y-m-d') ?>" required>
                </label>
                <label class="block">
                  <span>Additional details</span><span class="text-slate-400 dark:text-navy-300">(optional)</span></label>
                  <textarea name="description" rows="3"
                class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 dark:placeholder:text-navy-300"
                placeholder="Describe what you're experiencing..."><?= htmlspecialchars($form_old['description'] ?? '') ?></textarea>
                </label>
                
                <!-- Warning -->
                <div class="rounded-lg bg-error/5 border border-error/20 p-3">
                  <p class="text-xs text-slate-600 dark:text-navy-200">
                    <span class="font-semibold text-error">If this is a medical emergency, do not use this form.</span> 
                    Contact your healthcare facility directly or go to the nearest emergency room.
                  </p>
                </div>
                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                  <button type="submit" class="btn h-10 bg-error px-6 font-medium text-white hover:bg-error/90 focus:bg-error/90 active:bg-error/80 dark:bg-error dark:hover:bg-error/90">
                    Submit Report
                  </button>
                  <a href="index.php" class="btn h-10 bg-slate-100 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Cancel</a>
                </div>
              </form>
              </div>
            </div>
          </div>
</div>
</div>

<?php 
 $notify_text = 'side_effect_reported';
 $notify_variant = 'success';
require_once 'patient_footer.php'; ?>