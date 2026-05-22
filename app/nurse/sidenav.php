<?php
 $current_page = basename($_SERVER['PHP_SELF']);

 $user_id = (int)$_SESSION['id'];
 $res = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $res->bind_param("i", $user_id);
 $res->execute();
 $user = $res->get_result()->fetch_assoc();

 $avatar_path = $user['image_paths'] ? '../../' . htmlspecialchars($user['image_paths']) : null;
 $initials = strtoupper(substr($user['name'] ?? 'NU', 0, 2));
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

  <!-- My Patients -->
  <a href="patients.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'patients.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'My Patients'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
    </svg>
  </a>

  <!-- Log Adherence (DOT) — primary action -->
  <a href="log_adherence.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= in_array($current_page, ['log_adherence.php', 'log_dose.php'])
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Log Adherence (DOT)'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
    </svg>
  </a>

  <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

  <!-- Adherence Summary -->
  <a href="adherence_summary.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'adherence_summary.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Adherence Summary'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
    </svg>
  </a>

  <!-- Side Effects Alert -->
  <a href="side_effects.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page === 'side_effects.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Side Effects'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
    </svg>
  </a>

</div>

<!-- Bottom Links -->
<div class="flex flex-col items-center space-y-3 py-3">
  <div x-data="usePopper({placement:'right-end',offset:12})" @click.outside="isShowPopper && (isShowPopper = false)" class="flex">
    <?php if ($avatar_path && file_exists($avatar_path)): ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <img class="rounded-full" src="<?= $avatar_path ?>" alt="avatar">
      <span class="absolute right-0 size-3.5 rounded-full border-2 border-white bg-success dark:border-navy-700"></span>
    </button>
    <?php else: ?>
    <button @click="isShowPopper = !isShowPopper" x-ref="popperRef" class="avatar size-12">
      <div class="is-initial rounded-full bg-success text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
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
            <div class="is-initial rounded-full bg-success text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent"><?= $initials ?></div>
          </div>
          <?php endif; ?>
          <div>
            <span class="text-base font-medium text-slate-700 dark:text-navy-100"><?= htmlspecialchars($_SESSION['name']); ?></span>
            <p class="text-xs text-slate-400 dark:text-navy-300">Nurse — <?= htmlspecialchars($stats['my_facility'] ?? '') ?></p>
          </div>
        </div>
        <div class="flex flex-col pt-2 pb-5">
          <a href="profile.php" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
            <div class="flex size-8 items-center justify-center rounded-lg bg-warning text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div>
              <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary dark:text-navy-100 dark:group-hover:text-accent-light">Profile</h2>
              <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">Your profile</div>
            </div>
          </a>
          <div class="mt-3 px-4">
            <a href="../auth/logout.php">
              <button class="btn h-9 w-full space-x-2 bg-primary text-white hover:bg-primary-focus dark:bg-accent dark:hover:bg-accent-focus">
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