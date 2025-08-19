<?php
// backend/logout.php

// ALWAYS start session FIRST
session_start();

// Unset all session variables for BOTH member and admin
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiry in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the main login selection page (index.html)
// Adjust the path if index.html is elsewhere
header("Location: ../index.html?status=logged_out");
exit;
?>