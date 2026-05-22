<?php
session_start();
$required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

// JSON endpoint for patient search
if (isset($_GET['q']) && !isset($_GET['patient_id'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['q']);
    $stmt = $conn->prepare("SELECT id, full_name, patient_code, phone FROM patients WHERE is_active = 1 AND (full_name LIKE ? OR patient_code LIKE ?) ORDER BY full_name LIMIT 10");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

$pageTitle = 'Drug Susceptibility - GxAlert';
$form_errors = [];
$old = [];

$test_methods = [
    'gene_xpert'         => 'GeneXpert MTB/RIF',
    'lpa'                => 'Line Probe Assay (LPA)',
    'phenotypic_mgit'    => 'MGIT Phenotypic DST',
    'phenotypic_lj'      => 'LJ Phenotypic DST',
    'molecular_wgs'      => 'Whole Genome Sequencing (WGS)',
];

// Fetch all drugs from catalog
$all_drugs = $conn->query("
    SELECT id, drug_name, drug_code, drug_group 
    FROM drugs WHERE is_active = 1 
    ORDER BY FIELD(drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other'), drug_name
")->fetch_all(MYSQLI_ASSOC);

$drug_groups = [];
foreach ($all_drugs as $d) {
    $g = $d['drug_group'] ?? 'other';
    if (!isset($drug_groups[$g])) $drug_groups[$g] = [];
    $drug_groups[$g][] = $d;
}

$group_labels = [
    'group_a' => 'Group A — Core (First-line injectables + bedaquiline/linezolid)',
    'group_b' => 'Group B — Choice (fluoroquinolones, clofazimine, delamanid)',
    'group_c' => 'Group C — Add-on (cycloserine, terizidone, PAS, ethionamide)',
    'group_d1' => 'Group D1 — Repurposed (pyrazinamide, ethambutol)',
    'group_d2' => 'Group D2 — Injectables (amikacin, capreomycin, streptomycin)',
    'other'   => 'Other',
];
$group_colors = [
    'group_a' => 'border-error', 'group_b' => 'border-warning', 'group_c' => 'border-info',
    'group_d1' => 'border-secondary', 'group_d2' => 'border-slate-400', 'other' => 'border-slate-300',
];

// Fetch previous DST results if patient selected
$previous_dst = [];
if (isset($_GET['patient_id']) && $_GET['patient_id'] > 0) {
    $pid = (int)$_GET['patient_id'];
    $prev_stmt = $conn->prepare("
        SELECT ds.drug_id, ds.result, ds.result_date, d.drug_name, d.drug_code
        FROM drug_susceptibility ds
        JOIN drugs d ON ds.drug_id = d.id
        WHERE ds.patient_id = ?
        ORDER BY ds.result_date DESC, ds.created_at DESC
    ");
    $prev_stmt->bind_param("i", $pid);
    $prev_stmt->execute();
    $prev_res = $prev_stmt->get_result();
    while ($row = $prev_res->fetch_assoc()) {
        if (!isset($previous_dst[$row['drug_id']])) {
            $previous_dst[$row['drug_id']] = $row;
        }
    }
    $prev_stmt->close();
}
$pre_patient = null;
if (isset($_GET['patient_id']) && $_GET['patient_id'] > 0) {
    $pid = (int)$_GET['patient_id'];
    $pp_stmt = $conn->prepare("SELECT id, full_name, patient_code, phone FROM patients WHERE id = ? AND is_active = 1");
    $pp_stmt->bind_param("i", $pid);
    $pp_stmt->execute();
    $pre_patient = $pp_stmt->get_result()->fetch_assoc();
    $pp_stmt->close();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $patient_id   = (int)($_POST['patient_id'] ?? 0);
    $test_method  = $_POST['test_method'] ?? '';
    $result_date  = $_POST['result_date'] ?? '';
    $specimen_date = $_POST['specimen_date'] ?? '';
    $lab_facility = trim($_POST['lab_facility'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');
    $drug_results = $_POST['drug_result'] ?? [];

    if ($patient_id === 0) $form_errors['patient_id'] = 'Select a patient';
    if (!in_array($test_method, array_keys($test_methods))) $form_errors['test_method'] = 'Select test method';
    if (empty($result_date)) $form_errors['result_date'] = 'Result date is required';

    if (empty($drug_results)) {
        $form_errors['drugs'] = 'Record at least one drug result';
    } else {
        $has_tested = false;
        foreach ($drug_results as $drug_id => $status) {
            if (!in_array($status, ['sensitive', 'resistant', 'indeterminate', 'not_done'])) {
                $form_errors['drug_' . $drug_id] = 'Invalid status';
            }
            if ($status !== 'not_done') $has_tested = true;
        }
        if (!$has_tested) {
            $form_errors['drugs'] = 'At least one drug must be tested (not all "Not Done")';
        }
    }
    if ($result === 'resistant') {
    notify_dst_resistant($conn, $patient_name, $drug_name);
}
    if (empty($form_errors)) {
        $conn->begin_transaction();
        try {
            $ins = $conn->prepare("
                INSERT INTO drug_susceptibility (patient_id, drug_id, test_method, result, specimen_date, result_date, lab_facility, performed_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($drug_results as $drug_id => $status) {
                if ($status === 'not_done') continue; // Don't save "not done" entries
                
                $v_drug_id = (int)$drug_id;
                $v_spec_date = $specimen_date ?: null;
                $v_lab_fac = $lab_facility ?: null;
                $v_notes = $notes ?: null;

                $ins->bind_param("iisssssis",
                    $patient_id, $v_drug_id, $test_method, $status,
                    $v_spec_date, $result_date, $v_lab_fac,
                    $lab_id, $v_notes
                );
                $ins->execute();
            }

            // Audit
            $v_action = 'INSERT';
            $v_table = 'drug_susceptibility';
            $v_json_data = json_encode($drug_results);
            $v_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $aud = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $aud->bind_param("issiis", $lab_id, $v_action, $v_table, $patient_id, $v_json_data, $v_ip);
            $aud->execute();

            $conn->commit();
            
            // Clear localStorage draft
            echo "<script>localStorage.removeItem('dst_draft');</script>";
            
            header("Location: find_patient.php?status=dst_saved");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("DST save error: " . $e->getMessage());
            $form_errors['db'] = 'Database error. Transaction rolled back.';
        }
    }
}
?>
<?php require_once 'lab_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-6xl" x-data="dstForm()" x-init="init()">
  <nav class="mb-3 text-xs text-slate-400 dark:text-navy-300">
    <a href="index.php" class="hover:text-primary dark:hover:text-accent-light">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100">Drug Susceptibility</span>
  </nav>

  <?php if (isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4"><p class="text-sm text-error"><?= $form_errors['db'] ?></p></div>
  <?php endif; ?>
  <?php if (!empty($form_errors) && !isset($form_errors['db'])): ?>
  <div class="card border-l-4 border-l-error bg-error/5 p-4 mb-4">
    <p class="text-sm font-medium text-error mb-1">Fix the following:</p>
    <ul class="list-disc list-inside text-xs text-error/80"><?php foreach ($form_errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
  </div>
  <?php endif; ?>

  <!-- Visual Result Summary (shows after selection) -->
  <div x-show="summary.total > 0" x-cloak
       class="card border-l-4 border-l-primary bg-primary/5 p-5 mb-6 mt-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-navy-100">Test Summary</h3>
      <button type="button" @click="clearAllResults()" class="text-xs text-slate-500 hover:text-error dark:text-navy-300">Clear All</button>
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
      <div class="rounded-lg bg-success/10 p-3 text-center">
        <p class="text-2xl font-bold text-success" x-text="summary.sensitive"></p>
        <p class="text-[10px] font-medium text-success/70 uppercase">Sensitive</p>
      </div>
      <div class="rounded-lg bg-error/10 p-3 text-center">
        <p class="text-2xl font-bold text-error" x-text="summary.resistant"></p>
        <p class="text-[10px] font-medium text-error/70 uppercase">Resistant</p>
      </div>
      <div class="rounded-lg bg-warning/10 p-3 text-center">
        <p class="text-2xl font-bold text-warning" x-text="summary.indeterminate"></p>
        <p class="text-[10px] font-medium text-warning/70 uppercase">Indeterminate</p>
      </div>
      <div class="rounded-lg bg-slate-200 dark:bg-navy-700 p-3 text-center">
        <p class="text-2xl font-bold text-slate-600 dark:text-navy-200" x-text="summary.not_done"></p>
        <p class="text-[10px] font-medium text-slate-500 dark:text-navy-300 uppercase">Not Done</p>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="h-2 rounded-full bg-slate-200 dark:bg-navy-700 overflow-hidden flex mt-6">
      <div class="bg-success transition-all" :style="`width: ${(summary.sensitive/summary.total)*100}%`"></div>
      <div class="bg-error transition-all" :style="`width: ${(summary.resistant/summary.total)*100}%`"></div>
      <div class="bg-warning transition-all" :style="`width: ${(summary.indeterminate/summary.total)*100}%`"></div>
    </div>

    <!-- Resistance Pattern Alert -->
    <div x-show="resistancePattern" x-cloak class="mt-5 rounded-lg bg-warning/10 border border-warning/20 p-3">
      <div class="flex items-start space-x-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-warning mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <div>
          <p class="text-xs font-semibold text-warning">Detected Pattern:</p>
          <p class="text-xs text-slate-600 dark:text-navy-200" x-text="resistancePattern"></p>
        </div>
      </div>
    </div>
  </div>

  <form method="POST" action="" @submit="clearDraft()">
    <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 lg:mt-6 lg:gap-6">
<div class="col-span-12 lg:col-span-4">
    <!-- Patient -->
    <div class="card mb-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3 flex items-center justify-between">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Patient</h2>
        <a href="find_patient.php" class="text-xs text-primary dark:text-accent-light">Change →</a>
      </div>
      <div class="p-5">
        <input type="hidden" name="patient_id" x-model="selectedPatient.id">
        
        <div x-show="selectedPatient.id" class="flex items-center space-x-3 rounded-lg bg-primary/5 p-3">
          <div class="flex size-10 items-center justify-center rounded-full bg-primary/10">
            <svg class="size-5 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-700 dark:text-navy-100" x-text="selectedPatient.full_name"></p>
            <p class="text-[10px] font-mono text-slate-400 dark:text-navy-300" x-text="selectedPatient.patient_code"></p>
          </div>
        </div>

        <div x-show="!selectedPatient.id">
          <div class="flex flex-col gap-2">
            <input type="text" 
                   x-ref="patientSearch"
                   placeholder="Search patient by name or code..." 
                   autocomplete="off"
                   class="form-input rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"
                   @input.debounce.300ms="searchPatients($event.target.value)"
                   <?= isset($form_errors['patient_id']) ? 'ring-2 ring-error' : '' ?>>
            <?php if (isset($form_errors['patient_id'])): ?>
            <p class="text-xs text-error"><?= $form_errors['patient_id'] ?></p>
            <?php endif; ?>
          </div>
          
          <div x-show="searchResults.length > 0"
               class="mt-2 max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-700 shadow-lg">
            <template x-for="p in searchResults" :key="p.id">
              <button type="button" 
                      @click="selectPatient(p)"
                      class="flex items-center justify-between w-full px-4 py-3 text-left hover:bg-slate-100 dark:hover:bg-navy-600 border-b border-slate-100 dark:border-navy-600 last:border-0">
                <div>
                  <p class="text-sm font-medium text-slate-700 dark:text-navy-100" x-text="p.full_name"></p>
                  <p class="text-[10px] font-mono text-slate-400" x-text="p.patient_code"></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Previous DST Results (if available) -->
    <?php if (!empty($previous_dst) && isset($_GET['patient_id'])): ?>
    <div class="card border-l-4 border-l-info bg-info/5 p-5 mb-5">
      <div class="flex items-center space-x-2 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h3 class="text-sm font-semibold text-slate-700 dark:text-navy-100">Previous DST Results</h3>
      </div>
      <div class="overflow-x-auto">
        <div class="flex space-x-3">
          <?php 
          $shown = 0;
          foreach ($previous_dst as $drug_id => $prev): 
            if ($shown >= 8) break; // Show max 8
            $shown++;
            $result_badge = '';
            $result_text = '';
            if ($prev['result'] === 'resistant') {
              $result_badge = 'bg-error/10 text-error';
              $result_text = 'Resistant';
            } elseif ($prev['result'] === 'sensitive') {
              $result_badge = 'bg-success/10 text-success';
              $result_text = 'Sensitive';
            } else {
              $result_badge = 'bg-warning/10 text-warning';
              $result_text = ucfirst($prev['result']);
            }
          ?>
          <div class="min-w-[140px] rounded-lg border border-slate-200 dark:border-navy-600 p-3">
            <p class="text-xs font-semibold text-slate-700 dark:text-navy-100 mb-1"><?= htmlspecialchars($prev['drug_name']) ?></p>
            <span class="inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase <?= $result_badge ?>">
              <?= $result_text ?>
            </span>
            <p class="text-[9px] text-slate-400 mt-1"><?= date('M d, Y', strtotime($prev['result_date'])) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Test Method -->
    <div class="card mb-5 mt-5">
    <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Test Method & Dates</h2>
    </div>

    <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
        
        <div class="sm:col-span-2">
            <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Test Method <span class="text-error">*</span></label>
            <select name="test_method" required x-model="testMethod" @change="saveDraft()"
                class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['test_method']) ? 'ring-2 ring-error' : '' ?>">
                <option value="">Select method...</option>
                <?php foreach ($test_methods as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Specimen Date</label>
            <input type="date" name="specimen_date" x-model="specimenDate" @change="saveDraft()" max="<?= date('Y-m-d') ?>"
                class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50">
        </div>

        <div>
            <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Result Date <span class="text-error">*</span></label>
            <input type="date" name="result_date" x-model="resultDate" @change="saveDraft()" max="<?= date('Y-m-d') ?>" required
                class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 <?= isset($form_errors['result_date']) ? 'ring-2 ring-error' : '' ?>">
        </div>
    </div>

    <div class="border-t border-slate-150 dark:border-navy-600 px-5 pb-5 pt-2">
        <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Lab Facility</label>
        <input type="text" name="lab_facility" x-model="labFacility" @input.debounce="saveDraft()"
            placeholder="e.g. National Reference Lab"
            class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50 max-w-md">
    </div>
</div>
  </div>
<div class="col-span-12 lg:col-span-8">
    <!-- Drug Results -->
    <div class="card mb-5 mt-5">
      <div class="border-b border-slate-150 dark:border-navy-600 px-5 py-3 flex items-center justify-between">
        <h2 class="font-medium text-slate-700 dark:text-navy-100">Drug Results</h2>
        <div class="flex items-center space-x-2">
          <label class="flex items-center space-x-2">
            <input type="checkbox" x-model="hideNotDone" class="form-checkbox is-basic size-4 rounded border-slate-400/70 checked:bg-primary checked:border-primary dark:border-navy-400 dark:checked:bg-accent dark:checked:border-accent">
            <span class="text-xs text-slate-500 dark:text-navy-300">Hide "Not Done"</span>
          </label>
        </div>
      </div>
      <div class="p-5 space-y-5">

        <!-- Resistance Pattern Templates -->
        <div class="rounded-lg bg-slate-100 dark:bg-navy-800 p-4">
          <p class="text-xs font-semibold text-slate-600 dark:text-navy-200 mb-2">Quick Templates:</p>
          <div class="flex flex-wrap gap-2">
            <button type="button" @click="applyTBPattern()" 
                    class="btn rounded-full px-3 py-1.5 text-xs bg-error/10 text-error hover:bg-error/20 border border-error/20">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
              GxAlert Pattern
            </button>
            <button type="button" @click="applyXDRPattern()" 
                    class="btn rounded-full px-3 py-1.5 text-xs bg-error text-white hover:bg-error-focus border border-error">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
              XDR-TB Pattern
            </button>
            <button type="button" @click="selectAll('sensitive')" 
                    class="btn rounded-full px-3 py-1.5 text-xs bg-success/10 text-success hover:bg-success/20">
              All Sensitive
            </button>
            <button type="button" @click="selectAll('resistant')" 
                    class="btn rounded-full px-3 py-1.5 text-xs bg-error/10 text-error hover:bg-error/20">
              All Resistant
            </button>
            <button type="button" @click="selectAll('not_done')" 
                    class="btn rounded-full px-3 py-1.5 text-xs bg-slate-200 text-slate-600 hover:bg-slate-300 dark:bg-navy-700 dark:text-navy-200">
              All Not Done
            </button>
          </div>
        </div>

        <?php foreach ($group_labels as $group_key => $group_label):
            $group_drugs = $drug_groups[$group_key] ?? [];
            if (empty($group_drugs)) continue;
            $border_color = $group_colors[$group_key] ?? 'border-slate-300';
        ?>
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-navy-300 mb-3 border-l-4 <?= $border_color ?> pl-2">
            <?= $group_label ?>
          </h3>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($group_drugs as $drug):
                $did = $drug['id'];
                $has_previous = isset($previous_dst[$did]);
                $prev_result = $has_previous ? $previous_dst[$did]['result'] : null;
            ?>
            <div x-show="!hideNotDone || (drugResults[<?= $did ?>] && drugResults[<?= $did ?>] !== 'not_done')"
                 class="rounded-lg border border-slate-200 dark:border-navy-600 p-3 hover:shadow-md transition-shadow"
                 :class="{'ring-2 ring-primary/30': drugResults[<?= $did ?>] && drugResults[<?= $did ?>] !== 'not_done'}">
              <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($drug['drug_name']) ?></span>
                    <span class="inline-block rounded-full size-2 transition-all"
                          :class="{
                            'bg-success': drugResults[<?= $did ?>] === 'sensitive',
                            'bg-error': drugResults[<?= $did ?>] === 'resistant',
                            'bg-warning': drugResults[<?= $did ?>] === 'indeterminate',
                            'bg-slate-300': !drugResults[<?= $did ?>] || drugResults[<?= $did ?>] === 'not_done'
                          }"></span>
                  </div>
                  <p class="text-[10px] font-mono text-slate-400"><?= $drug['drug_code'] ?></p>
                  <?php if ($has_previous): ?>
                  <p class="text-[9px] text-slate-500 dark:text-navy-300 mt-1">
                    Previous: <span class="font-semibold <?= $prev_result === 'resistant' ? 'text-error' : 'text-success' ?>">
                      <?= ucfirst($prev_result) ?>
                    </span>
                  </p>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Mobile: Buttons, Desktop: Dropdown -->
              <div class="block sm:hidden">
                <div class="grid grid-cols-2 gap-1">
                  <button type="button" @click="setDrugResult(<?= $did ?>, 'sensitive')"
                          class="rounded px-2 py-1.5 text-[10px] font-bold uppercase transition-all"
                          :class="drugResults[<?= $did ?>] === 'sensitive' ? 'bg-success text-white' : 'bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-300'">
                    S
                  </button>
                  <button type="button" @click="setDrugResult(<?= $did ?>, 'resistant')"
                          class="rounded px-2 py-1.5 text-[10px] font-bold uppercase transition-all"
                          :class="drugResults[<?= $did ?>] === 'resistant' ? 'bg-error text-white' : 'bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-300'">
                    R
                  </button>
                  <button type="button" @click="setDrugResult(<?= $did ?>, 'indeterminate')"
                          class="rounded px-2 py-1.5 text-[10px] font-bold uppercase transition-all"
                          :class="drugResults[<?= $did ?>] === 'indeterminate' ? 'bg-warning text-white' : 'bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-300'">
                    I
                  </button>
                  <button type="button" @click="setDrugResult(<?= $did ?>, 'not_done')"
                          class="rounded px-2 py-1.5 text-[10px] font-bold uppercase transition-all"
                          :class="!drugResults[<?= $did ?>] || drugResults[<?= $did ?>] === 'not_done' ? 'bg-slate-300 text-slate-700 dark:bg-navy-600 dark:text-navy-200' : 'bg-slate-100 text-slate-600 dark:bg-navy-700 dark:text-navy-300'">
                    ND
                  </button>
                </div>
              </div>

              <div class="hidden sm:block">
                <select x-model="drugResults[<?= $did ?>]" 
                        @change="updateDrugResult(<?= $did ?>)"
                        name="drug_result[<?= $did ?>]"
                        class="form-select w-full rounded-lg border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-700 px-2 py-1.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-accent transition-all"
                        :class="{
                          'bg-error/10 text-error border-error/30': drugResults[<?= $did ?>] === 'resistant',
                          'bg-success/10 text-success border-success/30': drugResults[<?= $did ?>] === 'sensitive',
                          'bg-warning/10 text-warning border-warning/30': drugResults[<?= $did ?>] === 'indeterminate'
                        }">
                  <option value="not_done">Not Done</option>
                  <option value="sensitive">Sensitive</option>
                  <option value="resistant">Resistant</option>
                  <option value="indeterminate">Indeterminate</option>
                </select>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Notes with Auto-interpretation -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs text-slate-400 dark:text-navy-300">Notes</label>
            <button type="button" @click="generateInterpretation()" 
                    class="text-[10px] text-primary dark:text-accent-light hover:underline">
              ✨ Generate Interpretation
            </button>
          </div>
          <textarea name="notes" rows="3" x-model="notes" @input.debounce="saveDraft()"
                    placeholder="Clinical interpretation or observations..."
                    class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm ring-primary/50 dark:bg-navy-900/90 dark:ring-accent/50"></textarea>
        </div>
      </div>
    </div>

    <!-- Sticky Submit Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-navy-800 border-t border-slate-200 dark:border-navy-600 p-4 shadow-lg z-40 lg:left-0">
      <div class="max-w-6xl mx-auto flex gap-3 justify-between items-center">
        <div class="text-xs text-slate-500 dark:text-navy-300">
          <span x-show="lastSaved" x-text="'Draft saved ' + lastSaved"></span>
        </div>
        <div class="flex gap-3">
          <a href="index.php" class="btn h-10 bg-slate-100 px-6 font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">Cancel</a>
          <button type="submit" 
                  :disabled="!selectedPatient.id || summary.total === 0"
                  class="btn h-10 bg-primary px-6 font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="inline size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
            </svg>
            Save DST Results
          </button>
        </div>
      </div>
    </div>
</div>
</div>
    <!-- Bottom spacing for sticky bar -->
    <div class="h-20"></div>
  </form>
</div>

<script>
function dstForm() {
    return {
        selectedPatient: { id: null, full_name: '', patient_code: '' },
        searchResults: [],
        drugResults: {},
        testMethod: '<?= $old['test_method'] ?? '' ?>',
        specimenDate: '<?= $old['specimen_date'] ?? '' ?>',
        resultDate: '<?= $old['result_date'] ?? date('Y-m-d') ?>',
        labFacility: '<?= htmlspecialchars($old['lab_facility'] ?? $_SESSION['location'] ?? '') ?>',
        notes: '<?= htmlspecialchars($old['notes'] ?? '') ?>',
        hideNotDone: false,
        lastSaved: null,
        
        init() {
            // Load draft from localStorage
            const draft = localStorage.getItem('dst_draft');
            if (draft) {
                try {
                    const data = JSON.parse(draft);
                    this.drugResults = data.drugResults || {};
                    this.testMethod = data.testMethod || '';
                    this.specimenDate = data.specimenDate || '';
                    this.resultDate = data.resultDate || '<?= date('Y-m-d') ?>';
                    this.labFacility = data.labFacility || '';
                    this.notes = data.notes || '';
                    this.lastSaved = 'from draft';
                } catch (e) {
                    console.error('Failed to load draft:', e);
                }
            }

            // Pre-select patient if coming from URL
            <?php if (isset($_GET['patient_id']) && isset($pre_patient)): ?>
            this.selectedPatient = {
                id: <?= $pre_patient['id'] ?>,
                full_name: '<?= addslashes($pre_patient['full_name']) ?>',
                patient_code: '<?= $pre_patient['patient_code'] ?>'
            };
            <?php endif; ?>

            // Auto-save every 30 seconds
            setInterval(() => this.saveDraft(), 30000);
        },

        get summary() {
                const counts = { sensitive: 0, resistant: 0, indeterminate: 0, not_done: 0, total: 0 };
                for (let result of Object.values(this.drugResults)) {
                    if (result) {
                        counts[result]++;
                        if (result !== 'not_done') counts.total++;
                    }
                }
                return counts;
            },

        get resistancePattern() {
            const resistant = [];
            for (let [drugId, result] of Object.entries(this.drugResults)) {
                if (result === 'resistant') {
                    const el = document.querySelector(`select[name='drug_result[${drugId}]']`);
                    if (el) {
                        const drugName = el.closest('div').querySelector('.text-sm').textContent.trim();
                        resistant.push(drugName);
                    }
                }
            }

            // Check for TB pattern (Rifampicin + Isoniazid)
            const hasRifampicin = resistant.some(d => d.toLowerCase().includes('rifampicin') || d.toLowerCase().includes('rifampin'));
            const hasIsoniazid = resistant.some(d => d.toLowerCase().includes('isoniazid'));
            
            if (hasRifampicin && hasIsoniazid && resistant.length >= 3) {
                return 'XDR-TB Pattern Detected — TB + additional resistance';
            } else if (hasRifampicin && hasIsoniazid) {
                return 'GxAlert Pattern Detected — Resistant to Rifampicin and Isoniazid';
            } else if (resistant.length >= 3) {
                return 'Multiple Drug Resistance — ' + resistant.length + ' drugs resistant';
            }
            return null;
        },

        searchPatients(query) {
            if (query.length < 2) {
                this.searchResults = [];
                return;
            }
            fetch(`?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => this.searchResults = data)
                .catch(e => console.error('Search error:', e));
        },

        selectPatient(patient) {
            this.selectedPatient = patient;
            this.searchResults = [];
            this.$refs.patientSearch.value = '';
            this.saveDraft();
        },

        setDrugResult(drugId, result) {
            this.drugResults[drugId] = result;
            // Sync to the hidden <select> so POST data is correct
            const sel = document.querySelector(`select[name='drug_result[${drugId}]']`);
            if (sel) sel.value = result;
            this.saveDraft();
        },

        updateDrugResult(drugId) {
            this.saveDraft();
        },

        selectAll(status) {
            document.querySelectorAll('select[name^="drug_result["]').forEach(el => {
                const drugId = el.name.match(/\d+/)[0];
                this.drugResults[drugId] = status;
            });
            this.saveDraft();
        },

        applyTBPattern() {
            // Set Rifampicin and Isoniazid to resistant, others to sensitive
            const TBDrugs = ['rifampicin', 'rifampin', 'isoniazid'];
            document.querySelectorAll('select[name^="drug_result["]').forEach(el => {
                const drugName = el.closest('div').querySelector('.text-sm').textContent.toLowerCase();
                const drugId = el.name.match(/\d+/)[0];
                if (TBDrugs.some(d => drugName.includes(d))) {
                    this.drugResults[drugId] = 'resistant';
                } else {
                    this.drugResults[drugId] = 'sensitive';
                }
            });
            this.saveDraft();
        },

        applyXDRPattern() {
            // TB + fluoroquinolone + injectable
            const xdrDrugs = ['rifampicin', 'rifampin', 'isoniazid', 'levofloxacin', 'moxifloxacin', 'amikacin', 'kanamycin'];
            document.querySelectorAll('select[name^="drug_result["]').forEach(el => {
                const drugName = el.closest('div').querySelector('.text-sm').textContent.toLowerCase();
                const drugId = el.name.match(/\d+/)[0];
                if (xdrDrugs.some(d => drugName.includes(d))) {
                    this.drugResults[drugId] = 'resistant';
                } else {
                    this.drugResults[drugId] = 'sensitive';
                }
            });
            this.saveDraft();
        },

        clearAllResults() {
            if (confirm('Clear all drug results?')) {
                this.drugResults = {};
                this.saveDraft();
            }
        },

        generateInterpretation() {
            const resistant = [];
            const sensitive = [];
            
            for (let [drugId, result] of Object.entries(this.drugResults)) {
                const el = document.querySelector(`select[name='drug_result[${drugId}]']`);
                if (!el) continue;
                const drugName = el.closest('div').querySelector('.text-sm').textContent.trim();
                
                if (result === 'resistant') resistant.push(drugName);
                if (result === 'sensitive') sensitive.push(drugName);
            }

            let interpretation = '';
            
            const hasRifampicin = resistant.some(d => d.toLowerCase().includes('rifampicin') || d.toLowerCase().includes('rifampin'));
            const hasIsoniazid = resistant.some(d => d.toLowerCase().includes('isoniazid'));
            
            if (hasRifampicin && hasIsoniazid) {
                interpretation = 'GxAlert confirmed. Patient shows resistance to both Rifampicin and Isoniazid. ';
                if (resistant.length > 2) {
                    interpretation += `Additional resistance detected to: ${resistant.filter(d => !d.toLowerCase().includes('rifampicin') && !d.toLowerCase().includes('isoniazid') && !d.toLowerCase().includes('rifampin')).join(', ')}. `;
                }
                interpretation += 'Recommend second-line treatment regimen with susceptible drugs from Group A and B.';
            } else if (resistant.length > 0) {
                interpretation = `Resistance detected to ${resistant.length} drug${resistant.length > 1 ? 's' : ''}: ${resistant.join(', ')}. `;
                interpretation += `Patient remains sensitive to ${sensitive.length} tested drug${sensitive.length > 1 ? 's' : ''}. `;
                interpretation += 'Tailor treatment regimen accordingly.';
            } else if (sensitive.length > 0) {
                interpretation = `Patient shows full susceptibility to all ${sensitive.length} tested drugs. First-line treatment recommended.`;
            }

            this.notes = interpretation;
            this.saveDraft();
        },

        saveDraft() {
            const draft = {
                drugResults: this.drugResults,
                testMethod: this.testMethod,
                specimenDate: this.specimenDate,
                resultDate: this.resultDate,
                labFacility: this.labFacility,
                notes: this.notes,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('dst_draft', JSON.stringify(draft));
            this.lastSaved = 'just now';
        },

        clearDraft() {
            localStorage.removeItem('dst_draft');
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>

<?php require_once 'lab_footer.php'; ?>