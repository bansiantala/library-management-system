<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/* =========================================
   BOOK REPORT DATA
========================================= */

$totalBooks = 0;
$totalQuantity = 0;
$totalAvailable = 0;
$totalIssued = 0;

/* Total book titles */
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM books"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalBooks = (int) $row['total'];
}

/* Quantity */
$result = $conn->query(
    "SELECT
        COALESCE(SUM(quantity), 0) AS total_quantity,
        COALESCE(SUM(available_quantity), 0) AS total_available
     FROM books"
);

if ($result) {
    $row = $result->fetch_assoc();

    $totalQuantity = (int) $row['total_quantity'];
    $totalAvailable = (int) $row['total_available'];
}

$totalIssued = $totalQuantity - $totalAvailable;

/* Book list */
$books = [];

$result = $conn->query(
    "SELECT
        books.id,
        books.title,
        books.author,
        books.isbn,
        books.quantity,
        books.available_quantity,
        categories.category_name
     FROM books
     LEFT JOIN categories
        ON books.category_id = categories.id
     ORDER BY books.id DESC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Books Report | Library Management System</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link
    rel="stylesheet"
    href="<?php echo BASE_URL; ?>/css/style.css">

<link
    rel="stylesheet"
    href="<?php echo BASE_URL; ?>/css/admin.css">


<style>

/* =====================================================
   REPORT PAGE
===================================================== */

.report-page {

    width: 100%;

    max-width: 1750px;

    margin: 0 auto;

    padding:
        32px 38px 60px;
}


/* =====================================================
   PAGE HEADER
===================================================== */

.report-header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 25px;

    margin-bottom: 30px;
}

.report-heading h2 {

    margin: 0;

    color: #172033;

    font-size: 34px;

    font-weight: 800;
}

.report-heading p {

    margin: 8px 0 0;

    color: #7b8498;

    font-size: 15px;
}

.report-breadcrumb {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-top: 12px;

    color: #8b94a7;

    font-size: 13px;
}

.report-breadcrumb .current {

    color: #4f46e5;

    font-weight: 700;
}


/* =====================================================
   ACTIONS
===================================================== */

.report-actions {

    display: flex;

    gap: 10px;
}

.report-btn {

    height: 46px;

    padding: 0 18px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    border-radius: 10px;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

    transition: .25s ease;
}

.print-btn {

    background: #4f46e5;

    color: white;

    border: 1px solid #4f46e5;
}

.print-btn:hover {

    background: #4338ca;

    color: white;

    transform: translateY(-2px);
}

.back-btn {

    background: white;

    color: #4b5563;

    border: 1px solid #dfe3eb;
}

.back-btn:hover {

    color: #4f46e5;

    border-color: #c7d2fe;

    background: #f8f9ff;
}


/* =====================================================
   STAT CARDS
===================================================== */

.report-stats {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 28px;
}

.report-stat-card {

    background: white;

    border: 1px solid #e7eaf0;

    border-radius: 16px;

    padding: 23px;

    box-shadow:
        0 7px 25px rgba(30,41,59,.06);

    display: flex;

    align-items: center;

    gap: 16px;
}

.stat-icon {

    width: 52px;

    height: 52px;

    flex-shrink: 0;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef2ff;

    color: #4f46e5;

    font-size: 23px;
}

.stat-content small {

    display: block;

    color: #8b94a7;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.stat-content strong {

    display: block;

    margin-top: 3px;

    color: #172033;

    font-size: 26px;

    font-weight: 800;
}


/* =====================================================
   REPORT CARD
===================================================== */

.report-card {

    background: white;

    border: 1px solid #e7eaf0;

    border-radius: 18px;

    box-shadow:
        0 8px 30px rgba(30,41,59,.07);

    overflow: hidden;
}

.report-card-header {

    padding: 24px 28px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    border-bottom: 1px solid #edf0f5;
}

.report-card-header h4 {

    margin: 0;

    color: #172033;

    font-size: 20px;

    font-weight: 800;
}

.report-card-header span {

    color: #8b94a7;

    font-size: 13px;
}


/* =====================================================
   TABLE
===================================================== */

.report-table-wrapper {

    width: 100%;

    overflow-x: auto;
}

.report-table {

    width: 100%;

    min-width: 1050px;

    border-collapse: collapse;
}

.report-table thead th {

    padding: 17px 20px;

    background: #f8f9fc;

    color: #687287;

    border-bottom: 1px solid #e8ebf1;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;

    white-space: nowrap;
}

.report-table tbody td {

    padding: 19px 20px;

    border-bottom: 1px solid #f0f2f6;

    color: #374151;

    font-size: 14px;

    vertical-align: middle;
}

.report-table tbody tr:hover {

    background: #fafbff;
}

.book-title {

    color: #172033;

    font-weight: 700;
}

.author {

    color: #6b7280;
}

.category-badge {

    display: inline-flex;

    padding: 6px 11px;

    border-radius: 20px;

    background: #eef2ff;

    color: #4f46e5;

    font-size: 12px;

    font-weight: 700;
}

.quantity-badge {

    display: inline-flex;

    min-width: 32px;

    justify-content: center;

    padding: 5px 9px;

    border-radius: 7px;

    background: #f3f4f6;

    color: #374151;

    font-weight: 700;
}

.available {

    color: #059669;

    font-weight: 700;
}

.issued {

    color: #dc2626;

    font-weight: 700;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-report {

    padding: 70px 20px;

    text-align: center;

    color: #8b94a7;
}

.empty-report i {

    font-size: 50px;

    color: #cbd5e1;
}

.empty-report h5 {

    margin-top: 15px;

    color: #374151;

    font-weight: 700;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1200px) {

    .report-page {
        padding-left: 25px;
        padding-right: 25px;
    }

    .report-stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media(max-width:768px) {

    .report-page {
        padding:
            25px 18px 45px;
    }

    .report-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .report-heading h2 {
        font-size: 29px;
    }

    .report-actions {
        width: 100%;
    }

    .report-btn {
        flex: 1;
    }

}

@media(max-width:576px) {

    .report-page {
        padding:
            20px 15px 35px;
    }

    .report-heading h2 {
        font-size: 25px;
    }

    .report-stats {
        grid-template-columns: 1fr;
    }

    .report-card-header {
        padding: 20px;
    }

}


/* =====================================================
   PRINT
===================================================== */

@media print {

    .admin-sidebar,
    .admin-navbar,
    .report-actions {
        display: none !important;
    }

    .admin-main {
        margin-left: 0 !important;
    }

    .report-page {
        max-width: 100%;
        padding: 15px;
    }

    .report-card,
    .report-stat-card {
        box-shadow: none;
    }

}

</style>

</head>


<body>


<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="admin-navbar">

    <div class="navbar-left">

        <div class="navbar-title">

            <h5>
                Books Report / Admin Dashboard
            </h5>

            <span>

                <i class="bi bi-house-door"></i>

                Home

                <i class="bi bi-chevron-right"></i>

                Reports

                <i class="bi bi-chevron-right"></i>

                Books Report

            </span>

        </div>

    </div>


    <div class="navbar-right">

        <button
            type="button"
            class="notification-btn">

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

            <span>Logout</span>

        </a>

    </div>

</nav>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="admin-content report-page">


<div class="report-header">

    <div class="report-heading">

        <h2>
            Books Report
        </h2>

        <p>
            Complete overview of books available in the library.
        </p>

        <div class="report-breadcrumb">

            <span>
                <i class="bi bi-house-door"></i>
                Home
            </span>

            <i class="bi bi-chevron-right"></i>

            <span>
                Reports
            </span>

            <i class="bi bi-chevron-right"></i>

            <span class="current">
                Books Report
            </span>

        </div>

    </div>


    <div class="report-actions">

        <button
            onclick="window.print()"
            class="report-btn print-btn">

            <i class="bi bi-printer"></i>

            Print Report

        </button>

        <a
            href="../dashboard.php"
            class="report-btn back-btn">

            <i class="bi bi-arrow-left"></i>

            Dashboard

        </a>

    </div>

</div>


<!-- =====================================================
     STATS
===================================================== -->

<div class="report-stats">


<div class="report-stat-card">

    <div class="stat-icon">
        <i class="bi bi-book"></i>
    </div>

    <div class="stat-content">

        <small>
            Total Books
        </small>

        <strong>
            <?php echo $totalBooks; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">
        <i class="bi bi-stack"></i>
    </div>

    <div class="stat-content">

        <small>
            Total Quantity
        </small>

        <strong>
            <?php echo $totalQuantity; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">
        <i class="bi bi-check-circle"></i>
    </div>

    <div class="stat-content">

        <small>
            Available
        </small>

        <strong>
            <?php echo $totalAvailable; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">
        <i class="bi bi-journal-bookmark"></i>
    </div>

    <div class="stat-content">

        <small>
            Issued
        </small>

        <strong>
            <?php echo $totalIssued; ?>
        </strong>

    </div>

</div>


</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="report-card">

    <div class="report-card-header">

        <div>

            <h4>
                Book Inventory
            </h4>

            <span>
                All books currently registered in the system
            </span>

        </div>

    </div>


    <?php if (count($books) > 0) { ?>

    <div class="report-table-wrapper">

        <table class="report-table">

            <thead>

                <tr>

                    <th>#</th>

                    <th>Book Title</th>

                    <th>Author</th>

                    <th>Category</th>

                    <th>ISBN</th>

                    <th>Total</th>

                    <th>Available</th>

                    <th>Issued</th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($books as $index => $book) { ?>

                <tr>

                    <td>
                        <?php echo $index + 1; ?>
                    </td>

                    <td class="book-title">
                        <?php
                        echo htmlspecialchars(
                            $book['title']
                        );
                        ?>
                    </td>

                    <td class="author">
                        <?php
                        echo htmlspecialchars(
                            $book['author']
                        );
                        ?>
                    </td>

                    <td>

                        <span class="category-badge">

                            <?php
                            echo htmlspecialchars(
                                $book['category_name']
                                ?? 'Uncategorized'
                            );
                            ?>

                        </span>

                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $book['isbn']
                            ?? '-'
                        );
                        ?>
                    </td>

                    <td>

                        <span class="quantity-badge">

                            <?php
                            echo (int)
                                $book['quantity'];
                            ?>

                        </span>

                    </td>

                    <td class="available">

                        <?php
                        echo (int)
                            $book['available_quantity'];
                        ?>

                    </td>

                    <td class="issued">

                        <?php

                        echo
                            (int)$book['quantity']
                            -
                            (int)$book['available_quantity'];

                        ?>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

    <?php } else { ?>

        <div class="empty-report">

            <i class="bi bi-book"></i>

            <h5>
                No Books Found
            </h5>

            <p>
                There are no books available in the library.
            </p>

        </div>

    <?php } ?>

</div>


</div>

</div>


</body>

</html>