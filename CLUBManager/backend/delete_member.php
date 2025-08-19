<?php
session_start();
// Use require for config as it's essential
require '../Dashboard/config.php'; // Assumes config.php is in the same backend folder

// Check if ADMIN is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login if not logged in
    header("Location: ../admin_login.html?error=auth_required");
    exit;
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if member_id is provided
    if (isset($_POST['member_id'])) {
        $member_id_to_delete = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);

        // Validate the ID: must be a positive integer
        if ($member_id_to_delete && $member_id_to_delete > 0) {

            $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
            if (!$conn) {
                 error_log("Delete Member DB Connection Failed: " . mysqli_connect_error());
                 // Redirect back to the manage page (one level up from backend)
                 header("Location: ../admin_manage_members.php?error=db_error");
                 exit;
            }

            // *** MODIFIED: Use 'Member' table ***
            $sql = "DELETE FROM Member WHERE MemberID = ?";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $member_id_to_delete);

                if (mysqli_stmt_execute($stmt)) {
                    // Check if any row was actually deleted
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $status = "deleted"; // Success
                    } else {
                        $status = "not_found"; // ID might not exist
                    }
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    // Redirect back to the manage page (one level up from backend)
                    header("Location: ../admin_manage_members.php?status=" . $status);
                    exit;
                } else {
                    // Deletion execution failed
                    $error_msg = mysqli_stmt_error($stmt);
                    error_log("Failed to delete member ID $member_id_to_delete: $error_msg");
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    header("Location: ../admin_manage_members.php?error=delete_failed");
                    exit;
                }
            } else {
                // Prepare statement failed
                 error_log("Prepare failed for delete member: " . mysqli_error($conn));
                 mysqli_close($conn);
                 header("Location: ../admin_manage_members.php?error=prepare_failed");
                 exit;
            }
        } else {
            // Invalid ID provided
             header("Location: ../admin_manage_members.php?error=invalid_delete_request");
             exit;
        }
    } else {
        // No member_id provided in POST data
         header("Location: ../admin_manage_members.php?error=no_id_provided");
         exit;
    }
} else {
     // Invalid request method (not POST)
     header("Location: ../admin_manage_members.php?error=invalid_request_method");
     exit;
}
?>