<?php

session_start();

include "config/database.php";

$message = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($email === "") {

        $message = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif ($password === "") {

        $message = "Please enter your password.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $message = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $admin = $result->fetch_assoc();

                // Check password
                if (password_verify($password, $admin['password'])) {

                    // IMPORTANT: Only Admin can login here
                    if ($admin['role'] === 'admin') {

                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $admin['id'];
                        $_SESSION['user_name'] = $admin['name'];
                        $_SESSION['user_email'] = $admin['email'];
                        $_SESSION['role'] = 'admin';

                        header("Location: admin/dashboard.php");
                        exit();

                    } else {

                        $message = "Access denied. This login is for Admin only.";
                    }

                } else {

                    $message = "Invalid email or password.";
                }

            } else {

                $message = "Invalid email or password.";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Library Management System</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

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
    font-family: 'Inter', Arial, sans-serif;

    background:
        radial-gradient(circle at top left, #233b72 0%, transparent 35%),
        radial-gradient(circle at bottom right, #162a58 0%, transparent 35%),
        #0b1220;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 25px;

    color: #172033;
}

/* MAIN CARD */

.login-container {
    width: 100%;
    max-width: 460px;

    background: rgba(255,255,255,0.97);

    border: 1px solid rgba(255,255,255,0.15);

    border-radius: 24px;

    padding: 42px 42px 30px;

    box-shadow:
        0 30px 80px rgba(0,0,0,0.35);

    position: relative;
    overflow: hidden;
}

/* TOP DECORATION */

.login-container::before {
    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    border-radius: 50%;

    background:
        rgba(13,110,253,0.08);

    top: -110px;
    right: -90px;
}

.login-container::after {
    content: "";

    position: absolute;

    width: 150px;
    height: 150px;

    border-radius: 50%;

    background:
        rgba(81,68,198,0.07);

    bottom: -80px;
    left: -70px;
}

/* BRAND */

.left-panel {
    display: block;
    padding: 0;

    background: transparent;
    color: #172033;
}

.circle-one,
.circle-two {
    display: none;
}

.brand {
    position: relative;
    z-index: 2;

    display: flex;
    flex-direction: column;

    align-items: center;

    justify-content: center;

    gap: 12px;

    margin-bottom: 28px;
}

.brand-icon {
    width: 68px;
    height: 68px;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #5144c6
        );

    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 31px;

    box-shadow:
        0 12px 30px rgba(13,110,253,0.28);
}

.brand-text {
    text-align: center;
}

.brand-text h3 {
    font-size: 20px;
    font-weight: 800;

    color: #172033;
}

.brand-text span {
    display: block;

    font-size: 10px;

    color: #7c8798;

    margin-top: 3px;

    letter-spacing: 0.8px;
}

/* LEFT CONTENT */

.left-content {
    display: none;
}

.left-footer {
    display: none;
}

/* RIGHT PANEL */

.right-panel {
    padding: 0;

    display: block;

    background: transparent;
}

.login-content {
    width: 100%;
    max-width: none;

    position: relative;
    z-index: 2;
}

/* HEADER */

.login-header {
    text-align: center;

    margin-bottom: 25px;
}

.login-header h2 {
    font-size: 27px;

    font-weight: 800;

    color: #172033;

    margin-bottom: 7px;
}

.login-header p {
    font-size: 11px;

    line-height: 1.6;

    color: #7b8495;
}

/* ADMIN BADGE */

.login-header::before {
    content: "ADMIN PORTAL";

    display: inline-flex;

    align-items: center;
    justify-content: center;

    padding: 6px 12px;

    border-radius: 30px;

    background: #edf4ff;

    color: #315fd8;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: 1px;

    margin-bottom: 12px;
}

/* ALERT */

.alert-message {
    display: flex;
    align-items: center;
    gap: 8px;

    background: #fff3f3;

    border:
        1px solid #ffd7d7;

    color: #c62828;

    padding: 11px 12px;

    border-radius: 10px;

    font-size: 10px;

    margin-bottom: 17px;
}

/* FORM */

.form-group {
    margin-bottom: 17px;
}

.form-label {
    display: block;

    font-size: 10px;

    font-weight: 700;

    color: #344054;

    margin-bottom: 7px;
}

.input-box {
    position: relative;
}

.input-box > i {
    position: absolute;

    left: 14px;

    top: 50%;

    transform: translateY(-50%);

    color: #8792a5;

    font-size: 15px;

    z-index: 2;
}

.form-input {
    width: 100%;

    height: 48px;

    border:
        1px solid #dfe5ee;

    border-radius: 11px;

    background: #f9fbfd;

    padding: 0 15px 0 42px;

    outline: none;

    font-family: inherit;

    font-size: 11px;

    color: #172033;

    transition:
        all 0.25s ease;
}

.form-input::placeholder {
    color: #a2aab7;
}

.form-input:focus {
    background: #ffffff;

    border-color: #3b6ff5;

    box-shadow:
        0 0 0 4px rgba(59,111,245,0.09);
}

/* LOGIN BUTTON */

.login-button {
    width: 100%;

    height: 48px;

    border: none;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #5144c6
        );

    color: white;

    font-family: inherit;

    font-size: 11px;

    font-weight: 800;

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.login-button:hover {
    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(49,95,216,0.28);
}

/* USER LOGIN */

.user-login {
    display: block;

    text-align: center;

    margin-top: 20px;

    color: #315fd8;

    font-size: 10px;

    font-weight: 700;

    text-decoration: none;

    transition: 0.2s;
}

.user-login:hover {
    color: #173f9e;
}

/* HOME */

.home-link {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    margin-top: 13px;

    color: #8a93a2;

    text-decoration: none;

    font-size: 10px;

    transition: 0.2s;
}

.home-link:hover {
    color: #315fd8;
}

/* MOBILE */

@media (max-width: 576px) {

    body {
        padding: 15px;
    }

    .login-container {
        max-width: 430px;

        padding:
            35px 25px 27px;

        border-radius: 20px;
    }

    .brand-icon {
        width: 62px;
        height: 62px;

        font-size: 28px;
    }

    .brand-text h3 {
        font-size: 18px;
    }

    .login-header h2 {
        font-size: 24px;
    }
}

@media (max-width: 380px) {

    .login-container {
        padding:
            30px 18px 23px;
    }

    .brand-icon {
        width: 58px;
        height: 58px;

        font-size: 26px;
    }

    .login-header h2 {
        font-size: 22px;
    }
}
    </style>

</head>

<body>

<div class="login-container">

    <div class="left-panel">

        <div class="circle-one"></div>
        <div class="circle-two"></div>

        <div class="brand">

            <div class="brand-icon">
                <i class="bi bi-book-half"></i>
            </div>

            <div class="brand-text">

                <h3>Library</h3>

                <span>Management System</span>

            </div>

        </div>

        <div class="left-content">

            <div class="small-title">
                Admin Portal
            </div>

            <h1>
                Manage your library smarter.
            </h1>

            <p>
                Administer books, categories, users,
                issue requests, returns and reports
                from one centralized platform.
            </p>

        </div>

        <div class="left-footer">

            © <?php echo date("Y"); ?>
            Library Management System

        </div>

    </div>


    <div class="right-panel">

        <div class="login-content">

            <div class="login-header">

                <h2>Admin Login</h2>

                <p>
                    Sign in to access the Admin Dashboard.
                </p>

            </div>


            <?php if (!empty($message)) { ?>

                <div class="alert-message">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php } ?>


            <form
                method="POST"
                action="login.php"
                autocomplete="off"
            >

                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >
                        Admin Email
                    </label>

                    <div class="input-box">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input"
                            placeholder="Enter admin email"
                            maxlength="100"
                            autocomplete="off"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Password
                    </label>

                    <div class="input-box">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input"
                            placeholder="Enter password"
                            minlength="6"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    <i class="bi bi-shield-lock"></i>

                    Admin Sign In

                </button>

            </form>


            <a
                href="user_login.php"
                class="user-login"
            >
                User Login →
            </a>


            <a
                href="index.php"
                class="home-link"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Home

            </a>

        </div>

    </div>

</div>

</body>

</html>