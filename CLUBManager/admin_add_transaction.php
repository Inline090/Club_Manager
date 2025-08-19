<?php
session_start();
// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.html?error=not_logged_in"); exit;
}

$transaction_type = $_GET['type'] ?? 'expense'; // Default to expense if not specified
$page_title = ($transaction_type === 'income') ? 'Add Club Income' : 'Add Club Expense';
$amount_label = ($transaction_type === 'income') ? 'Income Amount (₹)' : 'Expense Amount (₹)';
$amount_color = ($transaction_type === 'income') ? 'green' : 'red'; // Just for potential styling

$error_message = '';
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
    <nav class="bg-white shadow-sm"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/admin_dashboard.php" class="text-xl font-bold text-indigo-600">Admin Panel</a> <div> <a href="Dashboard/admin_dashboard.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="admin_manage_finances.php" class="text-gray-600 hover:text-indigo-600 mr-4">Manage Finances</a> <a href="backend/admin_logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-xl mx-auto mt-8 mb-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $page_title; ?></h1>

        <!-- Messages -->
        <?php if ($error_message): ?> <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert"> <?php echo $error_message; ?> </div> <?php endif; ?>

        <form action="backend/save_transaction.php" method="POST">
            <!-- Hidden field to indicate type -->
            <input type="hidden" name="transaction_nature" value="<?php echo htmlspecialchars($transaction_type); ?>">

            <div class="mb-4">
                <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input type="date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

             <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                <input type="text" id="description" name="description" required maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

             <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1"><?php echo $amount_label; ?> <span class="text-red-500">*</span></label>
                <input type="number" id="amount" name="amount" required min="0.01" step="0.01" placeholder="Enter amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
             </div>

            <!-- Optional fields like category, uploaded receipt etc. could be added here -->

             <!-- Form Actions -->
            <div class="flex justify-end mt-8">
                <a href="admin_manage_finances.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3"> Cancel </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-<?php echo $amount_color; ?>-600 hover:bg-<?php echo $amount_color; ?>-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-<?php echo $amount_color; ?>-500">
                    <i class="fas fa-save mr-2"></i> Record <?php echo ucfirst($transaction_type); ?>
                </button>
            </div>
        </form>
    </div>
</body>
</html>