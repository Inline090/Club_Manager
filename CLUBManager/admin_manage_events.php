<?php
session_start();
require 'Dashboard/config.php'; // Path relative to this file in root

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// --- Database Connection ---
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// --- Fetch Events ---
$events = [];
// Fetch most columns needed for the list view
$sql = "SELECT EventID, EventName, EventDate, EventLocation, EventFee, Capacity
        FROM Event
        ORDER BY EventDate DESC"; // Show most recent first in admin view

$result = mysqli_query($conn, $sql);
$fetch_error = '';
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Fetch registration count for each event (could be less performant for many events)
        $count_sql = "SELECT COUNT(*) as reg_count FROM MemberEvent WHERE EventID = ?";
        $stmt_count = mysqli_prepare($conn, $count_sql);
        if ($stmt_count) {
            mysqli_stmt_bind_param($stmt_count, "i", $row['EventID']);
            mysqli_stmt_execute($stmt_count);
            mysqli_stmt_bind_result($stmt_count, $reg_count);
            mysqli_stmt_fetch($stmt_count);
            $row['RegistrationCount'] = $reg_count;
            mysqli_stmt_close($stmt_count);
        } else {
            $row['RegistrationCount'] = '?'; // Indicate error fetching count
        }
        $events[] = $row;
    }
     mysqli_free_result($result);
} else {
    error_log("Error fetching events: " . mysqli_error($conn));
    $fetch_error = "Could not retrieve event list.";
}

mysqli_close($conn);

// --- Get Status Messages from URL ---
$status_message = ''; $status_type = '';
if (isset($_GET['status'])) { /* ... code to set messages for added, updated, deleted, not_found ... */ }
if (isset($_GET['error'])) { /* ... code to set error messages ... */ }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; } /* Add scrollbar styles if needed */ </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p> </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <a href="Dashboard/admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-gauge w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Dashboard</a>
                 <a href="admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-users w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Manage Members</a>
                 <a href="admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-indigo-500"></i> Manage Events</a>
                 <!-- Other Links -->
            </nav>
             <div class="p-4 border-t border-gray-200 mt-auto"> <a href="backend/admin_logout.php" class="flex items-center text-sm text-gray-500 hover:text-indigo-600"> <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i> Logout </a> </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
             <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Events</h2>
                <a href="admin_edit_event.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-sm"> <i class="fas fa-plus mr-2"></i> Add New Event </a>
             </div>

             <!-- Status Messages -->
             <?php if ($status_message): ?> <div class="mb-4 p-4 rounded-lg <?php echo ($status_type === 'success') ? 'bg-green-100 ... text-green-700' : 'bg-red-100 ... text-red-700'; ?>"> <?php echo htmlspecialchars($status_message); ?> </div> <?php endif; ?>
             <?php if ($fetch_error): ?> <div class="mb-4 p-4 ... bg-red-100 ..."> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

             <!-- Events Table -->
             <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3 text-center">Fee</th>
                            <th class="px-4 py-3 text-center">Capacity</th>
                            <th class="px-4 py-3 text-center">Registered</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo $event['EventID']; ?></td>
                                    <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($event['EventName']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?php echo date("d M Y, H:i", strtotime($event['EventDate'])); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($event['EventLocation'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo ($event['EventFee'] > 0) ? '₹'.number_format($event['EventFee'], 2) : 'Free'; ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo ($event['Capacity'] > 0) ? $event['Capacity'] : 'N/A'; ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="admin_view_registrations.php?event_id=<?php echo $event['EventID']; ?>" class="text-blue-600 hover:underline">
                                            <?php echo $event['RegistrationCount']; ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center space-x-3">
                                        <a href="admin_edit_event.php?id=<?php echo $event['EventID']; ?>" class="text-indigo-600 hover:text-indigo-800" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                        <form action="backend/delete_event.php" method="POST" class="inline-block" onsubmit="return confirm('Delete event: <?php echo htmlspecialchars(addslashes($event['EventName'])); ?>? This may also affect registrations.');">
                                            <input type="hidden" name="event_id" value="<?php echo $event['EventID']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr> <td colspan="8" class="text-center px-6 py-10 text-gray-500"> No events found. <a href="admin_edit_event.php" class="text-indigo-600 hover:underline">Add the first one?</a> </td> </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </main>
    </div>
</body>
</html>