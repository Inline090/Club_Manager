<?php
session_start();
require 'Dashboard/config.php'; // Path relative to root

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

// Database Connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Fetch All Transactions
$transactions = [];
$sql = "SELECT p.PaymentID, p.PaymentDate, p.Amount, p.TransactionType, p.Description,
               m.Name as MemberName, e.EventName
        FROM Payment p
        LEFT JOIN Member m ON p.MemberID = m.MemberID
        LEFT JOIN Event e ON p.EventID = e.EventID
        ORDER BY p.PaymentDate DESC, p.PaymentID DESC"; // Show most recent first

$result = mysqli_query($conn, $sql);
$fetch_error = '';
$totalIncome = 0.00;
$totalExpense = 0.00;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Construct display description
        $displayDesc = $row['Description']; // Start with DB description if available

        // If no DB description, use Transaction Type as base
        if (empty($displayDesc)) {
            $displayDesc = $row['TransactionType'] ?? 'Transaction';
        }

        // Add context ONLY if the base description/type doesn't already imply it
        // And if the MemberName or EventName exists
        if (!empty($row['MemberName']) && $row['TransactionType'] !== 'Membership Fee' && strpos($displayDesc, 'Membership') === false && strpos($displayDesc, $row['MemberName']) === false) {
            // Add Member context if it's NOT a membership fee and member name isn't already there
            $displayDesc .= ' (Member: ' . htmlspecialchars($row['MemberName']) . ')';
        } elseif (!empty($row['EventName']) && !in_array($row['TransactionType'], ['Event Fee', 'Event Payment']) && strpos($displayDesc, 'Event') === false && strpos($displayDesc, $row['EventName']) === false) {
             // Add Event context if it's not explicitly an Event Fee/Payment type and event name isn't already there
            $displayDesc .= ' (Event: ' . htmlspecialchars($row['EventName']) . ')';
        }

        $row['DisplayDescription'] = $displayDesc; // Assign final description

        // Calculate totals
        if ($row['Amount'] > 0) {
            $totalIncome += $row['Amount'];
        } else {
            $totalExpense += abs($row['Amount']);
        }
        $transactions[] = $row;
    }
    mysqli_free_result($result);
}  else {
    error_log("Error fetching financial transactions: " . mysqli_error($conn));
    $fetch_error = "Could not retrieve transaction list.";
}
mysqli_close($conn);

// Calculate Net Balance
$netBalance = $totalIncome - $totalExpense;

// Get Status Messages from URL
$status_message = ''; $status_type = '';
if (isset($_GET['status'])) {
    if($_GET['status'] == 'expense_added') { $status_message = 'Expense recorded successfully.'; $status_type = 'success'; }
    if($_GET['status'] == 'income_added') { $status_message = 'Income recorded successfully.'; $status_type = 'success'; } // If adding income later
    if($_GET['status'] == 'deleted') { $status_message = 'Transaction deleted successfully.'; $status_type = 'success'; }
    // Add more status messages as needed
}
if(isset($_GET['error'])) { $status_message = htmlspecialchars(urldecode($_GET['error'])); $status_type = 'error'; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Finances - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
             <div class="p-6 text-center border-b border-gray-200"> <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1> <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p> </div>
             <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                 <a href="Dashboard/admin_dashboard.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-gauge ..."></i> Dashboard</a>
                 <a href="admin_manage_members.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-users ..."></i> Manage Members</a>
                 <a href="admin_manage_events.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-calendar-check ..."></i> Manage Events</a>
                 <a href="admin_manage_announcements.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg group"> <i class="fa-solid fa-bullhorn ..."></i> Manage Announcements</a>
                 <a href="admin_manage_finances.php" class="flex items-center px-4 py-2.5 text-gray-700 bg-indigo-50 text-indigo-600 font-semibold rounded-lg group"> <i class="fa-solid fa-dollar-sign w-6 h-6 mr-3 text-indigo-500"></i> Manage Finances</a> <!-- Highlighted -->
             </nav>
              <div class="p-4 border-t border-gray-200 mt-auto"> <a href="backend/admin_logout.php" class="flex items-center ..."> Logout </a> </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
             <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
                <h2 class="text-3xl font-semibold text-gray-800">Club Finances</h2>
                <div class="flex gap-2">
                     <a href="admin_add_transaction.php?type=expense" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition duration-150"> <i class="fas fa-minus-circle mr-2"></i> Add Expense </a>
                     <a href="admin_add_transaction.php?type=income" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition duration-150"> <i class="fas fa-plus-circle mr-2"></i> Add Income </a>
                     <!-- Add button for fee management later -->
                     <!-- <button class="bg-blue-500 ...">Manage Fees</button> -->
                </div>
             </div>

             <!-- Status Messages -->
             <?php if ($status_message): ?> <div class="mb-4 p-4 rounded-lg <?php echo ($status_type === 'success') ? 'bg-green-100 ... text-green-700' : 'bg-red-100 ... text-red-700'; ?>"> <?php echo htmlspecialchars($status_message); ?> </div> <?php endif; ?>
             <?php if ($fetch_error): ?> <div class="mb-4 p-4 ... bg-red-100 ..."> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

            <!-- Summary Totals -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-sm font-medium text-green-600">Total Income</p>
                    <p class="text-2xl font-semibold">₹<?php echo number_format($totalIncome, 2); ?></p>
                </div>
                 <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-sm font-medium text-red-600">Total Expenses</p>
                    <p class="text-2xl font-semibold">₹<?php echo number_format($totalExpense, 2); ?></p>
                </div>
                 <div class="bg-white p-4 rounded-lg shadow text-center">
                    <p class="text-sm font-medium text-blue-600">Net Balance</p>
                    <p class="text-2xl font-semibold <?php echo ($netBalance >= 0) ? 'text-green-700' : 'text-red-700'; ?>">₹<?php echo number_format($netBalance, 2); ?></p>
                </div>
            </div>


             <!-- Transactions Table -->
             <h3 class="text-xl font-semibold text-gray-700 mb-4">Transaction History</h3>
             <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Description / Type</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap"><?php echo date("d M Y", strtotime($trans['PaymentDate'])); ?></td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($trans['DisplayDescription']); ?>
                                        <span class="block text-xs text-gray-500 italic"><?php echo htmlspecialchars($trans['TransactionType'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium <?php echo ($trans['Amount'] >= 0) ? 'text-green-700' : 'text-red-700'; ?>">
                                        <?php echo ($trans['Amount'] < 0 ? '-' : ''); ?>₹<?php echo number_format(abs($trans['Amount']), 2); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center space-x-2">
                                        <!-- Edit Link Removed -->

                                        <!-- Delete Form/Button -->
                                        <form action="backend/delete_transaction.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this transaction?');">
                                            <input type="hidden" name="payment_id" value="<?php echo $trans['PaymentID']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr> <td colspan="4" class="text-center px-6 py-10 text-gray-500"> No financial transactions found. </td> </tr> <!-- Adjusted colspan -->
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </main>
    </div>
</body>
</html>