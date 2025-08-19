<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: student.html?error=not_logged_in");
    exit;
}

require 'Dashboard/config.php'; // Path relative to this file in root
$userId = $_SESSION['user_id'] ?? 0;

$payments = [];
$fetch_error = '';

if ($userId > 0) {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        // Fetch ALL payments for the user, most recent first
        // Join with Event table to optionally get Event Name if EventID exists
        $sql = "SELECT p.PaymentDate, p.Amount, p.TransactionType, p.Description, e.EventName
                FROM Payment p
                LEFT JOIN Event e ON p.EventID = e.EventID
                WHERE p.MemberID = ?
                ORDER BY p.PaymentDate DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                // Construct a final description
                $displayDesc = $row['Description'];
                if (empty($displayDesc)) {
                    $displayDesc = $row['TransactionType'] ?? 'Payment';
                    if (!empty($row['EventName']) && ($row['TransactionType'] == 'Event Fee' || $row['TransactionType'] == 'Event Payment')) {
                       $displayDesc .= ' - ' . $row['EventName'];
                    }
                }
                $row['DisplayDescription'] = $displayDesc; // Add processed description
                $payments[] = $row;
            }
            mysqli_stmt_close($stmt);
        } else { $fetch_error = "Could not retrieve payment history."; error_log("Payment Fetch Prepare Error: ".mysqli_error($conn));}
        mysqli_close($conn);
    } else { $fetch_error = "Database connection error."; }
} else { $fetch_error = "Invalid session."; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payment History - Manipal Club Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>@import url('https://rsms.me/inter/inter.css'); html { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
     <nav class="bg-white shadow-sm mb-8"> <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center"> <a href="Dashboard/main_board.php" class="text-xl font-bold text-indigo-600">Member Portal</a> <div> <a href="Dashboard/main_board.php" class="text-gray-600 hover:text-indigo-600 mr-4">Dashboard</a> <a href="backend/logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a> </div> </div> </nav>

    <div class="max-w-4xl mx-auto p-6">
         <h1 class="text-3xl font-semibold text-gray-800 mb-6">My Payment History</h1>

          <?php if ($fetch_error): ?> <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert"> <?php echo htmlspecialchars($fetch_error); ?> </div> <?php endif; ?>

         <?php if (empty($payments) && !$fetch_error): ?>
             <div class="bg-white p-6 rounded-lg shadow text-center">
                 <p class="text-gray-500">You have no payment history yet.</p>
             </div>
         <?php elseif (!empty($payments)): ?>
             <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Description / Type</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date("M j, Y", strtotime($payment['PaymentDate'])); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($payment['DisplayDescription']); ?></td>
                                <td class="px-6 py-4 text-right font-medium">₹<?php echo htmlspecialchars(number_format($payment['Amount'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
         <?php endif; ?>
    </div>
</body>
</html>