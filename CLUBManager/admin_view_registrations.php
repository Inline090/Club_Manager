<?php
session_start();
require 'Dashboard/config.php'; // Path relative to this file in root

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// Get and validate Event ID from URL
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id || $event_id <= 0) {
     header("Location: admin_manage_events.php?error=invalid_event_id"); exit;
}

// --- Database Connection ---
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// --- Fetch Event Details ---
$event = null;
$sql_event = "SELECT EventName, EventDate FROM Event WHERE EventID = ?";
$stmt_event = mysqli_prepare($conn, $sql_event);
if ($stmt_event) {
    mysqli_stmt_bind_param($stmt_event, "i", $event_id);
    mysqli_stmt_execute($stmt_event);
    $result_event = mysqli_stmt_get_result($stmt_event);
    if (!($event = mysqli_fetch_assoc($result_event))) {
         mysqli_close($conn);
         header("Location: admin_manage_events.php?error=event_not_found"); exit;
    }
    mysqli_stmt_close($stmt_event);
} else {
    error_log("Failed to prepare event fetch: " . mysqli_error($conn));
    mysqli_close($conn);
     header("Location: admin_manage_events.php?error=fetch_failed"); exit;
}

// --- Fetch Registered Members ---
$registrants = [];
// Assumes MemberEvent has 'Attended' column (BOOLEAN/TINYINT)
$sql_regs = "SELECT m.MemberID, m.Name, m.Email, m.Phone, me.Attended
             FROM MemberEvent me
             JOIN Member m ON me.MemberID = m.MemberID
             WHERE me.EventID = ?
             ORDER BY m.Name ASC";
$stmt_regs = mysqli_prepare($conn, $sql_regs);
$fetch_error = '';
if ($stmt_regs) {
    mysqli_stmt_bind_param($stmt_regs, "i", $event_id);
    mysqli_stmt_execute($stmt_regs);
    $result_regs = mysqli_stmt_get_result($stmt_regs);
    while ($row = mysqli_fetch_assoc($result_regs)) {
        $registrants[] = $row;
    }
    mysqli_stmt_close($stmt_regs);
} else {
     error_log("Error fetching registrants for event $event_id: " . mysqli_error($conn));
     $fetch_error = "Could not retrieve registrant list.";
}

mysqli_close($conn);

// --- Get Status Messages from URL ---
$status_message = ''; $status_type = '';
if (isset($_GET['status'])) {
     if ($_GET['status'] == 'attendance_updated') { $status_message = 'Attendance updated successfully.'; $status_type = 'success'; }
     if ($_GET['status'] == 'attendance_failed') { $status_message = 'Failed to update attendance.'; $status_type = 'error'; }
}
if(isset($_GET['error'])) { $status_message = htmlspecialchars(urldecode($_GET['error'])); $status_type = 'error'; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations: <?php echo htmlspecialchars($event['EventName'] ?? 'Unknown Event'); ?> - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p> </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <a href="admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-gauge w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Dashboard</a>
                 <a href="admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-users w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Manage Members</a>
                 <a href="admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-indigo-500"></i> Manage Events</a>
                 <!-- Other Links -->
            </nav>
             <div class="p-4 border-t border-gray-200 mt-auto"> <a href="backend/admin_logout.php" class="flex items-center text-sm text-gray-500 hover:text-indigo-600"> <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i> Logout </a> </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
             <div class="flex justify-between items-center mb-2">
                 <h2 class="text-3xl font-semibold text-gray-800">Event Registrations</h2>
                 <a href="admin_manage_events.php" class="text-sm text-indigo-600 hover:underline">← Back to All Events</a>
             </div>
             <div class="mb-6 border-b pb-2">
                <p class="text-xl font-medium text-indigo-700"><?php echo htmlspecialchars($event['EventName'] ?? 'Unknown Event'); ?></p>
                <p class="text-sm text-gray-600"><?php echo date("l, F j, Y, g:i A", strtotime($event['EventDate'])); ?></p>
             </div>

             <!-- Status Messages -->
             <?php if ($status_message): ?> <div class="mb-4 p-4 rounded-lg <?php echo ($status_type === 'success') ? 'bg-green-100 ... text-green-700' : 'bg-red-100 ... text-red-700'; ?>"> <?php echo htmlspecialchars($status_message); ?> </div> <?php endif; ?>
             <?php if ($fetch_error): ?> <div class="mb-4 p-4 ... bg-red-100 ..."> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>


             <!-- Registrants Table -->
             <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Member ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3 text-center">Attended?</th>
                            <th class="px-4 py-3 text-center">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($registrants)): ?>
                            <?php foreach ($registrants as $reg): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo $reg['MemberID']; ?></td>
                                    <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($reg['Name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($reg['Email']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($reg['Phone'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-center font-semibold <?php echo $reg['Attended'] ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $reg['Attended'] ? 'Yes' : 'No'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        <!-- Simple form POST method -->
                                        <form action="backend/mark_attendance.php" method="POST" class="inline-block">
                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                            <input type="hidden" name="member_id" value="<?php echo $reg['MemberID']; ?>">
                                            <?php if ($reg['Attended']): ?>
                                                <input type="hidden" name="attendance_status" value="0"> <!-- Set to Not Attended -->
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded shadow-sm" title="Mark as Not Attended">Mark No</button>
                                            <?php else: ?>
                                                 <input type="hidden" name="attendance_status" value="1"> <!-- Set to Attended -->
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded shadow-sm" title="Mark as Attended">Mark Yes</button>
                                            <?php endif; ?>
                                        </form>
                                        <!-- Alternative: Use AJAX for smoother updates later -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr> <td colspan="6" class="text-center px-6 py-10 text-gray-500"> No members have registered for this event yet. </td> </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </main>
    </div>
</body>
</html>