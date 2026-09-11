<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/* =========================================
   ISSUE REPORT DATA
========================================= */

$totalRecords = 0;
$totalIssued = 0;
$totalReturned = 0;
$totalFine = 0;


/* Total records */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalRecords = (int) $row['total'];
}


/* Issued */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalIssued = (int) $row['total'];
}


/* Returned */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Returned'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalReturned = (int) $row['total'];
}


/* Fine */

$result = $conn->query(
    "SELECT COALESCE(SUM(fine), 0) AS total
     FROM issued_books"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalFine = (float) $row['total'];
}


/* Issue history */

$issues = [];

$result = $conn->query(
    "SELECT
        ib.id,
        ib.issue_date,
        ib.return_date,
        ib.actual_return_date,
        ib.fine,
        ib.status,

        b.title AS book_title,

        u.name AS user_name,
        u.email AS user_email

     FROM issued_books ib

     INNER JOIN books b
        ON ib.book_id = b.id

     INNER JOIN users u
        ON ib.user_id = u.id

     ORDER BY ib.id DESC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $issues[] = $row;

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

<title>Issue Report | Library Management System</title>

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
   ISSUE REPORT
===================================================== */

.report-page {

    width: 100%;

    max-width: 1750px;

    margin: 0 auto;

    padding:
        32px 38px 60px;
}


/* =====================================================
   HEADER
===================================================== */

.report-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

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
   BUTTONS
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

    font-size: 14px;

    font-weight: 700;

    text-decoration: none;

    transition: .25s ease;
}

.print-btn {

    border: 1px solid #4f46e5;

    background: #4f46e5;

    color: white;
}

.print-btn:hover {

    background: #4338ca;

    color: white;
}

.back-btn {

    background: white;

    color: #4b5563;

    border: 1px solid #dfe3eb;
}

.back-btn:hover {

    background: #f8f9ff;

    color: #4f46e5;

    border-color: #c7d2fe;
}


/* =====================================================
   STATS
===================================================== */

.report-stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 28px;
}

.report-stat-card {

    background: white;

    border: 1px solid #e7eaf0;

    border-radius: 16px;

    padding: 23px;

    display: flex;

    align-items: center;

    gap: 16px;

    box-shadow:
        0 7px 25px rgba(30,41,59,.06);
}

.stat-icon {

    width: 54px;

    height: 54px;

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

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.stat-content strong {

    display: block;

    margin-top: 3px;

    color: #172033;

    font-size: 25px;

    font-weight: 800;
}


/* =====================================================
   REPORT CARD
===================================================== */

.report-card {

    background: white;

    border: 1px solid #e7eaf0;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 8px 30px rgba(30,41,59,.07);
}

.report-card-header {

    padding: 24px 28px;

    border-bottom: 1px solid #edf0f5;
}

.report-card-header h4 {

    margin: 0;

    color: #172033;

    font-size: 20px;

    font-weight: 800;
}

.report-card-header span {

    display: block;

    margin-top: 5px;

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

    min-width: 1250px;

    border-collapse: collapse;
}

.report-table th {

    padding: 17px 19px;

    background: #f8f9fc;

    color: #687287;

    border-bottom: 1px solid #e8ebf1;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    white-space: nowrap;
}

.report-table td {

    padding: 18px 19px;

    border-bottom: 1px solid #f0f2f6;

    color: #374151;

    font-size: 14px;

    vertical-align: middle;
}

.report-table tbody tr:hover {

    background: #fafbff;
}


/* =====================================================
   BOOK
===================================================== */

.book-cell {

    display: flex;

    align-items: center;

    gap: 12px;
}

.book-icon {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #eef2ff;

    color: #4f46e5;

    font-size: 18px;
}

.book-title {

    color: #172033;

    font-weight: 700;
}


/* =====================================================
   USER
===================================================== */

.user-name {

    color: #172033;

    font-weight: 700;
}

.user-email {

    margin-top: 3px;

    color: #8b94a7;

    font-size: 12px;
}


/* =====================================================
   STATUS
===================================================== */

.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;
}

.status-issued {

    background: #fff7ed;

    color: #ea580c;
}

.status-returned {

    background: #ecfdf5;

    color: #059669;
}

.status-badge i {

    font-size: 7px;
}


/* =====================================================
   FINE
===================================================== */

.fine {

    color: #dc2626;

    font-weight: 700;
}

.no-fine {

    color: #059669;

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
                Issue Report / Admin Dashboard
            </h5>

            <span>

                <i class="bi bi-house-door"></i>

                Home

                <i class="bi bi-chevron-right"></i>

                Reports

                <i class="bi bi-chevron-right"></i>

                Issue Report

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

            <span>
                Logout
            </span>

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
        Issue Report
    </h2>

    <p>
        Complete history of books issued and returned.
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
            Issue Report
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

        <i class="bi bi-journal-text"></i>

    </div>

    <div class="stat-content">

        <small>
            Total Records
        </small>

        <strong>
            <?php echo $totalRecords; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-bookmark-check"></i>

    </div>

    <div class="stat-content">

        <small>
            Currently Issued
        </small>

        <strong>
            <?php echo $totalIssued; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-check2-circle"></i>

    </div>

    <div class="stat-content">

        <small>
            Returned
        </small>

        <strong>
            <?php echo $totalReturned; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-currency-rupee"></i>

    </div>

    <div class="stat-content">

        <small>
            Total Fine
        </small>

        <strong>
            ₹<?php echo number_format($totalFine, 2); ?>
        </strong>

    </div>

</div>


</div>


<!-- =====================================================
     ISSUE TABLE
===================================================== -->

<div class="report-card">


<div class="report-card-header">

    <h4>
        Issue & Return History
    </h4>

    <span>
        Complete record of library transactions
    </span>

</div>


<?php if (count($issues) > 0) { ?>


<div class="report-table-wrapper">

<table class="report-table">


<thead>

<tr>

    <th>#</th>

    <th>Book</th>

    <th>User</th>

    <th>Issue Date</th>

    <th>Due Date</th>

    <th>Return Date</th>

    <th>Fine</th>

    <th>Status</th>

</tr>

</thead>


<tbody>


<?php foreach ($issues as $index => $issue) { ?>


<tr>


<td>

    <?php echo $index + 1; ?>

</td>


<td>


<div class="book-cell">


<div class="book-icon">

    <i class="bi bi-book"></i>

</div>


<div class="book-title">

    <?php

    echo htmlspecialchars(
        $issue['book_title']
    );

    ?>

</div>


</div>


</td>


<td>


<div class="user-name">

    <?php

    echo htmlspecialchars(
        $issue['user_name']
    );

    ?>

</div>


<div class="user-email">

    <?php

    echo htmlspecialchars(
        $issue['user_email']
    );

    ?>

</div>


</td>


<td>

    <?php

    echo date(
        "d M Y",
        strtotime(
            $issue['issue_date']
        )
    );

    ?>

</td>


<td>

    <?php

    echo date(
        "d M Y",
        strtotime(
            $issue['return_date']
        )
    );

    ?>

</td>


<td>

    <?php

    if (!empty(
        $issue['actual_return_date']
    )) {

        echo date(
            "d M Y",
            strtotime(
                $issue['actual_return_date']
            )
        );

    } else {

        echo "-";

    }

    ?>

</td>


<td>


<?php

if ((float)$issue['fine'] > 0) {

?>

<span class="fine">

    ₹<?php

    echo number_format(
        (float)$issue['fine'],
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


<td>


<?php

if ($issue['status'] === 'Issued') {

?>

<span class="status-badge status-issued">

    <i class="bi bi-circle-fill"></i>

    Issued

</span>

<?php

} else {

?>

<span class="status-badge status-returned">

    <i class="bi bi-circle-fill"></i>

    Returned

</span>

<?php

}

?>


</td>


</tr>


<?php } ?>


</tbody>


</table>

</div>


<?php } else { ?>


<div class="empty-report">

    <i class="bi bi-journal-x"></i>

    <h5>
        No Issue Records Found
    </h5>

    <p>
        No books have been issued yet.
    </p>

</div>


<?php } ?>


</div>


</div>

</div>


</body>

</html>