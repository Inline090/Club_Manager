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

    $userId = (int)$_SESSION['user_id']; // Ensure integer
    $newName = trim($_POST['name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? ''); // Allow empty phone

    // --- Validation ---
    $errors = [];
    if (empty($newName)) {
        $errors[] = "Name cannot be empty.";
    }
    // Very basic phone format check (allows digits, spaces, +, -) - enhance if needed
    if (!empty($newPhone) && !preg_match('/^[\d\s+-]{5,20}$/', $newPhone)) {
         $errors[] = "Invalid phone number format (allow digits, spaces, +, -).";
    }

    if (!empty($errors)) {
        // Redirect back with errors
        $errorString = implode(' ', $errors); // Join errors
        header("Location: ../edit_profile.php?error=" . urlencode($errorString));
        exit;
    }

    // --- Update Database ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Update Profile DB Connection Failed: " . mysqli_connect_error());
        header("Location: ../edit_profile.php?error=" . urlencode("Database connection error."));
        exit;
    }

    // Prepare UPDATE statement - Only update Name and Phone
    $sql = "UPDATE Member SET Name = ?, Phone = ? WHERE MemberID = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $newName, $newPhone, $userId);

        if (mysqli_stmt_execute($stmt)) {
            // Success! Update session name if changed
            $_SESSION['user_name'] = $newName;
            $affected_rows = mysqli_stmt_affected_rows($stmt); // Check if anything actually changed

            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            // Redirect back with success message
            header("Location: ../edit_profile.php?status=success&changed=" . $affected_rows); // Optionally pass how many rows changed
            exit;
        } else {
            // Execution Error
            $error_msg = mysqli_stmt_error($stmt);
            error_log("Update Profile Execute Error for UserID $userId: $error_msg");
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: ../edit_profile.php?error=" . urlencode("Update failed. Please try again."));
            exit;
        }
    } else {
        // Prepare Error
        error_log("Update Profile Prepare Error: " . mysqli_error($conn));
        mysqli_close($conn);
        header("Location: ../edit_profile.php?error=" . urlencode("Could not prepare update."));
        exit;
    }

} else {
    // Not a POST request
    header("Location: ../edit_profile.php");
    exit;
}
?>