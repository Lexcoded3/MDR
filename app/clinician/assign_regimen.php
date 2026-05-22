<?php
session_start();
 $required_role = 'clinician';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'clinician_init.php';
require_once '../config/notify_helper.php';

 $pageTitle = 'Assign Regimen - GxAlert';
 $form_errors = [];
 $old = [];

 $patient_id = (int)($_GET['id'] ?? 0);
if ($patient_id === 0) { header("Location: patients.php"); exit; }

// 1. Verify patient exists
 $p_check = $conn->prepare("SELECT id, full_name, patient_code FROM patients WHERE id = ? AND created_by = ? AND is_active = 1");
 $p_check->bind_param("ii", $patient_id, $clinician_id);
 $p_check->execute();
 $p_result = $p_check->get_result();
if ($p_result->num_rows !== 1) { header("Location: patients.php"); exit; }
 $patient = $p_result->fetch_assoc();
 $p_check->close();

// 2. Check for existing active regimen
 $old_reg_stmt = $conn->prepare("SELECT id, regimen_name, start_date FROM treatment_regimens WHERE patient_id = ? AND status = 'active'");
 $old_reg_stmt->bind_param("i", $patient_id);
 $old_reg_stmt->execute();
 $old_regimen = $old_reg_stmt->get_result()->fetch_assoc();
 $old_reg_stmt->close();

// 3. Fetch all drugs
 $drugs_query = $conn->query("
    SELECT * FROM drugs WHERE is_active = 1 
    ORDER BY FIELD(drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other'), drug_name
");
 $drugs_list = $drugs_query->fetch_all(MYSQLI_ASSOC);

 $drug_groups = [];
foreach ($drugs_list as $d) {
    $g = $d['drug_group'] ?? 'other';
    if (!isset($drug_groups[$g])) $drug_groups[$g] = [];
    $drug_groups[$g][] = $d;
}

 $group_labels = [
    'group_a'  => 'Group A — Core (Highest Priority)',
    'group_b'  => 'Group B — Choice',
    'group_c'  => 'Group C — Add-on',
    'group_d1' => 'Group D1 — Repurposed (Oral)',
    'group_d2' => 'Group D2 — Injectables / Infused',
    'other'    => 'Other',
];

 $group_colors = [
    'group_a'  => 'border-error',
    'group_b'  => 'border-warning',
    'group_c'  => 'border-info',
    'group_d1' => 'border-secondary',
    'group_d2' => 'border-slate-400',
    'other'    => 'border-slate-300',
];

// Alpine init data
 $alpine_times = json_encode(['08:00']);
 $alpine_drugs = json_encode(new stdClass());

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $reg_name       = trim($_POST['regimen_name'] ?? '');
    $start_date     = $_POST['start_date'] ?? '';
    $end_date       = $_POST['end_date'] ?: null;
    $notes          = trim($_POST['notes'] ?? '') ?: null;
    $schedule_times = $_POST['schedule_times'] ?? [];
    $selected_drugs = $_POST['drugs'] ?? [];

    // Validation
    if (empty($start_date)) $form_errors['start_date'] = 'Start date is required';
    if (!empty($end_date) && $end_date < $start_date) $form_errors['end_date'] = 'End date cannot be before start date';
    if (empty($schedule_times)) $form_errors['schedule_times'] = 'Select at least one dose time';

    $valid_drugs = [];
    if (empty($selected_drugs)) {
        $form_errors['drugs'] = 'Select at least one drug';
    } else {
        foreach ($selected_drugs as $drug_id) {
            $dose       = $_POST['dose_' . $drug_id] ?? null;
            $duration   = $_POST['duration_' . $drug_id] ?? null;
            $start_week = $_POST['start_week_' . $drug_id] ?? 0;

            if (empty($dose) || $dose <= 0) {
                $form_errors['dose_' . $drug_id] = 'Required';
            } else {
                $valid_drugs[] = [
                    'drug_id'        => (int)$drug_id,
                    'dose_mg'        => (int)$dose,
                    'frequency'      => count($schedule_times),
                    'duration_weeks' => !empty($duration) ? (int)$duration : null,
                    'start_week'     => (int)$start_week,
                ];
            }
        }
    }

    // Recalculate Alpine state for form repopulation on error
    $alpine_times = json_encode($old['schedule_times'] ?? ['08:00']);
    $alpine_drugs = json_encode(array_fill_keys($old['drugs'] ?? [], true));

    if (empty($form_errors)) {
        $conn->begin_transaction();
        try {
            // 1. Discontinue old regimen
            if ($old_regimen) {
                $disc = $conn->prepare("UPDATE treatment_regimens SET status = 'discontinued', discontinued_at = CURDATE(), updated_at = NOW() WHERE patient_id = ? AND status = 'active'");
                $disc->bind_param("i", $patient_id);
                $disc->execute();
                $disc->close();

                $disc_sch = $conn->prepare("UPDATE medication_schedule SET is_active = 0 WHERE regimen_id = ?");
                $disc_sch->bind_param("i", $old_regimen['id']);
                $disc_sch->execute();
                $disc_sch->close();

                $disc_sch_all = $conn->prepare("
                    UPDATE medication_schedule ms
                    JOIN treatment_regimens tr ON ms.regimen_id = tr.id
                    SET ms.is_active = 0
                    WHERE tr.patient_id = ? AND tr.status = 'discontinued'
                ");
                $disc_sch_all->bind_param("i", $patient_id);
                $disc_sch_all->execute();
                $disc_sch_all->close();
            }

            // 2. Insert new regimen
            $reg_name_val = $reg_name ?: null;
            $ins_reg = $conn->prepare("
                INSERT INTO treatment_regimens (patient_id, regimen_name, start_date, end_date, notes, prescribed_by, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_review')
            ");
            $ins_reg->bind_param("issssi", $patient_id, $reg_name_val, $start_date, $end_date, $notes, $clinician_id);
            $ins_reg->execute();
            $new_reg_id = $conn->insert_id;
            $ins_reg->close();

            // 3. Insert drugs and schedules
            $ins_rd  = $conn->prepare("INSERT INTO regimen_drugs (regimen_id, drug_id, dose_mg, frequency_per_day, duration_weeks, start_week) VALUES (?, ?, ?, ?, ?, ?)");
            $ins_sch = $conn->prepare("INSERT INTO medication_schedule (regimen_id, drug_id, dose_time, frequency, effective_from, is_active) VALUES (?, ?, ?, 'daily', ?, 1)");

            foreach ($valid_drugs as $d) {
                $ins_rd->bind_param("iiiiii", $new_reg_id, $d['drug_id'], $d['dose_mg'], $d['frequency'], $d['duration_weeks'], $d['start_week']);
                $ins_rd->execute();

                foreach ($schedule_times as $time) {
                    $ins_sch->bind_param("iiss", $new_reg_id, $d['drug_id'], $time, $start_date);
                    $ins_sch->execute();
                }
            }
            $ins_rd->close();
            $ins_sch->close();

            // 4. Update patient status
            $upd_p = $conn->prepare("UPDATE patients SET treatment_status = 'on_treatment', updated_at = NOW() WHERE id = ?");
            $upd_p->bind_param("i", $patient_id);
            $upd_p->execute();
            $upd_p->close();

            // 5. Audit
            $audit_action = 'INSERT';
            $audit_table  = 'treatment_regimens';
            $audit_vals   = json_encode($valid_drugs);
            $ip_addr      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $aud = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $aud->bind_param("ississ", $clinician_id, $audit_action, $audit_table, $new_reg_id, $audit_vals, $ip_addr);
            $aud->execute();
            $aud->close();

            // Commit BEFORE notification — data is safe
            $conn->commit();

            // 6. Notification — FULLY ISOLATED, cannot break anything
            try {
                notify_regimen_review($conn, $clinician_id, $required_role, $patient['full_name'], $new_reg_id);
            } catch (\Throwable $notify_err) {
                error_log("Regimen notification error (non-fatal): " . $notify_err->getMessage());
            }

            // Redirect with JS fallback
            $redirect_url = "viewpatient.php?id=$patient_id&status=regimen_assigned";
            header("Location: $redirect_url");
            echo "<script>window.location.href='" . htmlspecialchars($redirect_url) . "';</script>";
            exit;

        } catch (\Throwable $e) {
            $conn->rollback();
            error_log("Regimen assignment error: " . $e->getMessage());
            $form_errors['db'] = 'A system error occurred. Please try again. (Ref: ' . substr(md5($e->getMessage()), 0, 8) . ')';
        }
    }
}
?>
<?php require_once 'clinician_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl">

  <!-- Breadcrumb -->
  <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="patients.php" class="hover:text-primary dark:hover:text-accent-light">Patients</a>
    <span class="mx-1">/</span>
    <a href="viewpatient.php?id=<?= $patient_id ?>" class="hover:text-primary dark:hover:text-accent-light"><?= htmlspecialchars($patient['full_name']) ?></a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100">Assign Regimen</span>
  </nav>

  <!-- Database Error -->
  <?php if (isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4">
    <p class="text-sm text-error"><?= $form_errors['db'] ?></p>
  </div>
  <?php endif; ?>

  <!-- Validation Errors -->
  <?php if (!empty($form_errors) && !isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4">
    <p class="text-sm font-medium text-error mb-1">Fix the following:</p>
    <ul class="list-disc list-inside text-xs text-error/80">
      <?php foreach ($form_errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- Active Regimen Warning -->
  <?php if ($old_regimen): ?>
  <div class="card border-l-4 border-l-warning bg-warning/5 p-4 mb-4 mt-5" x-data="{ show: true }" x-show="show" x-transition>
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm font-semibold text-warning">Active Regimen Exists</p>
        <p class="text-xs text-slate-600 dark:text-navy-200 mt-1">
          "<strong><?= htmlspecialchars($old_regimen['regimen_name'] ?? 'Individualized') ?></strong>" started on <?= $old_regimen['start_date'] ?>.
          Submitting this form will <strong>discontinue</strong> the current regimen and activate the new one.
        </p>
      </div>
      <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-navy-200 ml-4 shrink-0">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
  <?php endif; ?>

  <!-- ==================== FORM ==================== -->
<script>
function regimenForm() {
    return {

        selectedTimes: <?= $alpine_times ?>,
        selectedDrugs: <?= $alpine_drugs ?>,
        defaultTimes: ['08:00','12:00','14:00','16:00','20:00','21:00'],
        customTime: '',
        get customTimes() {
            return this.selectedTimes.filter(t => this.defaultTimes.indexOf(t) === -1);
        },
        addCustomTime() {
            var t = this.customTime.trim();
            if (t && this.selectedTimes.indexOf(t) === -1) {
                this.selectedTimes.push(t);
                this.customTime = '';
            }
        },
        removeTime(t) {
            this.selectedTimes = this.selectedTimes.filter(x => x !== t);
        }
    }
}
</script>

<form method="POST" action="" x-data="regimenForm()" @submit="submitting = true">
    <div class="grid grid-cols-12 gap-4 lg:gap-6">

      <!-- ========== LEFT COLUMN ========== -->
      <div class="col-span-12 lg:col-span-4">

        <!-- Regimen Details Card -->
        <div class="card mb-5 mt-5">

          <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
            <h2 class="font-medium text-slate-700 dark:text-navy-100">Regimen Details</h2>
          </div>

          <div class="grid grid-cols-1 gap-4 p-5">
            <!-- Regimen Name -->
            <div>
              <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Regimen Name (Optional)</label>
              <input type="text" name="regimen_name"
                     value="<?= htmlspecialchars($old['regimen_name'] ?? '') ?>"
                     placeholder="e.g. Short-course regimen"
                     class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Start Date <span class="text-error">*</span></label>
                <input type="date" name="start_date"
                       value="<?= $old['start_date'] ?? date('Y-m-d') ?>" required
                       class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['start_date']) ? 'ring-2 ring-error' : '' ?>">
              </div>
              <div>
                <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">End Date (Expected)</label>
                <input type="date" name="end_date"
                       value="<?= $old['end_date'] ?? '' ?>"
                       class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['end_date']) ? 'ring-2 ring-error' : '' ?>">
              </div>
            </div>
          </div>

          <!-- Schedule Times -->
          <div class="border-t border-slate-150 dark:border-navy-600 px-5 pt-4 pb-5">
            <label class="block text-xs font-medium text-slate-700 dark:text-navy-100 mb-2">
              Dose Times <span class="text-error">*</span>
              <span class="font-normal text-slate-400 dark:text-navy-300 ml-1">(Applies to all selected drugs)</span>
            </label>

            <?php if (isset($form_errors['schedule_times'])): ?>
            <p class="text-[10px] text-error mb-2"><?= $form_errors['schedule_times'] ?></p>
            <?php endif; ?>

            <div class="flex flex-wrap gap-2 items-center">

              <!-- Preset time chips -->
              <template x-for="time in defaultTimes" :key="'def-'+time">
                <label class="flex items-center space-x-1.5 cursor-pointer rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-100 dark:border-navy-600 dark:hover:bg-navy-700 has-[:checked]:border-primary has-[:checked]:bg-primary/10 dark:has-[:checked]:border-accent dark:has-[:checked]:bg-accent/10 transition-colors">
                  <input type="checkbox" name="schedule_times[]" :value="time"
                         class="form-checkbox size-3.5 rounded border-slate-400 bg-slate-100 before:bg-primary checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent"
                         x-model="selectedTimes">
                  <span class="text-xs font-mono font-medium text-slate-700 dark:text-navy-100" x-text="time"></span>
                </label>
              </template>

              <!-- Custom time chips -->
              <template x-for="time in customTimes" :key="'cus-'+time">
                <label class="flex items-center space-x-1.5 cursor-pointer rounded-lg border border-primary bg-primary/10 px-3 py-1.5 dark:border-accent dark:bg-accent/10 transition-colors">
                  <input type="checkbox" name="schedule_times[]" :value="time"
                         class="form-checkbox size-3.5 rounded border-slate-400 bg-slate-100 before:bg-primary checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent"
                         x-model="selectedTimes">
                  <span class="text-xs font-mono font-medium text-slate-700 dark:text-navy-100" x-text="time"></span>
                  <button type="button" @click.prevent="removeTime(time)"
                          class="ml-0.5 text-error/70 hover:text-error transition-colors" title="Remove">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </label>
              </template>

              <!-- Add custom time -->
              <div class="flex items-center space-x-1">
                <input type="time" x-model="customTime"
                       @keydown.enter.prevent="addCustomTime()"
                       class="form-input w-[5.5rem] rounded-lg bg-slate-150 px-2 py-1 text-xs font-mono dark:bg-navy-900/90">
                <button type="button" @click="addCustomTime()"
                        class="btn size-8 rounded-lg p-0 bg-slate-200 text-xs font-bold hover:bg-slate-300 dark:bg-navy-700 dark:hover:bg-navy-600 transition-colors"
                        title="Add custom dose time">+</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Clinical Notes Card -->
        <div class="card mb-5">
          <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
            <h2 class="font-medium text-slate-700 dark:text-navy-100">Clinical Notes</h2>
          </div>
          <div class="p-5">
            <textarea name="notes" rows="3"
                      placeholder="Any notes on rationale for this regimen choice..."
                      class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
          </div>
        </div>

      </div>

      <!-- ========== RIGHT COLUMN ========== -->
      <div class="col-span-12 lg:col-span-8">

        <!-- Drug Selection Card -->
        <div class="card mb-5 mt-5">
          <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3 flex items-center justify-between">
            <h2 class="font-medium text-slate-700 dark:text-navy-100">Select Drugs</h2>
            <?php if (isset($form_errors['drugs'])): ?>
            <span class="text-xs text-error"><?= $form_errors['drugs'] ?></span>
            <?php endif; ?>
          </div>

          <div class="p-5 space-y-6">
            <?php foreach ($group_labels as $group_key => $group_label):
                $group_drugs = $drug_groups[$group_key] ?? [];
                if (empty($group_drugs)) continue;
                $border_color = $group_colors[$group_key] ?? 'border-slate-300';
            ?>
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-navy-300 mb-3 border-l-4 <?= $border_color ?> pl-2">
                <?= $group_label ?>
              </h3>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                <?php foreach ($group_drugs as $drug):
                    $did        = $drug['id'];
                    $is_checked = isset($old['drugs']) && in_array($did, $old['drugs']);
                    $dose_err   = $form_errors['dose_' . $did] ?? '';
                ?>
                <div class="rounded-lg border border-slate-200 dark:border-navy-600 p-3 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5 dark:has-[:checked]:border-accent dark:has-[:checked]:bg-accent/5">
                  <label class="flex items-start space-x-2 cursor-pointer">
                    <input type="checkbox" name="drugs[]" value="<?= $did ?>"
                           class="form-checkbox mt-0.5 size-4 rounded border-slate-400 bg-slate-100 before:bg-primary checked:border-primary dark:border-navy-500 dark:bg-navy-900 dark:before:bg-accent dark:checked:border-accent"
                           <?= $is_checked ? 'checked' : '' ?>
                           @change="$el.checked ? selectedDrugs[<?= $did ?>] = true : selectedDrugs[<?= $did ?>] = false">
                    <div class="flex-1 min-w-0">
                      <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($drug['drug_name']) ?></span>
                      <span class="ml-1.5 text-[10px] font-mono text-slate-400 dark:text-navy-300"><?= $drug['drug_code'] ?></span>
                      <?php if (!empty($drug['default_dose_mg'])): ?>
                      <span class="ml-1 text-[10px] text-slate-400">(Def: <?= $drug['default_dose_mg'] ?><?= $drug['unit'] ?? 'mg' ?>)</span>
                      <?php endif; ?>
                    </div>
                  </label>

                  <!-- Dose / Duration / Start Week -->
                  <div class="mt-3 grid grid-cols-3 gap-2 pl-6 hidden"
                       :class="selectedDrugs[<?= $did ?>] ? '!grid' : ''">

                    <div>
                      <label class="block text-[10px] text-slate-400 dark:text-navy-300 mb-0.5">
                        Dose (mg) <span class="text-error">*</span>
                      </label>
                      <input type="number" name="dose_<?= $did ?>"
                             value="<?= $old['dose_' . $did] ?? $drug['default_dose_mg'] ?? '' ?>"
                             min="1"
                             :disabled="!selectedDrugs[<?= $did ?>]"
                             class="form-input w-full rounded bg-slate-100 px-2 py-1 text-xs disabled:opacity-40 dark:bg-navy-800 <?= $dose_err ? 'ring-2 ring-error' : '' ?>">
                      <?php if ($dose_err): ?>
                      <p class="text-[10px] text-error mt-0.5"><?= $dose_err ?></p>
                      <?php endif; ?>
                    </div>

                    <div>
                      <label class="block text-[10px] text-slate-400 dark:text-navy-300 mb-0.5">Duration (wks)</label>
                      <input type="number" name="duration_<?= $did ?>"
                             value="<?= $old['duration_' . $did] ?? '' ?>"
                             placeholder="Full course"
                             min="1"
                             :disabled="!selectedDrugs[<?= $did ?>]"
                             class="form-input w-full rounded bg-slate-100 px-2 py-1 text-xs disabled:opacity-40 dark:bg-navy-800">
                    </div>

                    <div>
                      <label class="block text-[10px] text-slate-400 dark:text-navy-300 mb-0.5">Start Week</label>
                      <input type="number" name="start_week_<?= $did ?>"
                             value="<?= $old['start_week_' . $did] ?? '0' ?>"
                             min="0"
                             :disabled="!selectedDrugs[<?= $did ?>]"
                             class="form-input w-full rounded bg-slate-100 px-2 py-1 text-xs disabled:opacity-40 dark:bg-navy-800"
                             title="Use for intro phases (e.g. BDQ loading dose)">
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>

              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3 mt-2 mb-8">
    
      <button type="submit"
        :disabled="submitting"
        class="btn h-10 bg-primary px-6 font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center gap-2">
  
          <!-- Spinner (shown when submitting) -->
          <svg x-show="submitting" class="animate-spin size-4 shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>

          <!-- Checkmark (shown when not submitting) -->
          <svg x-show="!submitting" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>

          <span x-text="submitting ? 'Saving...' : '<?= $old_regimen ? "Change Regimen" : "Assign Regimen" ?>'"></span>
        </button>
      <a href="viewpatient.php?id=<?= $patient_id ?>"
         class="btn h-10 bg-slate-150 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600 inline-flex items-center gap-2">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Cancel
      </a>
    </div>
  </form>
</div>

<?php
 $notify_text = '';
require_once 'clinician_footer.php'; ?>