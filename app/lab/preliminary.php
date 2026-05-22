<?php
session_start();
$required_role = 'lab_personnel';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'lab_init.php';

$pageTitle = 'Preliminary Results - GxAlert';

// Filters
$search     = trim($_GET['search'] ?? '');
$test_filter = trim($_GET['test_type'] ?? '');
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

// Build query dynamically
$where  = ["lr.is_final = 0", "lr.uploaded_by = ?"];
$params = [$lab_id];
$types  = "i";

if ($search !== '') {
    $where[]  = "(p.full_name LIKE ? OR p.patient_code LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($test_filter !== '') {
    $where[]  = "lr.test_type LIKE ?";
    $params[] = "%$test_filter%";
    $types   .= "s";
}
if ($date_from !== '') {
    $where[]  = "lr.result_date >= ?";
    $params[] = $date_from;
    $types   .= "s";
}
if ($date_to !== '') {
    $where[]  = "lr.result_date <= ?";
    $params[] = $date_to;
    $types   .= "s";
}

$where_sql = implode(" AND ", $where);

$stmt = $conn->prepare("
    SELECT lr.id, lr.test_type, lr.specimen_type, lr.result, lr.result_date,
           lr.specimen_date, lr.lab_facility, lr.created_at,
           p.id AS patient_id, p.full_name, p.patient_code
    FROM lab_results lr
    LEFT JOIN patients p ON lr.patient_id = p.id
    WHERE $where_sql
    ORDER BY lr.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count total preliminary for this lab user (unfiltered — for the badge)
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM lab_results WHERE is_final = 0 AND uploaded_by = ?");
$count_stmt->bind_param("i", $lab_id);
$count_stmt->execute();
$total_preliminary = $count_stmt->get_result()->fetch_column();
$count_stmt->close();

// Distinct test types for filter dropdown
$types_stmt = $conn->prepare("SELECT DISTINCT test_type FROM lab_results WHERE uploaded_by = ? AND is_final = 0 ORDER BY test_type ASC");
$types_stmt->bind_param("i", $lab_id);
$types_stmt->execute();
$test_types = $types_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$types_stmt->close();
?>
<?php require_once 'lab_header.php'; ?>

<div class="mt-4 sm:mt-5 lg:mt-6">

  <!-- Breadcrumb -->
  <nav class="mb-4 text-xs text-slate-400 dark:text-navy-300">
    <a href="index.php" class="hover:text-primary dark:hover:text-accent-light">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-slate-700 dark:text-navy-100">Preliminary Results</span>
  </nav>

  <!-- Page header -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
      <h1 class="text-lg font-semibold text-slate-700 dark:text-navy-100">Preliminary Results</h1>
      <span class="rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning">
        <?= $total_preliminary ?> pending
      </span>
    </div>
    <a href="upload_result.php"
       class="btn h-9 bg-primary px-5 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
      <svg class="inline size-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      Upload New Result
    </a>
  </div>

  <!-- Info banner -->
  <div class="card border-l-4 border-l-warning bg-warning/5 p-4 mb-5 flex items-start gap-3 mt-3">
    <svg class="size-5 text-warning shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div>
      <p class="text-sm font-medium text-warning">Pending Finalization</p>
      <p class="text-xs text-slate-500 dark:text-navy-300 mt-0.5">
        These results are marked as <strong>preliminary</strong>. Click <em>Finalize</em> on any row to upload the confirmed final result for that patient.
      </p>
    </div>
  </div>

  <!-- Filters -->
  <div class="card p-4 mb-5 mt-5">
    <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
      <!-- Search -->
      <div class="flex-1 min-w-[180px]">
        <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Patient name / code</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0z"/>
            </svg>
          </span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                 placeholder="Search..."
                 class="form-input w-full rounded-lg bg-slate-150 pl-9 pr-3 py-2 text-sm dark:bg-navy-900/90">
        </div>
      </div>

      <!-- Test type -->
      <div class="min-w-[160px]">
        <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">Test type</label>
        <select name="test_type" class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm dark:bg-navy-900/90">
          <option value="">All tests</option>
          <?php foreach ($test_types as $tt): ?>
          <option value="<?= htmlspecialchars($tt['test_type']) ?>"
            <?= $test_filter === $tt['test_type'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($tt['test_type']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date from -->
      <div class="min-w-[140px]">
        <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
               class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm dark:bg-navy-900/90">
      </div>

      <!-- Date to -->
      <div class="min-w-[140px]">
        <label class="block text-xs text-slate-400 dark:text-navy-300 mb-1">To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
               class="form-input w-full rounded-lg bg-slate-150 px-3 py-2 text-sm dark:bg-navy-900/90">
      </div>

      <!-- Buttons -->
      <div class="flex gap-2">
        <button type="submit"
                class="btn h-9 bg-primary px-4 text-sm font-medium text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
          Filter
        </button>
        <?php if ($search || $test_filter || $date_from || $date_to): ?>
        <a href="preliminary.php"
           class="btn h-9 bg-slate-100 px-4 text-sm font-medium text-slate-600 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-200 dark:hover:bg-navy-600">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Results count -->
  <?php if ($search || $test_filter || $date_from || $date_to): ?>
  <p class="text-xs text-slate-400 dark:text-navy-300 mb-3">
    Showing <strong><?= count($results) ?></strong> result<?= count($results) !== 1 ? 's' : '' ?>
    <?php if ($search): ?>matching <em><?= htmlspecialchars($search) ?></em><?php endif; ?>
</p>
  <?php endif; ?>

  <!-- Table -->
  <div class="card mt-5">
    <?php if (empty($results)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
      <svg class="size-12 text-success mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-sm font-medium text-slate-600 dark:text-navy-200">
        <?= ($search || $test_filter || $date_from || $date_to) ? 'No results match your filters' : 'No preliminary results — all caught up!' ?>
      </p>
      <p class="text-xs text-slate-400 dark:text-navy-300 mt-1">
        <?= ($search || $test_filter || $date_from || $date_to) ? '' : 'All your uploaded results have been finalized.' ?>
      </p>
    </div>
    <?php else: ?>
    <div class="is-scrollbar-hidden overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Patient</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Test</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Specimen</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Result Date</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Preliminary Result</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Uploaded</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
          <tr class="border-y border-transparent border-b-slate-150 dark:border-b-navy-600"
    data-patient-id="<?= $r['patient_id'] ?>"
    data-edit-id="<?= $r['id'] ?>">

            <!-- Patient -->
            <td class="whitespace-nowrap px-4 py-3">
              <p class="text-sm font-medium text-slate-700 dark:text-navy-100">
                <?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?>
              </p>
              <p class="text-[10px] font-mono text-slate-400 dark:text-navy-300">
                <?= htmlspecialchars($r['patient_code'] ?? '') ?>
              </p>
            </td>

            <!-- Test type -->
            <td class="whitespace-nowrap px-4 py-3">
              <p class="text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($r['test_type']) ?></p>
              <?php if ($r['lab_facility']): ?>
              <p class="text-[10px] text-slate-400 dark:text-navy-300"><?= htmlspecialchars($r['lab_facility']) ?></p>
              <?php endif; ?>
            </td>

            <!-- Specimen -->
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-300">
              <?= ucfirst(str_replace('_', ' ', $r['specimen_type'] ?? '-')) ?>
              <?php if ($r['specimen_date']): ?>
              <p class="text-[10px] text-slate-400"><?= $r['specimen_date'] ?></p>
              <?php endif; ?>
            </td>

            <!-- Result date -->
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-navy-200">
              <?= $r['result_date'] ?? '-' ?>
            </td>

            <!-- Result preview -->
            <td class="px-4 py-3 max-w-[200px]">
              <p class="text-xs text-slate-600 dark:text-navy-200 truncate font-mono" title="<?= htmlspecialchars($r['result']) ?>">
                <?= htmlspecialchars(mb_strimwidth($r['result'], 0, 60, '…')) ?>
              </p>
              <span class="mt-1 inline-block rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-semibold text-warning">
                Preliminary
              </span>
            </td>

            <!-- Uploaded at -->
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 dark:text-navy-300">
              <?= date('M j, H:i', strtotime($r['created_at'])) ?>
            </td>

            <!-- Actions -->
            <td class="whitespace-nowrap px-4 py-3">
              <div class="flex items-center gap-2">
                <!-- Finalize: go to upload_result with patient pre-filled and edit_id -->
                <a href="upload_result.php?patient_id=<?= $r['patient_id'] ?>&edit_id=<?= $r['id'] ?>"
                   class="btn h-7 bg-success/10 px-3 text-xs font-medium text-success hover:bg-success/20"
                   title="Finalize this result">
                  <svg class="inline size-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Finalize
                </a>
                <!-- View patient -->
                <button type="button"
        onclick="viewPatient(<?= $r['patient_id'] ?>)"
        class="btn h-7 bg-slate-100 px-3 text-xs font-medium text-slate-500 hover:bg-slate-200 dark:bg-navy-700 dark:text-navy-300 dark:hover:bg-navy-600"
        title="View patient">
  <svg class="inline size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.572-3.007-9.964-7.178z"/>
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
  </svg>
</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Footer count -->
    <div class="border-t border-slate-150 dark:border-navy-600 px-4 py-3">
      <p class="text-xs text-slate-400 dark:text-navy-300">
        <?= count($results) ?> preliminary result<?= count($results) !== 1 ? 's' : '' ?> pending finalization
      </p>
    </div>
    <?php endif; ?>
  </div>

</div>
<!-- Patient View Modal -->
<div id="patientModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeModal()">
  <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
  <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-navy-700 shadow-xl">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-150 dark:border-navy-600 px-6 py-4">
      <h3 class="font-semibold text-slate-700 dark:text-navy-100">Patient Details</h3>
      <button onclick="closeModal()" class="text-slate-400 hover:text-error transition-colors">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Loading state -->
    <div id="modalLoading" class="flex items-center justify-center py-16">
      <div class="size-8 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></div>
    </div>

    <!-- Content -->
    <div id="modalContent" class="hidden px-6 py-5 space-y-4">

      <!-- Avatar + name -->
      <div class="flex items-center gap-4">
        <div class="avatar size-14 shrink-0">
          <div class="is-initial rounded-full bg-primary/10 text-lg uppercase text-primary dark:bg-accent/10 dark:text-accent-light"
               id="modalInitials"></div>
        </div>
        <div>
          <p class="text-base font-semibold text-slate-700 dark:text-navy-100" id="modalName"></p>
          <p class="text-xs font-mono text-slate-400 dark:text-navy-300" id="modalCode"></p>
          <span id="modalStatus" class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold"></span>
        </div>
      </div>

      <!-- Info grid -->
      <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Gender</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalGender">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Date of Birth</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalDob">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Phone</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalPhone">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">HIV Status</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalHiv">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">TB Classification</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalTb">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">MDR Confirmation</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalMdr">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Weight</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalWeight">—</p>
        </div>
        <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
          <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Enrolled</p>
          <p class="text-sm font-medium text-slate-700 dark:text-navy-100" id="modalEnrolled">—</p>
        </div>
      </div>

      <!-- Address -->
      <div class="rounded-lg bg-slate-50 dark:bg-navy-800 p-3">
        <p class="text-[10px] text-slate-400 dark:text-navy-300 uppercase tracking-wide mb-0.5">Address</p>
        <p class="text-sm text-slate-700 dark:text-navy-100" id="modalAddress">—</p>
      </div>

      <!-- Footer actions -->
      <div class="flex justify-end gap-2 pt-2">
        <button onclick="closeModal()"
                class="btn h-9 bg-slate-100 px-4 text-sm text-slate-600 hover:bg-slate-200 dark:bg-navy-600 dark:text-navy-200">
          Close
        </button>
        <a id="modalFinalizeBtn" href="#"
           class="btn h-9 bg-success/10 px-4 text-sm font-medium text-success hover:bg-success/20">
          Finalize Result
        </a>
      </div>
    </div>

    <!-- Error state -->
    <div id="modalError" class="hidden px-6 py-10 text-center">
      <p class="text-sm text-error">Failed to load patient. Please try again.</p>
      <button onclick="closeModal()" class="mt-3 btn h-8 bg-slate-100 px-4 text-xs text-slate-600">Close</button>
    </div>

  </div>
</div>

<script>
function viewPatient(patientId) {
    // Find the result id for this patient from the table
    const row   = document.querySelector(`[data-patient-id="${patientId}"]`);
    const editId = row ? row.dataset.editId : '';

    const modal = document.getElementById('patientModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('modalLoading').classList.remove('hidden');
    document.getElementById('modalContent').classList.add('hidden');
    document.getElementById('modalError').classList.add('hidden');
    document.body.style.overflow = 'hidden';

    fetch(`patient_ajax.php?id=${patientId}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('modalInitials').textContent = p.full_name.substring(0, 2);
            document.getElementById('modalName').textContent     = p.full_name;
            document.getElementById('modalCode').textContent     = p.patient_code;
            document.getElementById('modalGender').textContent   = p.gender ?? '—';
            document.getElementById('modalDob').textContent      = p.date_of_birth ?? '—';
            document.getElementById('modalPhone').textContent    = p.phone ?? '—';
            document.getElementById('modalHiv').textContent      = p.hiv_status ?? '—';
            document.getElementById('modalTb').textContent       = (p.tb_case_classification ?? '—').replace(/_/g,' ');
            document.getElementById('modalMdr').textContent      = p.mdr_confirmation ?? '—';
            document.getElementById('modalWeight').textContent   = p.weight_kg ? p.weight_kg + ' kg' : '—';
            document.getElementById('modalEnrolled').textContent = p.enrollment_date ?? '—';
            document.getElementById('modalAddress').textContent  = p.address ?? '—';

            // Status badge
            const statusEl  = document.getElementById('modalStatus');
            const statusMap = {
                enrolled:        ['bg-info/10 text-info',        'Enrolled'],
                on_treatment:    ['bg-primary/10 text-primary',  'On Treatment'],
                completed:       ['bg-success/10 text-success',  'Completed'],
                cured:           ['bg-success/10 text-success',  'Cured'],
                failed:          ['bg-error/10 text-error',      'Failed'],
                died:            ['bg-slate-200 text-slate-500', 'Died'],
                lost_to_followup:['bg-warning/10 text-warning',  'Lost to Follow-up'],
                transferred_out: ['bg-slate-200 text-slate-500', 'Transferred Out'],
            };
            const [cls, label] = statusMap[p.treatment_status] ?? ['bg-slate-100 text-slate-500', p.treatment_status ?? 'Unknown'];
            statusEl.className = `mt-1 inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold ${cls}`;
            statusEl.textContent = label;

            // Finalize button
            if (editId) {
                document.getElementById('modalFinalizeBtn').href =
                    `upload_result.php?patient_id=${patientId}&edit_id=${editId}`;
            } else {
                document.getElementById('modalFinalizeBtn').href =
                    `upload_result.php?patient_id=${patientId}`;
            }

            document.getElementById('modalLoading').classList.add('hidden');
            document.getElementById('modalContent').classList.remove('hidden');
        })
        .catch(() => {
            document.getElementById('modalLoading').classList.add('hidden');
            document.getElementById('modalError').classList.remove('hidden');
        });
}

function closeModal() {
    const modal = document.getElementById('patientModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
<?php

$notify_text = '';
require_once 'lab_footer.php';
?>