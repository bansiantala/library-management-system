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
    die("Dompdf not found. Please run: composer require dompdf/dompdf");
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
$member_id = (int) ($_GET['member_id'] ?? 0);
$download = $_GET['download'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/
if ($status !== 'Issued' && $status !== 'Returned') {
    $status = '';
}


/*
|--------------------------------------------------------------------------
| VALIDATE DATES
|--------------------------------------------------------------------------
*/
if (
    $date_from !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)
) {
    $date_from = '';
}

if (
    $date_to !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)
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

        if ((int) $member['id'] === $member_id) {

            $selectedMemberName = $member['name'];
            $selectedMemberEmail = $member['email'];

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

    $searchValue = "%" . $search . "%";

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
    $stmt->bind_param($types, ...$params);
}


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/
$stmt->execute();

$result = $stmt->get_result();

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
$totalRecords = count($issuedRecords);

$totalIssued = 0;
$totalReturned = 0;
$totalFine = 0;
$totalFinePaid = 0;

foreach ($issuedRecords as $record) {

    if ($record['status'] === 'Issued') {
        $totalIssued++;
    }

    if ($record['status'] === 'Returned') {
        $totalReturned++;
    }

    $totalFine += (float) ($record['fine'] ?? 0);

    $totalFinePaid += (float) ($record['fine_paid'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| DOWNLOAD PERSONAL PDF
|--------------------------------------------------------------------------
*/
if ($download === 'pdf') {

    /*
    |--------------------------------------------------------------------------
    | DOMPDF OPTIONS
    |--------------------------------------------------------------------------
    */
    $options = new \Dompdf\Options();

    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );

    $options->set(
        'isRemoteEnabled',
        true
    );

    $dompdf = new \Dompdf\Dompdf($options);


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
    font-family: DejaVu Sans, sans-serif;
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
    color: #64748b;
    font-weight: normal;
}

.member-box {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.member-box td {
    border: 1px solid #dbe3ed;
    padding: 7px;
}

.member-label {
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
        Personal Issue / Return Report
    </h2>

</div>
';


    /*
    |--------------------------------------------------------------------------
    | MEMBER INFORMATION
    |--------------------------------------------------------------------------
    */
    if ($selectedMemberName !== '') {

        $pdfHtml .= '

<table class="member-box">

    <tr>

        <td class="member-label">
            Member Name
        </td>

        <td>
            ' .
            htmlspecialchars($selectedMemberName) .
            '
        </td>

    </tr>

    <tr>

        <td class="member-label">
            Member Email
        </td>

        <td>
            ' .
            htmlspecialchars($selectedMemberEmail) .
            '
        </td>

    </tr>

</table>

';

    } else {

        $pdfHtml .= '

<table class="member-box">

    <tr>

        <td class="member-label">
            Report
        </td>

        <td>
            All Members
        </td>

    </tr>

</table>

';
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER INFORMATION
    |--------------------------------------------------------------------------
    */
    $pdfHtml .= '
<table class="member-box">
';


    if ($search !== '') {

        $pdfHtml .= '

    <tr>

        <td class="member-label">
            Search
        </td>

        <td>
            ' .
            htmlspecialchars($search) .
            '
        </td>

    </tr>
';

    }


    if ($status !== '') {

        $pdfHtml .= '

    <tr>

        <td class="member-label">
            Status
        </td>

        <td>
            ' .
            htmlspecialchars($status) .
            '
        </td>

    </tr>
';

    }


    if ($date_from !== '') {

        $pdfHtml .= '

    <tr>

        <td class="member-label">
            Issue Date From
        </td>

        <td>
            ' .
            htmlspecialchars(
                date(
                    "d M Y",
                    strtotime($date_from)
                )
            ) .
            '
        </td>

    </tr>
';

    }


    if ($date_to !== '') {

        $pdfHtml .= '

    <tr>

        <td class="member-label">
            Issue Date To
        </td>

        <td>
            ' .
            htmlspecialchars(
                date(
                    "d M Y",
                    strtotime($date_to)
                )
            ) .
            '
        </td>

    </tr>
';

    }


    $pdfHtml .= '

</table>


<table class="summary">

<tr>

    <td>

        <span class="summary-number">
            ' .
            $totalRecords .
            '
        </span>

        <span class="summary-label">
            Total Records
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $totalIssued .
            '
        </span>

        <span class="summary-label">
            Currently Issued
        </span>

    </td>


    <td>

        <span class="summary-number">
            ' .
            $totalReturned .
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
                $totalFine,
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
    if (empty($issuedRecords)) {

        $pdfHtml .= '

<tr>

    <td colspan="10" class="no-data">
        No issue or return records found.
    </td>

</tr>

';

    } else {

        foreach ($issuedRecords as $index => $record) {

            $fine = (float) (
                $record['fine'] ?? 0
            );

            $finePaid = (float) (
                $record['fine_paid'] ?? 0
            );


            $issueDate = !empty(
                $record['issue_date']
            )
                ? date(
                    "d M Y",
                    strtotime(
                        $record['issue_date']
                    )
                )
                : 'N/A';


            $dueDate = !empty(
                $record['return_date']
            )
                ? date(
                    "d M Y",
                    strtotime(
                        $record['return_date']
                    )
                )
                : 'N/A';


            $actualReturn = !empty(
                $record['actual_return_date']
            )
                ? date(
                    "d M Y",
                    strtotime(
                        $record['actual_return_date']
                    )
                )
                : '—';


            $statusClass =
                $record['status'] === 'Issued'
                    ? 'issued-status'
                    : 'returned-status';


            $paymentStatus =
                $record['payment_status'] ?? 'Unpaid';


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
                $record['title']
            ) .
            '
        </strong>

        <br>

        ' .
        htmlspecialchars(
            $record['author']
        ) .
        '

    </td>


    <td>

        ' .
        htmlspecialchars(
            $record['user_name']
        ) .
        '

        <br>

        ' .
        htmlspecialchars(
            $record['user_email']
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
            $record['status']
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
            $paymentStatus
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
    date("d M Y h:i A") .
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
    if ($selectedMemberName !== '') {

        $safeMemberName = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $selectedMemberName
        );

        $fileName =
            'member_report_' .
            $safeMemberName .
            '_' .
            date('Y-m-d_H-i-s') .
            '.pdf';

    } else {

        $fileName =
            'issue_return_report_' .
            date('Y-m-d_H-i-s') .
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
| DOWNLOAD PDF URL
|--------------------------------------------------------------------------
*/
$downloadQuery = http_build_query(
    [
        'search' => $search,
        'status' => $status,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'member_id' => $member_id,
        'download' => 'pdf'
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
   ACTION BUTTONS
========================================================= */

.report-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.download-btn,
.print-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.download-btn {
    background: #059669;
    border: 1px solid #059669;
    color: #ffffff;
}

.download-btn:hover {
    background: #047857;
    border-color: #047857;
    color: #ffffff;
}

.print-btn {
    background: #2563eb;
    border: 1px solid #2563eb;
    color: #ffffff;
}

.print-btn:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
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
    box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
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
   SELECTED MEMBER HIGHLIGHT
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
   FINE / PAYMENT
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

@media (max-width: 900px) {

    .report-summary {
        grid-template-columns: repeat(2, 1fr);
    }

    .filter-card form {
        grid-template-columns: 1fr 1fr;
    }

    .search-btn,
    .reset-btn {
        width: 100%;
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

    .report-actions {
        width: 100%;
    }

    .download-btn,
    .print-btn {
        width: 100%;
        flex: 1;
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

    .report-summary {
        grid-template-columns: repeat(4, 1fr);
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
        background: #ffffff !important;
    }

}

    </style>

</head>


<body>

<div class="admin-layout">


    <!-- =========================================================
         ADMIN SIDEBAR
    ========================================================== -->

    <?php include "../../includes/admin_sidebar.php"; ?>


    <div class="admin-main">


        <!-- =====================================================
             NAVBAR
        ====================================================== -->

        <?php include "../../includes/navbar.php"; ?>


        <main class="report-page">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="report-header">

                <div class="report-title">

                    <h2>

                        <i class="bi bi-arrow-left-right"></i>

                        Issue / Return Report

                    </h2>

                    <p>
                        Track book issue, due-date and return records.
                    </p>

                </div>


                <!-- ACTIONS -->

                <div class="report-actions">


                    <!-- DOWNLOAD PDF -->

                    <a
                        href="?<?php echo htmlspecialchars($downloadQuery); ?>"
                        class="download-btn no-print"
                        title="Download Personal PDF Report"
                    >

                        <i class="bi bi-file-earmark-pdf"></i>

                        Download PDF

                    </a>


                    <!-- PRINT -->

                    <button
                        type="button"
                        class="print-btn no-print"
                        onclick="window.print()"
                        title="Print Report"
                    >

                        <i class="bi bi-printer"></i>

                        Print Report

                    </button>

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="report-summary">


                <!-- TOTAL -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-journal-text"></i>

                    </div>

                    <h3>
                        <?php echo $totalRecords; ?>
                    </h3>

                    <p>
                        Total Records
                    </p>

                </div>


                <!-- ISSUED -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-book-half"></i>

                    </div>

                    <h3>
                        <?php echo $totalIssued; ?>
                    </h3>

                    <p>
                        Currently Issued
                    </p>

                </div>


                <!-- RETURNED -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <h3>
                        <?php echo $totalReturned; ?>
                    </h3>

                    <p>
                        Returned Books
                    </p>

                </div>


                <!-- FINE -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-currency-rupee"></i>

                    </div>

                    <h3>
                        ₹<?php echo number_format($totalFine, 2); ?>
                    </h3>

                    <p>
                        Total Fine
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FILTER
            ================================================== -->

            <div class="filter-card no-print">

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
                            value="<?php echo htmlspecialchars($search); ?>"
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

                            <?php foreach ($members as $member): ?>

                                <option
                                    value="<?php echo (int) $member['id']; ?>"
                                    <?php
                                    echo
                                        $member_id === (int) $member['id']
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


                    <!-- FROM -->

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


                    <!-- TO -->

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


                    <!-- SEARCH BUTTON -->

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>


                    <!-- RESET -->

                    <a
                        href="issue_report.php"
                        class="reset-btn"
                    >

                        <i class="bi bi-arrow-clockwise"></i>

                        Reset

                    </a>

                </form>


                <!-- SELECTED MEMBER INFO -->

                <?php if ($selectedMemberName !== ''): ?>

                    <div class="selected-member-info">

                        <i class="bi bi-person-check"></i>

                        Selected Member:

                        <strong>
                            <?php echo htmlspecialchars($selectedMemberName); ?>
                        </strong>

                        -
                        
                        <?php echo htmlspecialchars($selectedMemberEmail); ?>

                        <span>
                            | PDF will contain only this member's records.
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
                        echo count($issuedRecords);
                        ?>

                        records found

                    </span>

                </div>


                <?php if (empty($issuedRecords)): ?>


                    <div class="empty-report">

                        <i class="bi bi-journal-x"></i>

                        <h4>
                            No Records Found
                        </h4>

                        <p>
                            No issue or return records match your filters.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="table-responsive">

                        <table class="report-table">


                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>Book</th>

                                    <th>Member</th>

                                    <th>Issue Date</th>

                                    <th>Due Date</th>

                                    <th>Actual Return</th>

                                    <th>Status</th>

                                    <th>Fine</th>

                                    <th>Fine Paid</th>

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
                                            class="book-author"
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
                                                $record['user_name']
                                            );
                                            ?>

                                        </div>


                                        <div
                                            class="user-email"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $record['user_email']
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- ISSUE -->

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


                                    <!-- DUE -->

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


                                    <!-- RETURN -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record['actual_return_date']
                                            )
                                        ): ?>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $record['actual_return_date']
                                                )
                                            );

                                            ?>

                                        <?php else: ?>

                                            <span class="no-fine">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if (
                                            $record['status'] === 'Issued'
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
                                            (float) (
                                                $record['fine'] ?? 0
                                            );

                                        ?>

                                        <?php if ($fine > 0): ?>

                                            <span
                                                class="fine-amount"
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
                                                class="no-fine"
                                            >

                                                ₹0.00

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- FINE PAID -->

                                    <td>

                                        <?php

                                        $finePaid =
                                            (float) (
                                                $record['fine_paid']
                                                ?? 0
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
                                                $record['payment_status']
                                                ?? 'Unpaid'
                                            ) === 'Paid'
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

                    <div class="report-footer">

                        Generated on

                        <?php
                        echo date("d M Y h:i A");
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


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>