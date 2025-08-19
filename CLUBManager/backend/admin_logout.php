<?php
// backend/admin_logout.php

// ALWAYS start session FIRST
session_start();

// --- Unset only ADMIN specific session variables ---
// This prevents logging out a member if they happen to be logged in in the same browser session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']); // If you ever set this
unset($_SESSION['admin_logged_in']);

// --- Optionally, destroy session IF no other sessions (like member) need to persist ---
// If you want a complete logout regardless of member session, uncomment the destroy part.
/*
// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();
*/

// --- Redirect to the ADMIN login page ---
// Adjust path if admin_login.html is not in the root
header("Location: ../admin.html?status=logged_out"); // Use admin.html // Use admin_login.html
exit;
?>