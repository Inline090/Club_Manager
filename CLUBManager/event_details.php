<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

// Include DB configuration (path relative to this file in root)
require 'Dashboard/config.php';

// Get Event ID from URL and validate
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'] ?? 0; // Get logged-in user's ID

// Initialize variables
$event = null;
$isRegistered = false;
$registrationOpen = false;
$isFull = false;
$error_message = '';
$success_message = '';
$currentRegistrations = 0; // Initialize count

// Validate IDs before proceeding
if (!$eventId || $eventId <= 0) {
    $error_message = "Invalid event specified.";
} elseif ($userId <= 0) {
    $error_message = "Invalid user session. Please login again.";
} else {
    // --- Database Connection ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        $error_message = "Database connection error.";
        error_log("Event Details DB Connection Failed: " . mysqli_connect_error());
    } else {
        // --- Fetch Event Details ---
        // Fetch all necessary columns, including Description, Capacity, Deadline if they exist
        $sql_event = "SELECT EventID, EventName, EventDate, EventLocation, EventFee, Description, Capacity, RegistrationDeadline
                      FROM Event WHERE EventID = ?";
        $stmt_event = mysqli_prepare($conn, $sql_event);

        if ($stmt_event) {
            mysqli_stmt_bind_param($stmt_event, "i", $eventId);
            mysqli_stmt_execute($stmt_event);
            $result_event = mysqli_stmt_get_result($stmt_event);
            $event = mysqli_fetch_assoc($result_event); // Fetch the event data
            mysqli_stmt_close($stmt_event);

            if ($event) {
                // --- Check User's Registration Status ---
                $sql_check_reg = "SELECT COUNT(*) FROM MemberEvent WHERE MemberID = ? AND EventID = ?";
                $stmt_check_reg = mysqli_prepare($conn, $sql_check_reg);
                if ($stmt_check_reg) {
                    mysqli_stmt_bind_param($stmt_check_reg, "ii", $userId, $eventId);
                    mysqli_stmt_execute($stmt_check_reg);
                    mysqli_stmt_bind_result($stmt_check_reg, $reg_count);
                    mysqli_stmt_fetch($stmt_check_reg);
                    $isRegistered = ($reg_count > 0);
                    mysqli_stmt_close($stmt_check_reg);
                } else { $error_message .= " Error checking registration status."; error_log("Check Reg Prepare Error: ".mysqli_error($conn)); }

                // --- Determine if Registration is Open ---
                $todayTimestamp = time();
                $eventTimestamp = strtotime($event['EventDate']); // Assumes EventDate is DATETIME or similar

                // Use deadline if set, otherwise use event date itself
                $deadlineStr = $event['RegistrationDeadline'] ?? $event['EventDate'];
                 // Handle date-only deadline - assume end of day
                 if (strlen($deadlineStr) == 10) { $deadlineStr .= ' 23:59:59'; }
                 $deadlineTimestamp = strtotime($deadlineStr);

                 // Open if NOW is before the deadline AND before the event starts
                 $registrationOpen = ($todayTimestamp <= $deadlineTimestamp && $todayTimestamp < $eventTimestamp);

                 // --- Check Capacity (if applicable) ---
                 if ($event['Capacity'] !== null && $event['Capacity'] > 0) {
                     $sql_capacity = "SELECT COUNT(*) FROM MemberEvent WHERE EventID = ?";
                     $stmt_capacity = mysqli_prepare($conn, $sql_capacity);
                     if($stmt_capacity) {
                         mysqli_stmt_bind_param($stmt_capacity, "i", $eventId);
                         mysqli_stmt_execute($stmt_capacity);
                         mysqli_stmt_bind_result($stmt_capacity, $currentRegistrations);
                         mysqli_stmt_fetch($stmt_capacity);
                         mysqli_stmt_close($stmt_capacity);
                         if ($currentRegistrations >= $event['Capacity']) {
                             $isFull = true;
                             $registrationOpen = false; // Override if full
                         }
                     } else { error_log("Capacity Check Prepare Error: ".mysqli_error($conn)); }
                 }

            } else {
                // Event ID from URL does not exist in the database
                $error_message = "Event not found.";
            }
        } else {
            // Error preparing the SQL statement
            $error_message = "Error fetching event details.";
            error_log("Event Fetch Prepare Error: " . mysqli_error($conn));
        }
        mysqli_close($conn); // Close connection
    } // End if $conn
} // End if validation passed

// --- Check for status messages passed back from registration ---
if(isset($_GET['reg_status'])) {
    if($_GET['reg_status'] == 'success') {
        $success_message = "You have successfully registered for this event!";
        $isRegistered = true; // Update status
        $registrationOpen = false; // Close button after successful registration on this load
    } elseif($_GET['reg_status'] == 'already_registered') {
        $error_message = "You are already registered for this event.";
        $isRegistered = true; $registrationOpen = false;
    } elseif($_GET['reg_status'] == 'closed') {
        $error_message = "Registration for this event is closed" . ($isFull ? ' or the event is full' : '') . ".";
        $registrationOpen = false;
    } elseif($_GET['reg_status'] == 'failed') {
        $error_message = "Registration failed. Please try again or contact support.";
    }
}

// --- Prepare Event Description from User Input ---
// You can store this full description in your database Event.Description column
// For now, we'll display it if the event name matches "Cosmic Quest" (or check ID if known)
$eventDescription = $event['Description'] ?? null; // Get from DB if exists
$event_specific_id = 1; // *** IMPORTANT: Replace 1 with the actual EventID for Cosmic Quest ***

if ($event && $event['EventID'] == $event_specific_id) {
    // Override DB description with the provided text for this specific event
    $eventDescription = <<<EOD
Cosmic Quest- An Astronomy Themed Treasure Hunt
Greetings from the Astronomy Club!

Get ready to participate in "Cosmic Quest", a treasure hunt that will take you on a journey through the cosmos and our campus!

Date: 16/2/24
Time: 4:30PM
Location: Room 207

The registrations will be first come first serve basis so register ASAP!
NOTE: Only the team captain needs to fill the registration form.
Day scholars need to make arrangements for their transport.


Gather your team and prepare for an exciting challenge as you decode clues, solve puzzles, and uncover hidden Clues scattered throughout the Campus.

For more details, feel free to reach out to us.

Join us for an unforgettable Event!
EOD;
    // Update event details array for consistency if needed (or just use $eventDescription below)
    // $event['Description'] = $eventDescription;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $event ? htmlspecialchars($event['EventName']) : 'Event Details'; ?> - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        /* Add styles for prose if needed */
        .prose { max-width: 65ch; /* Or another suitable max-width */ }
        .prose h2 { margin-bottom: 0.5em; }
        .prose p { margin-bottom: 1em; line-height: 1.6; }
    </style>
</head>
<body class="bg-gray-100">
     <!-- Navbar -->
     <nav class="bg-white shadow-sm mb-8">
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a>
            <div>
                 <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a>
                 <a href="event_list.php" class="text-gray-600 hover:text-indigo-600 mr-4">All Events</a>
                 <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow mb-10">
        <?php if ($event): // Only display content if event was found ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($event['EventName']); ?></h1>

             <!-- Status Messages Area -->
             <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm" role="alert"> <?php echo htmlspecialchars($success_message); ?> </div> <?php endif; ?>
             <?php // Display error message, but avoid showing redundant "closed" if already showing button disabled text
             if ($error_message && !(isset($_GET['reg_status']) && ($_GET['reg_status'] == 'closed' || $_GET['reg_status'] == 'already_registered'))): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert"> <?php echo htmlspecialchars($error_message); ?> </div>
             <?php endif; ?>

            <!-- Event Metadata -->
            <div class="space-y-2 text-gray-700 mb-6 border-b pb-4">
                <p><i class="far fa-calendar-alt fa-fw mr-2 w-5 text-indigo-600"></i> <strong>Date & Time:</strong> <?php echo date("l, F j, Y, g:i A", strtotime($event['EventDate'])); ?></p>
                <?php if (!empty($event['EventLocation'])): ?> <p><i class="fas fa-map-marker-alt fa-fw mr-2 w-5 text-indigo-600"></i> <strong>Location:</strong> <?php echo htmlspecialchars($event['EventLocation']); ?></p> <?php endif; ?>
                <p><i class="fas fa-dollar-sign fa-fw mr-2 w-5 text-indigo-600"></i> <strong>Fee:</strong> <?php echo (isset($event['EventFee']) && $event['EventFee'] > 0) ? '₹'.htmlspecialchars(number_format($event['EventFee'], 2)) : 'Free'; ?></p>
                <?php if (!empty($event['RegistrationDeadline'])): ?> <p><i class="fas fa-clock fa-fw mr-2 w-5 text-indigo-600"></i> <strong>Register By:</strong> <?php echo date("M j, Y, g:i A", strtotime($event['RegistrationDeadline'])); ?></p> <?php endif; ?>
                <?php if ($event['Capacity'] !== null && $event['Capacity'] > 0): ?>                     <p><i class="fas fa-users fa-fw mr-2 w-5 text-indigo-600"></i> <strong>Capacity:</strong> <?php echo htmlspecialchars($event['Capacity']); ?> spots</p> <?php endif; ?>
            </div>

            <!-- Event Description -->
            <?php if (!empty($eventDescription)): ?>
            <div class="prose prose-sm sm:prose-base max-w-none text-gray-800 mt-4">
                 <!-- <h2 class="text-xl font-semibold mb-2">Details</h2> -->
                 <?php echo nl2br(htmlspecialchars($eventDescription)); // Use the fetched or overridden description ?>
            </div>
            <?php endif; ?>

            <!-- Registration Button/Status -->
            <div class="mt-8 pt-6 border-t">
                <?php if ($isRegistered): ?>
                    <p class="p-3 rounded-md bg-green-100 text-green-800 font-medium text-center border border-green-200"> <i class="fas fa-check-circle mr-2"></i> You are registered for this event! </p>
                <?php elseif ($registrationOpen): ?>
                    <form action="backend/register_event.php" method="POST" onsubmit="return confirm('Confirm registration for <?php echo htmlspecialchars(addslashes($event['EventName'])); ?>?');">
                        <input type="hidden" name="event_id" value="<?php echo $event['EventID']; ?>">
                        <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"> Register Now <?php echo (isset($event['EventFee']) && $event['EventFee'] > 0) ? '(Fee: ₹'.number_format($event['EventFee'], 2).')' : '(Free)'; ?> </button>
                    </form>
                <?php else: ?>
                     <p class="p-3 rounded-md bg-gray-200 text-gray-600 font-medium text-center border border-gray-300"> <i class="fas fa-times-circle mr-2"></i> Registration for this event is <?php echo $isFull ? 'full' : 'closed'; ?>. </p>
                <?php endif; ?>
            </div>

        <?php else: // Event not found or initial error ?>
             <h1 class="text-2xl font-semibold text-red-600 mb-4">Error</h1>
             <p class="text-gray-600"><?php echo htmlspecialchars($error_message ?: "Event details could not be loaded."); ?></p>
             <a href="event_list.php" class="mt-6 inline-block text-indigo-600 hover:underline">← Back to Event List</a>
        <?php endif; ?>
    </div>
</body>
</html>