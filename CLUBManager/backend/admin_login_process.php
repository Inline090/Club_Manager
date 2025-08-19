<?php
// ALWAYS start session FIRST
session_start();

// --- Define Hardcoded Admin Credentials ---
// Make sure your admin login form field name matches the key used in $_POST below (e.g., 'username')
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin');

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data from the form - Use 'username' or 'email' based on your admin_login.html form field name
    // Ensure your input field in admin.html has name="username"
    $submitted_username = $_POST['username'] ?? '';
    $submitted_password = $_POST['password'] ?? '';

    // Basic Validation
    if (empty($submitted_username) || empty($submitted_password)) {
        // Redirect back to admin login page (adjust path if admin_login.html is not in root)
        header("Location: ../admin.html?error=missing_fields"); // Assuming admin.html is in root
        exit;
    }

    // --- Check against Hardcoded Credentials ---
    if ($submitted_username === ADMIN_USERNAME && $submitted_password === ADMIN_PASSWORD) {
        // --- Credentials CORRECT ---
        session_regenerate_id(true); // Prevent session fixation

        // Store admin-specific session variables
        $_SESSION['admin_id'] = 'ADMIN_HARDCODED'; // Use a special identifier
        $_SESSION['admin_name'] = 'Administrator'; // Generic name
        $_SESSION['admin_logged_in'] = true; // Flag for admin login

        // **** MODIFIED: Redirect to admin dashboard inside the Dashboard folder ****
        header("Location: ../Dashboard/admin_dashboard.php");
        exit; // Essential after header redirect

    } else {
        // --- Credentials INCORRECT ---
         // Redirect back to admin login page (adjust path if admin_login.html is not in root)
        header("Location: ../admin.html?error=invalid_credentials"); // Assuming admin.html is in root
        exit;
    }

} else {
    // Not a POST request, redirect to admin login page
    // Redirect back to admin login page (adjust path if admin_login.html is not in root)
    header("Location: ../admin.html"); // Assuming admin.html is in root
    exit;
}
?>