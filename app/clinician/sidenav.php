<?php
 $current_page = basename($_SERVER['PHP_SELF']);

 $user_id = (int)$_SESSION['id'];
 $res = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $res->bind_param("i", $user_id);
 $res->execute();
 $user = $res->get_result()->fetch_assoc();

 $avatar_path = $user['image_paths'] ? '../../' . htmlspecialchars($user['image_paths']) : null;
 $initials = strtoupper(substr($user['name'] ?? 'CL', 0, 2));
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

  <!-- Patients -->
  <a href="patients.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= in_array($current_page, ['patients.php', 'addpatient.php', 'viewpatient.php'])
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Patients'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
    </svg>
  </a>


  <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>


  <!-- Reports -->
  <a href="reviews.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'reports.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Regimen reviews'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
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
    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
  </svg>
</a>

</div>

<!-- Bottom Links -->
<div class="flex flex-col items-center space-y-3 py-3">

  <!-- Settings -->
  <a href="profile.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'profile.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Settings'">
    <svg class="size-7" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path fill-opacity="0.3" fill="currentColor" d="M2 12.947v-1.771c0-1.047.85-1.913 1.899-1.913 1.81 0 2.549-1.288 1.64-2.868a1.919 1.919 0 0 1 .699-2.607l1.729-.996c.79-.474 1.81-.192 2.279.603l.11.192c.9 1.58 2.379 1.58 3.288 0l.11-.192c.47-.795 1.49-1.077 2.279-.603l1.73.996a1.92 1.92 0 0 1 .699 2.607c-.91 1.58-.17 2.868 1.639 2.868 1.04 0 1.899.856 1.899 1.912v1.772c0 1.047-.85 1.912-1.9 1.912-1.808 0-2.548 1.288-1.638 2.869.52.915.21 2.083-.7 2.606l-1.729.997c-.79.473-1.81.191-2.279-.604l-.11-.191c-.9-1.58-2.379-1.58-3.288 0l-.11.19c-.47.796-1.49 1.078-2.279.605l-1.73-.997a1.919 1.919 0 0 1-.699-2.606c.91-1.58.17-2.869-1.639-2.869A1.911 1.911 0 0 1 2 12.947Z"></path>
      <path fill="currentColor" d="M11.995 15.332c1.794 0 3.248-1.464 3.248-3.27 0-1.807-1.454-3.272-3.248-3.272-1.794 0-3.248 1.465-3.248 3.271 0 1.807 1.454 3.271 3.248 3.271Z"></path>
    </svg>
  </a>

  <!-- Profile Popper -->
  <div x-data="usePopper({placement:'right-end',offset:12})" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
    <?php if ($avatar_path && file_exists($avatar_path)): ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
      <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
    </button>
    <?php else: ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary ring-1 ring-primary dark:text-accent-light dark:ring-accent">
        <?= $initials ?>
      </div>
      <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
    </button>
    <?php endif; ?>

    <div :class="isShowPopper && 'show'" class="popper-root fixed" x-ref="popperRoot">
      <div class="popper-box w-64 rounded-lg border border-slate-150 bg-white shadow-soft dark:border-navy-600 dark:bg-navy-700">
        <div class="flex items-center space-x-4 rounded-t-lg bg-slate-100 py-5 px-4 dark:bg-navy-800">
          <?php if ($avatar_path && file_exists($avatar_path)): ?>
          <div class="avatar size-14"><img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar"></div>
          <?php else: ?>
          <div class="avatar size-8 hover:z-10">
            <div class="is-initial rounded-full bg-primary/10 text-xs+ uppercase text-primary ring-1 ring-primary dark:text-accent-light dark:ring-accent"><?= $initials ?></div>
          </div>
          <?php endif; ?>
          <div>
            <a href="profile.php" class="text-base font-medium text-slate-700 hover:text-primary dark:text-navy-100 dark:hover:text-accent-light"><?= htmlspecialchars($_SESSION['name']); ?></a>
            <p class="text-xs text-slate-400 dark:text-navy-300">GxAlert Clinician</p>
          </div>
        </div>
        <div class="flex flex-col pt-2 pb-5">
          <a href="profile.php" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 dark:hover:bg-navy-600">
            <div class="flex size-8 items-center justify-center rounded-lg bg-warning text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
              <h2 class="font-medium text-slate-700 group-hover:text-primary dark:text-navy-100 dark:group-hover:text-accent-light">Profile</h2>
              <div class="text-xs text-slate-400 dark:text-navy-300">Account settings</div>
            </div>
          </a>
          <div class="mt-3 px-4">
            <a href="../auth/logout.php">
              <button class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewbox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Logout</span>
              </button>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>