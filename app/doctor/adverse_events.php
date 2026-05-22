<?php
session_start();
$required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';

$pageTitle   = 'Adverse Events - GxAlert';
$notify_text = '';
$form_errors = [];

// Carry these through so form re-renders correctly on validation failure
$action_taken   = '';
$outcome        = '';
$modify_regimen = 0;
$manage_id      = (int)($_GET['manage'] ?? 0);

// Show success message if redirected back after resolve
if (isset($_GET['resolved'])) {
    $notify_text = 'Adverse event resolved successfully';
}

// Doctor location for facility filter
$loc_stmt = $conn->prepare("SELECT location FROM users WHERE id = ?");
$loc_stmt->bind_param("i", $doctor_id);
$loc_stmt->execute();
$doc_loc = $loc_stmt->get_result()->fetch_column();
$loc_stmt->close();

$severity_colors = [
    'mild'             => 'bg-info/10 text-info',
    'moderate'         => 'bg-warning/10 text-warning',
    'severe'           => 'bg-error/10 text-error',
    'life_threatening' => 'bg-error text-white',
];

$severity_labels = [
    'mild'             => 'Mild',
    'moderate'         => 'Moderate',
    'severe'           => 'Severe',
    'life_threatening' => 'Life-threatening',
];

$action_options = [
    'continued'        => 'Continued — monitoring only',
    'dose_reduced'     => 'Dose reduced',
    'drug_stopped'     => 'Drug stopped',
    'drug_substituted' => 'Drug substituted',
    'regimen_changed'  => 'Regimen changed',
    'hospitalized'     => 'Hospitalized',
];

$outcome_options = [
    'resolved'               => 'Resolved — no action needed',
    'resolved_with_treatment'=> 'Resolved — with treatment',
    'drug_modified'          => 'Resolved — drug dose modified',
    'drug_stopped'           => 'Resolved — drug stopped',
    'drug_substituted'       => 'Resolved — drug substituted',
    'ongoing'                => 'Ongoing — monitoring continues',
];

// Handle resolve POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_ae'])) {
    $ae_id          = (int)($_POST['ae_id'] ?? 0);
    $action_taken   = trim($_POST['action_taken'] ?? '');
    $outcome        = trim($_POST['outcome'] ?? '');
    $modify_regimen = isset($_POST['modify_regimen']) ? 1 : 0;

    // Keep form open on the right card if validation fails
    $manage_id = $ae_id;

    // Whitelist action_taken against enum
    if (!array_key_exists($action_taken, $action_options)) $form_errors[] = 'Select a valid action taken';
    if (!array_key_exists($outcome, $outcome_options))     $form_errors[] = 'Select a valid outcome';

    if (empty($form_errors)) {
        // Append outcome to notes — no outcome column in schema
        $notes_append = "\n[Outcome] " . $outcome_options[$outcome];
        if ($modify_regimen) $notes_append .= " · Regimen flagged for modification";

        $upd = $conn->prepare("
            UPDATE adverse_events
            SET resolution_date = CURDATE(),
                action_taken    = ?,
                notes           = CONCAT(IFNULL(notes, ''), ?)
            WHERE id = ? AND resolution_date IS NULL
        ");
        $upd->bind_param("ssi", $action_taken, $notes_append, $ae_id);
        $upd->execute();
        $upd->close();

        // Flag regimen for modification if requested
        if ($modify_regimen) {
            $ae_data = $conn->prepare("SELECT patient_id FROM adverse_events WHERE id = ?");
            $ae_data->bind_param("i", $ae_id);
            $ae_data->execute();
            $patient_id = (int)$ae_data->get_result()->fetch_column();
            $ae_data->close();

            if ($patient_id) {
                $note_text = "\n[AE #$ae_id] Drug modification flagged by doctor";
                $reg = $conn->prepare("
                    UPDATE treatment_regimens
                    SET notes = CONCAT(IFNULL(notes, ''), ?)
                    WHERE patient_id = ? AND status = 'active'
                ");
                $reg->bind_param("si", $note_text, $patient_id);
                $reg->execute();
                $reg->close();
            }
        }

        header("Location: adverse_events.php?resolved=1");
        exit;
    }
}

// Fetch active AEs for this doctor's facility
$ae_stmt = $conn->prepare("
    SELECT ae.*, p.full_name, p.patient_code, p.weight_kg,
           d.drug_name AS suspected_drug_name, d.drug_code,
           u.name AS reported_by_name,
           tr.regimen_name
    FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    LEFT JOIN drugs d ON ae.suspected_drug_id = d.id
    LEFT JOIN users u ON ae.reported_by = u.id
    LEFT JOIN treatment_regimens tr ON p.id = tr.patient_id AND tr.status = 'active'
    WHERE ae.resolution_date IS NULL
      AND p.facility_id IN (SELECT id FROM facilities WHERE name LIKE CONCAT('%', ?, '%'))
    ORDER BY
        CASE ae.severity
            WHEN 'life_threatening' THEN 1
            WHEN 'severe'           THEN 2
            WHEN 'moderate'         THEN 3
            ELSE 4
        END,
        ae.onset_date DESC
");
$ae_stmt->bind_param("s", $doc_loc);
$ae_stmt->execute();
$active_aes = $ae_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ae_stmt->close();
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Adverse Events Management</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">Review and manage side effects reported by patients and clinicians.</p>
  </div>

  <?php if ($notify_text): ?>
  <div class="alert mt-4 flex overflow-hidden rounded-lg bg-success/10 text-success dark:bg-success/15">
    <div class="flex flex-1 items-center p-4 text-sm font-medium"><?= htmlspecialchars($notify_text) ?></div>
    <div class="w-1.5 bg-success"></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($form_errors)): ?>
  <div class="alert mt-4 flex overflow-hidden rounded-lg bg-error/10 text-error dark:bg-error/15">
    <div class="flex flex-1 items-center p-4 text-sm"><?= implode('<br>', array_map('htmlspecialchars', $form_errors)) ?></div>
    <div class="w-1.5 bg-error"></div>
  </div>
  <?php endif; ?>

  <!-- Severity Summary -->
  <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
    <?php
    $counts = ['mild' => 0, 'moderate' => 0, 'severe' => 0, 'life_threatening' => 0];
    foreach ($active_aes as $ae) $counts[$ae['severity']]++;
    foreach ($counts as $sev => $cnt):
    ?>
    <div class="card p-3 flex items-center gap-3">
      <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $severity_colors[$sev] ?>">
        <?= $severity_labels[$sev] ?>
      </span>
      <span class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= $cnt ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Active Events -->
  <?php if (empty($active_aes)): ?>
  <div class="card mt-6 p-12 text-center text-slate-400">
    <svg class="mx-auto size-12 mb-3 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-lg font-medium">No active adverse events</p>
  </div>
  <?php else: ?>
  <div class="mt-5 space-y-4">
    <?php foreach ($active_aes as $ae):
      $is_managing = ($manage_id === (int)$ae['id']);
      $duration    = (new DateTime('today'))->diff(new DateTime($ae['onset_date']))->days;
    ?>
    <div class="card <?= $is_managing ? 'ring-2 ring-primary' : '' ?>">
      <div class="p-5">

        <!-- Header row -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          <div class="flex items-start gap-3">
            <div class="avatar size-10 mt-0.5">
              <div class="is-initial rounded-full <?= $severity_colors[$ae['severity']] ?>">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
              </div>
            </div>
            <div>
              <h3 class="font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['event_type']) ?></h3>
              <p class="text-sm text-slate-400 dark:text-navy-300">
                <?= htmlspecialchars($ae['full_name']) ?> — <?= htmlspecialchars($ae['patient_code']) ?> · <?= htmlspecialchars($ae['weight_kg']) ?>kg
              </p>
              <?php if ($ae['suspected_drug_name']): ?>
              <p class="mt-1 text-sm">
                <span class="text-slate-400">Suspected drug:</span>
                <span class="font-medium text-error"><?= htmlspecialchars($ae['suspected_drug_name']) ?> (<?= htmlspecialchars($ae['drug_code']) ?>)</span>
              </p>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $severity_colors[$ae['severity']] ?>">
              <?= $severity_labels[$ae['severity']] ?>
            </span>
            <?php if (!$is_managing): ?>
            <a href="adverse_events.php?manage=<?= (int)$ae['id'] ?>"
               class="btn size-8 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600" title="Manage">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
              </svg>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Detail grid -->
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div>
            <span class="text-slate-400 dark:text-navy-300">Onset</span>
            <p class="font-medium text-slate-700 dark:text-navy-100"><?= date('M j, Y', strtotime($ae['onset_date'])) ?></p>
          </div>
          <div>
            <span class="text-slate-400 dark:text-navy-300">Reported by</span>
            <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['reported_by_name'] ?? 'Patient') ?></p>
          </div>
          <div>
            <span class="text-slate-400 dark:text-navy-300">Current Regimen</span>
            <p class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($ae['regimen_name'] ?? 'None') ?></p>
          </div>
          <div>
            <span class="text-slate-400 dark:text-navy-300">Duration</span>
            <p class="font-medium text-slate-700 dark:text-navy-100"><?= $duration ?> day<?= $duration !== 1 ? 's' : '' ?></p>
          </div>
        </div>

        <?php if ($ae['notes']): ?>
        <p class="mt-3 text-sm text-slate-500 dark:text-navy-300 bg-slate-50 dark:bg-navy-800 rounded-lg p-3">
          <?= nl2br(htmlspecialchars($ae['notes'])) ?>
        </p>
        <?php endif; ?>

        <!-- Resolution Form -->
        <?php if ($is_managing): ?>
        <form method="POST" class="mt-4 border-t border-slate-100 dark:border-navy-600 pt-4">
          <input type="hidden" name="resolve_ae" value="1">
          <input type="hidden" name="ae_id" value="<?= (int)$ae['id'] ?>">
          <h4 class="text-sm font-semibold text-slate-700 dark:text-navy-100 mb-3">Resolve This Event</h4>
          <div class="space-y-4">

            <!-- Action Taken -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Action Taken *</label>
              <select name="action_taken" required
                      class="form-select w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90 max-w-xs">
                <option value="">Select action taken</option>
                <?php foreach ($action_options as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $action_taken === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Outcome -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Outcome *</label>
              <select name="outcome" required
                      class="form-select w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90 max-w-xs">
                <option value="">Select outcome</option>
                <?php foreach ($outcome_options as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $outcome === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Regimen flag -->
            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" name="modify_regimen" value="1"
                     <?= $modify_regimen ? 'checked' : '' ?>
                     class="form-checkbox size-5 rounded border-slate-400 bg-slate-100 checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:checked:border-accent">
              <span class="text-sm text-slate-600 dark:text-navy-200">Flag regimen for modification</span>
            </label>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
              <a href="adverse_events.php"
                 class="btn h-9 border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 px-5">Cancel</a>
              <button type="submit"
                      class="btn h-9 bg-success text-white hover:bg-success/90 px-5">Resolve Event</button>
            </div>
          </div>
        </form>
        <?php endif; ?>

      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php $notify_variant = 'success'; require_once 'doctor_footer.php'; ?>