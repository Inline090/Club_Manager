<?php
// --- VERY FIRST THING: DIAGNOSTICS (Optional: Can remove later) ---
echo "--- save_event.php Debug Start ---<br>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "<pre>POST Data Received: ";
print_r($_POST);
echo "</pre>";
echo "<pre>GET Data Received: ";
print_r($_GET);
echo "</pre>";
echo "--- Debug End ---<br>";
// exit; // <-- Make sure this is commented out or removed

// --- Step 1: Start Session & Error Reporting ---
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Turn off in production

// --- Step 2: Include Database Configuration ---
require '../Dashboard/config.php';

// --- Step 3: Security Check - Admin Login ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin_login.html?error=auth_required");
    exit;
}

// --- Step 4: Check if the form was submitted via POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Step 5: Get Data from POST ---
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $edit_mode = ($event_id && $event_id > 0);

    $event_name = trim($_POST['event_name'] ?? '');
    $event_date_str = trim($_POST['event_date'] ?? '');
    $event_location = trim($_POST['event_location'] ?? '');
    $event_fee_str = trim($_POST['event_fee'] ?? '0');
    $capacity_str = trim($_POST['capacity'] ?? '');
    $reg_deadline_str = trim($_POST['registration_deadline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // --- Step 6: Basic Validation ---
    $errors = [];
    if (empty($event_name)) {
        $errors[] = "Event Name is required.";
    }
    if (empty($event_date_str)) {
        $errors[] = "Event Date & Time is required.";
    }

    $event_date_mysql = null;
    if (!empty($event_date_str)) {
        $event_date_dt = date_create_from_format('Y-m-d\TH:i', $event_date_str);
        if ($event_date_dt === false) {
            $errors[] = "Invalid Event Date format.";
        } else {
            $event_date_mysql = date_format($event_date_dt, 'Y-m-d H:i:s');
        }
    }

    $reg_deadline_mysql = null;
    if (!empty($reg_deadline_str)) {
        $reg_deadline_dt = date_create_from_format('Y-m-d\TH:i', $reg_deadline_str);
         if ($reg_deadline_dt === false) {
             $errors[] = "Invalid Registration Deadline format.";
         } else {
            if ($event_date_dt && $reg_deadline_dt >= $event_date_dt) {
                $errors[] = "Registration deadline must be before the event date.";
            } else {
                $reg_deadline_mysql = date_format($reg_deadline_dt, 'Y-m-d H:i:s');
            }
         }
    }

    $event_fee = 0.00;
    if ($event_fee_str !== '' && $event_fee_str !== null) {
        if (!is_numeric($event_fee_str) || $event_fee_str < 0) {
            $errors[] = "Invalid Event Fee.";
        } else {
            $event_fee = (float)$event_fee_str;
        }
    }

    $capacity = null;
    if ($capacity_str !== '' && $capacity_str !== null) {
         if (!ctype_digit($capacity_str) || $capacity_str < 0) {
             $errors[] = "Invalid Capacity.";
         } else {
             $capacity = (int)$capacity_str;
         }
    }

    // --- Step 7: If Validation Errors, Redirect Back ---
    if (!empty($errors)) {
        $error_query = http_build_query(['error' => implode('<br>', $errors)]);
        $id_param = $edit_mode ? "&id=" . $event_id : "";
        header("Location: ../admin_edit_event.php?" . $error_query . $id_param);
        exit;
    }

    // --- Step 8: Database Connection ---
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        error_log("Database Connection Failed in save_event.php: " . mysqli_connect_error());
        $id_param = $edit_mode ? "&id=" . $event_id : "";
        header("Location: ../admin_edit_event.php?error=Database+connection+error." . $id_param);
        exit;
    }

    // --- Step 9: Prepare SQL Statement (INSERT or UPDATE) ---
    $sql = "";
    $types = "";
    $params = [];

    // $admin_id = $_SESSION['admin_id'] ?? null; // Removed $admin_id usage as columns are missing

    if ($edit_mode) {
        // ----- UPDATE existing event -----
        // ** MODIFIED: Removed LastUpdatedByAdminID = ? **
        $sql = "UPDATE Event SET
                    EventName = ?, EventDate = ?, EventLocation = ?, EventFee = ?,
                    Description = ?, Capacity = ?, RegistrationDeadline = ?
                WHERE EventID = ?";
        // ** MODIFIED: Removed one 'i' from the end for LastUpdatedByAdminID **
        $types = "sssdsisi"; // 7 columns to set + 1 for WHERE = 8 types
        $params = [
            $event_name,          // s
            $event_date_mysql,    // s
            $event_location,      // s
            $event_fee,           // d
            $description,         // s
            $capacity,            // i
            $reg_deadline_mysql,  // s
            // $admin_id,         // Removed
            $event_id             // i (for WHERE clause)
        ];
        $success_status = "updated";

    } else {
        // ----- INSERT new event -----
        // ** MODIFIED: Removed CreatedByAdminID column and corresponding VALUE placeholder **
        $sql = "INSERT INTO Event (EventName, EventDate, EventLocation, EventFee, Description, Capacity, RegistrationDeadline)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        // ** MODIFIED: Removed one 'i' from the end for CreatedByAdminID **
        $types = "sssdsis"; // 7 columns = 7 types
         $params = [
            $event_name,          // s
            $event_date_mysql,    // s
            $event_location,      // s
            $event_fee,           // d
            $description,         // s
            $capacity,            // i
            $reg_deadline_mysql   // s
            // $admin_id          // Removed
        ];
        $success_status = "added";
    }

    // --- Step 10: Prepare, Bind, and Execute ---
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            // SUCCESS!
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            // Redirect to manage page with success status
            header("Location: ../admin_manage_events.php?status=" . $success_status);
            exit;
        } else {
            // EXECUTION FAILED
            $db_error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            error_log("SQL Execute Error in save_event.php (EventID: {$event_id}): " . $db_error . " | SQL: " . $sql);
            $id_param = $edit_mode ? "&id=" . $event_id : "";
            header("Location: ../admin_edit_event.php?error=Database+save+failed.+Check+data+or+logs.+[Code: EXEC]" . $id_param);
            exit;
        }
    } else {
        // PREPARE FAILED
        $db_error = mysqli_error($conn);
        mysqli_close($conn);
        error_log("SQL Prepare Error in save_event.php: " . $db_error . " | SQL Template: " . $sql);
        $id_param = $edit_mode ? "&id=" . $event_id : "";
        header("Location: ../admin_edit_event.php?error=Database+error.+Check+SQL+syntax.+[Code: PREP]" . $id_param);
        exit;
    }

} else {
    // --- Step 11: Handle non-POST requests ---
    header("Location: ../admin_manage_events.php?error=invalid_request");
    exit;
}

?>