<?php
 $current_page = basename($_SERVER['PHP_SELF']);

// Safe user fetch — always use prepared statements
 $user_id = (int)$_SESSION['id'];
 $res = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $res->bind_param("i", $user_id);
 $res->execute();
 $user = $res->get_result()->fetch_assoc();

 $avatar_path = $user['image_paths'] ? '../../' . htmlspecialchars($user['image_paths']) : null;
 $initials = strtoupper(substr($user['name'] ?? 'PT', 0, 2));
?>

<div class="is-scrollbar-hidden flex grow flex-col space-y-4 overflow-y-auto pt-6">

  <!-- Dashboard -->
  <a href="index.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'index.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Dashboard'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
    </svg>
  </a>

  <!-- My Medications -->
  <a href="medications.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'medications.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'My Medications'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 31.934 31.934" stroke-width="1.5" stroke="currentColor" class="size-6 rotate-45">
      <path d="M15.966,0c-4.74,0-8.652,3.857-8.652,8.597v14.739c0,4.74,3.912,8.598,8.652,8.598c4.741,0,8.653-3.857,8.653-8.598V8.597 C24.618,3.857,20.707,0,15.966,0z M9.837,8.905c0-3.906,2.515-5.598,3.841-5.948c0.657-0.173,1.333,0.221,1.505,0.875 c0.171,0.651-0.186,1.317-0.832,1.497c-0.219,0.065-1.99,0.697-1.99,3.576v4.606c0,0.678-0.583,1.228-1.262,1.228 c-0.677,0-1.262-0.55-1.262-1.228V8.905z M22.094,23.336c0,3.386-2.742,6.141-6.128,6.141c-3.385,0-6.129-2.755-6.129-6.141 v-5.167c0-0.972,0.788-1.759,1.759-1.759h8.739c0.973,0,1.76,0.787,1.76,1.759V23.336z"></path>
    </svg>
  </a>

  <!-- My Lab Results -->
  <a href="results.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'results.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Lab Results'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
    </svg>
  </a>

  <!-- My Adherence -->
  <a href="adherence.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'adherence.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'My Adherence'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
    </svg>
  </a>

  <!-- Appointments -->
  <a href="appointments.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'appointments.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Appointments'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
    </svg>
  </a>

  <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>

  <!-- Report Side Effect -->
  <a href="report_side_effect.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'report_side_effect.php'
        ? 'bg-primary/10 text-primary dark:bg-navy-600 dark:text-accent-light'
        : 'hover:bg-primary/20 focus:bg-primary/20 active:bg-primary/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25' ?>"
     x-tooltip.placement.right="'Report Side Effect'">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
    </svg>
  </a>

</div>

<!-- Bottom Links -->
<div class="flex flex-col items-center space-y-3 py-3">

  <!-- Settings -->
  <a href="profile.php"
     class="flex size-11 items-center justify-center rounded-lg outline-none transition-colors duration-200
     <?= $current_page == 'profile.php'
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
      <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
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
            <div class="is-initial rounded-full bg-info text-xs+ uppercase text-white ring-1 ring-primary dark:ring-accent">
              <?= $initials ?>
            </div>
          </div>
          <?php endif; ?>
          <div>
            <a href="profile.php" class="text-base font-medium text-slate-700 hover:text-primary focus:text-primary dark:text-navy-100 dark:hover:text-accent-light dark:focus:text-accent-light">
              <?= htmlspecialchars($_SESSION['name']); ?>
            </a>
            <p class="text-xs text-slate-400 dark:text-navy-300">GxAlert Patient</p>
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
              <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">Profile</h2>
              <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">View your details</div>
            </div>
          </a>
          <a href="report_side_effect.php" class="group flex items-center space-x-3 py-2 px-4 tracking-wide outline-none transition-all hover:bg-slate-100 focus:bg-slate-100 dark:hover:bg-navy-600 dark:focus:bg-navy-600">
            <div class="flex size-8 items-center justify-center rounded-lg bg-error text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewbox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path>
              </svg>
            </div>
            <div>
              <h2 class="font-medium text-slate-700 transition-colors group-hover:text-primary group-focus:text-primary dark:text-navy-100 dark:group-hover:text-accent-light dark:group-focus:text-accent-light">Report Side Effect</h2>
              <div class="text-xs text-slate-400 line-clamp-1 dark:text-navy-300">Report a drug reaction</div>
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