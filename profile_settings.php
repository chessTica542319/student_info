<?php
include 'auth.php';
include 'db.php';

$success = "";
$error = "";

// Helpers
function sha256_hash($value) {
    return hash('sha256', $value);
}

function validate_password_policy($password) {
    if (strlen($password) < 6) return false;
    $hasLower = (bool)preg_match('/[a-z]/', $password);
    $hasUpper = (bool)preg_match('/[A-Z]/', $password);
    $hasDigit = (bool)preg_match('/[0-9]/', $password);
    $hasSpecial = (bool)preg_match('/[^a-zA-Z0-9]/', $password);
    return $hasLower && $hasUpper && $hasDigit && $hasSpecial;
}

function safe_trim($v) {
    return trim((string)$v);
}

$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$currentUsername = trim((string)$currentUsername);


// Handle Change Username
if (isset($_POST['change_username'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';

    if ($currentUsername === '') {
        $error = "<div class='popup error'>Session error: username missing.</div>";
    } else if ($currentPassword === '') {
        $error = "<div class='popup error'>Error: Current password is required.</div>";
    } else if ($newUsername === '' || strlen($newUsername) < 3) {
        $error = "<div class='popup error'>Error: Username must be at least 3 characters.</div>";
    } else {
        // Verify current password
        $checkPwd = $conn->prepare("SELECT hashpassword FROM userAccount WHERE username = ? LIMIT 1");
        $checkPwd->bind_param("s", $currentUsername);
        $checkPwd->execute();
        $resPwd = $checkPwd->get_result();

        if (!$resPwd || $resPwd->num_rows !== 1) {
            $error = "<div class='popup error'>Error: User not found.</div>";
        } else {
            $rowPwd = $resPwd->fetch_assoc();
            $stored = $rowPwd['hashpassword'];
            $incoming = sha256_hash($currentPassword);

            if (!hash_equals($stored, $incoming)) {
                $error = "<div class='popup error'>Error: Current password is incorrect.</div>";
            } else {
                // Check if new username already exists
                $check = $conn->prepare("SELECT accID FROM userAccount WHERE username = ? LIMIT 1");
                $check->bind_param("s", $newUsername);
                $check->execute();
                $res = $check->get_result();

                if ($res && $res->num_rows > 0 && $newUsername !== $currentUsername) {
                    $error = "<div class='popup error'>Error: Username already exists.</div>";
                } else {
                    // Update username
                    $upd = $conn->prepare("UPDATE userAccount SET username = ? WHERE username = ?");
                    $upd->bind_param("ss", $newUsername, $currentUsername);

                    if ($upd->execute()) {
                        $_SESSION['username'] = $newUsername;
                        $success = "<div class='popup success'>Username updated successfully.</div>";
                    } else {
                        $error = "<div class='popup error'>Error: Failed to update username.</div>";
                    }

                    $upd->close();
                }

                $check->close();
            }
        }

        $checkPwd->close();
    }
}

// Handle Change Password
if (isset($_POST['change_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';

    if ($currentUsername === '') {
        $error = "<div class='popup error'>Session error: username missing.</div>";
    } else if ($currentPassword === '') {
        $error = "<div class='popup error'>Error: Current password is required.</div>";
    } else if ($newPassword === '' || $confirmPassword === '') {
        $error = "<div class='popup error'>Error: Password fields are required.</div>";
    } else if ($newPassword !== $confirmPassword) {
        $error = "<div class='popup error'>Error: Password and confirm password do not match.</div>";
    } else if (!validate_password_policy($newPassword)) {
        $error = "<div class='popup error'>Error: Password is weak (must follow policy).</div>";
    } else {
        // Verify current password
        $checkPwd = $conn->prepare("SELECT hashpassword FROM userAccount WHERE username = ? LIMIT 1");
        $checkPwd->bind_param("s", $currentUsername);
        $checkPwd->execute();
        $resPwd = $checkPwd->get_result();

        if (!$resPwd || $resPwd->num_rows !== 1) {
            $error = "<div class='popup error'>Error: User not found.</div>";
        } else {
            $rowPwd = $resPwd->fetch_assoc();
            $stored = $rowPwd['hashpassword'];
            $incoming = sha256_hash($currentPassword);

            if (!hash_equals($stored, $incoming)) {
                $error = "<div class='popup error'>Error: Current password is incorrect.</div>";
            } else {
                $hash = sha256_hash($newPassword);
                $upd = $conn->prepare("UPDATE userAccount SET hashpassword = ? WHERE username = ?");
                $upd->bind_param("ss", $hash, $currentUsername);

                if ($upd->execute()) {
                    $success = "<div class='popup success'>Password updated successfully.</div>";
                } else {
                    $error = "<div class='popup error'>Error: Failed to update password.</div>";
                }

                $upd->close();
            }
        }

        $checkPwd->close();
    }
}

// Handle Account Deletion (2-step: verify then delete)
if (isset($_POST['delete_account'])) {
    $confirmUsername = safe_trim($_POST['confirm_username'] ?? '');
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $step = (int)($_POST['delete_step'] ?? 1);

    if ($currentUsername === '') {
        $error = "<div class='popup error'>Session error: username missing.</div>";
    } else if ($confirmUsername === '') {
        $error = "<div class='popup error'>Error: Confirm username is required.</div>";
    } else if ($confirmPassword === '') {
        $error = "<div class='popup error'>Error: Confirm password is required.</div>";
    } else if ($confirmUsername !== $currentUsername) {
        $error = "<div class='popup error'>Error: Confirm username does not match your current account.</div>";
    } else {
        // Verify password first
        $checkPwd = $conn->prepare("SELECT hashpassword FROM userAccount WHERE username = ? LIMIT 1");
        $checkPwd->bind_param("s", $confirmUsername);
        $checkPwd->execute();
        $resPwd = $checkPwd->get_result();

        if (!$resPwd || $resPwd->num_rows !== 1) {
            $error = "<div class='popup error'>Error: User not found.</div>";
        } else {
            $rowPwd = $resPwd->fetch_assoc();
            $stored = $rowPwd['hashpassword'];
            $incoming = sha256_hash($confirmPassword);

            if (!hash_equals($stored, $incoming)) {
                $error = "<div class='popup error'>Error: Password is incorrect.</div>";
            } else {
                if ($step === 1) {
                    // Verified: show confirmation step by setting POST value via hidden input (client will submit step=2)
                    // We can't do UI state via POST without session; use success message with a visible confirm form trigger.
                    // For simplicity, render the second form via GET-like hidden flag using $success.
                    $success = "<div class='popup success'>Verified. Submit again to confirm account deletion.</div>";
                } else {
                    // Step 2: delete
                    $del = $conn->prepare("DELETE FROM userAccount WHERE username = ?");
                    $del->bind_param("s", $confirmUsername);

                    if ($del->execute()) {
                        $del->close();
                        header("Location: logout.php");
                        exit();
                    } else {
                        $error = "<div class='popup error'>Error: Failed to delete account.</div>";
                    }

                    $del->close();
                }
            }
        }

        $checkPwd->close();
    }
}
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile Settings</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0fdf4; font-family: 'Segoe UI', Tahoma, sans-serif; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
            min-height: 100vh;
            position: fixed;
            padding: 30px 20px;
            left: 0;
            top: 0;
        }
        .logo { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 40px; text-align: center; }
        .nav-link { display: block; color: #94a3b8; padding: 14px 20px; text-decoration: none; border-radius: 10px; margin-bottom: 8px; font-weight: 500; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: #ffffff; }

        .main-content { margin-left: 260px; padding: 30px; }
        .header { background: #ffffff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h2 { color: #1e293b; font-size: 24px; font-weight: 600; }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
        }
        .form-card-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        .form-card-header h3 { color: #1e293b; font-size: 18px; font-weight: 600; }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            padding: 26px;
        }
        @media (max-width: 860px) {
            .grid { grid-template-columns: 1fr; }
        }

        form { padding: 26px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #4a5568; font-size: 14px; font-weight: 600; margin-bottom: 8px; }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            color: #2d3748;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5); }

        .btn-cancel {
            display: inline-block;
            background: #ef4444;
            color: #ffffff;
            padding: 14px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover { background: #dc2626; }

        .popup.error { background-color: #fed7d7; color: #c53030; border: 1px solid #fc8181; padding: 12px; margin: 0 26px 18px; border-radius: 8px; text-align: center; font-weight: 700; }
        .popup.success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 12px; margin: 0 26px 18px; border-radius: 8px; text-align: center; font-weight: 700; }

        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; margin-top: 30px; }

        .hint {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
            line-height: 1.3;
        }

        /* Password eye toggle */
        .password-field { position: relative; }
        .password-field input { padding-right: 48px; }
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            color: #a0aec0;
            font-weight: 900;
            font-size: 16px;
        }
        .toggle-password:focus { outline: none; }

        .password-strength {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 800;
            display: none;
            text-align: left;
            color: #991b1b;
        }

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Student<br>Management</div>
<a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="add_student.php" class="nav-link">Add Student</a>
        <a href="honor_students.php" class="nav-link">Honor Students</a>
        <a href="fail_students.php" class="nav-link">Failed Students</a>
        <a href="profile_settings.php" class="nav-link active">Profile Settings</a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Profile Settings</h2>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h3>Account: <?php echo htmlspecialchars($currentUsername); ?></h3>
            </div>

            <?php echo $success; ?>
            <?php echo $error; ?>

            <div class="grid">
                <div>
                    <form method="POST" action="profile_settings.php">
                        <input type="hidden" name="change_username" value="1">
                        <div class="form-group">
                            <label>Change Username</label>
                            <input type="text" name="new_username" placeholder="Enter new username" required>
                        </div>

                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="password-field">
                                <input type="password" name="current_password" placeholder="Enter current password" required id="pw-current-username">
                                <span class="toggle-password" data-pw-toggle="pw-current-username" aria-label="Show/Hide password">👁️</span>
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit">Update Username</button>
                        </div>
                        <div class="hint">Username must be at least 3 characters. Already taken usernames are not allowed.</div>
                    </form>
                </div>

                <div>
                    <form method="POST" action="profile_settings.php">
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="password-field">
                                <input type="password" name="current_password" placeholder="Enter current password" required id="pw-current-change">
                                <span class="toggle-password" data-pw-toggle="pw-current-change" aria-label="Show/Hide password">👁️</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Change Password</label>
                            <div class="password-field">
                                <input type="password" name="new_password" placeholder="Enter new password" required id="pw-new">
                                <span class="toggle-password" data-pw-toggle="pw-new" aria-label="Show/Hide password">👁️</span>
                            </div>
                            <div id="pw-new-strength" class="password-strength" aria-live="polite">Weak password</div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="password-field">
                                <input type="password" name="confirm_password" placeholder="Confirm new password" required id="pw-confirm-new">
                                <span class="toggle-password" data-pw-toggle="pw-confirm-new" aria-label="Show/Hide password">👁️</span>
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit">Update Password</button>
                        </div>
                        <div class="hint">Password policy: at least 6 chars, include uppercase, lowercase, number, and special character.</div>
                    </form>
                </div>

                <div style="grid-column: 1 / -1;">
                    <form id="delete_account_form" method="POST" action="profile_settings.php" style="border-top: 1px solid #e2e8f0; margin-top: 10px;">

                        <input type="hidden" name="delete_account" value="1">

                        <div class="form-group" style="margin-top: 20px;">
                            <label style="color:#c53030; font-size:15px;">Delete Account (requires confirmation)</label>
                            <div class="hint" style="margin-top: 4px; color:#64748b; font-size:13px;">
                                Step 1: Enter your username and password, then click <b>Delete Account</b> to verify your account.
                                <br>
                                Step 2: After you see the verification message, click <b>Delete Account</b> again to permanently delete it.
                            </div>

                        </div>

                        <input type="hidden" name="delete_step" value="1" id="delete_step_input">

                        <div class="grid" style="padding: 0; grid-template-columns: 1fr 1fr;">
                            <div class="form-group">
                                <label>Confirm Username</label>
                                <input type="text" name="confirm_username" placeholder="Type your username" required>
                            </div>

                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="password-field">
                                    <input type="password" name="confirm_password" placeholder="Type your password" required id="pw-delete">
                                    <span class="toggle-password" data-pw-toggle="pw-delete" aria-label="Show/Hide password">👁️</span>
                                </div>
                            </div>
                        </div>

                        <div class="actions" style="margin-top: 8px;">
                            <button type="button" id="delete_submit" onclick="return promptDeleteConfirm();" style="background:#ef4444; color:#ffffff; border:none; border-radius:8px; padding:14px 24px; font-weight:700; cursor:pointer;">
                                Delete Account
                            </button>

                        </div>


                        <div class="hint">
                            Warning: This will permanently remove your account and sign you out immediately.
                        </div>


                        <script>
                            function promptDeleteConfirm() {
                                // OK: continue to deletion (step 2) and submit the form.
                                // Cancel: restart verification (set step back to 1) and do not delete.
                                const stepInput = document.getElementById('delete_step_input');
                                const form = document.getElementById('delete_account_form');

                                // If user cancels, restart process.
                                const ok = confirm('Are you sure you want to delete your account permanently?\n\nOK = delete account\nCancel = stop and restart');
                                if (!ok) {
                                    if (stepInput) stepInput.value = '1';
                                    return false;
                                }

                                // Ensure step 2 when user confirms.
                                if (stepInput) stepInput.value = '2';

                                if (form) {
                                    form.submit();
                                    return false;
                                }

                                return true;
                            }

                            (function(){
                                const successText = <?php echo json_encode($success); ?>;
                                const btn = document.getElementById('delete_submit');
                                const stepInput = document.getElementById('delete_step_input');

                                // If server already verified (success message), auto set step=2 before submission.
                                if (successText && typeof successText === 'string' && successText.indexOf('Verified.') !== -1) {
                                    stepInput.value = '2';
                                    if (btn) btn.textContent = 'Confirm Deletion';
                                }
                            })();
                        </script>

                    </form>
                </div>

            </div>

        </div>

        <div class="footer">&copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.</div>
    </div>
<script>
    (function () {
        const toggles = document.querySelectorAll('[data-pw-toggle]');
        if (toggles && toggles.length > 0) {
            toggles.forEach(function (toggle) {
                const id = toggle.getAttribute('data-pw-toggle');
                if (!id) return;

                const input = document.getElementById(id);
                if (!input) return;

                toggle.addEventListener('click', function () {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    toggle.textContent = isPassword ? '🙈' : '👁️';
                });
            });
        }

        // Password strength indicator for Change Password form (front-end only)
        const pwNew = document.getElementById('pw-new');
        const strengthMsg = document.getElementById('pw-new-strength');

        if (pwNew && strengthMsg) {
            function countPolicyRequirements(pw) {
                const hasLower = /[a-z]/.test(pw);
                const hasUpper = /[A-Z]/.test(pw);
                const hasDigit = /[0-9]/.test(pw);
                const hasSpecial = /[^a-zA-Z0-9]/.test(pw);
                const has6 = pw.length >= 6;

                let count = 0;
                if (has6) count++;
                if (hasLower) count++;
                if (hasUpper) count++;
                if (hasDigit) count++;
                if (hasSpecial) count++;

                return { count, total: 5 };
            }

            function setStrengthLabel(pw) {
                if (!pw || pw.length === 0) {
                    strengthMsg.style.display = 'none';
                    return;
                }

                const { count, total } = countPolicyRequirements(pw);

                if (count === 1) {
                    strengthMsg.textContent = 'Weak password';
                    strengthMsg.style.display = 'block';
                    strengthMsg.style.color = '#991b1b';
                } else if (count === total) {
                    strengthMsg.textContent = 'Strong password';
                    strengthMsg.style.display = 'block';
                    strengthMsg.style.color = '#166534';
                } else {
                    strengthMsg.textContent = 'Moderate password';
                    strengthMsg.style.display = 'block';
                    strengthMsg.style.color = '#b45309';
                }
            }

            // initial state
            strengthMsg.style.display = 'none';
            setStrengthLabel(pwNew.value || '');

            pwNew.addEventListener('input', function () {
                setStrengthLabel(this.value || '');
            });
        }
    })();
</script>
</body>
</html>


