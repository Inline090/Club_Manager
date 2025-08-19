<?php
session_start();
require '../Dashboard/config.php'; // Path relative to this file in backend

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied.');
}

// Check POST request and required fields
if ($_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST['event_id'])
    && isset($_POST['member_id'])
    && isset($_POST['attendance_status']) )
{
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $member_id = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    // Validate attendance status (should be 0 or 1)
    $attendance_status = filter_input(INPUT_POST, 'attendance_status', FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 1]]);

    // Ensure all inputs are valid integers (status can be 0)
    if ($event_id && $event_id > 0 && $member_id && $member_id > 0 && $attendance_status !== false) { // Check if filter passed

        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn) {
             $redirect_url = "../admin_view_registrations.php?event_id=" . $event_id . "&error=db_error";
             header("Location: " . $redirect_url);
             exit;
        }

        // Prepare UPDATE statement for MemberEvent table
        // Assumes MemberEvent has columns: MemberID, EventID, Attended
        $sql = "UPDATE MemberEvent SET Attended = ? WHERE EventID = ? AND MemberID = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iii", $attendance_status, $event_id, $member_id);

            if (mysqli_stmt_execute($stmt)) {
                // Success! Check if any row was actually updated
                $status = (mysqli_stmt_affected_rows($stmt) > 0) ? "attendance_updated" : "no_change"; // Indicate if row existed but value was same
            } else {
                // Execution failed
                $status = "attendance_failed";
                error_log("Mark Attendance Execute Error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            // Prepare failed
            $status = "prepare_failed";
            error_log("Mark Attendance Prepare Error: " . mysqli_error($conn));
        }

        mysqli_close($conn);
        // Redirect back to the view registrations page
        header("Location: ../admin_view_registrations.php?event_id=" . $event_id . "&status=" . $status);
        exit;

    } else { // Invalid input
         header("Location: ../admin_manage_events.php?error=invalid_attendance_input"); exit;
    }
} else { // Invalid request
    header("Location: ../admin_manage_events.php?error=invalid_request"); exit;
}
?>