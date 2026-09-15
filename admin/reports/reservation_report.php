<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| DOMPDF
|--------------------------------------------------------------------------
*/

$autoloadPath = "../../vendor/autoload.php";

if (!file_exists($autoloadPath)) {

    die(
        "Dompdf not found. Please run: composer require dompdf/dompdf"
    );

}

require_once $autoloadPath;


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status = $_GET['status'] ?? '';

$date_from = $_GET['date_from'] ?? '';

$date_to = $_GET['date_to'] ?? '';

$download = $_GET['download'] ?? '';

$report_type = $_GET['report_type'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

if (
    $status !== 'Pending' &&
    $status !== 'Approved' &&
    $status !== 'Cancelled'
) {

    $status = '';

}


/*
|--------------------------------------------------------------------------
| VALIDATE DATES
|--------------------------------------------------------------------------
*/

if (
    $date_from !== '' &&
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date_from
    )
) {

    $date_from = '';

}


if (
    $date_to !== '' &&
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date_to
    )
) {

    $date_to = '';

}


/*
|--------------------------------------------------------------------------
| RESERVATION REPORT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        reservations.id,
        reservations.reservation_date,
        reservations.status,

        books.title,
        books.author,
        books.isbn,
        books.available_quantity,

        users.name AS user_name,
        users.email AS user_email,

        categories.category_name

    FROM reservations

    INNER JOIN books
        ON reservations.book_id = books.id

    INNER JOIN users
        ON reservations.user_id = users.id

    LEFT JOIN categories
        ON books.category_id = categories.id

    WHERE 1 = 1
";

$params = [];

$types = "";


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
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

    $searchValue =
        "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= "
        AND reservations.status = ?
    ";

    $params[] = $status;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| DATE FROM
|--------------------------------------------------------------------------
*/

if ($date_from !== '') {

    $sql .= "
        AND DATE(reservations.reservation_date) >= ?
    ";

    $params[] = $date_from;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| DATE TO
|--------------------------------------------------------------------------
*/

if ($date_to !== '') {

    $sql .= "
        AND DATE(reservations.reservation_date) <= ?
    ";

    $params[] = $date_to;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY reservations.id DESC
";


/*
|--------------------------------------------------------------------------
| PREPARE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Reservation report query failed: " .
        htmlspecialchars($conn->error)
    );

}


/*
|--------------------------------------------------------------------------
| BIND
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt->execute();

$result =
    $stmt->get_result();

$reservationRecords = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $reservationRecords[] =
        $row;

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalReservations =
    count($reservationRecords);

$totalPending = 0;

$totalApproved = 0;

$totalCancelled = 0;


foreach (
    $reservationRecords
    as $record
) {

    if (
        $record['status']
        === 'Pending'
    ) {

        $totalPending++;

    }

    if (
        $record['status']
        === 'Approved'
    ) {

        $totalApproved++;

    }

    if (
        $record['status']
        === 'Cancelled'
    ) {

        $totalCancelled++;

    }

}


/*
|--------------------------------------------------------------------------
| DOWNLOAD PDF
|--------------------------------------------------------------------------
|
| report_type = all
| report_type = status
|
*/

if (
    $download === 'pdf'
) {


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS PDF
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'status' &&
        $status === ''
    ) {

        header(
            "Location: reservation_report.php?error=select_status"
        );

        exit();

    }


    /*
    |--------------------------------------------------------------------------
    | PDF RECORDS
    |--------------------------------------------------------------------------
    */

    $pdfRecords = [];


    /*
    |--------------------------------------------------------------------------
    | ALL RESERVATION PDF
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'all'
    ) {


        $allSql = "

            SELECT
                reservations.id,
                reservations.reservation_date,
                reservations.status,

                books.title,
                books.author,
                books.isbn,
                books.available_quantity,

                users.name AS user_name,
                users.email AS user_email,

                categories.category_name

            FROM reservations

            INNER JOIN books
                ON reservations.book_id =
                   books.id

            INNER JOIN users
                ON reservations.user_id =
                   users.id

            LEFT JOIN categories
                ON books.category_id =
                   categories.id

            ORDER BY
                reservations.id DESC

        ";


        $allResult =
            $conn->query(
                $allSql
            );


        if ($allResult) {

            while (
                $row =
                $allResult->fetch_assoc()
            ) {

                $pdfRecords[] =
                    $row;

            }

        }


        $pdfTitle =
            "All Reservation Report";


        $pdfSubTitle =
            "Complete Library Reservation Report";

    }


    /*
    |--------------------------------------------------------------------------
    | SELECTED STATUS PDF
    |--------------------------------------------------------------------------
    */

    else {


        $statusSql = "

            SELECT
                reservations.id,
                reservations.reservation_date,
                reservations.status,

                books.title,
                books.author,
                books.isbn,
                books.available_quantity,

                users.name AS user_name,
                users.email AS user_email,

                categories.category_name

            FROM reservations

            INNER JOIN books
                ON reservations.book_id =
                   books.id

            INNER JOIN users
                ON reservations.user_id =
                   users.id

            LEFT JOIN categories
                ON books.category_id =
                   categories.id

            WHERE reservations.status = ?

            ORDER BY
                reservations.id DESC

        ";


        $statusStmt =
            $conn->prepare(
                $statusSql
            );


        if (!$statusStmt) {

            die(
                "Status PDF query failed: " .
                htmlspecialchars(
                    $conn->error
                )
            );

        }


        $statusStmt->bind_param(
            "s",
            $status
        );


        $statusStmt->execute();


        $statusResult =
            $statusStmt->get_result();


        while (
            $row =
            $statusResult->fetch_assoc()
        ) {

            $pdfRecords[] =
                $row;

        }


        $statusStmt->close();


        $pdfTitle =
            $status .
            " Reservation Report";


        $pdfSubTitle =
            "Selected Reservation Status Report";

    }


    /*
    |--------------------------------------------------------------------------
    | PDF SUMMARY
    |--------------------------------------------------------------------------
    */

    $pdfTotalReservations =
        count($pdfRecords);

    $pdfPending = 0;

    $pdfApproved = 0;

    $pdfCancelled = 0;


    foreach (
        $pdfRecords
        as $record
    ) {


        if (
            $record['status']
            === 'Pending'
        ) {

            $pdfPending++;

        }


        if (
            $record['status']
            === 'Approved'
        ) {

            $pdfApproved++;

        }


        if (
            $record['status']
            === 'Cancelled'
        ) {

            $pdfCancelled++;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DOMPDF OPTIONS
    |--------------------------------------------------------------------------
    */

    $options =
        new \Dompdf\Options();


    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );


    $options->set(
        'isRemoteEnabled',
        true
    );


    $dompdf =
        new \Dompdf\Dompdf(
            $options
        );


    /*
    |--------------------------------------------------------------------------
    | SAFE TITLE
    |--------------------------------------------------------------------------
    */

    $safePdfTitle =
        htmlspecialchars(
            $pdfTitle,
            ENT_QUOTES,
            'UTF-8'
        );


    $safePdfSubTitle =
        htmlspecialchars(
            $pdfSubTitle,
            ENT_QUOTES,
            'UTF-8'
        );


    /*
    |--------------------------------------------------------------------------
    | PDF HTML
    |--------------------------------------------------------------------------
    */

    $pdfHtml = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 28px;
}

body {
    font-family:
        DejaVu Sans,
        sans-serif;

    color: #1e293b;

    font-size: 8px;
}

.header {
    text-align: center;
    margin-bottom: 18px;
}

.header h1 {
    margin: 0;
    font-size: 20px;
    font-weight: bold;
}

.header h2 {
    margin: 5px 0 0;
    font-size: 14px;
    color: #2563eb;
    font-weight: bold;
}

.header h3 {
    margin: 5px 0 0;
    font-size: 10px;
    color: #64748b;
    font-weight: normal;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.info-table td {
    border: 1px solid #dbe3ed;
    padding: 7px;
}

.info-label {
    width: 23%;
    background: #f8fafc;
    font-weight: bold;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}

.summary td {
    width: 25%;
    text-align: center;
    padding: 9px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
}

.summary-number {
    display: block;
    font-size: 16px;
    font-weight: bold;
    color: #2563eb;
}

.summary-label {
    display: block;
    margin-top: 3px;
    font-size: 8px;
    color: #475569;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #1d4ed8;
    padding: 6px;
    font-size: 7px;
    text-align: left;
}

.report-table td {
    border: 1px solid #dfe5ec;
    padding: 6px;
    font-size: 7px;
}

.report-table tr:nth-child(even) td {
    background: #f8fafc;
}

.pending {
    color: #ea580c;
    font-weight: bold;
}

.approved {
    color: #059669;
    font-weight: bold;
}

.cancelled {
    color: #dc2626;
    font-weight: bold;
}

.available {
    color: #059669;
    font-weight: bold;
}

.not-available {
    color: #dc2626;
    font-weight: bold;
}

.footer {
    margin-top: 18px;
    text-align: center;
    color: #64748b;
    font-size: 7px;
}

.no-data {
    text-align: center;
    padding: 14px;
}

</style>

</head>

<body>


<div class="header">

    <h1>
        Library Management System
    </h1>

    <h2>
        ' . $safePdfTitle . '
    </h2>

    <h3>
        ' . $safePdfSubTitle . '
    </h3>

</div>


<table class="info-table">

    <tr>

        <td class="info-label">
            Report Type
        </td>

        <td>
';


    if (
        $report_type === 'all'
    ) {

        $pdfHtml .= '
            All Reservation Records
        ';

    } else {

        $pdfHtml .= '
            Selected Reservation Status
        ';

    }


    $pdfHtml .= '

        </td>

    </tr>
';


    /*
    |--------------------------------------------------------------------------
    | SELECTED STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'status'
    ) {

        $pdfHtml .= '

    <tr>

        <td class="info-label">
            Status
        </td>

        <td>
            ' .
            htmlspecialchars(
                $status,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
        </td>

    </tr>

';

    }


    /*
    |--------------------------------------------------------------------------
    | GENERATED DATE
    |--------------------------------------------------------------------------
    */

    $pdfHtml .= '

    <tr>

        <td class="info-label">
            Generated On
        </td>

        <td>
            ' .
            date(
                "d M Y h:i A"
            ) .
            '
        </td>

    </tr>

</table>


<table class="summary">

<tr>


    <td>

        <span class="summary-number">
            ' .
            $pdfTotalReservations .
            '
        </span>

        <span class="summary-label">
            Total Reservations
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $pdfPending .
            '
        </span>

        <span class="summary-label">
            Pending
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $pdfApproved .
            '
        </span>

        <span class="summary-label">
            Approved
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $pdfCancelled .
            '
        </span>

        <span class="summary-label">
            Cancelled
        </span>

    </td>


</tr>

</table>


<table class="report-table">

<thead>

<tr>

    <th>#</th>

    <th>Book</th>

    <th>User</th>

    <th>Category</th>

    <th>ISBN</th>

    <th>Reservation Date</th>

    <th>Availability</th>

    <th>Status</th>

</tr>

</thead>

<tbody>
';


    /*
    |--------------------------------------------------------------------------
    | PDF RECORDS
    |--------------------------------------------------------------------------
    */

    if (
        empty($pdfRecords)
    ) {

        $pdfHtml .= '

<tr>

    <td
        colspan="8"
        class="no-data"
    >

        No reservation records found.

    </td>

</tr>

';

    } else {


        foreach (
            $pdfRecords
            as $index =>
            $record
        ) {


            /*
            |--------------------------------------------------------------
            | Reservation Date
            |--------------------------------------------------------------
            */

            $reservationDate =
                !empty(
                    $record[
                        'reservation_date'
                    ]
                )

                    ? date(
                        "d M Y h:i A",
                        strtotime(
                            $record[
                                'reservation_date'
                            ]
                        )
                    )

                    : 'N/A';


            /*
            |--------------------------------------------------------------
            | Status Class
            |--------------------------------------------------------------
            */

            if (
                $record['status']
                === 'Pending'
            ) {

                $statusClass =
                    'pending';

            } elseif (
                $record['status']
                === 'Approved'
            ) {

                $statusClass =
                    'approved';

            } else {

                $statusClass =
                    'cancelled';

            }


            /*
            |--------------------------------------------------------------
            | Availability
            |--------------------------------------------------------------
            */

            $isAvailable =
                (int)(
                    $record[
                        'available_quantity'
                    ]
                    ?? 0
                ) > 0;


            $availabilityClass =
                $isAvailable
                    ? 'available'
                    : 'not-available';


            $availabilityText =
                $isAvailable
                    ? 'Available'
                    : 'Not Available';


            /*
            |--------------------------------------------------------------
            | PDF ROW
            |--------------------------------------------------------------
            */

            $pdfHtml .= '

<tr>


    <td>
        ' .
        ($index + 1) .
        '
    </td>


    <td>

        <strong>
            ' .
            htmlspecialchars(
                $record['title'],
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
        </strong>

        <br>

        ' .
        htmlspecialchars(
            $record['author'],
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

    </td>


    <td>

        ' .
        htmlspecialchars(
            $record['user_name'],
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

        <br>

        ' .
        htmlspecialchars(
            $record['user_email'],
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

    </td>


    <td>

        ' .
        htmlspecialchars(
            $record[
                'category_name'
            ]
            ?? 'N/A',
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

    </td>


    <td>

        ' .
        htmlspecialchars(
            $record['isbn']
            ?? 'N/A',
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

    </td>


    <td>

        ' .
        $reservationDate .
        '

    </td>


    <td class="' .
        $availabilityClass .
        '">

        ' .
        $availabilityText .
        '

    </td>


    <td class="' .
        $statusClass .
        '">

        ' .
        htmlspecialchars(
            $record['status'],
            ENT_QUOTES,
            'UTF-8'
        ) .
        '

    </td>


</tr>

';
        }

    }


    $pdfHtml .= '

</tbody>

</table>


<div class="footer">

    Generated on
    ' .
    date(
        "d M Y h:i A"
    ) .
    '

    |

    Library Management System

</div>


</body>

</html>
';


    /*
    |--------------------------------------------------------------------------
    | CREATE PDF
    |--------------------------------------------------------------------------
    */

    $dompdf->loadHtml(
        $pdfHtml,
        'UTF-8'
    );


    $dompdf->setPaper(
        'A4',
        'landscape'
    );


    $dompdf->render();


    /*
    |--------------------------------------------------------------------------
    | FILE NAME
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'status'
    ) {


        $safeStatus =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $status
            );


        $fileName =
            $safeStatus .
            '_reservation_report_' .
            date(
                'Y-m-d_H-i-s'
            ) .
            '.pdf';


    } else {


        $fileName =
            'all_reservation_report_' .
            date(
                'Y-m-d_H-i-s'
            ) .
            '.pdf';

    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF
    |--------------------------------------------------------------------------
    */

    $dompdf->stream(
        $fileName,
        [
            'Attachment' => true
        ]
    );


    exit();

}


/*
|--------------------------------------------------------------------------
| PDF URLS
|--------------------------------------------------------------------------
*/


/*
| ALL PDF
*/

$allPdfQuery =
    http_build_query(
        [
            'download' => 'pdf',
            'report_type' => 'all'
        ]
    );


/*
| STATUS PDF
*/

$statusPdfQuery =
    http_build_query(
        [
            'download' => 'pdf',
            'report_type' => 'status',
            'status' => $status
        ]
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
        Reservation Report | Library Management System
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

/* =========================================================
   PAGE
========================================================= */

.report-page {
    padding: 30px;
}


/* =========================================================
   ADMIN NAVBAR
========================================================= */

.admin-navbar {

    height: 72px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow:
        0 2px 10px
        rgba(15, 23, 42, 0.04);

}


.navbar-left {

    display: flex;
    align-items: center;

}


.navbar-title h5 {

    margin: 0;
    color: #1e293b;
    font-size: 17px;
    font-weight: 700;

}


.navbar-title span {

    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 4px;
    color: #64748b;
    font-size: 11px;

}


.navbar-title span i {

    font-size: 10px;

}


.navbar-right {

    display: flex;
    align-items: center;
    gap: 10px;

}


.notification-btn,
.theme-toggle-btn {

    width: 40px;
    height: 40px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s ease;
    padding: 0;

}


.notification-btn:hover,
.theme-toggle-btn:hover {

    background: #f8fafc;
    color: #2563eb;
    border-color: #cbd5e1;

}


.header-divider {

    width: 1px;
    height: 34px;
    background: #e2e8f0;
    margin: 0 5px;

}


.nav-admin {

    display: flex;
    align-items: center;
    gap: 10px;

}


.nav-avatar {

    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f0f6ff;
    color: #2563eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex: 0 0 40px;
    box-shadow:
        0 3px 10px
        rgba(37,99,235,.12);

}


.nav-avatar i {

    color: #2563eb;
    font-size: 15px;

}


.nav-admin-info {

    display: flex;
    flex-direction: column;
    line-height: 1.1;

}


.nav-admin-info strong {

    color: #1e293b;
    font-size: 12px;
    font-weight: 700;

}


.nav-admin-info small {

    color: #64748b;
    font-size: 10px;
    margin-top: 3px;

}


.admin-logout-btn {

    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 13px;
    border-radius: 8px;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;

}


.admin-logout-btn:hover {

    background: #fee2e2;
    color: #b91c1c;

}


/* =========================================================
   REPORT HEADER
========================================================= */

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


/* =========================================================
   PDF ACTIONS
========================================================= */

.report-actions {

    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;

}


.download-btn,
.print-btn {

    min-height: 42px;
    padding: 0 15px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    transition: .2s ease;

}


.download-all-btn {

    background: #2563eb;
    border: 1px solid #2563eb;
    color: #ffffff;

}


.download-all-btn:hover {

    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #ffffff;

}


.download-status-btn {

    background: #059669;
    border: 1px solid #059669;
    color: #ffffff;

}


.download-status-btn:hover {

    background: #047857;
    border-color: #047857;
    color: #ffffff;

}


.download-disabled {

    background: #cbd5e1;
    border: 1px solid #cbd5e1;
    color: #64748b;
    cursor: not-allowed;

}


.print-btn {

    background: #475569;
    border: 1px solid #475569;
    color: #ffffff;
    cursor: pointer;

}


.print-btn:hover {

    background: #334155;
    border-color: #334155;
    color: #ffffff;

}


/* =========================================================
   SUMMARY
========================================================= */

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
    box-shadow:
        0 4px 15px
        rgba(15,23,42,.05);

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

    background: #fff7ed;
    color: #ea580c;

}


.summary-card:nth-child(3) .icon {

    background: #ecfdf5;
    color: #059669;

}


.summary-card:nth-child(4) .icon {

    background: #fef2f2;
    color: #dc2626;

}


.summary-card h3 {

    margin: 0;
    font-size: 25px;
    font-weight: 700;
    color: #1e293b;

}


.summary-card p {

    margin: 4px 0 0;
    color: #64748b;
    font-size: 13px;

}


/* =========================================================
   FILTER
========================================================= */

.filter-card {

    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow:
        0 4px 15px
        rgba(15,23,42,.04);

}


.filter-card form {

    display: grid;
    grid-template-columns:
        1.5fr
        170px
        150px
        150px
        auto
        auto;
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
    color: #334155;

}


.filter-group input:focus,
.filter-group select:focus {

    border-color: #2563eb;
    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.10);

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
    cursor: pointer;

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


.selected-status-info {

    margin-top: 14px;
    padding: 10px 13px;
    border-radius: 8px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;

}


/* =========================================================
   TABLE
========================================================= */

.table-card {

    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    overflow: hidden;
    box-shadow:
        0 4px 15px
        rgba(15,23,42,.04);

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
    min-width: 1200px;

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


.category-badge {

    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 20px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 11px;
    font-weight: 700;

}


.availability-badge {

    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;

}


.availability-yes {

    background: #ecfdf5;
    color: #059669;

}


.availability-no {

    background: #fef2f2;
    color: #dc2626;

}


.status-badge {

    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;

}


.status-pending {

    background: #fff7ed;
    color: #ea580c;

}


.status-approved {

    background: #ecfdf5;
    color: #059669;

}


.status-cancelled {

    background: #fef2f2;
    color: #dc2626;

}


/* =========================================================
   EMPTY
========================================================= */

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


/* =========================================================
   FOOTER
========================================================= */

.report-footer {

    padding: 15px 20px;
    border-top: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 12px;

}


/* =========================================================
   DARK MODE
========================================================= */

body.library-dark-mode {

    background: #0f172a !important;
    color: #e2e8f0 !important;

}


body.library-dark-mode .admin-main {

    background: #0f172a !important;

}


body.library-dark-mode .admin-navbar {

    background: #1e293b !important;
    border-bottom-color: #334155 !important;

}


body.library-dark-mode .navbar-title h5 {

    color: #f8fafc !important;

}


body.library-dark-mode .navbar-title span,
body.library-dark-mode .navbar-title span i {

    color: #94a3b8 !important;

}


body.library-dark-mode .notification-btn,
body.library-dark-mode .theme-toggle-btn {

    background: #273449 !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;

}


body.library-dark-mode .theme-toggle-btn {

    color: #facc15 !important;

}


body.library-dark-mode .header-divider {

    background: #475569 !important;

}


body.library-dark-mode .nav-avatar {

    background: #334155 !important;
    color: #93c5fd !important;

}


body.library-dark-mode .nav-avatar i {

    color: #93c5fd !important;

}


body.library-dark-mode .nav-admin-info strong {

    color: #f8fafc !important;

}


body.library-dark-mode .nav-admin-info small {

    color: #94a3b8 !important;

}


body.library-dark-mode .admin-logout-btn {

    background: #3f1d2a !important;
    border-color: #7f1d3c !important;
    color: #fb7185 !important;

}


body.library-dark-mode .report-page {

    background: #0f172a !important;

}


body.library-dark-mode .report-title h2 {

    color: #f8fafc !important;

}


body.library-dark-mode .report-title p {

    color: #94a3b8 !important;

}


body.library-dark-mode .summary-card,
body.library-dark-mode .filter-card,
body.library-dark-mode .table-card {

    background: #1e293b !important;
    border-color: #334155 !important;

}


body.library-dark-mode .summary-card h3 {

    color: #f8fafc !important;

}


body.library-dark-mode .summary-card p {

    color: #94a3b8 !important;

}


body.library-dark-mode .summary-card .icon {

    background: #273449 !important;
    color: #60a5fa !important;

}


body.library-dark-mode .filter-group label {

    color: #cbd5e1 !important;

}


body.library-dark-mode .filter-group input,
body.library-dark-mode .filter-group select {

    background: #111827 !important;
    color: #f8fafc !important;
    border-color: #475569 !important;

}


body.library-dark-mode .filter-group input::placeholder {

    color: #94a3b8 !important;

}


body.library-dark-mode .selected-status-info {

    background: #172554 !important;
    border-color: #1e40af !important;
    color: #93c5fd !important;

}


body.library-dark-mode .reset-btn {

    background: #334155 !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;

}


body.library-dark-mode .table-header {

    border-bottom-color: #334155 !important;

}


body.library-dark-mode .table-header h4 {

    color: #f8fafc !important;

}


body.library-dark-mode .table-header span {

    color: #94a3b8 !important;

}


body.library-dark-mode .report-table th {

    background: #273449 !important;
    color: #f8fafc !important;

}


body.library-dark-mode .report-table td {

    background: #1e293b !important;
    color: #cbd5e1 !important;
    border-top-color: #334155 !important;

}


body.library-dark-mode
.report-table tbody tr:hover td {

    background: #273449 !important;

}


body.library-dark-mode .book-title,
body.library-dark-mode .user-name {

    color: #f8fafc !important;

}


body.library-dark-mode .book-author,
body.library-dark-mode .user-email {

    color: #94a3b8 !important;

}


body.library-dark-mode .category-badge {

    background: #273449 !important;
    color: #93c5fd !important;

}


body.library-dark-mode .availability-yes {

    background: #064e3b !important;
    color: #6ee7b7 !important;

}


body.library-dark-mode .availability-no {

    background: #3f1d2a !important;
    color: #fb7185 !important;

}


body.library-dark-mode .status-pending {

    background: #7c2d12 !important;
    color: #fdba74 !important;

}


body.library-dark-mode .status-approved {

    background: #064e3b !important;
    color: #6ee7b7 !important;

}


body.library-dark-mode .status-cancelled {

    background: #3f1d2a !important;
    color: #fb7185 !important;

}


body.library-dark-mode .empty-report {

    color: #94a3b8 !important;

}


body.library-dark-mode .empty-report h4 {

    color: #f8fafc !important;

}


body.library-dark-mode .report-footer {

    border-top-color: #334155 !important;
    color: #94a3b8 !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .filter-card form {

        grid-template-columns:
            1fr
            1fr
            1fr;

    }

}


@media (max-width: 1000px) {

    .report-summary {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .filter-card form {

        grid-template-columns:
            1fr 1fr;

    }

}


@media (max-width: 800px) {

    .admin-navbar {

        padding: 0 15px;

    }


    .nav-admin-info {

        display: none;

    }


    .admin-logout-btn span {

        display: none;

    }


    .admin-logout-btn {

        width: 40px;
        padding: 0;

    }


    .report-actions {

        width: 100%;
        justify-content: flex-start;

    }

}


@media (max-width: 600px) {

    .report-page {

        padding: 20px 15px;

    }


    .admin-navbar {

        min-height: 68px;

    }


    .navbar-title span {

        display: none;

    }


    .notification-btn,
    .theme-toggle-btn,
    .admin-logout-btn {

        width: 37px;
        height: 37px;
        flex-basis: 37px;

    }


    .nav-avatar {

        width: 34px;
        height: 34px;
        flex-basis: 34px;

    }


    .report-title h2 {

        font-size: 23px;

    }


    .report-summary {

        grid-template-columns:
            1fr;

    }


    .filter-card form {

        grid-template-columns:
            1fr;

    }


    .report-actions {

        flex-direction: column;
        align-items: stretch;

    }


    .download-btn,
    .print-btn {

        width: 100%;

    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    .admin-sidebar,
    .sidebar,
    .navbar,
    .admin-navbar,
    .filter-card,
    .report-actions,
    .download-btn,
    .print-btn,
    .no-print {

        display: none !important;

    }


    .report-page {

        padding: 0;

    }


    .summary-card,
    .table-card {

        box-shadow: none !important;

    }


    .table-card {

        border: 1px solid #ddd;

    }


    .report-table {

        min-width: 0;

    }


    body {

        background: #ffffff !important;

    }

}

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- SIDEBAR -->

    <?php include "../../includes/admin_sidebar.php"; ?>


    <div class="admin-main">


        <!-- =====================================================
             NAVBAR
        ====================================================== -->

        <nav class="admin-navbar">


            <div class="navbar-left">

                <div class="navbar-title">

                    <h5>
                        Admin Dashboard
                    </h5>


                    <span>

                        <i class="bi bi-house-door"></i>

                        Home

                        <i class="bi bi-chevron-right"></i>

                        Reports

                        <i class="bi bi-chevron-right"></i>

                        Reservation Report

                    </span>

                </div>

            </div>


            <div class="navbar-right">


                <!-- NOTIFICATION -->

                <button
                    type="button"
                    class="notification-btn"
                    title="Notifications"
                    aria-label="Notifications"
                >

                    <i class="bi bi-bell"></i>

                </button>


                <!-- THEME -->

                <button
                    type="button"
                    id="adminThemeToggle"
                    class="theme-toggle-btn"
                    title="Switch to Dark Mode"
                    aria-label="Switch to Dark Mode"
                >

                    <i class="bi bi-moon-fill"></i>

                </button>


                <div class="header-divider"></div>


                <!-- PROFILE -->

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


                <!-- LOGOUT -->

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
             MAIN CONTENT
        ====================================================== -->

        <main class="report-page">


            <!-- HEADER -->

            <div class="report-header">


                <div class="report-title">

                    <h2>
                        Reservation Report
                    </h2>

                    <p>
                        Download all reservations or selected status reservations as PDF.
                    </p>

                </div>


                <!-- =================================================
                     TWO PDF OPTIONS
                ================================================== -->

                <div class="report-actions">


                    <!-- ALL PDF -->

                    <a
                        href="?<?php
                        echo htmlspecialchars(
                            $allPdfQuery
                        );
                        ?>"
                        class="
                            download-btn
                            download-all-btn
                            no-print
                        "
                        title="Download all reservation records"
                    >

                        <i
                            class="
                                bi
                                bi-file-earmark-pdf-fill
                            "
                        ></i>

                        Download All PDF

                    </a>


                    <!-- SELECTED STATUS PDF -->

                    <?php if (
                        $status === 'Pending' ||
                        $status === 'Approved' ||
                        $status === 'Cancelled'
                    ): ?>


                        <a
                            href="?<?php
                            echo htmlspecialchars(
                                $statusPdfQuery
                            );
                            ?>"
                            class="
                                download-btn
                                download-status-btn
                                no-print
                            "
                            title="Download selected status PDF"
                        >

                            <i
                                class="
                                    bi
                                    bi-filter-circle-fill
                                "
                            ></i>

                            Download
                            <?php
                            echo htmlspecialchars(
                                $status
                            );
                            ?>
                            PDF

                        </a>


                    <?php else: ?>


                        <span
                            class="
                                download-btn
                                download-disabled
                                no-print
                            "
                            title="Select a status first"
                        >

                            <i
                                class="
                                    bi
                                    bi-filter-circle
                                "
                            ></i>

                            Select Status PDF

                        </span>


                    <?php endif; ?>


                   

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="report-summary">


                <!-- TOTAL -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-bookmark-check
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalReservations;
                        ?>

                    </h3>


                    <p>
                        Total Reservations
                    </p>

                </div>


                <!-- PENDING -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-hourglass-split
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalPending;
                        ?>

                    </h3>


                    <p>
                        Pending
                    </p>

                </div>


                <!-- APPROVED -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-check-circle-fill
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalApproved;
                        ?>

                    </h3>


                    <p>
                        Approved
                    </p>

                </div>


                <!-- CANCELLED -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-x-circle-fill
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalCancelled;
                        ?>

                    </h3>


                    <p>
                        Cancelled
                    </p>

                </div>


            </div>


            <!-- =================================================
                 FILTER
            ================================================== -->

            <div
                class="
                    filter-card
                    no-print
                "
            >


                <form method="GET">


                    <!-- SEARCH -->

                    <div class="filter-group">

                        <label for="search">
                            Search
                        </label>


                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php

                            echo htmlspecialchars(
                                $search
                            );

                            ?>"
                            placeholder="Book, author, user or email..."
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="filter-group">

                        <label for="status">
                            Status
                        </label>


                        <select
                            id="status"
                            name="status"
                        >

                            <option value="">
                                All Status
                            </option>


                            <option
                                value="Pending"
                                <?php

                                echo
                                    $status === 'Pending'
                                        ? 'selected'
                                        : '';

                                ?>
                            >
                                Pending
                            </option>


                            <option
                                value="Approved"
                                <?php

                                echo
                                    $status === 'Approved'
                                        ? 'selected'
                                        : '';

                                ?>
                            >
                                Approved
                            </option>


                            <option
                                value="Cancelled"
                                <?php

                                echo
                                    $status === 'Cancelled'
                                        ? 'selected'
                                        : '';

                                ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <!-- DATE FROM -->

                    <div class="filter-group">

                        <label
                            for="date_from"
                        >
                            Date From
                        </label>


                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?php

                            echo htmlspecialchars(
                                $date_from
                            );

                            ?>"
                        >

                    </div>


                    <!-- DATE TO -->

                    <div class="filter-group">

                        <label
                            for="date_to"
                        >
                            Date To
                        </label>


                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?php

                            echo htmlspecialchars(
                                $date_to
                            );

                            ?>"
                        >

                    </div>


                    <!-- SEARCH -->

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i
                            class="bi bi-search"
                        ></i>

                        Search

                    </button>


                    <!-- RESET -->

                    <a
                        href="reservation_report.php"
                        class="reset-btn"
                    >

                        <i
                            class="
                                bi
                                bi-arrow-clockwise
                            "
                        ></i>

                        Reset

                    </a>


                </form>


                <!-- SELECTED STATUS INFO -->

                <?php if (
                    $status === 'Pending' ||
                    $status === 'Approved' ||
                    $status === 'Cancelled'
                ): ?>


                    <div
                        class="
                            selected-status-info
                        "
                    >

                        <i
                            class="
                                bi
                                bi-check-circle-fill
                            "
                        ></i>

                        Selected Status:

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $status
                            );
                            ?>

                        </strong>

                        — Status PDF contains only
                        <?php
                        echo htmlspecialchars(
                            $status
                        );
                        ?>
                        reservations.

                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-header">

                    <h4>
                        Reservation Records
                    </h4>


                    <span>

                        <?php

                        echo count(
                            $reservationRecords
                        );

                        ?>

                        records found

                    </span>

                </div>


                <?php if (
                    empty($reservationRecords)
                ): ?>


                    <div
                        class="empty-report"
                    >

                        <i
                            class="
                                bi
                                bi-bookmark-x
                            "
                        ></i>


                        <h4>
                            No Reservation Records Found
                        </h4>


                        <p>
                            No reservations match your filter criteria.
                        </p>

                    </div>


                <?php else: ?>


                    <div
                        class="
                            table-responsive
                        "
                    >


                        <table
                            class="
                                report-table
                            "
                        >


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
                                        Category
                                    </th>

                                    <th>
                                        ISBN
                                    </th>

                                    <th>
                                        Reservation Date
                                    </th>

                                    <th>
                                        Availability
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $reservationRecords
                                as $index =>
                                $record
                            ): ?>


                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <?php
                                        echo $index + 1;
                                        ?>

                                    </td>


                                    <!-- BOOK -->

                                    <td>

                                        <div
                                            class="
                                                book-title
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record[
                                                    'title'
                                                ]
                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="
                                                book-author
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record[
                                                    'author'
                                                ]
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <!-- USER -->

                                    <td>

                                        <div
                                            class="
                                                user-name
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record[
                                                    'user_name'
                                                ]
                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="
                                                user-email
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record[
                                                    'user_email'
                                                ]
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span
                                            class="
                                                category-badge
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record[
                                                    'category_name'
                                                ]
                                                ?? 'N/A'
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- ISBN -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $record[
                                                'isbn'
                                            ]
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?php

                                        echo date(
                                            "d M Y h:i A",
                                            strtotime(
                                                $record[
                                                    'reservation_date'
                                                ]
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- AVAILABILITY -->

                                    <td>

                                        <?php if (
                                            (int)$record[
                                                'available_quantity'
                                            ] > 0
                                        ): ?>


                                            <span
                                                class="
                                                    availability-badge
                                                    availability-yes
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle
                                                    "
                                                ></i>

                                                Available

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    availability-badge
                                                    availability-no
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-x-circle
                                                    "
                                                ></i>

                                                Not Available

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- STATUS -->

                                    <td>


                                        <?php if (
                                            $record['status']
                                            === 'Pending'
                                        ): ?>


                                            <span
                                                class="
                                                    status-badge
                                                    status-pending
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-hourglass-split
                                                    "
                                                ></i>

                                                Pending

                                            </span>


                                        <?php elseif (
                                            $record['status']
                                            === 'Approved'
                                        ): ?>


                                            <span
                                                class="
                                                    status-badge
                                                    status-approved
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>

                                                Approved

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    status-badge
                                                    status-cancelled
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-x-circle-fill
                                                    "
                                                ></i>

                                                Cancelled

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                    <!-- FOOTER -->

                    <div
                        class="
                            report-footer
                        "
                    >

                        Generated on

                        <?php

                        echo date(
                            "d M Y h:i A"
                        );

                        ?>

                        &nbsp; | &nbsp;

                        Library Management System

                    </div>


                <?php endif; ?>


            </div>


        </main>


    </div>


</div>


<!-- =========================================================
     THEME SCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const body =
            document.body;

        const themeButton =
            document.getElementById(
                "adminThemeToggle"
            );


        if (!themeButton) {
            return;
        }


        function updateThemeButton() {

            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );


            themeButton.innerHTML =
                isDark
                    ? '<i class="bi bi-sun-fill"></i>'
                    : '<i class="bi bi-moon-fill"></i>';


            themeButton.title =
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode";


            themeButton.setAttribute(
                "aria-label",
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode"
            );

        }


        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );


        if (
            savedTheme === "dark"
        ) {

            body.classList.add(
                "library-dark-mode"
            );

        } else {

            body.classList.remove(
                "library-dark-mode"
            );

        }


        updateThemeButton();


        themeButton.addEventListener(
            "click",
            function () {

                body.classList.toggle(
                    "library-dark-mode"
                );


                const isDark =
                    body.classList.contains(
                        "library-dark-mode"
                    );


                localStorage.setItem(
                    "library_theme",
                    isDark
                        ? "dark"
                        : "light"
                );


                updateThemeButton();

            }
        );

    }
);

</script>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>