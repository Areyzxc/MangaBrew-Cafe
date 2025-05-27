<?php
// logout.php — Ends the user session cleanly and redirects

session_start();

// 1. Unset all session variables
$_SESSION = [];

// 2. Destroy the session cookie (optional but tidy)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session
session_destroy();

// 4. Optional: Clear the "remember me" cookie too (if you want full logout)
setcookie("remember_username", "", time() - 3600, "/");

// 5. Redirect to login page (or home)
header("Location: index.php");
exit;
?>
