<?php
session_start();
// Redirect if user is not logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require 'Dashboard/config.php'; // Path relative to this file in root
$userId = $_SESSION['user_id'] ?? 0;

$memberDetails = null;
$lastPayment = null;
$fetch_error = '';
$membershipStatus = 'Unknown'; // Recalculate here for accuracy

if ($userId > 0) {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        // Fetch Member Details
        $sql_member = "SELECT Name, MembershipType, MembershipExpiry FROM Member WHERE MemberID = ?";
        $stmt_member = mysqli_prepare($conn, $sql_member);
        if($stmt_member) {
            mysqli_stmt_bind_param($stmt_member, "i", $userId);
            mysqli_stmt_execute($stmt_member);
            $result_member = mysqli_stmt_get_result($stmt_member);
            if ($details = mysqli_fetch_assoc($result_member)) {
                $memberDetails = $details;
                 // Calculate status
                 if (!empty($memberDetails['MembershipExpiry'])) {
                    $expiryDate = strtotime($memberDetails['MembershipExpiry']);
                    $today = strtotime(date('Y-m-d'));
                    $membershipStatus = ($expiryDate >= $today) ? 'Active' : 'Expired'; // Should be Active if they landed here
                } else { $membershipStatus = 'No Expiry Date'; }
            } else { $fetch_error .= " Could not load profile details."; }
            mysqli_stmt_close($stmt_member);
        } else { $fetch_error .= " Error fetching profile."; error_log(mysqli_error($conn)); }

        // Fetch Last Membership Payment (Example: identifies by type/description)
        // Adjust WHERE clause if your payment types/descriptions are different
        $sql_last_payment = "SELECT PaymentDate, Amount, TransactionType
                             FROM Payment
                             WHERE MemberID = ?
                               AND (TransactionType LIKE '%Membership%' OR TransactionType IN ('Monthly', 'Quarterly', 'Half Yearly', 'Yearly'))
                             ORDER BY PaymentDate DESC LIMIT 1";
        $stmt_last_payment = mysqli_prepare($conn, $sql_last_payment);
        if ($stmt_last_payment) {
             mysqli_stmt_bind_param($stmt_last_payment, "i", $userId);
             mysqli_stmt_execute($stmt_last_payment);
             $result_last_payment = mysqli_stmt_get_result($stmt_last_payment);
             $lastPayment = mysqli_fetch_assoc($result_last_payment); // Will be null if no matching payment found
             mysqli_stmt_close($stmt_last_payment);
        } else { $fetch_error .= " Error fetching payment history."; error_log(mysqli_error($conn)); }

        mysqli_close($conn);
    } else { $fetch_error = "Database connection error."; }
} else { $fetch_error = "Invalid session."; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Status - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }
        body { font-family: 'Inter', sans-serif; }
        .status-active { color: #16a34a; font-weight: 600; }
        .status-expired { color: #dc2626; font-weight: 600; }
        .status-no-expiry { color: #ca8a04; font-weight: 600; }
        .status-unknown { color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm mb-8"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a> <div> <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-xl mx-auto mt-10 p-8 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Your Membership Status</h1>

        <?php if ($fetch_error): ?>
             <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert">
                 <?php echo htmlspecialchars($fetch_error); ?>
             </div>
        <?php endif; ?>

        <?php if ($memberDetails): ?>
            <div class="space-y-3 text-center border border-gray-200 rounded-md p-6 mb-6">
                 <p class="text-lg">Current Plan: <span class="font-semibold text-indigo-700"><?php echo htmlspecialchars($memberDetails['MembershipType'] ?? 'N/A'); ?></span></p>
                 <p class="text-lg">Status:
                    <span class="<?php echo ($membershipStatus === 'Active') ? 'status-active' : (($membershipStatus === 'Expired') ? 'status-expired' : (($membershipStatus === 'No Expiry Date') ? 'status-no-expiry' : 'status-unknown')); ?>">
                         <?php echo htmlspecialchars($membershipStatus); ?>
                    </span>
                 </p>
                <p class="text-lg">Expires On: <span class="font-semibold"><?php echo htmlspecialchars(!empty($memberDetails['MembershipExpiry']) ? date("F j, Y", strtotime($memberDetails['MembershipExpiry'])) : 'N/A'); ?></span></p>
            </div>

            <?php if($lastPayment): ?>
             <div class="text-center text-sm text-gray-600 mb-8">
                 <p>Last Recorded Payment:</p>
                 <p>Date: <?php echo date("M j, Y", strtotime($lastPayment['PaymentDate'])); ?> | Amount: ₹<?php echo number_format($lastPayment['Amount'], 2); ?> | Type: <?php echo htmlspecialchars($lastPayment['TransactionType'] ?? 'Membership'); ?></p>
             </div>
            <?php endif; ?>

             <div class="text-center">
                 <p class="text-sm text-gray-600 mb-4">Need to renew or change your plan?</p>
                <a href="payments.php" class="inline-block py-2 px-5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go to Payment/Renewal Page
                </a>
             </div>

        <?php elseif (!$fetch_error): ?>
            <p class="text-center text-red-500">Could not load your membership details.</p>
        <?php endif; ?>

        <div class="mt-8 text-center">
             <a href="Dashboard/main_board.php" class="text-sm text-indigo-600 hover:underline">← Back to Dashboard</a>
        </div>
    </div>

</body>
</html>