//issues

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

$member_id = (int)($_GET['member_id'] ?? 0);

$download = $_GET['download'] ?? '';

$report_type = $_GET['report_type'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

if (
    $status !== 'Issued' &&
    $status !== 'Returned'
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
| GET MEMBERS
|--------------------------------------------------------------------------
*/

$members = [];

$memberResult = $conn->query("
    SELECT
        id,
        name,
        email
    FROM users
    WHERE role = 'user'
    ORDER BY name ASC
");

if ($memberResult) {

    while ($member = $memberResult->fetch_assoc()) {

        $members[] = $member;

    }

}


/*
|--------------------------------------------------------------------------
| SELECTED MEMBER
|--------------------------------------------------------------------------
*/

$selectedMemberName = '';

$selectedMemberEmail = '';

if ($member_id > 0) {

    foreach ($members as $member) {

        if (
            (int)$member['id'] === $member_id
        ) {

            $selectedMemberName =
                $member['name'];

            $selectedMemberEmail =
                $member['email'];

            break;

        }

    }

}


/*
|--------------------------------------------------------------------------
| ISSUE / RETURN REPORT QUERY
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
        issued_books.status,

        books.title,
        books.author,

        users.id AS user_id,
        users.name AS user_name,
        users.email AS user_email

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    INNER JOIN users
        ON issued_books.user_id = users.id

    WHERE 1 = 1
";

$params = [];

$types = '';


/*
|--------------------------------------------------------------------------
| SEARCH
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
| MEMBER FILTER
|--------------------------------------------------------------------------
*/

if ($member_id > 0) {

    $sql .= "
        AND issued_books.user_id = ?
    ";

    $params[] = $member_id;

    $types .= "i";

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= "
        AND issued_books.status = ?
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
        AND issued_books.issue_date >= ?
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
        AND issued_books.issue_date <= ?
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
    ORDER BY issued_books.id DESC
";


/*
|--------------------------------------------------------------------------
| PREPARE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Issue report query failed: " .
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

$issuedRecords = [];

while ($row = $result->fetch_assoc()) {

    $issuedRecords[] = $row;

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalRecords =
    count($issuedRecords);

$totalIssued = 0;

$totalReturned = 0;

$totalFine = 0;

$totalFinePaid = 0;


foreach ($issuedRecords as $record) {

    if (
        $record['status'] === 'Issued'
    ) {

        $totalIssued++;

    }

    if (
        $record['status'] === 'Returned'
    ) {

        $totalReturned++;

    }

    $totalFine +=
        (float)(
            $record['fine'] ?? 0
        );

    $totalFinePaid +=
        (float)(
            $record['fine_paid'] ?? 0
        );

}


/*
|--------------------------------------------------------------------------
| DOWNLOAD PDF
|--------------------------------------------------------------------------
|
| report_type = all
| report_type = member
|
*/

if ($download === 'pdf') {


    /*
    |--------------------------------------------------------------------------
    | MEMBER PDF VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'member' &&
        $member_id <= 0
    ) {

        header(
            "Location: issue_report.php?error=select_member"
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
    | ALL RECORDS PDF
    |--------------------------------------------------------------------------
    */

    if ($report_type === 'all') {


        $allSql = "

            SELECT

                issued_books.id,

                issued_books.issue_date,

                issued_books.return_date,

                issued_books.actual_return_date,

                issued_books.fine,

                issued_books.fine_paid,

                issued_books.payment_status,

                issued_books.status,

                books.title,

                books.author,

                users.id AS user_id,

                users.name AS user_name,

                users.email AS user_email

            FROM issued_books

            INNER JOIN books

                ON issued_books.book_id =
                   books.id

            INNER JOIN users

                ON issued_books.user_id =
                   users.id

            ORDER BY
                issued_books.id DESC

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
            "All Issue / Return Report";


        $pdfSubTitle =
            "Complete Library Issue and Return Report";

    }


    /*
    |--------------------------------------------------------------------------
    | SELECTED MEMBER PDF
    |--------------------------------------------------------------------------
    */

    else {


        $memberSql = "

            SELECT

                issued_books.id,

                issued_books.issue_date,

                issued_books.return_date,

                issued_books.actual_return_date,

                issued_books.fine,

                issued_books.fine_paid,

                issued_books.payment_status,

                issued_books.status,

                books.title,

                books.author,

                users.id AS user_id,

                users.name AS user_name,

                users.email AS user_email

            FROM issued_books

            INNER JOIN books

                ON issued_books.book_id =
                   books.id

            INNER JOIN users

                ON issued_books.user_id =
                   users.id

            WHERE
                issued_books.user_id = ?

            ORDER BY
                issued_books.id DESC

        ";


        $memberStmt =
            $conn->prepare(
                $memberSql
            );


        if (!$memberStmt) {

            die(
                "Member PDF query failed: " .
                htmlspecialchars(
                    $conn->error
                )
            );

        }


        $memberStmt->bind_param(
            "i",
            $member_id
        );


        $memberStmt->execute();


        $memberResult =
            $memberStmt->get_result();


        while (
            $row =
            $memberResult->fetch_assoc()
        ) {

            $pdfRecords[] =
                $row;

        }


        $memberStmt->close();


        $pdfTitle =
            $selectedMemberName .
            " - Issue / Return Report";


        $pdfSubTitle =
            "Selected Member Issue and Return Report";

    }


    /*
    |--------------------------------------------------------------------------
    | PDF SUMMARY
    |--------------------------------------------------------------------------
    */

    $pdfTotalRecords =
        count($pdfRecords);

    $pdfTotalIssued = 0;

    $pdfTotalReturned = 0;

    $pdfTotalFine = 0;

    $pdfTotalFinePaid = 0;


    foreach (
        $pdfRecords
        as $record
    ) {


        if (
            $record['status']
            === 'Issued'
        ) {

            $pdfTotalIssued++;

        }


        if (
            $record['status']
            === 'Returned'
        ) {

            $pdfTotalReturned++;

        }


        $pdfTotalFine +=
            (float)(
                $record['fine']
                ?? 0
            );


        $pdfTotalFinePaid +=
            (float)(
                $record['fine_paid']
                ?? 0
            );

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
    | SAFE TITLES
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

    font-size: 9px;
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
    width: 20%;
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
    font-size: 17px;
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
    font-size: 7.5px;
    text-align: left;
}

.report-table td {
    border: 1px solid #dfe5ec;
    padding: 6px;
    font-size: 7.5px;
}

.report-table tr:nth-child(even) td {
    background: #f8fafc;
}

.issued-status {
    color: #2563eb;
    font-weight: bold;
}

.returned-status {
    color: #059669;
    font-weight: bold;
}

.fine {
    color: #dc2626;
    font-weight: bold;
}

.paid {
    color: #059669;
    font-weight: bold;
}

.unpaid {
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
        ' .
        $safePdfTitle .
        '
    </h2>

    <h3>
        ' .
        $safePdfSubTitle .
        '
    </h3>

</div>
';


    /*
    |--------------------------------------------------------------------------
    | REPORT INFORMATION
    |--------------------------------------------------------------------------
    */

    $pdfHtml .= '

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
            All Members
        ';

    } else {

        $pdfHtml .= '
            Selected Member
        ';

    }


    $pdfHtml .= '

        </td>

    </tr>
';


    /*
    |--------------------------------------------------------------------------
    | SELECTED MEMBER INFORMATION
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'member'
    ) {


        $pdfHtml .= '

    <tr>

        <td class="info-label">
            Member Name
        </td>

        <td>
            ' .
            htmlspecialchars(
                $selectedMemberName,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
        </td>

    </tr>


    <tr>

        <td class="info-label">
            Member Email
        </td>

        <td>
            ' .
            htmlspecialchars(
                $selectedMemberEmail,
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
            $pdfTotalRecords .
            '
        </span>

        <span class="summary-label">
            Total Records
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $pdfTotalIssued .
            '
        </span>

        <span class="summary-label">
            Currently Issued
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $pdfTotalReturned .
            '
        </span>

        <span class="summary-label">
            Returned Books
        </span>

    </td>


    <td>

        <span class="summary-number">
            ₹' .
            number_format(
                $pdfTotalFine,
                2
            ) .
            '
        </span>

        <span class="summary-label">
            Total Fine
        </span>

    </td>

</tr>

</table>


<table class="report-table">

<thead>

<tr>

    <th>#</th>

    <th>Book</th>

    <th>Member</th>

    <th>Issue Date</th>

    <th>Due Date</th>

    <th>Return Date</th>

    <th>Status</th>

    <th>Fine</th>

    <th>Fine Paid</th>

    <th>Payment</th>

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
        colspan="10"
        class="no-data"
    >

        No issue or return records found.

    </td>

</tr>

';


    } else {


        foreach (
            $pdfRecords
            as $index =>
            $record
        ) {


            $fine =
                (float)(
                    $record['fine']
                    ?? 0
                );


            $finePaid =
                (float)(
                    $record['fine_paid']
                    ?? 0
                );


            $issueDate =
                !empty(
                    $record['issue_date']
                )

                    ? date(
                        "d M Y",
                        strtotime(
                            $record['issue_date']
                        )
                    )

                    : 'N/A';


            $dueDate =
                !empty(
                    $record['return_date']
                )

                    ? date(
                        "d M Y",
                        strtotime(
                            $record['return_date']
                        )
                    )

                    : 'N/A';


            $actualReturn =
                !empty(
                    $record[
                        'actual_return_date'
                    ]
                )

                    ? date(
                        "d M Y",
                        strtotime(
                            $record[
                                'actual_return_date'
                            ]
                        )
                    )

                    : '—';


            $statusClass =
                $record['status']
                === 'Issued'

                    ? 'issued-status'

                    : 'returned-status';


            $paymentStatus =
                $record[
                    'payment_status'
                ]
                ?? 'Unpaid';


            $paymentClass =
                $paymentStatus === 'Paid'

                    ? 'paid'

                    : 'unpaid';


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
        $issueDate .
        '
    </td>


    <td>
        ' .
        $dueDate .
        '
    </td>


    <td>
        ' .
        $actualReturn .
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


    <td>

        <span class="fine">

            ₹' .
            number_format(
                $fine,
                2
            ) .
            '

        </span>

    </td>


    <td>

        ₹' .
        number_format(
            $finePaid,
            2
        ) .
        '

    </td>


    <td class="' .
        $paymentClass .
        '">

        ' .
        htmlspecialchars(
            $paymentStatus,
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
        $report_type === 'member'
    ) {


        $safeMemberName =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $selectedMemberName
            );


        $safeMemberName =
            trim(
                $safeMemberName,
                '_'
            );


        if (
            $safeMemberName === ''
        ) {

            $safeMemberName =
                'member';

        }


        $fileName =
            'member_' .
            $safeMemberName .
            '_issue_return_report_' .
            date(
                'Y-m-d_H-i-s'
            ) .
            '.pdf';


    } else {


        $fileName =
            'all_issue_return_report_' .
            date(
                'Y-m-d_H-i-s'
            ) .
            '.pdf';

    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD
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
| MEMBER PDF
*/

$memberPdfQuery =
    http_build_query(
        [
            'download' => 'pdf',
            'report_type' => 'member',
            'member_id' => $member_id
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
        Issue / Return Report |
        Library Management System
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >


    <!-- ADMIN CSS -->

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

    width: 100%;
    min-height: 76px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    gap: 20px;
    box-sizing: border-box;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow:
        0 3px 15px
        rgba(15, 23, 42, 0.05);

}


.navbar-left {

    display: flex;
    align-items: center;
    min-width: 0;

}


.navbar-title h5 {

    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;

}


.navbar-title span {

    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 4px;
    color: #64748b;
    font-size: 11px;

}


.navbar-title span i {

    font-size: 12px;

}


.navbar-right {

    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex: 0 0 auto;
    min-width: max-content;
    white-space: nowrap;

}


.notification-btn,
.theme-toggle-btn {

    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    font-size: 17px;
    transition: all 0.2s ease;
    padding: 0;

}


.notification-btn:hover,
.theme-toggle-btn:hover {

    background: #f8fafc;
    border-color: #cbd5e1;
    color: #2563eb;

}


.header-divider {

    width: 1px;
    height: 34px;
    background: #e2e8f0;
    margin: 0 4px;

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

    min-height: 40px;
    padding: 0 13px;
    border: 1px solid #fecaca;
    border-radius: 9px;
    background: #fef2f2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: all .2s ease;

}


.admin-logout-btn:hover {

    background: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;

}


/* =========================================================
   HEADER
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

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 15px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;

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


.download-member-btn {

    background: #059669;
    border: 1px solid #059669;
    color: #ffffff;

}


.download-member-btn:hover {

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

}


.print-btn:hover {

    background: #334155;
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

    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    padding: 18px;
    box-shadow:
        0 4px 15px
        rgba(15, 23, 42, .05);

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

    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow:
        0 4px 15px
        rgba(15, 23, 42, .04);

}


.filter-card form {

    display: grid;
    grid-template-columns:
        1.5fr
        180px
        180px
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
    background: #ffffff;
    color: #334155;

}


.filter-group input:focus,
.filter-group select:focus {

    border-color: #2563eb;
    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, .10);

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
    color: #ffffff;
    cursor: pointer;

}


.search-btn:hover {

    background: #1d4ed8;
    color: #ffffff;

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


/* =========================================================
   SELECTED MEMBER
========================================================= */

.selected-member-info {

    margin-top: 15px;
    padding: 12px 15px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 9px;
    color: #1e40af;
    font-size: 13px;

}


.selected-member-info strong {

    color: #1e3a8a;

}


/* =========================================================
   TABLE
========================================================= */

.table-card {

    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    overflow: hidden;
    box-shadow:
        0 4px 15px
        rgba(15, 23, 42, .04);

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


/* =========================================================
   STATUS
========================================================= */

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


.status-issued {

    background: #eff6ff;
    color: #2563eb;

}


.status-returned {

    background: #ecfdf5;
    color: #059669;

}


/* =========================================================
   FINE
========================================================= */

.fine-amount {

    font-weight: 700;
    color: #dc2626;

}


.no-fine {

    color: #64748b;

}


.payment-paid {

    color: #059669;
    font-weight: 700;

}


.payment-unpaid {

    color: #dc2626;
    font-weight: 700;

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


body.library-dark-mode .summary-card {

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


body.library-dark-mode .filter-card {

    background: #1e293b !important;
    border-color: #334155 !important;

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


body.library-dark-mode .reset-btn {

    background: #334155 !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;

}


body.library-dark-mode .selected-member-info {

    background: #172554 !important;
    border-color: #1e40af !important;
    color: #bfdbfe !important;

}


body.library-dark-mode .selected-member-info strong {

    color: #dbeafe !important;

}


body.library-dark-mode .table-card {

    background: #1e293b !important;
    border-color: #334155 !important;

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


body.library-dark-mode .status-issued {

    background: #172554 !important;
    color: #93c5fd !important;

}


body.library-dark-mode .status-returned {

    background: #064e3b !important;
    color: #6ee7b7 !important;

}


body.library-dark-mode .no-fine {

    color: #94a3b8 !important;

}


body.library-dark-mode .report-footer {

    border-top-color: #334155 !important;
    color: #94a3b8 !important;

}


body.library-dark-mode .empty-report {

    color: #94a3b8 !important;

}


body.library-dark-mode .empty-report h4 {

    color: #f8fafc !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1300px) {

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


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <?php include "../../includes/admin_sidebar.php"; ?>


    <div class="admin-main">


        <!-- =====================================================
             NAVBAR
        ====================================================== -->

        

        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <main class="report-page">


            <!-- HEADER -->

            <div class="report-header">


                <div class="report-title">

                    <h2>
                        Issue / Return Report
                    </h2>

                    <p>
                        Download all records or selected member records as PDF.
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
                        title="Download all issue and return records"
                    >

                        <i
                            class="
                                bi
                                bi-file-earmark-pdf-fill
                            "
                        ></i>

                        Download All PDF

                    </a>


                    <!-- MEMBER PDF -->

                    <?php if (
                        $member_id > 0 &&
                        $selectedMemberName !== ''
                    ): ?>


                        <a
                            href="?<?php
                            echo htmlspecialchars(
                                $memberPdfQuery
                            );
                            ?>"
                            class="
                                download-btn
                                download-member-btn
                                no-print
                            "
                            title="Download selected member PDF"
                        >

                            <i
                                class="
                                    bi
                                    bi-person-badge-fill
                                "
                            ></i>

                            Download
                            <?php
                            echo htmlspecialchars(
                                $selectedMemberName
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
                            title="Select a member first"
                        >

                            <i
                                class="
                                    bi
                                    bi-person-badge
                                "
                            ></i>

                            Select Member PDF

                        </span>


                    <?php endif; ?>


                    <!-- PRINT -->

                   

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
                                bi-journal-text
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalRecords;
                        ?>

                    </h3>


                    <p>
                        Total Records
                    </p>

                </div>


                <!-- ISSUED -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-book-half
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalIssued;
                        ?>

                    </h3>


                    <p>
                        Currently Issued
                    </p>

                </div>


                <!-- RETURNED -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-check-circle
                            "
                        ></i>

                    </div>


                    <h3>

                        <?php
                        echo $totalReturned;
                        ?>

                    </h3>


                    <p>
                        Returned Books
                    </p>

                </div>


                <!-- FINE -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="
                                bi
                                bi-currency-rupee
                            "
                        ></i>

                    </div>


                    <h3>

                        ₹<?php

                        echo number_format(
                            $totalFine,
                            2
                        );

                        ?>

                    </h3>


                    <p>
                        Total Fine
                    </p>

                </div>


            </div>


            <!-- =================================================
                 FILTER
            ================================================== -->

            <div class="
                filter-card
                no-print
            ">


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


                    <!-- MEMBER -->

                    <div class="filter-group">

                        <label for="member_id">
                            Member
                        </label>


                        <select
                            id="member_id"
                            name="member_id"
                        >

                            <option value="0">
                                All Members
                            </option>


                            <?php foreach (
                                $members
                                as $member
                            ): ?>


                                <option
                                    value="<?php
                                    echo (int)$member['id'];
                                    ?>"
                                    <?php

                                    echo
                                        $member_id
                                        ===
                                        (int)$member['id']

                                            ? 'selected'

                                            : '';

                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $member['name']
                                    );

                                    echo " - ";

                                    echo htmlspecialchars(
                                        $member['email']
                                    );

                                    ?>

                                </option>


                            <?php endforeach; ?>


                        </select>

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
                                value="Issued"
                                <?php
                                echo
                                    $status === 'Issued'
                                        ? 'selected'
                                        : '';
                                ?>
                            >
                                Issued
                            </option>


                            <option
                                value="Returned"
                                <?php
                                echo
                                    $status === 'Returned'
                                        ? 'selected'
                                        : '';
                                ?>
                            >
                                Returned
                            </option>

                        </select>

                    </div>


                    <!-- DATE FROM -->

                    <div class="filter-group">

                        <label for="date_from">
                            Issue Date From
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

                        <label for="date_to">
                            Issue Date To
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
                        href="issue_report.php"
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


                <!-- SELECTED MEMBER INFO -->

                <?php if (
                    $selectedMemberName !== ''
                ): ?>


                    <div
                        class="
                            selected-member-info
                        "
                    >

                        <i
                            class="
                                bi
                                bi-person-check
                            "
                        ></i>


                        Selected Member:


                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $selectedMemberName
                            );

                            ?>

                        </strong>


                        -

                        <?php

                        echo htmlspecialchars(
                            $selectedMemberEmail
                        );

                        ?>


                        <span>

                            | Member PDF contains
                            only this member's records.

                        </span>

                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-header">

                    <h4>
                        Issue / Return Records
                    </h4>


                    <span>

                        <?php

                        echo count(
                            $issuedRecords
                        );

                        ?>

                        records found

                    </span>

                </div>


                <?php if (
                    empty($issuedRecords)
                ): ?>


                    <div
                        class="empty-report"
                    >

                        <i
                            class="
                                bi
                                bi-journal-x
                            "
                        ></i>


                        <h4>
                            No Records Found
                        </h4>


                        <p>
                            No issue or return records match your filters.
                        </p>

                    </div>


                <?php else: ?>


                    <div
                        class="table-responsive"
                    >


                        <table
                            class="report-table"
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
                                        Member
                                    </th>

                                    <th>
                                        Issue Date
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Actual Return
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Fine
                                    </th>

                                    <th>
                                        Fine Paid
                                    </th>

                                    <th>
                                        Payment Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $issuedRecords
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
                                            class="book-title"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $record['title']
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
                                                $record['author']
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <!-- MEMBER -->

                                    <td>

                                        <div
                                            class="user-name"
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
                                            class="user-email"
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


                                    <!-- ISSUE DATE -->

                                    <td>

                                        <?php

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $record[
                                                    'issue_date'
                                                ]
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- DUE DATE -->

                                    <td>

                                        <?php

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $record[
                                                    'return_date'
                                                ]
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- ACTUAL RETURN -->

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

                                            <span
                                                class="no-fine"
                                            >
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if (
                                            $record['status']
                                            === 'Issued'
                                        ): ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-issued
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-book-half
                                                    "
                                                ></i>

                                                Issued

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    status-badge
                                                    status-returned
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle
                                                    "
                                                ></i>

                                                Returned

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- FINE -->

                                    <td>

                                        <?php

                                        $fine =
                                            (float)(
                                                $record[
                                                    'fine'
                                                ] ?? 0
                                            );

                                        ?>


                                        <?php if (
                                            $fine > 0
                                        ): ?>

                                            <span
                                                class="
                                                    fine-amount
                                                "
                                            >

                                                ₹<?php

                                                echo number_format(
                                                    $fine,
                                                    2
                                                );

                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    no-fine
                                                "
                                            >

                                                ₹0.00

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- FINE PAID -->

                                    <td>

                                        <?php

                                        $finePaid =
                                            (float)(
                                                $record[
                                                    'fine_paid'
                                                ] ?? 0
                                            );

                                        ?>

                                        ₹<?php

                                        echo number_format(
                                            $finePaid,
                                            2
                                        );

                                        ?>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td>

                                        <?php if (
                                            (
                                                $record[
                                                    'payment_status'
                                                ]
                                                ?? 'Unpaid'
                                            )
                                            === 'Paid'
                                        ): ?>

                                            <span
                                                class="
                                                    payment-paid
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>

                                                Paid

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    payment-unpaid
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-exclamation-circle-fill
                                                    "
                                                ></i>

                                                Unpaid

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
                        class="report-footer"
                    >

                        Generated on

                        <?php

                        echo date(
                            "d M Y h:i A"
                        );

                        ?>

                        &nbsp; | &nbsp;

                        Library Management System

                        &nbsp; | &nbsp;

                        Fine Collected:

                        ₹<?php

                        echo number_format(
                            $totalFinePaid,
                            2
                        );

                        ?>

                    </div>


                <?php endif; ?>


            </div>


        </main>


    </div>


</div>


<!-- =========================================================
     GLOBAL THEME
========================================================== -->

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