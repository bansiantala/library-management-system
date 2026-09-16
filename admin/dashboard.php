<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireAdmin();

/* =========================================================
   DASHBOARD COUNTS
========================================================= */

/* =========================
   TOTAL BOOKS
========================= */

$totalBooks = 0;

$bookResult = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
);

if ($bookResult) {

    $bookData = $bookResult->fetch_assoc();

    $totalBooks = (int)($bookData['total'] ?? 0);
}


/* =========================
   TOTAL USERS
========================= */

$totalUsers = 0;

$userResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'"
);

if ($userResult) {

    $userData = $userResult->fetch_assoc();

    $totalUsers = (int)($userData['total'] ?? 0);
}


/* =========================
   TOTAL CATEGORIES
========================= */

$totalCategories = 0;

$categoryResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM categories"
);

if ($categoryResult) {

    $categoryData = $categoryResult->fetch_assoc();

    $totalCategories =
        (int)($categoryData['total'] ?? 0);
}


/* =========================
   ACTIVE ISSUED BOOKS
========================= */

$totalIssued = 0;

$issuedResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);

if ($issuedResult) {

    $issuedData = $issuedResult->fetch_assoc();

    $totalIssued =
        (int)($issuedData['total'] ?? 0);
}


/* =========================
   OVERDUE BOOKS
========================= */

$totalOverdue = 0;

$overdueResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'
       AND return_date < CURDATE()"
);

if ($overdueResult) {

    $overdueData = $overdueResult->fetch_assoc();

    $totalOverdue =
        (int)($overdueData['total'] ?? 0);
}


/* =========================
   PENDING ISSUE REQUESTS
========================= */

$totalPending = 0;

$pendingResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issue_requests
     WHERE status = 'Pending'"
);

if ($pendingResult) {

    $pendingData = $pendingResult->fetch_assoc();

    $totalPending =
        (int)($pendingData['total'] ?? 0);
}


/* =========================
   PENDING RESERVATIONS
========================= */

$totalPendingReservations = 0;

$pendingReservationResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM reservations
     WHERE status = 'Pending'"
);

if ($pendingReservationResult) {

    $pendingReservationData =
        $pendingReservationResult->fetch_assoc();

    $totalPendingReservations =
        (int)($pendingReservationData['total'] ?? 0);
}


/* =========================
   TOTAL AVAILABLE BOOK COPIES
========================= */

$totalAvailable = 0;

$availableResult = $conn->query(
    "SELECT COALESCE(
        SUM(available_quantity), 0
     ) AS total
     FROM books"
);

if ($availableResult) {

    $availableData =
        $availableResult->fetch_assoc();

    $totalAvailable =
        (int)($availableData['total'] ?? 0);
}


/* =========================
   TOTAL FINE
========================= */

$totalFine = 0;

$fineResult = $conn->query(
    "SELECT COALESCE(
        SUM(fine), 0
     ) AS total
     FROM issued_books"
);

if ($fineResult) {

    $fineData =
        $fineResult->fetch_assoc();

    $totalFine =
        (float)($fineData['total'] ?? 0);
}


/* =========================================================
   RECENT ISSUED BOOKS
========================================================= */

$recentIssues = $conn->query(
    "SELECT
        issued_books.id,
        books.title,
        users.name AS user_name,
        issued_books.issue_date,
        issued_books.return_date,
        issued_books.status
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     INNER JOIN users
        ON issued_books.user_id = users.id
     ORDER BY issued_books.id DESC
     LIMIT 5"
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

    <title>
        Admin Dashboard - Library Management
    </title>


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
         ADMIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >


    <style>

        /* =====================================================
           DASHBOARD STATISTICS
        ====================================================== */

        .dashboard-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 30px;

        }


        /* =====================================================
           OVERDUE BOOKS
        ====================================================== */

        .stat-red .stat-icon {

            background: #fef2f2;

            color: #dc2626;

        }


        .stat-red .stat-info h3 {

            color: #dc2626;

        }


        .stat-red:hover {

            border-color: #fecaca;

        }


        /* =====================================================
           PENDING REQUESTS
        ====================================================== */

        .stat-yellow .stat-icon {

            background: #fff7ed;

            color: #ea580c;

        }


        .stat-yellow .stat-info h3 {

            color: #ea580c;

        }


        .stat-yellow:hover {

            border-color: #fed7aa;

        }


        /* =====================================================
           PENDING RESERVATIONS
        ====================================================== */

        .stat-teal .stat-icon {

            background: #ecfeff;

            color: #0891b2;

        }


        .stat-teal .stat-info h3 {

            color: #0891b2;

        }


        .stat-teal:hover {

            border-color: #a5f3fc;

        }


        /* =====================================================
           RESERVATIONS QUICK ACTION
        ====================================================== */

        .quick-action.reservation-action {

            border-color: #cffafe;

        }


        .quick-action.reservation-action:hover {

            border-color: #a5f3fc;

        }


        .quick-action.reservation-action i {

            color: #0891b2;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1200px) {

            .dashboard-stats {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));

            }

        }


        @media (max-width: 768px) {

            .dashboard-stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

        }


        @media (max-width: 500px) {

            .dashboard-stats {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php include "../includes/admin_sidebar.php"; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="admin-main">


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <nav class="admin-navbar">


        <!-- LEFT SIDE -->

        <div class="navbar-left">


            <div class="navbar-title">

                <h5>
                    Admin Dashboard
                </h5>


                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Dashboard

                </span>

            </div>


        </div>


        <!-- RIGHT SIDE -->

        <div class="navbar-right">


            <!-- =================================================
                 NOTIFICATION
            ================================================== -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >

                <i class="bi bi-bell"></i>


                <?php if (
                    $totalIssued > 0 ||
                    $totalPending > 0 ||
                    $totalOverdue > 0 ||
                    $totalPendingReservations > 0
                ): ?>

                    <span class="notification-dot"></span>

                <?php endif; ?>


            </button>


            <!-- DIVIDER -->

            <div class="header-divider"></div>


            <!-- =================================================
                 ADMIN PROFILE
            ================================================== -->

            <div class="nav-admin">


                <div class="nav-avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <div class="nav-admin-info">


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION['user_name']
                            ?? 'Admin'
                        );

                        ?>

                    </strong>


                    <small>
                        Administrator
                    </small>


                </div>


            </div>


            <!-- =================================================
                 LOGOUT
            ================================================== -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>


        </div>


    </nav>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="dashboard-content">


        <!-- =================================================
             WELCOME
        ================================================== -->

        <div class="dashboard-welcome">


            <h2>

                Welcome back,

                <?php

                echo htmlspecialchars(
                    $_SESSION['user_name']
                    ?? 'Administrator'
                );

                ?>

            </h2>


            <p>

                Manage your library, books, users and
                issue records from one place.

            </p>


        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="dashboard-stats">


            <!-- =================================================
                 TOTAL BOOKS
            ================================================== -->

            <div class="stat-card stat-blue">


                <div class="stat-icon">

                    <i class="bi bi-book-fill"></i>

                </div>


                <div class="stat-info">


                    <span>
                        Total Books
                    </span>


                    <h3>

                        <?php

                        echo $totalBooks;

                        ?>

                    </h3>


                </div>


            </div>


            <!-- =================================================
                 TOTAL USERS
            ================================================== -->

            <div class="stat-card stat-green">


                <div class="stat-icon">

                    <i class="bi bi-people-fill"></i>

                </div>


                <div class="stat-info">


                    <span>
                        Total Users
                    </span>


                    <h3>

                        <?php

                        echo $totalUsers;

                        ?>

                    </h3>


                </div>


            </div>


            <!-- =================================================
                 ISSUED BOOKS
            ================================================== -->

            <div class="stat-card stat-orange">


                <div class="stat-icon">

                    <i class="bi bi-arrow-left-right"></i>

                </div>


                <div class="stat-info">


                    <span>
                        Issued Books
                    </span>


                    <h3>

                        <?php

                        echo $totalIssued;

                        ?>

                    </h3>


                </div>


            </div>


            <!-- =================================================
                 CATEGORIES
            ================================================== -->

            <div class="stat-card stat-purple">


                <div class="stat-icon">

                    <i class="bi bi-collection-fill"></i>

                </div>


                <div class="stat-info">


                    <span>
                        Categories
                    </span>


                    <h3>

                        <?php

                        echo $totalCategories;

                        ?>

                    </h3>


                </div>


            </div>


            <!-- =================================================
                 OVERDUE BOOKS
            ================================================== -->

</div>


        <!-- =================================================
             SECOND ROW
        ================================================== -->

        <div class="dashboard-grid">


            <!-- =================================================
                 RECENT ISSUES
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <h5>
                        Recent Issue Records
                    </h5>


                    <a
                        href="<?php echo BASE_URL; ?>/admin/issue/index.php"
                    >
                        View All
                    </a>


                </div>


                <div class="card-body p-0">


                    <div class="table-responsive">


                        <table class="admin-table">


                            <thead>


                                <tr>


                                    <th>
                                        #
                                    </th>


                                    <th>
                                        Book
                                    </th>


                                    <th>
                                        User
                                    </th>


                                    <th>
                                        Issue Date
                                    </th>


                                    <th>
                                        Status
                                    </th>


                                </tr>


                            </thead>


                            <tbody>


                            <?php if (
                                $recentIssues &&
                                $recentIssues->num_rows > 0
                            ): ?>


                                <?php

                                $count = 1;

                                ?>


                                <?php while (
                                    $row =
                                    $recentIssues->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <!-- NUMBER -->

                                        <td>

                                            <?php

                                            echo $count++;

                                            ?>

                                        </td>


                                        <!-- BOOK -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $row['title']
                                            );

                                            ?>

                                        </td>


                                        <!-- USER -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $row['user_name']
                                            );

                                            ?>

                                        </td>


                                        <!-- ISSUE DATE -->

                                        <td>

                                            <?php

                                            echo date(
                                                'd M Y',
                                                strtotime(
                                                    $row[
                                                        'issue_date'
                                                    ]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <?php if (
                                                $row['status']
                                                === 'Issued'
                                            ): ?>


                                                <span
                                                    class="
                                                        status-badge
                                                        status-issued
                                                    "
                                                >

                                                    Issued

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="
                                                        status-badge
                                                        status-returned
                                                    "
                                                >

                                                    Returned

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>


                                    <td
                                        colspan="5"
                                        class="text-center py-4"
                                    >

                                        No issue records found.


                                    </td>


                                </tr>


                            <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <h5>
                        Quick Actions
                    </h5>


                </div>


                <div class="card-body">


                    <div class="quick-actions">


                        <!-- ADD BOOK -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/books/add.php"
                            class="quick-action"
                        >

                            <i class="bi bi-plus-circle-fill"></i>


                            <strong>
                                Add Book
                            </strong>


                            <span>
                                Add a new library book
                            </span>


                        </a>


                        <!-- ADD CATEGORY -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/categories/add.php"
                            class="quick-action"
                        >

                            <i class="bi bi-folder-plus"></i>


                            <strong>
                                Add Category
                            </strong>


                            <span>
                                Create book category
                            </span>


                        </a>


                        <!-- MANAGE USERS -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/users/index.php"
                            class="quick-action"
                        >

                            <i class="bi bi-people-fill"></i>


                            <strong>
                                Manage Users
                            </strong>


                            <span>
                                View library users
                            </span>


                        </a>


                        <!-- ISSUE / RETURN -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/issue/index.php"
                            class="quick-action"
                        >

                            <i class="bi bi-arrow-left-right"></i>


                            <strong>
                                Issue / Return
                            </strong>


                            <span>
                                Manage book circulation
                            </span>


                        </a>


                        <!-- PENDING REQUESTS -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/issue/index.php"
                            class="quick-action"
                        >

                            <i class="bi bi-clock-history"></i>


                            <strong>
                                Pending Requests
                            </strong>


                            <span>
                                Review book requests
                            </span>


                        </a>


                        <!-- RESERVATIONS -->

                        <a
                            href="<?php echo BASE_URL; ?>/admin/reservations/index.php"
                            class="
                                quick-action
                                reservation-action
                            "
                        >

                            <i
                                class="
                                    bi
                                    bi-bookmark-star-fill
                                "
                            ></i>


                            <strong>
                                Reservations
                            </strong>


                            <span>
                                Manage book reservations
                            </span>


                        </a>


                    </div>


                </div>


            </div>


        </div>


        <!-- =================================================
             LIBRARY SUMMARY
        ================================================== -->

        <div
            class="dashboard-card mt-4"
        >


            <div class="card-header">


                <h5>
                    Library Summary
                </h5>


            </div>


            <div class="card-body">


                <div class="row g-3">


                    <!-- AVAILABLE -->

                    <div class="col-md-4">


                        <div class="p-3 bg-light rounded-3">


                            <small class="text-muted">

                                Available Book Copies

                            </small>


                            <h4 class="mt-1 mb-0">


                                <?php

                                echo $totalAvailable;

                                ?>


                            </h4>


                        </div>


                    </div>


                    <!-- ACTIVE ISSUED -->

                    <div class="col-md-4">


                        <div class="p-3 bg-light rounded-3">


                            <small class="text-muted">

                                Active Issued Books

                            </small>


                            <h4 class="mt-1 mb-0">


                                <?php

                                echo $totalIssued;

                                ?>


                            </h4>


                        </div>


                    </div>


                    <!-- TOTAL FINE -->

                    <div class="col-md-4">


                        <div class="p-3 bg-light rounded-3">


                            <small class="text-muted">

                                Total Fine

                            </small>


                            <h4 class="mt-1 mb-0">


                                ₹<?php

                                echo number_format(
                                    $totalFine,
                                    2
                                );

                                ?>


                            </h4>


                        </div>


                    </div>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     SIDEBAR SCRIPT
========================================================= -->

<script>

const sidebarToggle =
    document.getElementById("sidebarToggle");


const adminSidebar =
    document.getElementById("adminSidebar");


if (sidebarToggle && adminSidebar) {


    sidebarToggle.addEventListener(
        "click",
        function () {

            adminSidebar.classList.toggle("show");

        }
    );


    document.addEventListener(
        "click",
        function (event) {


            if (

                window.innerWidth <= 992 &&

                adminSidebar.classList.contains("show") &&

                !adminSidebar.contains(event.target) &&

                !sidebarToggle.contains(event.target)

            ) {


                adminSidebar.classList.remove(
                    "show"
                );


            }


        }
    );


}

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>