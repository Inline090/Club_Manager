<?php
session_start();
require '../Dashboard/config.php'; // Path relative to this file in backend

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Can't use header redirect if potentially outputting errors, but exit is crucial
    exit('Access denied. Please login as admin.');
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Determine mode: Check if member_id is present (hidden field)
    $edit_mode = isset($_POST['member_id']) && filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $member_id = $edit_mode ? (int)$_POST['member_id'] : null;

    // --- Get and Sanitize Form Data ---
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); // Allow empty
    $membership_type = $_POST['membership_type'] ?? ''; // Get from dropdown
    $membership_expiry = trim($_POST['membership_expiry'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0; // Get admin status (0 or 1)
    $password_plain = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Basic Validation ---
    $errors = [];
    if (empty($name)) { $errors[] = "Name is required."; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Valid email is required."; }

    // Password validation
    $update_password = false;
    if (!empty($password_plain)) { // Only validate/update password if provided
        if ($password_plain !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($password_plain) < 6) { // Example minimum length
            $errors[] = "Password must be at least 6 characters.";
        } else {
            $update_password = true;
        }
    } elseif (!$edit_mode) { // Password is required when adding a new member
        $errors[] = "Password is required for new members.";
    }

    // Membership expiry validation (allow blank or valid date)
    if (!empty($membership_expiry) && !DateTime::createFromFormat('Y-m-d', $membership_expiry)) {
        $errors[] = "Invalid membership expiry date format (use YYYY-MM-DD).";
    } elseif (empty($membership_expiry)) {
         $membership_expiry = null; // Ensure it's NULL if left blank
    }

     // Admin status validation
     if ($is_admin !== 0 && $is_admin !== 1) {
         $errors[] = "Invalid admin status value.";
         $is_admin = 0; // Default to non-admin on error
     }

     // Validate membership type (should match dropdown options + Allow empty)
     $allowed_types = ['Monthly', 'Quarterly', 'Half Yearly', 'Yearly', 'Admin', 'Honorary', '']; // Include empty
     if (!in_array($membership_type, $allowed_types)) {
         $errors[] = "Invalid membership type selected.";
         $membership_type = ''; // Default to empty on error
     }


    // If errors, redirect back to form
    if (!empty($errors)) {
        $errorString = implode(' ', $errors);
        $redirect_url = $edit_mode ? "../admin_edit_member.php?id=" . $member_id : "../admin_edit_member.php";
        header("Location: " . $redirect_url . "&error=" . urlencode($errorString));
        exit;
    }

    // --- Database Operations ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
         $errorString = "Database connection error.";
         $redirect_url = $edit_mode ? "../admin_edit_member.php?id=" . $member_id : "../admin_edit_member.php";
         header("Location: " . $redirect_url . "&error=" . urlencode($errorString));
         exit;
    }

    // Check for duplicate email (on ADD or if email is CHANGED during EDIT)
    $email_check_sql = $edit_mode ? "SELECT MemberID FROM Member WHERE Email = ? AND MemberID != ?" : "SELECT MemberID FROM Member WHERE Email = ?";
    $stmt_email_check = mysqli_prepare($conn, $email_check_sql);
    if($stmt_email_check) {
        if ($edit_mode) { mysqli_stmt_bind_param($stmt_email_check, "si", $email, $member_id); }
        else { mysqli_stmt_bind_param($stmt_email_check, "s", $email); }
        mysqli_stmt_execute($stmt_email_check);
        mysqli_stmt_store_result($stmt_email_check);
        if (mysqli_stmt_num_rows($stmt_email_check) > 0) {
             $errors[] = "Email address is already registered to another member.";
        }
        mysqli_stmt_close($stmt_email_check);
    } else { $errors[] = "Error checking email uniqueness."; error_log(mysqli_error($conn));}

    // If duplicate email error, redirect back
    if (!empty($errors)) {
        $errorString = implode(' ', $errors);
        $redirect_url = $edit_mode ? "../admin_edit_member.php?id=" . $member_id : "../admin_edit_member.php";
        header("Location: " . $redirect_url . "&error=" . urlencode($errorString));
        mysqli_close($conn); exit;
    }


    // --- Prepare SQL for INSERT or UPDATE ---
    $sql = "";
    $types = "";
    $params = [];

    if ($edit_mode) {
        // UPDATE Mode
        if ($update_password) {
            $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
            $sql = "UPDATE Member SET Name=?, Email=?, Phone=?, MembershipType=?, MembershipExpiry=?, IsAdmin=?, Password=? WHERE MemberID = ?";
            $types = "sssssisi"; // s: string, i: integer
            $params = [$name, $email, $phone, $membership_type, $membership_expiry, $is_admin, $password_hashed, $member_id];
        } else {
            // Update without changing password
            $sql = "UPDATE Member SET Name=?, Email=?, Phone=?, MembershipType=?, MembershipExpiry=?, IsAdmin=? WHERE MemberID = ?";
            $types = "sssssii";
            $params = [$name, $email, $phone, $membership_type, $membership_expiry, $is_admin, $member_id];
        }
    } else {
        // INSERT Mode (Add New)
        $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT); // Hash the required password
        $sql = "INSERT INTO Member (Name, Email, Phone, MembershipType, MembershipExpiry, IsAdmin, Password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $types = "sssssis";
        $params = [$name, $email, $phone, $membership_type, $membership_expiry, $is_admin, $password_hashed];
    }

     // --- Execute Statement ---
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        // Dynamically bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params); // Use variadic operator (...)

        if (mysqli_stmt_execute($stmt)) {
            // Success! Redirect back to the manage page
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            $status = $edit_mode ? 'updated' : 'added';
            header("Location: ../admin_manage_members.php?status=" . $status);
            exit;
        } else {
            // Execution Error
            $error_msg = mysqli_stmt_error($stmt);
            error_log(($edit_mode ? "Update" : "Insert") . " Member Execute Error: " . $error_msg);
            $errorString = "Failed to save member data. Please try again.";
        }
        mysqli_stmt_close($stmt);
    } else {
        // Prepare Error
        $error_msg = mysqli_error($conn);
        error_log(($edit_mode ? "Update" : "Insert") . " Member Prepare Error: " . $error_msg);
        $errorString = "Error preparing save operation.";
    }

    mysqli_close($conn);

    // Redirect back to form on error
    $redirect_url = $edit_mode ? "../admin_edit_member.php?id=" . $member_id : "../admin_edit_member.php";
    header("Location: " . $redirect_url . "&error=" . urlencode($errorString));
    exit;


} else {
    // Not a POST request, redirect to manage members page
    header("Location: ../admin_manage_members.php");
    exit;
}
?>