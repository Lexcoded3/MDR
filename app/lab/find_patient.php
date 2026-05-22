<?php
session_start();
 $required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

 $pageTitle = 'Find Patient - GxAlert';

 $search = trim($_GET['q'] ?? '');
 $results = [];

if ($search !== '' && strlen($search) >= 2) {
    $stmt = $conn->prepare("
        SELECT id, patient_code, full_name, gender, date_of_birth, phone, facility_id
        FROM patients 
        WHERE is_active = 1 AND (full_name LIKE ? OR patient_code LIKE ? OR phone LIKE ?)
        ORDER BY full_name LIMIT 20
    ");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<?php require_once 'lab_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6 max-w-3xl">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100 mb-5">Find Patient</h1>

  <form method="GET" class="mb-5">
    <div class="flex gap-2 mt-5">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, code, or phone..." autofocus
               class="form-input peer h-9 w-full rounded-l-lg bg-white px-3 py-2 shadow-soft ring-primary/50 placeholder:text-slate-400 focus:ring dark:bg-navy-700 dark:shadow-none dark:ring-accent/50 dark:placeholder:text-navy-300 lg:pl-90"
               minlength="2" required>
      </div>
      <button type="submit" class="btn bg-primary px-5 text-sm text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">Search</button>
    </div>

  </form>

  <?php if ($search !== '' && strlen($search) < 2): ?>
  <div class="card border-l-4 border-l-warning bg-warning/5 p-4 mb-4">
    <p class="text-sm text-slate-600 dark:text-navy-200">Please enter at least 2 characters to search.</p>
  </div>
  <?php endif; ?>

  <?php if ($search !== '' && strlen($search) >= 2 && empty($results)): ?>
  <div class="card p-16 text-center mt-5">
    <svg class="mx-auto size-12 text-slate-300 dark:text-navy-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
    <p class="mt-3 text-sm text-slate-400 dark:text-navy-300">No patients found matching "<?= htmlspecialchars($search) ?>"</p>
  </div>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
  <div class="card mt-5">
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Code</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Name</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Age/Sex</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Phone</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r):
              $age = $r['date_of_birth'] ? (new DateTime('today'))->diff(new DateTime($r['date_of_birth']))->y : '-';
          ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-3"><code class="text-xs"><?= htmlspecialchars($r['patient_code']) ?></code></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= $age ?>/<?= substr($r['gender'], 0, 1) ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-200"><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex justify-center space-x-2">
                <a href="upload_result.php?patient_id=<?= $r['id'] ?>">
                            <button  class="btn size-8 p-0 text-info hover:bg-info/20 focus:bg-info/20 active:bg-info/25" x-tooltip.info="'Upload result'">
                                <i class="fa fa-plus"></i>
                            </button>
                          </a>
                          <a href="drug_susceptibility.php?patient_id=<?= $r['id'] ?>">
                            <button  class="btn size-8 p-0 text-warning hover:bg-warning/20 focus:bg-warning/20 active:bg-warning/25" x-tooltip.warning="'Drug susceptibility'">
                                <i class="fa-solid fa-flask"></i>
                            </button>
                          </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'lab_footer.php'; ?>