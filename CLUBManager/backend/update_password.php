<?php
session_start();
// Ensure user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("location: ../student.html?error=auth_required");
    exit;
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require '../Dashboard/config.php'; // DB config

    $userId = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // --- Basic Validation ---
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: ../change_password.php?error=missing_fields"); exit;
    }
    if ($newPassword !== $confirmPassword) {
         header("Location: ../change_password.php?error=mismatch"); exit;
    }
    // Optional: Add password complexity validation
    if (strlen($newPassword) < 8) { // Example minimum length
        header("Location: ../change_password.php?error=short_password"); exit;
    }


    // --- Update Password ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Change Password DB Connection Failed: " . mysqli_connect_error());
        header("Location: ../change_password.php?error=db_error"); exit;
    }

    // 1. Get current hashed password from DB
    $sql_get_hash = "SELECT Password FROM Member WHERE MemberID = ?";
    $stmt_get_hash = mysqli_prepare($conn, $sql_get_hash);
    $currentHash = null;
    if ($stmt_get_hash) {
        mysqli_stmt_bind_param($stmt_get_hash, "i", $userId);
        mysqli_stmt_execute($stmt_get_hash);
        mysqli_stmt_bind_result($stmt_get_hash, $currentHash);
        mysqli_stmt_fetch($stmt_get_hash);
        mysqli_stmt_close($stmt_get_hash);
    } else {
        error_log("Prepare failed get hash (change pw): " . mysqli_error($conn));
        mysqli_close($conn);
        header("Location: ../change_password.php?error=db_error"); exit;
    }

    // 2. Verify submitted current password against the stored hash
    if ($currentHash === null || !password_verify($currentPassword, $currentHash)) {
        mysqli_close($conn);
        header("Location: ../change_password.php?error=current_incorrect"); exit;
    }

    // 3. Hash the new password
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($newHashedPassword === false) {
        error_log("Password Hashing Failed (change pw) for UserID: " . $userId);
        mysqli_close($conn);
        header("Location: ../change_password.php?error=hashing_failed"); exit;
    }

    // 4. Update the password in the database
    $sql_update = "UPDATE Member SET Password = ? WHERE MemberID = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "si", $newHashedPassword, $userId);
        if (mysqli_stmt_execute($stmt_update)) {
            // Success!
            mysqli_stmt_close($stmt_update);
            mysqli_close($conn);
            header("Location: ../change_password.php?status=success"); exit;
        } else {
            // Execution Error
            error_log("Update Password Execute Error for UserID $userId: " . mysqli_stmt_error($stmt_update));
            mysqli_stmt_close($stmt_update);
            mysqli_close($conn);
            header("Location: ../change_password.php?error=update_failed"); exit;
        }
    } else {
        // Prepare Error
        error_log("Update Password Prepare Error: " . mysqli_error($conn));
        mysqli_close($conn);
        header("Location: ../change_password.php?error=prepare_failed"); exit;
    }

} else {
    // Not a POST request
    header("Location: ../change_password.php"); exit;
}
?>