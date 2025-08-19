<?php
session_start();
require 'Dashboard/config.php'; // Path relative to this file in root

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// --- Determine Mode (Add or Edit) ---
$edit_mode = false;
$member_id = null;
$member_data = [ // Default empty values
    'Name' => '', 'Email' => '', 'Phone' => '',
    'MembershipType' => '', 'MembershipExpiry' => '', 'IsAdmin' => 0
];
$page_title = 'Add New Member';
$error_message = '';
$success_message = '';

if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $member_id = (int)$_GET['id'];
    $page_title = 'Edit Member Details';

    // --- Fetch existing member data ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        // Fetch ALL columns needed for the form, including IsAdmin
        $sql = "SELECT Name, Email, Phone, MembershipType, MembershipExpiry, IsAdmin FROM Member WHERE MemberID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $member_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($data = mysqli_fetch_assoc($result)) {
                $member_data = $data; // Pre-fill form data
            } else {
                $error_message = "Member with ID " . $member_id . " not found.";
                $edit_mode = false; // Switch back to Add mode conceptually
                $page_title = 'Add New Member'; // Reset title
                $member_id = null; // Clear ID
            }
            mysqli_stmt_close($stmt);
        } else { $error_message = "Error preparing to fetch member data."; error_log(mysqli_error($conn));}
        mysqli_close($conn);
    } else { $error_message = "Database connection error."; }
}

// Check for status messages from save_member.php redirect
if(isset($_GET['status']) && $_GET['status'] == 'success') { $success_message = "Member saved successfully!"; }
if(isset($_GET['error'])) { $error_message = htmlspecialchars(urldecode($_GET['error'])); }

// Allowed membership types for dropdown
$allowed_types = ['Monthly', 'Quarterly', 'Half Yearly', 'Yearly', 'Admin', 'Honorary']; // Added Admin/Honorary examples

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; } select { appearance: menulist; }</style>
</head>
<body class="bg-gray-100">
     <!-- Navbar -->
     <nav class="bg-white shadow-sm"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/admin_dashboard.php" class="text-xl font-bold text-indigo-600">Admin Panel</a> <div> <a href="Dashboard/admin_dashboard.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="admin_manage_members.php" class="text-gray-600 hover:text-indigo-600 mr-4">Manage Members</a> <a href="backend/admin_logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-2xl mx-auto mt-8 mb-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $page_title; ?></h1>

        <!-- Messages -->
        <?php if ($success_message): ?> <div class="mb-4 p-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-sm" role="alert"> <?php echo $success_message; ?> </div> <?php endif; ?>
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert"> <?php echo $error_message; ?> </div> <?php endif; ?>

        <form action="backend/save_member.php" method="POST">
             <!-- Hidden field for MemberID in edit mode -->
            <?php if ($edit_mode): ?>
                <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
            <?php endif; ?>

             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($member_data['Name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                 <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member_data['Email']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                 <div>
                     <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member_data['Phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="membership_type" class="block text-sm font-medium text-gray-700 mb-1">Membership Type</label>
                    <select id="membership_type" name="membership_type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="" <?php echo empty($member_data['MembershipType']) ? 'selected' : ''; ?>>-- Select --</option>
                         <?php foreach ($allowed_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo ($member_data['MembershipType'] == $type) ? 'selected' : ''; ?>>
                                <?php echo $type; ?>
                            </option>
                         <?php endforeach; ?>
                    </select>
                </div>
                 <div>
                     <label for="membership_expiry" class="block text-sm font-medium text-gray-700 mb-1">Membership Expiry</label>
                    <input type="date" id="membership_expiry" name="membership_expiry" value="<?php echo htmlspecialchars($member_data['MembershipExpiry'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if pending payment or no expiry.</p>
                </div>
                 <div>
                    <label for="is_admin" class="block text-sm font-medium text-gray-700 mb-1">Admin Status</label>
                    <select id="is_admin" name="is_admin" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="0" <?php echo ($member_data['IsAdmin'] == 0) ? 'selected' : ''; ?>>No (Regular Member)</option>
                        <option value="1" <?php echo ($member_data['IsAdmin'] == 1) ? 'selected' : ''; ?>>Yes (Administrator)</option>
                    </select>
                 </div>
             </div> <!-- end grid -->

             <!-- Password Section (Only show for ADD mode or if specifically resetting) -->
             <div class="mt-6 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3">Password</h3>
                <?php if ($edit_mode): ?>
                     <p class="text-sm text-gray-500 mb-4">Leave these fields blank to keep the current password. Enter a new password in both fields below to change it.</p>
                <?php else: // Add Mode ?>
                     <p class="text-sm text-gray-500 mb-4">Set the initial password for the new member. <span class="text-red-500">* Required</span></p>
                <?php endif; ?>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password <?php echo $edit_mode ? '' : '<span class="text-red-500">*</span>'; ?></label>
                        <input type="password" id="password" name="password" <?php echo $edit_mode ? '' : 'required'; ?> class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                     </div>
                     <div>
                         <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <?php echo $edit_mode ? '' : '<span class="text-red-500">*</span>'; ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" <?php echo $edit_mode ? '' : 'required'; ?> class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                     </div>
                 </div>
             </div>

             <!-- Form Actions -->
            <div class="flex justify-end mt-8">
                <a href="admin_manage_members.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-save mr-2"></i> Save Member
                </button>
            </div>
        </form>
    </div>
</body>
</html>