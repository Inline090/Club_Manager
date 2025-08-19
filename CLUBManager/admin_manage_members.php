<?php
session_start();
// Use require for config as it's essential
// Make sure this path is correct, likely just 'config.php' if it's in the same backend folder
require 'Dashboard/config.php';

// Check if admin is logged in (uses the session set by admin_login_process.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin_login.html?error=not_logged_in"); // Redirect relative to current file
    exit;
}

// --- Database Connection ---
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// --- Fetch Members ---
$members = [];
// *** MODIFIED: Use 'Member' table and remove 'IsAdmin' from select list ***
$sql = "SELECT MemberID, Name, Email, Phone, MembershipType, MembershipExpiry
        FROM Member -- Changed table name
        ORDER BY Name ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate membership status (logic remains the same)
        $status = 'Unknown'; $status_color = 'text-gray-600';
        if (!empty($row['MembershipExpiry'])) {
            $expiryDate = strtotime($row['MembershipExpiry']);
            $today = strtotime(date('Y-m-d'));
            if ($expiryDate >= $today) {
                $status = 'Active'; $status_color = 'text-green-600 font-semibold';
            } else {
                $status = 'Expired'; $status_color = 'text-red-600 font-semibold';
            }
        } else { $status = 'No Expiry'; $status_color = 'text-yellow-600'; }
        $row['MembershipStatus'] = $status;
        $row['StatusColor'] = $status_color;
        $members[] = $row;
    }
} else {
    error_log("Error fetching members: " . mysqli_error($conn));
    $fetch_error = "Could not retrieve member list.";
}

mysqli_close($conn);

// (Getting status messages remains the same)
$status_message = ''; $status_type = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added': $status_message = 'Member added successfully.'; $status_type = 'success'; break;
        case 'updated': $status_message = 'Member updated successfully.'; $status_type = 'success'; break;
        case 'deleted': $status_message = 'Member deleted successfully.'; $status_type = 'success'; break;
        case 'delete_failed': $status_message = 'Failed to delete member.'; $status_type = 'error'; break;
        case 'not_found': $status_message = 'Member not found for deletion.'; $status_type = 'error'; break; // Added case
    }
}
if (isset($_GET['error'])) { $status_message = htmlspecialchars($_GET['error']); $status_type = 'error'; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css');
        html { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #c5c5c5; border-radius: 3px; } ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
             <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p> </div>
             <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <a href="Dashboard/admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-gauge w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Dashboard Home </a>
                 <a href="admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-users w-6 h-6 mr-3 text-indigo-500"></i> Manage Members </a>
                 <a href="admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-calendar-check w-6 h-6 mr-3 text-gray-400 group-hover:text-indigo-500"></i> Manage Events </a>
                 <!-- Add other admin links here, point relative path correctly -->
             </nav>
             <div class="p-4 border-t border-gray-200 mt-auto">
                 <a href="admin_logout.php" class="flex items-center text-sm text-gray-500 hover:text-indigo-600"> <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2"></i> Logout </a>
             </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
             <div class="flex justify-between items-center mb-6">
                 <h2 class="text-3xl font-semibold text-gray-800">Manage Members</h2>
                 <a href="admin_edit_member.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-sm"><i class="fas fa-user-plus mr-2"></i> Add New Member</a>
             </div>

             <!-- Status Messages Area -->
             <?php if ($status_message): ?> <div class="mb-4 p-4 rounded-lg <?php echo ($status_type === 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>" role="alert"> <?php echo htmlspecialchars($status_message); ?> </div> <?php endif; ?>
             <?php if (isset($fetch_error)): ?> <div class="mb-4 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert"> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

             <!-- Members Table -->
             <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Expiry</th>
                            <th class="px-6 py-3">Status</th>
                            <!-- **** REMOVED Admin Column Header **** -->
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($members)): ?>
                            <?php foreach ($members as $member): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['MemberID']); ?></td>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($member['Name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['Email']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['Phone'] ?? '-'); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($member['MembershipType'] ?? '-'); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars(!empty($member['MembershipExpiry']) ? date("d M Y", strtotime($member['MembershipExpiry'])) : '-'); ?></td>
                                    <td class="px-6 py-4"><span class="<?php echo $member['StatusColor']; ?>"><?php echo htmlspecialchars($member['MembershipStatus']); ?></span></td>
                                    <!-- **** REMOVED Admin Data Cell **** -->
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <!-- Edit Link (Points one level up) -->
                                        <a href="admin_edit_member.php?id=<?php echo $member['MemberID']; ?>" class="text-indigo-600 hover:text-indigo-800" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                        <!-- Delete Form (Points to correct path) -->
                                        <form action="delete_member.php" method="POST" class="inline-block" onsubmit="return confirm('Delete member: <?php echo htmlspecialchars(addslashes($member['Name'])); ?>?');">
                                            <input type="hidden" name="member_id" value="<?php echo $member['MemberID']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- *** MODIFIED colspan *** -->
                            <tr><td colspan="8" class="text-center px-6 py-10 text-gray-500"> No members found. <a href="admin_edit_member.php" class="text-indigo-600 hover:underline">Add the first one?</a> </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </main>
    </div>
</body>
</html>