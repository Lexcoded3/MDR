<?php
session_start();
$required_role = 'doctor';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'doctor_init.php';
require_once '../config/notify_helper.php';

$pageTitle = 'Regimen Reviews - GxAlert';
$notify_text = '';
$form_errors = [];

// Get doctor's facility_id directly — cleaner than matching location string to facility name
$doc_stmt = $conn->prepare("SELECT facility_id FROM users WHERE id = ?");
$doc_stmt->bind_param("i", $doctor_id);
$doc_stmt->execute();
$doctor_facility_id = $doc_stmt->get_result()->fetch_column();
$doc_stmt->close();

if (!$doctor_facility_id) {
    die("Your account has no facility assigned. Please contact admin.");
}

// Handle approve/reject from GET quick actions
if (isset($_GET['action'], $_GET['id'])) {
    $rid    = (int)$_GET['id'];
    $action = $_GET['action'];

    if (in_array($action, ['approve', 'reject'])) {
        // Verify regimen belongs to doctor's facility and is pending
        $check = $conn->prepare("
            SELECT tr.id, tr.prescribed_by, p.full_name
            FROM treatment_regimens tr
            JOIN patients p ON tr.patient_id = p.id
            WHERE tr.id = ? 
            AND tr.status = 'pending_review'
            AND p.facility_id = ?
        ");
        $check->bind_param("ii", $rid, $doctor_facility_id);
        $check->execute();
        $check_row = $check->get_result()->fetch_assoc();
        $check->close();

        if ($check_row) {
            $new_status = $action === 'approve' ? 'active' : 'rejected';
            $clin_id    = $check_row['prescribed_by'];
            $p_name     = $check_row['full_name'];

            // Update regimen status
            $upd = $conn->prepare("
                UPDATE treatment_regimens 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
                WHERE id = ?
            ");
            $upd->bind_param("sii", $new_status, $doctor_id, $rid);
            $upd->execute();
            $upd->close();

            if ($action === 'approve') {
                // Activate medication schedule
                $act_sch = $conn->prepare("UPDATE medication_schedule SET is_active = 1 WHERE regimen_id = ?");
                $act_sch->bind_param("i", $rid);
                $act_sch->execute();
                $act_sch->close();

                // Update patient treatment status
                $upd_p = $conn->prepare("
                    UPDATE patients SET treatment_status = 'on_treatment'
                    WHERE id = (SELECT patient_id FROM treatment_regimens WHERE id = ?)
                ");
                $upd_p->bind_param("i", $rid);
                $upd_p->execute();
                $upd_p->close();

                notify_regimen_approved($conn, $clin_id, $p_name);
            } else {
                notify_regimen_rejected($conn, $clin_id, $p_name, 'Rejected by doctor — no notes provided');
            }

            // Audit — using correct columns from audit_log table
            $audit_vals = json_encode(['status' => $new_status]);
            $aud = $conn->prepare("
                INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address)
                VALUES (?, ?, 'treatment_regimens', ?, ?, ?)
            ");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $aud->bind_param("isiss", $doctor_id, $action, $rid, $audit_vals, $ip);
            $aud->execute();
            $aud->close();

            $notify_text = $action === 'approve' ? 'Regimen approved' : 'Regimen rejected';
            header("Location: regimen_reviews.php?status=" . urlencode($notify_text));
            exit;
        }
    }
}

// Handle reject with notes (POST form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_with_notes'])) {
    $rid   = (int)($_POST['regimen_id'] ?? 0);
    $notes = trim($_POST['rejection_notes'] ?? '');

    if (empty($notes)) {
        $form_errors[] = 'Provide a rejection reason';
    } else {
        // Get clinician and patient info before updating
        $info = $conn->prepare("
            SELECT tr.prescribed_by, p.full_name 
            FROM treatment_regimens tr
            JOIN patients p ON tr.patient_id = p.id
            WHERE tr.id = ? AND tr.status = 'pending_review' AND p.facility_id = ?
        ");
        $info->bind_param("ii", $rid, $doctor_facility_id);
        $info->execute();
        $info_row = $info->get_result()->fetch_assoc();
        $info->close();

        if ($info_row) {
            $upd = $conn->prepare("
                UPDATE treatment_regimens 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                WHERE id = ? AND status = 'pending_review'
            ");
            $upd->bind_param("isi", $doctor_id, $notes, $rid);
            $upd->execute();
            $upd->close();

            notify_regimen_rejected($conn, $info_row['prescribed_by'], $info_row['full_name'], $notes);

            // Audit — correct table name (no 's')
            $audit_vals = json_encode(['review_notes' => $notes, 'status' => 'rejected']);
            $aud = $conn->prepare("
                INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address)
                VALUES (?, 'reject', 'treatment_regimens', ?, ?, ?)
            ");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $aud->bind_param("iiss", $doctor_id, $rid, $audit_vals, $ip);
            $aud->execute();
            $aud->close();

            $notify_text = 'Regimen rejected with notes';
            header("Location: regimen_reviews.php?status=" . urlencode($notify_text));
            exit;
        } else {
            $form_errors[] = 'Regimen not found or already reviewed';
        }
    }
}

// Fetch pending regimens for this doctor's facility
$reg_stmt = $conn->prepare("
    SELECT tr.*, 
           p.full_name, p.patient_code, p.gender, p.date_of_birth, 
           p.hiv_status, p.weight_kg, p.enrollment_date, p.mdr_confirmation,
           u.name AS assigned_by_name,
           f.name AS facility_name,
           (SELECT COUNT(*) FROM regimen_drugs rd WHERE rd.regimen_id = tr.id AND rd.is_active = 1) AS drug_count
    FROM treatment_regimens tr
    JOIN patients p ON tr.patient_id = p.id
    LEFT JOIN users u ON tr.prescribed_by = u.id
    LEFT JOIN facilities f ON p.facility_id = f.id
    WHERE tr.status = 'pending_review'
    AND p.facility_id = ?
    ORDER BY tr.created_at DESC
");
$reg_stmt->bind_param("i", $doctor_facility_id);
$reg_stmt->execute();
$pending = $reg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reg_stmt->close();

// Fetch recently reviewed (last 30 days)
$done_stmt = $conn->prepare("
    SELECT tr.*, 
           p.full_name, p.patient_code,
           u.name AS assigned_by_name,
           rv.name AS reviewer_name
    FROM treatment_regimens tr
    JOIN patients p ON tr.patient_id = p.id
    LEFT JOIN users u ON tr.prescribed_by = u.id
    LEFT JOIN users rv ON tr.reviewed_by = rv.id
    WHERE tr.status IN ('active', 'rejected')
    AND p.facility_id = ?
    AND tr.reviewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY tr.reviewed_at DESC
    LIMIT 10
");
$done_stmt->bind_param("i", $doctor_facility_id);
$done_stmt->execute();
$reviewed = $done_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$done_stmt->close();
?>
<?php require_once 'doctor_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4">
    <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">Regimen Reviews</h1>
    <p class="mt-1 text-sm text-slate-500">Review and approve treatment regimens proposed by clinicians.</p>
  </div>

  <?php if (!empty($form_errors)): ?>
  <div class="alert mt-4 flex overflow-hidden rounded-lg bg-error/10 text-error dark:bg-error/15">
    <div class="flex flex-1 items-center p-4 text-sm"><?= implode('<br>', $form_errors) ?></div>
    <div class="w-1.5 bg-error"></div>
  </div>
  <?php endif; ?>

  <!-- Pending -->
  <h2 class="mt-6 mb-3 text-sm font-semibold uppercase tracking-wide text-warning">Pending Review (<?= count($pending) ?>)</h2>
  
  <?php if (empty($pending)): ?>
  <div class="card mb-8 mt-5 p-8 text-center text-slate-400">No pending regimen reviews.</div>
  <?php else: ?>
  <div class="space-y-4">
    <?php foreach ($pending as $r): 
      // Get drugs for this regimen
      $drugs_stmt = $conn->prepare("
          SELECT rd.*, d.drug_name, d.drug_code, d.drug_group, d.default_dose_mg, d.unit
          FROM regimen_drugs rd JOIN drugs d ON rd.drug_id = d.id
          WHERE rd.regimen_id = ? AND rd.is_active = 1
          ORDER BY FIELD(d.drug_group, 'group_a','group_b','group_c','group_d1','group_d2','other')
      ");
      $drugs_stmt->bind_param("i", $r['id']);
      $drugs_stmt->execute();
      $reg_drugs = $drugs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

      // Get DST results for this patient
      $dst_stmt = $conn->prepare("
          SELECT d.drug_name, d.drug_code, ds.result, ds.test_method, ds.result_date
          FROM drug_susceptibility ds
          JOIN drugs d ON ds.drug_id = d.id
          WHERE ds.patient_id = ? AND ds.result = 'resistant'
          ORDER BY ds.result_date DESC LIMIT 10
      ");
      $dst_stmt->bind_param("i", $r['patient_id']);
      $dst_stmt->execute();
      $resistance = $dst_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      
      $age = $r['date_of_birth'] ? (new DateTime('today'))->diff(new DateTime($r['date_of_birth']))->y : null;
    ?>
    <div class="card" id="regimen-<?= $r['id'] ?>">
      <div class="p-5">
        <!-- Patient Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="avatar size-12">
              <div class="is-initial rounded-full bg-primary/10 text-sm+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
                <?= strtoupper(substr($r['full_name'], 0, 2)) ?>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-700 dark:text-navy-100"><?= htmlspecialchars($r['full_name']) ?></h3>
              <p class="text-sm text-slate-400"><?= $r['patient_code'] ?> · <?= ucfirst($r['gender'] ?? '') ?> <?= $age ? "· $age yrs" : '' ?> · <?= $r['weight_kg'] ?>kg</p>
              <div class="mt-1 flex flex-wrap gap-2 text-xs">
                <?php if ($r['hiv_status'] === 'positive'): ?>
                <span class="rounded-full bg-error/10 px-2 py-0.5 text-error">HIV+</span>
                <?php endif; ?>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500"><?= ucfirst($r['TB_confirmation'] ?? 'N/A') ?></span>
                <span class="text-slate-400">Proposed by <?= htmlspecialchars($r['assigned_by_name'] ?? '') ?> on <?= date('M j, H:i', strtotime($r['created_at'])) ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-5">
          <!-- Proposed Regimen -->
          <div>
            <h4 class="text-sm font-semibold text-slate-700 dark:text-navy-100 mb-2">Proposed Regimen: <?= htmlspecialchars($r['regimen_name']) ?></h4>
            <div class="rounded-lg border border-slate-200 dark:border-navy-600 overflow-hidden">
              <table class="w-full text-left text-sm">
                <thead>
                  <tr class="bg-slate-50 dark:bg-navy-800">
                    <th class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-navy-200">DRUG</th>
                    <th class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-navy-200">DOSE</th>
                    <th class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-navy-200">DURATION</th>
                    <th class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-navy-200">FREQUENCY</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reg_drugs as $d): ?>
                  <tr class="border-t border-slate-100 dark:border-navy-600">
                    <td class="px-3 py-2">
                      <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($d['drug_name']) ?></span>
                      <span class="text-xs text-slate-400 ml-1">(<?= $d['drug_code'] ?>)</span>
                    </td>
                    <td class="px-3 py-2 text-slate-600 dark:text-navy-200"><?= $d['dose_mg'] ?> <?= $d['unit'] ?></td>
                    <td class="px-3 py-2 text-slate-600 dark:text-navy-200"><?= $d['duration_weeks'] ?> wks</td>
                    <td class="px-3 py-2 text-slate-600 dark:text-navy-200"><?= htmlspecialchars($d['frequency'] ?? 'Daily') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($r['notes']): ?>
            <p class="mt-2 text-xs text-slate-500"><span class="font-medium">Clinician notes:</span> <?= htmlspecialchars($r['notes']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Resistance Profile -->
          <div>
            <h4 class="text-sm font-semibold text-slate-700 dark:text-navy-100 mb-2">Resistance Profile</h4>
            <?php if (empty($resistance)): ?>
            <div class="rounded-lg border border-slate-200 dark:border-navy-600 p-4 text-center text-sm text-slate-400">
              No resistance data recorded
            </div>
            <?php else: ?>
            <div class="rounded-lg border border-error/20 bg-error/5 p-3 space-y-1.5">
              <p class="text-xs font-semibold text-error uppercase">Resistant to:</p>
              <?php foreach ($resistance as $res): ?>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-700 dark:text-navy-100"><?= htmlspecialchars($res['drug_name']) ?> (<?= $res['drug_code'] ?>)</span>
                <span class="text-xs text-slate-400"><?= $res['test_method'] ?> — <?= date('M j', strtotime($res['result_date'])) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-5 flex flex-col sm:flex-row items-start sm:items-center justify-end gap-3 border-t border-slate-100 dark:border-navy-600 pt-4">
          <button type="button" onclick="document.getElementById('reject-<?= $r['id'] ?>').classList.toggle('hidden')" class="btn h-9 border border-error/30 text-error hover:bg-error/10 px-5">Reject</button>
          <a href="regimen_reviews.php?action=approve&id=<?= $r['id'] ?>" class="btn h-9 bg-success text-white hover:bg-success/90 px-8">
            <svg class="mr-1 size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            Approve Regimen
          </a>
        </div>

        <!-- Reject Form (hidden) -->
        <div id="reject-<?= $r['id'] ?>" class="hidden mt-3 border-t border-slate-100 dark:border-navy-600 pt-4">
          <form method="POST">
            <input type="hidden" name="reject_with_notes" value="1">
            <input type="hidden" name="regimen_id" value="<?= $r['id'] ?>">
            <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Rejection Reason *</label>
            <textarea name="rejection_notes" rows="2" required placeholder="Explain why this regimen is being rejected..."
                      class="form-input w-full rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90"></textarea>
            <div class="flex justify-end gap-2 mt-2">
              <button type="button" onclick="document.getElementById('reject-<?= $r['id'] ?>').classList.add('hidden')" class="btn h-8 border border-slate-300 text-slate-600 dark:border-navy-500 dark:text-navy-200 px-4 text-xs">Cancel</button>
              <button type="submit" class="btn h-8 bg-error text-white hover:bg-error/90 px-4 text-xs">Confirm Rejection</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Recently Reviewed -->
  <?php if (!empty($reviewed)): ?>
  <h2 class="mt-8 mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Recently Reviewed</h2>
  <div class="card">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">PATIENT</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">REGIMEN</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">STATUS</th>
            <th class="whitespace-nowrap bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">REVIEWED BY</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold uppercase text-slate-600 dark:bg-navy-800 dark:text-navy-200">DATE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviewed as $r): ?>
          <tr class="border-y border-transparent border-b-slate-100 dark:border-b-navy-600">
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-700 dark:text-navy-100"><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($r['regimen_name']) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5">
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $r['status'] === 'active' ? 'bg-success/10 text-success' : 'bg-error/10 text-error' ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500"><?= htmlspecialchars($r['reviewer_name'] ?? '') ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500"><?= $r['reviewed_at'] ? date('M j, H:i', strtotime($r['reviewed_at'])) : '' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>


<?php $notify_variant = 'success'; require_once 'doctor_footer.php'; ?>