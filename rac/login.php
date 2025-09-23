<?php
session_start();

// Database connection
$servername = "localhost";   
$username   = "root";        
$password   = "";            
$dbname     = "gatepass";    

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo "❌ Database connection failed!";
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['username']);
    $pass  = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM r_db WHERE R_Email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($pass, $row['Password'])) {
            $_SESSION['R_Email'] = $row['R_Email'];
            echo "✅ Login successful";
            exit();
        } elseif ($pass === $row['Password']) {
            $_SESSION['R_Email'] = $row['R_Email'];
            echo "✅ Login successful";
            exit();
        } else {
            echo "❌ Invalid password";
            exit();
        }
    } else {
        echo "⚠️ No user found with that email";
        exit();
    }

    $stmt->close();
}

// Process change password form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $email = trim($_POST['email']);
    $old_pass = trim($_POST['old_password']);
    $new_pass = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    // Step 1: Validate new & confirm password match
    if ($new_pass !== $confirm_pass) {
        echo "❌ New password and confirm password do not match.";
        exit();
    }

    // Step 2: Check if email exists
    $stmt = $conn->prepare("SELECT * FROM r_db WHERE R_Email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Step 3: Verify old password
        if (password_verify($old_pass, $row['Password']) || $old_pass === $row['Password']) {
            // Step 4: Hash new password and update DB
            $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE r_db SET Password = ? WHERE R_Email = ?");
            $updateStmt->bind_param("ss", $new_pass_hashed, $email);
            $updateStmt->execute();
            $updateStmt->close();

            echo "✅ Password changed successfully.";
            exit();
        } else {
            echo "❌ Old password is incorrect.";
            exit();
        }
    } else {
        echo "⚠️ No user found with that email.";
        exit();
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>LOGIN FORM ||</title>
    <link rel="stylesheet" href="./login_style.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>
    <div class="wrapper">
        <form id="loginForm" method="POST">
            <h1>Login</h1>
            
            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required />
                <i class="bx bxs-user"></i>
            </div>
            
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required />
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="remember-forgot">
                <a href="#" id="forgotPasswordLink">Forgot password?</a>
            </div>

            <input type="hidden" name="action" value="login" />
            <button type="submit" class="btn">Login</button>
        </form>
    </div>

    <!-- Change Password Popup -->
    <div id="passwordPopup" class="popup-overlay">
        <div class="popup">
            <h1>Change Password</h1>

            <form id="changePasswordForm" method="POST">
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required />
                    <i class="bx bxs-envelope"></i>
                </div>

                <div class="input-box">
                    <input type="password" name="old_password" placeholder="Old Password" required />
                    <i class="bx bxs-lock-alt"></i>
                </div>

                <div class="input-box">
                    <input type="password" name="new_password" placeholder="New Password" required />
                    <i class="bx bxs-lock-alt"></i>
                </div>

                <div class="input-box">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
                    <i class="bx bxs-lock-alt"></i>
                </div>

                <input type="hidden" name="action" value="change_password" />
                <button type="submit" class="btn">Change Password</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById("loginForm").addEventListener("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch("login.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            if (data.includes("successful")) {
                window.location = "rac.php";
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });
    });

    document.getElementById("forgotPasswordLink").addEventListener("click", function(e) {
        e.preventDefault();
        document.getElementById("passwordPopup").style.display = "flex";
    });

    document.getElementById("changePasswordForm").addEventListener("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch("login.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            if (data.includes("successfully")) {
                document.getElementById("passwordPopup").style.display = "none";
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });
    });

    window.addEventListener("click", function(e) {
        const popup = document.getElementById("passwordPopup");
        if (e.target === popup) {
            popup.style.display = "none";
        }
    });

    window.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            document.getElementById("passwordPopup").style.display = "none";
        }
    });
    </script>

    <style>
    .popup-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .popup {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(20px);
        padding: 30px 40px;
        border-radius: 10px;
        width: 400px;
    }

    .popup h1 {
        text-align: center;
        font-size: 36px;
    }

    .popup .input-box {
        position: relative;
        width: 100%;
        height: 50px;
        margin: 20px 0;
    }

    .popup .input-box input {
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        border: 2px solid rgba(255, 255, 255, .2);
        border-radius: 40px;
        font-size: 16px;
        color: #fff;
        padding: 20px 45px 20px 20px;
    }

    .popup .input-box input::placeholder {
        color: #fff;
    }

    .popup .input-box i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
    }

    .popup .btn {
        width: 100%;
        height: 45px;
        border-radius: 40px;
        border: none;
        outline: none;
        background: #fff;
        box-shadow: 0 0 10px rgba(0 , 0 , 0 , .1);
        cursor: pointer;
        font-size: 16px;
        color: #333;
        font-weight: 600;
    }
    </style>
</body>
</html>
