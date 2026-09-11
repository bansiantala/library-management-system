<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];

// Get user
$stmt = $conn->prepare(
    "SELECT *
     FROM users
     WHERE id = ?
     AND role = 'user'"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();


// Get issued books
$bookStmt = $conn->prepare(
    "SELECT
        issued_books.*,
        books.title
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     WHERE issued_books.user_id = ?
     ORDER BY issued_books.id DESC"
);

$bookStmt->bind_param("i", $id);
$bookStmt->execute();

$booksResult = $bookStmt->get_result();


// Statistics
$totalBooks = $booksResult->num_rows;

$issuedCount = 0;
$returnedCount = 0;
$totalFine = 0;

$bookHistory = [];

while ($bookRow = $booksResult->fetch_assoc()) {

    $bookHistory[] = $bookRow;

    if ($bookRow['status'] === 'Issued') {
        $issuedCount++;
    }

    if ($bookRow['status'] === 'Returned') {
        $returnedCount++;
    }

    $totalFine += (float) $bookRow['fine'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>User Details | Library Management System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css">

    <!-- Admin CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css">


    <style>

        /* =====================================================
           USER DETAILS - LARGE PROFESSIONAL DESIGN
        ===================================================== */

        .user-details-page {
            padding: 30px 35px 50px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
        }

        .page-heading h2 {
            margin: 0;
            color: #172033;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
        }

        .page-heading p {
            margin: 9px 0 0;
            color: #737d91;
            font-size: 15px;
        }

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 12px;
            color: #8b94a7;
            font-size: 13px;
        }

        .breadcrumb-custom i {
            font-size: 10px;
        }

        .breadcrumb-custom .current {
            color: #4f46e5;
            font-weight: 700;
        }


        /* =====================================================
           PAGE BUTTONS
        ===================================================== */

        .page-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-btn,
        .edit-btn {
            min-height: 46px;
            padding: 0 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .back-btn {
            color: #4b5563;
            background: #ffffff;
            border: 1px solid #dfe3eb;
        }

        .back-btn:hover {
            color: #4f46e5;
            background: #f7f8ff;
            border-color: #c7d2fe;
            transform: translateY(-1px);
        }

        .edit-btn {
            color: #ffffff;
            background: linear-gradient(
                135deg,
                #4f46e5,
                #6366f1
            );
            border: 1px solid transparent;
        }

        .edit-btn:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {
            width: 100%;
            margin-bottom: 28px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e5e8ef;
            border-radius: 18px;
            box-shadow: 0 7px 25px rgba(30, 41, 59, 0.07);
        }


        .profile-cover {
            height: 150px;
            background: linear-gradient(
                135deg,
                #3730a3,
                #4f46e5,
                #6366f1,
                #818cf8
            );
            position: relative;
        }


        .profile-cover::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: 5%;
            top: -100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }


        .profile-body {
            padding: 0 35px 32px;
        }


        .profile-main {
            display: flex;
            align-items: flex-end;
            gap: 22px;
            position: relative;
            margin-top: -48px;
        }


        .profile-avatar {
            width: 100px;
            height: 100px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;
            border: 6px solid #ffffff;

            background: #ffffff;
            color: #4f46e5;

            font-size: 36px;
            font-weight: 800;

            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.15);
        }


        .profile-name {
            padding-bottom: 10px;
        }


        .profile-name h3 {
            margin: 0;
            color: #172033;
            font-size: 25px;
            font-weight: 800;
        }


        .profile-name p {
            margin: 5px 0 0;
            color: #7f899c;
            font-size: 14px;
        }


        /* =====================================================
           PROFILE DETAILS
        ===================================================== */

        .profile-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);

            margin-top: 30px;
            padding-top: 25px;

            border-top: 1px solid #edf0f5;
        }


        .profile-detail {
            display: flex;
            align-items: center;
            gap: 14px;

            min-height: 60px;
            padding: 0 24px;

            border-right: 1px solid #edf0f5;
        }


        .profile-detail:first-child {
            padding-left: 0;
        }


        .profile-detail:last-child {
            border-right: none;
        }


        .detail-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: #f0f2ff;
            color: #4f46e5;

            font-size: 19px;
        }


        .detail-content small {
            display: block;

            margin-bottom: 4px;

            color: #929bad;

            font-size: 11px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.6px;
        }


        .detail-content strong {
            display: block;

            color: #303a4d;

            font-size: 14px;
            font-weight: 700;

            word-break: break-word;
        }


        /* =====================================================
           STATISTICS
        ===================================================== */

        .user-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;

            margin-bottom: 28px;
        }


        .stat-card {
            min-height: 105px;

            display: flex;
            align-items: center;

            gap: 18px;

            padding: 23px;

            background: #ffffff;

            border: 1px solid #e5e8ef;
            border-radius: 15px;

            box-shadow: 0 6px 20px rgba(30, 41, 59, 0.055);

            transition: all 0.25s ease;
        }


        .stat-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(30, 41, 59, 0.09);
        }


        .stat-icon {
            width: 55px;
            height: 55px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: #f0f2ff;
            color: #4f46e5;

            font-size: 23px;
        }


        .stat-info span {
            display: block;

            color: #7f899c;

            font-size: 13px;
            font-weight: 600;
        }


        .stat-info strong {
            display: block;

            margin-top: 3px;

            color: #172033;

            font-size: 27px;
            font-weight: 800;
        }


        /* =====================================================
           HISTORY CARD
        ===================================================== */

        .history-card {
            width: 100%;
            overflow: hidden;

            background: #ffffff;

            border: 1px solid #e5e8ef;
            border-radius: 18px;

            box-shadow: 0 7px 25px rgba(30, 41, 59, 0.07);
        }


        .history-header {
            min-height: 90px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 20px 28px;

            border-bottom: 1px solid #edf0f5;
        }


        .history-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .history-title-icon {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: #eef2ff;
            color: #4f46e5;

            font-size: 21px;
        }


        .history-title h4 {
            margin: 0;

            color: #172033;

            font-size: 20px;
            font-weight: 800;
        }


        .history-title p {
            margin: 4px 0 0;

            color: #8b94a7;

            font-size: 13px;
        }


        .history-count {
            padding: 8px 14px;

            border-radius: 20px;

            background: #eef2ff;
            color: #4f46e5;

            font-size: 12px;
            font-weight: 800;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .history-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }


        .history-table {
            width: 100%;
            min-width: 1050px;

            margin: 0;

            border-collapse: collapse;
        }


        .history-table thead th {
            height: 55px;

            padding: 0 22px;

            background: #f8f9fc;

            color: #707a8d;

            border-bottom: 1px solid #e6eaf0;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.5px;

            white-space: nowrap;
        }


        .history-table tbody td {
            height: 72px;

            padding: 10px 22px;

            color: #4b5563;

            border-bottom: 1px solid #eef0f4;

            font-size: 14px;

            vertical-align: middle;
        }


        .history-table tbody tr:last-child td {
            border-bottom: none;
        }


        .history-table tbody tr {
            transition: background 0.2s ease;
        }


        .history-table tbody tr:hover {
            background: #fafbff;
        }


        /* =====================================================
           NUMBER
        ===================================================== */

        .row-number {
            color: #8c95a7;

            font-size: 13px;
            font-weight: 700;
        }


        /* =====================================================
           BOOK
        ===================================================== */

        .book-cell {
            display: flex;
            align-items: center;
            gap: 13px;

            min-width: 230px;
        }


        .book-icon {
            width: 45px;
            height: 45px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #fff4e8;
            color: #ea580c;

            font-size: 19px;
        }


        .book-title {
            color: #303a4d;

            font-size: 14px;
            font-weight: 700;

            line-height: 1.4;
        }


        /* =====================================================
           DATE
        ===================================================== */

        .date-cell {
            color: #596376;

            font-size: 13px;
            font-weight: 600;

            white-space: nowrap;
        }


        /* =====================================================
           FINE
        ===================================================== */

        .fine-amount {
            color: #dc2626;

            font-size: 14px;
            font-weight: 800;

            white-space: nowrap;
        }


        .no-fine {
            color: #059669;

            font-size: 14px;
            font-weight: 800;

            white-space: nowrap;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 20px;

            font-size: 11px;
            font-weight: 800;

            white-space: nowrap;
        }


        .status-issued {
            background: #fff4e5;
            color: #c2410c;
        }


        .status-returned {
            background: #eafaf3;
            color: #047857;
        }


        .status-badge i {
            font-size: 7px;
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-history {
            padding: 80px 25px;

            text-align: center;
        }


        .empty-icon {
            width: 85px;
            height: 85px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f1f3ff;
            color: #818cf8;

            font-size: 35px;
        }


        .empty-history h5 {
            margin: 0;

            color: #303a4d;

            font-size: 19px;
            font-weight: 800;
        }


        .empty-history p {
            margin: 8px 0 0;

            color: #929bad;

            font-size: 14px;
        }


        /* =====================================================
           LARGE SCREEN
        ===================================================== */

        @media (min-width: 1400px) {

            .user-details-page {
                padding-left: 45px;
                padding-right: 45px;
            }

            .page-heading h2 {
                font-size: 34px;
            }

            .profile-cover {
                height: 165px;
            }

            .profile-avatar {
                width: 110px;
                height: 110px;
                font-size: 40px;
            }

            .profile-name h3 {
                font-size: 27px;
            }

            .history-table thead th {
                font-size: 12px;
            }

            .history-table tbody td {
                font-size: 14px;
            }

        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            .profile-details {
                grid-template-columns: repeat(2, 1fr);
                row-gap: 20px;
            }


            .profile-detail:nth-child(2) {
                border-right: none;
            }


            .profile-detail:nth-child(3) {
                padding-left: 0;
            }


            .user-stats {
                gap: 16px;
            }

        }


        /* =====================================================
           900 PX
        ===================================================== */

        @media (max-width: 900px) {

            .user-details-page {
                padding: 25px 20px 40px;
            }


            .page-header-section {
                align-items: flex-start;
                flex-direction: column;
            }


            .page-actions {
                width: 100%;
            }


            .back-btn,
            .edit-btn {
                flex: 1;
            }


            .user-stats {
                grid-template-columns: 1fr;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .user-details-page {
                padding: 20px 15px 35px;
            }


            .page-heading h2 {
                font-size: 27px;
            }


            .page-heading p {
                font-size: 14px;
            }


            .profile-cover {
                height: 115px;
            }


            .profile-body {
                padding: 0 20px 25px;
            }


            .profile-main {
                align-items: center;
                gap: 15px;
            }


            .profile-avatar {
                width: 82px;
                height: 82px;
                font-size: 30px;
            }


            .profile-name h3 {
                font-size: 21px;
            }


            .profile-details {
                grid-template-columns: 1fr;

                gap: 0;

                margin-top: 25px;
                padding-top: 20px;
            }


            .profile-detail,
            .profile-detail:nth-child(3) {
                min-height: 65px;

                padding: 10px 0;

                border-right: none;
                border-bottom: 1px solid #edf0f5;
            }


            .profile-detail:last-child {
                border-bottom: none;
            }


            .stat-card {
                min-height: 95px;
            }


            .history-header {
                padding: 18px 20px;
            }


            .history-title h4 {
                font-size: 17px;
            }


            .history-title p {
                font-size: 12px;
            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .page-actions {
                flex-direction: column;
            }


            .back-btn,
            .edit-btn {
                width: 100%;
            }


            .profile-cover {
                height: 100px;
            }


            .profile-main {
                margin-top: -40px;
            }


            .profile-avatar {
                width: 75px;
                height: 75px;

                border-width: 5px;

                font-size: 27px;
            }


            .profile-name h3 {
                font-size: 19px;
            }


            .profile-name p {
                font-size: 12px;
            }


            .profile-body {
                padding-left: 16px;
                padding-right: 16px;
            }


            .stat-card {
                padding: 18px;
            }


            .stat-icon {
                width: 48px;
                height: 48px;

                font-size: 20px;
            }


            .stat-info strong {
                font-size: 24px;
            }


            .history-header {
                min-height: 75px;
            }


            .history-count {
                display: none;
            }


            .history-title-icon {
                width: 40px;
                height: 40px;
            }


            .empty-history {
                padding: 60px 15px;
            }

        }


        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            .admin-sidebar,
            .admin-navbar,
            .page-actions {
                display: none !important;
            }


            .admin-main {
                margin-left: 0 !important;
            }


            .user-details-page {
                padding: 20px !important;
            }


            .profile-card,
            .history-card,
            .stat-card {
                box-shadow: none !important;
            }

        }

    </style>

</head>


<body>


<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

    <nav class="admin-navbar">


        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    User Details / Admin Dashboard
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Users

                    <i class="bi bi-chevron-right"></i>

                    User Details

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <button
                type="button"
                class="notification-btn"
                title="Notifications">

                <i class="bi bi-bell"></i>

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
                            $_SESSION['user_name'] ?? 'Admin'
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
                class="admin-logout-btn">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>


        </div>


    </nav>


    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <div class="admin-content user-details-page">


        <!-- PAGE HEADER -->

        <div class="page-header-section">


            <div class="page-heading">


                <h2>
                    User Details
                </h2>


                <p>
                    View complete user information and borrowing history.
                </p>


                <div class="breadcrumb-custom">


                    <span>

                        <i class="bi bi-house-door"></i>

                        Home

                    </span>


                    <i class="bi bi-chevron-right"></i>


                    <span>
                        Users
                    </span>


                    <i class="bi bi-chevron-right"></i>


                    <span class="current">
                        User Details
                    </span>


                </div>


            </div>


            <div class="page-actions">


                <a
                    href="index.php"
                    class="back-btn">

                    <i class="bi bi-arrow-left"></i>

                    Back to Users

                </a>


                <a
                    href="edit.php?id=<?php echo $user['id']; ?>"
                    class="edit-btn">

                    <i class="bi bi-pencil-square"></i>

                    Edit User

                </a>


            </div>


        </div>


        <!-- =================================================
             PROFILE CARD
        ================================================== -->

        <div class="profile-card">


            <div class="profile-cover"></div>


            <div class="profile-body">


                <div class="profile-main">


                    <div class="profile-avatar">

                        <?php

                        echo strtoupper(
                            substr(
                                $user['name'],
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <div class="profile-name">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $user['name']
                            );

                            ?>

                        </h3>


                        <p>
                            Library User Account
                        </p>


                    </div>


                </div>


                <!-- PROFILE INFORMATION -->

                <div class="profile-details">


                    <!-- USER ID -->

                    <div class="profile-detail">


                        <div class="detail-icon">

                            <i class="bi bi-hash"></i>

                        </div>


                        <div class="detail-content">

                            <small>
                                User ID
                            </small>

                            <strong>
                                #<?php echo $user['id']; ?>
                            </strong>

                        </div>


                    </div>


                    <!-- EMAIL -->

                    <div class="profile-detail">


                        <div class="detail-icon">

                            <i class="bi bi-envelope"></i>

                        </div>


                        <div class="detail-content">

                            <small>
                                Email Address
                            </small>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $user['email']
                                );

                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- PHONE -->

                    <div class="profile-detail">


                        <div class="detail-icon">

                            <i class="bi bi-telephone"></i>

                        </div>


                        <div class="detail-content">

                            <small>
                                Phone Number
                            </small>

                            <strong>

                                <?php

                                echo !empty($user['phone'])
                                    ? htmlspecialchars($user['phone'])
                                    : 'Not provided';

                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- REGISTERED -->

                    <div class="profile-detail">


                        <div class="detail-icon">

                            <i class="bi bi-calendar-check"></i>

                        </div>


                        <div class="detail-content">

                            <small>
                                Registered Date
                            </small>

                            <strong>

                                <?php

                                echo date(
                                    "d-m-Y",
                                    strtotime(
                                        $user['created_at']
                                    )
                                );

                                ?>

                            </strong>

                        </div>


                    </div>


                </div>


            </div>


        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="user-stats">


            <!-- TOTAL -->

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-journal-bookmark"></i>

                </div>


                <div class="stat-info">

                    <span>
                        Total Book Records
                    </span>

                    <strong>
                        <?php echo $totalBooks; ?>
                    </strong>

                </div>


            </div>


            <!-- ISSUED -->

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-book-half"></i>

                </div>


                <div class="stat-info">

                    <span>
                        Currently Issued
                    </span>

                    <strong>
                        <?php echo $issuedCount; ?>
                    </strong>

                </div>


            </div>


            <!-- RETURNED -->

            <div class="stat-card">


                <div class="stat-icon">

                    <i class="bi bi-check2-circle"></i>

                </div>


                <div class="stat-info">

                    <span>
                        Returned Books
                    </span>

                    <strong>
                        <?php echo $returnedCount; ?>
                    </strong>

                </div>


            </div>


        </div>


        <!-- =================================================
             BOOK HISTORY
        ================================================== -->

        <div class="history-card">


            <div class="history-header">


                <div class="history-title">


                    <div class="history-title-icon">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <div>


                        <h4>
                            Book Issue History
                        </h4>


                        <p>
                            Complete borrowing and return records
                        </p>


                    </div>


                </div>


                <div class="history-count">

                    <?php echo $totalBooks; ?>

                    Records

                </div>


            </div>


            <?php if ($totalBooks > 0) { ?>


                <div class="history-table-wrapper">


                    <table class="history-table">


                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Book
                                </th>

                                <th>
                                    Issue Date
                                </th>

                                <th>
                                    Return Date
                                </th>

                                <th>
                                    Actual Return
                                </th>

                                <th>
                                    Fine
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        $count = 1;

                        foreach ($bookHistory as $book) {

                        ?>


                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <span class="row-number">

                                        <?php

                                        echo $count++;

                                        ?>

                                    </span>

                                </td>


                                <!-- BOOK -->

                                <td>


                                    <div class="book-cell">


                                        <div class="book-icon">

                                            <i class="bi bi-book"></i>

                                        </div>


                                        <div class="book-title">

                                            <?php

                                            echo htmlspecialchars(
                                                $book['title']
                                            );

                                            ?>

                                        </div>


                                    </div>


                                </td>


                                <!-- ISSUE DATE -->

                                <td>

                                    <span class="date-cell">

                                        <?php

                                        echo date(
                                            "d-m-Y",
                                            strtotime(
                                                $book['issue_date']
                                            )
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- RETURN DATE -->

                                <td>

                                    <span class="date-cell">

                                        <?php

                                        echo date(
                                            "d-m-Y",
                                            strtotime(
                                                $book['return_date']
                                            )
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- ACTUAL RETURN -->

                                <td>

                                    <span class="date-cell">

                                        <?php

                                        if (
                                            $book['actual_return_date']
                                        ) {

                                            echo date(
                                                "d-m-Y",
                                                strtotime(
                                                    $book[
                                                        'actual_return_date'
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo "-";

                                        }

                                        ?>

                                    </span>

                                </td>


                                <!-- FINE -->

                                <td>


                                    <?php

                                    if (
                                        (float) $book['fine'] > 0
                                    ) {

                                    ?>

                                        <span class="fine-amount">

                                            ₹<?php

                                            echo number_format(
                                                $book['fine'],
                                                2
                                            );

                                            ?>

                                        </span>

                                    <?php

                                    } else {

                                    ?>

                                        <span class="no-fine">

                                            ₹0.00

                                        </span>

                                    <?php

                                    }

                                    ?>


                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php

                                    if (
                                        $book['status'] === 'Issued'
                                    ) {

                                    ?>

                                        <span
                                            class="status-badge status-issued">

                                            <i
                                                class="bi bi-circle-fill">
                                            </i>

                                            Issued

                                        </span>

                                    <?php

                                    } else {

                                    ?>

                                        <span
                                            class="status-badge status-returned">

                                            <i
                                                class="bi bi-check-circle-fill">
                                            </i>

                                            Returned

                                        </span>

                                    <?php

                                    }

                                    ?>


                                </td>


                            </tr>


                        <?php

                        }

                        ?>


                        </tbody>


                    </table>


                </div>


            <?php } else { ?>


                <!-- EMPTY -->

                <div class="empty-history">


                    <div class="empty-icon">

                        <i class="bi bi-journal-x"></i>

                    </div>


                    <h5>
                        No Book History Found
                    </h5>


                    <p>
                        This user has not borrowed any books yet.
                    </p>


                </div>


            <?php } ?>


        </div>


    </div>


</div>


</body>

</html>