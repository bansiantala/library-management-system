<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

// =========================
// CHANGE PASSWORD
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check empty fields
    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {
        $error = "Please fill in all password fields.";
    }

    // Check new password length
    elseif (strlen($new_password) < 6) {
        $error = "New password must contain at least 6 characters.";
    }

    // Check password confirmation
    elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    }

    else {

        // Get current password from database
        $stmt = $conn->prepare(
            "SELECT password
             FROM users
             WHERE id = ?"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {

            $error = "User account not found.";

        }

        // Verify current password
        elseif (!password_verify(
            $current_password,
            $user['password']
        )) {

            $error = "Current password is incorrect.";

        }

        // Prevent same password
        elseif (password_verify(
            $new_password,
            $user['password']
        )) {

            $error = "New password must be different from current password.";

        }

        else {

            // Hash new password
            $hashed_password = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            // Update password
            $update = $conn->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "si",
                $hashed_password,
                $user_id
            );

            if ($update->execute()) {

                $success = "Password changed successfully.";

            } else {

                $error = "Unable to change password. Please try again.";

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
        Change Password - Library Management System
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
        

        .user-navbar {
    height: 78px;
    background: #ffffff;

    padding: 0 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    border-bottom: 1px solid #edf0f5;

    position: sticky;
    top: 0;
    z-index: 900;

    box-shadow: 0 3px 15px rgba(15, 23, 42, 0.035);
}


/* =========================================
   LEFT
========================================= */

.user-nav-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-welcome-icon {
    width: 43px;
    height: 43px;

    border-radius: 13px;

    background: #eff6ff;
    color: #2563eb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.user-welcome span {
    display: block;

    color: #94a3b8;

    font-size: 10px;
    font-weight: 600;

    margin-bottom: 2px;
}

.user-welcome h5 {
    margin: 0;

    color: #172033;

    font-size: 15px;
    font-weight: 800;
}


/* =========================================
   CENTER STATUS
========================================= */

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

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    color: #64748b;

    font-size: 11px;
    font-weight: 600;
}

.status-circle {
    width: 8px;
    height: 8px;

    background: #22c55e;

    border-radius: 50%;

    box-shadow: 0 0 0 4px rgba(34,197,94,.10);
}


/* =========================================
   RIGHT
========================================= */

.user-nav-right {
    display: flex;
    align-items: center;

    gap: 10px;
}


/* =========================================
   ACTION BUTTONS
========================================= */

.nav-action {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    color: #64748b;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.nav-action:hover {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #2563eb;

    transform: translateY(-1px);
}


/* =========================================
   FAVORITE NAV ACTION
========================================= */

.favorite-nav-action {
    color: #e11d48;
}

.favorite-nav-action:hover {
    background: #fff1f2;

    border-color: #fecdd3;

    color: #e11d48;

    transform: translateY(-1px);
}


/* =========================================
   SEPARATOR
========================================= */

.nav-separator {
    width: 1px;
    height: 34px;

    background: #e5e7eb;

    margin: 0 5px;
}


/* =========================================
   USER PROFILE PILL
========================================= */

.user-profile-pill {
    display: flex;
    align-items: center;

    gap: 9px;

    padding: 5px 10px 5px 5px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    cursor: default;
}

.user-avatar {
    width: 35px;
    height: 35px;

    border-radius: 50%;

    background: #2563eb;
    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 13px;
    font-weight: 800;
}

.user-profile-name strong {
    display: block;

    color: #334155;

    font-size: 11px;
    font-weight: 700;

    max-width: 110px;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-name small {
    display: block;

    color: #94a3b8;

    font-size: 9px;

    margin-top: 1px;
}

.profile-arrow {
    color: #94a3b8;

    font-size: 10px;

    margin-left: 2px;
}


/* =========================================
   LOGOUT
========================================= */

.user-logout {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #fff5f5;

    border: 1px solid #fee2e2;

    color: #ef4444;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.user-logout:hover {
    background: #ef4444;

    color: #ffffff;

    border-color: #ef4444;
}



        /* =========================================================
           CHANGE PASSWORD - RESIZED PROFESSIONAL DESIGN
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f6fb;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* =========================================================
           MAIN PAGE
        ========================================================= */

        .password-page {
            min-height: calc(100vh - 68px);
            padding: 18px 24px 24px;
            background: #f3f6fb;
        }

        .password-container {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        /* =========================================================
           PAGE TITLE
        ========================================================= */

        .password-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .password-heading {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .password-heading-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color: #ffffff;
            font-size: 21px;

            box-shadow:
                0 6px 16px
                rgba(37, 99, 235, 0.20);
        }

        .password-heading-text h3 {
            margin: 0;
            color: #172033;
            font-size: 24px;
            font-weight: 800;
        }

        .password-heading-text p {
            margin: 3px 0 0;
            color: #718096;
            font-size: 14px;
        }

        /* =========================================================
           MAIN CARD
        ========================================================= */

        .password-main-card {
            background: #ffffff;

            border:
                1px solid
                #e5ebf3;

            border-radius: 15px;
            overflow: hidden;

            box-shadow:
                0 6px 22px
                rgba(15, 23, 42, 0.06);
        }

        /* =========================================================
           BLUE CARD HEADER
        ========================================================= */

        .password-card-header {
            position: relative;

            padding: 22px 26px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1e40af
                );

            color: #ffffff;
            overflow: hidden;
        }

        .password-card-header::before {
            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            right: -30px;
            bottom: -105px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.08);
        }

        .password-card-header::after {
            content: "";

            position: absolute;

            width: 120px;
            height: 120px;

            right: 90px;
            bottom: -85px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.05);
        }

        .password-card-header-content {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            gap: 16px;
        }

        .password-header-icon {
            width: 58px;
            height: 58px;
            min-width: 58px;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                rgba(255,255,255,0.16);

            border:
                1px solid
                rgba(255,255,255,0.32);

            font-size: 27px;
        }

        .password-header-text h2 {
            margin: 0 0 3px;
            font-size: 22px;
            font-weight: 800;
        }

        .password-header-text p {
            margin: 0;
            color: #dbeafe;
            font-size: 14px;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            margin-top: 7px;

            padding: 4px 10px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.15);

            border:
                1px solid
                rgba(255,255,255,0.20);

            color: #ffffff;

            font-size: 11px;
            font-weight: 700;
        }

        /* =========================================================
           CARD BODY
        ========================================================= */

        .password-card-body {
            padding: 22px 26px 24px;
        }

        /* =========================================================
           ALERTS
        ========================================================= */

        .password-alert {
            margin-bottom: 12px;

            padding: 11px 14px;

            border: none;
            border-radius: 9px;

            font-size: 14px;
        }

        /* =========================================================
           SECTION TITLE
        ========================================================= */

        .password-section-heading {
            display: flex;
            align-items: center;
            gap: 11px;

            margin-top: 14px;
            margin-bottom: 17px;
        }

        .password-section-icon {
            width: 38px;
            height: 38px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eaf2ff;
            color: #2563eb;

            font-size: 17px;
        }

        .password-section-heading h5 {
            margin: 0;

            color: #172033;

            font-size: 18px;
            font-weight: 800;
        }

        .password-section-heading span {
            display: block;

            margin-top: 2px;

            color: #94a3b8;

            font-size: 12px;
        }

        /* =========================================================
           FORM
        ========================================================= */

        .password-form-label {
            display: block;

            margin-bottom: 7px;

            color: #374151;

            font-size: 14px;
            font-weight: 700;
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-input-icon {
            position: absolute;

            left: 13px;
            top: 50%;

            transform: translateY(-50%);

            color: #64748b;
            font-size: 15px;

            pointer-events: none;
            z-index: 2;
        }

        .password-input {
            width: 100%;
            height: 44px;

            padding:
                9px
                43px
                9px
                39px;

            border:
                1px solid
                #d9e1ec;

            border-radius: 9px;

            background: #ffffff;

            color: #1e293b;

            font-size: 14px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .password-input::placeholder {
            color: #a0aec0;
            font-size: 13px;
        }

        .password-input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }

        .password-toggle {
            position: absolute;

            right: 7px;
            top: 50%;

            transform: translateY(-50%);

            width: 32px;
            height: 32px;

            border: none;
            border-radius: 7px;

            background: transparent;
            color: #64748b;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;

            font-size: 15px;
        }

        .password-toggle:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .password-help {
            display: flex;
            align-items: center;
            gap: 6px;

            margin-top: 6px;

            color: #94a3b8;

            font-size: 11px;
        }

        .password-help i {
            color: #2563eb;
            font-size: 12px;
        }

        /* =========================================================
           BOOTSTRAP ROW SPACING
        ========================================================= */

        .password-card-body .row {
            --bs-gutter-x: 18px;
            --bs-gutter-y: 15px;
        }

        /* =========================================================
           PASSWORD REQUIREMENTS
        ========================================================= */

        .password-requirements {
            margin-top: 17px;

            padding: 13px 15px;

            border:
                1px solid
                #dce6f4;

            border-radius: 10px;

            background: #f8fbff;
        }

        .requirements-title {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 10px;

            color: #2563eb;

            font-size: 13px;
            font-weight: 800;
        }

        .requirements-list {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 10px 20px;

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .requirements-list li {
            display: flex;
            align-items: center;
            gap: 6px;

            color: #64748b;

            font-size: 11px;
        }

        .requirements-list li i {
            color: #16a34a;
            font-size: 12px;
        }

        /* =========================================================
           FORM FOOTER
        ========================================================= */

        .password-form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 18px;

            margin-top: 16px;

            padding-top: 14px;

            border-top:
                1px solid
                #edf1f6;
        }

        .password-secure-note {
            display: flex;
            align-items: center;
            gap: 7px;

            color: #94a3b8;

            font-size: 11px;
        }

        .password-secure-note i {
            color: #16a34a;
            font-size: 14px;
        }

        .change-password-btn {
            min-width: 220px;
            height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 0 22px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 6px 14px
                rgba(37,99,235,0.18);

            transition: 0.2s ease;
        }

        .change-password-btn:hover {
            color: #ffffff;

            transform:
                translateY(-1px);

            box-shadow:
                0 9px 18px
                rgba(37,99,235,0.24);
        }

        /* =========================================================
           SECURITY INFO
        ========================================================= */

        .security-info {
            margin-top: 14px;

            padding: 13px 15px;

            border-radius: 10px;

            background: #eff6ff;

            border:
                1px solid
                #dbeafe;
        }

        .security-info-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .security-info-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #dbeafe;

            color: #2563eb;

            font-size: 15px;
        }

        .security-info h6 {
            margin: 0 0 3px;

            color: #1e3a8a;

            font-size: 13px;
            font-weight: 800;
        }

        .security-info p {
            margin: 0;

            color: #64748b;

            font-size: 11px;

            line-height: 1.5;
        }

        /* =========================================================
           USER NAVBAR
        ========================================================= */

        .user-navbar {
            height: 68px;

            background: #ffffff;

            padding: 0 24px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            position: sticky;

            top: 0;

            z-index: 900;

            border-bottom:
                1px solid
                #edf0f5;

            box-shadow:
                0 2px 10px
                rgba(15,23,42,0.04);
        }

        .user-nav-left {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .user-welcome-icon {
            width: 40px;
            height: 40px;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 18px;
        }

        .user-welcome span {
            display: block;

            color: #94a3b8;

            font-size: 11px;
            font-weight: 600;
        }

        .user-welcome h5 {
            margin: 2px 0 0;

            color: #172033;

            font-size: 15px;
            font-weight: 800;
        }

        .user-nav-center {
            position: absolute;

            left: 50%;

            transform:
                translateX(-50%);
        }

        .library-status {
            display: flex;
            align-items: center;
            gap: 7px;

            padding: 7px 12px;

            border-radius: 30px;

            background: #f0fdf4;

            color: #15803d;

            font-size: 11px;
            font-weight: 700;
        }

        .status-circle {
            width: 8px;
            height: 8px;

            border-radius: 50%;

            background: #22c55e;
        }

        .user-nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-action,
        .user-logout {
            width: 36px;
            height: 36px;

            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-decoration: none;

            font-size: 15px;
        }

        .nav-action {
            color: #64748b;

            background: #f8fafc;

            border:
                1px solid
                #edf1f5;
        }

        .nav-action:hover {
            color: #2563eb;
            background: #eff6ff;
        }

        .user-logout {
            color: #e11d48;
            background: #fff1f2;

            border:
                1px solid
                #ffe4e6;
        }

        .user-logout:hover {
            color: #be123c;
            background: #ffe4e6;
        }

        .nav-separator {
            width: 1px;
            height: 28px;

            background: #e5eaf0;

            margin: 0 4px;
        }

        .user-profile-pill {
            display: flex;
            align-items: center;
            gap: 8px;

            padding:
                4px
                9px
                4px
                4px;

            border-radius: 30px;

            background: #f8fafc;

            border:
                1px solid
                #edf1f5;
        }

        .user-avatar {
            width: 32px;
            height: 32px;

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

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .user-profile-name strong {
            display: block;

            color: #1e293b;

            font-size: 11px;
        }

        .user-profile-name small {
            display: block;

            color: #94a3b8;

            font-size: 9px;
        }

        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 900px) {

            .user-nav-center {
                display: none;
            }

            .requirements-list {
                grid-template-columns:
                    1fr 1fr;
            }

        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 768px) {

            .password-page {
                padding:
                    16px
                    13px
                    22px;
            }

            .password-container {
                max-width: 100%;
            }

            .password-card-body {
                padding: 18px;
            }

            .password-card-header {
                padding: 18px;
            }

            .password-heading-text h3 {
                font-size: 21px;
            }

            .password-heading-text p {
                font-size: 12px;
            }

            .password-header-text h2 {
                font-size: 20px;
            }

            .password-header-text p {
                font-size: 12px;
            }

        }

        /* =========================================================
           SMALL MOBILE
        ========================================================= */

        @media (max-width: 650px) {

            .user-navbar {
                height: 62px;

                padding:
                    0
                    12px;
            }

            .password-page {
                min-height:
                    calc(
                        100vh - 62px
                    );
            }

            .user-profile-name {
                display: none;
            }

            .user-profile-pill {
                padding: 4px;
            }

            .password-card-header-content {
                align-items: flex-start;
            }

            .requirements-list {
                grid-template-columns:
                    1fr;
            }

            .password-form-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .password-secure-note {
                justify-content: center;
            }

            .change-password-btn {
                width: 100%;
                min-width: 0;
            }

        }

        /* =========================================================
           EXTRA SMALL MOBILE
        ========================================================= */

        @media (max-width: 480px) {

            .password-page-header {
                margin-bottom: 12px;
            }

            .password-heading-icon {
                width: 42px;
                height: 42px;

                font-size: 18px;
            }

            .password-heading-text h3 {
                font-size: 19px;
            }

            .password-heading-text p {
                font-size: 11px;
            }

            .password-card-header {
                padding: 16px;
            }

            .password-header-icon {
                width: 48px;
                height: 48px;
                min-width: 48px;

                font-size: 22px;
            }

            .password-card-body {
                padding: 16px;
            }

            .password-section-heading h5 {
                font-size: 16px;
            }

            .password-section-heading span {
                font-size: 11px;
            }

            .password-form-label {
                font-size: 13px;
            }

            .password-input {
                height: 43px;
                font-size: 13px;
            }

            .password-help {
                font-size: 10px;
            }

            .requirements-title {
                font-size: 12px;
            }

            .requirements-list li {
                font-size: 10px;
            }

            .password-secure-note {
                font-size: 10px;
            }

            .nav-action:nth-child(2) {
                display: none;
            }

            .user-welcome-icon {
                display: none;
            }

        }

    </style>

</head>

<body>

<?php require_once "../includes/user_sidebar.php"; ?>

<div class="user-main">

    <!-- =====================================================
         USER NAVBAR
    ===================================================== -->

    <nav class="user-navbar">

        <div class="user-nav-left">

            <div class="user-welcome-icon">

                <i class="bi bi-shield-lock"></i>

            </div>

            <div class="user-welcome">

                <span>
                    Account Security
                </span>

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

                <span>
                    Library is Open
                </span>

            </div>

        </div>


        <div class="user-nav-right">

         

            

            <div class="nav-separator"></div>


            <!-- Profile -->
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

                    <small>
                        Member
                    </small>

                </div>

            </div>


            <!-- Logout -->
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
         PAGE CONTENT
    ===================================================== -->

    <main class="password-page">

        <div class="password-container">

            <!-- PAGE HEADER -->

            <div class="password-page-header">

                <div class="password-heading">

                   

                    <div class="password-heading-text">

                       
                    </div>

                </div>

            </div>


            <!-- MAIN CARD -->

            <div class="password-main-card">

                <!-- CARD HEADER -->

                <div class="password-card-header">

                    <div class="password-card-header-content">

                        <div class="password-header-icon">

                            <i class="bi bi-shield-lock"></i>

                        </div>

                        <div class="password-header-text">

                            <h2>
                                Secure Your Account
                            </h2>

                            <p>
                                Update your password to keep your
                                library account protected.
                            </p>

                            <span class="security-badge">

                                <i class="bi bi-shield-check"></i>

                                Secure Account

                            </span>

                        </div>

                    </div>

                </div>


                <!-- CARD BODY -->

                <div class="password-card-body">

                    <!-- SUCCESS MESSAGE -->

                    <?php if ($success !== ""): ?>

                        <div
                            class="alert alert-success password-alert alert-dismissible fade show"
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


                    <!-- ERROR MESSAGE -->

                    <?php if ($error !== ""): ?>

                        <div
                            class="alert alert-danger password-alert alert-dismissible fade show"
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


                    <!-- SECTION HEADING -->

                    <div class="password-section-heading">

                        <div class="password-section-icon">

                            <i class="bi bi-lock"></i>

                        </div>

                        <div>

                            <h5>
                                Update Your Password
                            </h5>

                            <span>
                                Enter your current password and choose
                                a new one
                            </span>

                        </div>

                    </div>


                    <!-- PASSWORD FORM -->

                    <form
                        method="POST"
                        action=""
                    >

                        <div class="row g-4">

                            <!-- CURRENT PASSWORD -->

                            <div class="col-12">

                                <label
                                    class="password-form-label"
                                    for="current_password"
                                >

                                    <i class="bi bi-lock me-1"></i>

                                    Current Password

                                </label>

                                <div class="password-input-wrapper">

                                    <i
                                        class="bi bi-lock password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="current_password"
                                        id="current_password"
                                        class="password-input"
                                        placeholder="Enter your current password"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        onclick="togglePassword(
                                            'current_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>

                                <div class="password-help">

                                    <i class="bi bi-info-circle"></i>

                                    Enter the password you currently use
                                    to sign in.

                                </div>

                            </div>


                            <!-- NEW PASSWORD -->

                            <div class="col-md-6">

                                <label
                                    class="password-form-label"
                                    for="new_password"
                                >

                                    <i class="bi bi-key me-1"></i>

                                    New Password

                                </label>

                                <div class="password-input-wrapper">

                                    <i
                                        class="bi bi-key password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="new_password"
                                        id="new_password"
                                        class="password-input"
                                        placeholder="Enter new password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        onclick="togglePassword(
                                            'new_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>

                                <div class="password-help">

                                    <i class="bi bi-check-circle"></i>

                                    Minimum 6 characters

                                </div>

                            </div>


                            <!-- CONFIRM PASSWORD -->

                            <div class="col-md-6">

                                <label
                                    class="password-form-label"
                                    for="confirm_password"
                                >

                                    <i class="bi bi-check2-square me-1"></i>

                                    Confirm New Password

                                </label>

                                <div class="password-input-wrapper">

                                    <i
                                        class="bi bi-check2-square password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="confirm_password"
                                        id="confirm_password"
                                        class="password-input"
                                        placeholder="Confirm new password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        onclick="togglePassword(
                                            'confirm_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>

                                <div class="password-help">

                                    <i class="bi bi-shield-check"></i>

                                    Must match new password

                                </div>

                            </div>

                        </div>


                        <!-- PASSWORD REQUIREMENTS -->

                        <div class="password-requirements">

                            <div class="requirements-title">

                                <i class="bi bi-list-check"></i>

                                Password Requirements

                            </div>

                            <ul class="requirements-list">

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    At least 6 characters

                                </li>

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    Different from current password

                                </li>

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    Confirm password must match

                                </li>

                            </ul>

                        </div>


                        <!-- FORM FOOTER -->

                        <div class="password-form-footer">

                            <div class="password-secure-note">

                                <i class="bi bi-shield-check"></i>

                                <span>
                                    Your password is securely encrypted.
                                </span>

                            </div>

                            <button
                                type="submit"
                                class="change-password-btn"
                            >

                                <i class="bi bi-shield-check"></i>

                                Change Password

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </main>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

    // =====================================================
    // TOGGLE SIDEBAR
    // =====================================================

    function toggleSidebar() {

        const sidebar =
            document.querySelector(".user-sidebar");

        if (sidebar) {

            sidebar.classList.toggle("show");

        }

    }


    // =====================================================
    // SHOW / HIDE PASSWORD
    // =====================================================

    function togglePassword(inputId, button) {

        const input =
            document.getElementById(inputId);

        const icon =
            button.querySelector("i");

        if (input.type === "password") {

            input.type = "text";

            icon.classList.remove(
                "bi-eye"
            );

            icon.classList.add(
                "bi-eye-slash"
            );

            button.title = "Hide password";

        } else {

            input.type = "password";

            icon.classList.remove(
                "bi-eye-slash"
            );

            icon.classList.add(
                "bi-eye"
            );

            button.title = "Show password";

        }

    }

</script>

</body>

</html>