<?php
// backend/config.php

// --- Database Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Your DB password, if any
define('DB_NAME', 'clubmanager');

// --- General Settings ---
// Set default timezone (Important for date comparisons)
date_default_timezone_set('Asia/Kolkata'); // Example: India - Change to your timezone if needed
// Find timezone strings here: https://www.php.net/manual/en/timezones.php

// --- Error Reporting (Development vs Production) ---
// For development: shows all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For production: log errors, don't show them
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Set a path accessible by the web server

?>