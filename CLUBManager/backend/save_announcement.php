<?php
session_start();
require '../Dashboard/config.php'; // Path relative to backend

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied.');
}

// Check POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Determine mode
    $edit_mode = isset($_POST['announcement_id']) && filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
    $announcement_id = $edit_mode ? (int)$_POST['announcement_id'] : null;

    // Get data
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation
    $errors = [];
    if (empty($title)) { $errors[] = "Title is required."; }
    if (empty($content)) { $errors[] = "Content is required."; }

    // Redirect back if errors
    if (!empty($errors)) {
        $errorString = implode(' ', $errors);
        $redirect_url = $edit_mode ? "../admin_edit_announcement.php?id=" . $announcement_id : "../admin_edit_announcement.php";
        header("Location: " . $redirect_url . "&error=" . urlencode($errorString));
        exit;
    }

    // Database Operation
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) { /* Redirect db error */ exit; }

    $sql = "";
    $types = "";
    $params = [];

    if ($edit_mode) {
        // UPDATE
        $sql = "UPDATE Announcements SET Title = ?, Content = ? WHERE AnnouncementID = ?";
        $types = "ssi"; // string, string, integer
        $params = [$title, $content, $announcement_id];
    } else {
        // INSERT
        $sql = "INSERT INTO Announcements (Title, Content) VALUES (?, ?)";
        $types = "ss"; // string, string
        $params = [$title, $content];
    }

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $status = $edit_mode ? 'updated' : 'added';
        } else { $errorString = "Failed to save announcement."; error_log(mysqli_stmt_error($stmt)); }
        mysqli_stmt_close($stmt);
    } else { $errorString = "Error preparing save operation."; error_log(mysqli_error($conn)); }

    mysqli_close($conn);

    // Redirect based on outcome
    if (isset($status)) {
         header("Location: ../admin_manage_announcements.php?status=" . $status);
         exit;
    } else {
        $redirect_url = $edit_mode ? "../admin_edit_announcement.php?id=" . $announcement_id : "../admin_edit_announcement.php";
        header("Location: " . $redirect_url . "&error=" . urlencode($errorString ?? 'Unknown save error.'));
        exit;
    }

} else {
    header("Location: ../admin_manage_announcements.php"); exit;
}
?>