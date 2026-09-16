<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];


// =====================================================
// CURRENTLY ISSUED BOOKS
// =====================================================

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ? AND status = 'Issued'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$issued_books = $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


// =====================================================
// RETURNED BOOKS
// =====================================================

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ? AND status = 'Returned'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$returned_books = $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


// =====================================================
// TOTAL BOOKS
// =====================================================

$total_books = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
);

if ($result) {
    $total_books = $result->fetch_assoc()['total'];
}


// =====================================================
// DUE DATE REMINDER
// SHOW REMINDER EXACTLY 3 DAYS BEFORE DUE DATE
// =====================================================

$dueDateReminders = [];

$today = new DateTime(date('Y-m-d'));

$stmtReminder = $conn->prepare(
    "SELECT
        ib.id,
        ib.book_id,
        ib.return_date,
        b.title
     FROM issued_books ib
     INNER JOIN books b
        ON ib.book_id = b.id
     WHERE ib.user_id = ?
       AND ib.status = 'Issued'
     ORDER BY ib.return_date ASC"
);

if ($stmtReminder) {

    $stmtReminder->bind_param("i", $user_id);
    $stmtReminder->execute();

    $reminderResult = $stmtReminder->get_result();

    while ($row = $reminderResult->fetch_assoc()) {

        $dueDate = new DateTime(
            date(
                'Y-m-d',
                strtotime($row['return_date'])
            )
        );

        $daysRemaining = (int) $today
            ->diff($dueDate)
            ->format('%r%a');


        // =================================================
        // EXACTLY 3 DAYS BEFORE DUE DATE
        // =================================================

        if ($daysRemaining === 3) {

            $dueDateReminders[] = [

                'id' => $row['id'],

                'title' => $row['title'],

                'due_date' => $dueDate->format('d M Y'),

                'days_remaining' => $daysRemaining

            ];
        }
    }

    $stmtReminder->close();
}


$totalDueDateReminders = count($dueDateReminders);

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
                localStorage.getItem('library_theme');

            if (savedTheme === 'dark') {

                document.documentElement.classList.add(
                    'library-dark-mode'
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
                rgba(34,197,94,.10);

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

        .notification-nav-action {

            position: relative;

        }


        .notification-badge {

            position: absolute;

            top: -5px;

            right: -5px;

            min-width: 18px;

            height: 18px;

            padding: 0 5px;

            border-radius: 50px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ef4444;

            color: #ffffff;

            border: 2px solid #ffffff;

            font-size: 8px;

            font-weight: 800;

            line-height: 1;

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
           DUE DATE REMINDER
        ===================================================== */

        .due-reminder-card {

            background: #ffffff;

            border: 1px solid #dbe5f1;

            border-radius: 14px;

            padding: 18px 20px;

            margin-top: 22px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.06);

            transition:
                background .25s ease,
                border-color .25s ease,
                box-shadow .25s ease;

        }


        .due-reminder-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 14px;

        }


        .due-reminder-title {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .due-reminder-icon {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 18px;

        }


        .due-reminder-title h5 {

            margin: 0;

            color: #172033;

            font-size: 16px;

            font-weight: 800;

        }


        .due-reminder-title p {

            margin: 3px 0 0;

            color: #718096;

            font-size: 11px;

        }


        .due-reminder-badge {

            min-width: 28px;

            height: 28px;

            padding: 0 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50px;

            background: #2563eb;

            color: #ffffff;

            font-size: 11px;

            font-weight: 800;

        }


        .due-reminder-list {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .due-reminder-item {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 11px 12px;

            border-radius: 9px;

            background: #f8fbff;

            border: 1px solid #e5edf7;

        }


        .reminder-book {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 0;

        }


        .reminder-book > i {

            color: #2563eb;

            font-size: 16px;

            flex-shrink: 0;

        }


        .reminder-book strong {

            display: block;

            color: #1e293b;

            font-size: 12px;

            font-weight: 700;

            max-width: 450px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .reminder-book small {

            display: block;

            margin-top: 2px;

            color: #94a3b8;

            font-size: 10px;

        }


        .reminder-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 20px;

            background: #fff7ed;

            color: #d97706;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

        }


        .reminder-status i {

            font-size: 8px;

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
           DARK MODE - REMINDER
        ===================================================== */

        body.library-dark-mode .due-reminder-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.25);

        }


        body.library-dark-mode .due-reminder-title h5 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .due-reminder-title p {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .due-reminder-icon {

            background: #172554 !important;

            color: #60a5fa !important;

        }


        body.library-dark-mode .due-reminder-item {

            background: #172033 !important;

            border-color: #334155 !important;

        }


        body.library-dark-mode .reminder-book strong {

            color: #f1f5f9 !important;

        }


        body.library-dark-mode .reminder-book small {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .reminder-status {

            background: #422006 !important;

            color: #fbbf24 !important;

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


            .due-reminder-card {

                padding: 15px;

            }


            .due-reminder-item {

                align-items: flex-start;

                flex-direction: column;

            }


            .reminder-status {

                margin-left: 26px;

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


        <!-- LEFT -->

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

                    echo htmlspecialchars(
                        $_SESSION['user_name'] ?? 'User'
                    );

                    ?>

                </h5>

            </div>

        </div>


        <!-- CENTER -->

        <div class="user-nav-center">

            <div class="library-status">

                <span class="status-circle"></span>

                <span>
                    Library is Open
                </span>

            </div>

        </div>


        <!-- RIGHT -->

        <div class="user-nav-right">


            <!-- Favorite Books -->

            <a
                href="<?php echo BASE_URL; ?>/user/favorites/index.php"
                class="nav-action favorite-nav-action"
                title="Favorite Books"
                aria-label="Favorite Books"
            >

                <i class="bi bi-heart"></i>

            </a>


            <!-- Notification -->

            <a
                href="#dueDateReminder"
                class="nav-action notification-nav-action"
                title="Due Date Notifications"
                aria-label="Due Date Notifications"
            >

                <i class="bi bi-bell-fill"></i>


                <?php if ($totalDueDateReminders > 0): ?>

                    <span class="notification-badge">

                        <?php
                        echo $totalDueDateReminders;
                        ?>

                    </span>

                <?php endif; ?>

            </a>


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


            <!-- Divider -->

            <div class="nav-separator"></div>


            <!-- User -->

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
        ================================================= -->

        <div class="welcome-box">

            <h2>

                Welcome,

                <?php

                echo htmlspecialchars(
                    $_SESSION['user_name'] ?? 'User'
                );

                ?>

            </h2>


            <p>

                Explore books, check your issued books,
                and manage your library account.

            </p>

        </div>


        <!-- =================================================
             DUE DATE REMINDER
             EXACTLY 3 DAYS BEFORE DUE DATE
        ================================================= -->

        <?php if ($totalDueDateReminders > 0): ?>

            <div
                class="due-reminder-card"
                id="dueDateReminder"
            >


                <div class="due-reminder-header">


                    <div class="due-reminder-title">


                        <div class="due-reminder-icon">

                            <i class="bi bi-bell-fill"></i>

                        </div>


                        <div>

                            <h5>
                                Due Date Reminder
                            </h5>


                            <p>

                                You have a book due in
                                3 days.

                            </p>

                        </div>


                    </div>


                    <span class="due-reminder-badge">

                        <?php

                        echo $totalDueDateReminders;

                        ?>

                    </span>


                </div>


                <div class="due-reminder-list">


                    <?php foreach (
                        $dueDateReminders
                        as $reminder
                    ): ?>


                        <div class="due-reminder-item">


                            <div class="reminder-book">


                                <i class="bi bi-book"></i>


                                <div>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $reminder['title']
                                        );

                                        ?>

                                    </strong>


                                    <small>

                                        Due:

                                        <?php

                                        echo htmlspecialchars(
                                            $reminder['due_date']
                                        );

                                        ?>

                                    </small>


                                </div>


                            </div>


                            <span class="reminder-status">

                                <i class="bi bi-clock-fill"></i>

                                3 Days Left

                            </span>


                        </div>


                    <?php endforeach; ?>


                </div>


            </div>

        <?php endif; ?>


        <!-- =================================================
             STAT CARDS
        ================================================= -->

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
        ================================================= -->

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


            <!-- Search Books -->

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
        document.querySelector('.user-sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }
}


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

            if (theme === "dark") {

                body.classList.add(
                    "library-dark-mode"
                );

                document.documentElement.classList.add(
                    "library-dark-mode"
                );

            } else {

                body.classList.remove(
                    "library-dark-mode"
                );

                document.documentElement.classList.remove(
                    "library-dark-mode"
                );

            }


            localStorage.setItem(
                "library_theme",
                theme
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


                    if (isDark) {

                        applyTheme("light");

                    } else {

                        applyTheme("dark");

                    }

                }
            );

        }

    }
);


/* =====================================================
   NOTIFICATION SCROLL
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const notification =
            document.querySelector(
                ".notification-nav-action"
            );


        if (!notification) {
            return;
        }


        notification.addEventListener(
            "click",
            function (event) {

                const reminder =
                    document.getElementById(
                        "dueDateReminder"
                    );


                if (reminder) {

                    event.preventDefault();


                    reminder.scrollIntoView({

                        behavior: "smooth",

                        block: "center"

                    });

                }

            }
        );

    }
);

</script>


</body>

</html>