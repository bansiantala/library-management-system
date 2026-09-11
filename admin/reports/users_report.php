<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/* =========================================
   USER REPORT DATA
========================================= */

$totalUsers = 0;
$activeBorrowers = 0;
$totalIssued = 0;

/* Total users */
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalUsers = (int) $row['total'];
}


/* Total issued books */
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $totalIssued = (int) $row['total'];
}


/* Active borrowers */
$result = $conn->query(
    "SELECT COUNT(DISTINCT user_id) AS total
     FROM issued_books
     WHERE status = 'Issued'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $activeBorrowers = (int) $row['total'];
}


/* User list */
$users = [];

$result = $conn->query(
    "SELECT
        u.id,
        u.name,
        u.email,
        u.phone,
        u.created_at,
        COUNT(
            CASE
                WHEN ib.status = 'Issued'
                THEN ib.id
            END
        ) AS issued_books
     FROM users u
     LEFT JOIN issued_books ib
        ON u.id = ib.user_id
     WHERE u.role = 'user'
     GROUP BY
        u.id,
        u.name,
        u.email,
        u.phone,
        u.created_at
     ORDER BY u.id DESC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $users[] = $row;

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

<title>Users Report | Library Management System</title>

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
   USERS REPORT
===================================================== */

.report-page {

    width: 100%;

    max-width: 1750px;

    margin: 0 auto;

    padding:
        32px 38px 60px;
}

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
}

.back-btn {

    background: white;

    color: #4b5563;

    border: 1px solid #dfe3eb;
}

.back-btn:hover {

    color: #4f46e5;

    background: #f8f9ff;

    border-color: #c7d2fe;
}


/* =====================================================
   STATS
===================================================== */

.report-stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 28px;
}

.report-stat-card {

    background: white;

    border: 1px solid #e7eaf0;

    border-radius: 16px;

    padding: 24px;

    display: flex;

    align-items: center;

    gap: 16px;

    box-shadow:
        0 7px 25px rgba(30,41,59,.06);
}

.stat-icon {

    width: 54px;

    height: 54px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef2ff;

    color: #4f46e5;

    font-size: 24px;
}

.stat-content small {

    display: block;

    color: #8b94a7;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;
}

.stat-content strong {

    display: block;

    margin-top: 3px;

    color: #172033;

    font-size: 27px;

    font-weight: 800;
}


/* =====================================================
   CARD
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

    min-width: 1000px;

    border-collapse: collapse;
}

.report-table th {

    padding: 17px 20px;

    background: #f8f9fc;

    color: #687287;

    border-bottom: 1px solid #e8ebf1;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    white-space: nowrap;
}

.report-table td {

    padding: 18px 20px;

    border-bottom: 1px solid #f0f2f6;

    color: #374151;

    font-size: 14px;

    vertical-align: middle;
}

.report-table tbody tr:hover {

    background: #fafbff;
}


/* =====================================================
   USER
===================================================== */

.user-cell {

    display: flex;

    align-items: center;

    gap: 13px;
}

.user-avatar {

    width: 43px;

    height: 43px;

    flex-shrink: 0;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef2ff;

    color: #4f46e5;

    font-size: 15px;

    font-weight: 800;
}

.user-name {

    color: #172033;

    font-weight: 700;
}

.email-cell {

    color: #667085;
}

.phone-cell {

    color: #667085;
}

.issued-badge {

    display: inline-flex;

    min-width: 34px;

    justify-content: center;

    padding: 6px 10px;

    border-radius: 20px;

    background: #fff7ed;

    color: #ea580c;

    font-size: 12px;

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

    .report-stats {
        grid-template-columns: 1fr;
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


<nav class="admin-navbar">

    <div class="navbar-left">

        <div class="navbar-title">

            <h5>
                Users Report / Admin Dashboard
            </h5>

            <span>

                <i class="bi bi-house-door"></i>

                Home

                <i class="bi bi-chevron-right"></i>

                Reports

                <i class="bi bi-chevron-right"></i>

                Users Report

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


<div class="admin-content report-page">


<div class="report-header">

    <div class="report-heading">

        <h2>
            Users Report
        </h2>

        <p>
            Complete overview of registered library users.
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
                Users Report
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


<!-- STATS -->

<div class="report-stats">


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-people"></i>

    </div>

    <div class="stat-content">

        <small>
            Total Users
        </small>

        <strong>
            <?php echo $totalUsers; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-person-check"></i>

    </div>

    <div class="stat-content">

        <small>
            Active Borrowers
        </small>

        <strong>
            <?php echo $activeBorrowers; ?>
        </strong>

    </div>

</div>


<div class="report-stat-card">

    <div class="stat-icon">

        <i class="bi bi-journal-bookmark"></i>

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


</div>


<!-- TABLE -->

<div class="report-card">


<div class="report-card-header">

    <h4>
        Registered Users
    </h4>

    <span>
        All normal users registered in the library system
    </span>

</div>


<?php if (count($users) > 0) { ?>


<div class="report-table-wrapper">

<table class="report-table">

<thead>

<tr>

    <th>#</th>

    <th>User</th>

    <th>Email</th>

    <th>Phone</th>

    <th>Joined Date</th>

    <th>Issued Books</th>

</tr>

</thead>


<tbody>


<?php foreach ($users as $index => $item) { ?>


<tr>


<td>
    <?php echo $index + 1; ?>
</td>


<td>

<div class="user-cell">

    <div class="user-avatar">

        <?php

        echo strtoupper(
            substr(
                $item['name'],
                0,
                1
            )
        );

        ?>

    </div>


    <div class="user-name">

        <?php

        echo htmlspecialchars(
            $item['name']
        );

        ?>

    </div>

</div>

</td>


<td class="email-cell">

    <?php

    echo htmlspecialchars(
        $item['email']
    );

    ?>

</td>


<td class="phone-cell">

    <?php

    echo !empty($item['phone'])

        ? htmlspecialchars(
            $item['phone']
        )

        : 'Not provided';

    ?>

</td>


<td>

    <?php

    echo date(
        "d M Y",
        strtotime(
            $item['created_at']
        )
    );

    ?>

</td>


<td>

    <span class="issued-badge">

        <?php

        echo (int)
            $item['issued_books'];

        ?>

    </span>

</td>


</tr>


<?php } ?>


</tbody>

</table>

</div>


<?php } else { ?>


<div class="empty-report">

    <i class="bi bi-people"></i>

    <h5>
        No Users Found
    </h5>

    <p>
        No library users are currently registered.
    </p>

</div>


<?php } ?>


</div>


</div>

</div>


</body>

</html>