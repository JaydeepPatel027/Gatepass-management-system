<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "gatepass");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentId = $_POST["studentId"];
    $password  = $_POST["password"];

    // Query
    $sql = "SELECT * FROM s_db WHERE Enrollment_No = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($password === $row["Password"]) {   // ⚠️ typo is in DB column
            $_SESSION["username"] = $row["Enrollment_No"];
            header("Location: student.php"); // ✅ redirect only on success
            exit();
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "❌ Student ID not found!";
    }

    $stmt->close();
}
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Gateway - Student Login</title>
    <style>
    /* Reset and base styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: linear-gradient(135deg, #fefce8 0%, #fef3c7 50%, #ecfdf5 100%);
        min-height: 100vh;
        overflow-x: hidden;
    }

    .container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        position: relative;
    }

    /* Floating shapes */
    .floating-shapes {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(2px);
    }

    .shape-1 {
        top: 5rem;
        left: 5rem;
        width: 8rem;
        height: 8rem;
        background: linear-gradient(135deg, rgba(161, 98, 7, 0.2), rgba(132, 204, 22, 0.2));
        animation: float 4s ease-in-out infinite;
    }

    .shape-2 {
        top: 10rem;
        right: 8rem;
        width: 6rem;
        height: 6rem;
        background: linear-gradient(135deg, rgba(132, 204, 22, 0.3), rgba(161, 98, 7, 0.3));
        border-radius: 0.5rem;
        transform: rotate(45deg);
        animation: float 4s ease-in-out infinite 1s;
    }

    .shape-3 {
        bottom: 8rem;
        left: 10rem;
        width: 10rem;
        height: 10rem;
        background: linear-gradient(135deg, rgba(161, 98, 7, 0.15), rgba(132, 204, 22, 0.15));
        animation: float 4s ease-in-out infinite 2s;
    }

    .shape-4 {
        bottom: 5rem;
        right: 5rem;
        width: 7rem;
        height: 7rem;
        background: linear-gradient(135deg, rgba(161, 98, 7, 0.25), rgba(132, 204, 22, 0.25));
        border-radius: 0.5rem;
        animation: float 4s ease-in-out infinite 0.5s;
    }

    .shape-5 {
        top: 50%;
        left: 2.5rem;
        width: 4rem;
        height: 4rem;
        background: linear-gradient(to right, #fcd34d, #fb923c);
        opacity: 0.6;
        animation: wiggle 2s ease-in-out infinite 3s;
    }

    .shape-6 {
        top: 25%;
        right: 2.5rem;
        width: 5rem;
        height: 5rem;
        background: linear-gradient(to right, #86efac, #60a5fa);
        opacity: 0.5;
        animation: bounce-in 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 4s;
    }

    /* Login card */
    .login-card {
        width: 100%;
        max-width: 28rem;
        background: linear-gradient(135deg, white 0%, white 70%, rgba(254, 252, 232, 0.5) 100%);
        border-radius: 1rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border: 2px solid rgba(161, 98, 7, 0.1);
        position: relative;
        z-index: 10;
        backdrop-filter: blur(8px);
        animation: slide-in-up 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* Header */
    .card-header {
        text-align: center;
        padding: 2rem 2rem 0;
        margin-bottom: 2rem;
    }

    .logo-container {
        position: relative;
        margin: 0 auto 1.5rem;
        width: fit-content;
    }

    .logo-circle {
        width: 5rem;
        height: 5rem;
        background: linear-gradient(135deg, #a16207, #84cc16);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        animation: pulse-glow 3s ease-in-out infinite;
    }

    .graduation-cap {
        width: 2.5rem;
        height: 2.5rem;
        color: white;
        animation: bounce-in 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .sparkles {
        position: absolute;
        top: -0.5rem;
        right: -0.5rem;
        width: 1.5rem;
        height: 1.5rem;
        color: #84cc16;
        animation: wiggle 2s ease-in-out infinite;
    }

    .mini-icon {
        position: absolute;
        width: 1.25rem;
        height: 1.25rem;
        color: rgba(161, 98, 7, 0.6);
    }

    .mini-icon-1 {
        left: -2rem;
        top: 0.5rem;
        animation: float 4s ease-in-out infinite 1s;
    }

    .mini-icon-2 {
        right: -2rem;
        top: 0.5rem;
        animation: float 4s ease-in-out infinite 2s;
    }

    .title-section {
        animation: fade-in 1s ease-out 0.3s both;
    }

    .title {
        font-size: 1.875rem;
        font-weight: bold;
        background: linear-gradient(to right, #a16207, #84cc16);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.75rem;
    }

    .subtitle {
        font-size: 1.125rem;
        color: #6b7280;
        font-weight: 500;
    }

    /* Form */
    .card-content {
        padding: 0 2rem 2rem;
    }

    .login-form {
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        animation: fade-in 1s ease-out both;
    }

    .form-group:first-child {
        animation-delay: 0.5s;
    }

    .form-group:last-child {
        animation-delay: 0.7s;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
    }

    .label-icon {
        width: 1rem;
        height: 1rem;
        color: #a16207;
    }

    .input-wrapper {
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1.125rem;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        transition: all 0.3s ease;
        outline: none;
    }

    .form-input:hover {
        border-color: rgba(161, 98, 7, 0.5);
    }

    .form-input:focus {
        border-color: #a16207;
        box-shadow: 0 0 0 4px rgba(161, 98, 7, 0.2);
    }

    .password-input {
        padding-right: 3rem;
    }

    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.2s ease;
    }

    .password-toggle:hover {
        color: #a16207;
    }

    .password-toggle svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .input-glow {
        position: absolute;
        inset: 0;
        border-radius: 0.75rem;
        background: linear-gradient(to right, rgba(161, 98, 7, 0.1), rgba(132, 204, 22, 0.1));
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
        z-index: -1;
    }

    .input-wrapper:hover .input-glow {
        opacity: 1;
    }

    /* Submit button */
    .submit-btn {
        width: 100%;
        background: linear-gradient(to right, #a16207, #84cc16);
        color: white;
        font-weight: bold;
        padding: 1rem;
        font-size: 1.125rem;
        border: none;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.3s ease;
        transform: scale(1);
        animation: fade-in 1s ease-out 0.9s both;
        position: relative;
        overflow: hidden;
    }

    .submit-btn:hover {
        background: linear-gradient(to right, rgba(161, 98, 7, 0.9), rgba(132, 204, 22, 0.9));
        transform: scale(1.02);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .submit-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: scale(1);
    }

    .btn-content,
    .loading-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .btn-sparkles {
        animation: wiggle 2s ease-in-out infinite;
    }

    .spinner {
        width: 1.25rem;
        height: 1.25rem;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top: 3px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Help section */
    .help-section {
        text-align: center;
        margin-bottom: 1.5rem;
        animation: fade-in 1s ease-out 1.1s both;
    }

    .forgot-password {
        background: none;
        border: none;
        color: #84cc16;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s ease;
        margin-bottom: 1rem;
    }

    .forgot-password:hover {
        color: rgba(132, 204, 22, 0.8);
        text-decoration: underline;
    }

    .contact-section {
        padding-top: 1rem;
        border-top: 1px solid rgba(229, 231, 235, 0.5);
    }

    .help-text {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.75rem;
    }

    .contact-btn {
        background: linear-gradient(to right, rgba(132, 204, 22, 0.5), rgba(161, 98, 7, 0.2));
        border: 1px solid rgba(161, 98, 7, 0.2);
        color: #374151;
        font-size: 0.75rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .contact-btn:hover {
        background: linear-gradient(to right, rgba(132, 204, 22, 0.7), rgba(161, 98, 7, 0.3));
    }

    /* Security info */
    .security-info {
        background: linear-gradient(to right, rgba(161, 98, 7, 0.05), rgba(132, 204, 22, 0.05));
        border: 1px solid rgba(161, 98, 7, 0.2);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        gap: 0.75rem;
        animation: fade-in 1s ease-out 1.3s both;
    }

    .security-icon {
        width: 2rem;
        height: 2rem;
        background: linear-gradient(135deg, #a16207, #84cc16);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .security-icon svg {
        width: 1rem;
        height: 1rem;
        color: white;
    }

    .security-text {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .security-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .hidden {
        display: none !important;
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 60;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.18s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }

    .modal {
        background: white;
        border-radius: 0.75rem;
        max-width: 420px;
        width: 92%;
        padding: 1.25rem;
        box-shadow: 0 30px 60px rgba(2, 6, 23, 0.35);
        transform: translateY(0);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-body {
        color: #374151;
        font-size: 0.95rem;
    }

    .contact-list {
        list-style: none;
        padding: 0;
        margin: 0.5rem 0 0;
    }

    .contact-list li {
        margin-bottom: 0.5rem;
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    body.modal-open {
        overflow: hidden;
    }

    /* Animations */
    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        33% {
            transform: translateY(-15px) rotate(2deg);
        }

        66% {
            transform: translateY(-5px) rotate(-1deg);
        }
    }

    @keyframes pulse-glow {

        0%,
        100% {
            box-shadow: 0 0 30px rgba(161, 98, 7, 0.4);
            transform: scale(1);
        }

        50% {
            box-shadow: 0 0 50px rgba(161, 98, 7, 0.6);
            transform: scale(1.05);
        }
    }

    @keyframes slide-in-up {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes bounce-in {
        0% {
            opacity: 0;
            transform: scale(0.3) rotate(-10deg);
        }

        50% {
            opacity: 1;
            transform: scale(1.1) rotate(5deg);
        }

        100% {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
    }

    @keyframes wiggle {

        0%,
        100% {
            transform: rotate(0deg);
        }

        25% {
            transform: rotate(3deg);
        }

        75% {
            transform: rotate(-3deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive design */
    @media (max-width:640px) {
        .container {
            padding: 0.5rem;
        }

        .login-card {
            max-width: 100%;
        }

        .card-header {
            padding: 1.5rem 1.5rem 0;
        }

        .card-content {
            padding: 0 1.5rem 1.5rem;
        }

        .title {
            font-size: 1.5rem;
        }

        .subtitle {
            font-size: 1rem;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Floating geometric shapes -->
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="shape shape-5"></div>
            <div class="shape shape-6"></div>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <!-- Header -->
            <div class="card-header">
                <div class="logo-container">
                    <div class="logo-circle">
                        <svg class="graduation-cap" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                            <path d="M6 12v5c3 3 9 3 12 0v-5" />
                        </svg>
                        <svg class="sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .962 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.582a.5.5 0 0 1 0 .962L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.962 0z" />
                        </svg>
                    </div>
                    <svg class="mini-icon mini-icon-1" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M2 3h6l2 13h10l4-8H8" />
                    </svg>
                    <svg class="mini-icon mini-icon-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <div class="title-section">
                    <h1 class="title">Campus Gateway</h1>
                    <p class="subtitle">Your digital key to campus life ✨</p>
                </div>
            </div>
<?php if (!empty($error)): ?>
    <div class="error-message" style="color:red; margin-bottom:10px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

            <!-- Form Content -->
            <div class="card-content">
                <form id="loginForm" class="login-form" method="POST" action="">
    <div class="form-group">
        <label for="studentId" class="form-label">
            <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            Student ID
        </label>
        <div class="input-wrapper">
            <input type="text" id="studentId" name="studentId" placeholder="Enter your student ID"
                class="form-input" required />
            <div class="input-glow"></div>
        </div>
    </div>

    <div class="form-group">
        <label for="password" class="form-label">
            <svg class="label-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            Password
        </label>
        <div class="input-wrapper">
            <input type="password" id="password" name="password" placeholder="Enter your password"
                class="form-input password-input" required />
            <button type="button" class="password-toggle" id="passwordToggle">
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg class="eye-off-icon hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                    <path
                        d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                    <line x1="2" x2="22" y1="2" y2="22" />
                </svg>
            </button>
            <div class="input-glow"></div>
        </div>
    </div>

    <button type="submit" class="submit-btn" id="submitBtn">
        <div class="btn-content">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
            </svg>
            <span class="btn-text">Enter Campus</span>
            <svg class="btn-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path
                    d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .962 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.582a.5.5 0 0 1 0 .962L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.962 0z" />
            </svg>
        </div>
        <div class="loading-content hidden">
            <div class="spinner"></div>
            <span>Unlocking Gateway...</span>
        </div>
    </button>
</form>


                <div class="help-section">
                    <span class="btn btn-warning disabled">⚠️ If you don't have an ID and password, please contact the admin.</span>
                    <div class="contact-section">
                            <button class="contact-btn" id="contactBtn">📞 Contact Admin</button>
                        </div>

                    <!-- Contact Admin Modal -->
                    <div class="modal-overlay" id="contactModal" aria-hidden="true">
                        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="contactTitle">
                            <div class="modal-header">
                                <h3 id="contactTitle">Contact Admin</h3>
                                <button class="modal-close" id="modalClose" aria-label="Close modal">✕</button>
                            </div>
                            <div class="modal-body">
                                <p>If you don't have an ID and password or need help, contact the admin using one of the options below.</p>
                                <ul class="contact-list">
                                    <li>🏫 Name: Jaspreet Singh</li>
                                    <li>📧 Email: <a href="mailto:jaspreetsingh88995@gmail.com">Jaspreetsingh88995@gmail.com</a></li>
                                    <li>📞 Phone: <a href="tel:+918799177132">+91 8799177132</a></li>
                                    <li>🏫 Office: Room 204, Admin Building</li>
                                </ul>
                                <div style="margin-top:1rem; text-align:right;">
                                    <button class="submit-btn" id="modalOk">OK</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>

    <script>
    const passwordInput = document.getElementById("password")
    const passwordToggle = document.getElementById("passwordToggle")
    const eyeIcon = passwordToggle.querySelector(".eye-icon")
    const eyeOffIcon = passwordToggle.querySelector(".eye-off-icon")
    const loginForm = document.getElementById("loginForm")
    const submitBtn = document.getElementById("submitBtn")
    const btnContent = submitBtn.querySelector(".btn-content")
    const loadingContent = submitBtn.querySelector(".loading-content")
    const formData = {
        studentId: "",
        password: ""
    }
    let isLoading = false

    passwordToggle.addEventListener("click", () => {
        const isPassword = passwordInput.type === "password"
        passwordInput.type = isPassword ? "text" : "password"
        eyeIcon.classList.toggle("hidden", isPassword)
        eyeOffIcon.classList.toggle("hidden", !isPassword)
    })

    document.getElementById("studentId").addEventListener("input", e => formData.studentId = e.target.value)
    passwordInput.addEventListener("input", e => formData.password = e.target.value)



    document.querySelectorAll(".form-input").forEach(input => {
        input.addEventListener("focus", () => input.parentElement.style.transform = "scale(1.02)")
        input.addEventListener("blur", () => input.parentElement.style.transform = "scale(1)")
    })

    const forgotBtn = document.querySelector(".forgot-password")
    if (forgotBtn) {
        forgotBtn.addEventListener("click", () => {
            alert("Password reset functionality would be implemented here.")
        })
    }

    // Contact Admin modal logic
    const contactBtn = document.getElementById('contactBtn')
    const contactModal = document.getElementById('contactModal')
    const modalClose = document.getElementById('modalClose')
    const modalOk = document.getElementById('modalOk')

    function openModal() {
        contactModal.classList.add('active')
        contactModal.setAttribute('aria-hidden', 'false')
        document.body.classList.add('modal-open')
        // focus first focusable element
        modalClose.focus()
    }

    function closeModal() {
        contactModal.classList.remove('active')
        contactModal.setAttribute('aria-hidden', 'true')
        document.body.classList.remove('modal-open')
        contactBtn.focus()
    }

    contactBtn.addEventListener('click', openModal)
    modalClose.addEventListener('click', closeModal)
    modalOk.addEventListener('click', closeModal)

    // Close when clicking outside modal content
    contactModal.addEventListener('click', (e) => {
        if (e.target === contactModal) closeModal()
    })

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && contactModal.classList.contains('active')) {
            closeModal()
        }
    })

    window.addEventListener("load", () => {
        const shapes = document.querySelectorAll(".shape")
        shapes.forEach((shape, index) => shape.style.animationDelay = `${index*0.5}s`)
        document.addEventListener("mousemove", e => {
            const mouseX = e.clientX / window.innerWidth
            const mouseY = e.clientY / window.innerHeight
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.5
                const x = (mouseX - 0.5) * speed
                const y = (mouseY - 0.5) * speed
                shape.style.transform += ` translate(${x}px,${y}px)`
            })
        })
    })
    </script>
</body>

</html>