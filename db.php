<?php
// 1. Server Credentials (Default XAMPP settings)
$host = "localhost";
$username = "root";       
$password = "";           
$database = "SAMDB_sims";

// 2. Configure Error Reporting for Prepared Statements
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 3. Connect to the MySQL server (Without selecting a database yet)
    $conn = new mysqli($host, $username, $password);
    
    // 4. Create the database automatically if it doesn't exist
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$database`";
    $conn->query($sql_create_db);
    
    // 5. Select the database we just created/verified
    $conn->select_db($database);
    
    // 6. Set Character Encoding to support all standard text/symbols
    $conn->set_charset("utf8mb4"); 

    // 7. Create the `student` table automatically if it doesn't exist
    // It includes your 7 columns with 'id' as a unique, not null, auto-incrementing primary key.
$sql_create_table = "CREATE TABLE IF NOT EXISTS `student` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `f_name` VARCHAR(50) NOT NULL,
        `m_name` VARCHAR(50) NOT NULL,
        `l_name` VARCHAR(50) NOT NULL,
        `gender` CHAR(1) NOT NULL,
        `birthday` DATE NOT NULL,
        `address` VARCHAR(255) NOT NULL,
        `gwa` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `course` VARCHAR(255) NOT NULL DEFAULT ''
    )";
    $conn->query($sql_create_table);
    
// 8. Migrate grade column to gwa if it exists (for existing databases)
    $result = @$conn->query("DESCRIBE student");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Migrate grade column to gwa if needed
        if (in_array('grade', $columns) && !in_array('gwa', $columns)) {
            try {
                @$conn->query("ALTER TABLE student ADD COLUMN `gwa` DECIMAL(5,2) NOT NULL DEFAULT 0.00");
                @$conn->query("UPDATE student SET gwa = CAST(grade AS DECIMAL(5,2))");
                @$conn->query("ALTER TABLE student DROP COLUMN grade");
            } catch (Exception $e) {
                // Silent fail - table might be in use
            }
        }
    
// 9. Add missing columns to existing tables if they don't exist
        
        // Add each missing column individually with error handling
        if (!in_array('gender', $columns)) {
            try {
                $conn->query("ALTER TABLE student ADD COLUMN gender VARCHAR(10) NOT NULL DEFAULT ''");
            } catch (Exception $e) {
                // Column might already exist from previous attempt, continue
            }
        }
        if (!in_array('grade', $columns)) {
            try {
                $conn->query("ALTER TABLE student ADD COLUMN grade INT NOT NULL DEFAULT 0");
            } catch (Exception $e) {
                // Column might already exist from previous attempt, continue
            }
        }
        if (!in_array('course', $columns)) {
            try {
                $conn->query("ALTER TABLE student ADD COLUMN course VARCHAR(255) NOT NULL DEFAULT ''");
            } catch (Exception $e) {
                // Column might already exist from previous attempt, continue
            }
        }
    }

} catch (Exception $e) {
    // 9. Handle any errors during connection or creation
    die("Database setup failed: " . $e->getMessage());
}
?>