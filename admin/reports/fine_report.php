<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$search = trim($_GET['search'] ?? '');
$payment_status = $_GET['payment_status'] ?? '';

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

/*
|--------------------------------------------------------------------------
| Fine Report Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        issued_books.id,
        issued_books.issue_date,
        issued_books.return_date,
        issued_books.actual_return_date,
        issued_books.fine,
        issued_books.fine_paid,
        issued_books.payment_status,
        issued_books.payment_date,
        issued_books.status,

        books.title,
        books.author,

        users.name AS user_name,
        users.email AS user_email

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    INNER JOIN users
        ON issued_books.user_id = users.id

    WHERE issued_books.fine > 0
";

$params = [];
$types = "";

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR users.name LIKE ?
            OR users.email LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}

/*
|--------------------------------------------------------------------------
| Payment Status Filter
|--------------------------------------------------------------------------
*/

if (
    $payment_status === 'Paid' ||
    $payment_status === 'Unpaid'
) {

    $sql .= " AND issued_books.payment_status = ?";

    $params[] = $payment_status;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Date From
|--------------------------------------------------------------------------
*/

if ($date_from !== '') {

    $sql .= " AND issued_books.issue_date >= ?";

    $params[] = $date_from;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Date To
|--------------------------------------------------------------------------
*/

if ($date_to !== '') {

    $sql .= " AND issued_books.issue_date <= ?";

    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY issued_books.id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Fine report query failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$fineRecords = [];

while ($row = $result->fetch_assoc()) {
    $fineRecords[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$totalFine = 0;
$totalFinePaid = 0;
$totalFineUnpaid = 0;

$paidRecords = 0;
$unpaidRecords = 0;

foreach ($fineRecords as $record) {

    $fine = (float)($record['fine'] ?? 0);
    $finePaid = (float)($record['fine_paid'] ?? 0);

    $totalFine += $fine;
    $totalFinePaid += $finePaid;

    if (($record['payment_status'] ?? 'Unpaid') === 'Paid') {
        $paidRecords++;
    } else {
        $unpaidRecords++;
        $totalFineUnpaid += max(0, $fine - $finePaid);
    }
}

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
        Fine Report | Library Management System
    </title>

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

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >

    <!-- Admin CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >

    <style>

        .report-page {
            padding: 30px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .report-title h2 {
            margin: 0;
            color: #1e293b;
            font-size: 28px;
            font-weight: 700;
        }

        .report-title p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: #2563eb;
            color: #fff;
            padding: 10px 16px;
            border-radius: 9px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 18px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .05);
        }

        .summary-card .icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .summary-card:nth-child(2) .icon {
            background: #ecfdf5;
            color: #059669;
        }

        .summary-card:nth-child(3) .icon {
            background: #fef2f2;
            color: #dc2626;
        }

        .summary-card:nth-child(4) .icon {
            background: #fff7ed;
            color: #ea580c;
        }

        .summary-card h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }

        .summary-card p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .filter-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
        }

        .filter-card form {
            display: grid;
            grid-template-columns: 1.5fr 170px 150px 150px auto auto;
            gap: 12px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 13px;
            background: #fff;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .search-btn,
        .reset-btn {
            height: 42px;
            padding: 0 15px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            white-space: nowrap;
        }

        .search-btn {
            background: #2563eb;
            color: #fff;
        }

        .search-btn:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .reset-btn {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .reset-btn:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h4 {
            margin: 0;
            color: #1e293b;
            font-size: 17px;
            font-weight: 700;
        }

        .table-header span {
            color: #64748b;
            font-size: 13px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1250px;
        }

        .report-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 13px 15px;
            text-align: left;
            white-space: nowrap;
        }

        .report-table td {
            padding: 14px 15px;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .report-table tbody tr:hover {
            background: #f8fafc;
        }

        .book-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .book-author {
            color: #64748b;
            font-size: 11px;
        }

        .user-name {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .user-email {
            color: #64748b;
            font-size: 11px;
        }

        .fine-amount {
            font-weight: 700;
            color: #dc2626;
        }

        .paid-amount {
            font-weight: 700;
            color: #059669;
        }

        .unpaid-amount {
            font-weight: 700;
            color: #dc2626;
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .payment-paid {
            background: #ecfdf5;
            color: #059669;
        }

        .payment-unpaid {
            background: #fef2f2;
            color: #dc2626;
        }

        .empty-report {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-report i {
            display: block;
            font-size: 50px;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .empty-report h4 {
            color: #334155;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .report-footer {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 1200px) {

            .filter-card form {
                grid-template-columns: 1fr 1fr 1fr;
            }

        }

        @media (max-width: 900px) {

            .report-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-card form {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .report-page {
                padding: 20px 15px;
            }

            .report-title h2 {
                font-size: 23px;
            }

            .report-summary {
                grid-template-columns: 1fr;
            }

            .filter-card form {
                grid-template-columns: 1fr;
            }

        }

        @media print {

            .admin-sidebar,
            .sidebar,
            .navbar,
            .admin-navbar,
            .filter-card,
            .print-btn,
            .no-print {
                display: none !important;
            }

            .report-page {
                padding: 0;
            }

            .summary-card,
            .table-card {
                box-shadow: none;
            }

            .table-card {
                border: 1px solid #ddd;
            }

            .report-table {
                min-width: 0;
            }

            body {
                background: #fff !important;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- Admin Sidebar -->
    <?php include "../../includes/admin_sidebar.php"; ?>

    <div class="admin-main">

        <!-- Navbar -->
        <?php include "../../includes/navbar.php"; ?>

        <main class="report-page">

            <!-- Header -->

            <div class="report-header">

                <div class="report-title">

                    <h2>
                        <i class="bi bi-currency-rupee"></i>
                        Fine Report
                    </h2>

                    <p>
                        Track overdue fines and payment status.
                    </p>

                </div>

                <button
                    type="button"
                    class="print-btn no-print"
                    onclick="window.print()"
                >
                    <i class="bi bi-printer"></i>
                    Print Report
                </button>

            </div>


            <!-- Summary -->

            <div class="report-summary">

                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <h3>
                        ₹<?php echo number_format($totalFine, 2); ?>
                    </h3>

                    <p>
                        Total Fine
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <h3>
                        ₹<?php echo number_format($totalFinePaid, 2); ?>
                    </h3>

                    <p>
                        Fine Paid
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>

                    <h3>
                        ₹<?php echo number_format($totalFineUnpaid, 2); ?>
                    </h3>

                    <p>
                        Fine Unpaid
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <h3>
                        <?php echo count($fineRecords); ?>
                    </h3>

                    <p>
                        Fine Records
                    </p>

                </div>

            </div>


            <!-- Filters -->

            <div class="filter-card no-print">

                <form method="GET">

                    <div class="filter-group">

                        <label for="search">
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Book, author, user or email..."
                        >

                    </div>


                    <div class="filter-group">

                        <label for="payment_status">
                            Payment Status
                        </label>

                        <select
                            id="payment_status"
                            name="payment_status"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="Paid"
                                <?php
                                echo $payment_status === 'Paid'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Paid
                            </option>

                            <option
                                value="Unpaid"
                                <?php
                                echo $payment_status === 'Unpaid'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Unpaid
                            </option>

                        </select>

                    </div>


                    <div class="filter-group">

                        <label for="date_from">
                            Issue Date From
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?php echo htmlspecialchars($date_from); ?>"
                        >

                    </div>


                    <div class="filter-group">

                        <label for="date_to">
                            Issue Date To
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?php echo htmlspecialchars($date_to); ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        class="search-btn"
                    >
                        <i class="bi bi-search"></i>
                        Search
                    </button>


                    <a
                        href="fine_report.php"
                        class="reset-btn"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                        Reset
                    </a>

                </form>

            </div>


            <!-- Fine Table -->

            <div class="table-card">

                <div class="table-header">

                    <h4>
                        Fine Records
                    </h4>

                    <span>
                        <?php echo count($fineRecords); ?>
                        records found
                    </span>

                </div>


                <?php if (empty($fineRecords)): ?>

                    <div class="empty-report">

                        <i class="bi bi-cash"></i>

                        <h4>
                            No Fine Records Found
                        </h4>

                        <p>
                            No fine records match your filter criteria.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

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
                                    <th>Fine Paid</th>
                                    <th>Remaining</th>
                                    <th>Payment Status</th>
                                    <th>Payment Date</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($fineRecords as $index => $record): ?>

                                <?php

                                $fine =
                                    (float)($record['fine'] ?? 0);

                                $finePaid =
                                    (float)($record['fine_paid'] ?? 0);

                                $remaining =
                                    max(
                                        0,
                                        $fine - $finePaid
                                    );

                                ?>

                                <tr>

                                    <td>
                                        <?php echo $index + 1; ?>
                                    </td>


                                    <td>

                                        <div class="book-title">

                                            <?php
                                            echo htmlspecialchars(
                                                $record['title']
                                            );
                                            ?>

                                        </div>

                                        <div class="book-author">

                                            <?php
                                            echo htmlspecialchars(
                                                $record['author']
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="user-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $record['user_name']
                                            );
                                            ?>

                                        </div>

                                        <div class="user-email">

                                            <?php
                                            echo htmlspecialchars(
                                                $record['user_email']
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $record['issue_date']
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $record['return_date']
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'actual_return_date'
                                                ]
                                            )
                                        ): ?>

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $record[
                                                        'actual_return_date'
                                                    ]
                                                )
                                            );
                                            ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <span class="fine-amount">

                                            ₹<?php
                                            echo number_format(
                                                $fine,
                                                2
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="paid-amount">

                                            ₹<?php
                                            echo number_format(
                                                $finePaid,
                                                2
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($remaining > 0): ?>

                                            <span class="unpaid-amount">

                                                ₹<?php
                                                echo number_format(
                                                    $remaining,
                                                    2
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="paid-amount">

                                                ₹0.00

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            ($record['payment_status']
                                                ?? 'Unpaid') === 'Paid'
                                        ): ?>

                                            <span
                                                class="payment-badge payment-paid"
                                            >

                                                <i
                                                    class="bi bi-check-circle-fill"
                                                ></i>

                                                Paid

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="payment-badge payment-unpaid"
                                            >

                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>

                                                Unpaid

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $record['payment_date']
                                            )
                                        ): ?>

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $record[
                                                        'payment_date'
                                                    ]
                                                )
                                            );
                                            ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <div class="report-footer">

                        Generated on
                        <?php echo date("d M Y h:i A"); ?>

                        &nbsp; | &nbsp;

                        Library Management System

                        &nbsp; | &nbsp;

                        Records:
                        <?php echo count($fineRecords); ?>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>