<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'Drug Catalog - GxAlert';
 $notify_text = $_GET['status'] ?? '';

 $group_labels = [
    'group_a'  => 'Group A — Core',
    'group_b'  => 'Group B — Choice',
    'group_c'  => 'Group C — Add-on',
    'group_d1' => 'Group D1 — Repurposed',
    'group_d2' => 'Group D2 — Injectable',
    'other'    => 'Other',
];

 $group_colors = [
    'group_a'  => 'border-l-primary',
    'group_b'  => 'border-l-info',
    'group_c'  => 'border-l-secondary',
    'group_d1' => 'border-l-warning',
    'group_d2' => 'border-l-error',
    'other'    => 'border-l-slate-400',
];

// Fetch all drugs
 $drugs = $conn->query("
    SELECT d.*,
           (SELECT COUNT(*) FROM regimen_drugs rd WHERE rd.drug_id = d.id AND rd.is_active = 1) AS in_regimen_count
    FROM drugs d 
    WHERE d.is_active = 1
    ORDER BY FIELD(d.drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other'), d.drug_name
")->fetch_all(MYSQLI_ASSOC);

// Group them
 $grouped = [];
foreach ($drugs as $d) {
    $g = $d['drug_group'] ?? 'other';
    if (!isset($grouped[$g])) $grouped[$g] = [];
    $grouped[$g][] = $d;
}

// Handle add drug
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_drug'])) {
    $dname   = trim($_POST['drug_name'] ?? '');
    $dcode   = trim($_POST['drug_code'] ?? '');
    $dgroup  = $_POST['drug_group'] ?? 'other';
    $ddose   = (float)($_POST['default_dose_mg'] ?? 0);
    $dunit   = trim($_POST['unit'] ?? 'mg');
    $dnotes  = trim($_POST['notes'] ?? '');

    if (empty($dname) || empty($dcode)) {
        $form_err = 'Drug name and code are required';
    } else {
        $ins = $conn->prepare("INSERT INTO drugs (drug_name, drug_code, drug_group, default_dose_mg, unit, notes, is_active) VALUES (?,?,?,?,?,?,1)");
        $ins->bind_param("sssdss", $dname, $dcode, $dgroup, $ddose, $dunit, $dnotes);
        $ins->execute();
        header("Location: drugs.php?status=added");
        exit;
    }
}

// Handle delete (soft)
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Check if in active regimen
    $ck = $conn->prepare("SELECT COUNT(*) FROM regimen_drugs WHERE drug_id = ? AND is_active = 1");
    $ck->bind_param("i", $did);
    $ck->execute();
    if ($ck->get_result()->fetch_column() > 0) {
        header("Location: drugs.php?status=in_use");
    } else {
        $del = $conn->prepare("UPDATE drugs SET is_active = 0 WHERE id = ?");
        $del->bind_param("i", $did);
        $del->execute();
        header("Location: drugs.php?status=removed");
    }
    exit;
}
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Drug Catalog (<?= count($drugs) ?> drugs)</h1>
    <button onclick="document.getElementById('addDrugForm').classList.toggle('hidden')" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="mr-1 size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Add Drug
    </button>
  </div>

  <!-- Add Drug Form -->
  <form id="addDrugForm" method="POST" class="hidden card mt-4 p-5">
    <input type="hidden" name="add_drug" value="1">
    <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">Add New Drug</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Drug Name *</label>
        <input type="text" name="drug_name" required placeholder="e.g. Bedaquiline" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Drug Code *</label>
        <input type="text" name="drug_code" required placeholder="e.g. BDQ" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">WHO Group *</label>
        <select name="drug_group" class="form-select w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
          <?php foreach ($group_labels as $k => $v): ?>
          <option value="<?= $k ?>"><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Default Dose</label>
        <input type="number" name="default_dose_mg" step="0.01" placeholder="400" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Unit</label>
        <input type="text" name="unit" value="mg" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Notes</label>
        <input type="text" name="notes" placeholder="Any additional info" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
    </div>
    <div class="flex justify-end gap-3 mt-4">
      <button type="button" onclick="document.getElementById('addDrugForm').classList.add('hidden')" class="btn h-9 border border-slate-300 text-slate-600 dark:border-navy-500 dark:text-navy-200 px-5">Cancel</button>
      <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Add Drug</button>
    </div>
  </form>

  <?php if (isset($form_err)): ?>
  <div class="alert mt-4 flex overflow-hidden rounded-lg bg-error/10 text-error dark:bg-error/15">
    <div class="flex flex-1 items-center p-4 text-sm"><?= htmlspecialchars($form_err) ?></div>
    <div class="w-1.5 bg-error"></div>
  </div>
  <?php endif; ?>

  <!-- Drugs by Group -->
  <?php foreach ($grouped as $group => $group_drugs): ?>
  <div class="mt-6">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-navy-300"><?= $group_labels[$group] ?? $group ?> (<?= count($group_drugs) ?>)</h2>
    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
      <?php foreach ($group_drugs as $d): ?>
      <div class="card border-l-4 <?= $group_colors[$group] ?? 'border-l-slate-400' ?> p-4">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($d['drug_name']) ?></h3>
            <span class="text-xs font-mono text-slate-400"><?= htmlspecialchars($d['drug_code']) ?></span>
          </div>
          <?php if ($d['in_regimen_count'] == 0): ?>
          <a href="drugs.php?delete=<?= $d['id'] ?>" onclick="return confirm('Remove this drug?')" class="text-slate-400 hover:text-error" title="Remove">
            <svg class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </a>
          <?php else: ?>
          <span class="text-xs text-info" title="In <?= $d['in_regimen_count'] ?> active regimen(s)"><?= $d['in_regimen_count'] ?> Rx</span>
          <?php endif; ?>
        </div>
        <div class="mt-2 flex gap-3 text-xs text-slate-400">
          <?php if ($d['default_dose_mg'] > 0): ?><span><?= $d['default_dose_mg'] ?> <?= $d['unit'] ?></span><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($drugs)): ?>
  <div class="card mt-6 p-12 text-center text-slate-400">
    <p class="text-lg">No drugs in catalog</p>
    <p class="mt-1 text-sm">Add GxAlert drugs to enable regimen assignment.</p>
  </div>
  <?php endif; ?>
</main>

<?php require_once 'admin_footer.php'; ?>