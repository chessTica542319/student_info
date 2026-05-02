<?php
// Authentication helper - protects pages from unauthorized access
session_start();

// Check if session is valid and user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['created'])) {
    // Not authenticated - redirect to login
    header("Location: login.php");
    exit();
}

// Optional: Check session IP (uncomment if you want stricter security)
// if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
//     header("Location: login.php");
//     exit();
// }
?>
