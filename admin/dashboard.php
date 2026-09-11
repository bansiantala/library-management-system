<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireAdmin();

/* =========================
   DASHBOARD COUNTS
   ========================= */

// Total books
$bookResult = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
);
$totalBooks = $bookResult->fetch_assoc()['total'];


// Total users
$userResult = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
);
$totalUsers = $userResult->fetch_assoc()['total'];


// Total categories
$categoryResult = $conn->query(
    "SELECT COUNT(*) AS total FROM categories"
);
$totalCategories = $categoryResult->fetch_assoc()['total'];


// Active issued books
$issuedResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);
$totalIssued = $issuedResult->fetch_assoc()['total'];


// Total available books
$availableResult = $conn->query(
    "SELECT COALESCE(SUM(available_quantity), 0) AS total
     FROM books"
);
$totalAvailable = $availableResult->fetch_assoc()['total'];


// Total fine
$fineResult = $conn->query(
    "SELECT COALESCE(SUM(fine), 0) AS total
     FROM issued_books"
);
$totalFine = $fineResult->fetch_assoc()['total'];


// Recent issued books
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

    <title>Admin Dashboard - Library Management</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Admin CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >

</head>


<body>


<?php include "../includes/admin_sidebar.php"; ?>


<!-- MAIN -->

<div class="admin-main">


    <!-- NAVBAR -->

    <nav class="admin-navbar">

    <!-- LEFT SIDE -->
    <div class="navbar-left">

       

        <!-- Page Title -->
        <div class="navbar-title">
            <h5>Admin Dashboard</h5>
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

      

        <!-- Notification -->
        <button
            type="button"
            class="notification-btn"
            title="Notifications"
        >
            <i class="bi bi-bell"></i>

            <?php if ($totalIssued > 0): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
        </button>


        <!-- Divider -->
        <div class="header-divider"></div>


        <!-- Admin Profile -->
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

        <!-- Logout Button -->
    <a
        href="<?php echo BASE_URL; ?>/logout.php"
        class="admin-logout-btn"
        title="Logout"
    >
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>

    </div>

</nav>

    <!-- CONTENT -->

    <main class="dashboard-content">


        <!-- WELCOME -->

        <div class="dashboard-welcome">

            <h2>
                Welcome back,
                <?php
                echo htmlspecialchars(
                    $_SESSION['user_name'] ?? 'Administrator'
                );
                ?>
                
            </h2>

            <p>
                Manage your library, books, users and issue records
                from one place.
            </p>

        </div>


        <!-- STATISTICS -->

        <div class="dashboard-stats">


            <!-- BOOKS -->

            <div class="stat-card stat-blue">

                <div class="stat-icon">

                    <i class="bi bi-book-fill"></i>

                </div>

                <div class="stat-info">

                    <span>Total Books</span>

                    <h3>
                        <?php echo $totalBooks; ?>
                    </h3>

                </div>

            </div>


            <!-- USERS -->

            <div class="stat-card stat-green">

                <div class="stat-icon">

                    <i class="bi bi-people-fill"></i>

                </div>

                <div class="stat-info">

                    <span>Total Users</span>

                    <h3>
                        <?php echo $totalUsers; ?>
                    </h3>

                </div>

            </div>


            <!-- ISSUED -->

            <div class="stat-card stat-orange">

                <div class="stat-icon">

                    <i class="bi bi-arrow-left-right"></i>

                </div>

                <div class="stat-info">

                    <span>Issued Books</span>

                    <h3>
                        <?php echo $totalIssued; ?>
                    </h3>

                </div>

            </div>


            <!-- CATEGORIES -->

            <div class="stat-card stat-purple">

                <div class="stat-icon">

                    <i class="bi bi-collection-fill"></i>

                </div>

                <div class="stat-info">

                    <span>Categories</span>

                    <h3>
                        <?php echo $totalCategories; ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- SECOND ROW -->

        <div class="dashboard-grid">


            <!-- RECENT ISSUES -->

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

                                    <th>#</th>

                                    <th>Book</th>

                                    <th>User</th>

                                    <th>Issue Date</th>

                                    <th>Status</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php if ($recentIssues && $recentIssues->num_rows > 0): ?>

                                <?php
                                $count = 1;
                                ?>

                                <?php while ($row = $recentIssues->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            <?php echo $count++; ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['title']
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['user_name']
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime(
                                                    $row['issue_date']
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>

                                            <?php if ($row['status'] === 'Issued'): ?>

                                                <span class="status-badge status-issued">
                                                    Issued
                                                </span>

                                            <?php else: ?>

                                                <span class="status-badge status-returned">
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


            <!-- QUICK ACTIONS -->

            <div class="dashboard-card">

                <div class="card-header">

                    <h5>
                        Quick Actions
                    </h5>

                </div>


                <div class="card-body">

                    <div class="quick-actions">


                        <a
                            href="<?php echo BASE_URL; ?>/admin/books/add.php"
                            class="quick-action"
                        >

                            <i class="bi bi-plus-circle-fill"></i>

                            <strong>Add Book</strong>

                            <span>
                                Add a new library book
                            </span>

                        </a>


                        <a
                            href="<?php echo BASE_URL; ?>/admin/categories/add.php"
                            class="quick-action"
                        >

                            <i class="bi bi-folder-plus"></i>

                            <strong>Add Category</strong>

                            <span>
                                Create book category
                            </span>

                        </a>


                        <a
                            href="<?php echo BASE_URL; ?>/admin/users/index.php"
                            class="quick-action"
                        >

                            <i class="bi bi-people-fill"></i>

                            <strong>Manage Users</strong>

                            <span>
                                View library users
                            </span>

                        </a>


                        <a
                            href="<?php echo BASE_URL; ?>/admin/issue/index.php"
                            class="quick-action"
                        >

                            <i class="bi bi-arrow-left-right"></i>

                            <strong>Issue / Return</strong>

                            <span>
                                Manage book circulation
                            </span>

                        </a>


                    </div>

                </div>

            </div>

        </div>


        <!-- LIBRARY SUMMARY -->

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


                    <div class="col-md-4">

                        <div class="p-3 bg-light rounded-3">

                            <small class="text-muted">
                                Available Book Copies
                            </small>

                            <h4 class="mt-1 mb-0">
                                <?php echo $totalAvailable; ?>
                            </h4>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="p-3 bg-light rounded-3">

                            <small class="text-muted">
                                Active Issued Books
                            </small>

                            <h4 class="mt-1 mb-0">
                                <?php echo $totalIssued; ?>
                            </h4>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="p-3 bg-light rounded-3">

                            <small class="text-muted">
                                Total Fine
                            </small>

                            <h4 class="mt-1 mb-0">
                                ₹<?php echo number_format($totalFine, 2); ?>
                            </h4>

                        </div>

                    </div>


                </div>

            </div>

        </div>


    </main>

</div>


<!-- SIDEBAR SCRIPT -->

<script>

const sidebarToggle =
    document.getElementById("sidebarToggle");

const adminSidebar =
    document.getElementById("adminSidebar");


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

            adminSidebar.classList.remove("show");

        }

    }
);

</script>


</body>
</html>