<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

$error_message = '';
$success_message = '';

// Check for status messages from redirect
if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $success_message = "Password changed successfully!";
}
if(isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $error_map = [
        'missing_fields' => 'Please fill in all password fields.',
        'mismatch' => 'New passwords do not match.',
        'current_incorrect' => 'Incorrect current password entered.',
        'update_failed' => 'Failed to update password. Database error.',
        'prepare_failed' => 'Could not prepare password update process.',
        'db_error' => 'Database connection error.',
        'hashing_failed' => 'Password processing error.',
        'short_password' => 'New password is too short (minimum 8 characters recommended).' // Example
    ];
    $error_message = $error_map[$error_code] ?? 'An unknown error occurred changing password.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Manipal Club Portal</title>
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
                 <a href="edit_profile.php" class="text-gray-600 hover:text-indigo-600 mr-4">Edit Profile</a>
                 <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-md mx-auto mt-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Change Your Password</h1>

         <!-- Messages -->
        <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm" role="alert"> <?php echo $success_message; ?> </div> <?php endif; ?>
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert"> <?php echo $error_message; ?> </div> <?php endif; ?>

        <form action="backend/update_password.php" method="POST">
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password <span class="text-red-500">*</span></label>
                <input type="password" id="current_password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
             <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-red-500">*</span></label>
                <input type="password" id="new_password" name="new_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" minlength="8"> <!-- Example: Enforce min length in HTML -->
                 <p class="text-xs text-gray-500 mt-1">Minimum 8 characters recommended.</p>
            </div>
             <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password <span class="text-red-500">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" minlength="8">
            </div>
            <div class="flex justify-end">
                 <a href="Dashboard/main_board.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"> Change Password </button>
            </div>
        </form>
    </div>
</body>
</html>