<?php
// backend/process_payment.php
session_start();

// Ensure user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("location: ../student.html?error=auth_required");
    exit;
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require '../Dashboard/config.php'; // Include DB config

    $userId = (int)$_SESSION['user_id'];
    $selectedPlan = $_POST['membership_type'] ?? '';

    // --- Validate Selected Plan ---
    $allowed_types = ['Monthly', 'Quarterly', 'Half Yearly', 'Yearly'];
    if (empty($selectedPlan) || !in_array($selectedPlan, $allowed_types)) {
        header("Location: ../payments.php?error=invalid_plan");
        exit;
    }

    // --- Calculate New Expiry Date ---
    $current_date = new DateTime(); // Use current date/time
    $interval = null;
    $amount = 0.00; // Set default amount or determine based on plan

    switch ($selectedPlan) {
        case 'Monthly':     $interval = new DateInterval('P1M'); $amount = 50.00; break; // Example fee
        case 'Quarterly':   $interval = new DateInterval('P3M'); $amount = 130.00; break; // Example fee
        case 'Half Yearly': $interval = new DateInterval('P6M'); $amount = 250.00; break; // Example fee
        case 'Yearly':      $interval = new DateInterval('P1Y'); $amount = 450.00; break; // Example fee
        // No default needed due to validation above
    }

    $current_date->add($interval);
    $newExpiryDate = $current_date->format('Y-m-d'); // Format for DB

    // --- Update Membership Expiry in Database ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Process Payment DB Connection Failed: " . mysqli_connect_error());
        header("Location: ../payments.php?error=db_error");
        exit;
    }

    // Transaction START (Optional but good practice if logging payment too)
    mysqli_begin_transaction($conn);

    // Prepare UPDATE statement for Member table
    $sql_update = "UPDATE Member SET MembershipExpiry = ?, MembershipType = ? WHERE MemberID = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    $update_success = false;

    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssi", $newExpiryDate, $selectedPlan, $userId);
        if (mysqli_stmt_execute($stmt_update)) {
            if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                 $update_success = true;
            } else {
                // MemberID might be invalid or expiry date didn't change (unlikely here)
                error_log("Membership update affected 0 rows for UserID: $userId");
                // Can decide if this is an error or not - let's assume it might be okay if expiry didn't need update
                $update_success = true; // Treat as success if execute worked, even if no rows changed
            }
        } else {
            error_log("Membership Update Execute Error for UserID $userId: " . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);
    } else {
        error_log("Membership Update Prepare Error: " . mysqli_error($conn));
    }

    // --- Optional: Log the Payment Transaction ---
    $log_success = false;
    if ($update_success) { // Only log if the membership update seemed okay
        $paymentDesc = "Membership Fee - " . $selectedPlan;
        $sql_log = "INSERT INTO Payment (MemberID, PaymentDate, Amount, TransactionType, Description, EventID) VALUES (?, NOW(), ?, ?, ?, NULL)";
        $stmt_log = mysqli_prepare($conn, $sql_log);
        if($stmt_log) {
            mysqli_stmt_bind_param($stmt_log, "idss", $userId, $amount, $selectedPlan, $paymentDesc); // Adjusted type to match logic
            if (mysqli_stmt_execute($stmt_log)) {
                $log_success = true;
            } else {
                error_log("Payment Log Execute Error for UserID $userId: " . mysqli_stmt_error($stmt_log));
            }
            mysqli_stmt_close($stmt_log);
        } else {
             error_log("Payment Log Prepare Error: " . mysqli_error($conn));
        }
    }

    // --- Commit or Rollback Transaction ---
    if ($update_success && $log_success) {
        mysqli_commit($conn);
        mysqli_close($conn);
        // Redirect to confirmation page
        header("Location: ../payment_confirmed.php?status=success&plan=" . urlencode($selectedPlan) . "&expiry=" . urlencode($newExpiryDate));
        exit;
    } elseif ($update_success && !$log_success) {
         // Membership updated, but logging failed. Commit the update but warn user? Or rollback?
         // Let's commit but maybe send a different status code or message.
         mysqli_commit($conn);
         mysqli_close($conn);
         error_log("CRITICAL: Membership updated for UserID $userId but payment logging failed.");
         // Redirect to confirmation but maybe with a note? Or redirect to payment page with error?
         // Let's redirect to confirmation for now, admin can cross-check later.
          header("Location: ../payment_confirmed.php?status=success_log_failed&plan=" . urlencode($selectedPlan) . "&expiry=" . urlencode($newExpiryDate));
         exit;

    } else {
        // Membership update failed, rollback any potential changes (though logging wouldn't have run)
        mysqli_rollback($conn);
        mysqli_close($conn);
        header("Location: ../payments.php?error=update_failed");
        exit;
    }

} else {
    // Not a POST request
    header("Location: ../payments.php");
    exit;
}
?>