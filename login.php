<?php
// Login page with default hardcoded credentials + DB registration/login
// Start fresh session - destroy any existing one first
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
session_start();

// Clear any existing session data completely
$_SESSION = array();

// If already logged in, redirect to index
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

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

// Handle login only
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($username) < 3 || $password === '') {
        $error = "<div class='popup error'>Access Denied: Invalid credentials</div>";
        exit();
    }

    // Verify against DB only (no hardcoded credentials)
    $conn = new mysqli($host, $db_user, $db_pass, $database);

    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare("SELECT hashpassword FROM userAccount WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stored = $row['hashpassword'];
        $incoming = sha256_hash($password);

        if (hash_equals($stored, $incoming)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['created'] = time();
            $stmt->close();
            $conn->close();
            header("Location: index.php");
            exit();
        }
    }

    $stmt->close();
    $conn->close();

    $error = "<div class='popup error'>Access Denied: Invalid credentials</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
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

        .login-header p {
            color: rgba(255,255,255,0.85);
            margin-top: 8px;
            font-size: 14px;
        }

        form { padding: 28px; }

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

        .hint {
            margin-top: 14px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
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

        .weak-msg {
            margin-top: 10px;
            text-align: center;
            color: #991b1b;
            font-weight: 800;
            font-size: 13px;
            display: none;
        }

        #login_toggle { transition: opacity 0.2s ease; }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Login to your account</h2>

                <p>Enter your username and password to continue.</p>
            </div>

            <?php echo $success; ?>
            <?php echo $error; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>username</label>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>

                <div class="form-group" style="position: relative;">
                    <label>password</label>
                    <input type="password" name="password" placeholder="Enter password" required id="login_password">
                    <span id="login_toggle" style="position: absolute; right: 16px; top: 40px; cursor: pointer; color:#a0aec0; user-select:none; font-weight:900;">👁️</span>
                </div>

                <div id="loginPasswordStrengthMsg" class="weak-msg">Weak password</div>

                <button type="submit" name="login">Login</button>
                <div class="hint"> </div>


                <div class="form-footer">Dont have an account then <a href="register.php">Register</a></div>
            </form>
        </div>

        <div class="footer">&copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.</div>
    </div>
    <script>
        (function () {
            const toggle = document.getElementById('login_toggle');
            const input = document.getElementById('login_password');
            const strengthMsg = document.getElementById('loginPasswordStrengthMsg');

            if (!input) return;

            function countPolicyRequirements(pw) {
                const has6 = pw.length >= 6;
                const hasLower = /[a-z]/.test(pw);
                const hasUpper = /[A-Z]/.test(pw);
                const hasDigit = /[0-9]/.test(pw);
                const hasSpecial = /[^a-zA-Z0-9]/.test(pw);

                let count = 0;
                if (has6) count++;
                if (hasLower) count++;
                if (hasUpper) count++;
                if (hasDigit) count++;
                if (hasSpecial) count++;
                return { count, total: 5 };
            }

            function setStrengthLabel(pw) {
                if (!strengthMsg) return;

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

            // eye toggle
            if (toggle) {
                toggle.addEventListener('click', function () {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                });
            }

            // strength indicator
            input.addEventListener('input', function () {
                setStrengthLabel(this.value || '');
            });
        })();
    </script>
</body>
</html>


