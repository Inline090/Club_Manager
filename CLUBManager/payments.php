<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Member';
$error_message = '';
$success_message = ''; // Might not be needed here, confirmation is on next page

// Check for error messages from backend redirect
if(isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $error_map = [
        'missing_plan' => 'Please select a membership plan.',
        'invalid_plan' => 'Invalid membership plan selected.',
        'db_error' => 'Database connection error. Please try again.',
        'update_failed' => 'Failed to update membership. Please contact support.',
        'prepare_failed' => 'Could not process payment request.',
        'payment_log_failed' => 'Membership updated, but payment logging failed. Contact support if needed.' // Optional error
    ];
    $error_message = $error_map[$error_code] ?? 'An unknown error occurred.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Payment - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
         /* Add styles for select arrow if needed, similar to register.php */
        select {
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236c757d%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat; background-position: right 10px center; background-size: 10px auto; padding-right: 30px;
        }
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

    <div class="max-w-lg mx-auto mt-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Membership Payment / Renewal</h1>
        <p class="text-center text-gray-600 text-sm mb-6">Select your desired membership plan below to activate or extend your membership.</p>

        <!-- Error Messages -->
        <?php if ($error_message): ?>
             <div class="mb-4 p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-sm" role="alert">
                 <?php echo $error_message; ?>
             </div>
        <?php endif; ?>

        <form action="backend/process_payment.php" method="POST" onsubmit="return confirm('Confirm payment simulation for the selected plan?');">
             <div class="mb-6">
                <label for="membership_type" class="block text-sm font-medium text-gray-700 mb-1">Select Plan <span class="text-red-500">*</span></label>
                <select id="membership_type" name="membership_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base">
                    <option value="" selected disabled>-- Choose Duration --</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly (3 Months)</option>
                    <option value="Half Yearly">Half Yearly (6 Months)</option>
                    <option value="Yearly">Yearly (12 Months)</option>
                </select>
                 <!-- Optionally display prices here if you have them -->
                 <!-- <p class="text-xs text-gray-500 mt-1">Yearly plan offers the best value!</p> -->
            </div>

            <!-- Add a hidden field for CSRF token later if needed -->

            <div class="mt-8">
                 <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                    <i class="fas fa-check-circle mr-2"></i> Confirm Payment (Simulated)
                </button>
            </div>
             <p class="text-xs text-gray-500 mt-4 text-center">Note: This is a simulated payment. Clicking confirms your selected plan and updates your membership expiry date.</p>
        </form>
    </div>
</body>
</html>