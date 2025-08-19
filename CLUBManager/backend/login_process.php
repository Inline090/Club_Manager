<?php
// ALWAYS start session FIRST
session_start();

// Database connection parameters (or include config.php)
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "clubmanager";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn = mysqli_connect($servername, $username, $password_db, $dbname);

    if (!$conn) {
        error_log("Login DB Connection Failed: " . mysqli_connect_error());
        header("Location: ../student.html?error=db_error");
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password_plain = $_POST['password'] ?? '';

    // Input validation
    if (empty($email) || empty($password_plain)) {
        mysqli_close($conn);
        header("Location: ../student.html?error=missing_fields");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         mysqli_close($conn);
         header("Location: ../student.html?error=invalid_credentials");
         exit;
    }

    // --- Prepare SQL to fetch MEMBER credentials ---
    // *** MODIFIED: Use 'Member' table ***
    // Assumes Member table has Password column
    $sql = "SELECT MemberID, Name, Password FROM Member WHERE Email = ? LIMIT 1"; // Changed table name
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($member = mysqli_fetch_assoc($result)) {
            // --- User found, Verify Password ---
             if (password_verify($password_plain, $member['Password'])) {
                 // --- Password CORRECT for Member ---
                 session_regenerate_id(true);
                 $_SESSION['user_id'] = $member['MemberID'];
                 $_SESSION['user_name'] = $member['Name'];
                 $_SESSION['user_email'] = $email;
                 $_SESSION['logged_in'] = true; // Regular user logged in

                 mysqli_stmt_close($stmt);
                 mysqli_close($conn);
                 header("Location: ../Dashboard/main_board.php"); // Go to member dashboard
                 exit;

             } else {
                 // Password Incorrect
                 mysqli_stmt_close($stmt);
                 mysqli_close($conn);
                 header("Location: ../student.html?error=invalid_credentials");
                 exit;
             }
        } else {
            // User (email) not found
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: ../student.html?error=invalid_credentials");
            exit;
        }
    } else {
        // SQL Prepare Error
        $error_msg = mysqli_error($conn);
        mysqli_close($conn);
        error_log("Member Login SQL Prepare Error: " . $error_msg);
        header("Location: ../student.html?error=server_error");
        exit;
    }

} else {
    // Not a POST request
    header("Location: ../index.html");
    exit;
}
?>