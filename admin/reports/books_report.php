<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/*
|--------------------------------------------------------------------------
| Dompdf Autoload
|--------------------------------------------------------------------------
*/

$autoloadPath = "../../vendor/autoload.php";

if (!file_exists($autoloadPath)) {
    die("Dompdf not found. Please run: composer require dompdf/dompdf");
}

require_once $autoloadPath;

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$category_id = (int)($_GET['category_id'] ?? 0);

$download = $_GET['download'] ?? '';

$report_type = $_GET['report_type'] ?? '';

$categories = [];

/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$categoryResult = $conn->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

if ($categoryResult) {

    while ($category = $categoryResult->fetch_assoc()) {
        $categories[] = $category;
    }
}

/*
|--------------------------------------------------------------------------
| Selected Category Name
|--------------------------------------------------------------------------
*/

$selectedCategoryName = '';

if ($category_id > 0) {

    foreach ($categories as $category) {

        if ((int)$category['id'] === $category_id) {

            $selectedCategoryName = $category['category_name'];

            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Book Report Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        books.id,
        books.title,
        books.author,
        books.isbn,
        books.quantity,
        books.available_quantity,
        books.created_at,
        categories.category_name

    FROM books

    LEFT JOIN categories
        ON books.category_id = categories.id

    WHERE 1 = 1
";

$params = [];
$types = "";

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR books.isbn LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

/*
|--------------------------------------------------------------------------
| Category Filter
|--------------------------------------------------------------------------
*/

if ($category_id > 0) {

    $sql .= "
        AND books.category_id = ?
    ";

    $params[] = $category_id;

    $types .= "i";
}

/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY books.id DESC
";

/*
|--------------------------------------------------------------------------
| Prepare
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Book report query failed: " .
        htmlspecialchars($conn->error)
    );
}

/*
|--------------------------------------------------------------------------
| Bind
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
| Execute
|--------------------------------------------------------------------------
*/

$stmt->execute();

$result = $stmt->get_result();

$books = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$totalBookTitles = count($books);

$totalQuantity = 0;

$totalAvailable = 0;

$totalIssuedCopies = 0;

foreach ($books as $book) {

    $quantity = (int)$book['quantity'];

    $available = (int)$book['available_quantity'];

    $totalQuantity += $quantity;

    $totalAvailable += $available;

    $totalIssuedCopies += max(
        0,
        $quantity - $available
    );
}

/*
|--------------------------------------------------------------------------
| DOWNLOAD PDF
|--------------------------------------------------------------------------
|
| report_type = all
| report_type = category
|
*/

if ($download === 'pdf') {

    /*
    |--------------------------------------------------------------------------
    | CATEGORY PDF VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'category' &&
        $category_id <= 0
    ) {

        header(
            "Location: books_report.php?error=select_category"
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | PDF DATA
    |--------------------------------------------------------------------------
    |
    | ALL PDF
    |      category filter is ignored
    |
    | CATEGORY PDF
    |      selected category is used
    |
    */

    $pdfBooks = [];

    if ($report_type === 'all') {

        /*
        |--------------------------------------------------------------
        | Get ALL books
        |--------------------------------------------------------------
        */

        $allSql = "
            SELECT
                books.id,
                books.title,
                books.author,
                books.isbn,
                books.quantity,
                books.available_quantity,
                books.created_at,
                categories.category_name

            FROM books

            LEFT JOIN categories
                ON books.category_id = categories.id

            ORDER BY books.id DESC
        ";

        $allResult = $conn->query($allSql);

        if ($allResult) {

            while ($row = $allResult->fetch_assoc()) {
                $pdfBooks[] = $row;
            }
        }

        $pdfTitle = "All Books Report";

        $pdfSubTitle = "Complete Library Book Report";

    } else {

        /*
        |--------------------------------------------------------------
        | Get SELECTED CATEGORY books
        |--------------------------------------------------------------
        */

        $categorySql = "
            SELECT
                books.id,
                books.title,
                books.author,
                books.isbn,
                books.quantity,
                books.available_quantity,
                books.created_at,
                categories.category_name

            FROM books

            LEFT JOIN categories
                ON books.category_id = categories.id

            WHERE books.category_id = ?

            ORDER BY books.id DESC
        ";

        $categoryStmt = $conn->prepare($categorySql);

        if (!$categoryStmt) {

            die(
                "Category PDF query failed: " .
                htmlspecialchars($conn->error)
            );
        }

        $categoryStmt->bind_param(
            "i",
            $category_id
        );

        $categoryStmt->execute();

        $categoryResult = $categoryStmt->get_result();

        while ($row = $categoryResult->fetch_assoc()) {
            $pdfBooks[] = $row;
        }

        $categoryStmt->close();

        $pdfTitle = $selectedCategoryName .
            " Book Report";

        $pdfSubTitle = "Selected Category Book Report";
    }

    /*
    |--------------------------------------------------------------------------
    | PDF SUMMARY
    |--------------------------------------------------------------------------
    */

    $pdfTotalTitles = count($pdfBooks);

    $pdfTotalQuantity = 0;

    $pdfTotalAvailable = 0;

    $pdfTotalIssued = 0;

    foreach ($pdfBooks as $book) {

        $quantity = (int)$book['quantity'];

        $available = (int)$book['available_quantity'];

        $issued = max(
            0,
            $quantity - $available
        );

        $pdfTotalQuantity += $quantity;

        $pdfTotalAvailable += $available;

        $pdfTotalIssued += $issued;
    }

    /*
    |--------------------------------------------------------------------------
    | Dompdf Options
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

    $dompdf = new \Dompdf\Dompdf(
        $options
    );

    /*
    |--------------------------------------------------------------------------
    | Safe PDF Heading
    |--------------------------------------------------------------------------
    */

    $safePdfTitle = htmlspecialchars(
        $pdfTitle,
        ENT_QUOTES,
        'UTF-8'
    );

    $safePdfSubTitle = htmlspecialchars(
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
    margin: 30px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    color: #1e293b;
    font-size: 10px;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0;
    font-size: 21px;
    font-weight: bold;
    color: #1e293b;
}

.header h2 {
    margin: 6px 0 0;
    font-size: 16px;
    color: #2563eb;
}

.header h3 {
    margin: 5px 0 0;
    font-size: 11px;
    font-weight: normal;
    color: #64748b;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}

.info-table td {
    border: 1px solid #e2e8f0;
    padding: 7px;
}

.info-label {
    width: 25%;
    font-weight: bold;
    background: #f8fafc;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.summary td {
    width: 25%;
    border: 1px solid #dbeafe;
    background: #eff6ff;
    padding: 10px;
    text-align: center;
}

.summary-number {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #2563eb;
}

.summary-label {
    display: block;
    margin-top: 4px;
    color: #475569;
    font-size: 9px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #1d4ed8;
    padding: 7px;
    font-size: 8px;
    text-align: left;
}

.report-table td {
    border: 1px solid #e2e8f0;
    padding: 7px;
    font-size: 8px;
}

.report-table tr:nth-child(even) td {
    background: #f8fafc;
}

.available {
    color: #059669;
    font-weight: bold;
    text-align: center;
}

.issued {
    color: #ea580c;
    font-weight: bold;
    text-align: center;
}

.footer {
    text-align: center;
    margin-top: 18px;
    font-size: 8px;
    color: #64748b;
}

.no-data {
    text-align: center;
    padding: 15px;
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

    if ($report_type === 'all') {

        $pdfHtml .= '
            All Books
        ';

    } else {

        $pdfHtml .= '
            Selected Category
        ';
    }

    $pdfHtml .= '

        </td>

    </tr>
';

    /*
    |--------------------------------------------------------------------------
    | Category Information
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'category' &&
        $selectedCategoryName !== ''
    ) {

        $pdfHtml .= '

    <tr>

        <td class="info-label">
            Category
        </td>

        <td>
            ' .
            htmlspecialchars(
                $selectedCategoryName,
                ENT_QUOTES,
                'UTF-8'
            )
            . '
        </td>

    </tr>

        ';
    }

    /*
    |--------------------------------------------------------------------------
    | Generated Date
    |--------------------------------------------------------------------------
    */

    $pdfHtml .= '

    <tr>

        <td class="info-label">
            Generated On
        </td>

        <td>
            ' .
            htmlspecialchars(
                date("d M Y h:i A"),
                ENT_QUOTES,
                'UTF-8'
            )
            . '
        </td>

    </tr>

</table>


<table class="summary">

    <tr>

        <td>

            <span class="summary-number">
                ' . $pdfTotalTitles . '
            </span>

            <span class="summary-label">
                Book Titles
            </span>

        </td>

        <td>

            <span class="summary-number">
                ' . $pdfTotalQuantity . '
            </span>

            <span class="summary-label">
                Total Copies
            </span>

        </td>

        <td>

            <span class="summary-number">
                ' . $pdfTotalAvailable . '
            </span>

            <span class="summary-label">
                Available Copies
            </span>

        </td>

        <td>

            <span class="summary-number">
                ' . $pdfTotalIssued . '
            </span>

            <span class="summary-label">
                Issued Copies
            </span>

        </td>

    </tr>

</table>


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
            <th>Added Date</th>

        </tr>

    </thead>

    <tbody>
';

    /*
    |--------------------------------------------------------------------------
    | PDF Records
    |--------------------------------------------------------------------------
    */

    if (empty($pdfBooks)) {

        $pdfHtml .= '

        <tr>

            <td
                colspan="9"
                class="no-data"
            >
                No books found.
            </td>

        </tr>

        ';

    } else {

        foreach (
            $pdfBooks as $index => $book
        ) {

            $quantity =
                (int)$book['quantity'];

            $available =
                (int)$book['available_quantity'];

            $issued =
                max(
                    0,
                    $quantity - $available
                );

            $addedDate =
                !empty($book['created_at'])
                    ? date(
                        "d M Y",
                        strtotime(
                            $book['created_at']
                        )
                    )
                    : 'N/A';

            $pdfHtml .= '

        <tr>

            <td>
                ' . ($index + 1) . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $book['title'],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $book['author'],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $book['category_name'] ?? 'N/A',
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $book['isbn'] ?? 'N/A',
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td style="text-align:center;">
                ' . $quantity . '
            </td>

            <td class="available">
                ' . $available . '
            </td>

            <td class="issued">
                ' . $issued . '
            </td>

            <td>
                ' . $addedDate . '
            </td>

        </tr>

            ';
        }
    }

    $pdfHtml .= '

    </tbody>

</table>


<div class="footer">

    Library Management System
    |
    ' . $safePdfTitle . '
    |
    Generated automatically

</div>


</body>

</html>
';

    /*
    |--------------------------------------------------------------------------
    | Generate PDF
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
    | File Name
    |--------------------------------------------------------------------------
    */

    if ($report_type === 'all') {

        $fileName =
            'all_books_report_' .
            date('Y-m-d_H-i-s') .
            '.pdf';

    } else {

        $safeCategory =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $selectedCategoryName
            );

        $safeCategory =
            trim(
                $safeCategory,
                '_'
            );

        if ($safeCategory === '') {
            $safeCategory = 'category';
        }

        $fileName =
            $safeCategory .
            '_books_report_' .
            date('Y-m-d_H-i-s') .
            '.pdf';
    }

    /*
    |--------------------------------------------------------------------------
    | Download PDF
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
| PDF URLs
|--------------------------------------------------------------------------
*/

/*
| ALL BOOKS PDF
*/

$allPdfQuery = http_build_query(
    [
        'download' => 'pdf',
        'report_type' => 'all'
    ]
);

/*
| CATEGORY PDF
*/

$categoryPdfQuery = http_build_query(
    [
        'download' => 'pdf',
        'report_type' => 'category',
        'category_id' => $category_id
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
        Book Report | Library Management System
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
            box-shadow: 0 3px 15px rgba(15,23,42,.05);

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

            font-size: 11px;

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
            transition: all .2s ease;
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
            box-shadow: 0 3px 10px rgba(37,99,235,.12);

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
           REPORT PAGE
        ========================================================= */

        .report-page {

            padding: 30px;

        }

        .report-header {

            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;

        }

        .report-title {

            flex: 1 1 auto;
            min-width: 0;

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

        .download-btn {

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
            color: #ffffff;

        }

        .download-category-btn {

            background: #059669;
            border: 1px solid #059669;
            color: #ffffff;

        }

        .download-category-btn:hover {

            background: #047857;
            color: #ffffff;

        }

        .download-disabled {

            background: #cbd5e1;
            border: 1px solid #cbd5e1;
            color: #64748b;
            cursor: not-allowed;

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
            box-shadow: 0 4px 15px rgba(15,23,42,.05);

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

            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(15,23,42,.04);

        }

        .filter-card form {

            display: grid;
            grid-template-columns:
                minmax(0,1fr)
                240px
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
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);

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
            white-space: nowrap;

        }

        .search-btn {

            background: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
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

        .selected-category-info {

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
            box-shadow: 0 4px 15px rgba(15,23,42,.04);

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
            min-width: 850px;

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

        .quantity-badge {

            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            padding: 5px 8px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;

        }

        .available-badge {

            background: #ecfdf5;
            color: #059669;

        }

        .issued-badge {

            background: #fff7ed;
            color: #ea580c;

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
            color: #e2e8f0;

        }

        body.library-dark-mode .admin-navbar {

            background: #111827 !important;
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

            background: #1e293b !important;
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

            background: #1e293b !important;
            color: #60a5fa !important;

        }

        body.library-dark-mode .nav-avatar i {

            color: #60a5fa !important;

        }

        body.library-dark-mode .nav-admin-info strong {

            color: #f8fafc !important;

        }

        body.library-dark-mode .nav-admin-info small {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .report-title h2,
        body.library-dark-mode .summary-card h3,
        body.library-dark-mode .table-header h4,
        body.library-dark-mode .empty-report h4 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .report-title p,
        body.library-dark-mode .summary-card p,
        body.library-dark-mode .table-header span,
        body.library-dark-mode .report-footer,
        body.library-dark-mode .empty-report {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .summary-card,
        body.library-dark-mode .filter-card,
        body.library-dark-mode .table-card {

            background: #1e293b !important;
            border-color: #334155 !important;

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
            border-color: #475569 !important;
            color: #f8fafc !important;

        }

        body.library-dark-mode .filter-group input::placeholder {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .selected-category-info {

            background: #172554 !important;
            border-color: #1e40af !important;
            color: #93c5fd !important;

        }

        body.library-dark-mode .reset-btn {

            background: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;

        }

        body.library-dark-mode .report-table th {

            background: #273449 !important;
            color: #f8fafc !important;

        }

        body.library-dark-mode .report-table td {

            color: #cbd5e1 !important;
            border-top-color: #334155 !important;

        }

        body.library-dark-mode .report-table tbody tr:hover {

            background: #273449 !important;

        }

        body.library-dark-mode .book-title {

            color: #f8fafc !important;

        }

        body.library-dark-mode .category-badge {

            background: #273449 !important;
            color: #93c5fd !important;

        }

        body.library-dark-mode .quantity-badge {

            background: #334155 !important;
            color: #e2e8f0 !important;

        }

        body.library-dark-mode .available-badge {

            background: #064e3b !important;
            color: #6ee7b7 !important;

        }

        body.library-dark-mode .issued-badge {

            background: #7c2d12 !important;
            color: #fdba74 !important;

        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .report-actions {

                width: 100%;
                justify-content: flex-start;

            }

        }

        @media (max-width: 1000px) {

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

            .table-header {

                align-items: flex-start;
                flex-direction: column;

            }

            .navbar-title span {

                display: none;

            }

            .admin-navbar {

                min-height: 68px;

            }

            .notification-btn,
            .theme-toggle-btn {

                width: 37px;
                height: 37px;
                flex-basis: 37px;

            }

            .nav-avatar {

                width: 34px;
                height: 34px;
                flex-basis: 34px;

            }

            .report-actions {

                flex-direction: column;
                align-items: stretch;

            }

            .download-btn {

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

    <!-- SIDEBAR -->

    <?php include "../../includes/admin_sidebar.php"; ?>

    <!-- MAIN -->

    <div class="admin-main">

        <!-- =====================================================
             ADMIN NAVBAR
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

                        Book Report

                    </span>

                </div>

            </div>

            <div class="navbar-right">

                <button
                    type="button"
                    class="notification-btn"
                    title="Notifications"
                    aria-label="Notifications"
                >

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
             REPORT CONTENT
        ====================================================== -->

        <main class="report-page">

            <div class="report-header">

                <div class="report-title">

                    <h2>
                        Book Report
                    </h2>

                    <p>
                        Download the complete report or selected category report.
                    </p>

                </div>


                <!-- =================================================
                     TWO PDF OPTIONS
                ================================================== -->

                <div class="report-actions">

                    <!-- ALL BOOKS PDF -->

                    <a
                        href="?<?php echo htmlspecialchars($allPdfQuery); ?>"
                        class="download-btn download-all-btn no-print"
                        title="Download all books PDF"
                    >

                        <i class="bi bi-file-earmark-pdf-fill"></i>

                        Download All PDF

                    </a>


                    <!-- CATEGORY PDF -->

                    <?php if ($category_id > 0): ?>

                        <a
                            href="?<?php echo htmlspecialchars($categoryPdfQuery); ?>"
                            class="download-btn download-category-btn no-print"
                            title="Download selected category PDF"
                        >

                            <i class="bi bi-folder2-open"></i>

                            Download
                            <?php echo htmlspecialchars($selectedCategoryName); ?>
                            PDF

                        </a>

                    <?php else: ?>

                        <span
                            class="download-btn download-disabled no-print"
                            title="Select a category first"
                        >

                            <i class="bi bi-folder2-open"></i>

                            Select Category PDF

                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="report-summary">

                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-book"></i>
                    </div>

                    <h3>
                        <?php echo $totalBookTitles; ?>
                    </h3>

                    <p>
                        Book Titles
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-stack"></i>
                    </div>

                    <h3>
                        <?php echo $totalQuantity; ?>
                    </h3>

                    <p>
                        Total Copies
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-bookmark-check"></i>
                    </div>

                    <h3>
                        <?php echo $totalAvailable; ?>
                    </h3>

                    <p>
                        Available Copies
                    </p>

                </div>


                <div class="summary-card">

                    <div class="icon">
                        <i class="bi bi-journal-arrow-up"></i>
                    </div>

                    <h3>
                        <?php echo $totalIssuedCopies; ?>
                    </h3>

                    <p>
                        Issued Copies
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
                            Search Book
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by title, author or ISBN..."
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div class="filter-group">

                        <label for="category_id">
                            Category
                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                        >

                            <option value="0">
                                All Categories
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?php echo (int)$category['id']; ?>"
                                    <?php
                                    echo
                                        $category_id ===
                                        (int)$category['id']
                                            ? 'selected'
                                            : '';
                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $category['category_name']
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SEARCH -->

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>


                    <!-- RESET -->

                    <a
                        href="books_report.php"
                        class="reset-btn"
                    >

                        <i class="bi bi-arrow-clockwise"></i>

                        Reset

                    </a>

                </form>


                <?php if ($category_id > 0): ?>

                    <div class="selected-category-info">

                        <i class="bi bi-check-circle-fill"></i>

                        Selected Category:

                        <?php

                        echo htmlspecialchars(
                            $selectedCategoryName
                        );

                        ?>

                        — Category PDF contains only this category.

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="table-card">

                <div class="table-header">

                    <h4>
                        Book Records
                    </h4>

                    <span>

                        <?php echo count($books); ?>

                        records found

                    </span>

                </div>


                <?php if (empty($books)): ?>

                    <div class="empty-report">

                        <i class="bi bi-book"></i>

                        <h4>
                            No Books Found
                        </h4>

                        <p>
                            No book records match your search criteria.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="report-table">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>
                                        Book Title
                                    </th>

                                    <th>
                                        Author
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        ISBN
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Available
                                    </th>

                                    <th>
                                        Issued
                                    </th>

                                    <th>
                                        Added Date
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($books as $index => $book): ?>

                                    <?php

                                    $quantity =
                                        (int)$book['quantity'];

                                    $available =
                                        (int)$book['available_quantity'];

                                    $issued =
                                        max(
                                            0,
                                            $quantity - $available
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
                                                    $book['title']
                                                );

                                                ?>

                                            </div>

                                        </td>

                                        <td>

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
                                                    ?? 'N/A'
                                                );

                                                ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $book['isbn']
                                                ?? 'N/A'
                                            );

                                            ?>

                                        </td>

                                        <td>

                                            <span class="quantity-badge">

                                                <?php
                                                echo $quantity;
                                                ?>

                                            </span>

                                        </td>

                                        <td>

                                            <span
                                                class="
                                                    quantity-badge
                                                    available-badge
                                                "
                                            >

                                                <?php
                                                echo $available;
                                                ?>

                                            </span>

                                        </td>

                                        <td>

                                            <span
                                                class="
                                                    quantity-badge
                                                    issued-badge
                                                "
                                            >

                                                <?php
                                                echo $issued;
                                                ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php

                                            echo !empty(
                                                $book['created_at']
                                            )

                                                ? date(
                                                    "d M Y",
                                                    strtotime(
                                                        $book['created_at']
                                                    )
                                                )

                                                : 'N/A';

                                            ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <div class="report-footer">

                        Generated on

                        <?php
                        echo date("d M Y h:i A");
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
     SIDEBAR TOGGLE
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const sidebarToggle =
            document.getElementById(
                "sidebarToggle"
            );

        const adminSidebar =
            document.getElementById(
                "adminSidebar"
            );

        if (
            sidebarToggle &&
            adminSidebar
        ) {

            sidebarToggle.addEventListener(
                "click",
                function () {

                    adminSidebar.classList.toggle(
                        "show"
                    );

                }
            );

            document.addEventListener(
                "click",
                function (event) {

                    if (

                        window.innerWidth <= 992 &&

                        adminSidebar.classList.contains(
                            "show"
                        ) &&

                        !adminSidebar.contains(
                            event.target
                        ) &&

                        !sidebarToggle.contains(
                            event.target
                        )

                    ) {

                        adminSidebar.classList.remove(
                            "show"
                        );

                    }

                }
            );

        }

    }
);

</script>


<!-- =========================================================
     GLOBAL THEME
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

        }

        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );

        if (savedTheme === "dark") {

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