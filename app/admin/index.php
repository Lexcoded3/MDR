<?php
session_start();
 $required_role = 'admin';
require_once '../config/auth_check.php';
require_once '../config/db.php';
require_once 'admin_init.php';

 $pageTitle = 'Admin Dashboard - GxAlert';
 $stats = getSystemStats($conn);

// Recent users created
 $recent_users = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM patients WHERE created_by = u.id AND is_active = 1) AS patient_count
    FROM users u 
    ORDER BY u.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Recent facilities
 $recent_facilities = $conn->query("
    SELECT f.*, 
           (SELECT COUNT(*) FROM patients WHERE facility_id = f.id AND is_active = 1) AS patient_count
    FROM facilities f 
    ORDER BY f.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Users by role for chart
 $role_chart_data = [];
foreach ($stats['users_by_role'] as $role => $count) {
    $role_chart_data[] = [
        'label' => $role_labels[$role] ?? $role,
        'count' => $count
    ];
}

// SMS stats last 7 days
 $sms_trend = $conn->query("
    SELECT DATE(sent_at) AS day,
           SUM(status IN ('sent','delivered')) AS sent_count,
           SUM(status = 'failed') AS failed_count
    FROM sms_logs 
    WHERE sent_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sent_at) ORDER BY day
")->fetch_all(MYSQLI_ASSOC);

?>
<?php require_once 'admin_header.php'; ?>

<main class="main-content w-full px-[var(--margin-x)] pb-8">
  <div class="mt-4 grid grid-cols-12 gap-4 sm:mt-5 sm:gap-5 lg:mt-6 lg:gap-6">

    <!-- Welcome Banner -->
    <div class="col-span-12 lg:col-span-8 xl:col-span-9">
  <!-- 1. Added flex layout to the card container (flex flex-col sm:flex-row sm:items-center) -->
  <div class="card flex flex-col sm:flex-row sm:items-center bg-gradient-to-r from-slate-700 to-slate-800 p-5 dark:from-navy-600 dark:to-navy-700">
    
    <!-- 2. Added shrink-0 to prevent squishing and sm:ml-auto/sm:ml-6 to ensure it pushes right with a gap -->
    <div class="flex justify-center sm:order-last shrink-0 sm:ml-6">
      <img class="-mt-16 h-40 sm:mt-0" src="../images/illustrations/doctor.svg" alt="image">
    </div>
    
    <!-- 3. flex-1 acts as the pusher, filling the empty space -->
    <div class="mt-2 flex-1 pt-2 text-center text-white sm:mt-0 sm:text-left">
      <h3 class="text-xl">System Administration</h3>
      <p class="mt-2 leading-relaxed text-slate-300">Manage users, facilities, drug catalog, and system settings.</p>
      
      <!-- 4. Added justify-center sm:justify-start to keep buttons aligned nicely on mobile vs desktop -->
      <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-3">
        <a href="adduser.php" class="btn border border-white/10 bg-white/20 text-white hover:bg-white/30">
          <i class="fa fa-user-plus mr-1.5 text-sm"></i>Add User
        </a>
        <button type="button" onclick="document.getElementById('addFacilityModal').classList.remove('hidden')"
                class="btn border border-white/10 bg-white/20 text-white hover:bg-white/30">
          <i class="fa fa-hospital mr-1.5 text-sm"></i>Add Facility
        </button>
        <button type="button" onclick="document.getElementById('addDrugModal').classList.remove('hidden')"
                class="btn border border-white/10 bg-white/20 text-white hover:bg-white/30">
          <i class="fa fa-pills mr-1.5 text-sm"></i>Add Drug
        </button>
      </div>
    </div>
    
  </div>
</div>

    <!-- System Health -->
    <div class="col-span-12 lg:col-span-4 xl:col-span-3">
      <div class="card p-5 space-y-4">
        <h3 class="font-medium text-slate-700 dark:text-navy-100">System Health</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500 dark:text-navy-300">Cron Job</span>
            <?php if ($stats['cron_healthy']): ?>
            <span class="flex items-center text-xs font-medium text-success">
              <span class="mr-1 size-2 rounded-full bg-success"></span> Active
            </span>
            <?php else: ?>
            <span class="flex items-center text-xs font-medium text-error">
              <span class="mr-1 size-2 rounded-full bg-error"></span> Inactive
            </span>
            <?php endif; ?>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500 dark:text-navy-300">Last Activity</span>
            <span class="text-xs text-slate-700 dark:text-navy-100">
              <?= $stats['last_cron_activity'] ? date('M j, H:i', strtotime($stats['last_cron_activity'])) : 'Never' ?>
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500 dark:text-navy-300">SMS Today</span>
            <span class="text-xs font-medium text-success"><?= $stats['sms_today'] ?> sent</span>
          </div>
          <?php if ($stats['sms_failed_today'] > 0): ?>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500 dark:text-navy-300">SMS Failed</span>
            <span class="text-xs font-medium text-error"><?= $stats['sms_failed_today'] ?></span>
          </div>
          <?php endif; ?>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-500 dark:text-navy-300">Active AEs</span>
            <span class="text-xs font-medium text-warning"><?= $stats['active_ae'] ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="col-span-12 grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-5 lg:col-span-8 xl:col-span-9">
      <div class="card p-4">
        <p class="text-xs text-slate-400 dark:text-navy-300">Total Users</p>
        <p class="mt-1 text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= number_format($stats['total_users']) ?></p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-slate-400 dark:text-navy-300">Total Patients</p>
        <p class="mt-1 text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= number_format($stats['total_patients']) ?></p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-slate-400 dark:text-navy-300">On Treatment</p>
        <p class="mt-1 text-2xl font-semibold text-primary dark:text-accent"><?= number_format($stats['on_treatment']) ?></p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-slate-400 dark:text-navy-300">Facilities</p>
        <p class="mt-1 text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= number_format($stats['active_facilities']) ?></p>
      </div>
    </div>

    <!-- Users by Role -->
    <div class="col-span-12 lg:col-span-4 xl:col-span-3">
      <div class="card p-4">
        <h3 class="mb-4 font-medium text-slate-700 dark:text-navy-100">Users by Role</h3>
        <div class="space-y-3">
          <?php foreach ($stats['users_by_role'] as $role => $count): 
            $pct = $stats['total_users'] > 0 ? round(($count / $stats['total_users']) * 100) : 0;
          ?>
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-slate-600 dark:text-navy-200"><?= $role_labels[$role] ?? $role ?></span>
              <span class="font-medium text-slate-700 dark:text-navy-100"><?= $count ?></span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-200 dark:bg-navy-600">
              <div class="h-full rounded-full bg-primary dark:bg-accent" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="col-span-12 lg:col-span-8 xl:col-span-9">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Recent Users</h2>
        <a href="users.php" class="text-xs text-primary dark:text-accent-light hover:underline">View All</a>
      </div>
      <div class="card">
        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
          <table class="is-hoverable w-full text-left">
            <thead>
              <tr>
                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">NAME</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">ROLE</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LOCATION</th>
                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100">LAST LOGIN</th>
                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 text-xs font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_users as $u): ?>
              <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center space-x-3">
                    <div class="avatar size-8">
                      <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary dark:bg-accent/10 dark:text-accent">
                        <?= strtoupper(substr($u['name'], 0, 2)) ?>
                      </div>
                    </div>
                    <div>
                      <span class="font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($u['name']) ?></span>
                      <p class="text-xs text-slate-400 dark:text-navy-300"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?= $role_colors[$u['role']] ?? 'bg-slate-100 text-slate-600' ?>">
                    <?= $role_labels[$u['role']] ?? $u['role'] ?>
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-navy-200"><?= htmlspecialchars($u['location'] ?? '-') ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-navy-300"><?= $u['last_login'] ? date('M j, H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                <td class="whitespace-nowrap px-4 py-3">
                  <a href="edituser.php?id=<?= $u['id'] ?>" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 dark:hover:bg-navy-300/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent Facilities -->
    <div class="col-span-12 lg:col-span-4 xl:col-span-3">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-medium text-slate-700 dark:text-navy-100">Facilities</h2>
        <a href="facilities.php" class="text-xs text-primary dark:text-accent-light hover:underline">View All</a>
      </div>
      <div class="space-y-3">
        <?php foreach ($recent_facilities as $f): ?>
        <div class="card p-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium text-slate-700 dark:text-navy-100 line-clamp-1"><?= htmlspecialchars($f['name']) ?></h3>
              <p class="text-xs text-slate-400 dark:text-navy-300"><?= htmlspecialchars($f['address'] ?? '') ?></p>
            </div>
            <span class="text-lg font-semibold text-primary dark:text-accent"><?= $f['patient_count'] ?></span>
          </div>
          <p class="mt-1 text-xs text-slate-400">patients</p>
        </div>
        <?php endforeach; ?>
        <?php if (empty($recent_facilities)): ?>
        <div class="card p-6 text-center text-slate-400">No facilities yet</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<!-- ══════════════════════════════════════════════
     ADD FACILITY MODAL
═══════════════════════════════════════════════ -->
<div id="addFacilityModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center px-4">
  <div class="absolute inset-0 bg-slate-900/60" onclick="closeModal('addFacilityModal')"></div>
  <div class="relative w-full max-w-lg rounded-xl bg-white dark:bg-navy-750 shadow-xl z-10 overflow-hidden">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-navy-600">
      <div class="flex items-center gap-2.5">
        <div class="flex size-8 items-center justify-center rounded-lg bg-success/10">
          <i class="fa fa-hospital text-success text-sm"></i>
        </div>
        <div>
          <h3 class="font-semibold text-slate-700 dark:text-navy-100">Add Facility</h3>
          <p class="text-xs text-slate-400">Register a new treatment facility</p>
        </div>
      </div>
      <button onclick="closeModal('addFacilityModal')" class="btn size-7 rounded-full p-0 hover:bg-slate-100 dark:hover:bg-navy-600">
        <i class="fa fa-times text-slate-400"></i>
      </button>
    </div>

    <!-- Form -->
    <form id="facilityForm" class="px-6 py-5 space-y-4 max-h-[75vh] overflow-y-auto">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Name -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">
            Facility Name <span class="text-error">*</span>
          </label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-building text-sm"></i></span>
            <input type="text" name="name" required placeholder="e.g. Mulago National Referral Hospital"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Facility Code -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Facility Code</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-hashtag text-sm"></i></span>
            <input type="text" name="facility_code" placeholder="e.g. MNH-001"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Facility Type -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Facility Type</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-layer-group text-sm"></i></span>
            <select name="facility_type" class="form-select flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
              <option value="">— Select Type —</option>
              <option value="national_referral">National Referral</option>
              <option value="regional_referral">Regional Referral</option>
              <option value="district">District</option>
              <option value="primary">Primary</option>
              <option value="community">Community</option>
            </select>
          </div>
        </div>

        <!-- Contact Person -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Contact Person</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-user text-sm"></i></span>
            <input type="text" name="contact_person" placeholder="e.g. Dr. John Doe"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Phone</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-phone text-sm"></i></span>
            <input type="text" name="phone" placeholder="+256 700 000 000"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Email</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-envelope text-sm"></i></span>
            <input type="email" name="email" placeholder="facility@health.go.ug"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Contact Phone (alternate) -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Alternate Phone</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 text-slate-400"><i class="fa fa-phone-volume text-sm"></i></span>
            <input type="text" name="contact_phone" placeholder="+256 700 000 001"
                   class="form-input flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Address -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Address</label>
          <div class="flex items-start rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center pl-3 pt-3 text-slate-400"><i class="fa fa-location-dot text-sm"></i></span>
            <textarea name="address" rows="2" placeholder="Physical address..."
                      class="form-textarea flex-1 bg-transparent py-2.5 pl-2 pr-3 text-sm outline-none border-none focus:ring-0 resize-none"></textarea>
          </div>
        </div>
      </div>

      <!-- Result -->
      <div id="facilityResult" class="hidden rounded-lg px-4 py-3 text-sm font-medium"></div>

    </form>

    <!-- Footer -->
    <div class="flex gap-3 px-6 py-4 border-t border-slate-100 dark:border-navy-600 bg-slate-50 dark:bg-navy-800">
      <button type="button" onclick="closeModal('addFacilityModal')"
              class="btn flex-1 h-10 border border-slate-200 dark:border-navy-500 text-slate-600 dark:text-navy-200 hover:bg-slate-100 dark:hover:bg-navy-700">
        Cancel
      </button>
      <button type="button" onclick="submitFacility()"
              id="facilitySubmitBtn"
              class="btn flex-1 h-10 bg-success text-white hover:bg-success/80 flex items-center justify-center gap-2">
        <i class="fa fa-hospital text-sm"></i> Save Facility
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     ADD DRUG MODAL
═══════════════════════════════════════════════ -->
<div id="addDrugModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center px-4">
  <div class="absolute inset-0 bg-slate-900/60" onclick="closeModal('addDrugModal')"></div>
  <div class="relative w-full max-w-lg rounded-xl bg-white dark:bg-navy-750 shadow-xl z-10 overflow-hidden">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-navy-600">
      <div class="flex items-center gap-2.5">
        <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 dark:bg-accent/10">
          <i class="fa fa-pills text-primary dark:text-accent text-sm"></i>
        </div>
        <div>
          <h3 class="font-semibold text-slate-700 dark:text-navy-100">Add Drug</h3>
          <p class="text-xs text-slate-400">Add to the MDR-TB drug catalog</p>
        </div>
      </div>
      <button onclick="closeModal('addDrugModal')" class="btn size-7 rounded-full p-0 hover:bg-slate-100 dark:hover:bg-navy-600">
        <i class="fa fa-times text-slate-400"></i>
      </button>
    </div>

    <!-- Form -->
    <form id="drugForm" class="px-6 py-5 space-y-4">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <!-- Drug Name -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">
            Drug Name <span class="text-error">*</span>
          </label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 text-slate-400"><i class="fa fa-capsules text-sm"></i></span>
            <input type="text" name="drug_name" required placeholder="e.g. Bedaquiline"
                   class="form-input flex-1 bg-transparent py-2.5 pl-0 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Drug Code -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">
            Drug Code <span class="text-error">*</span>
          </label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 text-slate-400"><i class="fa fa-hashtag text-sm"></i></span>
            <input type="text" name="drug_code" required placeholder="e.g. BDQ"
                   class="form-input flex-1 bg-transparent py-2.5 pl-0 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- WHO Group -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">WHO Group</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 text-slate-400"><i class="fa fa-layer-group text-sm"></i></span>
            <select name="drug_group" class="form-select flex-1 bg-transparent py-2.5 pl-0 pr-3 text-sm outline-none border-none focus:ring-0">
              <option value="group_a">Group A — Core</option>
              <option value="group_b">Group B — Choice</option>
              <option value="group_c">Group C — Add-on</option>
              <option value="group_d1">Group D1 — Repurposed</option>
              <option value="group_d2">Group D2 — Injectable</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <!-- Default Dose -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Default Dose</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 text-slate-400"><i class="fa fa-scale-balanced text-sm"></i></span>
            <input type="number" name="default_dose_mg" placeholder="400" min="0"
                   class="form-input flex-1 bg-transparent py-2.5 pl-0 pr-3 text-sm outline-none border-none focus:ring-0">
          </div>
        </div>

        <!-- Unit -->
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Unit</label>
          <div class="flex items-center rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 text-slate-400"><i class="fa fa-ruler text-sm"></i></span>
            <select name="unit" class="form-select flex-1 bg-transparent py-2.5 pl-0 pr-3 text-sm outline-none border-none focus:ring-0">
              <option value="mg">mg</option>
              <option value="ml">ml</option>
              <option value="tablets">tablets</option>
              <option value="g">g</option>
            </select>
          </div>
        </div>

        <!-- Notes -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-slate-600 dark:text-navy-200 mb-1.5">Notes</label>
          <div class="flex items-start rounded-lg bg-slate-100 dark:bg-navy-900/90 focus-within:ring-2 focus-within:ring-primary/30">
            <span class="flex shrink-0 items-center px-3 pt-3 text-slate-400"><i class="fa fa-note-sticky text-sm"></i></span>
            <textarea name="notes" rows="2" placeholder="e.g. Monitor QTc interval, hepatotoxic..."
                      class="form-textarea flex-1 bg-transparent py-2.5 pl-0 pr-3 gap-2 text-sm outline-none border-none focus:ring-0 resize-none"></textarea>
          </div>
        </div>
      </div>

      <!-- Result -->
      <div id="drugResult" class="hidden rounded-lg px-4 py-3 text-sm font-medium"></div>

    </form>

    <!-- Footer -->
    <div class="flex gap-3 px-6 py-4 border-t border-slate-100 dark:border-navy-600 bg-slate-50 dark:bg-navy-800">
      <button type="button" onclick="closeModal('addDrugModal')"
              class="btn flex-1 h-10 border border-slate-200 dark:border-navy-500 text-slate-600 dark:text-navy-200 hover:bg-slate-100">
        Cancel
      </button>
      <button type="button" onclick="submitDrug()"
              id="drugSubmitBtn"
              class="btn flex-1 h-10 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus flex items-center justify-center gap-2">
        <i class="fa fa-pills text-sm"></i> Add Drug
      </button>
    </div>
  </div>
</div>


<?php require_once 'admin_footer.php'; ?>