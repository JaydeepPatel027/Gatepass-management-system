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

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enrollment_no'], $_POST['status'])) {
    $enrollment_no = $_POST['enrollment_no'];
    $new_status = $_POST['status'];

    // Update only in leave_requests table
    $updateLeave = "UPDATE leave_requests SET Status = '$new_status' WHERE Enrollment_No = '$enrollment_no'";
    mysqli_query($conn, $updateLeave);

    header("Location: approved_requests.php?msg=updated");
    exit();
}

// Fetch approved leave requests filtered by Rector's block
$query = "
SELECT lr.*, s.Student_Name, s.Hostel_Block_No, s.Hostel_Room_No 
FROM leave_requests lr
JOIN s_db s ON TRIM(LOWER(lr.Enrollment_No)) = TRIM(LOWER(s.Enrollment_No))
WHERE lr.Status = 'Rejected' 
  AND s.Hostel_Block_No = '$rectorBlockNo'
ORDER BY lr.Leaving_Date DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rejected Leave Requests</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            color: white;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
        }
        table {
            color: white;
            width: 100%;
        }
        th, td {
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            vertical-align: middle;
            color: white;
        }
        .dropdown-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        h3 {
            font-weight: bold;
            color: #fff;
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

<div class="container">
    <h3 class="text-center mb-4">Rejected Leave Requests</h3>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success text-center">Status updated successfully!</div>
    <?php endif; ?>

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
                    <th>Time Out</th>
                    <th>Time In</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['Enrollment_No'] ?></td>
                        <td><?= $row['Student_Name'] ?></td>
                        <td><?= $row['Hostel_Block_No'] ?></td>
                        <td><?= $row['Hostel_Room_No'] ?></td>
                        <td><?= $row['Reason'] ?></td>
                        <td><?= $row['Location'] ?></td>
                        <td><?= $row['Leaving_Date'] ?></td>
                        <td><?= $row['Returning_Date'] ?></td>
                        <td><?= date("H:i", strtotime($row['Leaving_Time'])) ?></td>
                        <td><?= date("H:i", strtotime($row['Returning_Time'])) ?></td>
                        <td><span style="color:red;font-weight:bold;"><?= $row['Status'] ?></span></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    Change Status
                                </button>
                                <div class="dropdown-menu">
                                    <form method="POST" action="rejected_requests.php" class="px-3 py-1">
                                        <input type="hidden" name="enrollment_no" value="<?= $row['Enrollment_No'] ?>">
                                        <button class="dropdown-item text-success" type="submit" name="status" value="Approved">
                                            <i class="fas fa-check-circle"></i> Approve
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<br>
<a href="rac.php" class="btn btn-glow mb-3">
    <i class="fas fa-arrow-left mr-2"></i> Back
</a>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>
