<?php
// ALWAYS start session FIRST at the very top
session_start();

// **** Check for ADMIN login session ****
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    // Redirect to ADMIN login page (Relative path from Dashboard folder)
    header("location: ../admin_login.html?error=not_logged_in"); // Assuming admin_login.html is in root
    exit; // Stop script execution
}

// Get admin data from session
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminId = $_SESSION['admin_id'] ?? 'N/A';

// --- Optional: Fetch Admin-Specific Summary Data ---
// Uncomment and add DB connection (using require 'config.php'; since config is in Dashboard) if you implement this

require 'config.php'; // Config is in the same folder
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$totalMembers = 0;
$upcomingEventCount = 0;
if ($conn) {
    $result_mem_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM Member"); // Simpler count
    if ($row_mem = mysqli_fetch_assoc($result_mem_count)) { $totalMembers = $row_mem['count']; }
    $result_evt_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM Event WHERE EventDate >= CURDATE()");
    if ($row_evt = mysqli_fetch_assoc($result_evt_count)) { $upcomingEventCount = $row_evt['count']; }
    mysqli_close($conn);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($adminName); ?> - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        /* Keep base styles */
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #c5c5c5; border-radius: 3px; } ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b border-gray-200">
                <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1>
                <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($adminName); ?>!</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <!-- Link is relative to current file (Dashboard/admin_dashboard.php) -->
                <a href="admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group">
                    <i class="fa-solid fa-gauge w-6 h-6 mr-3 text-indigo-500"></i>
                    <span class="font-medium">Dashboard Home</span>
                </a>
                <!-- Link goes UP one level to the root -->
                 <a href="../admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group">
                    <i class="fa-solid fa-users w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i>
                    <span class="font-medium">Manage Members</span>
                </a>
                <!-- Link goes UP one level to the root -->
                <a href="../admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group">
                    <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i>
                    <span class="font-medium">Manage Events</span>
                </a>
                <!-- Link goes UP one level to the root -->
                <a href="../admin_manage_announcements.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group">
                    <i class="fa-solid fa-bullhorn w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i>
                    <span class="font-medium">Manage Announcements</span>
                </a>
                <!-- Link goes UP one level to the root -->
                 <a href="../admin_manage_finances.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group">
                    <i class="fa-solid fa-dollar-sign w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i>
                    <span class="font-medium">Manage Finances</span>
                </a>
                 <!-- REMOVED Reports Link -->
                 <!-- REMOVED Settings Link -->
            </nav>
            <!-- Removed duplicate nav links -->

            <!-- Sidebar Footer (Logout) -->
            <div class="p-4 border-t border-gray-200 mt-auto">
                 <!-- Link goes UP one level, then DOWN into backend -->
                 <a href="../backend/admin_logout.php" class="flex items-center text-sm text-gray-500 hover:text-indigo-600">
                    <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-8 overflow-y-auto">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">Admin Dashboard</h2>

            <!-- Content for Admin -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Admin Welcome Card -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-medium text-gray-700 mb-4">Welcome, Admin!</h3>

                    <p class="text-gray-600 mt-2 text-sm"> Logged in as: <?php echo htmlspecialchars($adminName); ?> </p>
                </div>

                 <!-- Quick Stats Card -->
                <div class="bg-white p-6 rounded-lg shadow">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">Quick Stats</h3>
                     <ul class="space-y-2">
                     <li>
    <span class="font-semibold">Total Members:</span>
    <span class="text-lg font-medium text-black">
        <?php echo htmlspecialchars($totalMembers); ?>
    </span>
</li>
<li>
    <span class="font-semibold">Upcoming Events:</span>
    <span class="text-lg font-medium text-black">
        <?php echo htmlspecialchars($upcomingEventCount); ?>
    </span>
</li>

                     </ul>

                </div>

                 <!-- Quick Actions Card -->
                <div class="bg-white p-6 rounded-lg shadow">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">Quick Actions</h3>
                     <ul class="space-y-2">
                         <!-- Links go UP one level -->
                        <li><a href="../admin_edit_member.php" class="text-indigo-600 hover:underline text-sm"><i class="fas fa-user-plus fa-fw mr-1"></i> Add New Member</a></li>
                        <li><a href="../admin_edit_event.php" class="text-indigo-600 hover:underline text-sm"><i class="fas fa-calendar-plus fa-fw mr-1"></i> Schedule New Event</a></li>
                        <li><a href="../admin_edit_announcement.php" class="text-indigo-600 hover:underline text-sm"><i class="fas fa-bullhorn fa-fw mr-1"></i> Post Announcement</a></li>
                        <li><a href="../admin_add_transaction.php?type=expense" class="text-indigo-600 hover:underline text-sm"><i class="fas fa-minus-circle fa-fw mr-1"></i> Record Expense</a></li>
                     </ul>
                </div>

            </div> <!-- End grid -->
        </main>
    </div>

</body>
</html>