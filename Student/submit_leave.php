<?php
session_start();
// Ensure user is logged in
if (empty($_SESSION['username'])) {
    header('Location: student_login.php');
    exit();
}

// Detect AJAX so we can return JSON and avoid redirects
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Start output buffering for AJAX requests so any accidental output from includes doesn't break JSON
if ($isAjax) {
    ob_start();
}

// Basic DB connection - adjust if you use a central include
$host = 'localhost';
$dbname = 'gatepass';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Expected POST fields: reason, location, leaving_date, returning_date, leaving_time, returning_time
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$leaving_date = isset($_POST['leaving_date']) ? $_POST['leaving_date'] : null;
$returning_date = isset($_POST['returning_date']) ? $_POST['returning_date'] : null;
$leaving_time = isset($_POST['leaving_time']) ? $_POST['leaving_time'] : null;
$returning_time = isset($_POST['returning_time']) ? $_POST['returning_time'] : null;
$enrollment = $_SESSION['username'];

// Basic validation
// Basic validation
if (empty($reason) || empty($leaving_date)) {
    if ($isAjax) {
        // discard any buffered output and return JSON
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'validation', 'message' => 'Reason and Leaving Date are required']);
        exit();
    }
    // fallback for non-AJAX
    header('Location: student.php?page=leave');
    exit();
}

// Insert into leave_requests table. Adjust columns as per your DB schema.
$sql = "INSERT INTO leave_requests (Enrollment_No, Reason, Location, Leaving_Date, Returning_Date, Leaving_Time, Returning_Time, status) VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)";

$stmt = $conn->prepare($sql);
$status = 'Pending';

// Check prepare
if ($stmt === false) {
    $err = '[' . date('c') . '] prepare failed: ' . $conn->error . PHP_EOL;
    @file_put_contents(__DIR__ . '/submit_leave_error.log', $err, FILE_APPEND | LOCK_EX);
    $conn->close();
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Prepare failed: " . $conn->error . "\n";
        echo "SQL: " . $sql . "\n";
        exit();
    }
    if ($isAjax) {
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'prepare_failed', 'message' => $conn->error]);
        exit();
    }
    header('Location: student.php?page=leave');
    exit();
}

$stmt->bind_param('ssssssss', $enrollment, $reason, $location, $leaving_date, $returning_date, $leaving_time, $returning_time, $status);

if ($stmt->execute()) {
    $stmt->close();
    // Build response for AJAX
    $conn->close();
    if ($isAjax) {
        // clear buffer then return JSON
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'message' => 'Leave request submitted']);
        exit();
    }
    header('Location: student.php?page=status');
    exit();
} else {
    $err = [
        'time' => date('c'),
        'error' => $stmt->error,
        'errno' => $stmt->errno,
        'conn_error' => $conn->error,
        'data' => [
            'Enrollment_No' => $enrollment,
            'Reason' => $reason,
            'Location' => $location,
            'Leaving_Date' => $leaving_date,
            'Returning_Date' => $returning_date,
            'Leaving_Time' => $leaving_time,
            'Returning_Time' => $returning_time,
        ],
    ];
    @file_put_contents(__DIR__ . '/submit_leave_error.log', json_encode($err, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'execute_failed', 'details' => $err], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stmt->close();
    $conn->close();
    if ($isAjax) {
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'execute_failed', 'details' => $err]);
        exit();
    }
    header('Location: student.php?page=leave');
    exit();
}
