<?php
require_once "../config/auth.php";
requireAdmin();

require_once "../config/database.php";

$userId = $_SESSION['user_id'] ?? 0;
$message = "";
$error = "";

/* =========================
   UPDATE PROFILE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === "" || $email === "") {
        $error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        /* Check duplicate email */
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND id != ?"
        );
        $check->bind_param("si", $email, $userId);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "This email address is already registered.";
        } else {

            $update = $conn->prepare(
                "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?"
            );

            $update->bind_param(
                "sssi",
                $name,
                $email,
                $phone,
                $userId
            );

            if ($update->execute()) {

                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

                $message = "Profile updated successfully.";
            } else {
                $error = "Unable to update profile.";
            }

            $update->close();
        }

        $check->close();
    }
}

/* =========================
   GET ADMIN DATA
========================= */

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, role, created_at
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

if (!$admin) {
    header("Location: dashboard.php");
    exit();
}

/* =========================
   STATISTICS
========================= */

$totalBooks = 0;
$issuedBooks = 0;
$returnedBooks = 0;

/* Total books */
$query = $conn->query("SELECT COUNT(*) AS total FROM books");

if ($query) {
    $row = $query->fetch_assoc();
    $totalBooks = (int)$row['total'];
}

/* Issued books */
$query = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);

if ($query) {
    $row = $query->fetch_assoc();
    $issuedBooks = (int)$row['total'];
}

/* Returned books */
$query = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Returned'"
);

if ($query) {
    $row = $query->fetch_assoc();
    $returnedBooks = (int)$row['total'];
}

/* =========================
   PAGE DATA
========================= */

$current_path = $_SERVER['PHP_SELF'];

function isAdminActive($path)
{
    global $current_path;

    $path = rtrim($path, '/');

    return (
        $current_path === $path ||
        strpos($current_path, $path . '/') === 0
    ) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Profile | Library Management System</title>

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
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(
                180deg,
                #172554 0%,
                #1e3a8a 55%,
                #1d4ed8 100%
            );
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.25);
            border-radius: 10px;
        }

        /* Logo */

        .admin-sidebar-logo {
            padding: 26px 22px;
            border-bottom: 1px solid rgba(255,255,255,.10);
        }

        .admin-sidebar-logo a {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
            color: white;
        }

        .admin-logo-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(
                135deg,
                #ffffff,
                #dbeafe
            );
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            box-shadow: 0 8px 20px rgba(0,0,0,.15);
        }

        .admin-logo-content h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
        }

        .admin-logo-content span {
            font-size: 11px;
            color: rgba(255,255,255,.65);
        }

        /* Menu */

        .admin-sidebar-menu {
            padding: 22px 14px;
        }

        .menu-section-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,.45);
            padding: 12px 12px 8px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            margin-bottom: 5px;
            border-radius: 11px;
            color: rgba(255,255,255,.78);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: .25s ease;
        }

        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.10);
            transform: translateX(2px);
        }

        .sidebar-link.active {
            color: white;
            background: rgba(255,255,255,.15);
            box-shadow: inset 3px 0 0 #fff;
        }

        .sidebar-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .logout-link {
            color: #fecaca !important;
            margin-top: 15px;
        }

        .logout-link:hover {
            background: rgba(239,68,68,.15);
            color: #fff !important;
        }

        /* User bottom */

        .sidebar-user {
            margin: 12px 14px 20px;
            padding: 14px;
            border-radius: 14px;
            background: rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .sidebar-user-info strong {
            display: block;
            font-size: 13px;
        }

        .sidebar-user-info small {
            font-size: 10px;
            color: rgba(255,255,255,.6);
        }

        /* =========================
           MAIN
        ========================= */

        .admin-main {
            margin-left: 260px;
            min-height: 100vh;
        }

        /* =========================
           NAVBAR
        ========================= */

        .admin-navbar {
            height: 82px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .navbar-title h5 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
            color: #172033;
        }

        .navbar-title span {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 12px;
        }

        .navbar-title span i {
            font-size: 9px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .notification-btn {
            width: 42px;
            height: 42px;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 11px;
            color: #64748b;
            font-size: 18px;
            position: relative;
            cursor: pointer;
        }

        .notification-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            top: 9px;
            right: 9px;
            border: 2px solid white;
        }

        .header-divider {
            width: 1px;
            height: 35px;
            background: #e5e7eb;
        }

        .nav-admin {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .nav-admin-info strong {
            display: block;
            font-size: 13px;
            color: #1e293b;
        }

        .nav-admin-info small {
            display: block;
            color: #94a3b8;
            font-size: 10px;
        }

        .admin-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border-radius: 10px;
            background: #fef2f2;
            color: #dc2626;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: .25s;
        }

        .admin-logout-btn:hover {
            background: #dc2626;
            color: white;
        }

        /* =========================
           CONTENT
        ========================= */

        .admin-content {
            padding: 34px;
            max-width: 1500px;
            margin: auto;
        }

        /* Alert */

        .alert-box {
            border: none;
            border-radius: 13px;
            padding: 14px 18px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .success-alert {
            background: #ecfdf5;
            color: #047857;
        }

        .error-alert {
            background: #fef2f2;
            color: #b91c1c;
        }

        /* =========================
           PROFILE HEADER
        ========================= */

        .profile-header {
            background: linear-gradient(
                135deg,
                #172554,
                #1d4ed8
            );
            border-radius: 20px;
            padding: 34px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            border: 35px solid rgba(255,255,255,.05);
            right: -60px;
            top: -80px;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 22px;
            position: relative;
            z-index: 2;
        }

        .profile-avatar-large {
            width: 92px;
            height: 92px;
            min-width: 92px;
            border-radius: 22px;
            background: white;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 800;
            box-shadow: 0 10px 25px rgba(0,0,0,.18);
        }

        .profile-header h2 {
            margin: 0 0 7px;
            font-size: 27px;
            font-weight: 800;
        }

        .profile-header p {
            margin: 0 0 13px;
            color: rgba(255,255,255,.75);
            font-size: 14px;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.13);
            border: 1px solid rgba(255,255,255,.20);
            padding: 7px 13px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
        }

        /* =========================
           GRID
        ========================= */

        .profile-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(300px, .75fr);
            gap: 28px;
        }

        .profile-card {
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(15,23,42,.04);
        }

        .card-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 27px;
            padding-bottom: 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .card-heading h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #172033;
        }

        .card-heading p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .heading-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            z-index: 2;
        }

        .form-control {
            min-height: 48px;
            border: 1px solid #dce3ec;
            border-radius: 10px;
            padding: 11px 14px 11px 43px;
            font-size: 14px;
            color: #1e293b;
            box-shadow: none !important;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.10) !important;
        }

        .form-control[readonly] {
            background: #f8fafc;
        }

        .field-help {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #94a3b8;
        }

        .save-btn {
            border: none;
            border-radius: 10px;
            padding: 12px 23px;
            background: #2563eb;
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: .25s;
        }

        .save-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        /* =========================
           ACCOUNT INFO
        ========================= */

        .info-list {
            display: flex;
            flex-direction: column;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 17px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-content small {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            margin-bottom: 3px;
        }

        .info-content strong {
            font-size: 13px;
            color: #334155;
            word-break: break-word;
        }

        /* =========================
           STAT CARDS
        ========================= */

        .stats-title {
            margin-top: 28px;
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 800;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 15px;
            padding: 19px;
            text-align: center;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 10px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 23px;
            font-weight: 800;
            color: #172033;
        }

        .stat-card p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 11px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-card {
                padding: 25px;
            }
        }

        @media (max-width: 992px) {

            .admin-sidebar {
                width: 230px;
            }

            .admin-main {
                margin-left: 230px;
            }

            .admin-navbar {
                padding: 0 22px;
            }

            .admin-content {
                padding: 25px;
            }

            .nav-admin-info {
                display: none;
            }
        }

        @media (max-width: 768px) {

            .admin-sidebar {
                left: -260px;
                width: 260px;
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-navbar {
                height: 75px;
            }

            .admin-content {
                padding: 18px;
            }

            .profile-header {
                padding: 25px;
            }

            .profile-header-content {
                align-items: flex-start;
            }

            .profile-avatar-large {
                width: 72px;
                height: 72px;
                min-width: 72px;
                font-size: 28px;
            }

            .profile-header h2 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {

            .admin-navbar {
                padding: 0 15px;
            }

            .navbar-title h5 {
                font-size: 15px;
            }

            .navbar-title span {
                display: none;
            }

            .notification-btn {
                width: 38px;
                height: 38px;
            }

            .nav-avatar {
                width: 38px;
                height: 38px;
            }

            .admin-logout-btn span {
                display: none;
            }

            .admin-logout-btn {
                width: 38px;
                height: 38px;
                justify-content: center;
                padding: 0;
            }

            .profile-header-content {
                flex-direction: column;
            }

            .profile-card {
                padding: 20px;
            }

            .card-heading {
                align-items: flex-start;
            }
        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            .admin-sidebar,
            .admin-navbar,
            .save-btn {
                display: none !important;
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-content {
                padding: 0;
            }

            body {
                background: white;
            }

            .profile-card {
                box-shadow: none;
            }
        }

    </style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="admin-sidebar" id="adminSidebar">

    <div class="admin-sidebar-logo">

        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">

            <div class="admin-logo-icon">
                <i class="bi bi-book-half"></i>
            </div>

            <div class="admin-logo-content">
                <h4>Library</h4>
                <span>Management System</span>
            </div>

        </a>

    </div>

    <div class="admin-sidebar-menu">

        <div class="menu-section-title">
            Main Menu
        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/dashboard.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/dashboard.php'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>
            <span>Dashboard</span>
        </a>

        <div class="menu-section-title">
            Library Management
        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/books/index.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/books'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-book"></i>
            </span>
            <span>Books</span>
        </a>

        <a
            href="<?php echo BASE_URL; ?>/admin/categories/index.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/categories'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-tags"></i>
            </span>
            <span>Categories</span>
        </a>

        <a
            href="<?php echo BASE_URL; ?>/admin/issue/index.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/issue'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-arrow-left-right"></i>
            </span>
            <span>Issue / Return</span>
        </a>

        <div class="menu-section-title">
            User Management
        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/users'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-people"></i>
            </span>
            <span>Users</span>
        </a>

        <div class="menu-section-title">
            Reports
        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/books_report.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/reports'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-file-earmark-bar-graph"></i>
            </span>
            <span>Reports</span>
        </a>

        <div class="menu-section-title">
            Account
        </div>

        <a
            href="<?php echo BASE_URL; ?>/admin/profile.php"
            class="sidebar-link <?php echo isAdminActive(BASE_URL . '/admin/profile.php'); ?>"
        >
            <span class="sidebar-icon">
                <i class="bi bi-person-circle"></i>
            </span>
            <span>My Profile</span>
        </a>

        <a
            href="<?php echo BASE_URL; ?>/logout.php"
            class="sidebar-link logout-link"
        >
            <span class="sidebar-icon">
                <i class="bi bi-box-arrow-right"></i>
            </span>
            <span>Logout</span>
        </a>

    </div>

    <div class="sidebar-user">

        <div class="sidebar-user-avatar">
            <?php
            echo strtoupper(
                substr($admin['name'], 0, 1)
            );
            ?>
        </div>

        <div class="sidebar-user-info">

            <strong>
                <?php echo htmlspecialchars($admin['name']); ?>
            </strong>

            <small>
                Administrator
            </small>

        </div>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="admin-main">

    <!-- =========================
         NAVBAR
    ========================= -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    My Profile
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>
                    Home

                    <i class="bi bi-chevron-right"></i>

                    Profile

                </span>

            </div>

        </div>


        <div class="navbar-right">

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >

                <i class="bi bi-bell"></i>

                <?php if ($issuedBooks > 0): ?>

                    <span class="notification-dot"></span>

                <?php endif; ?>

            </button>


            <div class="header-divider"></div>


            <div class="nav-admin">

                <div class="nav-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div class="nav-admin-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $admin['name']
                        );
                        ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>


            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>Logout</span>

            </a>

        </div>

    </nav>


    <!-- =========================
         CONTENT
    ========================= -->

    <div class="admin-content">


        <?php if ($message): ?>

            <div class="alert-box success-alert">

                <i class="bi bi-check-circle-fill"></i>

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert-box error-alert">

                <i class="bi bi-exclamation-circle-fill"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- PROFILE HEADER -->

        <div class="profile-header">

            <div class="profile-header-content">

                <div class="profile-avatar-large">

                    <?php
                    echo strtoupper(
                        substr($admin['name'], 0, 1)
                    );
                    ?>

                </div>


                <div>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $admin['name']
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $admin['email']
                        );
                        ?>
                    </p>

                    <span class="admin-badge">

                        <i class="bi bi-shield-check"></i>

                        Administrator Account

                    </span>

                </div>

            </div>

        </div>


        <!-- PROFILE GRID -->

        <div class="profile-grid">


            <!-- =====================
                 EDIT PROFILE
            ====================== -->

            <div class="profile-card">

                <div class="card-heading">

                    <div>

                        <h4>
                            Personal Information
                        </h4>

                        <p>
                            Update your account information
                        </p>

                    </div>

                    <div class="heading-icon">

                        <i class="bi bi-person-lines-fill"></i>

                    </div>

                </div>


                <form
                    method="POST"
                    action=""
                    id="profileForm"
                >

                    <!-- Name -->

                    <div class="form-group">

                        <label class="form-label">
                            Full Name
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['name']
                                    );
                                ?>"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>

                    </div>


                    <!-- Email -->

                    <div class="form-group">

                        <label class="form-label">
                            Email Address
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-envelope"></i>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['email']
                                    );
                                ?>"
                                placeholder="Enter email address"
                                required
                            >

                        </div>

                    </div>


                    <!-- Phone -->

                    <div class="form-group">

                        <label class="form-label">
                            Phone Number
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-telephone"></i>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['phone'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter phone number"
                            >

                        </div>

                        <span class="field-help">
                            Phone number is optional.
                        </span>

                    </div>


                    <!-- Role -->

                    <div class="form-group">

                        <label class="form-label">
                            Account Role
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-shield-check"></i>

                            <input
                                type="text"
                                class="form-control"
                                value="Administrator"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- Button -->

                    <button
                        type="submit"
                        class="save-btn"
                    >

                        <i class="bi bi-check2-circle"></i>

                        Save Changes

                    </button>

                </form>

            </div>


            <!-- =====================
                 ACCOUNT DETAILS
            ====================== -->

            <div>

                <div class="profile-card">

                    <div class="card-heading">

                        <div>

                            <h4>
                                Account Details
                            </h4>

                            <p>
                                Your account information
                            </p>

                        </div>

                        <div class="heading-icon">

                            <i class="bi bi-person-vcard"></i>

                        </div>

                    </div>


                    <div class="info-list">


                        <!-- ID -->

                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-hash"></i>

                            </div>

                            <div class="info-content">

                                <small>
                                    User ID
                                </small>

                                <strong>
                                    #<?php
                                    echo (int)$admin['id'];
                                    ?>
                                </strong>

                            </div>

                        </div>


                        <!-- Email -->

                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-envelope"></i>

                            </div>

                            <div class="info-content">

                                <small>
                                    Email Address
                                </small>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $admin['email']
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-telephone"></i>

                            </div>

                            <div class="info-content">

                                <small>
                                    Phone Number
                                </small>

                                <strong>

                                    <?php
                                    echo !empty($admin['phone'])
                                        ? htmlspecialchars($admin['phone'])
                                        : 'Not provided';
                                    ?>

                                </strong>

                            </div>

                        </div>


                        <!-- Role -->

                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-shield-lock"></i>

                            </div>

                            <div class="info-content">

                                <small>
                                    Account Role
                                </small>

                                <strong>
                                    Administrator
                                </strong>

                            </div>

                        </div>


                        <!-- Created -->

                        <div class="info-item">

                            <div class="info-icon">

                                <i class="bi bi-calendar3"></i>

                            </div>

                            <div class="info-content">

                                <small>
                                    Member Since
                                </small>

                                <strong>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $admin['created_at']
                                        )
                                    );
                                    ?>

                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================
                     STATISTICS
                ====================== -->

                <h4 class="stats-title">
                    Library Overview
                </h4>

                <div class="stats-grid">


                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-book"></i>

                        </div>

                        <h3>
                            <?php echo $totalBooks; ?>
                        </h3>

                        <p>
                            Total Books
                        </p>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-journal-arrow-up"></i>

                        </div>

                        <h3>
                            <?php echo $issuedBooks; ?>
                        </h3>

                        <p>
                            Issued Books
                        </p>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-journal-check"></i>

                        </div>

                        <h3>
                            <?php echo $returnedBooks; ?>
                        </h3>

                        <p>
                            Returned Books
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>


<script>

    /* =========================
       FORM VALIDATION
    ========================= */

    document
        .getElementById("profileForm")
        .addEventListener("submit", function (e) {

            const name =
                document.querySelector(
                    'input[name="name"]'
                ).value.trim();

            const email =
                document.querySelector(
                    'input[name="email"]'
                ).value.trim();

            if (name.length < 2) {

                e.preventDefault();

                alert("Please enter a valid name.");

                return;
            }

            if (email === "") {

                e.preventDefault();

                alert("Please enter your email address.");

                return;
            }

        });

</script>

</body>
</html>