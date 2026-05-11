<?php
// db.php - CONNECT-ONLY (no database/table creation)

// 1. Server Credentials (Default XAMPP settings)
$host = "localhost";
$username = "root";
$password = "";
$database = "SAMDB_sims";

// 2. Configure Error Reporting for Prepared Statements
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 3. Connect to MySQL server
    $conn = new mysqli($host, $username, $password);

    // 4. Select the database (will fail if it doesn't exist)
    $conn->select_db($database);

    // 5. Set Character Encoding
    $conn->set_charset("utf8mb4");

    // Nothing else: no CREATE DATABASE / CREATE TABLE / ALTER TABLE here.
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
