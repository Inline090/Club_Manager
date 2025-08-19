<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require 'Dashboard/config.php'; // Path relative to this file in root

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$announcements = [];
$fetch_error = '';

if ($conn) {
    // Fetch ALL announcements, most recent first
    $sql = "SELECT AnnouncementID, Title, Content, PostDate FROM Announcements ORDER BY PostDate DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
        mysqli_free_result($result);
    } else {
        $fetch_error = "Could not retrieve announcements.";
        error_log("Announcements List Fetch Error: ".mysqli_error($conn));
    }
    mysqli_close($conn);
} else {
    $fetch_error = "Database connection error.";
    error_log("Announcements List DB Connect Error: ".mysqli_connect_error());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Announcements - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
     <nav class="bg-white shadow-sm mb-8">
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a>
            <div>
                 <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a>
                 <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
             <h1 class="text-3xl font-semibold text-gray-800">All Announcements</h1>
             <a href="Dashboard/main_board.php#announcements-section" class="text-sm text-indigo-600 hover:underline">← Back to Dashboard</a>
        </div>


         <?php if ($fetch_error): ?>
             <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert">
                 <?php echo htmlspecialchars($fetch_error); ?>
             </div>
         <?php endif; ?>

        <!-- Announcements List -->
        <div class="space-y-6">
             <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-1"><?php echo htmlspecialchars($announcement['Title']); ?></h2>
                        <p class="text-xs text-gray-500 mb-3">
                            <i class="far fa-calendar-alt fa-fw mr-1"></i> Posted on <?php echo date("F j, Y, g:i A", strtotime($announcement['PostDate'])); ?>
                        </p>
                        <p class="text-sm text-gray-700 leading-relaxed">
                             <?php
                                // Show excerpt (e.g., first 200 chars) on the list page
                                $excerpt = substr($announcement['Content'], 0, 200);
                                echo nl2br(htmlspecialchars($excerpt));
                                if (strlen($announcement['Content']) > 200) echo "...";
                            ?>
                        </p>
                        <a href="backend/announcement_details.php?id=<?php echo $announcement['AnnouncementID']; ?>" class="inline-block mt-3 text-indigo-600 hover:underline text-sm font-medium">Read Full Announcement →</a>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!$fetch_error): ?>
                 <div class="bg-white p-6 rounded-lg shadow text-center">
                     <p class="text-gray-500">There are no announcements at this time.</p>
                 </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>