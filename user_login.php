<?php

session_start();

include "config/database.php";

$message = "";
$email = "";

/* =====================================================
   REMEMBERED EMAIL
===================================================== */

if (isset($_COOKIE['library_user_email'])) {

    $email = $_COOKIE['library_user_email'];
}


/* =====================================================
   LOGIN PROCESS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    /* =================================================
       VALIDATION
    ================================================= */

    if ($email === "") {

        $message = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($email) > 100) {

        $message = "Email address is too long.";

    } elseif ($password === "") {

        $message = "Please enter your password.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        /* =============================================
           DATABASE CONNECTION CHECK
        ============================================= */

        if (!isset($conn) || $conn->connect_error) {

            $message = "Unable to connect to the database.";

        } else {

            /* =========================================
               GET USER
            ========================================= */

            $stmt = $conn->prepare(
                "SELECT
                    id,
                    name,
                    email,
                    password,
                    role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            if (!$stmt) {

                $message =
                    "Something went wrong. Please try again.";

            } else {

                $stmt->bind_param("s", $email);

                $stmt->execute();

                $result = $stmt->get_result();


                /* =====================================
                   USER FOUND
                ===================================== */

                if ($result->num_rows === 1) {

                    $user = $result->fetch_assoc();


                    /* =================================
                       CHECK PASSWORD
                    ================================= */

                    if (password_verify(
                        $password,
                        $user['password']
                    )) {


                        /* =================================
                           CHECK USER ROLE
                        ================================= */

                        if ($user['role'] === 'user') {


                            /* =============================
                               REGENERATE SESSION
                            ============================= */

                            session_regenerate_id(true);


                            /* =============================
                               STORE SESSION
                            ============================= */

                            $_SESSION['user_id'] =
                                $user['id'];

                            $_SESSION['user_name'] =
                                $user['name'];

                            $_SESSION['user_email'] =
                                $user['email'];

                            $_SESSION['role'] =
                                'user';


                            /* =============================
                               REMEMBER EMAIL
                            ============================= */

                            if ($remember) {

                                setcookie(
                                    "library_user_email",
                                    $email,
                                    time() + (86400 * 30),
                                    "/",
                                    "",
                                    false,
                                    true
                                );

                            } else {

                                setcookie(
                                    "library_user_email",
                                    "",
                                    time() - 3600,
                                    "/"
                                );
                            }


                            /* =============================
                               REDIRECT
                            ============================= */

                            header(
                                "Location: user/dashboard.php"
                            );

                            exit();


                        } else {

                            $message =
                                "Access denied. Please use Admin Login.";

                        }


                    } else {

                        $message =
                            "Invalid email or password.";

                    }


                } else {

                    $message =
                        "Invalid email or password.";
                }


                $stmt->close();
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
        User Login | Library Management System
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
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


        /* =========================================
           LOGIN CONTAINER
        ========================================= */

        .login-container {

            width: 100%;

            max-width: 850px;

            min-height: 520px;

            background: #ffffff;

            border-radius: 20px;

            overflow: hidden;

            display: grid;

            grid-template-columns: 45% 55%;

            box-shadow:
                0 20px 55px
                rgba(15, 23, 42, 0.13);
        }


        /* =========================================
           LEFT PANEL
        ========================================= */

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


        /* =========================================
           BRAND
        ========================================= */

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


        /* =========================================
           LEFT CONTENT
        ========================================= */

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


        /* =========================================
           RIGHT PANEL
        ========================================= */

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


        /* =========================================
           LOGIN HEADER
        ========================================= */

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


        /* =========================================
           ALERT
        ========================================= */

        .alert-message {

            display: flex;

            gap: 8px;

            align-items: center;

            background: #fff2f2;

            border:
                1px solid #ffd4d4;

            color: #c62828;

            padding: 9px 10px;

            border-radius: 8px;

            font-size: 10px;

            margin-bottom: 15px;
        }


        /* =========================================
           FORM
        ========================================= */

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

            transform:
                translateY(-50%);

            color: #98a2b3;

            font-size: 14px;

            pointer-events: none;

            z-index: 2;
        }


        .form-input {

            width: 100%;

            height: 42px;

            border:
                1px solid #dfe4ec;

            border-radius: 9px;

            background: white;

            padding:
                0 42px 0 37px;

            outline: none;

            font-family: inherit;

            font-size: 11px;

            color: #172033;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-input:focus {

            border-color:
                #3b6ff5;

            box-shadow:
                0 0 0 3px
                rgba(59,111,245,0.10);
        }


        .form-input::placeholder {

            color: #a5adba;
        }


        /* =========================================
           PASSWORD TOGGLE
        ========================================= */

        .password-toggle {

            position: absolute;

            right: 5px;

            top: 50%;

            transform:
                translateY(-50%);

            width: 31px;

            height: 31px;

            border: none;

            background: transparent;

            color: #8b95a5;

            border-radius: 6px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;
        }


        .password-toggle:hover {

            background: #f1f4f8;

            color: #315fd3;
        }


        /* =========================================
           LOGIN OPTIONS
        ========================================= */

        .login-options {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin: 2px 0 17px;
        }


        .remember-me {

            display: flex;

            align-items: center;

            gap: 6px;

            color: #667085;

            font-size: 10px;

            cursor: pointer;
        }


        .remember-me input {

            width: 13px;

            height: 13px;

            cursor: pointer;

            accent-color: #315fd8;
        }


        .forgot-password {

            color: #315fd8;

            font-size: 10px;

            font-weight: 700;

            text-decoration: none;
        }


        .forgot-password:hover {

            text-decoration: underline;
        }


        /* =========================================
           LOGIN BUTTON
        ========================================= */

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

            transition: .2s;
        }


        .login-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 7px 18px
                rgba(49,95,216,0.22);
        }


        /* =========================================
           LINKS
        ========================================= */

        .register-link {

            display: block;

            text-align: center;

            margin-top: 18px;

            color: #315fd8;

            font-size: 10px;

            font-weight: 700;

            text-decoration: none;
        }


        .register-link:hover {

            text-decoration: underline;
        }


        .admin-login {

            display: block;

            text-align: center;

            margin-top: 12px;

            color: #6b7280;

            font-size: 10px;

            text-decoration: none;
        }


        .admin-login:hover {

            color: #315fd8;
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


        .home-link:hover {

            color: #315fd8;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 850px) {

            .login-container {

                max-width: 500px;

                grid-template-columns: 1fr;
            }


            .left-panel {

                display: none;
            }


            .right-panel {

                min-height: 560px;

                padding:
                    40px 45px;
            }

        }


        @media (max-width: 576px) {

            body {

                padding: 10px;
            }


            .login-container {

                border-radius: 16px;

                min-height: auto;
            }


            .right-panel {

                padding:
                    35px 22px;

                min-height: 520px;
            }


            .login-header h2 {

                font-size: 23px;
            }


            .login-options {

                gap: 10px;
            }

        }


    </style>

</head>


<body>


<div class="login-container">


    <!-- =========================================
         LEFT PANEL
    ========================================== -->

    <div class="left-panel">


        <div class="circle-one"></div>

        <div class="circle-two"></div>


        <!-- BRAND -->

        <div class="brand">


            <div class="brand-icon">

                <i class="bi bi-book-half"></i>

            </div>


            <div class="brand-text">

                <h3>
                    Library
                </h3>

                <span>
                    Management System
                </span>

            </div>


        </div>


        <!-- CONTENT -->

        <div class="left-content">


            <div class="small-title">

                User Portal

            </div>


            <h1>

                Discover knowledge
                with ease.

            </h1>


            <p>

                Browse books, search your
                favorite titles, request books,
                manage issued books and view
                your borrowing history.

            </p>


        </div>


        <!-- FOOTER -->

        <div class="left-footer">

            © <?php echo date("Y"); ?>

            Library Management System

        </div>


    </div>


    <!-- =========================================
         RIGHT PANEL
    ========================================== -->

    <div class="right-panel">


        <div class="login-content">


            <!-- HEADER -->

            <div class="login-header">

                <h2>
                    User Login
                </h2>

                <p>
                    Sign in to access your Library account.
                </p>

            </div>


            <!-- =====================================
                 ERROR MESSAGE
            ====================================== -->

            <?php if (!empty($message)) { ?>

                <div class="alert-message">

                    <i
                        class="bi bi-exclamation-circle-fill">
                    </i>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $message
                        );

                        ?>

                    </span>

                </div>

            <?php } ?>


            <!-- =====================================
                 LOGIN FORM
            ====================================== -->

            <form
                method="POST"
                action="user_login.php"
                autocomplete="off"
            >


                <!-- EMAIL -->

                <div class="form-group">


                    <label
                        class="form-label"
                        for="email"
                    >

                        Email Address

                    </label>


                    <div class="input-box">


                        <i
                            class="bi bi-envelope">
                        </i>


                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input"
                            placeholder="Enter your email"
                            maxlength="100"
                            autocomplete="off"
                            value="<?php
                                echo htmlspecialchars($email);
                            ?>"
                            required
                        >


                    </div>


                </div>


                <!-- PASSWORD -->

                <div class="form-group">


                    <label
                        class="form-label"
                        for="password"
                    >

                        Password

                    </label>


                    <div class="input-box">


                        <i
                            class="bi bi-lock">
                        </i>


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


                        <!-- SHOW / HIDE -->

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                            title="Show password"
                        >

                            <i
                                class="bi bi-eye"
                                id="passwordIcon">
                            </i>

                        </button>


                    </div>


                </div>


                <!-- LOGIN OPTIONS -->

                <div class="login-options">


                    <label
                        class="remember-me"
                    >

                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            <?php

                            if (
                                isset(
                                    $_COOKIE[
                                        'library_user_email'
                                    ]
                                )
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        Remember Me

                    </label>


                    <a
                        href="forgot_password.php"
                        class="forgot-password"
                    >

                        Forgot Password?

                    </a>


                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    <i
                        class="bi bi-box-arrow-in-right">
                    </i>

                    Sign In

                </button>


            </form>


            <!-- REGISTER -->

            <a
                href="register.php"
                class="register-link"
            >

                Don't have an account?
                Create an account

            </a>


            <!-- ADMIN LOGIN -->

            <a
                href="login.php"
                class="admin-login"
            >

                Admin Login

            </a>


            <!-- HOME -->

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


<!-- =========================================
     PASSWORD SHOW / HIDE
========================================== -->

<script>

const passwordInput =
    document.getElementById("password");

const togglePassword =
    document.getElementById("togglePassword");

const passwordIcon =
    document.getElementById("passwordIcon");


togglePassword.addEventListener(
    "click",
    function () {

        if (
            passwordInput.type === "password"
        ) {

            passwordInput.type = "text";

            passwordIcon.classList.remove(
                "bi-eye"
            );

            passwordIcon.classList.add(
                "bi-eye-slash"
            );

            togglePassword.setAttribute(
                "aria-label",
                "Hide password"
            );

            togglePassword.setAttribute(
                "title",
                "Hide password"
            );

        } else {

            passwordInput.type = "password";

            passwordIcon.classList.remove(
                "bi-eye-slash"
            );

            passwordIcon.classList.add(
                "bi-eye"
            );

            togglePassword.setAttribute(
                "aria-label",
                "Show password"
            );

            togglePassword.setAttribute(
                "title",
                "Show password"
            );

        }

    }
);

</script>


</body>

</html>