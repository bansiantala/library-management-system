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
    die(
        "Dompdf not found. Please run: composer require dompdf/dompdf"
    );
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

    $quantity =
        (int)$book['quantity'];

    $available =
        (int)$book['available_quantity'];

    $totalQuantity += $quantity;

    $totalAvailable += $available;

    $totalIssuedCopies +=
        max(
            0,
            $quantity - $available
        );

}


/*
|--------------------------------------------------------------------------
| Selected Category Name
|--------------------------------------------------------------------------
*/

$selectedCategoryName = '';

if ($category_id > 0) {

    foreach ($categories as $category) {

        if (
            (int)$category['id'] === $category_id
        ) {

            $selectedCategoryName =
                $category['category_name'];

            break;

        }

    }

}


/*
|--------------------------------------------------------------------------
| DOWNLOAD PDF
|--------------------------------------------------------------------------
*/

if ($download === 'pdf') {

    /*
    |--------------------------------------------------------------------------
    | Create Dompdf
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
    margin: 5px 0 0;
    font-size: 15px;
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
        Book Report
    </h2>

</div>


<table class="info-table">

    <tr>

        <td class="info-label">
            Generated On
        </td>

        <td>
            ' . htmlspecialchars(
                date("d M Y h:i A")
            ) . '
        </td>

    </tr>
';


/*
|--------------------------------------------------------------------------
| Search Info
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $pdfHtml .= '

    <tr>

        <td class="info-label">
            Search
        </td>

        <td>
            ' . htmlspecialchars(
                $search
            ) . '
        </td>

    </tr>

    ';

}


/*
|--------------------------------------------------------------------------
| Category Info
|--------------------------------------------------------------------------
*/

if ($selectedCategoryName !== '') {

    $pdfHtml .= '

    <tr>

        <td class="info-label">
            Category
        </td>

        <td>
            ' . htmlspecialchars(
                $selectedCategoryName
            ) . '
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
                ' . $totalBookTitles . '
            </span>

            <span class="summary-label">
                Book Titles
            </span>

        </td>


        <td>

            <span class="summary-number">
                ' . $totalQuantity . '
            </span>

            <span class="summary-label">
                Total Copies
            </span>

        </td>


        <td>

            <span class="summary-number">
                ' . $totalAvailable . '
            </span>

            <span class="summary-label">
                Available Copies
            </span>

        </td>


        <td>

            <span class="summary-number">
                ' . $totalIssuedCopies . '
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
| PDF Table
|--------------------------------------------------------------------------
*/

if (empty($books)) {

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
        $books as $index => $book
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
                ' . htmlspecialchars(
                    $book['title']
                ) . '
            </td>

            <td>
                ' . htmlspecialchars(
                    $book['author']
                ) . '
            </td>

            <td>
                ' . htmlspecialchars(
                    $book['category_name']
                    ?? 'N/A'
                ) . '
            </td>

            <td>
                ' . htmlspecialchars(
                    $book['isbn']
                    ?? 'N/A'
                ) . '
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
    Book Report
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


/*
|--------------------------------------------------------------------------
| A4 Landscape
|--------------------------------------------------------------------------
*/

$dompdf->setPaper(
    'A4',
    'landscape'
);


/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

$dompdf->render();


/*
|--------------------------------------------------------------------------
| Download
|--------------------------------------------------------------------------
*/

$dompdf->stream(
    'books_report_' .
    date('Y-m-d_H-i-s') .
    '.pdf',
    [
        'Attachment' => true
    ]
);

exit();


/*
|--------------------------------------------------------------------------
| PDF Download URL
|--------------------------------------------------------------------------
*/

}

$downloadQuery = http_build_query(
    [
        'search' => $search,
        'category_id' => $category_id,
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

            grid-template-columns:
                repeat(4, 1fr);

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

            background: #fff;

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
                1fr
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
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .report-summary {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .filter-card form {

                grid-template-columns:
                    1fr 1fr;
            }


            .search-btn,
            .reset-btn {

                width: 100%;
            }

        }


        @media (max-width: 700px) {

            .report-actions {

                width: 100%;
            }


            .download-btn,
            .print-btn {

                flex: 1;

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

                grid-template-columns:
                    1fr;
            }


            .filter-card form {

                grid-template-columns:
                    1fr;
            }


            .table-header {

                align-items: flex-start;

                flex-direction: column;
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

                grid-template-columns:
                    repeat(4, 1fr);
            }


            .summary-card,
            .table-card {

                box-shadow: none;
            }


            .table-card {

                border: 1px solid #ddd;
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

                        <i class="bi bi-book"></i>

                        Book Report

                    </h2>


                    <p>

                        View and filter complete library
                        book records.

                    </p>

                </div>


                <!-- =================================================
                     ACTION BUTTONS
                ================================================== -->

                <div class="report-actions">


                    <!-- DOWNLOAD PDF -->

                    <a
                        href="?<?php
                        echo htmlspecialchars(
                            $downloadQuery
                        );
                        ?>"
                        class="download-btn no-print"
                        title="Download PDF"
                    >

                        <i
                            class="bi bi-file-earmark-pdf"
                        ></i>

                        Download PDF

                    </a>



                </div>


            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="report-summary">


                <!-- BOOK TITLES -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-book"></i>

                    </div>

                    <h3>

                        <?php
                        echo $totalBookTitles;
                        ?>

                    </h3>

                    <p>
                        Book Titles
                    </p>

                </div>


                <!-- TOTAL COPIES -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-stack"></i>

                    </div>

                    <h3>

                        <?php
                        echo $totalQuantity;
                        ?>

                    </h3>

                    <p>
                        Total Copies
                    </p>

                </div>


                <!-- AVAILABLE -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="bi bi-bookmark-check"
                        ></i>

                    </div>

                    <h3>

                        <?php
                        echo $totalAvailable;
                        ?>

                    </h3>

                    <p>
                        Available Copies
                    </p>

                </div>


                <!-- ISSUED -->

                <div class="summary-card">

                    <div class="icon">

                        <i
                            class="bi bi-journal-arrow-up"
                        ></i>

                    </div>

                    <h3>

                        <?php
                        echo $totalIssuedCopies;
                        ?>

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
                            value="<?php
                            echo htmlspecialchars(
                                $search
                            );
                            ?>"
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


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>


                                <option
                                    value="<?php
                                    echo (int)
                                        $category['id'];
                                    ?>"
                                    <?php

                                    echo
                                        $category_id
                                        ===
                                        (int)$category['id']
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $category[
                                            'category_name'
                                        ]
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

                        <i
                            class="bi bi-arrow-clockwise"
                        ></i>

                        Reset

                    </a>


                </form>


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

                        <?php
                        echo count($books);
                        ?>

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

                            No book records match your
                            search criteria.

                        </p>


                    </div>


                <?php else: ?>


                    <div class="table-responsive">


                        <table class="report-table">


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

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


                                <?php foreach (
                                    $books
                                    as $index => $book
                                ): ?>


                                    <?php

                                    $quantity =
                                        (int)
                                        $book[
                                            'quantity'
                                        ];

                                    $available =
                                        (int)
                                        $book[
                                            'available_quantity'
                                        ];

                                    $issued =
                                        max(
                                            0,
                                            $quantity -
                                            $available
                                        );

                                    ?>


                                    <tr>


                                        <!-- NUMBER -->

                                        <td>

                                            <?php
                                            echo $index + 1;
                                            ?>

                                        </td>


                                        <!-- TITLE -->

                                        <td>

                                            <div
                                                class="book-title"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $book[
                                                        'title'
                                                    ]
                                                );

                                                ?>

                                            </div>

                                        </td>


                                        <!-- AUTHOR -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $book[
                                                    'author'
                                                ]
                                            );

                                            ?>

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
                                                    $book[
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
                                                $book[
                                                    'isbn'
                                                ]
                                                ?? 'N/A'
                                            );

                                            ?>

                                        </td>


                                        <!-- TOTAL -->

                                        <td>

                                            <span
                                                class="
                                                    quantity-badge
                                                "
                                            >

                                                <?php
                                                echo $quantity;
                                                ?>

                                            </span>

                                        </td>


                                        <!-- AVAILABLE -->

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


                                        <!-- ISSUED -->

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


                                        <!-- DATE -->

                                        <td>

                                            <?php

                                            echo !empty(
                                                $book[
                                                    'created_at'
                                                ]
                                            )

                                                ? date(
                                                    "d M Y",
                                                    strtotime(
                                                        $book[
                                                            'created_at'
                                                        ]
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


                    <!-- FOOTER -->

                    <div class="report-footer">

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


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>