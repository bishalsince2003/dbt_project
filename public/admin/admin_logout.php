<?php
session_start();

// Unset all admin session variables
unset($_SESSION['admin_logged']);

// Destroy full session for safety
session_unset();
session_destroy();

// Expire session cookie properly
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Redirect to login
header("Location: admin_login.php");
exit;
