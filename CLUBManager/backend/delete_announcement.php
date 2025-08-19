<?php
session_start();
require '../Dashboard/config.php'; // Path relative to backend

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied.');
}

// Check POST request and ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement_id'])) {
    $announcement_id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

    if ($announcement_id && $announcement_id > 0) {
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn) { /* Redirect db error */ exit; }

        $sql = "DELETE FROM Announcements WHERE AnnouncementID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $announcement_id);
            if (mysqli_stmt_execute($stmt)) {
                $status = (mysqli_stmt_affected_rows($stmt) > 0) ? "deleted" : "not_found";
            } else { $status = "delete_failed"; error_log(mysqli_stmt_error($stmt)); }
            mysqli_stmt_close($stmt);
        } else { $status = "prepare_failed"; error_log(mysqli_error($conn)); }

        mysqli_close($conn);
        header("Location: ../admin_manage_announcements.php?status=" . $status);
        exit;

    } else { header("Location: ../admin_manage_announcements.php?error=invalid_id"); exit; }
} else { header("Location: ../admin_manage_announcements.php?error=invalid_request"); exit; }
?>