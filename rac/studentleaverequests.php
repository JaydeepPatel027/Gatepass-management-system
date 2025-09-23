<?php
session_start();
include 'db_connection.php';

// Rector's email from session
$rectorEmail = $_SESSION['R_Email'] ?? '';

$rectorBlockNo = '';
if ($rectorEmail) {
    $rectorQuery = $conn->query("SELECT Hostel_Block_No FROM r_db WHERE R_Email = '$rectorEmail'");
    if ($rectorQuery->num_rows > 0) {
        $rectorRow = $rectorQuery->fetch_assoc();
        $rectorBlockNo = $rectorRow['Hostel_Block_No'];
    }
}

// Handle action update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enrollment_no'], $_POST['status'])) {
    $enrollment_no = $_POST['enrollment_no'];
    $new_status = $_POST['status'];

    // Get current status
    $check = mysqli_query($conn, "SELECT Status FROM leave_requests WHERE Enrollment_No = '$enrollment_no'");
    $row = mysqli_fetch_assoc($check);
    $current_status = $row['Status'];

    // Prevent moving back to Pending silently
    if (($current_status == 'Approved' || $current_status == 'Rejected') && $new_status == 'Pending') {
        header("Location: studentleaverequests.php");
        exit();
    }

    // Update status
    $updateLeave = "UPDATE leave_requests SET Status = '$new_status' WHERE Enrollment_No = '$enrollment_no'";
    mysqli_query($conn, $updateLeave);

    $updateStudent = "UPDATE s_db SET Status = '$new_status' WHERE Enrollment_No = '$enrollment_no'";
    mysqli_query($conn, $updateStudent);

    header("Location: studentleaverequests.php?msg=updated");
    exit();
}

// Fetch all leave requests for rector's block
$query = "
SELECT lr.*, s.Student_Name, s.Hostel_Block_No, s.Hostel_Room_No, lr.Status AS LeaveStatus
FROM leave_requests lr
LEFT JOIN s_db s ON TRIM(LOWER(lr.Enrollment_No)) = TRIM(LOWER(s.Enrollment_No))
WHERE s.Hostel_Block_No = '$rectorBlockNo'
ORDER BY lr.Leaving_Date DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
        }
        table {
            color: white;
        }
        
        th, td {
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            vertical-align: middle;
            color: white;
        }
        th {
            background: rgba(255, 255, 255, 0.2);
        }
        .dropdown-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        .btn-glow {
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            color: #1e1e2f;
            font-weight: bold;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease-in-out;
            border-radius: 30px;
            padding: 10px 20px;
        }
        .btn-glow:hover {
            background: #ffc107;
            color: #000;
            box-shadow: 0 0 25px #ffc107, 0 0 10px #ffc107;
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div class="alert alert-success text-center">Status updated successfully!</div>
<?php endif; ?>

<div class="container">
    <?php
    if (isset($_GET['room'])) {
        $pageTitle = "Room " . htmlspecialchars($_GET['room']) . " Leave Requests";
    } elseif (isset($_GET['RoomDetail'])) {
        $pageTitle = "Room Detail Leave Requests";
    } else {
        $pageTitle = "All Leave Requests";
    }
    ?>
    <h3 class="text-center mb-3"><?php echo $pageTitle; ?></h3>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Enrollment No</th>
                    <th>Student Name</th>
                    <th>Block</th>
                    <th>Room</th>
                    <th>Reason</th>
                    <th>Location</th>
                    <th>Leaving Date</th>
                    <th>Returning Date</th>
                    <th>Leaving Time</th>
                    <th>Returning Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Enrollment_No']); ?></td>
                        <td><?php echo htmlspecialchars($row['Student_Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Hostel_Block_No']); ?></td>
                        <td><?php echo htmlspecialchars($row['Hostel_Room_No']); ?></td>
                        <td><?php echo htmlspecialchars($row['Reason']); ?></td>
                        <td><?php echo htmlspecialchars($row['Location']); ?></td>
                        <td><?php echo htmlspecialchars($row['Leaving_Date']); ?></td>
                        <td><?php echo htmlspecialchars($row['Returning_Date']); ?></td>
                        <td><?php echo date("H:i", strtotime($row['Leaving_Time'])); ?></td>
                        <td><?php echo date("H:i", strtotime($row['Returning_Time'])); ?></td>
                        <td>
                            <?php
                            date_default_timezone_set("Asia/Kolkata");
                            $now = strtotime(date("Y-m-d H:i:s"));
                            $leaveStart = strtotime($row['Leaving_Date'] . ' ' . $row['Leaving_Time']);
                            $leaveEnd = strtotime($row['Returning_Date'] . ' ' . $row['Returning_Time']);

                            if ($row['LeaveStatus'] == 'Approved' && $now >= $leaveStart && $now <= $leaveEnd) {
                                echo '<span style="color: orange; font-weight:bold;">Active</span>';
                            } elseif ($row['LeaveStatus'] == 'Approved') {
                                echo '<span style="color: lightgreen; font-weight:bold;">Inactive</span>';
                            } elseif ($row['LeaveStatus'] == 'Rejected') {
                                echo '<span style="color: red; font-weight:bold;">Rejected</span>';
                            } else {
                                echo '<span style="color: yellow; font-weight:bold;">Pending</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($row['LeaveStatus'] == 'Pending') { ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                        Change Status
                                    </button>
                                    <div class="dropdown-menu">
                                        <form method="POST" class="px-3 py-1">
                                            <input type="hidden" name="enrollment_no" value="<?php echo htmlspecialchars($row['Enrollment_No']); ?>">
                                            <button class="dropdown-item text-success" type="submit" name="status" value="Approved">
                                                <i class="fas fa-check-circle"></i> Approved
                                            </button>
                                            <button class="dropdown-item text-danger" type="submit" name="status" value="Rejected">
                                                <i class="fas fa-times-circle"></i> Rejected
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <span>-</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<br>
<a href="javascript:history.back()" class="btn btn-glow mb-3">
    <i class="fas fa-arrow-left mr-2"></i> Back
</a>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>
