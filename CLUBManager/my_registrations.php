<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require 'Dashboard/config.php'; // Path relative to this file in root
$userId = $_SESSION['user_id'] ?? 0;

$registrations = [];
$fetch_error = '';

if ($userId > 0) {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        // Assumes Attended column exists in MemberEvent
        $sql = "SELECT e.EventID, e.EventName, e.EventDate, e.EventLocation, me.Attended
                FROM MemberEvent me
                JOIN Event e ON me.EventID = e.EventID
                WHERE me.MemberID = ?
                ORDER BY e.EventDate DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $registrations[] = $row;
            }
            mysqli_stmt_close($stmt);
        } else { $fetch_error = "Could not retrieve registrations."; error_log(mysqli_error($conn));}
        mysqli_close($conn);
    } else { $fetch_error = "Database connection error."; }
} else { $fetch_error = "Invalid session."; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Event Registrations - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
     <nav class="bg-white shadow-sm mb-8"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a> <div> <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="event_list.php" class="text-gray-600 hover:text-indigo-600 mr-4">All Events</a> <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-4xl mx-auto p-6">
         <h1 class="text-3xl font-semibold text-gray-800 mb-6">My Event Registrations</h1>

          <?php if ($fetch_error): ?> <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert"> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

         <?php if (empty($registrations) && !$fetch_error): ?>
             <div class="bg-white p-6 rounded-lg shadow text-center"> <p class="text-gray-500">You have not registered for any events yet.</p> <a href="event_list.php" class="mt-4 inline-block text-indigo-600 hover:underline font-medium">Browse Events</a> </div>
         <?php elseif (!empty($registrations)): ?>
             <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Event Name</th> <th class="px-6 py-3">Date</th> <th class="px-6 py-3">Location</th> <th class="px-6 py-3">Attended</th> <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($registrations as $reg): $isPast = strtotime($reg['EventDate']) < time(); ?>
                            <tr class="<?php echo $isPast ? 'bg-gray-50 opacity-75' : 'hover:bg-gray-50'; ?>">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($reg['EventName']); ?></td>
                                <td class="px-6 py-4"><?php echo date("M j, Y", strtotime($reg['EventDate'])); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($reg['EventLocation'] ?? '-'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($isPast && isset($reg['Attended'])): // Only show attendance if event is past AND column exists ?>
                                        <?php echo $reg['Attended'] ? '<span class="text-green-600 font-semibold">Yes</span>' : '<span class="text-red-600">No</span>'; ?>
                                    <?php else: ?> <span class="text-gray-400">-</span> <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="event_details.php?id=<?php echo $reg['EventID']; ?>" class="text-indigo-600 hover:underline">View Event</a>
                                    <!-- Add Unregister button/logic here if needed -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
         <?php endif; ?>
    </div>
</body>
</html>