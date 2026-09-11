<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

/* =========================
   FETCH CURRENT USER
========================= */

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, role, created_at
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: " . BASE_URL . "/logout.php");
    exit();
}


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === "") {

        $error = "Please enter your name.";

    } elseif ($email === "") {

        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($phone !== "" && !preg_match('/^[0-9]{10}$/', $phone)) {

        $error = "Phone number must contain exactly 10 digits.";

    } else {

        /* Check duplicate email */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?"
        );

        $check->bind_param("si", $email, $user_id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $error = "This email address is already registered.";

        } else {

            $update = $conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sssi",
                $name,
                $email,
                $phone,
                $user_id
            );

            if ($update->execute()) {

                /* Update session */

                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

                $success = "Profile updated successfully.";

                /* Fetch latest user data */

                $stmt = $conn->prepare(
                    "SELECT id, name, email, phone, role, created_at
                     FROM users
                     WHERE id = ?"
                );

                $stmt->bind_param("i", $user_id);
                $stmt->execute();

                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

            } else {

                $error = "Unable to update profile. Please try again.";

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Profile - Library Management System</title>


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


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <style>

        /* =====================================================
           PROFILE PAGE
        ===================================================== */

        .profile-page {
            padding: 30px;
            min-height: calc(100vh - 78px);
            background: #f7f9fc;
        }


        .profile-container {
            max-width: 1100px;
            margin: 0 auto;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .profile-page-header {

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 24px;

        }


        .profile-heading {

            display: flex;
            align-items: center;
            gap: 15px;

        }


        .profile-heading-icon {

            width: 52px;
            height: 52px;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color: white;

            font-size: 23px;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.20);

        }


        .profile-heading-text h3 {

            margin: 0;

            font-size: 25px;

            font-weight: 800;

            color: #172033;

        }


        .profile-heading-text p {

            margin: 4px 0 0;

            color: #718096;

            font-size: 14px;

        }


        /* =====================================================
           MAIN PROFILE CARD
        ===================================================== */

        .profile-main-card {

            background: #ffffff;

            border: 1px solid #e8edf4;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 8px 30px
                rgba(15, 23, 42, 0.06);

        }


        /* =====================================================
           PROFILE TOP
        ===================================================== */

        .profile-top {

            position: relative;

            padding: 34px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb 0%,
                    #1e40af 100%
                );

            color: white;

        }


        .profile-top::after {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            right: -50px;
            top: -70px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.08);

        }


        .profile-top-content {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .profile-avatar-large {

            width: 88px;
            height: 88px;

            min-width: 88px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                rgba(255,255,255,0.18);

            border:
                3px solid
                rgba(255,255,255,0.65);

            font-size: 38px;

        }


        .profile-top-info h2 {

            margin: 0 0 6px;

            font-size: 27px;

            font-weight: 800;

        }


        .profile-top-info p {

            margin: 0 0 10px;

            color: #dbeafe;

            font-size: 14px;

        }


        .member-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 12px;

            border-radius: 30px;

            background:
                rgba(255,255,255,0.15);

            border:
                1px solid
                rgba(255,255,255,0.25);

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           ALERTS
        ===================================================== */

        .profile-alert {

            border-radius: 11px;

            border: none;

            padding: 13px 16px;

            font-size: 14px;

        }


        /* =====================================================
           PROFILE BODY
        ===================================================== */

        .profile-body {

            padding: 32px;

        }


        .profile-section {

            margin-bottom: 30px;

        }


        .section-heading {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 22px;

        }


        .section-heading-icon {

            width: 38px;
            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 17px;

        }


        .section-heading h5 {

            margin: 0;

            color: #172033;

            font-size: 17px;

            font-weight: 800;

        }


        .section-heading span {

            display: block;

            margin-top: 2px;

            color: #94a3b8;

            font-size: 12px;

        }


        /* =====================================================
           FORM
        ===================================================== */

        .profile-form-label {

            display: block;

            margin-bottom: 8px;

            color: #374151;

            font-size: 13px;

            font-weight: 700;

        }


        .profile-form-label i {

            color: #2563eb;

        }


        .profile-input-wrapper {

            position: relative;

        }


        .profile-input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 16px;

            pointer-events: none;

        }


        .profile-input {

            width: 100%;

            min-height: 48px;

            padding:
                10px
                14px
                10px
                43px;

            border:
                1px solid #dbe3ed;

            border-radius: 11px;

            background: #ffffff;

            color: #1e293b;

            font-size: 14px;

            outline: none;

            transition: all 0.2s ease;

        }


        .profile-input::placeholder {

            color: #a0aec0;

        }


        .profile-input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,0.09);

        }


        /* =====================================================
           READONLY ROLE
        ===================================================== */

        .profile-readonly {

            min-height: 48px;

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 10px 14px;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;

            background: #f8fafc;

            color: #475569;

            font-size: 14px;

            font-weight: 600;

        }


        .role-icon {

            width: 29px;
            height: 29px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #e0edff;

            color: #2563eb;

        }


        .role-badge {

            margin-left: auto;

            padding: 5px 10px;

            border-radius: 20px;

            background: #dcfce7;

            color: #15803d;

            font-size: 11px;

            font-weight: 800;

        }


        /* =====================================================
           FORM FOOTER
        ===================================================== */

        .profile-form-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 8px;

            padding-top: 25px;

            border-top:
                1px solid #edf1f6;

        }


        .profile-security-note {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #94a3b8;

            font-size: 12px;

        }


        .profile-security-note i {

            color: #22c55e;

            font-size: 16px;

        }


        .update-profile-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 46px;

            padding: 0 23px;

            border: none;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            transition: all 0.2s ease;

            box-shadow:
                0 7px 18px
                rgba(37,99,235,0.20);

        }


        .update-profile-btn:hover {

            color: white;

            transform: translateY(-2px);

            box-shadow:
                0 10px 24px
                rgba(37,99,235,0.28);

        }


        /* =====================================================
           ACCOUNT INFORMATION
        ===================================================== */

        .account-card {

            margin-top: 30px;

            padding: 24px;

            border-radius: 15px;

            background: #f8fafc;

            border:
                1px solid #e4eaf1;

        }


        .account-card-header {

            display: flex;

            align-items: center;

            gap: 11px;

            margin-bottom: 18px;

        }


        .account-card-icon {

            width: 37px;
            height: 37px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #e0edff;

            color: #2563eb;

        }


        .account-card-header h5 {

            margin: 0;

            font-size: 16px;

            font-weight: 800;

            color: #1e293b;

        }


        .account-card-header p {

            margin: 2px 0 0;

            font-size: 12px;

            color: #94a3b8;

        }


        .account-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

        }


        .account-item {

            background: white;

            border:
                1px solid #e5eaf0;

            border-radius: 11px;

            padding: 15px;

        }


        .account-item-label {

            display: block;

            margin-bottom: 7px;

            color: #94a3b8;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;

        }


        .account-item-value {

            color: #1e293b;

            font-size: 14px;

            font-weight: 750;

        }


        .account-item-value i {

            margin-right: 5px;

            color: #2563eb;

        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .user-navbar {

            height: 78px;

            background: #ffffff;

            padding: 0 30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom:
                1px solid #edf0f5;

            position: sticky;

            top: 0;

            z-index: 900;

            box-shadow:
                0 3px 15px
                rgba(15,23,42,0.04);

        }


        .user-nav-left {

            display: flex;

            align-items: center;

            gap: 13px;

        }


        .sidebar-toggle {

            width: 40px;

            height: 40px;

            border: none;

            border-radius: 10px;

            background: #f1f5f9;

            color: #334155;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            cursor: pointer;

        }


        .sidebar-toggle:hover {

            background: #e8eef7;

            color: #2563eb;

        }


        .user-welcome-icon {

            width: 43px;

            height: 43px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 19px;

        }


        .user-welcome span {

            display: block;

            color: #94a3b8;

            font-size: 11px;

            font-weight: 600;

        }


        .user-welcome h5 {

            margin: 1px 0 0;

            color: #172033;

            font-size: 15px;

            font-weight: 800;

        }


        .user-nav-center {

            position: absolute;

            left: 50%;

            transform: translateX(-50%);

        }


        .library-status {

            display: flex;

            align-items: center;

            gap: 8px;

            padding: 8px 14px;

            border-radius: 30px;

            background: #f0fdf4;

            color: #15803d;

            font-size: 12px;

            font-weight: 700;

        }


        .status-circle {

            width: 8px;

            height: 8px;

            border-radius: 50%;

            background: #22c55e;

            box-shadow:
                0 0 0 4px
                rgba(34,197,94,0.12);

        }


        .user-nav-right {

            display: flex;

            align-items: center;

            gap: 9px;

        }


        .nav-action {

            width: 39px;

            height: 39px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            color: #64748b;

            background: #f8fafc;

            border:
                1px solid #edf1f5;

            transition: all 0.2s ease;

        }


        .nav-action:hover {

            color: #2563eb;

            background: #eff6ff;

        }


        .nav-separator {

            width: 1px;

            height: 32px;

            background: #e5eaf0;

            margin: 0 5px;

        }


        .user-profile-pill {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 5px 10px 5px 5px;

            border-radius: 30px;

            background: #f8fafc;

            border:
                1px solid #edf1f5;

        }


        .user-avatar {

            width: 34px;

            height: 34px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            font-size: 13px;

            font-weight: 800;

        }


        .user-profile-name strong {

            display: block;

            color: #1e293b;

            font-size: 12px;

        }


        .user-profile-name small {

            display: block;

            color: #94a3b8;

            font-size: 10px;

        }


        .profile-arrow {

            color: #94a3b8;

            font-size: 12px;

        }


        .user-logout {

            width: 39px;

            height: 39px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            background: #fff1f2;

            color: #e11d48;

            border:
                1px solid #ffe4e6;

        }


        .user-logout:hover {

            background: #ffe4e6;

            color: #be123c;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .user-nav-center {

                display: none;

            }

            .account-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 768px) {

            .profile-page {

                padding: 22px 16px;

            }


            .profile-body {

                padding: 24px;

            }


            .profile-top {

                padding: 27px 24px;

            }


            .profile-page-header {

                margin-bottom: 18px;

            }


            .profile-heading-text h3 {

                font-size: 21px;

            }

        }


        @media (max-width: 650px) {

            .user-navbar {

                padding: 0 15px;

            }


            .user-profile-name,
            .profile-arrow {

                display: none;

            }


            .user-profile-pill {

                padding: 5px;

            }


            .profile-top-content {

                flex-direction: column;

                text-align: center;

            }


            .profile-account-role {

                justify-content: center;

            }


            .profile-form-footer {

                flex-direction: column;

                align-items: stretch;

            }


            .profile-security-note {

                justify-content: center;

            }


            .update-profile-btn {

                width: 100%;

            }

        }


        @media (max-width: 576px) {

            .profile-page {

                padding: 16px 12px;

            }


            .profile-heading-icon {

                width: 44px;

                height: 44px;

                font-size: 19px;

            }


            .profile-heading-text h3 {

                font-size: 19px;

            }


            .profile-heading-text p {

                font-size: 12px;

            }


            .profile-top {

                padding: 24px 18px;

            }


            .profile-avatar-large {

                width: 76px;

                height: 76px;

                min-width: 76px;

                font-size: 32px;

            }


            .profile-top-info h2 {

                font-size: 22px;

            }


            .profile-body {

                padding: 20px 16px;

            }


            .account-card {

                padding: 18px;

            }


            .account-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 450px) {

            .nav-action:nth-child(2) {

                display: none;

            }


            .user-welcome-icon {

                display: none;

            }


            .user-welcome h5 {

                font-size: 14px;

            }

        }

    </style>

</head>


<body>


<?php require_once "../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =====================================================
         UNIQUE USER NAVBAR
    ===================================================== -->

    <nav class="user-navbar">


        <div class="user-nav-left">


            


            <div class="user-welcome-icon">

                <i class="bi bi-person"></i>

            </div>


            <div class="user-welcome">

                <span>My account</span>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['user_name'] ?? 'User'
                    );
                    ?>

                </h5>

            </div>


        </div>



        <div class="user-nav-center">

            <div class="library-status">

                <span class="status-circle"></span>

                <span>Library is Open</span>

            </div>

        </div>



        <div class="user-nav-right">


            <a
                href="<?php echo BASE_URL; ?>/user/books/search.php"
                class="nav-action"
                title="Search Books"
            >

                <i class="bi bi-search"></i>

            </a>


            <a
                href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                class="nav-action"
                title="My Books"
            >

                <i class="bi bi-journal-bookmark"></i>

            </a>


            <div class="nav-separator"></div>


            <div class="user-profile-pill">


                <div class="user-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION['user_name'] ?? 'U',
                            0,
                            1
                        )
                    );

                    ?>

                </div>


                <div class="user-profile-name">

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? 'User'
                        );
                        ?>

                    </strong>

                    <small>Member</small>

                </div>




            </div>


            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="user-logout"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>


        </div>

    </nav>



    <!-- =====================================================
         PROFILE CONTENT
    ===================================================== -->

    <main class="profile-page">


        <div class="profile-container">


            <!-- PAGE HEADER -->

            <div class="profile-page-header">


                <div class="profile-heading">


                    <div class="profile-heading-icon">

                        <i class="bi bi-person-vcard"></i>

                    </div>


                    <div class="profile-heading-text">

                        <h3>My Profile</h3>

                        <p>Manage your personal account information</p>

                    </div>


                </div>


            </div>



            <!-- MAIN CARD -->

            <div class="profile-main-card">


                <!-- PROFILE TOP -->

                <div class="profile-top">


                    <div class="profile-top-content">


                        <div class="profile-avatar-large">

                            <i class="bi bi-person-fill"></i>

                        </div>


                        <div class="profile-top-info">


                            <h2>

                                <?php
                                echo htmlspecialchars(
                                    $user['name']
                                );
                                ?>

                            </h2>


                            <p>

                                <i class="bi bi-envelope me-1"></i>

                                <?php
                                echo htmlspecialchars(
                                    $user['email']
                                );
                                ?>

                            </p>


                            <span class="member-badge">

                                <i class="bi bi-person-check"></i>

                                Library Member

                            </span>


                        </div>


                    </div>

                </div>



                <!-- PROFILE BODY -->

                <div class="profile-body">


                    <!-- ALERTS -->

                    <?php if ($success !== ""): ?>

                        <div
                            class="alert alert-success profile-alert alert-dismissible fade show"
                            role="alert"
                        >

                            <i class="bi bi-check-circle-fill me-2"></i>

                            <?php
                            echo htmlspecialchars($success);
                            ?>


                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>



                    <?php if ($error !== ""): ?>

                        <div
                            class="alert alert-danger profile-alert alert-dismissible fade show"
                            role="alert"
                        >

                            <i class="bi bi-exclamation-circle-fill me-2"></i>

                            <?php
                            echo htmlspecialchars($error);
                            ?>


                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>



                    <!-- PERSONAL INFORMATION -->

                    <section class="profile-section">


                        <div class="section-heading">


                            <div class="section-heading-icon">

                                <i class="bi bi-person-gear"></i>

                            </div>


                            <div>

                                <h5>Personal Information</h5>

                                <span>
                                    Update your basic account details
                                </span>

                            </div>


                        </div>



                        <form
                            method="POST"
                            action=""
                        >


                            <div class="row g-4">


                                <!-- NAME -->

                                <div class="col-md-6">


                                    <label
                                        class="profile-form-label"
                                    >

                                        <i class="bi bi-person me-1"></i>

                                        Full Name

                                    </label>


                                    <div class="profile-input-wrapper">


                                        <i
                                            class="bi bi-person profile-input-icon"
                                        ></i>


                                        <input
                                            type="text"
                                            name="name"
                                            class="profile-input"
                                            value="<?php
                                            echo htmlspecialchars(
                                                $user['name']
                                            );
                                            ?>"
                                            placeholder="Enter your full name"
                                            required
                                        >

                                    </div>

                                </div>



                                <!-- EMAIL -->

                                <div class="col-md-6">


                                    <label
                                        class="profile-form-label"
                                    >

                                        <i class="bi bi-envelope me-1"></i>

                                        Email Address

                                    </label>


                                    <div class="profile-input-wrapper">


                                        <i
                                            class="bi bi-envelope profile-input-icon"
                                        ></i>


                                        <input
                                            type="email"
                                            name="email"
                                            class="profile-input"
                                            value="<?php
                                            echo htmlspecialchars(
                                                $user['email']
                                            );
                                            ?>"
                                            placeholder="Enter your email"
                                            required
                                        >

                                    </div>

                                </div>



                                <!-- PHONE -->

                                <div class="col-md-6">


                                    <label
                                        class="profile-form-label"
                                    >

                                        <i class="bi bi-telephone me-1"></i>

                                        Phone Number

                                    </label>


                                    <div class="profile-input-wrapper">


                                        <i
                                            class="bi bi-telephone profile-input-icon"
                                        ></i>


                                        <input
                                            type="text"
                                            name="phone"
                                            class="profile-input"
                                            maxlength="10"
                                            inputmode="numeric"
                                            placeholder="Enter 10-digit mobile number"
                                            value="<?php
                                            echo htmlspecialchars(
                                                $user['phone'] ?? ''
                                            );
                                            ?>"
                                        >

                                    </div>

                                </div>



                                <!-- ROLE -->

                                <div class="col-md-6">


                                    <label
                                        class="profile-form-label"
                                    >

                                        <i class="bi bi-shield-check me-1"></i>

                                        Account Role

                                    </label>


                                    <div class="profile-readonly">


                                        <div class="role-icon">

                                            <i class="bi bi-person-badge"></i>

                                        </div>


                                        <span>

                                            <?php

                                            echo ucfirst(
                                                htmlspecialchars(
                                                    $user['role']
                                                )
                                            );

                                            ?>

                                        </span>


                                        <span class="role-badge">

                                            Active

                                        </span>


                                    </div>

                                </div>


                            </div>



                            <!-- FORM FOOTER -->

                            <div class="profile-form-footer">


                                <div class="profile-security-note">

                                    <i class="bi bi-shield-check"></i>

                                    <span>
                                        Your account information is secure.
                                    </span>

                                </div>


                                <button
                                    type="submit"
                                    class="update-profile-btn"
                                >

                                    <i class="bi bi-check2-circle"></i>

                                    Update Profile

                                </button>


                            </div>


                        </form>

                    </section>



                    <!-- ACCOUNT INFORMATION -->

                    <div class="account-card">


                        <div class="account-card-header">


                            <div class="account-card-icon">

                                <i class="bi bi-info-circle"></i>

                            </div>


                            <div>

                                <h5>Account Information</h5>

                                <p>Your library membership details</p>

                            </div>


                        </div>



                        <div class="account-grid">


                            <!-- USER ID -->

                            <div class="account-item">


                                <span class="account-item-label">

                                    User ID

                                </span>


                                <div class="account-item-value">

                                    <i class="bi bi-hash"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $user['id']
                                    );
                                    ?>

                                </div>

                            </div>



                            <!-- ACCOUNT TYPE -->

                            <div class="account-item">


                                <span class="account-item-label">

                                    Account Type

                                </span>


                                <div class="account-item-value">

                                    <i class="bi bi-person-badge"></i>

                                    <?php

                                    echo ucfirst(
                                        htmlspecialchars(
                                            $user['role']
                                        )
                                    );

                                    ?>

                                </div>

                            </div>



                            <!-- MEMBER SINCE -->

                            <div class="account-item">


                                <span class="account-item-label">

                                    Member Since

                                </span>


                                <div class="account-item-value">

                                    <i class="bi bi-calendar3"></i>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $user['created_at']
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>


    </main>


</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector(".user-sidebar");

    if (sidebar) {

        sidebar.classList.toggle("show");

    }

}

</script>


</body>

</html>