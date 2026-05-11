<?php
// Protect this page - require authentication
include 'auth.php';

include 'db.php';

$message = "";

// 1. Fetch existing data to populate the form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM student WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
$row = $result->fetch_assoc();
    } else {
        die("<div style='color:#ff073a; font-family:monospace; text-align:center; margin-top:50px;'>Error: Subject not found in matrix.</div>");
    }
    $stmt->close();
}

// 2. Handle the form submission to update data
if (isset($_POST['update'])) {
    $id      = $_POST['id']; // Hidden field from the form
    // Convert to title case
    $fname   = ucwords(strtolower($_POST['fname']));
    $mname   = ucwords(strtolower($_POST['mname']));
    $lname   = ucwords(strtolower($_POST['lname']));
    $gender  = strtoupper($_POST['gender']);
    $bday    = $_POST['bday'];
    $address = ucwords(strtolower($_POST['address']));
$grade = (float)$_POST['grade'];
    $grade = round($grade, 2);
    $course  = $_POST['course'];
    
    // Validate grade
    if ($grade < 0) $grade = 0;
    if ($grade > 100) $grade = 100;

    // New course logic: letters/spaces only, format before save
    $course = trim($course);
    if (!preg_match('/^[a-zA-Z\s]+$/', $course) || strlen($course) < 2 || strlen($course) > 50) {
        $message = "<div class='popup error'>Error: Course 2-50 letters/spaces only!</div>";
    } else {
        // Format course: preserve logic + new: multi-word always CAPS first + camelCase rest
        $words = preg_split('/\s+/', $course);
        if (count($words) === 1) {
            $course = strtoupper($course); // Single word ALL CAPS: "bsit" → "BSIT"
        } else {
            $words[0] = strtoupper($words[0]); // First word always CAPS
            for ($i = 1; $i < count($words); $i++) {
                $words[$i] = ucfirst(strtolower($words[$i])); // Rest camelCase
            }
            $course = implode(' ', $words); // e.g. "bs computer" → "BS Computer", "Bachelor science" → "BACHELOR Science"
        }
$sql = "UPDATE student SET f_name=?, m_name=?, l_name=?, gender=?, birthday=?, address=?, gwa=?, course=? WHERE id=?";

        try {
            $stmt = $conn->prepare($sql);
            // "ssssssdsi" means 6 strings, 1 double, 1 string, and 1 integer
            $stmt->bind_param("ssssssdsi", $fname, $mname, $lname, $gender, $bday, $address, $grade, $course, $id);
            
            if ($stmt->execute()) {
                header("Location: index.php");
                exit();
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "<div class='popup error'>System Error: " . $e->getMessage() . "</div>";
        }
    }
     // end else for course validation
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://kit.fontawesome.com/3131841332.js" crossorigin="anonymous"></script>
        <title>Update Student - SaaS Dashboard</title>
<style>
        /* SaaS Dashboard Theme */
        * { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f0fdf4; font-family: 'Segoe UI', Tahoma, sans-serif; min-height: 100vh; }
        
        .sidebar { width: 260px; background: linear-gradient(180deg, #22c55e 0%, #15803d 100%); min-height: 100vh; position: fixed; padding: 30px 20px; left: 0; top: 0; }
        .logo { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 40px; text-align: center; }
        .nav-link { display: block; color: #94a3b8; padding: 14px 20px; text-decoration: none; border-radius: 10px; margin-bottom: 8px; font-weight: 500; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: #ffffff; }
        
        .main-content { margin-left: 260px; padding: 30px; }
        .header { background: #ffffff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h2 { color: #1e293b; font-size: 24px; font-weight: 600; }
        
        .form-card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; max-width: 600px; }
        .form-card-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        .form-card-header h3 { color: #1e293b; font-size: 18px; font-weight: 600; }
        
        form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #4a5568; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        
        input[type="text"], input[type="date"], select { width: 100%; padding: 14px 16px; box-sizing: border-box; background-color: #f7fafc; border: 2px solid #e2e8f0; color: #2d3748; font-size: 15px; border-radius: 8px; transition: all 0.3s ease; }
        input[type="text"]:focus, input[type="date"]:focus, select:focus { outline: none; border-color: #667eea; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); }
        
        button[type="submit"] { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 14px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5); }
        
        .btn-cancel { display: inline-block; background: #ef4444; color: #ffffff; padding: 14px 24px; text-decoration: none; border-radius: 8px; margin-left: 10px; font-weight: 600; transition: all 0.3s ease; }
        .btn-cancel:hover { background: #dc2626; }
        
.popup.error { background-color: #fed7d7; color: #c53030; border: 1px solid #fc8181; padding: 12px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 600; }
        
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; margin-top: 30px; }
        
        input[type="number"] { width: 100%; padding: 14px 16px; box-sizing: border-box; background-color: #f7fafc; border: 2px solid #e2e8f0; color: #2d3748; font-size: 15px; border-radius: 8px; transition: all 0.3s ease; }
        
        .grade-error { background-color: #fef3cf; color: #b45309; border: 1px solid #fcd34d; padding: 12px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 600; display: none; }
    </style>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            var gwaInput = document.getElementById('grade');
var gwa = parseFloat(gwaInput.value);
            var errorDiv = document.getElementById('gradeError');
            
            if (gwa < 0 || gwa > 100 || isNaN(gwa)) {
                e.preventDefault();
                gwaInput.style.borderColor = '#ef4444';
                gwaInput.style.backgroundColor = '#fef2f2';
                errorDiv.textContent = 'Error: GWA must be between 0 and 100!';
                errorDiv.style.display = 'block';
                gwaInput.value = '';
                gwaInput.focus();
                setTimeout(function() {
                    errorDiv.style.display = 'none';
                    gwaInput.style.borderColor = '#e2e8f0';
                    gwaInput.style.backgroundColor = '#f7fafc';
                }, 2000);
            }
        });
    </script>
</head>
<body>

<!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Student<br>Management</div>
        <a href="index.php" class="nav-link">Dashboard</a>
        <a href="add_student.php" class="nav-link">Add Student</a>
        <a href="honor_students.php" class="nav-link">Honor Students</a>
        <a href="fail_students.php" class="nav-link">Failed Students</a>
        <a href="profile_settings.php" class="nav-link">Profile Settings</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Update Student</h2>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h3>Edit Student Information</h3>
            </div>
            <form method="POST" action="update_student.php">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                
                <div class="form-group">
                    <label>First Name</label>
<input type="text" required name="fname" value="<?php echo htmlspecialchars($row['f_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
<input type="text" required name="mname" value="<?php echo htmlspecialchars($row['m_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
<input type="text" required name="lname" value="<?php echo htmlspecialchars($row['l_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
<select required name="gender">
                        <option value="M" <?php echo ($row['gender'] == 'M') ? 'selected' : ''; ?>>M - Male</option>
                        <option value="F" <?php echo ($row['gender'] == 'F') ? 'selected' : ''; ?>>F - Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" required name="bday" value="<?php echo htmlspecialchars($row['birthday']); ?>">
                </div>
<div class="form-group">
                    <label>Address</label>
<input type="text" required name="address" value="<?php echo htmlspecialchars($row['address']); ?>">
                </div>
<div class="form-group">
                    <label>GWA (0-100)</label>
<input type="number" id="grade" required name="grade" value="<?php echo htmlspecialchars($row['gwa']); ?>" step="0.01" min="0" max="100">
                    <small style="color: #64748b;">GWA: 0 = Fail, 75 = Pass, 90+ = Honor</small>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <input type="text" required name="course" value="<?php echo htmlspecialchars($row['course']); ?>" placeholder="Enter course">
                </div>
                
                <div id="gradeError" class="grade-error"></div>
                <?php echo $message; ?>
                
                <button type="submit" name="update">Update Student</button>
                <a href="index.php" class="btn-cancel">Cancel</a>
            </form>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.
        </div>
    </div>

</body>
</html>
