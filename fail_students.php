<?php
// Protect this page - require authentication
include 'auth.php';

include 'db.php';

// Get failed students (gwa < 75)
$fail_sql = "SELECT id, f_name, m_name, l_name, gender, gwa, course FROM student WHERE gwa < 75 ORDER BY gwa ASC";
$fail_result = $conn->query($fail_sql);

try {
    $total_fail = $fail_result ? $fail_result->num_rows : 0;
} catch (Exception $e) {
    $total_fail = 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Failed Students - SaaS Dashboard</title>
    <style>
        /* SaaS Dashboard Theme */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0fdf4; font-family: 'Segoe UI', Tahoma, sans-serif; min-height: 100vh; }
        
        .sidebar { width: 260px; background: linear-gradient(180deg, #dc2626 0%, #991b1b 100%); min-height: 100vh; position: fixed; padding: 30px 20px; left: 0; top: 0; }
        .logo { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 40px; text-align: center; }
        .nav-link { display: block; color: #fecaca; padding: 14px 20px; text-decoration: none; border-radius: 10px; margin-bottom: 8px; font-weight: 600; transition: all 0.3s ease; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }

        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: #ffffff; }
        
        .main-content { margin-left: 260px; padding: 30px; }
        .header { background: #ffffff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h2 { color: #1e293b; font-size: 24px; font-weight: 600; }
        
        .stat-card { background: #ef4444; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .stat-card h3 { color: rgba(255,255,255,0.8); font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .stat-card .value { color: #ffffff; font-size: 32px; font-weight: 700; }
        
        .data-card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .data-card-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        .data-card-header h3 { color: #1e293b; font-size: 18px; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px 24px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #fef2f2; color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        tr:hover { background: #fef2f2; }
        
        .grade-badge { background: #ef4444; color: #ffffff; padding: 6px 12px; border-radius: 20px; font-weight: 700; }
        .text-center { text-align: center; color: #64748b; font-style: italic; }
        
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; margin-top: 30px; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Student<br>Management</div>
        <a href="index.php" class="nav-link">Dashboard</a>
        <a href="add_student.php" class="nav-link">Add Student</a>
        <a href="honor_students.php" class="nav-link">Honor Students</a>
        <a href="fail_students.php" class="nav-link active">Failed Students</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Failed Students</h2>
        </div>

        <div class="stat-card">
<h3>Total Failed Students (GWA < 75)
            <div class="value"><?php echo $total_fail; ?></div>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h3>Academic Warning List</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Gender</th>
<th>GWA</th>
                        <th>Course</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($fail_result && $fail_result->num_rows > 0) {
                        while($row = $fail_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['f_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['m_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['l_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
echo "<td><span class='grade-badge'>" . number_format((float)$row['gwa'], 2) . "</span></td>";
                            echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No failed students found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.
        </div>
    </div>

</body>
</html>
