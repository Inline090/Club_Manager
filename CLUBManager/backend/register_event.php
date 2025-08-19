<?php
session_start();
// Ensure user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("location: ../student.html?error=auth_required");
    exit;
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // *** CORRECTED: Path relative to backend folder to Dashboard folder ***
    require '../Dashboard/config.php'; // DB config

    $userId = (int)$_SESSION['user_id'];
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    // Validate Event ID
    if (!$eventId || $eventId <= 0) {
        header("Location: ../event_list.php?error=invalid_event"); exit;
    }

    // --- Database Connection ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Register Event DB Connection Failed: " . mysqli_connect_error());
        // *** CORRECTED: Add &id=... to error redirect ***
        header("Location: ../event_details.php?id=" . $eventId . "&error=db_error"); exit;
    }

    // --- Pre-checks before inserting ---
    $isAlreadyRegistered = false;
    $isRegistrationOpen = false;
    $isFull = false;
    $eventDetails = null;

    // 1. Fetch event details (deadline, capacity, date)
    $sql_event = "SELECT EventDate, Capacity, RegistrationDeadline FROM Event WHERE EventID = ?";
    $stmt_event = mysqli_prepare($conn, $sql_event);
    if($stmt_event){
        mysqli_stmt_bind_param($stmt_event, "i", $eventId);
        mysqli_stmt_execute($stmt_event);
        $result_event = mysqli_stmt_get_result($stmt_event);
        $eventDetails = mysqli_fetch_assoc($result_event);
        mysqli_stmt_close($stmt_event);
    }
    if (!$eventDetails) { // Event not found
        mysqli_close($conn);
        header("Location: ../event_list.php?error=event_not_found"); exit;
    }

    // 2. Check if user is already registered
    $sql_check_reg = "SELECT COUNT(*) FROM MemberEvent WHERE MemberID = ? AND EventID = ?";
    $stmt_check_reg = mysqli_prepare($conn, $sql_check_reg);
     if ($stmt_check_reg) {
         mysqli_stmt_bind_param($stmt_check_reg, "ii", $userId, $eventId);
         mysqli_stmt_execute($stmt_check_reg);
         mysqli_stmt_bind_result($stmt_check_reg, $reg_count);
         mysqli_stmt_fetch($stmt_check_reg);
         $isAlreadyRegistered = ($reg_count > 0);
         mysqli_stmt_close($stmt_check_reg);
     } else { error_log("Check Reg Prepare Error: " . mysqli_error($conn)); /* Handle error silently for now */ }

    if ($isAlreadyRegistered) {
         mysqli_close($conn);
         // *** CORRECTED: Use ®_status= ***
         header("Location: ../event_details.php?id=" . $eventId . "®_status=already_registered"); exit;
    }

    // 3. Check if registration is open (deadline & before event start)
    $todayTimestamp = time();
    $eventTimestamp = strtotime($eventDetails['EventDate']);
    $deadlineStr = $eventDetails['RegistrationDeadline'] ?? $eventDetails['EventDate'];
    if(strlen($deadlineStr) == 10) $deadlineStr .= ' 23:59:59';
    $deadlineTimestamp = strtotime($deadlineStr);

    $isRegistrationOpen = ($todayTimestamp <= $deadlineTimestamp && $todayTimestamp < $eventTimestamp);


    if (!$isRegistrationOpen) {
         mysqli_close($conn);
         // *** CORRECTED: Use ®_status= ***
         header("Location: ../event_details.php?id=" . $eventId . "®_status=closed"); exit;
    }

    // 4. Check capacity (if applicable)
    if ($eventDetails['Capacity'] !== null && $eventDetails['Capacity'] > 0) {
         $sql_capacity = "SELECT COUNT(*) FROM MemberEvent WHERE EventID = ?";
         $stmt_capacity = mysqli_prepare($conn, $sql_capacity);
         if($stmt_capacity) {
             mysqli_stmt_bind_param($stmt_capacity, "i", $eventId);
             mysqli_stmt_execute($stmt_capacity);
             mysqli_stmt_bind_result($stmt_capacity, $currentRegistrations);
             mysqli_stmt_fetch($stmt_capacity);
             mysqli_stmt_close($stmt_capacity);
             if ($currentRegistrations >= $eventDetails['Capacity']) {
                 $isFull = true;
             }
         }
    }

     if ($isFull) {
         mysqli_close($conn);
         // *** CORRECTED: Use ®_status= ***
         header("Location: ../event_details.php?id=" . $eventId . "®_status=closed"); exit; // Treat full as closed
    }


    // --- All Checks Passed - Proceed with INSERT ---
    $sql_insert = "INSERT INTO MemberEvent (MemberID, EventID) VALUES (?, ?)"; // Assumes Attended defaults to 0 in DB
    $stmt_insert = mysqli_prepare($conn, $sql_insert);

    if ($stmt_insert) {
        mysqli_stmt_bind_param($stmt_insert, "ii", $userId, $eventId);

        if (mysqli_stmt_execute($stmt_insert)) {
            // --- Success ---
            mysqli_stmt_close($stmt_insert);
            mysqli_close($conn);
             // *** CORRECTED: Use ®_status= ***
            header("Location: ../event_details.php?id=" . $eventId . "&reg_status=success"); exit;
        } else {
            // --- Execution Error ---
            $error_msg = mysqli_stmt_error($stmt_insert);
            error_log("Register Event Execute Error for UserID $userId, EventID $eventId: $error_msg");
            mysqli_stmt_close($stmt_insert);
            mysqli_close($conn);
             // *** CORRECTED: Use ®_status= ***
            header("Location: ../event_details.php?id=" . $eventId . "®_status=failed"); exit;
        }
    } else {
        // --- Prepare Error ---
        error_log("Register Event Prepare Error: " . mysqli_error($conn));
        mysqli_close($conn);
         // *** CORRECTED: Add &id=... to error redirect ***
        header("Location: ../event_details.php?id=" . $eventId . "&error=prepare_failed"); exit;
    }

} else {
    // Not a POST request
    header("Location: ../event_list.php"); exit;
}
?>