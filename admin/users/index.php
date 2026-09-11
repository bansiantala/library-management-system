<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| Get All Normal Users
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            users.id,
            users.name,
            users.email,
            users.phone,
            users.created_at,
            COUNT(issued_books.id) AS issued_count
        FROM users
        LEFT JOIN issued_books
            ON users.id = issued_books.user_id
            AND issued_books.status = 'Issued'
        WHERE users.role = 'user'
        GROUP BY users.id
        ORDER BY users.id DESC";

$result = $conn->query($sql);


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$activeBorrowers = 0;
$totalIssuedBooks = 0;

if ($result && $result->num_rows > 0) {

    $totalUsers = $result->num_rows;

    while ($statUser = $result->fetch_assoc()) {

        if ((int)$statUser['issued_count'] > 0) {
            $activeBorrowers++;
        }

        $totalIssuedBooks += (int)$statUser['issued_count'];
    }

    // Reset result pointer for table
    $result->data_seek(0);
}


/*
|--------------------------------------------------------------------------
| Additional Statistics
|--------------------------------------------------------------------------
*/

$todayUsers = 0;

$todayResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'
     AND DATE(created_at) = CURDATE()"
);

if ($todayResult) {

    $todayData = $todayResult->fetch_assoc();

    $todayUsers = (int)$todayData['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Manage Users | Admin Dashboard</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css">


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css">


    <style>


        /* =========================================================
           USERS PAGE
        ========================================================= */

        .users-page {

            padding: 30px;

        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .users-page-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 27px;

        }


        .users-page-title h2 {

            margin: 0 0 7px;

            color: #182230;

            font-size: 28px;

            font-weight: 800;

        }


        .users-page-title p {

            margin: 0;

            color: #7b8794;

            font-size: 14px;

        }


        /* =========================================================
           USER ICON HEADER
        ========================================================= */

        .users-header-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 22px;

        }


        /* =========================================================
           STAT CARDS
        ========================================================= */

        .users-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }


        .user-stat-card {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 14px;

            padding: 20px;

            display: flex;

            align-items: center;

            gap: 14px;

            box-shadow:
                0 4px 18px
                rgba(15, 23, 42, 0.05);

            transition: all 0.25s ease;

        }


        .user-stat-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 25px
                rgba(15, 23, 42, 0.08);

        }


        .user-stat-icon {

            width: 50px;

            height: 50px;

            min-width: 50px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

        }


        .user-stat-icon.blue {

            background: #eff6ff;

            color: #2563eb;

        }


        .user-stat-icon.green {

            background: #ecfdf5;

            color: #059669;

        }


        .user-stat-icon.orange {

            background: #fff7ed;

            color: #ea580c;

        }


        .user-stat-icon.purple {

            background: #f5f3ff;

            color: #7c3aed;

        }


        .user-stat-content small {

            display: block;

            margin-bottom: 4px;

            color: #8a96a3;

            font-size: 12px;

            font-weight: 600;

        }


        .user-stat-content strong {

            display: block;

            color: #1f2937;

            font-size: 23px;

            line-height: 1;

        }


        /* =========================================================
           USERS TABLE CARD
        ========================================================= */

        .users-table-card {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 4px 18px
                rgba(15, 23, 42, 0.05);

        }


        /* =========================================================
           TABLE HEADER
        ========================================================= */

        .users-table-header {

            padding: 20px 22px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            border-bottom: 1px solid #edf0f4;

        }


        .users-table-heading {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .users-table-heading-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 19px;

        }


        .users-table-heading h5 {

            margin: 0;

            color: #1f2937;

            font-size: 16px;

            font-weight: 800;

        }


        .users-table-heading span {

            display: block;

            margin-top: 3px;

            color: #8a96a3;

            font-size: 12px;

        }


        .users-record-count {

            background: #f1f5f9;

            color: #475569;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        /* =========================================================
           TABLE
        ========================================================= */

        .users-table-wrapper {

            overflow-x: auto;

        }


        .users-table {

            width: 100%;

            min-width: 950px;

            margin: 0;

            border-collapse: collapse;

        }


        .users-table thead th {

            padding: 14px 16px;

            background: #f8fafc;

            color: #64748b;

            border-bottom: 1px solid #e8edf3;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            white-space: nowrap;

        }


        .users-table tbody td {

            padding: 15px 16px;

            border-bottom: 1px solid #f0f2f5;

            color: #334155;

            font-size: 13px;

            vertical-align: middle;

        }


        .users-table tbody tr:last-child td {

            border-bottom: none;

        }


        .users-table tbody tr {

            transition: background 0.2s ease;

        }


        .users-table tbody tr:hover {

            background: #fafcff;

        }


        /* =========================================================
           NUMBER
        ========================================================= */

        .user-number {

            width: 35px;

            height: 35px;

            border-radius: 9px;

            background: #f1f5f9;

            color: #475569;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 800;

        }


        /* =========================================================
           USER PROFILE
        ========================================================= */

        .user-profile {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 170px;

        }


        .user-avatar {

            width: 40px;

            height: 40px;

            min-width: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color: #ffffff;

            font-size: 14px;

            font-weight: 800;

        }


        .user-profile-info strong {

            display: block;

            color: #1f2937;

            font-size: 13px;

            font-weight: 750;

        }


        .user-profile-info span {

            display: block;

            margin-top: 2px;

            color: #94a3b8;

            font-size: 11px;

        }


        /* =========================================================
           EMAIL
        ========================================================= */

        .user-email {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #475569;

            font-size: 12px;

        }


        .user-email i {

            color: #94a3b8;

        }


        /* =========================================================
           PHONE
        ========================================================= */

        .user-phone {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #475569;

            font-size: 12px;

            white-space: nowrap;

        }


        .user-phone i {

            color: #94a3b8;

        }


        /* =========================================================
           ISSUED BOOK COUNT
        ========================================================= */

        .issued-count {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 750;

        }


        .issued-count.active {

            background: #fff7ed;

            color: #c2410c;

        }


        .issued-count.zero {

            background: #f1f5f9;

            color: #64748b;

        }


        /* =========================================================
           REGISTERED DATE
        ========================================================= */

        .registered-date {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color: #64748b;

            font-size: 12px;

            white-space: nowrap;

        }


        .registered-date i {

            color: #94a3b8;

        }


        /* =========================================================
           ACTION BUTTONS
        ========================================================= */

        .user-actions {

            display: flex;

            align-items: center;

            gap: 6px;

            white-space: nowrap;

        }


        .user-action-btn {

            width: 34px;

            height: 34px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            border: 1px solid transparent;

            font-size: 14px;

            transition: all 0.2s ease;

        }


        /* View */

        .user-view-btn {

            background: #eff6ff;

            color: #2563eb;

            border-color: #dbeafe;

        }


        .user-view-btn:hover {

            background: #2563eb;

            color: #ffffff;

        }


        /* Edit */

        .user-edit-btn {

            background: #fff7ed;

            color: #ea580c;

            border-color: #fed7aa;

        }


        .user-edit-btn:hover {

            background: #ea580c;

            color: #ffffff;

        }


        /* Delete */

        .user-delete-btn {

            background: #fef2f2;

            color: #dc2626;

            border-color: #fecaca;

        }


        .user-delete-btn:hover {

            background: #dc2626;

            color: #ffffff;

        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .users-empty {

            text-align: center;

            padding: 70px 20px;

        }


        .users-empty-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 15px;

            border-radius: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 30px;

        }


        .users-empty h5 {

            margin: 0 0 6px;

            color: #334155;

            font-size: 16px;

            font-weight: 750;

        }


        .users-empty p {

            margin: 0;

            color: #94a3b8;

            font-size: 13px;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {

            .users-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 992px) {

            .users-page {

                padding: 25px 20px;

            }

        }


        @media (max-width: 768px) {

            .users-page {

                padding: 20px 15px;

            }


            .users-page-header {

                flex-direction: column;

            }


            .users-header-icon {

                display: none;

            }


            .users-table-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .users-record-count {

                align-self: flex-start;

            }

        }


        @media (max-width: 576px) {

            .users-stats {

                grid-template-columns: 1fr;

            }


            .users-page-title h2 {

                font-size: 23px;

            }


            .user-stat-card {

                padding: 17px;

            }

        }


        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            .admin-sidebar,
            .admin-navbar,
            .user-actions {

                display: none !important;

            }


            .admin-main {

                margin-left: 0 !important;

            }


            .users-page {

                padding: 10px;

            }


            .users-table-card {

                box-shadow: none;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Manage Users / Admin Dashboard
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Users

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <!-- Notification -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications">

                <i class="bi bi-bell"></i>

                <?php if ($activeBorrowers > 0): ?>

                    <span class="notification-dot"></span>

                <?php endif; ?>

            </button>


            <div class="header-divider"></div>


            <!-- Admin -->

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


            <!-- Logout -->

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

    <main class="users-page">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="users-page-header">

            <div>

                <div
                    class="d-flex align-items-center gap-3">

                    <div class="users-header-icon">

                        <i class="bi bi-people"></i>

                    </div>


                    <div class="users-page-title">

                        <h2>
                            Manage Users
                        </h2>

                        <p>
                            Manage registered library users and their book activity.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="users-stats">


            <!-- Total Users -->

            <div class="user-stat-card">

                <div class="user-stat-icon blue">

                    <i class="bi bi-people-fill"></i>

                </div>

                <div class="user-stat-content">

                    <small>
                        Total Users
                    </small>

                    <strong>
                        <?php echo $totalUsers; ?>
                    </strong>

                </div>

            </div>


            <!-- Active Borrowers -->

            <div class="user-stat-card">

                <div class="user-stat-icon orange">

                    <i class="bi bi-book-half"></i>

                </div>

                <div class="user-stat-content">

                    <small>
                        Active Borrowers
                    </small>

                    <strong>
                        <?php echo $activeBorrowers; ?>
                    </strong>

                </div>

            </div>


            <!-- Issued Books -->

            <div class="user-stat-card">

                <div class="user-stat-icon green">

                    <i class="bi bi-journal-bookmark-fill"></i>

                </div>

                <div class="user-stat-content">

                    <small>
                        Books Currently Issued
                    </small>

                    <strong>
                        <?php echo $totalIssuedBooks; ?>
                    </strong>

                </div>

            </div>


            <!-- New Today -->

            <div class="user-stat-card">

                <div class="user-stat-icon purple">

                    <i class="bi bi-person-plus-fill"></i>

                </div>

                <div class="user-stat-content">

                    <small>
                        Registered Today
                    </small>

                    <strong>
                        <?php echo $todayUsers; ?>
                    </strong>

                </div>

            </div>


        </div>


        <!-- =================================================
             TABLE CARD
        ================================================== -->

        <div class="users-table-card">


            <!-- TABLE HEADER -->

            <div class="users-table-header">

                <div class="users-table-heading">

                    <div class="users-table-heading-icon">

                        <i class="bi bi-people"></i>

                    </div>


                    <div>

                        <h5>
                            Registered Users
                        </h5>

                        <span>
                            View and manage library member accounts
                        </span>

                    </div>

                </div>


                <div class="users-record-count">

                    <?php echo $totalUsers; ?>

                    <?php echo ($totalUsers == 1)
                        ? ' User'
                        : ' Users'; ?>

                </div>

            </div>


            <!-- TABLE -->

            <div class="users-table-wrapper">

                <table class="users-table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>User</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Issued Books</th>

                            <th>Registered</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if ($result && $result->num_rows > 0) {

                        $count = 1;

                        while (
                            $user =
                            $result->fetch_assoc()
                        ) {


                            /*
                            |--------------------------------------------------------------------------
                            | User Initial
                            |--------------------------------------------------------------------------
                            */

                            $userName = trim(
                                $user['name']
                            );

                            $userInitial =
                                strtoupper(
                                    substr(
                                        $userName,
                                        0,
                                        1
                                    )
                                );

                    ?>


                        <tr>


                            <!-- Number -->

                            <td>

                                <span class="user-number">

                                    <?php
                                    echo $count++;
                                    ?>

                                </span>

                            </td>


                            <!-- User -->

                            <td>

                                <div class="user-profile">

                                    <div class="user-avatar">

                                        <?php
                                        echo htmlspecialchars(
                                            $userInitial
                                        );
                                        ?>

                                    </div>


                                    <div class="user-profile-info">

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $user['name']
                                            );
                                            ?>

                                        </strong>

                                        <span>
                                            Library Member
                                        </span>

                                    </div>

                                </div>

                            </td>


                            <!-- Email -->

                            <td>

                                <span class="user-email">

                                    <i class="bi bi-envelope"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $user['email']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $user['phone']
                                    )
                                ) {

                                ?>

                                    <span class="user-phone">

                                        <i class="bi bi-telephone"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $user['phone']
                                        );
                                        ?>

                                    </span>

                                <?php

                                } else {

                                ?>

                                    <span class="text-muted">

                                        —

                                    </span>

                                <?php

                                }

                                ?>

                            </td>


                            <!-- Issued Books -->

                            <td>

                                <?php

                                if (
                                    $user['issued_count'] > 0
                                ) {

                                ?>

                                    <span
                                        class="issued-count active">

                                        <i class="bi bi-book"></i>

                                        <?php
                                        echo $user[
                                            'issued_count'
                                        ];
                                        ?>

                                    </span>

                                <?php

                                } else {

                                ?>

                                    <span
                                        class="issued-count zero">

                                        <i class="bi bi-dash-circle"></i>

                                        0

                                    </span>

                                <?php

                                }

                                ?>

                            </td>


                            <!-- Registered -->

                            <td>

                                <span class="registered-date">

                                    <i class="bi bi-calendar3"></i>

                                    <?php

                                    echo date(
                                        "d-m-Y",
                                        strtotime(
                                            $user[
                                                'created_at'
                                            ]
                                        )
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="user-actions">


                                    <!-- View -->

                                    <a
                                        href="view.php?id=<?php echo $user['id']; ?>"
                                        class="user-action-btn user-view-btn"
                                        title="View User">

                                        <i class="bi bi-eye"></i>

                                    </a>


                                    <!-- Edit -->

                                    <a
                                        href="edit.php?id=<?php echo $user['id']; ?>"
                                        class="user-action-btn user-edit-btn"
                                        title="Edit User">

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <!-- Delete -->

                                    <a
                                        href="delete.php?id=<?php echo $user['id']; ?>"
                                        class="user-action-btn user-delete-btn"
                                        title="Delete User"
                                        onclick="return confirm(
                                            'Are you sure you want to delete this user?'
                                        );">

                                        <i class="bi bi-trash"></i>

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php

                        }

                    } else {

                    ?>


                        <!-- EMPTY STATE -->

                        <tr>

                            <td colspan="7">

                                <div class="users-empty">

                                    <div class="users-empty-icon">

                                        <i class="bi bi-people"></i>

                                    </div>

                                    <h5>
                                        No Users Found
                                    </h5>

                                    <p>
                                        No registered library users are available.
                                    </p>

                                </div>

                            </td>

                        </tr>


                    <?php

                    }

                    ?>


                    </tbody>

                </table>

            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        |--------------------------------------------------------------------------
        | Sidebar Mobile Toggle
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.getElementById("adminSidebar");

        const overlay =
            document.querySelector(".sidebar-overlay");

        const toggleButton =
            document.querySelector(".sidebar-toggle");


        if (
            toggleButton &&
            sidebar
        ) {

            toggleButton.addEventListener(
                "click",
                function () {

                    sidebar.classList.toggle(
                        "show"
                    );


                    if (overlay) {

                        overlay.classList.toggle(
                            "show"
                        );

                    }

                }
            );

        }


        if (
            overlay &&
            sidebar
        ) {

            overlay.addEventListener(
                "click",
                function () {

                    sidebar.classList.remove(
                        "show"
                    );

                    overlay.classList.remove(
                        "show"
                    );

                }
            );

        }


    }
);

</script>


</body>

</html>