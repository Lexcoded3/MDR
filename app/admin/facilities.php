<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'Facilities - GxAlert';
 $notify_text = $_GET['status'] ?? '';

 $search = trim($_GET['q'] ?? '');
 $where = ["1=1"];
 $params = []; $types = "";

if ($search !== '') {
    $where[] = "(f.name LIKE ? OR f.address LIKE ? OR f.contact_person LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    $types .= "sss";
}
 $where_sql = implode(' AND ', $where);

 $fac_list = $conn->prepare("
    SELECT f.*, 
           (SELECT COUNT(*) FROM patients WHERE facility_id = f.id AND is_active = 1) AS patient_count,
           (SELECT COUNT(*) FROM users WHERE facility_id = f.id AND is_active = 1) AS staff_count
    FROM facilities f WHERE $where_sql ORDER BY f.name
");
if ($params) $fac_list->bind_param($types, ...$params);
 $fac_list->execute();
 $facilities = $fac_list->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle add/edit inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_facility'])) {
    $fname = trim($_POST['name'] ?? '');
    $faddr = trim($_POST['address'] ?? '');
    $fcontact = trim($_POST['contact_person'] ?? '');
    $fphone = trim($_POST['phone'] ?? '');
    $femail = trim($_POST['email'] ?? '');
    $fid = (int)($_POST['facility_id'] ?? 0);
    
    if (empty($fname)) { $form_err = 'Facility name is required'; }
    else {
        if ($fid > 0) {
            $upd = $conn->prepare("UPDATE facilities SET name=?, address=?, contact_person=?, phone=?, email=?, updated_at=NOW() WHERE id=?");
            $upd->bind_param("sssssi", $fname, $faddr, $fcontact, $fphone, $femail, $fid);
            $upd->execute();
            $notify_text = 'updated';
        } else {
    // Corrected SQL: 5 placeholders (?) for the 5 strings provided in bind_param
    $ins = $conn->prepare("INSERT INTO facilities (name, address, contact_person, phone, email, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $ins->bind_param("sssss", $fname, $faddr, $fcontact, $fphone, $femail);
    $ins->execute(); 
    $notify_text = 'created';
}
        header("Location: facilities.php?status=$notify_text");
        exit;
    }
}

// Handle toggle active
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    $conn->query("UPDATE facilities SET is_active = NOT is_active WHERE id = $tid");
    header("Location: facilities.php?status=Toggled Successfully");
    exit;
}
?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Facilities (<?= count($facilities) ?>)</h1>
    <button onclick="document.getElementById('addFacForm').classList.toggle('hidden')" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="mr-1 size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Add Facility
    </button>
  </div>

  <!-- Add Form (hidden by default) -->
  <form id="addFacForm" method="POST" class="hidden card mt-4 p-5">
    <input type="hidden" name="save_facility" value="1">
    <input type="hidden" name="facility_id" value="0">
    <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">New Facility</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Name *</label>
        <input type="text" name="name" required class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Contact Person</label>
        <input type="text" name="contact_person" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Address</label>
        <input type="text" name="address" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Phone</label>
        <input type="text" name="phone" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-navy-100">Email</label>
        <input type="email" name="email" class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      </div>
    </div>
    <div class="flex justify-end gap-3 mt-4">
      <button type="button" onclick="document.getElementById('addFacForm').classList.add('hidden')" class="btn h-9 border border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-navy-500 dark:text-navy-200 dark:hover:bg-navy-600 px-5">Cancel</button>
      <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Save</button>
    </div>
  </form>

  <!-- Search -->
  <form method="GET" class="mt-4">
    <div class="relative max-w-md">
      <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search facilities..."
             class="form-input w-full rounded-lg bg-slate-150 py-2 pl-9 pr-3 text-sm dark:bg-navy-900/90">
    </div>
  </form>

  <!-- Facility Cards -->
  <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($facilities as $f): ?>
    <div class="card p-5 <?= !$f['is_active'] ? 'opacity-60' : '' ?>">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($f['name']) ?></h3>
          <?php if (!$f['is_active']): ?>
          <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-error/10 text-error">Inactive</span>
          <?php endif; ?>
        </div>
        <a href="facilities.php?toggle=1&id=<?= $f['id'] ?>" class="btn size-7 rounded-full p-0 hover:bg-slate-200 dark:hover:bg-navy-600" title="Toggle active">
            <?php if ($f['is_active']): ?>
              <svg class="size-4 text-success" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php else: ?>
            <svg class="size-4 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
            <?php endif; ?>          
        </a>
      </div>
      <?php if ($f['address']): ?><p class="mt-1 text-sm text-slate-500 dark:text-navy-300"><?= htmlspecialchars($f['address']) ?></p><?php endif; ?>
      <div class="mt-3 flex gap-4 text-xs text-slate-400">
        <?php if ($f['contact_person']): ?><span>Contact: <?= htmlspecialchars($f['contact_person']) ?></span><?php endif; ?>
        <?php if ($f['phone']): ?><span><?= htmlspecialchars($f['phone']) ?></span><?php endif; ?>
      </div>
      <div class="mt-3 flex gap-4">
        <div class="flex items-center gap-1.5">
          <div class="size-2 rounded-full bg-primary dark:bg-accent"></div>
          <span class="text-xs text-slate-600 dark:text-navy-200"><?= $f['patient_count'] ?> patients</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="size-2 rounded-full bg-info"></div>
          <span class="text-xs text-slate-600 dark:text-navy-200"><?= $f['staff_count'] ?> staff</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($facilities)): ?>
    <div class="col-span-full card p-8 text-center text-slate-400">No facilities found</div>
    <?php endif; ?>
  </div>
</main>

<?php require_once 'admin_footer.php'; ?>