<?php
session_start();
require 'Dashboard/config.php'; // Path relative to root

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// Database Connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Fetch Announcements
$announcements = [];
$sql = "SELECT AnnouncementID, Title, Content, PostDate FROM Announcements ORDER BY PostDate DESC";
$result = mysqli_query($conn, $sql);
$fetch_error = '';
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Error fetching announcements: " . mysqli_error($conn));
    $fetch_error = "Could not retrieve announcement list.";
}
mysqli_close($conn);

// Get Status Messages
$status_message = ''; $status_type = '';
if (isset($_GET['status'])) {
    if($_GET['status'] == 'added') { $status_message = 'Announcement added successfully.'; $status_type = 'success'; }
    if($_GET['status'] == 'updated') { $status_message = 'Announcement updated successfully.'; $status_type = 'success'; }
    if($_GET['status'] == 'deleted') { $status_message = 'Announcement deleted successfully.'; $status_type = 'success'; }
    if($_GET['status'] == 'not_found') { $status_message = 'Announcement not found.'; $status_type = 'error'; }
    if($_GET['status'] == 'delete_failed') { $status_message = 'Failed to delete announcement.'; $status_type = 'error'; }
}
if (isset($_GET['error'])) { $status_message = htmlspecialchars(urldecode($_GET['error'])); $status_type = 'error';}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; } /* Scrollbar styles optional */</style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p> </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <a href="Dashboard/admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-gauge ..."></i> Dashboard</a>
                 <a href="admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-users ..."></i> Manage Members</a>
                 <a href="admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-calendar-check ..."></i> Manage Events</a>
                 <a href="admin_manage_announcements.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-bullhorn w-6 h-6 mr-3 text-indigo-500"></i> Manage Announcements</a> <!-- Highlighted -->
                 <!-- Other Links -->
            </nav>
             <div class="p-4 border-t border-gray-200 mt-auto"> <a href="backend/admin_logout.php" class="flex items-center text-sm ..."> Logout </a> </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
             <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Announcements</h2>
                <a href="admin_edit_announcement.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-sm"> <i class="fas fa-plus mr-2"></i> Add New Announcement </a>
             </div>

             <!-- Status Messages -->
             <?php if ($status_message): ?> <div class="mb-4 p-4 rounded-lg <?php echo ($status_type === 'success') ? 'bg-green-100 ... text-green-700' : 'bg-red-100 ... text-red-700'; ?>"> <?php echo htmlspecialchars($status_message); ?> </div> <?php endif; ?>
             <?php if ($fetch_error): ?> <div class="mb-4 p-4 ... bg-red-100 ..."> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

             <!-- Announcements Table -->
             <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Excerpt</th>
                            <th class="px-4 py-3">Post Date</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo $item['AnnouncementID']; ?></td>
                                    <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($item['Title']); ?></td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <?php echo htmlspecialchars(substr($item['Content'], 0, 70)); echo (strlen($item['Content']) > 70) ? '...' : ''; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?php echo date("d M Y, H:i", strtotime($item['PostDate'])); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center space-x-3">
                                        <a href="admin_edit_announcement.php?id=<?php echo $item['AnnouncementID']; ?>" class="text-indigo-600 hover:text-indigo-800" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                        <form action="backend/delete_announcement.php" method="POST" class="inline-block" onsubmit="return confirm('Delete announcement: <?php echo htmlspecialchars(addslashes($item['Title'])); ?>?');">
                                            <input type="hidden" name="announcement_id" value="<?php echo $item['AnnouncementID']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr> <td colspan="5" class="text-center px-6 py-10 text-gray-500"> No announcements found. <a href="admin_edit_announcement.php" class="text-indigo-600 hover:underline">Add the first one?</a> </td> </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </main>
    </div>
</body>
</html>