<?php
// ALWAYS start session FIRST at the very top
session_start();

// Check if the user is logged in, if not then redirect to student login page
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    // Path is relative to Dashboard folder
    header("location: ../student.html?error=not_logged_in");
    exit;
}

// Get user data from session
$userName = $_SESSION['user_name'] ?? 'Member';
$userId = $_SESSION['user_id'] ?? 0; // Important to have the integer ID

// Include DB config (Path relative to Dashboard folder)
require 'config.php'; // Assumes config.php is in the Dashboard/ folder

// --- Database Connection ---
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$db_error = ''; // Initialize error message

if (!$conn) {
    error_log("Dashboard DB Connection Failed: " . mysqli_connect_error());
    $db_error = "Could not connect to fetch data.";
}

// --- Initialize data arrays/variables ---
$memberDetails = null;
$membershipStatus = 'Unknown'; // Default
$upcomingEvents = [];
$myUpcomingRegistrations = [];
$recentPayments = [];
$recentAnnouncements = [];
$paymentPageLink = '../payments.php'; // Default link

// --- Fetch data only if connection exists and user ID is valid ---
if (!$db_error && $userId > 0) {

    // --- Fetch Member Details (Profile & Membership) ---
    $sql_member = "SELECT Name, Email, Phone, MembershipType, MembershipExpiry FROM Member WHERE MemberID = ?";
    $stmt_member = mysqli_prepare($conn, $sql_member);
    if($stmt_member) {
        mysqli_stmt_bind_param($stmt_member, "i", $userId);
        mysqli_stmt_execute($stmt_member);
        $result_member = mysqli_stmt_get_result($stmt_member);
        if ($details = mysqli_fetch_assoc($result_member)) {
            $memberDetails = $details;
            if (!empty($memberDetails['MembershipExpiry'])) {
                $expiryDate = strtotime($memberDetails['MembershipExpiry']);
                $today = strtotime(date('Y-m-d'));
                $membershipStatus = ($expiryDate >= $today) ? 'Active' : 'Expired';
            } else { $membershipStatus = 'No Expiry Date'; } // Or Pending Payment if using that logic
            // *** Determine correct payment link based on calculated status ***
            $paymentPageLink = ($membershipStatus === 'Active') ? '../payment_status.php' : '../payments.php'; // Corrected base path

        } else { $fetch_error_member = "Could not load profile."; }
        mysqli_stmt_close($stmt_member);
    } else { error_log("Error preparing member fetch: " . mysqli_error($conn)); $fetch_error_member = "Error fetching profile."; }

    // --- Fetch Upcoming Events (General Listing) ---
    $sql_events = "SELECT EventID, EventName, EventDate, EventLocation FROM Event WHERE EventDate >= CURDATE() ORDER BY EventDate ASC LIMIT 5";
    $result_events = mysqli_query($conn, $sql_events);
    if ($result_events) { while ($row = mysqli_fetch_assoc($result_events)) { $upcomingEvents[] = $row; } mysqli_free_result($result_events); }
    else { error_log("Error fetching upcoming events: " . mysqli_error($conn)); }

    // --- Fetch Member's Upcoming Event Registrations ---
    $sql_my_regs = "SELECT e.EventID, e.EventName, e.EventDate FROM MemberEvent me JOIN Event e ON me.EventID = e.EventID WHERE me.MemberID = ? AND e.EventDate >= CURDATE() ORDER BY e.EventDate ASC LIMIT 3";
    $stmt_my_regs = mysqli_prepare($conn, $sql_my_regs);
    if($stmt_my_regs){ mysqli_stmt_bind_param($stmt_my_regs, "i", $userId); mysqli_stmt_execute($stmt_my_regs); $result_my_regs = mysqli_stmt_get_result($stmt_my_regs); while ($row = mysqli_fetch_assoc($result_my_regs)) { $myUpcomingRegistrations[] = $row; } mysqli_stmt_close($stmt_my_regs); }
    else { error_log("Error preparing user registrations fetch: " . mysqli_error($conn)); }

    // --- Fetch Member's Recent Payments ---
    $sql_payments = "SELECT p.PaymentDate, p.Amount, p.TransactionType, p.Description, e.EventName FROM Payment p LEFT JOIN Event e ON p.EventID = e.EventID WHERE p.MemberID = ? ORDER BY p.PaymentDate DESC LIMIT 5";
    $stmt_payments = mysqli_prepare($conn, $sql_payments);
     if($stmt_payments){
        mysqli_stmt_bind_param($stmt_payments, "i", $userId); mysqli_stmt_execute($stmt_payments); $result_payments = mysqli_stmt_get_result($stmt_payments);
        while ($row = mysqli_fetch_assoc($result_payments)) {
             $displayDesc = $row['Description'] ?: ($row['TransactionType'] ?: 'Payment');
             if (empty($row['Description']) && !empty($row['EventName']) && ($row['TransactionType'] == 'Event Fee' || $row['TransactionType'] == 'Event Payment')) { $displayDesc .= ' - ' . $row['EventName']; }
             $row['DisplayDescription'] = $displayDesc;
             $recentPayments[] = $row;
        } mysqli_stmt_close($stmt_payments);
    } else { error_log("Error preparing user payments fetch: " . mysqli_error($conn)); }

    // --- Fetch Recent Announcements ---
    $sql_announcements = "SELECT AnnouncementID, Title, Content, PostDate FROM Announcements ORDER BY PostDate DESC LIMIT 3";
    $result_announcements = mysqli_query($conn, $sql_announcements);
    if ($result_announcements) { while ($row = mysqli_fetch_assoc($result_announcements)) { $recentAnnouncements[] = $row; } mysqli_free_result($result_announcements); }
    else { error_log("Error fetching announcements: " . mysqli_error($conn)); }

    // --- Close connection ---
    mysqli_close($conn);

} // End if !$db_error & $userId > 0

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($userName); ?> - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        /* Base styles & status styles */
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #c5c5c5; border-radius: 3px; } ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
        .status-active { color: #16a34a; font-weight: 600; } .status-expired { color: #dc2626; font-weight: 600; } .status-no-expiry { color: #ca8a04; font-weight: 600; } .status-unknown { color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100">
<div class="absolute top-2 left-0 z-50"> <!-- Position top-left, high z-index -->
        <a href="main_board.php"> <!-- Link logo back to dashboard -->
        <img src="../images/logo.jpg" alt="Club Logo" class="h-[80px] w-[256px] rounded">
        
             
        </a>
    </div>    
<div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Member Portal</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($userName); ?>!</p> </div>
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <!-- Current Page -->
                <a href="main_board.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-gauge w-6 h-6 mr-3 text-indigo-500"></i> Dashboard </a>
                <!-- Links point UP one level (../) -->
                <a href="../edit_profile.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-id-card w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> My Profile </a>
                <a href="../change_password.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-key w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Change Password </a>
                <a href="../event_list.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Events </a>
                <a href="../my_registrations.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-clipboard-list w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> My Registrations </a>
                <a href="../explore_clubs.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-puzzle-piece w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Explore Clubs </a>
                 <!-- Dynamic Payment Link (already has ../ prepended in PHP) -->
                <a href="<?php echo $paymentPageLink; ?>" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-credit-card w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Membership Payment </a>
                 <!-- Payment History Link -->
                <a href="../my_payments.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-receipt w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Payment History </a>
                <!-- Announcements Anchor Link (Stays the same) -->
                 <a href="#announcements-section" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-bullhorn w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Announcements </a>
            </nav>
            <!-- Logout -->
            <div class="p-4 border-t border-gray-200 mt-auto"> <a href="../backend/logout.php" class="flex items-center text-sm text-gray-500 hover:text-indigo-600"> <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i> Logout </a> </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-8 overflow-y-auto">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">My Dashboard</h2>
            <!-- Display DB/Fetch Errors -->
            <?php if ($db_error): ?> <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert"> <?php echo htmlspecialchars($db_error); ?> Please contact support. </div> <?php endif; ?>
            <?php if (isset($fetch_error_member)): ?> <div class="mb-6 p-4 rounded-lg bg-yellow-100 border border-yellow-400 text-yellow-700" role="alert"> <?php echo htmlspecialchars($fetch_error_member); ?> </div> <?php endif; ?>

             <!-- First Row of Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">

                <!-- Profile & Membership Card -->
                <div class="bg-white p-6 rounded-lg shadow order-1">
                    <h3 class="text-xl font-medium text-gray-700 mb-4">My Profile & Membership</h3>
                    <?php if ($memberDetails): ?>
                        <div class="space-y-1 text-sm text-gray-700 mb-3">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($memberDetails['Name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($memberDetails['Email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($memberDetails['Phone'] ?? 'N/A'); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($memberDetails['MembershipType'] ?? 'N/A'); ?></p>
                            <p><strong>Expires:</strong> <?php echo htmlspecialchars(!empty($memberDetails['MembershipExpiry']) ? date("M j, Y", strtotime($memberDetails['MembershipExpiry'])) : 'N/A'); ?></p>
                            <p><strong>Status:</strong> <span class="<?php echo ($membershipStatus === 'Active') ? 'status-active' : (($membershipStatus === 'Expired') ? 'status-expired' : (($membershipStatus === 'No Expiry Date') ? 'status-no-expiry' : 'status-unknown')); ?>"> <?php echo htmlspecialchars($membershipStatus); ?> </span> </p>
                        </div>
                        <?php // Display appropriate link based on status - ensure paths use ../
                         if ($membershipStatus === 'Active'): ?>
                           <!-- Optional active message -->
                         <?php elseif ($membershipStatus === 'Expired'): ?>
                             <a href="../payments.php" class="mt-2 inline-block bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold py-1 px-3 rounded transition duration-150">Renew Now</a>
                         <?php else: ?>
                             <a href="../payments.php" class="mt-2 inline-block bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded transition duration-150">Complete Payment</a>
                         <?php endif; ?>
                        <a href="../edit_profile.php" class="mt-2 ml-1 inline-block text-indigo-600 hover:underline text-xs font-medium">Edit Profile</a>
                        <a href="../change_password.php" class="mt-2 ml-1 inline-block text-indigo-600 hover:underline text-xs font-medium">Change Password</a>
                    <?php elseif(!$db_error): ?> <p class="text-red-500 text-sm">Could not load profile details.</p> <?php endif; ?>
                </div>

                <!-- Upcoming Events Card -->
                <div class="bg-white p-6 rounded-lg shadow order-2">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">Upcoming Events</h3>
                     <!-- Featured Event -->
                     <div class="mb-6 border-b border-indigo-100 pb-4">
                         <img src="../images/cosmic-quest_(astronomy).jpg" alt="Cosmic Quest Event Image" class="w-full h-40 object-cover rounded-md mb-2 shadow-sm">
                         <h4 class="text-lg font-semibold text-indigo-700 mb-1 -mt-1">Cosmic Quest</h4>
                         <p class="text-sm text-gray-600 mb-2">Get ready to participate in "Cosmic Quest", a treasure hunt that will take you on a journey through the cosmos and our campus!</p>
                         <a href="../event_details.php?id=1" class="text-indigo-600 hover:underline font-medium text-xs">Learn More & Register →</a> <!-- Assuming ID is 1 -->
                     </div>
                     <!-- Dynamic List -->
                     <?php if (!empty($upcomingEvents)): ?>
                         <h4 class="text-md font-semibold text-gray-600 mb-3">More Upcoming Events:</h4>
                         <ul class="space-y-3 text-sm">
                             <?php foreach ($upcomingEvents as $event): ?> <li class="border-b border-gray-100 pb-2 last:border-b-0"> <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['EventName']); ?></p> <p class="text-gray-500"><i class="far fa-calendar-alt fa-fw mr-1"></i> <?php echo date("D, M j, Y", strtotime($event['EventDate'])); ?></p> <?php if(!empty($event['EventLocation'])): ?> <p class="text-gray-500"><i class="fas fa-map-marker-alt fa-fw mr-1"></i> <?php echo htmlspecialchars($event['EventLocation']); ?></p> <?php endif; ?> <a href="../event_details.php?id=<?php echo $event['EventID']; ?>" class="text-indigo-600 hover:underline font-medium block mt-1 text-xs">View Details / Register →</a> </li> <?php endforeach; ?>
                         </ul>
                     <?php elseif(!$db_error): ?> <p class="text-gray-500 text-sm">No other upcoming events scheduled.</p> <?php endif; ?>
                     <a href="../event_list.php" class="mt-4 inline-block text-indigo-600 hover:underline text-xs font-medium">View All Events</a>
                </div>

                 <!-- My Upcoming Registrations Card -->
                <div class="bg-white p-6 rounded-lg shadow order-3">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">My Upcoming Registrations</h3>
                      <?php if (!empty($myUpcomingRegistrations)): ?>
                         <ul class="space-y-2 text-sm">
                              <?php foreach ($myUpcomingRegistrations as $reg): ?> <li class="border-b border-gray-100 pb-1 last:border-b-0"> <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($reg['EventName']); ?></p> <p class="text-gray-500"><i class="far fa-calendar-alt fa-fw mr-1"></i> <?php echo date("D, M j, Y", strtotime($reg['EventDate'])); ?></p> <a href="../event_details.php?id=<?php echo $reg['EventID']; ?>" class="text-indigo-600 hover:underline text-xs">View Event</a> </li> <?php endforeach; ?>
                         </ul>
                      <?php elseif(!$db_error): ?> <p class="text-gray-500 text-sm">You are not registered for any upcoming events.</p> <?php endif; ?>
                      <a href="../my_registrations.php" class="mt-4 inline-block text-indigo-600 hover:underline text-xs font-medium">View All My Registrations</a>
                 </div>

            </div> <!-- End First Grid Row -->

             <!-- Second Row for Payments & Announcements -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                 <!-- Recent Payment History Card -->
                 <div class="bg-white p-6 rounded-lg shadow order-4">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">Recent Payment History</h3>
                      <?php if (!empty($recentPayments)): ?>
                         <ul class="space-y-2 text-sm">
                              <?php foreach ($recentPayments as $payment): ?> <li class="border-b border-gray-100 pb-2 last:border-b-0 flex justify-between items-center"> <div> <p class="font-semibold text-gray-800"> <?php echo htmlspecialchars($payment['DisplayDescription'] ?? 'Payment'); ?> </p> <p class="text-gray-500 text-xs"><i class="far fa-calendar-alt fa-fw mr-1"></i> <?php echo date("M j, Y", strtotime($payment['PaymentDate'])); ?></p> </div> <span class="font-semibold text-gray-700">₹<?php echo htmlspecialchars(number_format($payment['Amount'], 2)); ?></span> </li> <?php endforeach; ?>
                         </ul>
                      <?php elseif(!$db_error): ?> <p class="text-gray-500 text-sm">No recent payment history found.</p>
                      <?php else: ?> <p class="text-red-500 text-sm">Could not load payment history.</p> <?php endif; ?>
                       <a href="../my_payments.php" class="mt-4 inline-block text-indigo-600 hover:underline text-xs font-medium">View Full Payment History</a>
                 </div>

                  <!-- Recent Announcements Card -->
                 <div class="bg-white p-6 rounded-lg shadow order-5" id="announcements-section">
                     <h3 class="text-xl font-medium text-gray-700 mb-4">Recent Announcements</h3>
                      <?php if (!empty($recentAnnouncements)): ?>
                         <ul class="space-y-3">
                            <?php foreach ($recentAnnouncements as $announcement): ?> <li class="border-b border-gray-100 pb-3 last:border-b-0"> <p class="font-semibold text-indigo-800"><?php echo htmlspecialchars($announcement['Title']); ?></p> <p class="text-xs text-gray-500 mb-1"><?php echo date("M j, Y, g:i A", strtotime($announcement['PostDate'])); ?></p> <p class="text-sm text-gray-600 leading-snug"> <?php echo nl2br(htmlspecialchars(substr($announcement['Content'], 0, 120))); if (strlen($announcement['Content']) > 120) echo "..."; ?> </p> <a href="../backend/announcement_details.php?id=<?php echo $announcement['AnnouncementID']; ?>" class="text-indigo-600 hover:underline text-xs font-medium block mt-1"></a> </li> <?php endforeach; ?>
                         </ul>
                      <?php elseif(!$db_error): ?> <p class="text-gray-500 text-sm">No recent announcements.</p>
                      <?php else: ?> <p class="text-red-500 text-sm">Could not load announcements.</p> <?php endif; ?>
                       <a href="../announcements_list.php" class="mt-4 inline-block text-indigo-600 hover:underline text-xs font-medium">View All Announcements</a>
                 </div>

            </div>
             <!-- End Second Row -->

             <!-- Clubs Section -->
             <div class="bg-white p-6 rounded-lg shadow order-last" id="clubs-section">
                 <h3 class="text-xl font-medium text-gray-700 mb-5 border-b pb-2">Explore Our Clubs</h3>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <!-- Club divs -->
                    <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">DaVinci ART CLUB</h4> <p class="text-sm text-gray-600 leading-relaxed"> Unleash your inner artist!... </p> </div>
                    <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">Elixr Finance Club</h4> <p class="text-sm text-gray-600 leading-relaxed"> Decode the world of finance.... </p> </div>
                     <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">Astronomy Club</h4> <p class="text-sm text-gray-600 leading-relaxed"> Gaze beyond our world.... </p> </div>
                     <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">Cyberspace Club</h4> <p class="text-sm text-gray-600 leading-relaxed"> Navigate the digital frontier.... </p> </div>
                     <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">Motioncraft Animation Club</h4> <p class="text-sm text-gray-600 leading-relaxed"> Bring stories to life.... </p> </div>
                     <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">CodeX Club</h4> <p class="text-sm text-gray-600 leading-relaxed"> Crack the code!... </p> </div>
                     <div> <h4 class="text-md font-semibold text-indigo-700 mb-1">Endurance Club (Sports)</h4> <p class="text-sm text-gray-600 leading-relaxed"> Push your physical boundaries.... </p> </div>
                 </div> <!-- End inner clubs grid -->
             </div>
             <!-- End Clubs Section -->

        </main>
    </div>
</body>
</html>