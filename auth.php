<?php
// Authentication helper - protects pages from unauthorized access
session_start();

// Check if session is valid and user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['created'])) {
    // Not authenticated - redirect to login (index.php)
    header("Location: index.php");
    exit();
}
?>
