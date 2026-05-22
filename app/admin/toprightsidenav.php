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
              <!-- Mobile Search Toggle -->
              <button @click="$store.global.isSearchbarActive = !$store.global.isSearchbarActive" class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25 sm:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5.5 text-slate-500 dark:text-navy-100" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </button>

            
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
// ─── Right Sidebar: Real Data ────────────────────────────────────────────

$today = date('Y-m-d');

// TAB 1: SMS stats — status is only 'sent' or 'failed'
$sms_summary = $conn->query("
    SELECT 
        COUNT(*)                        AS total_today,
        SUM(status = 'sent')            AS sms_success,
        SUM(status = 'failed')          AS sms_failed,
        SUM(delivery_report = 'delivered') AS sms_delivered
    FROM sms_logs 
    WHERE DATE(sent_at) = '$today'
")->fetch_assoc();

$sms_today    = (int)($sms_summary['total_today'] ?? 0);
$sms_failed   = (int)($sms_summary['sms_failed']  ?? 0);

// Cron proxy — any SMS sent or attempted in last 2 hours
$cron_active = (bool)$conn->query("
    SELECT 1 FROM sms_logs 
    WHERE sent_at >= NOW() - INTERVAL 2 HOUR LIMIT 1
")->fetch_column();

// SMS gateway — did any succeed today?
$gateway_ok = (bool)$conn->query("
    SELECT 1 FROM sms_logs 
    WHERE DATE(sent_at) = '$today' AND status = 'sent' LIMIT 1
")->fetch_column();

// DB always connected if we reach here
$db_connected = true;

// Totals
$total_users      = (int)$conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetch_column();
$total_facilities = (int)$conn->query("SELECT COUNT(*) FROM facilities WHERE is_active = 1")->fetch_column();

// TAB 2: Users by Role — exact enum values from schema
$role_counts_raw = $conn->query("
    SELECT role, COUNT(*) AS cnt 
    FROM users 
    WHERE is_active = 1 
    GROUP BY role
")->fetch_all(MYSQLI_ASSOC);

$role_counts = [];
foreach ($role_counts_raw as $r) {
    $role_counts[$r['role']] = (int)$r['cnt'];
}

// Exact enum values: 'doctor','nurse','clinician','lab_personnel','data_officer','admin','patient'
$role_display = [
    'patient'       => ['label' => 'Patients',      'color' => 'bg-info/10 text-info'],
    'clinician'     => ['label' => 'Clinicians',     'color' => 'bg-primary/10 text-primary dark:text-accent-light'],
    'doctor'        => ['label' => 'Doctors',        'color' => 'bg-success/10 text-success'],
    'nurse'         => ['label' => 'Nurses',         'color' => 'bg-secondary/10 text-secondary dark:text-secondary-light'],
    'lab_personnel' => ['label' => 'Lab Personnel',  'color' => 'bg-warning/10 text-warning'],
    'data_officer'  => ['label' => 'Data Officers',  'color' => 'bg-error/10 text-error'],
    'admin'         => ['label' => 'Admins',         'color' => 'bg-slate-200 text-slate-700 dark:bg-navy-400 dark:text-navy-100'],
];

// TAB 3: Audit Trail — last 10 entries
// entity_type and entity_id are nullable — handle safely
$audit_recent = $conn->query("
    SELECT 
        al.action, 
        al.entity_type, 
        al.entity_id,
        al.table_name,
        al.created_at, 
        u.name AS user_name
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// action column stores INSERT/UPDATE/DELETE/LOGIN/LOGOUT — map to icons
$audit_icons = [
    'INSERT' => ['icon' => 'fa-plus',          'color' => 'text-primary dark:text-accent'],
    'UPDATE' => ['icon' => 'fa-pen',           'color' => 'text-info'],
    'DELETE' => ['icon' => 'fa-trash',         'color' => 'text-error'],
    'LOGIN'  => ['icon' => 'fa-sign-in-alt',   'color' => 'text-success'],
    'LOGOUT' => ['icon' => 'fa-sign-out-alt',  'color' => 'text-slate-400'],
    // lowercase fallbacks
    'create' => ['icon' => 'fa-plus',          'color' => 'text-primary dark:text-accent'],
    'update' => ['icon' => 'fa-pen',           'color' => 'text-info'],
    'delete' => ['icon' => 'fa-trash',         'color' => 'text-error'],
    'login'  => ['icon' => 'fa-sign-in-alt',   'color' => 'text-success'],
    'logout' => ['icon' => 'fa-sign-out-alt',  'color' => 'text-slate-400'],
];

// human_time_diff helper (add to admin_init.php if not already there)
if (!function_exists('human_time_diff')) {
    function human_time_diff(string $datetime): string {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'just now';
        if ($diff < 3600)   return floor($diff / 60) . ' min ago';
        if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
        return floor($diff / 86400) . 'd ago';
    }
}
?>
   <!-- Right Sidebar -->
<div x-show="$store.global.isRightSidebarExpanded" @keydown.window.escape="$store.global.isRightSidebarExpanded = false">
  <div class="fixed inset-0 z-[150] bg-slate-900/60 transition-opacity duration-200"
       @click="$store.global.isRightSidebarExpanded = false"
       x-show="$store.global.isRightSidebarExpanded"
       x-transition:enter="ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

  <div class="fixed right-0 top-0 z-[151] h-full w-full sm:w-80">
    <div x-data="{activeTab:'tabHome'}" class="relative flex h-full w-full transform-gpu flex-col bg-white transition-transform duration-200 dark:bg-navy-750"
         x-show="$store.global.isRightSidebarExpanded"
         x-transition:enter="ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

      <!-- Header -->
      <div class="flex items-center justify-between py-2 px-4">
        <p x-show="activeTab === 'tabHome'" class="flex shrink-0 items-center space-x-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
          <span class="text-xs">System Health</span>
        </p>
        <p x-show="activeTab === 'tabProjects'" class="flex shrink-0 items-center space-x-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
          <span class="text-xs">User Breakdown</span>
        </p>
        <p x-show="activeTab === 'tabActivity'" class="flex shrink-0 items-center space-x-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-xs">Audit Trail</span>
        </p>
        <button @click="$store.global.isRightSidebarExpanded=false" class="btn -mr-1 size-6 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- TAB 1: System Health -->
      <div x-show="activeTab === 'tabHome'"
           x-transition:enter="transition-all duration-500 easy-in-out"
           x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]"
           x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
           class="is-scrollbar-hidden overflow-y-auto overscroll-contain pt-1">

        <div class="mt-3 px-3">
          <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">System Status</h2>
          <div class="mt-3 space-y-2">

            <!-- Cron -->
            <div class="flex items-center justify-between rounded-lg p-3 <?= $cron_active ? 'bg-success/10 dark:bg-success/15' : 'bg-warning/10 dark:bg-warning/15' ?>">
              <div class="flex items-center space-x-3">
                <div class="size-2.5 rounded-full <?= $cron_active ? 'bg-success animate-pulse' : 'bg-warning' ?>"></div>
                <p class="text-sm text-slate-700 dark:text-navy-100">Cron Job</p>
              </div>
              <span class="text-xs <?= $cron_active ? 'text-success' : 'text-warning' ?>"><?= $cron_active ? 'Active' : 'Idle' ?></span>
            </div>

            <!-- DB -->
            <div class="flex items-center justify-between rounded-lg p-3 <?= $db_connected ? 'bg-success/10 dark:bg-success/15' : 'bg-error/10 dark:bg-error/15' ?>">
              <div class="flex items-center space-x-3">
                <div class="size-2.5 rounded-full <?= $db_connected ? 'bg-success' : 'bg-error' ?>"></div>
                <p class="text-sm text-slate-700 dark:text-navy-100">Database</p>
              </div>
              <span class="text-xs <?= $db_connected ? 'text-success' : 'text-error' ?>"><?= $db_connected ? 'Connected' : 'Error' ?></span>
            </div>

            <!-- SMS Gateway -->
            <div class="flex items-center justify-between rounded-lg p-3 <?= $gateway_ok ? 'bg-success/10 dark:bg-success/15' : 'bg-error/10 dark:bg-error/15' ?>">
              <div class="flex items-center space-x-3">
                <div class="size-2.5 rounded-full <?= $gateway_ok ? 'bg-success' : 'bg-error' ?>"></div>
                <p class="text-sm text-slate-700 dark:text-navy-100">SMS Gateway</p>
              </div>
              <span class="text-xs <?= $gateway_ok ? 'text-success' : 'text-error' ?>"><?= $gateway_ok ? 'Online' : 'No traffic' ?></span>
            </div>

          </div>
        </div>

        <!-- Stats Grid -->
        <div class="mt-4 grid grid-cols-2 gap-3 px-3">
          <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
            <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $sms_today ?></p>
            <p class="text-tiny+ text-slate-400">SMS Today</p>
          </div>
          <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
            <p class="text-xl font-semibold <?= $sms_failed > 0 ? 'text-error' : 'text-success' ?>"><?= $sms_failed ?></p>
            <p class="text-tiny+ text-slate-400">SMS Failed</p>
          </div>
          <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
            <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $total_users ?></p>
            <p class="text-tiny+ text-slate-400">Total Users</p>
          </div>
          <div class="rounded-lg bg-slate-100 p-3 dark:bg-navy-600">
            <p class="text-xl font-semibold text-slate-700 dark:text-navy-100"><?= $total_facilities ?></p>
            <p class="text-tiny+ text-slate-400">Facilities</p>
          </div>
        </div>

        <!-- Settings -->
        <div class="mt-4 px-3">
          <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">Settings</h2>
          <div class="mt-2 flex flex-col space-y-2">
            <label class="inline-flex items-center space-x-2">
              <input x-model="$store.global.isDarkModeEnabled" class="form-switch h-5 w-10 rounded-lg bg-slate-300 before:rounded-md before:bg-slate-50 checked:bg-slate-500 checked:before:bg-white dark:bg-navy-900 dark:before:bg-navy-300 dark:checked:bg-navy-400 dark:checked:before:bg-white" type="checkbox">
              <span>Dark Mode</span>
            </label>
            <label class="inline-flex items-center space-x-2">
              <input x-model="$store.global.isMonochromeModeEnabled" class="form-switch h-5 w-10 rounded-lg bg-slate-300 before:rounded-md before:bg-slate-50 checked:bg-slate-500 checked:before:bg-white dark:bg-navy-900 dark:before:bg-navy-300 dark:checked:bg-navy-400 dark:checked:before:bg-white" type="checkbox">
              <span>Monochrome Mode</span>
            </label>
          </div>
        </div>
        <div class="h-18"></div>
      </div>

      <!-- TAB 2: User Breakdown -->
      <div x-show="activeTab === 'tabProjects'"
           x-transition:enter="transition-all duration-500 easy-in-out"
           x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]"
           x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
           class="is-scrollbar-hidden overflow-y-auto overscroll-contain px-3 pt-1">
        <div class="mt-3">
          <h2 class="text-xs+ font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">Users by Role</h2>
          <div class="mt-3 space-y-1">
            <?php foreach ($role_display as $role_key => $meta):
              $count = $role_counts[$role_key] ?? 0;
              if ($count === 0) continue; // hide roles with no users
            ?>
            <div class="flex items-center justify-between p-2">
              <span class="text-sm text-slate-600 dark:text-navy-200"><?= $meta['label'] ?></span>
              <span class="badge rounded-full <?= $meta['color'] ?> px-2 text-xs"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty(array_filter($role_counts))): ?>
            <p class="py-4 text-center text-xs text-slate-400">No users found</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="h-18"></div>
      </div>

      <!-- TAB 3: Audit Trail -->
      <div x-show="activeTab === 'tabActivity'"
           x-transition:enter="transition-all duration-500 easy-in-out"
           x-transition:enter-start="opacity-0 [transform:translate3d(0,1rem,0)]"
           x-transition:enter-end="opacity-100 [transform:translate3d(0,0,0)]"
           class="is-scrollbar-hidden overflow-y-auto overscroll-contain pt-1">
        <ol class="timeline line-space mt-5 px-4 [--size:1.5rem]">
          <?php if (empty($audit_recent)): ?>
          <li class="px-4 py-8 text-center text-xs text-slate-400">No recent activity</li>
          <?php endif; ?>
          <?php foreach ($audit_recent as $entry):
            $action  = $entry['action'] ?? 'update';
            $icon    = $audit_icons[$action]['icon']  ?? 'fa-circle';
            $color   = $audit_icons[$action]['color'] ?? 'text-slate-400';
            $entity  = ucfirst(str_replace('_', ' ', $entry['entity_type'] ?? ''));
            $who     = htmlspecialchars($entry['user_name'] ?? 'System');
            $when    = $entry['created_at'];
            $ago     = human_time_diff($when); // helper below
          ?>
          <li class="timeline-item">
            <div class="timeline-item-point rounded-full border border-current bg-white <?= $color ?> dark:bg-navy-700">
              <i class="fa <?= $icon ?> text-tiny"></i>
            </div>
            <div class="timeline-item-content flex-1 pl-4">
              <div class="flex flex-col justify-between pb-2 sm:flex-row sm:pb-0">
                <p class="pb-2 font-medium leading-none text-slate-600 dark:text-navy-100 sm:pb-0"><?= ucfirst($action) ?><?= $entity ? " — $entity" : '' ?></p>
                <span class="text-xs text-slate-400 whitespace-nowrap"><?= $ago ?></span>
              </div>
              <p class="py-1 text-xs text-slate-500">By <?= $who ?></p>
            </div>
          </li>
          <?php endforeach; ?>
        </ol>
        <div class="h-18"></div>
      </div>

      <!-- Bottom Nav -->
      <div class="pointer-events-none absolute bottom-4 flex w-full justify-center">
        <div class="pointer-events-auto mx-auto flex space-x-1 rounded-full border border-slate-150 bg-white px-4 py-0.5 shadow-lg dark:border-navy-700 dark:bg-navy-900">
          <button @click="activeTab = 'tabHome'" :class="activeTab === 'tabHome' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
            <svg x-show="activeTab === 'tabHome'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <svg x-show="activeTab !== 'tabHome'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          </button>
          <button @click="activeTab = 'tabProjects'" :class="activeTab === 'tabProjects' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
            <svg x-show="activeTab === 'tabProjects'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <svg x-show="activeTab !== 'tabProjects'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
          </button>
          <button @click="activeTab = 'tabActivity'" :class="activeTab === 'tabActivity' && 'text-primary dark:text-accent'" class="btn h-9 rounded-full py-0 px-4 hover:bg-slate-300/20 hover:text-primary focus:bg-slate-300/20 focus:text-primary active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:hover:text-accent dark:focus:bg-navy-300/20 dark:focus:text-accent dark:active:bg-navy-300/25">
            <svg x-show="activeTab === 'tabActivity'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
            <svg x-show="activeTab !== 'tabActivity'" xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>