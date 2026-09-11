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

                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {

                    // IMPORTANT:
                    // Only normal users can login here

                    if ($user['role'] === 'user') {

                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = 'user';

                        header("Location: user/dashboard.php");
                        exit();

                    } else {

                        $message = "Access denied. Please use Admin Login.";

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        User Login | Library Management System
    </title>

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

            font-family:
                'Inter',
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef3ff,
                    #f8f9fd
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            color: #1e293b;
        }

        .login-container {

            width: 100%;

            max-width: 850px;

            min-height: 500px;

            background: #ffffff;

            border-radius: 20px;

            overflow: hidden;

            display: grid;

            grid-template-columns: 45% 55%;

            box-shadow:
                0 20px 55px
                rgba(15, 23, 42, 0.13);
        }

        .left-panel {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #0d6efd,
                    #3155d9 48%,
                    #593bb7
                );

            color: white;

            padding: 35px;

            display: flex;

            flex-direction: column;

            justify-content: space-between;
        }

        .circle-one {

            position: absolute;

            width: 250px;
            height: 250px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.07);

            top: -110px;

            right: -90px;
        }

        .circle-two {

            position: absolute;

            width: 190px;
            height: 190px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.06);

            bottom: -80px;

            left: -80px;
        }

        .brand {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            gap: 10px;
        }

        .brand-icon {

            width: 40px;

            height: 40px;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.16);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }

        .brand-text h3 {

            font-size: 16px;

            font-weight: 800;
        }

        .brand-text span {

            font-size: 10px;

            color:
                rgba(255,255,255,0.72);
        }

        .left-content {

            position: relative;

            z-index: 2;

            max-width: 350px;
        }

        .small-title {

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1.2px;

            color:
                rgba(255,255,255,0.70);

            margin-bottom: 10px;
        }

        .left-content h1 {

            font-size: 30px;

            line-height: 1.2;

            font-weight: 800;

            margin-bottom: 12px;
        }

        .left-content p {

            font-size: 12px;

            line-height: 1.7;

            color:
                rgba(255,255,255,0.78);
        }

        .left-footer {

            position: relative;

            z-index: 2;

            font-size: 9px;

            color:
                rgba(255,255,255,0.55);
        }

        .right-panel {

            padding: 35px 45px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: white;
        }

        .login-content {

            width: 100%;

            max-width: 340px;
        }

        .login-header {

            margin-bottom: 22px;
        }

        .login-header h2 {

            font-size: 25px;

            font-weight: 800;

            color: #172033;

            margin-bottom: 5px;
        }

        .login-header p {

            color: #7b8495;

            font-size: 11px;

            line-height: 1.5;
        }

        .alert-message {

            display: flex;

            gap: 8px;

            align-items: center;

            background: #fff2f2;

            border: 1px solid #ffd4d4;

            color: #c62828;

            padding: 9px 10px;

            border-radius: 8px;

            font-size: 10px;

            margin-bottom: 15px;
        }

        .form-group {

            margin-bottom: 14px;
        }

        .form-label {

            display: block;

            font-size: 10px;

            font-weight: 700;

            color: #344054;

            margin-bottom: 5px;
        }

        .input-box {

            position: relative;
        }

        .input-box > i {

            position: absolute;

            left: 12px;

            top: 50%;

            transform: translateY(-50%);

            color: #98a2b3;

            font-size: 14px;
        }

        .form-input {

            width: 100%;

            height: 42px;

            border: 1px solid #dfe4ec;

            border-radius: 9px;

            background: white;

            padding:
                0 40px 0 37px;

            outline: none;

            font-family: inherit;

            font-size: 11px;

            color: #172033;
        }

        .form-input:focus {

            border-color: #3b6ff5;

            box-shadow:
                0 0 0 3px
                rgba(59,111,245,0.10);
        }

        .login-button {

            width: 100%;

            height: 42px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #5144c6
                );

            color: white;

            font-family: inherit;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;
        }

        .register-link {

            display: block;

            text-align: center;

            margin-top: 18px;

            color: #315fd8;

            font-size: 10px;

            font-weight: 700;

            text-decoration: none;
        }

        .admin-login {

            display: block;

            text-align: center;

            margin-top: 12px;

            color: #6b7280;

            font-size: 10px;

            text-decoration: none;
        }

        .home-link {

            margin-top: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            color: #8a93a2;

            text-decoration: none;

            font-size: 10px;
        }

        @media (max-width: 850px) {

            .login-container {

                max-width: 500px;

                grid-template-columns: 1fr;
            }

            .left-panel {

                display: none;
            }

            .right-panel {

                min-height: 500px;

                padding: 40px 45px;
            }
        }

        @media (max-width: 576px) {

            body {

                padding: 10px;
            }

            .right-panel {

                padding: 35px 22px;
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

                <span>
                    Management System
                </span>

            </div>

        </div>


        <div class="left-content">

            <div class="small-title">
                User Portal
            </div>

            <h1>
                Discover knowledge with ease.
            </h1>

            <p>
                Browse books, search your favorite titles,
                request books, manage issued books and
                view your borrowing history.
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

                <h2>
                    User Login
                </h2>

                <p>
                    Sign in to access your Library account.
                </p>

            </div>


            <?php if (!empty($message)) { ?>

                <div class="alert-message">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <span>
                        <?php
                        echo htmlspecialchars($message);
                        ?>
                    </span>

                </div>

            <?php } ?>


            <form
                method="POST"
                action="user_login.php"
                autocomplete="off"
            >

                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >
                        Email Address
                    </label>

                    <div class="input-box">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input"
                            placeholder="Enter your email"
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
                            placeholder="Enter your password"
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

                    <i class="bi bi-box-arrow-in-right"></i>

                    Sign In

                </button>

            </form>


            <a
                href="register.php"
                class="register-link"
            >
                Don't have an account? Create an account
            </a>


            <a
                href="login.php"
                class="admin-login"
            >
                Admin Login
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