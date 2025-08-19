<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require 'Dashboard/config.php'; // Path relative to this file in root

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$upcoming_events = [];
$past_events = [];
$fetch_error = '';

if ($conn) {
    // Fetch Upcoming Events
    $sql_upcoming = "SELECT EventID, EventName, EventDate, EventLocation, EventFee FROM Event WHERE EventDate >= CURDATE() ORDER BY EventDate ASC";
    $result_upcoming = mysqli_query($conn, $sql_upcoming);
    if ($result_upcoming) {
        while ($row = mysqli_fetch_assoc($result_upcoming)) { $upcoming_events[] = $row; }
        mysqli_free_result($result_upcoming);
    } else { $fetch_error .= "Could not fetch upcoming events. "; error_log(mysqli_error($conn)); }

     // Fetch Past Events
    $sql_past = "SELECT EventID, EventName, EventDate, EventLocation FROM Event WHERE EventDate < CURDATE() ORDER BY EventDate DESC LIMIT 20"; // Limit past events
    $result_past = mysqli_query($conn, $sql_past);
    if ($result_past) {
        while ($row = mysqli_fetch_assoc($result_past)) { $past_events[] = $row; }
         mysqli_free_result($result_past);
    } else { $fetch_error .= "Could not fetch past events. "; error_log(mysqli_error($conn)); }

    mysqli_close($conn);
} else { $fetch_error = "Database connection error."; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Events - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
     <nav class="bg-white shadow-sm"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a> <div> <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-4xl mx-auto mt-8 p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Club Events</h1>

         <?php if ($fetch_error): ?> <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert"> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

        <!-- Upcoming Events -->
        <div class="mb-10">
            <h2 class="text-2xl font-medium text-gray-700 mb-4 border-b pb-2">Upcoming Events</h2>
            <?php if (!empty($upcoming_events)): ?>
                <div class="space-y-4">
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="bg-white p-4 rounded-lg shadow flex flex-col sm:flex-row justify-between items-start sm:items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-indigo-700"><?php echo htmlspecialchars($event['EventName']); ?></h3>
                                <p class="text-sm text-gray-600"><i class="far fa-calendar-alt fa-fw mr-1"></i> <?php echo date("l, F j, Y", strtotime($event['EventDate'])); // Removed time for list view ?></p>
                                <?php if(!empty($event['EventLocation'])): ?><p class="text-sm text-gray-600"><i class="fas fa-map-marker-alt fa-fw mr-1"></i> <?php echo htmlspecialchars($event['EventLocation']); ?></p><?php endif; ?>
                                <?php if(isset($event['EventFee']) && $event['EventFee'] > 0): ?><p class="text-sm text-gray-600"><i class="fas fa-dollar-sign fa-fw mr-1"></i> Fee: ₹<?php echo htmlspecialchars(number_format($event['EventFee'], 2)); ?></p><?php endif; ?>
                            </div>
                            <a href="event_details.php?id=<?php echo $event['EventID']; ?>" class="mt-3 sm:mt-0 inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded transition duration-150"> View Details & Register </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif(!$fetch_error): ?> <p class="text-gray-500">No upcoming events found.</p> <?php endif; ?>
        </div>

        <!-- Past Events -->
        <div>
             <h2 class="text-2xl font-medium text-gray-700 mb-4 border-b pb-2">Past Events (Recent)</h2>
             <?php if (!empty($past_events)): ?>
                <div class="space-y-3">
                    <?php foreach ($past_events as $event): ?>
                         <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center">
                             <div>
                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($event['EventName']); ?></h4>
                                <p class="text-xs text-gray-500"><i class="far fa-calendar-alt fa-fw mr-1"></i> <?php echo date("M j, Y", strtotime($event['EventDate'])); ?></p>
                             </div>
                              <a href="event_details.php?id=<?php echo $event['EventID']; ?>" class="text-indigo-600 hover:underline text-xs font-medium">View Details</a>
                         </div>
                    <?php endforeach; ?>
                </div>
             <?php elseif(!$fetch_error): ?> <p class="text-gray-500">No recent past events found.</p> <?php endif; ?>
        </div>
    </div>
</body>
</html>