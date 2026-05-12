<?php
// Protect this page - require authentication
include 'auth.php';
include 'db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT id, f_name, m_name, l_name, gender, birthday, address, gwa, course FROM student";
if ($search) {
    $search_param = '%' . $search . '%';
    $search_words = array_filter(preg_split('/\s+/', $search), function($word) {
        return $word !== '';
    });

    $sql .= " WHERE (LOWER(f_name) LIKE LOWER(?) "
          . "OR LOWER(m_name) LIKE LOWER(?) "
          . "OR LOWER(l_name) LIKE LOWER(?) "
          . "OR LOWER(CONCAT_WS(' ', f_name, m_name)) LIKE LOWER(?) "
          . "OR LOWER(CONCAT_WS(' ', l_name, m_name)) LIKE LOWER(?) "
          . "OR LOWER(CONCAT_WS(' ', l_name, f_name)) LIKE LOWER(?) "
          . "OR LOWER(CONCAT_WS(' ', f_name, m_name, l_name)) LIKE LOWER(?) "
          . "OR LOWER(CONCAT_WS(' ', l_name, m_name, f_name)) LIKE LOWER(?) "
          . "OR LOWER(course) LIKE LOWER(?) "
          . "OR LOWER(address) LIKE LOWER(?) "
          . "OR CAST(gwa AS CHAR) LIKE ?)";

    if (count($search_words) > 1) {
        $token_clauses = [];
        foreach ($search_words as $word) {
            $token_clauses[] = "(LOWER(f_name) LIKE LOWER(?) OR LOWER(m_name) LIKE LOWER(?) OR LOWER(l_name) LIKE LOWER(?))";
        }
        $sql .= " OR (" . implode(' AND ', $token_clauses) . ")";
    }
}
$sql .= " ORDER BY id DESC";

// Get honor students (gwa >= 90)
$honor_sql = "SELECT id, f_name, m_name, l_name, gender, gwa, course FROM student WHERE gwa >= 90 ORDER BY gwa DESC";
$honor_result = $conn->query($honor_sql);

// Get failed students (gwa < 75)
$fail_sql = "SELECT id, f_name, m_name, l_name, gender, gwa FROM student WHERE gwa < 75 ORDER BY gwa ASC";
$fail_result = $conn->query($fail_sql);

try {
    if ($search) {
        $stmt = $conn->prepare($sql);

        $params = array_fill(0, 11, $search_param);
        if (count($search_words) > 1) {
            foreach ($search_words as $word) {
                $params[] = '%' . $word . '%';
                $params[] = '%' . $word . '%';
                $params[] = '%' . $word . '%';
            }
        }

        $types = str_repeat('s', count($params));
        $bindParams = array_merge([$types], $params);
        $bindRefs = [];
        foreach ($bindParams as $key => $value) {
            $bindRefs[$key] = &$bindParams[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindRefs);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    // Get gender counts for pie chart
    $male_count = 0;
    $female_count = 0;
    $pass_count = 0;
    $fail_count = 0;
    $total_students = $result->num_rows;
    
    if ($total_students > 0) {
        $result->data_seek(0);
        while($row = $result->fetch_assoc()) {
            if ($row['gender'] == 'M') {
                $male_count++;
            } elseif ($row['gender'] == 'F') {
                $female_count++;
            }
            if ($row['gwa'] >= 75) {
                $pass_count++;
            } else {
                $fail_count++;
            }
        }
        $result->data_seek(0);
    }
    
    $male_pct = $total_students > 0 ? round(($male_count / $total_students) * 100, 1) : 0;
    $female_pct = $total_students > 0 ? round(($female_count / $total_students) * 100, 1) : 0;
    $pass_pct = $total_students > 0 ? round(($pass_count / $total_students) * 100, 1) : 0;
    $fail_pct = $total_students > 0 ? round(($fail_count / $total_students) * 100, 1) : 0;
    
} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student List - Neon Yellow Edition</title>
    <style>
        /* SaaS Dashboard Theme */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0fdf4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; }

        /* Sidebar - Light Floral Green */
        .sidebar { width: 260px; background: linear-gradient(180deg, #22c55e 0%, #15803d 100%); min-height: 100vh; position: fixed; padding: 30px 20px; left: 0; top: 0; }
        .logo { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 40px; text-align: center; }
        .nav-link { display: block; color: #d1fae5; padding: 14px 20px; text-decoration: none; border-radius: 10px; margin-bottom: 8px; font-weight: 600; transition: all 0.3s ease; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: #ffffff; box-shadow: inset 0 1px 0 rgba(255,255,255,0.3); }

        /* Main Content */
        .main-content { margin-left: 260px; padding: 30px; }
        .header { background: #ffffff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { color: #1e293b; font-size: 24px; font-weight: 600; }
        .user-info { color: #64748b; font-size: 14px; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #64748b; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .stat-card .value { color: #1e293b; font-size: 28px; font-weight: 700; }

        /* Pie Charts */
        .chart-container { display: flex; align-items: center; gap: 20px; }
        .pie-chart, .gender-pie-chart { width: 100px; height: 100px; border-radius: 50%; position: relative; }
        .pie-chart { background: conic-gradient( #ef4444 0deg <?php echo $fail_pct * 3.6; ?>deg, #22c55e <?php echo $fail_pct * 3.6; ?>deg 360deg ); }
        .gender-pie-chart { background: conic-gradient( #ef4444 0deg <?php echo $male_pct * 3.6; ?>deg, #22c55e <?php echo $male_pct * 3.6; ?>deg 360deg ); }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; }
        .legend-dot.fail, .legend-dot.male { background: #ef4444; }
        .legend-dot.pass, .legend-dot.female { background: #22c55e; }

        /* Data Container */
        .data-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            /* Removed overflow:hidden so sticky scroll works smoothly */
        }
        
        .data-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        
        .data-card-header h3 { color: #1e293b; font-size: 18px; font-weight: 600; }

        /* Buttons */
        .btn-add { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-update, .btn-delete { display: inline-block; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; transition: all 0.3s ease; }
        .btn-update { color: #667eea; background: rgba(102, 126, 234, 0.1); margin-right: 6px; }
        .btn-update:hover { background: #667eea; color: #ffffff; }
        .btn-delete { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
        .btn-delete:hover { background: #ef4444; color: #ffffff; }

        /* --- THE PERFECT CSS STICKY SCROLL SOLUTION --- */
        .table-container {
            position: relative;
            width: 100%;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Hide the native scrollbar to prevent ugly duplicates */
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .table-scroll::-webkit-scrollbar {
            display: none; 
        }

        table { width: 100%; min-width: 1350px; border-collapse: collapse; table-layout: fixed; }
        table thead th { background: #f8fafc; box-shadow: 0 1px 0 #e2e8f0; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: top; word-break: break-word; }
        th { word-break: normal; color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        th:last-child, td:last-child { width: 180px; white-space: normal; text-align: left; }
        td:last-child .action-buttons { display: flex; flex-direction: column; gap: 6px; }
        td:nth-child(1), th:nth-child(1) { width: 60px; }
        td:nth-child(5), th:nth-child(5) { width: 90px; }
        td:nth-child(6), th:nth-child(6) { width: 110px; }
        td:nth-child(7), th:nth-child(7) { width: 280px; max-width: 280px; overflow-wrap: anywhere; }
        td:nth-child(9), th:nth-child(9) { width: 220px; max-width: 220px; overflow-wrap: anywhere; }
        td:nth-child(2), th:nth-child(2) { width: 120px; }
        td:nth-child(3), th:nth-child(3) { width: 120px; }
        td:nth-child(4), th:nth-child(4) { width: 120px; }
        td:nth-child(8), th:nth-child(8) { width: 70px; }
        tr:hover { background: #f8fafc; }
        .text-center { text-align: center; color: #64748b; font-style: italic; }

        /* The Custom Scrollbar that natively sticks to the bottom */
        .sticky-scrollbar-container {
            position: sticky;
            bottom: 0;
            overflow-x: auto;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            border-top: 1px solid #e2e8f0;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            display: none; /* Hidden until JS verifies table is wider than screen */
        }

        .sticky-scrollbar-container.active {
            display: block;
        }

        .sticky-dummy {
            height: 1px; /* Just needs height to force browser to draw scrollbar */
        }

        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; margin-top: 30px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Student<br>Management</div>
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="add_student.php" class="nav-link">Add Student</a>
        <a href="honor_students.php" class="nav-link">Honor Students</a>
        <a href="fail_students.php" class="nav-link">Failed Students</a>
        <a href="profile_settings.php" class="nav-link">Profile Settings</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Student Dashboard</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <form action="" method="get" style="display: flex; gap: 10px; align-items: center;">
                    <div style="position: relative; width: 300px;">
                        <input type="text" id="searchInput" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 12px 40px 12px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; width: 100%; box-sizing: border-box;">
                        <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
                    </div>

                    <button type="submit" id="searchBtn" style="padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Search</button>
                    <?php if ($search): ?>
                        <a href="dashboard.php" style="padding: 12px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Clear</a>
                    <?php endif; ?>
                </form>

                <div class="user-info">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | 
                    <a href="logout.php" style="color: #ef4444; text-decoration: none;" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value"><?php echo $result->num_rows; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pass/Fail Distribution</h3>
                <div class="chart-container">
                    <div class="pie-chart"></div>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <span class="legend-dot fail"></span>
                            <span>Fail: <?php echo $fail_count; ?></span>
                            <span class="legend-value">(<?php echo $fail_pct; ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot pass"></span>
                            <span>Pass: <?php echo $pass_count; ?></span>
                            <span class="legend-value">(<?php echo $pass_pct; ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <h3>Gender Distribution</h3>
                <div class="chart-container">
                    <div class="gender-pie-chart"></div>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <span class="legend-dot male"></span>
                            <span>Male: <?php echo $male_count; ?></span>
                            <span class="legend-value">(<?php echo $male_pct; ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot female"></span>
                            <span>Female: <?php echo $female_count; ?></span>
                            <span class="legend-value">(<?php echo $female_pct; ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h3>Student Records</h3>
                <a href="add_student.php" class="btn-add">+ Add Student</a>
            </div>

            <div class="table-container">
                <div class="table-scroll" id="mainScroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Gender</th>
                                <th>Birthday</th>
                                <th>Address</th>
                                <th>GWA</th>
                                <th>Course</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $gwa = isset($row['gwa']) ? (float)$row['gwa'] : 0;
                                    $status_class = "";
                                    if ($gwa < 75) {
                                        $status_class = "style='color: #ef4444; font-weight: 600;'";
                                    }
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['f_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['m_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['l_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['birthday']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                    echo "<td $status_class>" . number_format($gwa, 2) . ($gwa < 75 ? " (Fail)" : "") . "</td>";
                                    echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                                    echo "<td>
                                            <div class='action-buttons'>
                                                <a href='update_student.php?id=" . $row['id'] . "' class='btn-update'>Edit</a>
                                                <a href='delete_student.php?id=" . $row['id'] . "' class='btn-delete' onclick=\"return confirm('Are you sure?');\">Delete</a>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>No student records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="sticky-scrollbar-container" id="stickyScroll">
                    <div class="sticky-dummy" id="stickyDummy"></div>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.
        </div>

        <div style="position: fixed; right: 22px; bottom: 22px; z-index: 9999;">
            <button id="backToTopBtn" type="button" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border: none; border-radius: 999px; cursor: pointer; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: #ffffff; font-weight: 800; box-shadow: 0 8px 30px rgba(34, 197, 94, 0.25);">
                <i class="fas fa-arrow-up"></i>
                Back to top
            </button>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainScroll = document.getElementById('mainScroll');
            const stickyScroll = document.getElementById('stickyScroll');
            const stickyDummy = document.getElementById('stickyDummy');

            // Back to top (page scroll only, no reload)
            const backToTopBtn = document.getElementById('backToTopBtn');
            if (backToTopBtn) {
                backToTopBtn.addEventListener('click', function() {
                    // Smooth scroll to the top of the page
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }


            // Set dummy width to table width, toggle display if scrolling needed
            function syncWidths() {
                const scrollWidth = mainScroll.scrollWidth;
                const clientWidth = mainScroll.clientWidth;
                
                if (scrollWidth > clientWidth) {
                    stickyDummy.style.width = scrollWidth + 'px';
                    stickyScroll.classList.add('active');
                } else {
                    stickyScroll.classList.remove('active');
                }
            }

            // Sync scrolling positions instantly
            let isSyncingMain = false;
            let isSyncingSticky = false;

            mainScroll.addEventListener('scroll', function() {
                if (!isSyncingMain) {
                    isSyncingSticky = true;
                    stickyScroll.scrollLeft = mainScroll.scrollLeft;
                }
                isSyncingMain = false;
            });

            stickyScroll.addEventListener('scroll', function() {
                if (!isSyncingSticky) {
                    isSyncingMain = true;
                    mainScroll.scrollLeft = stickyScroll.scrollLeft;
                }
                isSyncingSticky = false;
            });

            // Re-calculate when the window resizes or table content changes
            window.addEventListener('resize', syncWidths);
            const resizeObserver = new ResizeObserver(syncWidths);
            resizeObserver.observe(mainScroll);

            // Run once to initialize
            syncWidths();
        });
    </script>
</body>
</html>