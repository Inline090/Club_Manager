<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

// Path to config is relative to this file (edit_profile.php in root)
require 'Dashboard/config.php';

$userId = $_SESSION['user_id'] ?? 0;
$current_name = '';
$current_email = '';
$current_phone = '';
$error_message = '';
$success_message = '';

// Fetch current data
if ($userId > 0) {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        $sql = "SELECT Name, Email, Phone FROM Member WHERE MemberID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($user = mysqli_fetch_assoc($result)) {
                $current_name = $user['Name'];
                $current_email = $user['Email'];
                $current_phone = $user['Phone'];
            } else { $error_message = "Could not find your profile."; }
            mysqli_stmt_close($stmt);
        } else { $error_message = "Error preparing profile fetch: " . mysqli_error($conn); error_log($error_message); }
        mysqli_close($conn);
    } else { $error_message = "Database connection error."; error_log("DB Connect error in edit_profile"); }
} else { $error_message = "Invalid session."; }

// Check for status messages from redirect
if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $success_message = "Profile updated successfully!";
}
if(isset($_GET['error'])) {
    $error_message = htmlspecialchars(urldecode($_GET['error'])); // Decode URL encoded errors
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a>
            <div>
                 <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a>
                 <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-xl mx-auto mt-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Edit Your Profile</h1>

        <!-- Messages -->
        <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm" role="alert"> <?php echo $success_message; ?> </div> <?php endif; ?>
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert"> <?php echo $error_message; ?> </div> <?php endif; ?>

        <?php if ($userId > 0 && !($error_message && !isset($_GET['error'])) ): // Show form if user loaded or if there was a submission error ?>
        <form action="backend/update_profile.php" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (Cannot be changed)</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($current_email); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 focus:outline-none" readonly>
                 <input type="hidden" name="email" value="<?php echo htmlspecialchars($current_email); ?>"> <!-- Send email just in case -->
            </div>
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_name); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
             <div class="mb-6">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($current_phone ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex justify-end">
                <a href="Dashboard/main_board.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"> Save Changes </button>
            </div>
        </form>
        <?php elseif(!$error_message): ?> <p class="text-gray-500">Loading profile...</p> <?php endif; ?>
    </div>
</body>
</html>