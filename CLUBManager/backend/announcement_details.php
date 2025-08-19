<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require '../Dashboard/config.php'; // Path relative to this file in root

// Get Announcement ID from URL and validate
$announcementId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$announcement = null;
$error_message = '';

if (!$announcementId || $announcementId <= 0) {
    $error_message = "Invalid announcement specified.";
} else {
    // --- Database Connection ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        $error_message = "Database connection error.";
        error_log("Announcement Details DB Connection Failed: " . mysqli_connect_error());
    } else {
        // --- Fetch Announcement Details ---
        $sql = "SELECT Title, Content, PostDate FROM Announcements WHERE AnnouncementID = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $announcementId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (!($announcement = mysqli_fetch_assoc($result))) {
                // Announcement ID from URL does not exist
                $error_message = "Announcement not found.";
            }
            mysqli_stmt_close($stmt);
        } else {
            // Error preparing the SQL statement
            $error_message = "Error fetching announcement details.";
            error_log("Announcement Details Fetch Prepare Error: " . mysqli_error($conn));
        }
        mysqli_close($conn); // Close connection
    } // End if $conn
} // End if validation passed

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $announcement ? htmlspecialchars($announcement['Title']) : 'Announcement'; ?> - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
        /* Add styles for prose if needed for better text formatting */
        .prose { max-width: 75ch; /* Adjust max width as needed */ }
        .prose h1, .prose h2, .prose h3 { margin-bottom: 0.5em; font-weight: 600; }
        .prose p { margin-bottom: 1em; line-height: 1.7; } /* Increased line height */
        .prose strong { font-weight: 600; }
    </style>
</head>
<body class="bg-gray-100">
     <!-- Navbar -->
     <nav class="bg-white shadow-sm mb-8">
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="../Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a>
            <div>
                 <a href="../Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a>
                 <!-- Optional: Link back to a full announcement list -->
                 <!-- <a href="announcements_list.php" class="text-gray-600 hover:text-indigo-600 mr-4">All Announcements</a> -->
                 <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow mb-10">
        <?php if ($announcement): // Display content if announcement was found ?>
            <!-- Announcement Header -->
            <div class="border-b pb-4 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($announcement['Title']); ?></h1>
                <p class="text-sm text-gray-500">
                    <i class="far fa-calendar-alt fa-fw mr-1"></i> Posted on <?php echo date("F j, Y, g:i A", strtotime($announcement['PostDate'])); ?>
                </p>
            </div>

            <!-- Announcement Content -->
            <div class="prose prose-sm sm:prose-base max-w-none text-gray-800">
                 <?php echo nl2br(htmlspecialchars($announcement['Content'])); // Use nl2br to render line breaks correctly ?>
            </div>

            <!-- Back Link -->
             <div class="mt-8 pt-6 border-t">
                 <a href="../Dashboard/main_board.php#announcements-section" class="text-sm text-indigo-600 hover:underline">
                     ← Back to Dashboard Announcements
                 </a>
                 <!-- Or link to announcements_list.php if you create it -->
                 <!-- <a href="announcements_list.php" class="ml-4 text-sm text-indigo-600 hover:underline">View All Announcements</a> -->
             </div>


        <?php else: // Announcement not found or initial error ?>
             <h1 class="text-2xl font-semibold text-red-600 mb-4">Announcement Not Found</h1>
             <p class="text-gray-600"><?php echo htmlspecialchars($error_message ?: "The requested announcement could not be loaded."); ?></p>
             <a href="../Dashboard/main_board.php" class="mt-6 inline-block text-indigo-600 hover:underline">← Back to Dashboard</a>
             <!-- Or link to announcements_list.php -->
             <!-- <a href="announcements_list.php" class="mt-6 inline-block text-indigo-600 hover:underline">← Back to Announcements List</a> -->
        <?php endif; ?>
    </div>
</body>
</html>