<?php
// nurse/nurse_header.php
if (!isset($pageTitle)) $pageTitle = 'TB - Nurse Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/app.js" defer=""></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <script>
      localStorage.getItem("_x_darkMode_on") === "true" &&
        document.documentElement.classList.add("dark");
    </script>
</head>
<body x-data="" class="is-header-blur" x-bind="$store.global.documentBody">
    <div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
      <div class="app-preloader-inner relative inline-block size-48"></div>
    </div>
    <div id="root" class="min-h-100vh flex grow bg-slate-50 dark:bg-navy-900" x-cloak="">
      <div class="sidebar print:hidden">
        <div class="main-sidebar">
          <div class="flex h-full w-full flex-col items-center border-r border-slate-150 bg-white dark:border-navy-700 dark:bg-navy-800">
            <div class="flex pt-4">
              <a href="index.php">
                <img class="size-11 transition-transform duration-500 ease-in-out hover:rotate-[360deg]" src="../images/app-logo.png" alt="logo">
              </a>
            </div>
            <?php include 'sidenav.php'; ?>
            <div class="my-3 mx-4 h-px bg-slate-200 dark:bg-navy-500"></div>
            <?php include 'dashboardsider.php'; ?>
          </div>
        </div>
      </div>
      <?php include 'toprightsidenav.php'; ?>