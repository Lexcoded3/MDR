<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'SMS Logs - GxAlert';

 $filter_status = trim($_GET['status'] ?? '');
 $filter_date   = trim($_GET['date'] ?? date('Y-m-d'));
 $page = max(1, (int)($_GET['page'] ?? 1));
 $perPage = 8;
 $offset = ($page - 1) * $perPage;

 $where = ["DATE(sl.sent_at) = ?"];
 $params = [$filter_date]; $types = "s";

if (in_array($filter_status, ['sent', 'failed'])) {  // removed 'delivered','pending' — not in enum
    $where[] = "sl.status = ?"; $params[] = $filter_status; $types .= "s";
}

 $where_sql = implode(' AND ', $where);

// Daily summary
 $summary = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(status IN ('sent','delivered')) AS success,
        SUM(status = 'failed') AS failed,
        SUM(cost) AS total_cost
    FROM sms_logs WHERE DATE(sent_at) = '$filter_date'
")->fetch_assoc();

// Paginated logs
 $count_stmt = $conn->prepare("SELECT COUNT(*) FROM sms_logs sl WHERE $where_sql");
 $count_stmt->bind_param($types, ...$params);
 $count_stmt->execute();
 $total = (int)$count_stmt->get_result()->fetch_column();
 $totalPages = max(1, ceil($total / $perPage));

 $logs_stmt = $conn->prepare("
    SELECT sl.*, p.full_name AS patient_name, p.patient_code
    FROM sms_logs sl
    LEFT JOIN patients p ON sl.patient_id = p.id
    WHERE $where_sql ORDER BY sl.sent_at DESC LIMIT $perPage OFFSET $offset
");
 $logs_stmt->bind_param($types, ...$params);
 $logs_stmt->execute();
 $logs = $logs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$status_colors = [
    'sent'   => 'bg-info/10 text-info',
    'failed' => 'bg-error/10 text-error',
];

?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <!-- Page Header -->
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-xl font-semibold text-slate-700 dark:text-navy-100">SMS Logs</h1>

  <!-- Test Fire Button -->
  <div class="flex items-center gap-2">

    <!-- Patient selector -->
    <select id="testPatientSelect" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      <option value="">— Select Patient —</option>
      <?php
        $pts = $conn->query("
            SELECT p.id, p.full_name, p.patient_code 
            FROM patients p
            JOIN treatment_regimens tr ON tr.patient_id = p.id
            JOIN medication_schedule ms ON ms.regimen_id = tr.id
            WHERE tr.status = 'active' AND ms.is_active = 1 AND p.is_active = 1
            GROUP BY p.id
            ORDER BY p.full_name
        ");
        while ($pt = $pts->fetch_assoc()):
      ?>
      <option value="<?= $pt['id'] ?>">
        <?= htmlspecialchars($pt['full_name']) ?> (<?= $pt['patient_code'] ?>)
      </option>
      <?php endwhile; ?>
    </select>

    <!-- Fire button -->
    <button id="testFireBtn" onclick="triggerTestFire()"
      class="btn h-9 bg-warning text-white hover:bg-warning/80 px-4 flex items-center gap-2">
      <i class="fa fa-bolt text-sm"></i>
      <span>Test Fire SMS</span>
    </button>
    <!-- Quick Send Button -->
<button onclick="document.getElementById('quickSendModal').classList.remove('hidden')"
  class="btn h-9 bg-info text-white hover:bg-info/80 px-4 flex items-center gap-2">
  <i class="fa fa-paper-plane text-sm"></i>
  <span>Quick Send</span>
</button>


  </div>
</div>

<!-- Result toast -->
<div id="testFireResult" class="hidden mt-3 rounded-lg px-4 py-3 text-sm font-medium"></div>

  <!-- Summary Cards -->
  <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="card p-4">
      <p class="text-xs text-slate-400">Total</p>
      <p class="mt-1 text-xl font-semibold text-slate-700 dark:text-navy-100"><?= (int)($summary['total'] ?? 0) ?></p>
    </div>
    <div class="card p-4">
      <p class="text-xs text-slate-400">Delivered</p>
      <p class="mt-1 text-xl font-semibold text-success"><?= (int)($summary['success'] ?? 0) ?></p>
    </div>
    <div class="card p-4">
      <p class="text-xs text-slate-400">Failed</p>
      <p class="mt-1 text-xl font-semibold text-error"><?= (int)($summary['failed'] ?? 0) ?></p>
    </div>
    <div class="card p-4">
      <p class="text-xs text-slate-400">Cost</p>
      <p class="mt-1 text-xl font-semibold text-slate-700 dark:text-navy-100"><?= number_format($summary['total_cost'] ?? 0, 2) ?></p>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
    <input type="date" name="date" value="<?= $filter_date ?>" class="form-input rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
    <select name="status" class="form-select rounded-lg bg-slate-150 py-2 px-3 text-sm dark:bg-navy-900/90">
      <option value="">All Status</option>
      <option value="sent" <?= $filter_status === 'sent' ? 'selected' : '' ?>>Sent</option>
      <option value="delivered" <?= $filter_status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="failed" <?= $filter_status === 'failed' ? 'selected' : '' ?>>Failed</option>
      <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
    </select>
    <button type="submit" class="btn h-9 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus px-5">Filter</button>
    <a href="sms_logs.php" class="btn h-9 border border-slate-300 text-slate-600 dark:border-navy-500 dark:text-navy-200 px-4">Clear</a>
  </form>

  <!-- Table -->
  <div class="card mt-3">
    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
      <table class="is-hoverable w-full text-left">
        <thead>
          <tr>
            <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">TIME</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PATIENT</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">PHONE</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">MESSAGE</th>
            <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">STATUS</th>
            <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">COST</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-navy-300"><?= date('H:i:s', strtotime($l['sent_at'])) ?></td>
            <td class="whitespace-nowrap px-4 py-2.5 text-sm">
              <?= htmlspecialchars($l['patient_name'] ?? 'Unknown') ?>
              <span class="text-xs text-slate-400"><?= $l['patient_code'] ?? '' ?></span>
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs font-mono text-slate-500"><?= htmlspecialchars($l['phone_number'] ?? '') ?></td>
            <td class="px-4 py-2.5 text-xs text-slate-600 dark:text-navy-200 max-w-xs truncate" title="<?= htmlspecialchars($l['message'] ?? '') ?>">
              <?= htmlspecialchars(mb_substr($l['message'] ?? '', 0, 60)) ?>...
            </td>
            <td class="whitespace-nowrap px-4 py-2.5">
              <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $status_colors[$l['status']] ?? '' ?>">
        <?= ucfirst($l['status']) ?>
    </span>
    <?php if (!empty($l['delivery_report'])): ?>
    <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $l['delivery_report'] === 'delivered' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' ?>">
        <?= ucfirst($l['delivery_report']) ?>
    </span>
    <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500">
              <?= $l['cost'] !== null ? 'UGX ' . number_format($l['cost'], 4) : '-' ?>
          </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No SMS logs for this date</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="mt-4 flex items-center justify-between">
    <p class="text-sm text-slate-500">Page <?= $page ?> of <?= $totalPages ?></p>
    <div class="flex space-x-1">
      <?php for ($i = 1; $i <= min($totalPages, 10); $i++):
        $qp = http_build_query(array_merge($_GET, ['page' => $i]));
      ?>
      <a href="?<?= $qp ?>" class="btn size-8 rounded-lg p-0 text-sm <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-200 dark:hover:bg-navy-600' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
  <!-- ── Quick Send Modal ─────────────────────────────────────────────────── -->
<div id="quickSendModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center px-4">
  <!-- Backdrop -->
  <div class="absolute inset-0 bg-slate-900/60" onclick="closeQuickSend()"></div>

  <!-- Card -->
  <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-navy-750 shadow-xl p-6 z-10">
    
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-2">
        <div class="flex size-9 items-center justify-center rounded-lg bg-info/10">
          <i class="fa fa-paper-plane text-info"></i>
        </div>
        <div>
          <h3 class="font-semibold text-slate-700 dark:text-navy-100">Quick Send SMS</h3>
          <p class="text-xs text-slate-400">Send to any number — no schedule needed</p>
        </div>
      </div>
      <button onclick="closeQuickSend()" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20">
        <i class="fa fa-times text-slate-400"></i>
      </button>
    </div>

    <!-- Phone -->
    <div class="mb-4">
      <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-navy-200">
        Phone Number <span class="text-error">*</span>
      </label>
      <div class="flex rounded-lg overflow-hidden border border-slate-200 dark:border-navy-500 focus-within:border-info">
        <span class="flex items-center bg-slate-100 dark:bg-navy-900 px-3 text-sm text-slate-500 border-r border-slate-200 dark:border-navy-500">+</span>
        <input id="qs_phone" type="tel" placeholder="256701234567"
          class="flex-1 bg-white dark:bg-navy-700 px-3 py-2 text-sm outline-none text-slate-700 dark:text-navy-100 placeholder-slate-300">
      </div>
      <p class="mt-1 text-xs text-slate-400">Include country code, no + needed. e.g. 256701234567</p>
    </div>

    <!-- Message -->
    <div class="mb-5">
      <label class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-navy-200">
        Message <span class="text-error">*</span>
      </label>
      <textarea id="qs_message" rows="3" maxlength="160"
        placeholder="Type your message here..."
        oninput="document.getElementById('qs_charcount').textContent = this.value.length"
        class="w-full rounded-lg border border-slate-200 dark:border-navy-500 bg-white dark:bg-navy-700 px-3 py-2 text-sm outline-none focus:border-info text-slate-700 dark:text-navy-100 placeholder-slate-300 resize-none"></textarea>
      <p class="mt-1 text-right text-xs text-slate-400"><span id="qs_charcount">0</span>/160</p>
    </div>

    <!-- Quick message presets -->
    <div class="mb-5 mt-2">
      <p class="mb-2 text-xs font-medium text-slate-500">Quick templates:</p>
      <div class="flex flex-wrap gap-2">
        <button type="button" onclick="setTemplate('GxAlert: This is a system test message. Please ignore.')"
          class="rounded-full border border-slate-200 dark:border-navy-500 px-3 py-1 text-xs hover:bg-slate-100 dark:hover:bg-navy-600 text-slate-600 dark:text-navy-200">
          System Test
        </button>
        <button type="button" onclick="setTemplate('GxAlert Reminder: Please remember to take your medication today. Reply HELP for support.')"
          class="rounded-full border border-slate-200 dark:border-navy-500 px-3 py-1 text-xs hover:bg-slate-100 dark:hover:bg-navy-600 text-slate-600 dark:text-navy-200">
          Medication Reminder
        </button>
        <button type="button" onclick="setTemplate('GxAlert: Your appointment is due soon. Please contact your facility to confirm.')"
          class="rounded-full border border-slate-200 dark:border-navy-500 px-3 py-1 text-xs hover:bg-slate-100 dark:hover:bg-navy-600 text-slate-600 dark:text-navy-200">
          Appointment
        </button>
      </div>
    </div>

    <!-- Result -->
    <div id="qs_result" class="hidden mb-4 mt-3 rounded-lg px-4 py-3 text-sm font-medium"></div>

    <!-- Actions -->
    <div class="flex gap-3 mt-2">
      <button onclick="closeQuickSend()"
        class="btn flex-1 h-10 border border-slate-200 dark:border-navy-500 text-slate-600 dark:text-navy-200 hover:bg-slate-100">
        Cancel
      </button>
      <button id="qs_sendBtn" onclick="sendQuickSMS()"
        class="btn flex-1 h-10 bg-info text-white hover:bg-info/80 flex items-center justify-center gap-2">
        <i class="fa fa-paper-plane text-sm"></i> Send SMS
      </button>
    </div>

  </div>
</div>


<?php require_once 'admin_footer.php'; ?>