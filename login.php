<?php
// Login page with hardcoded credentials
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

// Handle login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hardcoded credentials
    $valid_username = "admin";
    $valid_password = "admin123";

// Validate credentials
    if ($username === $valid_username && $password === $valid_password) {
        // Login successful - create session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['created'] = time(); // Session creation timestamp
        
        // Redirect to index
        header("Location: index.php");
        exit();
    } else {
        // Login failed
        $error = "<div class='popup error'>Access Denied: Invalid credentials</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Neon Edition</title>
<style>
        /* SaaS Dashboard Theme */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

body {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        /* SaaS Card Style */
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

.login-header {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            padding: 40px 40px 30px;
            text-align: center;
        }

        .login-header h2 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 1px;
            margin: 0;
        }

        .login-header p {
            color: rgba(255,255,255,0.8);
            margin-top: 8px;
            font-size: 14px;
        }

        /* SaaS Form Container */
        form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* SaaS Inputs */
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            box-sizing: border-box;
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            color: #2d3748;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #22c55e;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

/* SaaS Login Button */
        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            color: #ffffff;
            padding: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.5);
        }

        /* SaaS Error Message */
        .popup.error {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        /* Hint text */
        .hint {
            text-align: center;
            color: #a0aec0;
            font-size: 13px;
            margin-top: 20px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" required name="username" placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" required name="password" placeholder="Enter password">
                </div>
                
                <?php echo $error; ?>
                
                <button type="submit" name="login">Sign In</button>
                
                <div class="hint">Default: admin / admin123</div>
            </form>
        </div>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> @ RudaDev. All Right Reserved.
        </div>
    </div>

</body>
</html>
