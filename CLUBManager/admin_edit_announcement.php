<?php
session_start();
require 'Dashboard/config.php'; // Path relative to root

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// Determine Mode & Fetch Data
$edit_mode = false;
$announcement_id = null;
$announcement_data = ['Title' => '', 'Content' => '']; // Defaults
$page_title = 'Add New Announcement';
$error_message = '';
$success_message = '';

if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $announcement_id = (int)$_GET['id'];
    $page_title = 'Edit Announcement';

    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        $sql = "SELECT Title, Content FROM Announcements WHERE AnnouncementID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $announcement_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!($announcement_data = mysqli_fetch_assoc($result))) {
                 $error_message = "Announcement not found."; $edit_mode = false; $announcement_id = null; $page_title = 'Add New Announcement';
            }
            mysqli_stmt_close($stmt);
        } else { $error_message = "Error fetching announcement data."; error_log(mysqli_error($conn)); }
        mysqli_close($conn);
    } else { $error_message = "Database connection error."; }
}

// Check for status messages
if(isset($_GET['status']) && $_GET['status'] == 'success') { $success_message = "Announcement saved successfully!"; }
if(isset($_GET['error'])) { $error_message = htmlspecialchars(urldecode($_GET['error'])); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
     <!-- Navbar -->
     <nav class="bg-white shadow-sm"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/admin_dashboard.php" class="text-xl font-bold text-indigo-600">Admin Panel</a> <div> <a href="Dashboard/admin_dashboard.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="admin_manage_announcements.php" class="text-gray-600 hover:text-indigo-600 mr-4">Manage Announcements</a> <a href="backend/admin_logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-3xl mx-auto mt-8 mb-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $page_title; ?></h1>

        <!-- Messages -->
        <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 ..."> <?php echo $success_message; ?> </div> <?php endif; ?>
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 ..."> <?php echo $error_message; ?> </div> <?php endif; ?>

        <form action="backend/save_announcement.php" method="POST">
             <?php if ($edit_mode): ?> <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>"> <?php endif; ?>

             <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($announcement_data['Title']); ?>" required maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

             <div class="mb-6">
                 <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="10" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($announcement_data['Content']); ?></textarea>
             </div>

             <!-- Form Actions -->
            <div class="flex justify-end mt-8">
                <a href="admin_manage_announcements.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"> <i class="fas fa-save mr-2"></i> Save Announcement </button>
            </div>
        </form>
    </div>
</body>
</html>