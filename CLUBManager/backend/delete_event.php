<?php
// --- Step 1: Start Session & Error Reporting ---
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Turn off in production

// --- Step 2: Include Database Configuration ---
// Ensure this path is correct relative to the 'backend' folder
require '../Dashboard/config.php';

// --- Step 3: Security Check - Admin Login ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login if not authenticated
    header("Location: ../admin_login.html?error=auth_required");
    exit;
}

// --- Step 4: Check Request Method and Input ---
// This script specifically handles POST requests with an 'event_id'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id'])) {

    // --- Step 5: Validate Input ID ---
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if ($event_id && $event_id > 0) {
        // --- Valid Event ID received ---

        // --- Step 6: Database Connection ---
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn) {
            error_log("Database Connection Failed in delete_event.php: " . mysqli_connect_error());
            header("Location: ../admin_manage_events.php?error=db_connection_failed");
            exit;
        }

        $status = ''; // To store the result status for redirection

        /*
        // --- Step 7 (Optional but Recommended): Handle Related Registrations ---
        // IF your 'MemberEvent' table's EventID foreign key DOES NOT have 'ON DELETE CASCADE',
        // UNCOMMENT this block to delete registrations manually first.
        // If it DOES have ON DELETE CASCADE, leave this commented out.
        $sql_delete_regs = "DELETE FROM MemberEvent WHERE EventID = ?";
        $stmt_regs = mysqli_prepare($conn, $sql_delete_regs);
        if ($stmt_regs) {
            mysqli_stmt_bind_param($stmt_regs, "i", $event_id);
            if (!mysqli_stmt_execute($stmt_regs)) {
                // Failed to delete registrations, log error and stop
                error_log("Failed to delete registrations for EventID {$event_id}: " . mysqli_stmt_error($stmt_regs));
                mysqli_stmt_close($stmt_regs);
                mysqli_close($conn);
                header("Location: ../admin_manage_events.php?error=delete_regs_failed");
                exit;
            }
            mysqli_stmt_close($stmt_regs);
            // Registrations deleted successfully, continue to delete event
        } else {
            // Failed to prepare the registration deletion statement
            error_log("SQL Prepare Error (Registrations) in delete_event.php: " . mysqli_error($conn));
            mysqli_close($conn);
            header("Location: ../admin_manage_events.php?error=delete_regs_prepare_failed");
            exit;
        }
        // End of optional registration deletion block
        */

        // --- Step 8: Prepare and Execute Event Deletion ---
        $sql = "DELETE FROM Event WHERE EventID = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $event_id);

            if (mysqli_stmt_execute($stmt)) {
                // Execution successful, check if any row was actually deleted
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $status = "deleted"; // Success!
                } else {
                    // Query executed, but no rows matched (event ID didn't exist)
                    $status = "not_found";
                }
            } else {
                // Execution failed (e.g., database error, constraints)
                $status = "delete_failed";
                error_log("SQL Execute Error (Event Delete) for EventID {$event_id}: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            // Prepare statement failed (e.g., SQL syntax error)
            $status = "prepare_failed";
            error_log("SQL Prepare Error (Event Delete) in delete_event.php: " . mysqli_error($conn));
        }

        // --- Step 9: Close Connection and Redirect ---
        mysqli_close($conn);
        // Redirect back to the list page with the result status
        header("Location: ../admin_manage_events.php?status=" . $status);
        exit; // IMPORTANT: Exit after redirect

    } else {
        // --- Step 10: Handle Invalid Event ID ---
        // The ID received via POST was not a positive integer
        header("Location: ../admin_manage_events.php?error=invalid_id");
        exit;
    }
} else {
    // --- Step 11: Handle Invalid Request ---
    // Script was accessed directly via GET, or POST data was missing 'event_id'
    header("Location: ../admin_manage_events.php?error=invalid_request");
    exit;
}
?>