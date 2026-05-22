<?php
 $current_page = basename($_SERVER['PHP_SELF']);

 $user_id = (int)$_SESSION['id'];
 $res = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $res->bind_param("i", $user_id);
 $res->execute();
 $user = $res->get_result()->fetch_assoc();

 $avatar_path = $user['image_paths'] ? '../../' . htmlspecialchars($user['image_paths']) : null;
 $initials = strtoupper(substr($user['name'] ?? 'AD', 0, 2));
?>

<div class="is-scrollbar-hidden flex grow flex-col space-y-4 overflow-y-auto pt-6">

  <!-- Dashboard -->
  <a href="index.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'index.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Dashboard'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
    </svg>
  </a>

  <!-- Users -->
  <a href="users.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= in_array($current_page, ['users.php', 'adduser.php', 'edituser.php'])
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Users'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
    </svg>
  </a>

  <!-- Facilities -->
  <a href="facilities.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= in_array($current_page, ['facilities.php', 'addfacility.php', 'editfacility.php'])
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Facilities'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21" />
    </svg>
  </a>

  <!-- Drug Catalog -->
  <a href="drugs.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= in_array($current_page, ['drugs.php', 'adddrug.php', 'editdrug.php'])
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Drug Catalog'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
    </svg>
  </a>

  <!-- SMS Logs -->
  <a href="sms_logs.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'sms_logs.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'SMS Logs'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
    </svg>
  </a>

  <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

  <!-- Audit Logs -->
  <a href="audit_logs.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'audit_logs.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Audit Logs'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
  </a>

  <!-- Settings -->
  <a href="settings.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'settings.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Settings'">
    <svg class="size-7" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path fill-opacity="0.3" fill="currentColor" d="M2 12.947v-1.771c0-1.047.85-1.913 1.899-1.913 1.81 0 2.549-1.288 1.64-2.868a1.919 1.919 0 0 1 .699-2.607l1.729-.996c.79-.474 1.81-.192 2.279.603l.11.192c.9 1.58 2.379 1.58 3.288 0l.11-.192c.47-.795 1.49-1.077 2.279-.603l1.73.996a1.92 1.92 0 0 1 .699 2.607c-.91 1.58-.17 2.868 1.639 2.868 1.04 0 1.899.856 1.899 1.912v1.772c0 1.047-.85 1.912-1.9 1.912-1.808 0-2.548 1.288-1.638 2.869.52.915.21 2.083-.7 2.606l-1.729.997c-.79.473-1.81.191-2.279-.604l-.11-.191c-.9-1.58-2.379-1.58-3.288 0l-.11.19c-.47.796-1.49 1.078-2.279.605l-1.73-.997a1.919 1.919 0 0 1-.699-2.606c.91-1.58.17-2.869-1.639-2.869A1.911 1.911 0 0 1 2 12.947Z"></path>
      <path fill="currentColor" d="M11.995 15.332c1.794 0 3.248-1.464 3.248-3.27 0-1.807-1.454-3.272-3.248-3.272-1.794 0-3.248 1.465-3.248 3.271 0 1.807 1.454 3.271 3.248 3.271Z"></path>
    </svg>
  </a>

</div>

<!-- Bottom Links -->
<div class="flex flex-col items-center space-y-3 py-3">
  <!-- Profile -->
  <div x-data="usePopper({placement:'right-end',offset:12})" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
    <?php if ($avatar_path && file_exists($avatar_path)): ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
      <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
    </button>
    <?php else: ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <div class="is-initial rounded-full bg-error text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
        <?= $initials ?>
      </div>
      <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
    </button>
    <?php endif; ?>

    <div :class="isShowPopper && 'show'" class="popper-root fixed" x-ref="popperRoot">
      <div class="popper-box w-64 rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-700">
        <div class="flex items-center space-x-4 rounded-t-lg bg-slate-100 py-5 px-4 dark:bg-navy-800">
          <?php if ($avatar_path && file_exists($avatar_path)): ?>
          <div class="avatar size-14">
            <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
          </div>
          <?php else: ?>
          <div class="avatar size-8 hover:z-10">
            <div class="is-initial rounded-full bg-error text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
              <?= $initials ?>
            </div>
          </div>
          <?php endif; ?>
          <div>
            <span class="text-base font-medium text-slate-700 dark:text-navy-100">
              <?= htmlspecialchars($_SESSION['name']); ?>
            </span>
            <p class="text-xs text-slate-400 dark:text-navy-300">System Administrator</p>
          </div>
        </div>
        <div class="flex flex-col pt-2 pb-5">
          <a href="settings.php" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
            <div class="flex size-8 items-center justify-center rounded-lg bg-warning text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div>
              <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary dark:text-navy-100 dark:group-hover:text-accent-light">Settings</h2>
              <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">System configuration</div>
            </div>
          </a>
          <div class="mt-3 px-4">
            <a href="../auth/logout.php">
              <button class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
              </button>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>