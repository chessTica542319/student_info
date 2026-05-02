<?php 
// Protect this page - require authentication
include 'auth.php';

// This will trigger db.php to run, ensuring the DB and table exist
include 'db.php';

$message = "";

if (isset($_POST['submit'])){
    // Retrieve data safely and convert to title case
    $fname   = ucwords(strtolower($_POST['fname']));
    $mname   = ucwords(strtolower($_POST['mname']));
    $lname   = ucwords(strtolower($_POST['lname']));
    $gender  = strtoupper($_POST['gender']);
    $bday    = $_POST['bday'];
    $address = ucwords(strtolower($_POST['address']));
    $grade   = intval($_POST['grade']);
    $course  = $_POST['course'];
    
    // Validate grade is between 0 and 100
    if ($grade < 0) $grade = 0;
    if ($grade > 100) $grade = 100;

    // Validate course is exactly 4 letters
    if (strlen($course) !== 4) {
        $message = "<div class='popup error'>Error: Course must be exactly 4 letters!</div>";
    } else {
        // Setup the prepared statement with placeholders
        // 8 columns: f_name, m_name, l_name, gender, birthday, address, grade, course
        $sql = "INSERT INTO student (f_name, m_name, l_name, gender, birthday, address, grade, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            // 7 strings (s) + 1 integer (i) = 8 parameters
            $bind_result = $stmt->bind_param("sssssssi", $fname, $mname, $lname, $gender, $bday, $address, $grade, $course);
            if (!$bind_result) {
                throw new Exception("Bind failed: " . $stmt->error);
            }
            
$execute_result = $stmt->execute();
            if (!$execute_result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Check if row was actually inserted
            if ($stmt->affected_rows > 0) {
                // Success! Close the statement and show success message
                $stmt->close();
                $message = "<div class='popup success'>Student added successfully! Redirecting...</div>";
                echo $message;
                // Small delay before redirect
                header("Refresh: 1; URL=index.php");
            } else {
                throw new Exception("No rows were inserted.");
            }

        } catch (Exception $e) {
            // If there's an error, stay on this page and show it in a neon red alert
            $message = "<div class='popup error'>System Error: " . $e->getMessage() . "</div>";
        }
    } // end else for course validation
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Student - Neon Edition</title>
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
        .popup.success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; padding: 12px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 600; }
        
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; margin-top: 30px; }
        
        input[type="number"] { width: 100%; padding: 14px 16px; box-sizing: border-box; background-color: #f7fafc; border: 2px solid #e2e8f0; color: #2d3748; font-size: 15px; border-radius: 8px; transition: all 0.3s ease; }
        
        .grade-error { background-color: #fef3cf; color: #b45309; border: 1px solid #fcd34d; padding: 12px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 600; display: none; }
    </style>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            var gradeInput = document.getElementById('grade');
            var grade = parseInt(gradeInput.value);
            var errorDiv = document.getElementById('gradeError');
            
            if (grade < 0 || grade > 100 || isNaN(grade)) {
                e.preventDefault();
                gradeInput.style.borderColor = '#ef4444';
                gradeInput.style.backgroundColor = '#fef2f2';
                errorDiv.textContent = 'Error: Grade must be between 0 and 100 only!';
                errorDiv.style.display = 'block';
                gradeInput.value = '';
                gradeInput.focus();
                setTimeout(function() {
                    errorDiv.style.display = 'none';
                    gradeInput.style.borderColor = '#e2e8f0';
                    gradeInput.style.backgroundColor = '#f7fafc';
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
        <a href="add_student.php" class="nav-link active">Add Student</a>
        <a href="honor_students.php" class="nav-link">Honor Students</a>
        <a href="fail_students.php" class="nav-link">Failed Students</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Add New Student</h2>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h3>Student Information</h3>
            </div>
            <form method="POST" action="add_student.php">
                <div class="form-group">
                    <label>First Name</label>
<input type="text" required name="fname">
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
<input type="text" required name="mname">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
<input type="text" required name="lname">
                </div>
                <div class="form-group">
                    <label>Gender</label>
<select required name="gender">
                        <option value="">Select Gender</option>
                        <option value="M">M - Male</option>
                        <option value="F">F - Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" required name="bday">
                </div>
<div class="form-group">
                    <label>Address</label>
<input type="text" required name="address">
                </div>
<div class="form-group">
                    <label>Grade (0-100)</label>
<input type="number" required name="grade" id="grade" min="0" max="100" value="0" placeholder="Enter grade 0-100">
                    <small style="color: #64748b;">Grade: 0 = Fail, 75 = Pass, 90+ = Honor</small>
                </div>
                <div class="form-group">
                    <label>Course</label>
<input type="text" required name="course" placeholder="Enter course">
                </div>
                
                <div id="gradeError" class="grade-error"></div>
                <?php echo $message; ?>
                
                <button type="submit" name="submit">Add Student</button>
                <a href="index.php" class="btn-cancel">Cancel</a>
            </form>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.
        </div>
    </div>

</body>
</html>
