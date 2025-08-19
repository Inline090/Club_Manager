<?php
session_start();
require '../Dashboard/config.php'; // Path relative to backend

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Access denied.');
}

// Check POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data
    $transaction_nature = $_POST['transaction_nature'] ?? 'expense'; // 'expense' or 'income'
    $payment_date_input = trim($_POST['payment_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount_input = trim($_POST['amount'] ?? '');

    // --- Validation ---
    $errors = [];
    if (empty($payment_date_input) || !DateTime::createFromFormat('Y-m-d', $payment_date_input)) {
        $errors[] = "Valid Date is required.";
    } else {
        $payment_date = $payment_date_input; // Use YYYY-MM-DD format for DATE column
    }
    if (empty($description)) { $errors[] = "Description is required."; }
    if (!is_numeric($amount_input) || $amount_input <= 0) {
         $errors[] = "Valid positive amount is required.";
    } else {
        $amount = (float)$amount_input;
        // Make amount negative if it's an expense
        if ($transaction_nature === 'expense') {
            $amount = -$amount;
        }
    }
    if ($transaction_nature !== 'expense' && $transaction_nature !== 'income') {
        $errors[] = "Invalid transaction type.";
    }

    // Set TransactionType for DB
    $transactionType = ($transaction_nature === 'expense') ? 'Expense' : 'Income'; // Use clear types


    // Redirect back if errors
    if (!empty($errors)) {
        $errorString = implode(' ', $errors);
        header("Location: ../admin_add_transaction.php?type=" . $transaction_nature . "&error=" . urlencode($errorString));
        exit;
    }

    // --- Database Operation ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) { /* ... redirect db error ... */ exit; }

    // INSERT into Payment table (MemberID and EventID are NULL)
    $sql = "INSERT INTO Payment (MemberID, EventID, PaymentDate, Amount, TransactionType, Description) VALUES (NULL, NULL, ?, ?, ?, ?)";
    $types = "sdss"; // date (string), amount (double), type (string), description (string)
    $params = [$payment_date, $amount, $transactionType, $description];

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $status = ($transaction_nature === 'expense') ? 'expense_added' : 'income_added';
        } else { $errorString = "Failed to record transaction."; error_log(mysqli_stmt_error($stmt)); }
        mysqli_stmt_close($stmt);
    } else { $errorString = "Error preparing save operation."; error_log(mysqli_error($conn)); }

    mysqli_close($conn);

    // Redirect based on outcome
    if (isset($status)) {
         header("Location: ../admin_manage_finances.php?status=" . $status);
         exit;
    } else {
        header("Location: ../admin_add_transaction.php?type=" . $transaction_nature . "&error=" . urlencode($errorString ?? 'Unknown save error.'));
        exit;
    }

} else {
    header("Location: ../admin_manage_finances.php"); exit;
}
?>