<?php
session_start();
require 'Dashboard/config.php'; // Path relative to root

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// --- Determine Mode & Fetch Data ---
$edit_mode = false;
$event_id = null;
$event_data = [ // Defaults
    'EventName' => '', 'EventDate' => '', 'EventLocation' => '',
    'EventFee' => '0.00', 'Description' => '', 'Capacity' => '',
    'RegistrationDeadline' => ''
];
$page_title = 'Add New Event';
$error_message = '';
$success_message = '';

if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $event_id = (int)$_GET['id'];
    $page_title = 'Edit Event Details';

    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        $sql = "SELECT EventName, EventDate, EventLocation, EventFee, Description, Capacity, RegistrationDeadline FROM Event WHERE EventID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $event_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($data = mysqli_fetch_assoc($result)) {
                // Format dates for datetime-local input
                $data['EventDate'] = !empty($data['EventDate']) ? date('Y-m-d\TH:i', strtotime($data['EventDate'])) : '';
                $data['RegistrationDeadline'] = !empty($data['RegistrationDeadline']) ? date('Y-m-d\TH:i', strtotime($data['RegistrationDeadline'])) : '';
                $event_data = $data;
            } else {
                 $error_message = "Event not found."; $edit_mode = false; $event_id = null; $page_title = 'Add New Event';
            }
            mysqli_stmt_close($stmt);
        } else { $error_message = "Error fetching event data."; error_log(mysqli_error($conn)); }
        mysqli_close($conn);
    } else { $error_message = "Database connection error."; }
}

// Check for status messages
if(isset($_GET['status']) && $_GET['status'] == 'success') { $success_message = "Event saved successfully!"; }
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
     <nav class="bg-white shadow-sm"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/admin_dashboard.php" class="text-xl font-bold text-indigo-600">Admin Panel</a> <div> <a href="Dashboard/admin_dashboard.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="admin_manage_events.php" class="text-gray-600 hover:text-indigo-600 mr-4">Manage Events</a> <a href="backend/admin_logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-3xl mx-auto mt-8 mb-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $page_title; ?></h1>

        <!-- Messages -->
        <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 ..."> <?php echo $success_message; ?> </div> <?php endif; ?>
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 ..."> <?php echo $error_message; ?> </div> <?php endif; ?>

        <form action="backend/save_event.php" method="POST">
             <?php if ($edit_mode): ?> <input type="hidden" name="event_id" value="<?php echo $event_id; ?>"> <?php endif; ?>

             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <!-- Event Name (Spans 2 cols on md+) -->
                 <div class="md:col-span-2">
                    <label for="event_name" class="block text-sm font-medium text-gray-700 mb-1">Event Name <span class="text-red-500">*</span></label>
                    <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event_data['EventName']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                 <!-- Event Date/Time -->
                 <div>
                    <label for="event_date" class="block text-sm font-medium text-gray-700 mb-1">Event Date & Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="event_date" name="event_date" value="<?php echo htmlspecialchars($event_data['EventDate']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <!-- Location -->
                 <div>
                     <label for="event_location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" id="event_location" name="event_location" value="<?php echo htmlspecialchars($event_data['EventLocation'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                 <!-- Fee -->
                 <div>
                    <label for="event_fee" class="block text-sm font-medium text-gray-700 mb-1">Fee (₹)</label>
                    <input type="number" id="event_fee" name="event_fee" value="<?php echo htmlspecialchars($event_data['EventFee'] ?? '0.00'); ?>" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Enter 0 or leave blank for free events.</p>
                 </div>
                 <!-- Capacity -->
                 <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                    <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($event_data['Capacity'] ?? ''); ?>" min="0" step="1" placeholder="Leave blank for unlimited" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                 </div>
                 <!-- Registration Deadline -->
                  <div class="md:col-span-2">
                     <label for="registration_deadline" class="block text-sm font-medium text-gray-700 mb-1">Registration Deadline</label>
                    <input type="datetime-local" id="registration_deadline" name="registration_deadline" value="<?php echo htmlspecialchars($event_data['RegistrationDeadline'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                     <p class="text-xs text-gray-500 mt-1">Leave blank to allow registration until the event starts.</p>
                 </div>
                 <!-- Description -->
                 <div class="md:col-span-2">
                     <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($event_data['Description'] ?? ''); ?></textarea>
                 </div>
             </div> <!-- end grid -->

             <!-- Form Actions -->
            <div class="flex justify-end mt-8">
                <a href="admin_manage_events.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"> <i class="fas fa-save mr-2"></i> Save Event </button>
            </div>
        </form>
    </div>
</body>
</html>