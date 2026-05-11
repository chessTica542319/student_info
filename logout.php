<?php
// Logout script - destroys session completely and prevents reuse
session_start();

// Clear all session variables
$_SESSION = array();

// Close the session (releases lock)
session_write_close();

// Destroy the session completely
session_destroy();

// Prevent session fixation - create new session with no data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent any recovery
session_regenerate_id(true);

// Clear session cookie forcibly
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
    $params['path'], $params['domain'],
    $params['secure'], $params['httponly']
);

// Destroy any remaining session data
session_unset();

// Final destroy
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>
