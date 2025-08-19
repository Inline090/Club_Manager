<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

// Get status and details from URL
$status = $_GET['status'] ?? 'failed';
$plan = isset($_GET['plan']) ? htmlspecialchars(urldecode($_GET['plan'])) : '';
$expiry = isset($_GET['expiry']) ? htmlspecialchars(urldecode($_GET['expiry'])) : '';
$formattedExpiry = !empty($expiry) ? date("F j, Y", strtotime($expiry)) : 'N/A';

$message = '';
$isSuccess = ($status === 'success' || $status === 'success_log_failed');

if ($isSuccess) {
     $message = "Payment Confirmed! Your " . $plan . " membership plan is now active.";
     if ($formattedExpiry != 'N/A') {
         $message .= " Your membership expires on " . $formattedExpiry . ".";
     }
     if ($status === 'success_log_failed') {
         $message .= " (Note: There was an issue logging this payment, please contact support if needed.)";
     }
} else {
     $message = "There was an issue processing your payment simulation. Please try again or contact support.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm mb-8"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a> <div> <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

     <div class="max-w-lg mx-auto mt-10 text-center">
        <div class="p-8 bg-white rounded-lg shadow">
            <?php if ($isSuccess): ?>
                <div class="text-green-500 mb-4">
                    <i class="fas fa-check-circle fa-3x"></i>
                </div>
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Payment Successful!</h1>
            <?php else: ?>
                 <div class="text-red-500 mb-4">
                     <i class="fas fa-times-circle fa-3x"></i>
                 </div>
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Payment Failed</h1>
            <?php endif; ?>

            <p class="text-gray-600 mb-8">
                <?php echo $message; ?>
            </p>

            <a href="Dashboard/main_board.php" class="inline-block py-2 px-5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Return to Dashboard
            </a>
        </div>
    </div>

</body>
</html>