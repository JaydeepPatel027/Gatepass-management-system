<?php
session_start();
// Check authentication
if (empty($_SESSION['username'])) {
    // Not logged in - redirect to login page
    header('Location: student_login.php');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'gatepass';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Normalize enrollment value from session
$enroll = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';

// Fetch student for the logged-in user
$student = null;
$student_sql = "SELECT * FROM s_db WHERE Enrollment_No = ? LIMIT 1";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param('s', $enroll);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $student = $res->fetch_assoc();
    }
}
$stmt->close();

if (!$student) {
    // If for some reason the session user doesn't exist in DB, force logout.
    session_unset();
    session_destroy();
    header('Location: student_login.php');
    exit();
}

// Determine current page (default to profile after login)
$page = isset($_GET['page']) ? $_GET['page'] : 'profile';
// If a fragment for status is requested, we'll return only that HTML (used by AJAX)
$isFragment = (isset($_GET['fragment']) && $_GET['fragment']=='1');

// Password change logic
$msg = "";
if ($page == 'profile' && isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($old_pass !== $student['Password']) {
        $msg = '<div class="alert alert-danger">❌ Old password is incorrect!</div>';
    } elseif ($new_pass !== $confirm_pass) {
        $msg = '<div class="alert alert-danger">❌ New password and Confirm password do not match!</div>';
    } else {
        $update_sql = "UPDATE s_db SET Password=? WHERE Enrollment_No=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $new_pass, $_SESSION['username']);
        if ($update_stmt->execute()) {
            $msg = '<div class="alert alert-success">✅ Password changed successfully!</div>';
            $student['Password'] = $new_pass;
        } else {
            $msg = '<div class="alert alert-danger">❌ Error updating password.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #6a11cb, #2575fc);
        color: #fff;
    }

    .wrapper {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        width: 250px;
        background-color: rgba(0, 0, 0, 0.8);
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        overflow-y: auto;
        transition: all 0.3s;
    }

    .sidebar img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 3px solid #fff;
    }

    .sidebar h4 {
        margin-bottom: 10px;
        text-align: center;
    }

    .sidebar .nav-link {
        color: #fff;
        margin-bottom: 10px;
        border-radius: 5px;
    }

    .sidebar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.3);
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .logout-btn {
        margin-top: auto;
    }

    /* Content */
    .content {
        flex-grow: 1;
        margin-left: 250px;
        padding: 20px;
        overflow-y: auto;
    }

    .status-box {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 10px;
        color: #fff;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .sidebar {
            left: -260px;
            top: 0;
            height: 100%;
            z-index: 1000;
        }

        .sidebar.active {
            left: 0;
        }

        .content {
            margin-left: 0;
            padding: 15px;
        }

        .toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background-color: #fff;
            color: #000;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
        }
    }
    </style>
</head>

<body>

    <!-- Mobile toggle button -->
    <button class="btn toggle-btn d-md-none" onclick="toggleSidebar()">☰ Menu</button>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <img src="<?php echo !empty($student['photo']) ? $student['photo'] : 'default.png'; ?>" alt="Student Photo">
            <h4><?php echo htmlspecialchars($student['Student_Name']); ?></h4>
            <a href="?page=profile" class="btn btn-info mb-3 w-100">👤 My Profile</a>

            <ul class="nav flex-column w-100">
                <li class="nav-item"><a class="nav-link <?php if($page=='leave'){echo 'active';} ?>"
                        href="?page=leave">📩 Leave Request</a></li>
                <li class="nav-item"><a class="nav-link <?php if($page=='gatepass'){echo 'active';} ?>"
                        href="?page=gatepass">🚪 Gate Pass</a></li>
                <li class="nav-item"><a class="nav-link <?php if($page=='status'){echo 'active';} ?>"
                        href="?page=status">📊 Status</a></li>
                <li class="nav-item"><a class="nav-link <?php if($page=='approved'){echo 'active';} ?>"
                        href="?page=approved">✅ Approved</a></li>
                <li class="nav-item"><a class="nav-link <?php if($page=='rejected'){echo 'active';} ?>"
                        href="?page=rejected">❌ Rejected</a></li>
                <li class="nav-item"><a class="nav-link <?php if($page=='visits'){echo 'active';} ?>"
                        href="?page=visits">👣 Visits</a></li>
            </ul>
            <button class="btn btn-danger logout-btn w-100 mt-3" onclick="location.href='logout.php'">🚪
                Logout</button>
        </nav>

        <!-- Main Content -->
        <div class="content container-fluid">
            <?php
    if ($page == 'leave') {
        echo "<h3>📩 Leave Request Form</h3>";
        // placeholder for AJAX messages
        echo '<div id="leaveMessage"></div>';
        // add id to the form so JavaScript can intercept submit
        echo '<form id="leaveForm" method="post" action="submit_leave.php" class="bg-dark p-4 rounded">'
            . '<div class="mb-3"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" required></div>'
            . '<div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>'
            . '<div class="row">'
            . '<div class="mb-3 col-md-6"><label class="form-label">Leaving Date</label><input type="date" name="leaving_date" class="form-control" required></div>'
            . '<div class="mb-3 col-md-6"><label class="form-label">Returning Date</label><input type="date" name="returning_date" class="form-control"></div>'
            . '</div>'
            . '<div class="row">'
            . '<div class="mb-3 col-md-6"><label class="form-label">Leaving Time</label><input type="time" name="leaving_time" class="form-control"></div>'
            . '<div class="mb-3 col-md-6"><label class="form-label">Returning Time</label><input type="time" name="returning_time" class="form-control"></div>'
            . '</div>'
            . '<button type="submit" class="btn btn-light">Submit</button>'
            . '</form>';
    } elseif ($page == 'gatepass') {
        // Render a simple gatepass card populated with logged-in student's info
        $now = new DateTime();
        $dateStr = $now->format('d M Y');
        $timeStr = $now->format('h:i A');

        echo '<div class="card p-4 mb-4">';
        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
        echo '<h3>🚪 Gate Pass</h3>';
        echo '<button class="btn btn-primary" onclick="printGatepass()">Print Gatepass</button>';
        echo '</div>';
        echo '<div id="gatepass">';
        echo '<h4>Campus Gatepass</h4>';
        echo '<table class="table table-borderless text-dark w-100">';
        echo '<tr><th style="width:35%">Enrollment No:</th><td>'.htmlspecialchars($student['Enrollment_No']).'</td></tr>';
        echo '<tr><th>Name:</th><td>'.htmlspecialchars($student['SurName'].' '.$student['Student_Name']).'</td></tr>';
        echo '<tr><th>Department:</th><td>'.htmlspecialchars($student['Department']).'</td></tr>';
        echo '<tr><th>Date:</th><td>'.htmlspecialchars($dateStr).'</td></tr>';
        echo '<tr><th>Time:</th><td>'.htmlspecialchars($timeStr).'</td></tr>';
        echo '</table>';
        echo '<p class="small text-muted">This gatepass is valid for one exit on the specified date and time.</p>';
        echo '</div>'; // gatepass
        echo '</div>';
        // Print script added once at bottom of page
    } elseif ($page == 'status') {
        // If AJAX fragment requested, return only the status HTML
        if ($isFragment) {
            // we'll output only the inner HTML for the status list
        }
        echo "<h3>📊 Leave Status</h3>";
            // Fetch leave requests only for the logged-in student
            $stmt = $conn->prepare("SELECT Enrollment_No, Reason, Leaving_Date, status FROM leave_requests WHERE Enrollment_No = ? ORDER BY Leaving_Date DESC");
            $stmt->bind_param('s', $enroll);
            $stmt->execute();
            $result = $stmt->get_result();
            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                header('Content-Type: text/plain; charset=utf-8');
                echo "DEBUG: enrollment used='{$enroll}'\n";
                if ($result) {
                    $all = $result->fetch_all(MYSQLI_ASSOC);
                    echo "Rows fetched: " . count($all) . "\n";
                    echo print_r($all, true);
                } else {
                    echo "No result set\n";
                }
                exit();
            }
            if ($result && $result->num_rows > 0) {
                echo '<div class="row">';
                while ($row = $result->fetch_assoc()) {
                    $color = ($row['status']=='Approved') ? 'green' : (($row['status']=='Rejected') ? 'red' : 'orange');
                    echo '<div class="col-md-6 col-lg-4">';
                    echo '<div class="status-box" style="background-color:' . $color . ';">';
                    echo '<h5>' . htmlspecialchars($row['Reason']) . '</h5>';
                    echo '<p>Leaving Date: ' . htmlspecialchars($row['Leaving_Date']) . '</p>';
                    echo '<p>Status: <strong>' . htmlspecialchars($row['status']) . '</strong></p>';
                    echo '</div></div>';
                }
                echo '</div>';
            } else { echo '<p>No data found.</p>'; }
            $stmt->close();
        // If this was requested as a fragment, stop output here (AJAX will fetch only this block)
        if ($isFragment) {
            $conn->close();
            exit();
        }
    } elseif ($page == 'approved') {
        echo "<h3>✅ Approved Requests</h3>";
        $stmt = $conn->prepare("SELECT Reason, Leaving_Date FROM leave_requests WHERE status='Approved' AND Enrollment_No = ? ORDER BY Leaving_Date DESC");
        $stmt->bind_param('s', $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            echo '<ul class="list-group">';
            while ($row = $result->fetch_assoc()) {
                echo '<li class="list-group-item bg-success text-white">'.htmlspecialchars($row['Reason'])." - ".htmlspecialchars($row['Leaving_Date']).'</li>';
            }
            echo '</ul>';
        } else { echo '<p>No approved requests.</p>'; }
        $stmt->close();
    } elseif ($page == 'rejected') {
        echo "<h3>❌ Rejected Requests</h3>";
        $stmt = $conn->prepare("SELECT Reason, Leaving_Date FROM leave_requests WHERE status='Rejected' AND Enrollment_No = ? ORDER BY Leaving_Date DESC");
        $stmt->bind_param('s', $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            echo '<ul class="list-group">';
            while ($row = $result->fetch_assoc()) {
                echo '<li class="list-group-item bg-danger text-white">'.htmlspecialchars($row['Reason'])." - ".htmlspecialchars($row['Leaving_Date']).'</li>';
            }
            echo '</ul>';
        } else { echo '<p>No rejected requests.</p>'; }
        $stmt->close();
    } elseif ($page == 'visits') {
        echo "<h3>👣 Visits</h3>";
        // Fetch approved leave requests for this student and show date/time of leaving
        $stmt = $conn->prepare("SELECT Reason, Leaving_Date, Leaving_Time FROM leave_requests WHERE status='Approved' AND Enrollment_No = ? ORDER BY Leaving_Date DESC, Leaving_Time DESC");
        $stmt->bind_param('s', $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            echo '<div class="list-group">';
            while ($row = $result->fetch_assoc()) {
                // Attempt to format date/time if present
                $date = $row['Leaving_Date'];
                $time = isset($row['Leaving_Time']) ? $row['Leaving_Time'] : null;
                $displayDate = $date ? date('d M Y', strtotime($date)) : 'Unknown date';
                $displayTime = $time ? date('h:i A', strtotime($time)) : '';
                echo '<div class="list-group-item bg-light text-dark mb-2">';
                echo '<h5>'.htmlspecialchars($row['Reason']).'</h5>';
                echo '<p>Left on: '.htmlspecialchars($displayDate).($displayTime ? ' at '.htmlspecialchars($displayTime) : '').'</p>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No approved visits found.</p>';
        }
        $stmt->close();
    } elseif ($page == 'profile') {
        echo "<h3>👤 My Profile</h3>";
        echo $msg;
        echo '<div class="card bg-light text-dark p-4 mb-4">';
        echo '<div class="row">';
        echo '<div class="col-md-4 text-center">';
        echo '<img src="'.(!empty($student['photo']) ? $student['photo'] : 'default.png').'" class="img-fluid rounded-circle mb-3" width="150">';
        echo '<h4>'.htmlspecialchars($student['SurName']." ".$student['Student_Name']." ".$student['Father_Name']).'</h4>';
        echo '<p>Enrollment No: '.$student['Enrollment_No'].'</p></div>';
        echo '<div class="col-md-8">';
        echo '<table class="table table-bordered">';
        echo '<tr><th>Email</th><td>'.$student['Email_id'].'</td></tr>';
        echo '<tr><th>Contact</th><td>'.$student['S_contact_No'].'</td></tr>';
        echo '<tr><th>Institute</th><td>'.$student['Institute'].'</td></tr>';
        echo '<tr><th>Batch</th><td>'.$student['Batch'].'</td></tr>';
        echo '<tr><th>Department</th><td>'.$student['Department'].'</td></tr>';
        echo '<tr><th>Hostel</th><td>Block '.$student['Hostel_Block_No'].', Room '.$student['Hostel_Room_No'].'</td></tr>';
        echo '<tr><th>Address</th><td>'.$student['Address_Line1'].', '.$student['Address_Line2'].'</td></tr>';
        echo '<tr><th>Date of Birth</th><td>'.$student['s_DOB'].'</td></tr>';
        echo '<tr><th>Parent Name</th><td>'.$student['P_Full_Name'].'</td></tr>';
        echo '<tr><th>Parent Contact</th><td>'.$student['P_contact_No_1'].', '.$student['P_contact_No_2'].'</td></tr>';
        echo '<tr><th>Parent Email</th><td>'.$student['P_email_id'].'</td></tr>';
        echo '</table></div></div></div>';

        echo '<div class="card bg-light text-dark p-4">';
        echo '<h5>Change Password</h5>';
        echo '<form method="POST">';
        echo '<div class="mb-3"><label class="form-label">Old Password</label><input type="password" name="old_password" class="form-control" required></div>';
        echo '<div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>';
        echo '<div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>';
        echo '<button type="submit" name="change_password" class="btn btn-primary">Update Password</button>';
        echo '</form></div>';
    }
    ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
    </script>
    <script>
    // AJAX submit for leave form
    (function(){
        const form = document.getElementById('leaveForm');
        if (!form) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const msgBox = document.getElementById('leaveMessage');
            msgBox.innerHTML = '<div class="alert alert-info">Submitting...</div>';
            const fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            }).then(res => res.json().catch(()=>({ok:false,message:'Invalid JSON response'}))).then(data => {
                if (data.ok) {
                    // Show main message
                    msgBox.innerHTML = '<div class="alert alert-success">' + (data.message || 'Submitted successfully') + '</div>';
                    // Show mail results if returned
                    if (data.mail) {
                        let details = '<div class="mt-2 small">';
                        if (data.mail.status) {
                            for (const em in data.mail.status) {
                                const st = data.mail.status[em];
                                details += '<div>' + em + ': ' + (st && st.ok ? '<span class="text-success">sent</span>' : '<span class="text-danger">failed</span>') + '</div>';
                            }
                        } else if (data.mail.note) {
                            details += '<div>' + data.mail.note + '</div>';
                        }
                        details += '</div>';
                        msgBox.innerHTML += details;
                    }
                    form.reset();
                } else {
                    msgBox.innerHTML = '<div class="alert alert-danger">Error: ' + (data.message || (data.error || 'Unknown error')) + '</div>';
                }
            }).catch(err => {
                msgBox.innerHTML = '<div class="alert alert-danger">Network error</div>';
                console.error(err);
            });
        });
    })();
    </script>
    <script>
    function printGatepass() {
        const el = document.getElementById('gatepass');
        if (!el) return alert('No gatepass available to print.');
        const win = window.open('', '_blank', 'width=700,height=800');
        win.document.write('<html><head><title>Gatepass</title>');
        win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">');
        win.document.write('</head><body>');
        win.document.write(el.outerHTML);
        win.document.write('</body></html>');
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); win.close(); }, 350);
    }
    </script>
</body>

</html>
<?php $conn->close(); ?>