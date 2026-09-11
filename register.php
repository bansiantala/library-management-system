<?php

include "config/database.php";

$message = "";
$messageType = "";

$name = "";
$email = "";
$phone = "";

/* =====================================================
   REGISTRATION PROCESS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {

    /* =================================================
       GET FORM DATA
    ================================================= */

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* =================================================
       PHP SERVER-SIDE VALIDATION
    ================================================= */

    // 1. Required fields
    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $message = "Please fill all required fields.";
        $messageType = "danger";

    }

    // 2. Name minimum length
    elseif (strlen($name) < 2) {

        $message = "Name must be at least 2 characters.";
        $messageType = "danger";

    }

    // 3. Name maximum length
    elseif (strlen($name) > 100) {

        $message = "Name cannot exceed 100 characters.";
        $messageType = "danger";

    }

    // 4. Name only letters and spaces
    elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {

        $message = "Name can contain only letters and spaces.";
        $messageType = "danger";

    }

    // 5. Email maximum length
    elseif (strlen($email) > 100) {

        $message = "Email address cannot exceed 100 characters.";
        $messageType = "danger";

    }

    // 6. Email validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "danger";

    }

    // 7. Phone validation
    elseif ($phone !== "" && !preg_match("/^[0-9]{10}$/", $phone)) {

        $message = "Phone number must contain exactly 10 digits.";
        $messageType = "danger";

    }

    // 8. Password minimum length
    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "danger";

    }

    // 9. Password maximum length
    elseif (strlen($password) > 255) {

        $message = "Password cannot exceed 255 characters.";
        $messageType = "danger";

    }

    // 10. Password match
    elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $messageType = "danger";

    }

    /* =================================================
       DATABASE VALIDATION
    ================================================= */

    else {

        /* =================================================
           CHECK DATABASE CONNECTION
        ================================================= */

        if (!isset($conn) || $conn->connect_error) {

            $message = "Database connection failed.";
            $messageType = "danger";

        } else {

            /* =================================================
               CHECK EMAIL ALREADY EXISTS
            ================================================= */

            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            if (!$stmt) {

                $message = "Unable to check email. Please try again.";
                $messageType = "danger";

            } else {

                $stmt->bind_param("s", $email);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    $message = "This email is already registered.";
                    $messageType = "danger";

                    $stmt->close();

                } else {

                    $stmt->close();

                    /* =================================================
                       HASH PASSWORD
                    ================================================= */

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    if ($hashedPassword === false) {

                        $message = "Password processing failed.";
                        $messageType = "danger";

                    } else {

                        /* =================================================
                           DEFAULT USER ROLE
                        ================================================= */

                        $role = "user";

                        /* =================================================
                           INSERT USER
                        ================================================= */

                        $stmt = $conn->prepare(
                            "INSERT INTO users
                            (name, email, password, phone, role)
                            VALUES (?, ?, ?, ?, ?)"
                        );

                        if (!$stmt) {

                            $message = "Registration failed. Please try again.";
                            $messageType = "danger";

                        } else {

                            $stmt->bind_param(
                                "sssss",
                                $name,
                                $email,
                                $hashedPassword,
                                $phone,
                                $role
                            );

                            if ($stmt->execute()) {

                                $message =
                                    "Registration successful! You can now login.";

                                $messageType = "success";

                                /* =================================================
                                   CLEAR FORM DATA AFTER SUCCESS
                                ================================================= */

                                $name = "";
                                $email = "";
                                $phone = "";

                            } else {

                                $message =
                                    "Registration failed. Please try again.";

                                $messageType = "danger";
                            }

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Create Account | Library Management System
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                radial-gradient(
                    circle at top left,
                    #dbeafe,
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    #ede9fe,
                    transparent 35%
                ),
                #f5f7ff;
        }

        /* =====================================================
           MAIN WRAPPER
        ===================================================== */

        .register-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }

        /* =====================================================
           MAIN CARD
        ===================================================== */

        .register-box {

            width: 100%;

            max-width: 900px;

            min-height: 540px;

            display: grid;

            grid-template-columns: 40% 60%;

            background: #ffffff;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 20px 55px
                rgba(37, 54, 90, 0.14);
        }

        /* =====================================================
           LEFT PANEL
        ===================================================== */

        .register-left {

            position: relative;

            padding: 38px 35px;

            color: #ffffff;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #2563eb 0%,
                    #4f46e5 55%,
                    #7c3aed 100%
                );
        }

        .register-left::before {

            content: "";

            position: absolute;

            width: 240px;

            height: 240px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.08);

            top: -90px;

            right: -90px;
        }

        .register-left::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.06);

            bottom: -70px;

            left: -70px;
        }

        .left-content {

            position: relative;

            z-index: 2;
        }

        /* =====================================================
           LOGO
        ===================================================== */

        .library-logo {

            width: 52px;

            height: 52px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(255,255,255,0.18);

            border:
                1px solid
                rgba(255,255,255,0.25);

            font-size: 24px;

            margin-bottom: 20px;

            backdrop-filter: blur(10px);
        }

        /* =====================================================
           LEFT HEADING
        ===================================================== */

        .register-left h1 {

            font-size: 29px;

            font-weight: 800;

            line-height: 1.2;

            margin-bottom: 12px;
        }

        .register-left .description {

            font-size: 13px;

            line-height: 1.6;

            color:
                rgba(255,255,255,0.85);

            max-width: 320px;
        }

        /* =====================================================
           FEATURES
        ===================================================== */

        .features {

            margin-top: 25px;
        }

        .feature-item {

            display: flex;

            align-items: center;

            gap: 11px;

            margin-bottom: 14px;
        }

        .feature-icon {

            width: 35px;

            height: 35px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.14);

            border:
                1px solid
                rgba(255,255,255,0.18);

            font-size: 15px;
        }

        .feature-item span {

            font-size: 12px;

            color:
                rgba(255,255,255,0.92);
        }

        /* =====================================================
           LEFT BOTTOM
        ===================================================== */

        .left-bottom {

            position: absolute;

            left: 35px;

            bottom: 22px;

            z-index: 2;

            font-size: 10px;

            color:
                rgba(255,255,255,0.65);
        }

        /* =====================================================
           RIGHT PANEL
        ===================================================== */

        .register-right {

            padding: 35px 42px;

            display: flex;

            align-items: center;
        }

        .form-container {

            width: 100%;

            max-width: 480px;

            margin: auto;
        }

        /* =====================================================
           HEADER
        ===================================================== */

        .form-header {

            margin-bottom: 20px;
        }

        .form-header h2 {

            font-size: 25px;

            font-weight: 800;

            color: #111827;

            margin-bottom: 5px;
        }

        .form-header p {

            font-size: 13px;

            color: #6b7280;
        }

        /* =====================================================
           ALERT
        ===================================================== */

        .alert {

            border: none;

            border-radius: 10px;

            font-size: 12px;

            padding: 9px 12px;

            margin-bottom: 14px;

            display: flex;

            align-items: center;

            gap: 7px;
        }

        /* =====================================================
           FORM ROW
        ===================================================== */

        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 13px;
        }

        /* =====================================================
           FORM GROUP
        ===================================================== */

        .form-group {

            margin-bottom: 13px;
        }

        .form-label {

            display: block;

            font-size: 12px;

            font-weight: 700;

            color: #374151;

            margin-bottom: 5px;
        }

        .required {

            color: #ef4444;
        }

        /* =====================================================
           INPUT
        ===================================================== */

        .input-wrapper {

            position: relative;
        }

        .input-icon {

            position: absolute;

            left: 12px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #9ca3af;

            font-size: 14px;

            pointer-events: none;

            z-index: 2;
        }

        .form-control {

            width: 100%;

            height: 42px;

            border:
                1px solid #e2e5eb;

            border-radius: 9px;

            padding:
                8px 38px;

            font-size: 13px;

            color: #111827;

            background: #f9fafb;

            transition:
                all 0.2s ease;
        }

        .form-control::placeholder {

            color: #a1a8b3;
        }

        .form-control:focus {

            outline: none;

            border-color: #4f46e5;

            background: #ffffff;

            box-shadow:
                0 0 0 4px
                rgba(79,70,229,0.08);
        }

        /* =====================================================
           PASSWORD TOGGLE
        ===================================================== */

        .password-toggle {

            position: absolute;

            right: 11px;

            top: 50%;

            transform:
                translateY(-50%);

            border: none;

            background: transparent;

            color: #9ca3af;

            cursor: pointer;

            font-size: 14px;

            padding: 3px;
        }

        .password-toggle:hover {

            color: #4f46e5;
        }

        /* =====================================================
           PASSWORD STRENGTH
        ===================================================== */

        .password-strength {

            margin-top: 5px;

            display: none;
        }

        .strength-bar {

            height: 3px;

            width: 100%;

            background: #e5e7eb;

            border-radius: 10px;

            overflow: hidden;
        }

        .strength-fill {

            height: 100%;

            width: 0;

            border-radius: 10px;

            transition:
                all 0.3s ease;
        }

        .strength-text {

            font-size: 10px;

            color: #6b7280;

            margin-top: 3px;
        }

        /* =====================================================
           REGISTER BUTTON
        ===================================================== */

        .btn-register {

            width: 100%;

            height: 43px;

            border: none;

            border-radius: 9px;

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5,
                    #7c3aed
                );

            box-shadow:
                0 8px 20px
                rgba(79,70,229,0.22);

            transition:
                all 0.25s ease;

            cursor: pointer;
        }

        .btn-register:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(79,70,229,0.30);
        }

        .btn-register i {

            margin-right: 7px;
        }

        /* =====================================================
           LOGIN LINK
        ===================================================== */

        .login-link {

            text-align: center;

            margin-top: 15px;

            font-size: 12px;

            color: #6b7280;
        }

        .login-link a {

            color: #4f46e5;

            font-weight: 700;

            text-decoration: none;
        }

        .login-link a:hover {

            text-decoration: underline;
        }

        /* =====================================================
           HOME LINK
        ===================================================== */

        .home-link {

            text-align: center;

            margin-top: 9px;
        }

        .home-link a {

            color: #6b7280;

            font-size: 12px;

            text-decoration: none;
        }

        .home-link a:hover {

            color: #4f46e5;
        }

        /* =====================================================
           SECURITY
        ===================================================== */

        .security-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            margin-top: 13px;

            font-size: 10px;

            color: #9ca3af;
        }

        .security-note i {

            color: #10b981;
        }

        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {

            .register-box {

                max-width: 650px;

                min-height: auto;

                grid-template-columns: 1fr;
            }

            .register-left {

                padding: 30px 35px;

                min-height: 230px;
            }

            .register-left h1 {

                font-size: 26px;
            }

            .features {

                display: flex;

                gap: 15px;

                margin-top: 20px;
            }

            .feature-item {

                margin-bottom: 0;
            }

            .left-bottom {

                display: none;
            }

            .register-right {

                padding: 30px 35px;
            }
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .register-wrapper {

                padding: 10px;
            }

            .register-box {

                width: 100%;

                border-radius: 15px;
            }

            .register-left {

                padding: 25px 22px;

                min-height: 200px;
            }

            .library-logo {

                width: 45px;

                height: 45px;

                font-size: 21px;

                margin-bottom: 15px;
            }

            .register-left h1 {

                font-size: 23px;
            }

            .register-left .description {

                font-size: 12px;
            }

            .features {

                display: none;
            }

            .register-right {

                padding: 25px 20px;
            }

            .form-header h2 {

                font-size: 23px;
            }

            .form-row {

                grid-template-columns: 1fr;

                gap: 0;
            }

            .form-control {

                height: 42px;
            }

            .btn-register {

                height: 43px;
            }
        }

        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .register-wrapper {

                padding: 7px;
            }

            .register-left {

                padding: 22px 18px;
            }

            .register-left h1 {

                font-size: 21px;
            }

            .register-right {

                padding: 22px 16px;
            }

            .form-header h2 {

                font-size: 21px;
            }

            .form-control {

                height: 40px;

                font-size: 12px;
            }

            .btn-register {

                height: 41px;

                font-size: 12px;
            }
        }

    </style>

</head>

<body>

<div class="register-wrapper">

    <div class="register-box">

        <!-- =================================================
             LEFT PANEL
        ================================================= -->

        <div class="register-left">

            <div class="left-content">

                <div class="library-logo">
                    <i class="bi bi-book-half"></i>
                </div>

                <h1>
                    Join Our<br>
                    Library Community
                </h1>

                <p class="description">
                    Create your library account and get
                    easy access to books, issue requests,
                    reading history and more.
                </p>

                <div class="features">

                    <div class="feature-item">

                        <div class="feature-icon">
                            <i class="bi bi-search"></i>
                        </div>

                        <span>
                            Browse thousands of books
                        </span>

                    </div>

                    <div class="feature-item">

                        <div class="feature-icon">
                            <i class="bi bi-journal-bookmark"></i>
                        </div>

                        <span>
                            Manage your issued books
                        </span>

                    </div>

                    <div class="feature-item">

                        <div class="feature-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>

                        <span>
                            Track your reading history
                        </span>

                    </div>

                </div>

            </div>

            <div class="left-bottom">

                <i class="bi bi-shield-check"></i>

                Secure Library Management System

            </div>

        </div>

        <!-- =================================================
             RIGHT PANEL
        ================================================= -->

        <div class="register-right">

            <div class="form-container">

                <div class="form-header">

                    <h2>
                        Create Account
                    </h2>

                    <p>
                        Fill in your details to become
                        a library member.
                    </p>

                </div>

                <!-- =================================================
                     PHP MESSAGE
                ================================================= -->

                <?php if (!empty($message)) { ?>

                    <div
                        class="alert
                        <?php
                        echo ($messageType === "success")
                            ? "alert-success"
                            : "alert-danger";
                        ?>"
                    >

                        <?php if ($messageType === "success") { ?>

                            <i class="bi bi-check-circle"></i>

                        <?php } else { ?>

                            <i class="bi bi-exclamation-circle"></i>

                        <?php } ?>

                        <span>
                            <?php echo htmlspecialchars($message); ?>
                        </span>

                    </div>

                <?php } ?>

                <!-- =================================================
                     REGISTRATION FORM
                ================================================= -->

                <form
                    method="POST"
                    action="register.php"
                    autocomplete="off"
                    onsubmit="return validateRegisterForm();"
                >

                    <!-- NAME + PHONE -->

                    <div class="form-row">

                        <!-- NAME -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="name"
                            >
                                Full Name
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="bi bi-person input-icon"></i>

                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    placeholder="Enter full name"
                                    value="<?php echo htmlspecialchars($name); ?>"
                                    maxlength="100"
                                    autocomplete="off"
                                    required
                                >

                            </div>

                        </div>

                        <!-- PHONE -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="phone"
                            >
                                Phone Number
                            </label>

                            <div class="input-wrapper">

                                <i class="bi bi-phone input-icon"></i>

                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    class="form-control"
                                    placeholder="Enter phone number"
                                    value="<?php echo htmlspecialchars($phone); ?>"
                                    maxlength="10"
                                    inputmode="numeric"
                                    autocomplete="off"
                                >

                            </div>

                        </div>

                    </div>

                    <!-- EMAIL -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="email"
                        >
                            Email Address
                            <span class="required">*</span>
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-envelope input-icon"></i>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                placeholder="Enter your email address"
                                value="<?php echo htmlspecialchars($email); ?>"
                                maxlength="100"
                                autocomplete="off"
                                required
                            >

                        </div>

                    </div>

                    <!-- PASSWORD + CONFIRM PASSWORD -->

                    <div class="form-row">

                        <!-- PASSWORD -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="password"
                            >
                                Password
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="bi bi-lock input-icon"></i>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    placeholder="Minimum 6 characters"
                                    minlength="6"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    required
                                    oninput="checkPasswordStrength();"
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword('password', this)"
                                    title="Show password"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                            </div>

                            <!-- PASSWORD STRENGTH -->

                            <div
                                class="password-strength"
                                id="passwordStrength"
                            >

                                <div class="strength-bar">

                                    <div
                                        class="strength-fill"
                                        id="strengthFill"
                                    ></div>

                                </div>

                                <div
                                    class="strength-text"
                                    id="strengthText"
                                ></div>

                            </div>

                        </div>

                        <!-- CONFIRM PASSWORD -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="confirm_password"
                            >
                                Confirm Password
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="bi bi-shield-lock input-icon"></i>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    class="form-control"
                                    placeholder="Confirm password"
                                    minlength="6"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword('confirm_password', this)"
                                    title="Show password"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                            </div>

                        </div>

                    </div>

                    <!-- REGISTER BUTTON -->

                    <button
                        type="submit"
                        name="register"
                        class="btn-register"
                    >

                        <i class="bi bi-person-plus-fill"></i>

                        Create Library Account

                    </button>

                </form>

                <!-- LOGIN -->

                <div class="login-link">

                    Already have an account?

                    <a href="login.php">
                        Login here
                    </a>

                </div>

                <!-- HOME -->

                <div class="home-link">

                    <a href="index.php">

                        <i class="bi bi-arrow-left"></i>

                        Back to Home

                    </a>

                </div>

                <!-- SECURITY -->

                <div class="security-note">

                    <i class="bi bi-shield-check"></i>

                    Your information is securely stored.

                </div>

            </div>

        </div>

    </div>

</div>

<script>

/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    const icon = button.querySelector("i");

    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

        button.setAttribute(
            "title",
            "Hide password"
        );

    } else {

        input.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

        button.setAttribute(
            "title",
            "Show password"
        );
    }
}


/* =====================================================
   PASSWORD STRENGTH
===================================================== */

function checkPasswordStrength() {

    const password =
        document.getElementById("password").value;

    const strengthBox =
        document.getElementById("passwordStrength");

    const strengthFill =
        document.getElementById("strengthFill");

    const strengthText =
        document.getElementById("strengthText");

    if (password.length === 0) {

        strengthBox.style.display = "none";

        strengthFill.style.width = "0%";

        strengthText.textContent = "";

        return;
    }

    strengthBox.style.display = "block";

    let score = 0;

    if (password.length >= 6) {
        score++;
    }

    if (password.length >= 8) {
        score++;
    }

    if (/[A-Z]/.test(password)) {
        score++;
    }

    if (/[0-9]/.test(password)) {
        score++;
    }

    if (/[^A-Za-z0-9]/.test(password)) {
        score++;
    }

    let width = "0%";
    let text = "";

    if (score <= 1) {

        width = "20%";
        text = "Very Weak";

    } else if (score === 2) {

        width = "40%";
        text = "Weak";

    } else if (score === 3) {

        width = "60%";
        text = "Medium";

    } else if (score === 4) {

        width = "80%";
        text = "Strong";

    } else {

        width = "100%";
        text = "Very Strong";
    }

    strengthFill.style.width = width;

    strengthText.textContent = text;
}


/* =====================================================
   FORM VALIDATION
===================================================== */

function validateRegisterForm() {

    const name =
        document.getElementById("name").value.trim();

    const email =
        document.getElementById("email").value.trim();

    const phone =
        document.getElementById("phone").value.trim();

    const password =
        document.getElementById("password").value;

    const confirmPassword =
        document.getElementById("confirm_password").value;


    /* =================================================
       NAME VALIDATION
    ================================================= */

    if (name === "") {

        alert("Please enter your full name.");

        document.getElementById("name").focus();

        return false;
    }

    if (name.length < 2) {

        alert("Name must be at least 2 characters.");

        document.getElementById("name").focus();

        return false;
    }

    if (name.length > 100) {

        alert("Name cannot exceed 100 characters.");

        document.getElementById("name").focus();

        return false;
    }

    const namePattern =
        /^[A-Za-z ]+$/;

    if (!namePattern.test(name)) {

        alert(
            "Name can contain only letters and spaces."
        );

        document.getElementById("name").focus();

        return false;
    }


    /* =================================================
       EMAIL VALIDATION
    ================================================= */

    if (email === "") {

        alert("Please enter your email address.");

        document.getElementById("email").focus();

        return false;
    }

    if (email.length > 100) {

        alert(
            "Email address cannot exceed 100 characters."
        );

        document.getElementById("email").focus();

        return false;
    }

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailPattern.test(email)) {

        alert(
            "Please enter a valid email address."
        );

        document.getElementById("email").focus();

        return false;
    }


    /* =================================================
       PHONE VALIDATION
    ================================================= */

    if (phone !== "") {

        const phonePattern =
            /^[0-9]{10}$/;

        if (!phonePattern.test(phone)) {

            alert(
                "Phone number must contain exactly 10 digits."
            );

            document.getElementById("phone").focus();

            return false;
        }
    }


    /* =================================================
       PASSWORD VALIDATION
    ================================================= */

    if (password === "") {

        alert("Please enter a password.");

        document.getElementById("password").focus();

        return false;
    }

    if (password.length < 6) {

        alert(
            "Password must be at least 6 characters."
        );

        document.getElementById("password").focus();

        return false;
    }

    if (password.length > 255) {

        alert(
            "Password cannot exceed 255 characters."
        );

        document.getElementById("password").focus();

        return false;
    }


    /* =================================================
       CONFIRM PASSWORD
    ================================================= */

    if (confirmPassword === "") {

        alert("Please confirm your password.");

        document
            .getElementById("confirm_password")
            .focus();

        return false;
    }

    if (password !== confirmPassword) {

        alert("Passwords do not match.");

        document
            .getElementById("confirm_password")
            .focus();

        return false;
    }


    /* =================================================
       SUCCESS
    ================================================= */

    return true;
}


/* =====================================================
   EXTRA PASSWORD AUTOFILL PROTECTION
===================================================== */

window.addEventListener("load", function () {

    document.getElementById("password").value = "";

    document.getElementById("confirm_password").value = "";

});


/* =====================================================
   PHONE ONLY NUMBERS
===================================================== */

document
    .getElementById("phone")
    .addEventListener("input", function () {

        this.value = this.value
            .replace(/[^0-9]/g, "")
            .slice(0, 10);

    });


/* =====================================================
   NAME ONLY LETTERS AND SPACES
===================================================== */

document
    .getElementById("name")
    .addEventListener("input", function () {

        this.value = this.value
            .replace(/[^A-Za-z ]/g, "");

    });

</script>

</body>
</html>