<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/");
    exit;
}

// Optional: role check function
function requireRole($role) {
    if ($_SESSION['role'] !== $role) {
        header("Location: ../auth/?error=unauthorized");
        exit;
    }
}
?>