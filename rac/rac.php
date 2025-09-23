<?php
session_start();
include 'db_connection.php';

// Rector's email from session
$rectorEmail = $_SESSION['R_Email'] ?? '';

if (empty($rectorEmail)) {
    die("Error: Rector email not set in session.");
}

// Fetch Rector's Hostel Block No
$rectorBlockNo = '';
$rectorQuery = $conn->query("SELECT Hostel_Block_No FROM r_db WHERE R_Email = '$rectorEmail'");
if ($rectorQuery && $rectorQuery->num_rows > 0) {
    $rectorRow = $rectorQuery->fetch_assoc();
    $rectorBlockNo = $rectorRow['Hostel_Block_No'];
} else {
    die("Error: Could not fetch Rector's block number from the database.");
}

// Fetch distinct rooms for dropdown
$roomsResult = $conn->query("SELECT DISTINCT Hostel_Room_No 
                             FROM s_db 
                             WHERE Hostel_Block_No = '$rectorBlockNo'
                             ORDER BY Hostel_Room_No ASC");

// Initialize variables
$roomFilter = '';
if (isset($_GET['room']) && $_GET['room'] != 'RoomDetail') {
    $roomFilter = $_GET['room'];
}

// Filter student data by Rector's block
$sql = "SELECT * FROM s_db WHERE Hostel_Block_No = '$rectorBlockNo'";
if ($roomFilter != '') {
    $sql .= " AND Hostel_Room_No = '$roomFilter'";
}
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <link rel="stylesheet" type="text/css" href="rac.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Professional Background */
       body {
    background: linear-gradient(to right, #141e30, #243b55);
    margin: 0;
    font-family: 'Arial', sans-serif;
    color: white;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* top aligned */
    flex-direction: column;
    height: 100vh;
    overflow: hidden; /* prevent whole page scrolling */
}

.container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    width: 95%;
    max-width: 1200px;
}

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
        }

        .header .block {
            font-size: 24px;
            font-weight: bold;
        }

        .user {
            display: flex;
            align-items: center;
        }

        .user select {
            padding: 8px;
            border-radius: 5px;
            background: #243b55;
            color: white;
            border: none;
            cursor: pointer;
        }

        .user button {
            background: #4CAF50;
            border: none;
            padding: 10px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .user button a {
            text-decoration: none;
        }

        .nav {
            margin-top: 10px;
            display: flex;
            justify-content: space-evenly;
            flex-wrap: wrap;

        }

        .nav select, .nav button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            background: #4CAF50;
            font-weight: bold;
            margin: 5px;

        }

        /* Table container with scroll */
       .excel-container {
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 15px;
    border-radius: 10px;
    width: 100%;
    height: 400px; /* fixed height */
    overflow-x: auto; /* horizontal scroll if table is wide */
    overflow-y: auto; /* vertical scroll if table is tall */
    box-shadow: 0px 0px 10px rgba(255, 255, 255, 0.2);
}




.search-container {
    display: flex;
    justify-content: flex-end; /* Moves search box to the right */
    width: 100%;
    margin-bottom: 10px;
    padding-right: 109px; /* Adds space on the right side */
}

#searchInput {
    width: 150px; /* Adjust width as needed */
    padding: 8px;
    border-radius: 5px;
    border: none;
    outline: none;
    font-size: 13px;
    
    text-align: center;
    background: rgba(255, 255, 255, 0.2);
    color: white;
}





        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px;
            text-align: center;
        }

        th {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Custom Scrollbars */
        .excel-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .excel-container::-webkit-scrollbar-thumb {
            background: #4CAF50;
            border-radius: 5px;
        }

        .excel-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
    body { background: linear-gradient(to right, #141e30, #243b55); height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; flex-direction: column; font-family: 'Arial', sans-serif; color: white; }
        .container { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; margin-top: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 10px; }
        .header .block { font-size: 24px; font-weight: bold; }
        .user { display: flex; align-items: center; }
        .user select { padding: 8px; border-radius: 5px; background: #243b55; color: white; border: none; cursor: pointer; }
        .user button { background: #4CAF50; border: none; padding: 10px 15px; margin-left: 10px; border-radius: 5px; cursor: pointer; }
        .user button a { text-decoration: none; }
        .nav { margin-top: 10px; display: flex; justify-content: space-evenly; flex-wrap: wrap; }
        .nav select, .nav button { padding: 10px; border-radius: 5px; border: none; cursor: pointer; background: #4CAF50; font-weight: bold; margin: 5px; }
        .excel-container { margin-top: 20px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 15px; border-radius: 10px; width: 90%; text-align: center; overflow-x: auto; overflow-y: auto; max-height: 400px; box-shadow: 0px 0px 10px rgba(255,255,255,0.2); }
        .search-container { display: flex; justify-content: flex-end; width: 100%; margin-bottom: 10px; padding-right: 109px; }
        #searchInput { width: 150px; padding: 8px; border-radius: 5px; border: none; outline: none; font-size: 13px; text-align: center; background: rgba(255,255,255,0.2); color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid rgba(255,255,255,0.3); padding: 10px; text-align: center; }
        th { background: rgba(255,255,255,0.2); }
        .excel-container::-webkit-scrollbar { width: 8px; height: 8px; }
        .excel-container::-webkit-scrollbar-thumb { background: #4CAF50; border-radius: 5px; }
        .excel-container::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="block">
            Hostel Block No: <strong><?php echo htmlspecialchars($rectorBlockNo); ?></strong>
        </div> 
        <div class="user">
            <span>Rector</span>
            <script>
                function navigateUser(url) {
                    if (url) window.location.href = url;
                }
            </script>
            <button><a href="login.php">Logout</a></button>
        </div>
    </div>

    <div class="nav">
        <!-- Dynamic Room Dropdown -->
        <select id="roomDetailDropdown">
            <option value="">RoomDetail</option>
            <?php while($roomRow = $roomsResult->fetch_assoc()) { ?>
                <option value="<?php echo $roomRow['Hostel_Room_No']; ?>" <?php if($roomFilter == $roomRow['Hostel_Room_No']) echo 'selected'; ?>>
                    <?php echo $roomRow['Hostel_Room_No']; ?>
                </option>
            <?php } ?>
        </select>

        <select id="gatePassDropdown" onchange="filterGatePass(this.value)">
            <option value="view">View Gate Pass Request</option>
            <option value="all">All Requests</option>
            <option value="pending">Pending Requests</option>
            <option value="approved">Approved Requests</option>
            <option value="rejected">Rejected Requests</option>
        </select>

        <button id="checkOutsideButton" onclick="window.location.href='check_student_outside_gate.php'">Check Student Outside Gate</button>
        <button id="RoomDetail" onclick="window.location.href='rac.php'">All Student Details</button>
    </div>
</div>

<h3 style="margin-top: 20px;">Student Data</h3>

<div class="search-container">
    <input type="text" id="searchInput" placeholder="Search student data..." onkeyup="searchTable()">
</div>

<div class="excel-container">
    <table>
        <thead>
            <tr>
                <th>SurName</th><th>Student Name</th><th>Father Name</th><th>Enrollment No</th>
                <th>Email ID</th><th>Contact No</th><th>Institute</th><th>Batch</th>
                <th>Department</th><th>Hostel Block</th><th>Room No</th><th>Address</th>
                <th>Date of Birth</th><th>Parent Name</th><th>Parent Contact 1</th>
                <th>Parent Contact 2</th><th>Parent Email</th>
            </tr>
        </thead>
        <tbody id="studentTableBody">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['SurName']}</td>
                        <td>{$row['Student_Name']}</td>
                        <td>{$row['Father_Name']}</td>
                        <td>{$row['Enrollment_No']}</td>
                        <td>{$row['Email_id']}</td>
                        <td>{$row['S_contact_No']}</td>
                        <td>{$row['Institute']}</td>
                        <td>{$row['Batch']}</td>
                        <td>{$row['Department']}</td>
                        <td>{$row['Hostel_Block_No']}</td>
                        <td>{$row['Hostel_Room_No']}</td>
                        <td>{$row['Address_Line1']}, {$row['Address_Line2']}</td>
                        <td>{$row['s_DOB']}</td>
                        <td>{$row['P_Full_Name']}</td>
                        <td>{$row['P_contact_No_1']}</td>
                        <td>{$row['P_contact_No_2']}</td>
                        <td>{$row['P_email_id']}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='18' class='text-center'>No Students Found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function filterGatePass(value) {
    switch (value) {
        case "view":
            window.location.href = "rac.php";
            break;
        case "all":
            window.location.href = "studentleaverequests.php";
            break;
        case "pending":
            window.location.href = "pending_requests.php";
            break;
        case "approved":
            window.location.href = "approved_requests.php";
            break;
        case "rejected":
            window.location.href = "rejected_requests.php";
            break;
        default:
            alert("Please select an action.");
    }

    // Reset the dropdown to default
    document.getElementById('gatePassDropdown').selectedIndex = 0;
}


// AJAX update table dynamically
$('#roomDetailDropdown').change(function(){
    var room = $(this).val();
    $.get('rac.php', { room: room }, function(data){
        var newBody = $(data).find('#studentTableBody').html();
        $('#studentTableBody').html(newBody);
    });
});

// Search table
function searchTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let table = document.querySelector("table");
    let rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
        let cells = rows[i].getElementsByTagName("td");
        let match = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j].innerText.toLowerCase().includes(input)) {
                match = true;
                break;
            }
        }

        rows[i].style.display = match ? "" : "none";
    }
}

</script>

</body>
</html>

<?php $conn->close(); ?>