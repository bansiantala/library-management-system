<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}


// =====================================================
// CURRENTLY ISSUED BOOKS
// =====================================================

$issued_books = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE user_id = ?
      AND status = 'Issued'
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $issued_books = (int)($row['total'] ?? 0);

    $stmt->close();
}


// =====================================================
// RETURNED BOOKS
// =====================================================

$returned_books = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE user_id = ?
      AND status = 'Returned'
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $returned_books = (int)($row['total'] ?? 0);

    $stmt->close();
}


// =====================================================
// TOTAL BOOKS
// =====================================================

$total_books = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM books
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_books = (int)($row['total'] ?? 0);

    $result->free();
}


// =====================================================
// DUE DATE NOTIFICATIONS
//
// Notification appears when:
//
// 3 days remaining -> Due Soon
// 2 days remaining -> Due Soon
// 1 day remaining  -> Due Soon
// 0 days remaining -> Due Today
//
// This matches the Due Soon box on My Books page.
// =====================================================

$notifications = [];

$today = new DateTime(date("Y-m-d"));

$stmtNotification = $conn->prepare("
    SELECT
        ib.id,
        ib.book_id,
        ib.issue_date,
        ib.return_date,
        ib.status,
        b.title
    FROM issued_books ib
    INNER JOIN books b
        ON ib.book_id = b.id
    WHERE ib.user_id = ?
      AND ib.status = 'Issued'
    ORDER BY ib.return_date ASC
");

if ($stmtNotification) {

    $stmtNotification->bind_param("i", $user_id);

    $stmtNotification->execute();

    $notificationResult = $stmtNotification->get_result();


    while ($row = $notificationResult->fetch_assoc()) {

        // -------------------------------------------------
        // Validate dates
        // -------------------------------------------------

        if (
            empty($row['issue_date']) ||
            empty($row['return_date'])
        ) {
            continue;
        }


        // -------------------------------------------------
        // Issue Date
        // -------------------------------------------------

        $issueTimestamp = strtotime($row['issue_date']);

        if ($issueTimestamp === false) {
            continue;
        }

        $issueDate = new DateTime(
            date("Y-m-d", $issueTimestamp)
        );


        // -------------------------------------------------
        // Due Date
        // -------------------------------------------------

        $dueTimestamp = strtotime($row['return_date']);

        if ($dueTimestamp === false) {
            continue;
        }

        $dueDate = new DateTime(
            date("Y-m-d", $dueTimestamp)
        );


        // -------------------------------------------------
        // Calculate borrowing period
        // -------------------------------------------------

        $borrowingPeriod = (int)$issueDate
            ->diff($dueDate)
            ->format("%r%a");


        // -------------------------------------------------
        // Calculate days remaining
        // -------------------------------------------------

        $daysRemaining = (int)$today
            ->diff($dueDate)
            ->format("%r%a");


        // -------------------------------------------------
        // SHOW NOTIFICATION
        //
        // This is the important fix.
        //
        // Previously:
        // $daysRemaining === $reminderDays
        //
        // Now:
        // 0 to 3 days remaining = notification
        // -------------------------------------------------

        if ($daysRemaining >= 0 && $daysRemaining <= 3) {

            if ($daysRemaining === 0) {

                $notificationTitle = "Book Due Today";

            } else {

                $notificationTitle = "Book Due Soon";
            }


            $notifications[] = [

                "id" => (int)$row['id'],

                "title" => $row['title'],

                "issue_date" =>
                    $issueDate->format("d M Y"),

                "due_date" =>
                    $dueDate->format("d M Y"),

                "borrowing_period" =>
                    $borrowingPeriod,

                "days_remaining" =>
                    $daysRemaining,

                "notification_title" =>
                    $notificationTitle
            ];
        }
    }


    $stmtNotification->close();
}


$totalNotifications = count($notifications);


// =====================================================
// USER NAME
// =====================================================

$userName = $_SESSION['user_name'] ?? 'User';

$userInitial = strtoupper(
    substr($userName, 0, 1)
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Dashboard</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >


    <!-- =====================================================
         USER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <!-- =====================================================
         APPLY SAVED THEME BEFORE PAGE LOAD
    ====================================================== -->

    <script>

        (function () {

            const savedTheme =
                localStorage.getItem("library_theme");

            if (savedTheme === "dark") {

                document.documentElement.classList.add(
                    "library-dark-mode"
                );
            }

        })();

    </script>


    <style>

        /* =====================================================
           GLOBAL DARK MODE
        ===================================================== */

        html.library-dark-mode,
        body.library-dark-mode {

            background: #0f172a !important;
            color: #e2e8f0 !important;
        }


        body.library-dark-mode {

            background: #0f172a !important;
        }


        /* =====================================================
           USER NAVBAR
        ===================================================== */

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

            box-shadow:
                0 3px 15px
                rgba(15, 23, 42, 0.035);

            transition:
                background .25s ease,
                border-color .25s ease,
                box-shadow .25s ease;
        }


        /* =====================================================
           LEFT
        ===================================================== */

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


        /* =====================================================
           CENTER STATUS
        ===================================================== */

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

            box-shadow:
                0 0 0 4px
                rgba(34, 197, 94, .10);
        }


        /* =====================================================
           RIGHT
        ===================================================== */

        .user-nav-right {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

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

            position: relative;

            cursor: pointer;
        }


        .nav-action:hover {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;

            transform: translateY(-1px);
        }


        /* =====================================================
           FAVORITE
        ===================================================== */

        .favorite-nav-action {

            color: #e11d48;
        }


        .favorite-nav-action:hover {

            background: #fff1f2;

            border-color: #fecdd3;

            color: #e11d48;
        }


        /* =====================================================
           NOTIFICATION
        ===================================================== */

        .notification-wrapper {

            position: relative;
        }


        .notification-nav-action {

            position: relative;

            cursor: pointer;
        }


        .notification-nav-action.active {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;
        }


        /* =====================================================
           NOTIFICATION BADGE
        ===================================================== */

        .notification-badge {

            position: absolute;

            top: -5px;

            right: -5px;

            min-width: 19px;

            height: 19px;

            padding: 0 5px;

            border-radius: 50px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ef4444;

            color: #ffffff;

            border: 2px solid #ffffff;

            font-size: 9px;

            font-weight: 800;

            line-height: 1;

            z-index: 5;
        }


        /* =====================================================
           NOTIFICATION POPUP
        ===================================================== */

        .notification-popup {

            position: absolute;

            top: calc(100% + 12px);

            right: -80px;

            width: 350px;

            max-width: calc(100vw - 30px);

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 15px 40px
                rgba(15, 23, 42, 0.15);

            opacity: 0;

            visibility: hidden;

            pointer-events: none;

            transform:
                translateY(-8px)
                scale(.98);

            transition:
                opacity .2s ease,
                transform .2s ease,
                visibility .2s ease;

            z-index: 2000;

            overflow: hidden;
        }


        .notification-popup.show {

            opacity: 1;

            visibility: visible;

            pointer-events: auto;

            transform:
                translateY(0)
                scale(1);
        }


        /* =====================================================
           POPUP HEADER
        ===================================================== */

        .notification-popup-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 15px 16px;

            border-bottom: 1px solid #edf0f5;

            background: #ffffff;
        }


        .notification-popup-title {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .notification-popup-title i {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 14px;
        }


        .notification-popup-title strong {

            color: #172033;

            font-size: 13px;

            font-weight: 800;
        }


        .notification-count {

            min-width: 24px;

            height: 24px;

            padding: 0 7px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50px;

            background: #2563eb;

            color: #ffffff;

            font-size: 9px;

            font-weight: 800;
        }


        /* =====================================================
           NOTIFICATION BODY
        ===================================================== */

        .notification-popup-body {

            max-height: 330px;

            overflow-y: auto;

            padding: 8px;
        }


        /* =====================================================
           SINGLE NOTIFICATION
        ===================================================== */

        .notification-item {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 11px;

            border-radius: 10px;

            text-decoration: none;

            transition: background .2s ease;
        }


        .notification-item:hover {

            background: #f8fafc;
        }


        .notification-item-icon {

            width: 35px;

            height: 35px;

            min-width: 35px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #fff7ed;

            color: #f59e0b;

            font-size: 15px;
        }


        .notification-item-content {

            min-width: 0;

            flex: 1;
        }


        .notification-item-content strong {

            display: block;

            color: #1e293b;

            font-size: 11px;

            font-weight: 800;

            line-height: 1.4;

            margin-bottom: 3px;
        }


        .notification-item-content p {

            margin: 0;

            color: #64748b;

            font-size: 10px;

            line-height: 1.5;
        }


        .notification-item-content .due-date {

            display: inline-block;

            margin-top: 5px;

            padding: 3px 7px;

            border-radius: 20px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 8px;

            font-weight: 700;
        }


        /* =====================================================
           NO NOTIFICATIONS
        ===================================================== */

        .no-notifications {

            padding: 30px 15px;

            text-align: center;

            color: #94a3b8;
        }


        .no-notifications i {

            display: block;

            font-size: 28px;

            margin-bottom: 8px;

            color: #cbd5e1;
        }


        .no-notifications strong {

            display: block;

            color: #64748b;

            font-size: 11px;

            margin-bottom: 3px;
        }


        .no-notifications span {

            font-size: 9px;

            color: #94a3b8;
        }


        /* =====================================================
           POPUP FOOTER
        ===================================================== */

        .notification-popup-footer {

            padding: 9px 14px;

            border-top: 1px solid #edf0f5;

            text-align: center;

            background: #fafbfc;
        }


        .notification-popup-footer a {

            color: #2563eb;

            font-size: 9px;

            font-weight: 600;

            text-decoration: none;
        }


        .notification-popup-footer a:hover {

            text-decoration: underline;
        }


        /* =====================================================
           DARK MODE - NOTIFICATION POPUP
        ===================================================== */

        body.library-dark-mode .notification-popup {

            background: #111827;

            border-color: #334155;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.40);
        }


        body.library-dark-mode .notification-popup-header {

            background: #111827;

            border-bottom-color: #334155;
        }


        body.library-dark-mode .notification-popup-title strong {

            color: #f8fafc;
        }


        body.library-dark-mode .notification-popup-title i {

            background: #172554;

            color: #60a5fa;
        }


        body.library-dark-mode .notification-item:hover {

            background: #1e293b;
        }


        body.library-dark-mode .notification-item-content strong {

            color: #f1f5f9;
        }


        body.library-dark-mode .notification-item-content p {

            color: #94a3b8;
        }


        body.library-dark-mode .notification-item-icon {

            background: #422006;

            color: #fbbf24;
        }


        body.library-dark-mode .notification-item-content .due-date {

            background: #172554;

            color: #60a5fa;
        }


        body.library-dark-mode .no-notifications i {

            color: #475569;
        }


        body.library-dark-mode .no-notifications strong {

            color: #cbd5e1;
        }


        body.library-dark-mode .no-notifications span {

            color: #64748b;
        }


        body.library-dark-mode .notification-popup-footer {

            background: #0f172a;

            border-top-color: #334155;
        }


        /* =====================================================
           DARK MODE BUTTON
        ===================================================== */

        .theme-toggle-btn {

            width: 40px;

            height: 40px;

            border-radius: 11px;

            background: #f8fafc;

            border: 1px solid #e8edf3;

            color: #64748b;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            font-size: 17px;

            transition: all .25s ease;
        }


        .theme-toggle-btn:hover {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;

            transform: translateY(-1px);
        }


        /* =====================================================
           SEPARATOR
        ===================================================== */

        .nav-separator {

            width: 1px;

            height: 34px;

            background: #e5e7eb;

            margin: 0 5px;
        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .user-profile-pill {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 5px 10px 5px 5px;

            background: #f8fafc;

            border: 1px solid #e8edf3;

            border-radius: 30px;
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


        /* =====================================================
           LOGOUT
        ===================================================== */

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


        /* =====================================================
           DARK MODE - PAGE CONTENT
        ===================================================== */

        body.library-dark-mode .dashboard-content {

            color: #e2e8f0;
        }


        body.library-dark-mode .welcome-box {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #e2e8f0 !important;
        }


        body.library-dark-mode .welcome-box h2 {

            color: #f8fafc !important;
        }


        body.library-dark-mode .welcome-box p {

            color: #94a3b8 !important;
        }


        body.library-dark-mode .stat-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.20) !important;
        }


        body.library-dark-mode .stat-card h6 {

            color: #94a3b8 !important;
        }


        body.library-dark-mode .stat-card h2 {

            color: #f8fafc !important;
        }


        body.library-dark-mode .section-title {

            color: #f8fafc !important;
        }


        body.library-dark-mode .quick-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #e2e8f0 !important;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.18) !important;
        }


        body.library-dark-mode .quick-card h6 {

            color: #f8fafc !important;
        }


        body.library-dark-mode .quick-card p {

            color: #94a3b8 !important;
        }


        /* =====================================================
           DARK MODE - NAVBAR
        ===================================================== */

        body.library-dark-mode .user-navbar {

            background: #111827 !important;

            border-bottom-color: #263449 !important;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.25);
        }


        body.library-dark-mode .user-welcome h5 {

            color: #f8fafc !important;
        }


        body.library-dark-mode .user-welcome-icon {

            background: #172554 !important;

            color: #60a5fa !important;
        }


        body.library-dark-mode .library-status {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #94a3b8 !important;
        }


        body.library-dark-mode .nav-action {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #cbd5e1 !important;
        }


        body.library-dark-mode .nav-action:hover {

            background: #172554 !important;

            border-color: #3b82f6 !important;

            color: #60a5fa !important;
        }


        body.library-dark-mode .favorite-nav-action {

            color: #fb7185 !important;
        }


        body.library-dark-mode .favorite-nav-action:hover {

            background: #3f172a !important;

            border-color: #881337 !important;

            color: #fb7185 !important;
        }


        body.library-dark-mode .theme-toggle-btn {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #facc15 !important;
        }


        body.library-dark-mode .theme-toggle-btn:hover {

            background: #422006 !important;

            border-color: #92400e !important;

            color: #fde68a !important;
        }


        body.library-dark-mode .nav-separator {

            background: #334155 !important;
        }


        body.library-dark-mode .user-profile-pill {

            background: #1e293b !important;

            border-color: #334155 !important;
        }


        body.library-dark-mode .user-profile-name strong {

            color: #f1f5f9 !important;
        }


        body.library-dark-mode .user-profile-name small {

            color: #94a3b8 !important;
        }


        body.library-dark-mode .user-logout {

            background: #3f172a !important;

            border-color: #7f1d1d !important;

            color: #f87171 !important;
        }


        body.library-dark-mode .user-logout:hover {

            background: #ef4444 !important;

            border-color: #ef4444 !important;

            color: #ffffff !important;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .user-nav-center {

                display: none;
            }


            .user-navbar {

                padding: 0 20px;
            }


            .notification-popup {

                right: -60px;
            }
        }


        @media (max-width: 650px) {

            .user-navbar {

                height: 70px;

                padding: 0 14px;
            }


            .user-welcome-icon {

                width: 39px;

                height: 39px;
            }


            .user-welcome span {

                font-size: 9px;
            }


            .user-welcome h5 {

                font-size: 13px;
            }


            .nav-action,
            .theme-toggle-btn {

                width: 37px;

                height: 37px;

                font-size: 15px;
            }


            .user-profile-name {

                display: none;
            }


            .user-profile-pill {

                padding: 3px;

                border-radius: 50%;
            }


            .user-avatar {

                width: 34px;

                height: 34px;
            }


            .user-logout {

                width: 37px;

                height: 37px;
            }


            .notification-popup {

                position: fixed;

                top: 75px;

                right: 12px;

                width: 350px;

                max-width: calc(100vw - 24px);
            }
        }


        @media (max-width: 450px) {

            .user-nav-right {

                gap: 5px;
            }


            .user-nav-left {

                gap: 8px;
            }


            .notification-badge {

                top: -4px;

                right: -4px;
            }


            .notification-popup {

                right: 10px;

                width: calc(100vw - 20px);

                max-width: none;
            }
        }

    </style>

</head>


<body>


<?php include "../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =====================================================
         TOP NAVBAR
    ===================================================== -->

    <nav class="user-navbar">


        <!-- =================================================
             LEFT
        ================================================== -->

        <div class="user-nav-left">

            <div class="user-welcome-icon">

                <i class="bi bi-book-half"></i>

            </div>


            <div class="user-welcome">

                <span>
                    Welcome back user
                </span>


                <h5>
                    <?php
                    echo htmlspecialchars($userName);
                    ?>
                </h5>

            </div>

        </div>


        <!-- =================================================
             CENTER
        ================================================== -->

        <div class="user-nav-center">

            <div class="library-status">

                <span class="status-circle"></span>

                <span>
                    Library is Open
                </span>

            </div>

        </div>


        <!-- =================================================
             RIGHT
        ================================================== -->

        <div class="user-nav-right">


            <!-- =================================================
                 FAVORITE
            ================================================== -->

            <a
                href="<?php echo BASE_URL; ?>/user/favorites/index.php"
                class="nav-action favorite-nav-action"
                title="Favorite Books"
                aria-label="Favorite Books"
            >

                <i class="bi bi-heart"></i>

            </a>


            <!-- =================================================
                 NOTIFICATION
            ================================================== -->

            <div class="notification-wrapper">


                <button
                    type="button"
                    id="notificationButton"
                    class="nav-action notification-nav-action"
                    title="Notifications"
                    aria-label="Notifications"
                    aria-expanded="false"
                >

                    <i class="bi bi-bell-fill"></i>


                    <?php if ($totalNotifications > 0): ?>

                        <span class="notification-badge">

                            <?php
                            echo $totalNotifications;
                            ?>

                        </span>

                    <?php endif; ?>

                </button>


                <!-- =================================================
                     NOTIFICATION POPUP
                ================================================== -->

                <div
                    id="notificationPopup"
                    class="notification-popup"
                    role="dialog"
                    aria-label="Notifications"
                >


                    <!-- POPUP HEADER -->

                    <div class="notification-popup-header">


                        <div class="notification-popup-title">

                            <i class="bi bi-bell-fill"></i>

                            <strong>
                                Notifications
                            </strong>

                        </div>


                        <span class="notification-count">

                            <?php
                            echo $totalNotifications;
                            ?>

                        </span>

                    </div>


                    <!-- POPUP BODY -->

                    <div class="notification-popup-body">


                        <?php if ($totalNotifications > 0): ?>


                            <?php foreach (
                                $notifications
                                as $notification
                            ): ?>


                                <div class="notification-item">


                                    <div class="notification-item-icon">

                                        <?php if (
                                            $notification['days_remaining'] == 0
                                        ): ?>

                                            <i class="bi bi-exclamation-circle-fill"></i>

                                        <?php else: ?>

                                            <i class="bi bi-clock-fill"></i>

                                        <?php endif; ?>

                                    </div>


                                    <div class="notification-item-content">


                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $notification['notification_title']
                                            );
                                            ?>

                                        </strong>


                                        <p>

                                            <b>
                                                <?php
                                                echo htmlspecialchars(
                                                    $notification['title']
                                                );
                                                ?>
                                            </b>


                                            <?php if (
                                                $notification['days_remaining'] == 0
                                            ): ?>

                                                is due today.

                                            <?php elseif (
                                                $notification['days_remaining'] == 1
                                            ): ?>

                                                is due tomorrow.

                                            <?php else: ?>

                                                is due in

                                                <?php
                                                echo (int)$notification[
                                                    'days_remaining'
                                                ];
                                                ?>

                                                days.

                                            <?php endif; ?>

                                        </p>


                                        <span class="due-date">

                                            <i class="bi bi-calendar-event"></i>

                                            Due:

                                            <?php
                                            echo htmlspecialchars(
                                                $notification['due_date']
                                            );
                                            ?>

                                        </span>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="no-notifications">

                                <i class="bi bi-bell-slash"></i>


                                <strong>
                                    No new notifications
                                </strong>


                                <span>
                                    You don't have any due-date reminders right now.
                                </span>

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- POPUP FOOTER -->

                    <div class="notification-popup-footer">

                        <a
                            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                        >
                            View My Books
                        </a>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 LIGHT / DARK MODE
            ================================================== -->

            <button
                type="button"
                id="darkModeToggle"
                class="theme-toggle-btn"
                title="Switch to Dark Mode"
                aria-label="Switch to Dark Mode"
            >

                <i class="bi bi-moon-fill"></i>

            </button>


            <!-- =================================================
                 DIVIDER
            ================================================== -->

            <div class="nav-separator"></div>


            <!-- =================================================
                 USER PROFILE
            ================================================== -->

            <div class="user-profile-pill">


                <div class="user-avatar">

                    <?php
                    echo htmlspecialchars($userInitial);
                    ?>

                </div>


                <div class="user-profile-name">

                    <strong>

                        <?php
                        echo htmlspecialchars($userName);
                        ?>

                    </strong>


                    <small>
                        Member
                    </small>

                </div>


            </div>


            <!-- =================================================
                 LOGOUT
            ================================================== -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="user-logout"
                title="Logout"
                aria-label="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>


        </div>

    </nav>


    <!-- =====================================================
         DASHBOARD
    ===================================================== -->

    <div class="dashboard-content">


        <!-- =================================================
             WELCOME
        ================================================== -->

        <div class="welcome-box">

            <h2>

                Welcome,

                <?php
                echo htmlspecialchars($userName);
                ?>

            </h2>


            <p>

                Explore books, check your issued books,
                and manage your library account.

            </p>

        </div>


        <!-- =================================================
             STAT CARDS
        ================================================== -->

        <div class="row g-4">


            <!-- Total Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">


                    <div class="stat-card-content">


                        <div>

                            <h6>
                                Total Books
                            </h6>


                            <h2>

                                <?php
                                echo $total_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon blue">

                            <i class="bi bi-book"></i>

                        </div>


                    </div>

                </div>

            </div>


            <!-- Issued Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">


                    <div class="stat-card-content">


                        <div>

                            <h6>
                                Currently Issued
                            </h6>


                            <h2>

                                <?php
                                echo $issued_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon green">

                            <i class="bi bi-journal-bookmark"></i>

                        </div>


                    </div>

                </div>

            </div>


            <!-- Returned Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">


                    <div class="stat-card-content">


                        <div>

                            <h6>
                                Returned Books
                            </h6>


                            <h2>

                                <?php
                                echo $returned_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon orange">

                            <i class="bi bi-clock-history"></i>

                        </div>


                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <h4 class="section-title">

            Quick Actions

        </h4>


        <div class="row g-4">


            <!-- Browse Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                    class="quick-card"
                >

                    <i class="bi bi-book"></i>


                    <h6>
                        Browse Books
                    </h6>


                    <p>
                        View all available books
                    </p>

                </a>

            </div>


            <!-- History Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/my_books/history.php"
                    class="quick-card"
                >

                    <i class="bi bi-clock-history"></i>


                    <h6>
                        History Book
                    </h6>


                    <p>
                        Find your History
                    </p>

                </a>

            </div>


            <!-- My Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                    class="quick-card"
                >

                    <i class="bi bi-journal-bookmark"></i>


                    <h6>
                        My Books
                    </h6>


                    <p>
                        View currently issued books
                    </p>

                </a>

            </div>


            <!-- My Profile -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/profile.php"
                    class="quick-card"
                >

                    <i class="bi bi-person"></i>


                    <h6>
                        My Profile
                    </h6>


                    <p>
                        Manage your account
                    </p>

                </a>

            </div>


        </div>


    </div>

</div>


<!-- =====================================================
     SIDEBAR SCRIPT
===================================================== -->

<script>

function toggleSidebar()
{
    const sidebar =
        document.querySelector(".user-sidebar");

    if (sidebar) {

        sidebar.classList.toggle("show");

    }
}


/* =====================================================
   NOTIFICATION POPUP
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const notificationButton =
            document.getElementById(
                "notificationButton"
            );

        const notificationPopup =
            document.getElementById(
                "notificationPopup"
            );


        if (
            !notificationButton ||
            !notificationPopup
        ) {

            return;

        }


        /* =============================================
           OPEN / CLOSE NOTIFICATION
        ============================================= */

        notificationButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                const isOpen =
                    notificationPopup.classList.contains(
                        "show"
                    );


                if (isOpen) {

                    notificationPopup.classList.remove(
                        "show"
                    );

                    notificationButton.classList.remove(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                } else {

                    notificationPopup.classList.add(
                        "show"
                    );

                    notificationButton.classList.add(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "true"
                    );

                }

            }
        );


        /* =============================================
           PREVENT POPUP CLICK FROM CLOSING IT
        ============================================= */

        notificationPopup.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );


        /* =============================================
           CLOSE WHEN CLICKING OUTSIDE
        ============================================= */

        document.addEventListener(
            "click",
            function () {

                notificationPopup.classList.remove(
                    "show"
                );

                notificationButton.classList.remove(
                    "active"
                );

                notificationButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }
        );


        /* =============================================
           CLOSE WITH ESCAPE KEY
        ============================================= */

        document.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape") {

                    notificationPopup.classList.remove(
                        "show"
                    );

                    notificationButton.classList.remove(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            }
        );

    }
);


/* =====================================================
   LIGHT / DARK MODE
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const body =
            document.body;

        const themeButton =
            document.getElementById(
                "darkModeToggle"
            );


        function updateThemeButton()
        {

            if (!themeButton) {

                return;

            }


            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );


            if (isDark) {

                themeButton.innerHTML =
                    '<i class="bi bi-sun-fill"></i>';

                themeButton.title =
                    "Switch to Light Mode";

                themeButton.setAttribute(
                    "aria-label",
                    "Switch to Light Mode"
                );

            } else {

                themeButton.innerHTML =
                    '<i class="bi bi-moon-fill"></i>';

                themeButton.title =
                    "Switch to Dark Mode";

                themeButton.setAttribute(
                    "aria-label",
                    "Switch to Dark Mode"
                );

            }

        }


        function applyTheme(theme)
        {

            const isDark =
                theme === "dark";


            body.classList.toggle(
                "library-dark-mode",
                isDark
            );


            document.documentElement.classList.toggle(
                "library-dark-mode",
                isDark
            );


            localStorage.setItem(
                "library_theme",
                isDark
                    ? "dark"
                    : "light"
            );


            updateThemeButton();

        }


        /* =============================================
           LOAD SAVED THEME
        ============================================= */

        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );


        if (savedTheme === "dark") {

            applyTheme("dark");

        } else {

            applyTheme("light");

        }


        /* =============================================
           TOGGLE THEME
        ============================================= */

        if (themeButton) {

            themeButton.addEventListener(
                "click",
                function () {

                    const isDark =
                        body.classList.contains(
                            "library-dark-mode"
                        );


                    applyTheme(
                        isDark
                            ? "light"
                            : "dark"
                    );

                }
            );

        }

    }
);

</script>


</body>

</html>