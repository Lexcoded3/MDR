<!-- App Header Wrapper-->
      <nav class="header before:bg-white dark:before:bg-navy-750 print:hidden">
        <!-- App Header  -->
        <div class="header-container relative flex w-full bg-white dark:bg-navy-750 print:hidden">
          <!-- Header Items -->
          <div class="flex w-full items-center justify-between">
            <!-- Left: Sidebar Toggle Button -->
            <div class="size-7">
              <button class="menu-toggle ml-0.5 flex size-7 flex-col justify-center space-y-1.5 text-primary outline-none focus:outline-none dark:text-accent-light/80" :class="$store.global.isSidebarExpanded && 'active'" @click="$store.global.isSidebarExpanded = !$store.global.isSidebarExpanded">
                <span></span>
                <span></span>
                <span></span>
              </button>
            </div>

            <!-- Right: Header buttons -->
            <div class="-mr-1.5 flex items-center space-x-2">

              <!-- Dark Mode Toggle -->
              <button @click="$store.global.isDarkModeEnabled = !$store.global.isDarkModeEnabled" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg x-show="$store.global.isDarkModeEnabled" x-transition:enter="transition-transform duration-200 ease-out absolute origin-top" x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static" class="size-6 text-amber-400" fill="currentColor" viewbox="0 0 24 24">
                  <path d="M11.75 3.412a.818.818 0 01-.07.917 6.332 6.332 0 00-1.4 3.971c0 3.564 2.98 6.494 6.706 6.494a6.86 6.86 0 002.856-.617.818.818 0 011.1 1.047C19.593 18.614 16.218 21 12.283 21 7.18 21 3 16.973 3 11.956c0-4.563 3.46-8.31 7.925-8.948a.818.818 0 01.826.404z"></path>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" x-show="!$store.global.isDarkModeEnabled" x-transition:enter="transition-transform duration-200 ease-out absolute origin-top" x-transition:enter-start="scale-75" x-transition:enter-end="scale-100 static" class="size-6 text-amber-400" viewbox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                </svg>
              </button>
              <!-- Monochrome Mode Toggle -->
              <button @click="$store.global.isMonochromeModeEnabled = !$store.global.isMonochromeModeEnabled" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <i class="fa-solid fa-palette bg-gradient-to-r from-sky-400 to-blue-600 bg-clip-text text-lg font-semibold text-transparent"></i>
              </button>

              <!-- Notification-->
              <div x-data="notificationBell()" 
               x-init="loadNotifications(); setInterval(loadNotifications, 30000)"
               x-effect="if($store.global.isSearchbarActive) isShowPopper = false" 
               x-data="usePopper({placement:'bottom-end',offset:12})" 
               @click.outside="isShowPopper && (isShowPopper = false)" 
               class="flex">
              <div x-effect="if($store.global.isSearchbarActive) isShowPopper = false" x-data="usePopper({placement:'bottom-end',offset:12})" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
                <!-- Bell Button -->
                <button @click="isShowPopper = !isShowPopper; if(isShowPopper && unreadCount > 0) markAllRead();" 
                        x-ref="popperRef" 
                        class="btn relative size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-500 dark:text-navy-100" stroke="currentColor" fill="none" viewbox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.375 17.556h-6.75m6.75 0H21l-1.58-1.562a2.254 2.254 0 01-.67-1.596v-3.51a6.612 6.612 0 00-1.238-3.85 6.744 6.744 0 00-3.262-2.437v-.379c0-.59-.237-1.154-.659-1.571A2.265 2.265 0 0012 2c-.597 0-1.169.234-1.591.65a2.208 2.208 0 00-.659 1.572v.38c-2.621.915-4.5 3.385-4.5 6.287v3.51c0 .598-.24 1.172-.67 1.595L3 17.556h12.375zm0 0v1.11c0 .885-.356 1.733-.989 2.358A3.397 3.397 0 0112 22a3.397 3.397 0 01-2.386-.976 3.313 3.313 0 01-.989-2.357v-1.111h6.75z"></path>
                  </svg>

                  <!-- Unread Badge -->
                  <span x-show="unreadCount > 0" class="absolute -top-px -right-px flex size-3 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-80"></span>
                    <span class="inline-flex size-2 rounded-full bg-secondary"></span>
                  </span>
                </button>
                <div :class="isShowPopper && 'show'" class="popper-root" x-ref="popperRoot">
                <div x-data="{activeTab:'tabAll'}" class="popper-box mx-4 mt-1 flex max-h-[calc(100vh-6rem)] w-[calc(100vw-2rem)] flex-col rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-800 dark:bg-navy-700 dark:shadow-soft-dark sm:m-0 sm:w-80">
                                <div class="rounded-t-lg bg-slate-100 text-slate-600 dark:bg-navy-800 dark:text-navy-200">
                                  <div class="flex items-center justify-between px-4 pt-2">
                      <div class="flex items-center space-x-2">
                        <h3 class="font-medium text-slate-700 dark:text-navy-100">Notifications</h3>
                        <div x-show="unreadCount > 0" class="badge h-5 rounded-full bg-primary/10 px-1.5 text-primary dark:bg-accent-light/15 dark:text-accent-light" x-text="unreadCount"></div>
                      </div>
                      <button @click="markAllRead()" class="btn -mr-1.5 size-7 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25" title="Mark all as read">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                      </button>
                    </div>

                      <!-- Tabs -->
                    <div class="is-scrollbar-hidden flex shrink-0 overflow-x-auto px-3">
                      <template x-for="tab in [{key:'tabAll',label:'All'},{key:'tabAlerts',label:'Alerts'},{key:'tabEvents',label:'Events'},{key:'tabLogs',label:'Logs'}]">
                        <button @click="activeTab = tab.key" 
                                :class="activeTab === tab.key ? 'border-primary dark:border-accent text-primary dark:text-accent-light' : 'border-transparent hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'" 
                                class="btn shrink-0 rounded-none border-b-2 px-3.5 py-2.5">
                          <span x-text="tab.label"></span>
                        </button>
                      </template>
                    </div>
                    </div>

                    <div class="tab-content flex flex-col overflow-hidden">
                      <!-- ALL TAB -->
                      <div x-show="activeTab === 'tabAll'" x-transition:enter="transition-all duration-300 ease-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-1 overflow-y-auto px-4 py-4">
                        <template x-for="n in filteredNotifications('')" :key="n.id">
                          <a :href="n.link || '#'" @click="markRead(n.id)" class="flex items-center space-x-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-slate-100 dark:hover:bg-navy-600" :class="!n.is_read && 'bg-primary/5 dark:bg-accent/5'">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="n.icon_bg">
                              <i :class="n.icon"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <p class="text-sm font-medium text-slate-700 dark:text-navy-100" :class="!n.is_read && 'font-semibold'">
                                <span x-text="n.title"></span>
                                <span x-show="!n.is_read" class="ml-1.5 inline-block size-1.5 rounded-full bg-primary dark:bg-accent"></span>
                              </p>
                              <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300" x-text="n.message"></p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400 dark:text-navy-300 whitespace-nowrap" x-text="n.time"></span>
                          </a>
                        </template>
                        <div x-show="filteredNotifications('').length === 0 && !loading" class="py-8 text-center">
                          <svg class="mx-auto size-12 text-slate-300 dark:text-navy-500" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          <p class="mt-2 text-sm text-slate-400">No notifications</p>
                        </div>
                      </div>
                      <!-- ALERTS TAB -->
                      <div x-show="activeTab === 'tabAlerts'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-1 overflow-y-auto px-4 py-4">
                        <template x-for="n in filteredNotifications('alert')" :key="n.id">
                          <a :href="n.link || '#'" @click="markRead(n.id)" class="flex items-center space-x-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-slate-100 dark:hover:bg-navy-600" :class="!n.is_read && 'bg-primary/5 dark:bg-accent/5'">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="n.icon_bg">
                              <i :class="n.icon"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <p class="text-sm font-medium text-slate-700 dark:text-navy-100" :class="!n.is_read && 'font-semibold'">
                                <span x-text="n.title"></span>
                                <span x-show="!n.is_read" class="ml-1.5 inline-block size-1.5 rounded-full bg-primary dark:bg-accent"></span>
                              </p>
                              <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300" x-text="n.message"></p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400 dark:text-navy-300 whitespace-nowrap" x-text="n.time"></span>
                          </a>
                        </template>
                        <div x-show="filteredNotifications('alert').length === 0 && !loading" class="py-8 text-center">
                          <p class="text-sm text-slate-400">No alerts</p>
                        </div>
                      </div>

                      <!-- EVENTS TAB -->
                      <div x-show="activeTab === 'tabEvents'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden space-y-1 overflow-y-auto px-4 py-4">
                        <template x-for="n in filteredNotifications('event')" :key="n.id">
                          <a :href="n.link || '#'" @click="markRead(n.id)" class="flex items-center space-x-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-slate-100 dark:hover:bg-navy-600" :class="!n.is_read && 'bg-primary/5 dark:bg-accent/5'">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="n.icon_bg">
                              <i :class="n.icon"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <p class="text-sm font-medium text-slate-700 dark:text-navy-100" :class="!n.is_read && 'font-semibold'">
                                <span x-text="n.title"></span>
                                <span x-show="!n.is_read" class="ml-1.5 inline-block size-1.5 rounded-full bg-primary dark:bg-accent"></span>
                              </p>
                              <p class="mt-0.5 text-xs text-slate-400 line-clamp-1 dark:text-navy-300" x-text="n.message"></p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400 dark:text-navy-300 whitespace-nowrap" x-text="n.time"></span>
                          </a>
                        </template>
                        <div x-show="filteredNotifications('event').length === 0 && !loading" class="py-8 text-center">
                          <p class="text-sm text-slate-400">No events</p>
                        </div>
                      </div>

                      <!-- LOGS TAB -->
                      <div x-show="activeTab === 'tabLogs'" x-transition:enter="transition-all duration-300 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(1rem,0,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden overflow-y-auto px-4">
                        <div class="py-8 text-center">
                          <img class="mx-auto w-36" src="../images/illustrations/empty-girl-box.svg" alt="image">
                          <div class="mt-5">
                            <p class="text-base font-semibold text-slate-700 dark:text-navy-100">No any logs</p>
                            <p class="text-slate-400 dark:text-navy-300">There are no unread logs yet</p>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>
              <script>
              // Remove the duplicate x-data conflict — merge usePopper into notificationBell
              document.addEventListener('alpine:init', () => {
                Alpine.data('notificationBell', () => ({
                  notifications: [],
                  unreadCount: 0,
                  loading: true,
                  isShowPopper: false,

                  async loadNotifications() {
                    try {
                      const res = await fetch('../api/notifications.php');
                      const data = await res.json();
                      this.notifications = data.notifications || [];
                      this.unreadCount = data.unread_count || 0;
                    } catch(e) {
                      // Silently fail — notifications are non-critical
                    }
                    this.loading = false;
                  },

                  filteredNotifications(type) {
                    if (!type) return this.notifications;
                    return this.notifications.filter(n => n.type === type);
                  },

                  async markRead(id) {
                    try {
                      await fetch(`../api/notifications.php?action=read&id=${id}`);
                      const n = this.notifications.find(n => n.id === id);
                      if (n && !n.is_read) {
                        n.is_read = 1;
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                      }
                    } catch(e) {}
                  },

                  async markAllRead() {
                    if (this.unreadCount === 0) return;
                    try {
                      await fetch('../api/notifications.php?action=read_all');
                      this.notifications.forEach(n => n.is_read = 1);
                      this.unreadCount = 0;
                    } catch(e) {}
                  }
                }));
              });
              </script>
              <!-- Right Sidebar Toggle -->
              <button @click="$store.global.isRightSidebarExpanded = true" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5.5 text-slate-500 dark:text-navy-100" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </nav>
<?php
if (empty($conn) || empty($clinician_id)) return;

$sidebar_today = date('Y-m-d');
$sidebar_low_adh_count = 0;
$sidebar_today_appts = 0;
$sidebar_pending_regimens = [];
$sidebar_doses_logged = 0;
$sidebar_total_scheduled = 0;
$sidebar_on_treatment = 0;
$sidebar_active_ae = 0;
$sidebar_recent = [];


// 1. Low adherence
$r = $conn->prepare("
    SELECT COUNT(*) FROM (
        SELECT al.patient_id, ROUND((SUM(al.status IN ('taken','late'))/COUNT(*))*100,1) AS adh
        FROM adherence_logs al
        WHERE al.dose_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY al.patient_id HAVING adh < 85
    ) t
    JOIN patients p ON t.patient_id = p.id 
    WHERE p.created_by = ? AND p.is_active = 1
");
if ($r) { $r->bind_param("i", $clinician_id); $r->execute(); $sidebar_low_adh_count = (int)$r->get_result()->fetch_column(); $r->close(); }

// 2. Today's appointments
$r = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE assigned_to = ? AND DATE(appointment_date) = ? AND status = 'pending'");
if ($r) { $r->bind_param("is", $clinician_id, $sidebar_today); $r->execute(); $sidebar_today_appts = (int)$r->get_result()->fetch_column(); $r->close(); }

// 3. Pending regimens
$r = $conn->prepare("
    SELECT tr.id, p.patient_code, p.full_name, tr.created_at,
           GROUP_CONCAT(CONCAT(d.drug_code,' ',rd.dose_mg,'mg') SEPARATOR ', ') AS drug_summary
    FROM treatment_regimens tr
    JOIN patients p ON tr.patient_id = p.id
    LEFT JOIN regimen_drugs rd ON tr.id = rd.regimen_id AND rd.is_active = 1
    LEFT JOIN drugs d ON rd.drug_id = d.id
    WHERE tr.status = 'pending_review' AND p.created_by = ?
    GROUP BY tr.id ORDER BY tr.created_at DESC LIMIT 3
");
if ($r) { $r->bind_param("i", $clinician_id); $r->execute(); $sidebar_pending_regimens = $r->get_result()->fetch_all(MYSQLI_ASSOC); $r->close(); }

// 4. Doses logged today
$r = $conn->prepare("
    SELECT COUNT(*) FROM adherence_logs al
    JOIN medication_schedule ms ON ms.id = al.schedule_id
    JOIN treatment_regimens tr ON ms.regimen_id = tr.id
    JOIN patients p ON tr.patient_id = p.id
    WHERE p.created_by = ? AND al.dose_date = ?
");
if ($r) { $r->bind_param("is", $clinician_id, $today); $r->execute(); $sidebar_doses_logged = (int)$r->get_result()->fetch_column(); $r->close(); }

// 5. Total scheduled doses up to now
$r = $conn->prepare("
    SELECT COUNT(*) FROM medication_schedule ms
    JOIN treatment_regimens tr ON ms.regimen_id = tr.id AND tr.status = 'active'
    JOIN patients p ON tr.patient_id = p.id
    WHERE p.created_by = ? AND p.is_active = 1 AND ms.dose_time <= CURTIME()
");
if ($r) { $r->bind_param("i", $clinician_id); $r->execute(); $sidebar_total_scheduled = (int)$r->get_result()->fetch_column(); $r->close(); }

$sidebar_doses_pct = $sidebar_total_scheduled > 0 ? round($sidebar_doses_logged / $sidebar_total_scheduled * 100) : 0;

// 6. On treatment count
$r = $conn->prepare("SELECT COUNT(*) FROM patients WHERE created_by = ? AND treatment_status = 'on_treatment' AND is_active = 1");
if ($r) { $r->bind_param("i", $clinician_id); $r->execute(); $sidebar_on_treatment = (int)$r->get_result()->fetch_column(); $r->close(); }

// 7. Active AEs
$r = $conn->prepare("
    SELECT COUNT(*) FROM adverse_events ae
    JOIN patients p ON ae.patient_id = p.id
    WHERE p.created_by = ? AND ae.resolution_date IS NULL AND p.is_active = 1
");
if ($r) { $r->bind_param("i", $clinician_id); $r->execute(); $sidebar_active_ae = (int)$r->get_result()->fetch_column(); $r->close(); }

// 8. Recent audit log
$r = $conn->prepare("
    SELECT a.action, a.table_name, a.record_id, a.created_at, u.name
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC LIMIT 5
");
if ($r) { $r->execute(); $sidebar_recent = $r->get_result()->fetch_all(MYSQLI_ASSOC); $r->close(); }
?>
    <!-- Right Sidebar -->
      <div x-show="$store.global.isRightSidebarExpanded" @keydown.window.escape="$store.global.isRightSidebarExpanded = false">
        <div class="fixed inset-0 z-[150] bg-slate-900/60 transition-opacity duration-200" @click="$store.global.isRightSidebarExpanded = false" x-show="$store.global.isRightSidebarExpanded" x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed right-0 top-0 z-[151] h-full w-full sm:w-80">
          <div x-data="{activeTab:'tabHome'}" class="relative flex h-full w-full transform-gpu flex-col bg-white transition-transform duration-200 dark:bg-navy-750" x-show="$store.global.isRightSidebarExpanded" x-transition:enter="ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between py-2 px-4">
              <p x-show="activeTab === 'tabHome'" class="flex shrink-0 items-center space-x-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"></path>
                </svg>
                <span class="text-xs">Clinic Overview</span>
              </p>
              <p x-show="activeTab === 'tabProjects'" class="flex shrink-0 items-center space-x-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-xs">Quick Actions</span>
              </p>
              <p x-show="activeTab === 'tabActivity'" class="flex shrink-0 items-center space-x-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs">Recent Activity</span>
              </p>
              <button @click="$store.global.isRightSidebarExpanded=false" class="btn -mr-1 size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>

            <div x-show="activeTab === 'tabHome'" x-transition:enter="transition-all duration-500 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden overflow-y-auto overscroll-contain pt-1">
              <div class="mt-3 grid grid-cols-2 gap-3 px-3">
                <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
                  <div class="flex justify-between">
                    <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $sidebar_on_treatment ?></p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary dark:text-accent" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                  </div>
                  <p class="mt-1 text-xs+">On Treatment</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
                  <div class="flex justify-between">
                    <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $sidebar_active_ae ?></p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-warning" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path></svg>
                  </div>
                  <p class="mt-1 text-xs+">Active AEs</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
                  <div class="flex justify-between">
                    <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $sidebar_low_adh_count ?> </p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-error" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path></svg>
                  </div>
                  <p class="mt-1 text-xs+">Low Adherence</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
                  <div class="flex justify-between">
                    <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $sidebar_today_appts ?></p>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-info" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"></path></svg>
                  </div>
                  <p class="mt-1 text-xs+">Today's Appts</p>
                </div>
              </div>

              <div class="mt-4 px-3">
                <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">Pending Regimens</h2>
                <div class="mt-3 space-y-2">
                  <?php if (empty($sidebar_pending_regimens)): ?>
    <p class="text-sm text-slate-400 py-3 text-center">No pending reviews</p>
<?php else: foreach ($sidebar_pending_regimens as $pr): ?>
                  <div class="flex items-center justify-between rounded-lg border border-warning/30 bg-warning/5 p-3 dark:bg-warning/10">
                    <div>
                      <p class="text-sm font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($pr['patient_code']) ?></p>
                      <p class="text-xs text-slate-400">Awaiting doctor review</p>
                    </div>
                    <a href="viewpatient.php?id=42" class="btn size-7 rounded-full bg-warning/20 p-0 text-warning hover:bg-warning/30">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                  </div>
                  <?php endforeach; endif; ?>
                </div>
              </div>

              <div class="mt-4 px-3">
                <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">Doses Logged Today</h2>
                <div class="mt-3 rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
                  <div class="flex items-center justify-between">
                    <p><span class="text-2xl font-semibold text-slate-700 dark:text-navy-100"><?= $sidebar_doses_logged ?></span><span class="text-xs text-slate-400"> / <?= $sidebar_total_scheduled ?></span></p>
                    <div class="rounded-full bg-success" style="width:<?= min($sidebar_doses_pct,100) ?>%"></div>
                  </div>
                  <div class="progress mt-2 h-2 bg-slate-150 dark:bg-navy-500">
                    <div class="rounded-full bg-success" style="width:<?= min($sidebar_doses_pct,100) ?>%"></div>
                  </div>
                </div>
              </div>

              <div class="mt-4 px-3">
                <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">Settings</h2>
                <div class="mt-2 flex flex-col space-y-2">
                  <label class="inline-flex items-center space-x-2">
                    <input x-model="$store.global.isDarkModeEnabled" class="form-switch h-5 w-10 rounded-lg bg-slate-300 before:rounded-md before:bg-slate-50 checked:bg-slate-500 checked:before:bg-white dark:bg-navy-900 dark:before:bg-navy-300 dark:checked:bg-navy-400 dark:checked:before:bg-white" type="checkbox">
                    <span>Dark Mode</span>
                  </label>
                </div>
              </div>
              <div class="h-18"></div>
            </div>

            <div x-show="activeTab === 'tabProjects'" x-transition:enter="transition-all duration-500 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden overflow-y-auto overscroll-contain px-3 pt-1">
              <div class="mt-3 space-y-2">
                <a href="addpatient.php" class="flex items-center space-x-3 rounded-lg bg-primary/10 p-3 hover:bg-primary/20 dark:bg-accent/10 dark:hover:bg-accent/20">
                  <div class="flex size-10 items-center justify-center rounded-lg bg-primary dark:bg-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path></svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-navy-100">Register Patient</p>
                    <p class="text-xs text-slate-400">New enrollment form</p>
                  </div>
                </a>
                <a href="adherence_log.php" class="flex items-center space-x-3 rounded-lg bg-slate-100 p-3 hover:bg-slate-150 dark:bg-navy-600 dark:hover:bg-navy-500">
                  <div class="flex size-10 items-center justify-center rounded-lg bg-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path></svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-navy-100">Log Adherence</p>
                    <p class="text-xs text-slate-400">Record today's doses</p>
                  </div>
                </a>
                <a href="assign_regimen.php" class="flex items-center space-x-3 rounded-lg bg-slate-100 p-3 hover:bg-slate-150 dark:bg-navy-600 dark:hover:bg-navy-500">
                  <div class="flex size-10 items-center justify-center rounded-lg bg-info">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"></path></svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-navy-100">Assign Regimen</p>
                    <p class="text-xs text-slate-400">Prescribe treatment</p>
                  </div>
                </a>
                <a href="sms_logs.php" class="flex items-center space-x-3 rounded-lg bg-slate-100 p-3 hover:bg-slate-150 dark:bg-navy-600 dark:hover:bg-navy-500">
                  <div class="flex size-10 items-center justify-center rounded-lg bg-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"></path></svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-navy-100">SMS Logs</p>
                    <p class="text-xs text-slate-400">View reminder status</p>
                  </div>
                </a>
              </div>
              <div class="h-18"></div>
            </div>

            <div x-show="activeTab === 'tabActivity'" x-transition:enter="transition-all duration-500 easy-in-out" x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]" x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]" class="is-scrollbar-hidden overflow-y-auto overscroll-contain pt-1">
              <ol class="timeline line-space mt-5 px-4 [--size:1.5rem]">
                <?php foreach ($sidebar_recent as $r): ?>
                <li class="timeline-item">
                  <div class="timeline-item-point rounded-full border border-current bg-white text-success dark:bg-navy-700"><i class="fa fa-user-plus text-tiny"></i></div>
                  <div class="timeline-item-content flex-1 pl-4">
                    <div class="flex flex-col justify-between pb-2 sm:flex-row sm:pb-0">
                      <p class="pb-2 font-medium leading-none text-slate-600 dark:text-navy-100 sm:pb-0"><?= htmlspecialchars($r['action']) ?></p>
                      <span class="text-xs text-slate-400"><?php
$diff = time() - strtotime($r['created_at']);
if ($diff < 60)           echo $diff . 's ago';
elseif ($diff < 3600)     echo round($diff/60) . 'm ago';
elseif ($diff < 86400)    echo round($diff/3600) . 'h ago';
else                      echo round($diff/86400) . 'd ago';
?></span>
                    </div>
                    <p class="py-1"><?= htmlspecialchars($r['table_name']) ?> #<?= (int)$r['record_id'] ?></p>
                  </div>
                </li>
                <?php endforeach; ?>
                <!-- <li class="timeline-item">
                  <div class="timeline-item-point rounded-full border border-current bg-white text-primary dark:bg-navy-700 dark:text-accent"><i class="fa fa-clipboard-check text-tiny"></i></div>
                  <div class="timeline-item-content flex-1 pl-4">
                    <div class="flex flex-col justify-between pb-2 sm:flex-row sm:pb-0">
                      <p class="pb-2 font-medium leading-none text-slate-600 dark:text-navy-100 sm:pb-0">Regimen Submitted</p>
                      <span class="text-xs text-slate-400">1 hour ago</span>
                    </div>
                    <p class="py-1">Regimen for MDR-2025-0042 sent for doctor review</p>
                  </div>
                </li>
                <li class="timeline-item">
                  <div class="timeline-item-point rounded-full border border-current bg-white text-info dark:bg-navy-700"><i class="fa fa-check-double text-tiny"></i></div>
                  <div class="timeline-item-content flex-1 pl-4">
                    <div class="flex flex-col justify-between pb-2 sm:flex-row sm:pb-0">
                      <p class="pb-2 font-medium leading-none text-slate-600 dark:text-navy-100 sm:pb-0">Adherence Batch Logged</p>
                      <span class="text-xs text-slate-400">2 hours ago</span>
                    </div>
                    <p class="py-1">18 doses logged for morning session</p>
                  </div>
                </li> -->
              </ol>
              <div class="h-18"></div>
            </div>

            <div class="pointer-events-none absolute bottom-4 flex w-full justify-center">
              <div class="pointer-events-auto mx-auto flex space-x-1 rounded-full border border-slate-150 bg-white px-4 py-0.5 shadow-lg dark:border-navy-700 dark:bg-navy-900">
                <button @click="activeTab = 'tabHome'" :class="activeTab === 'tabHome' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
                  <svg x-show="activeTab === 'tabHome'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewbox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                  <svg x-show="activeTab !== 'tabHome'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </button>
                <button @click="activeTab = 'tabProjects'" :class="activeTab === 'tabProjects' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
                  <svg x-show="activeTab === 'tabProjects'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewbox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  <svg x-show="activeTab !== 'tabProjects'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                </button>
                <button @click="activeTab = 'tabActivity'" :class="activeTab === 'tabActivity' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
                  <svg x-show="activeTab === 'tabActivity'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewbox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                  <svg x-show="activeTab !== 'tabActivity'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>