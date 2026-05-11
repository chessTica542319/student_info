<?php
// Start fresh session - destroy any existing one first
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
session_start();

$_SESSION = array();

$error = "";
$success = "";

$host = "localhost";
$db_user = "root";
$db_pass = "";
$database = "SAMDB_sims";

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

if (isset($_POST['register'])) {

    $reg_username = trim($_POST['reg_username'] ?? '');
    $reg_password = $_POST['reg_password'] ?? '';

    if ($reg_username === '' || $reg_password === '') {
        $error = "<div class='popup error'>Error: Username and password are required.</div>";
    } else if (strlen($reg_username) < 3) {
        $error = "<div class='popup error'>Error: Username must be at least 3 characters.</div>";
    } else if (!validate_password_policy($reg_password)) {
        $error = "<div class='popup error'>Error: Password is weak.</div>";
    } else {
        $conn = new mysqli($host, $db_user, $db_pass, $database);
        $conn->set_charset('utf8mb4');

        $check = $conn->prepare("SELECT accID FROM userAccount WHERE username = ? LIMIT 1");
        $check->bind_param("s", $reg_username);
        $check->execute();
        $res = $check->get_result();

        if ($res && $res->num_rows > 0) {
            $error = "<div class='popup error'>Error: Username already exists.</div>";
            $check->close();
            $conn->close();
        } else {
            $hash = sha256_hash($reg_password);
            $ins = $conn->prepare("INSERT INTO userAccount (username, hashpassword) VALUES (?, ?)");
            $ins->bind_param("ss", $reg_username, $hash);

                if ($ins->execute()) {
                // After successful registration, go to login page so the user can sign in.
                header("Location: login.php");
                exit();
            } else {
                $error = "<div class='popup error'>Error: Registration failed.</div>";
            }


            $ins->close();
            $check->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            padding: 34px 28px 26px;
            text-align: center;
        }

        .login-header h2 {
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
        }

            form { padding: 28px; }
        
        a { cursor: pointer; }


        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            color: #4a5568;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            color: #2d3748;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #22c55e;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: #ffffff;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 800;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
        }

        button[type="submit"]:hover { transform: translateY(-1px); }

        .weak-msg {
            margin-top: 10px;
            text-align: center;
            color: #991b1b;
            font-weight: 800;
            font-size: 13px;
            display: none;
        }

        .form-footer {
            text-align: center;
            margin-top: 16px;
            color: #64748b;
            font-size: 14px;
        }

        .form-footer a {
            color: #22c55e;
            font-weight: 900;
            text-decoration: none;
        }

        .form-footer a:hover { text-decoration: underline; }

        .footer {
            text-align: center;
            padding: 16px;
            color: rgba(255,255,255,0.75);
            font-size: 13px;
        }

        .popup { padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-weight: 700; }
        .popup.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .popup.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Register to your account</h2>

            </div>

            <form method="POST" action="register.php" id="registerForm">
                <?php echo $success; ?>
                <?php echo $error; ?>

                <div class="form-group">
                    <label>username</label>
                    <input type="text" name="reg_username" placeholder="Enter username" required>
                </div>

                <div class="form-group" style="position: relative;">
                    <label>password</label>
                    <input type="password" name="reg_password" placeholder="Enter password" id="reg_password" required>
                    <span id="register_toggle" style="position: absolute; right: 16px; top: 40px; cursor: pointer; color:#a0aec0; user-select:none; font-weight:900;">👁️</span>
                </div>


                <div id="weakPasswordMsg" class="weak-msg">Weak password</div>

                <button type="submit" name="register">Register</button>

                <div class="form-footer">Already have an accout and <a href="login.php">Login</a></div>
            </form>
        </div>

        <div class="footer">&copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.</div>
    </div>

    <script>
        function matchesPolicy(pw) {
            const has6 = pw.length >= 6;
            const hasLower = /[a-z]/.test(pw);
            const hasUpper = /[A-Z]/.test(pw);
            const hasDigit = /[0-9]/.test(pw);
            const hasSpecial = /[^a-zA-Z0-9]/.test(pw);
            return has6 && hasLower && hasUpper && hasDigit && hasSpecial;
        }

        const regPasswordField = document.getElementById('reg_password');
        const weakMsg = document.getElementById('weakPasswordMsg');

        if (regPasswordField && weakMsg) {
            regPasswordField.addEventListener('input', function () {
                const pw = this.value || '';
                const isWeak = pw.length > 0 && !matchesPolicy(pw);
                weakMsg.style.display = isWeak ? 'block' : 'none';
            });
        }
        (function () {
            const toggle = document.getElementById('register_toggle');
            const input = document.getElementById('reg_password');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
            });
        })();
    </script>
</body>
</html>


