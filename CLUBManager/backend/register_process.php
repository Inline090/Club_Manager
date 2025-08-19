<?php
// backend/register_process.php

require '../Dashboard/config.php'; // Use config file for DB credentials and timezone

// --- 1. Check if the form was submitted using POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 2. Establish Database Connection ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Registration DB Connection Failed: " . mysqli_connect_error());
        header("Location: ../register.php?error=db_connection"); exit;
    }

    // --- 3. Get data from the form ---
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password_plain = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $membership_type = $_POST['membership_type'] ?? '';
    // Expiry date is now CALCULATED, not from input

    // --- Basic Server-Side Validation ---
    $error = false; $error_type = '';
    // ** Check if membership type is selected **
    if (empty($name) || empty($email) || empty($phone) || empty($password_plain) || empty($confirm_password) || empty($membership_type)) {
        $error = true; $error_type = 'missing_fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true; $error_type = 'invalid_email';
    } elseif ($password_plain !== $confirm_password) {
        $error = true; $error_type = 'password_mismatch';
    }
    // Validate selected membership type
    $allowed_types = ['Monthly', 'Quarterly', 'Half Yearly', 'Yearly'];
    if (!$error && !in_array($membership_type, $allowed_types)) {
        $error = true; $error_type = 'invalid_type';
    }

    if ($error) {
        mysqli_close($conn);
        header("Location: ../register.php?error=" . $error_type); exit;
    }

    // --- 4. Calculate Membership Expiry Date ---
    $current_date = new DateTime(); // Gets current date and time based on server/PHP timezone
    $interval = null;

    switch ($membership_type) {
        case 'Monthly':
            $interval = new DateInterval('P1M'); // Period: 1 Month
            break;
        case 'Quarterly':
            $interval = new DateInterval('P3M'); // Period: 3 Months
            break;
        case 'Half Yearly':
            $interval = new DateInterval('P6M'); // Period: 6 Months
            break;
        case 'Yearly':
            $interval = new DateInterval('P1Y'); // Period: 1 Year
            break;
        default:
            // Should not happen due to validation, but good to handle
            mysqli_close($conn);
            error_log("Invalid membership type reached calculation: " . $membership_type);
            header("Location: ../register.php?error=invalid_type"); exit;
    }

    $current_date->add($interval); // Add the interval to the current date
    $membership_expiry_date = $current_date->format('Y-m-d'); // Format as YYYY-MM-DD for DATE column


    // --- 5. Securely Hash the Password ---
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
    if ($password_hashed === false) { /* ... hashing error handling ... */ exit; }

    // --- 6. Check if Email Already Exists in Member table ---
    $check_sql = "SELECT MemberID FROM Member WHERE Email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt); mysqli_close($conn);
            header("Location: ../register.php?error=email_exists"); exit;
        }
        mysqli_stmt_close($check_stmt);
    } else { /* ... email check error handling ... */ exit; }


    // --- 7. Prepare INSERT Statement (Now includes MembershipExpiry) ---
    $insert_sql = "INSERT INTO Member (Name, Email, Phone, Password, MembershipType, MembershipExpiry) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);

    if ($insert_stmt) {
        // --- 8. Bind parameters ---
        // *** MODIFIED: Type string is 'ssssss', added expiry date variable ***
        mysqli_stmt_bind_param($insert_stmt, "ssssss",
            $name,
            $email,
            $phone,
            $password_hashed,
            $membership_type,
            $membership_expiry_date // Use the calculated date string
        );

        // --- 9. Execute the statement ---
        if (mysqli_stmt_execute($insert_stmt)) {
            // --- 10. Success: Redirect to the STUDENT LOGIN page ---
            mysqli_stmt_close($insert_stmt);
            mysqli_close($conn);
            // Redirect to student login, maybe add a message indicating successful registration
            header("Location: ../student.html?registration=success");
            exit;
        } else {
            // Handle Execution Error
            $error_msg = mysqli_stmt_error($insert_stmt);
            error_log("SQL Execute Error (Register): Member '$email' - $error_msg");
            mysqli_stmt_close($insert_stmt); mysqli_close($conn);
            header("Location: ../register.php?error=registration_failed"); exit;
        }
    } else {
        // Handle Prepare Error
        $error_msg = mysqli_error($conn);
        error_log("SQL Prepare Error (Register): $error_msg");
        mysqli_close($conn);
        header("Location: ../register.php?error=prepare_failed"); exit;
    }
} else {
    // Not POST request
    header("Location: ../register.php"); exit;
}
?>