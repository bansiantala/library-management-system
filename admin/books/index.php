<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


/* ==========================================
   SEARCH & FILTER
========================================== */

$search = trim($_GET['search'] ?? '');

$selectedCategory =
    (int)($_GET['category'] ?? 0);


/* ==========================================
   FETCH CATEGORIES
========================================== */

$categories = [];

$categoryStmt = $conn->prepare(
    "SELECT
        id,
        category_name
     FROM categories
     ORDER BY category_name ASC"
);

if ($categoryStmt) {

    $categoryStmt->execute();

    $categoryResult =
        $categoryStmt->get_result();

    while (
        $categoryRow =
        $categoryResult->fetch_assoc()
    ) {

        $categories[] = $categoryRow;

    }

    $categoryStmt->close();
}


/* ==========================================
   FETCH BOOKS WITH SEARCH + FILTER
========================================== */

$sql = "
    SELECT
        books.*,
        categories.category_name

    FROM books

    INNER JOIN categories
        ON books.category_id = categories.id

    WHERE 1 = 1
";

$params = [];
$types = "";


/* ==========================================
   SEARCH
   TITLE / AUTHOR / ISBN
========================================== */

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR books.isbn LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}


/* ==========================================
   CATEGORY FILTER
========================================== */

if ($selectedCategory > 0) {

    $sql .= "
        AND books.category_id = ?
    ";

    $params[] =
        $selectedCategory;

    $types .= "i";
}


/* ==========================================
   ORDER
========================================== */

$sql .= "
    ORDER BY books.id DESC
";


/* ==========================================
   PREPARE
========================================== */

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


/* ==========================================
   BIND
========================================== */

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


/* ==========================================
   EXECUTE
========================================== */

$stmt->execute();

$result =
    $stmt->get_result();


/* ==========================================
   FILTERED BOOK COUNT
========================================== */

$filteredBooks =
    $result->num_rows;


/* ==========================================
   BOOK STATISTICS
   These remain GLOBAL statistics
========================================== */

$totalBooks = 0;
$totalCopies = 0;
$totalAvailable = 0;
$totalIssued = 0;


$countSql = "
    SELECT
        COUNT(*) AS total_books,
        COALESCE(SUM(quantity), 0) AS total_copies,
        COALESCE(SUM(available_quantity), 0) AS total_available

    FROM books
";


$countResult =
    $conn->query($countSql);


if (
    $countResult &&
    $countRow =
    $countResult->fetch_assoc()
) {

    $totalBooks =
        (int)$countRow['total_books'];

    $totalCopies =
        (int)$countRow['total_copies'];

    $totalAvailable =
        (int)$countRow['total_available'];

    $totalIssued =
        max(
            0,
            $totalCopies -
            $totalAvailable
        );

}


/* ==========================================
   ACTIVE ISSUED BOOKS
========================================== */

$issuedSql = "
    SELECT COUNT(*) AS total

    FROM issued_books

    WHERE status = 'Issued'
";


$issuedResult =
    $conn->query($issuedSql);


if (
    $issuedResult &&
    $issuedRow =
    $issuedResult->fetch_assoc()
) {

    $totalIssued =
        (int)$issuedRow['total'];

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
        Manage Books | Library Management System
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
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


        /* =========================================
           BOOKS PAGE
        ========================================= */

        .books-page {

            padding: 28px;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .books-page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 25px;

            gap: 20px;
        }


        .books-page-title h2 {

            margin: 0;

            font-size: 25px;

            font-weight: 700;

            color: #172033;
        }


        .books-page-title p {

            margin: 6px 0 0;

            color: #8b95a7;

            font-size: 13px;
        }


        /* =========================================
           ADD BOOK BUTTON
        ========================================= */

        .add-book-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 10px 17px;

            background: #2563eb;

            color: #ffffff;

            border-radius: 9px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            border: none;

            transition: 0.2s ease;
        }


        .add-book-btn:hover {

            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 5px 12px
                rgba(37, 99, 235, 0.2);
        }


        /* =========================================
           STAT CARDS
        ========================================= */

        .book-stat-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .book-stat-card {

            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            padding: 19px;

            display: flex;

            align-items: center;

            gap: 14px;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);

            transition: 0.2s ease;
        }


        .book-stat-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 7px 20px
                rgba(15, 23, 42, 0.06);
        }


        .book-stat-icon {

            width: 45px;

            height: 45px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            flex-shrink: 0;
        }


        .book-stat-blue {

            background: #eff6ff;

            color: #2563eb;
        }


        .book-stat-green {

            background: #ecfdf5;

            color: #059669;
        }


        .book-stat-orange {

            background: #fff7ed;

            color: #ea580c;
        }


        .book-stat-purple {

            background: #f5f3ff;

            color: #7c3aed;
        }


        .book-stat-info span {

            display: block;

            font-size: 11px;

            color: #8b95a7;

            margin-bottom: 3px;
        }


        .book-stat-info strong {

            display: block;

            font-size: 21px;

            font-weight: 700;

            color: #172033;
        }


        /* =========================================
           TABLE CARD
        ========================================= */

        .books-table-card {

            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.03);
        }


        /* =========================================
           TABLE HEADER
        ========================================= */

        .books-table-header {

            padding: 18px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid #edf0f4;

            flex-wrap: wrap;
        }


        .books-table-header h5 {

            margin: 0;

            font-size: 15px;

            font-weight: 700;

            color: #172033;
        }


        .books-table-header span {

            display: block;

            margin-top: 4px;

            font-size: 12px;

            color: #94a3b8;
        }


        /* =========================================
           ADVANCED SEARCH FILTER
        ========================================= */

        .book-filter-card {

            padding: 18px 20px;

            background: #fbfcfe;

            border-bottom: 1px solid #edf0f4;
        }


        .book-filter-form {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                240px
                auto
                auto;

            gap: 12px;

            align-items: end;
        }


        .book-filter-group {

            min-width: 0;
        }


        .book-filter-group label {

            display: block;

            margin-bottom: 6px;

            color: #475569;

            font-size: 11px;

            font-weight: 700;
        }


        .book-filter-group input,
        .book-filter-group select {

            width: 100%;

            height: 40px;

            padding: 0 12px;

            border: 1px solid #d8e0ea;

            border-radius: 8px;

            background: #ffffff;

            color: #334155;

            outline: none;

            font-size: 12px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .book-filter-group input::placeholder {

            color: #a1a9b6;
        }


        .book-filter-group input:focus,
        .book-filter-group select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);
        }


        .book-filter-search-btn,
        .book-filter-reset-btn {

            height: 40px;

            padding: 0 15px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 12px;

            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;

            transition: .2s ease;
        }


        .book-filter-search-btn {

            background: #2563eb;

            border: 1px solid #2563eb;

            color: #ffffff;

            cursor: pointer;
        }


        .book-filter-search-btn:hover {

            background: #1d4ed8;

            border-color: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .book-filter-reset-btn {

            background: #f8fafc;

            border: 1px solid #dbe3ed;

            color: #64748b;
        }


        .book-filter-reset-btn:hover {

            background: #eef2f7;

            color: #334155;
        }


        /* =========================================
           ACTIVE FILTERS
        ========================================= */

        .book-active-filters {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;

            margin-top: 12px;

            color: #64748b;

            font-size: 11px;
        }


        .book-active-filters > i {

            color: #2563eb;
        }


        .book-filter-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            background: #eff6ff;

            color: #2563eb;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;
        }


        /* =========================================
           TABLE SEARCH
        ========================================= */

        .book-table-search {

            width: 220px;

            height: 36px;

            display: flex;

            align-items: center;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 7px;

            padding: 0 10px;
        }


        .book-table-search i {

            color: #94a3b8;

            font-size: 14px;
        }


        .book-table-search input {

            width: 100%;

            border: none;

            outline: none;

            background: transparent;

            margin-left: 8px;

            font-size: 12px;

            color: #334155;
        }


        .book-table-search input::placeholder {

            color: #a1a9b6;
        }


        /* =========================================
           TABLE
        ========================================= */

        .books-table-wrapper {

            overflow-x: auto;
        }


        .books-table {

            width: 100%;

            margin: 0;

            border-collapse: collapse;

            min-width: 950px;
        }


        .books-table thead th {

            background: #f8fafc;

            color: #64748b;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            padding: 13px 16px;

            border-bottom: 1px solid #e9edf3;

            white-space: nowrap;
        }


        .books-table tbody td {

            padding: 15px 16px;

            font-size: 13px;

            color: #475569;

            border-bottom: 1px solid #f1f3f6;

            vertical-align: middle;
        }


        .books-table tbody tr:last-child td {

            border-bottom: none;
        }


        .books-table tbody tr:hover {

            background: #fafbfc;
        }


        /* =========================================
           BOOK TITLE
        ========================================= */

        .book-title-cell {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 190px;
        }


        .book-icon {

            width: 35px;

            height: 35px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 16px;

            flex-shrink: 0;
        }


        .book-title {

            font-weight: 600;

            color: #1e293b;

            font-size: 13px;
        }


        /* =========================================
           CATEGORY
        ========================================= */

        .book-category {

            display: inline-block;

            padding: 5px 9px;

            background: #f1f5f9;

            color: #475569;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }


        /* =========================================
           ISBN
        ========================================= */

        .book-isbn {

            font-family: monospace;

            color: #64748b;

            font-size: 12px;

            white-space: nowrap;
        }


        /* =========================================
           STOCK STATUS
        ========================================= */

        .stock-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }


        .stock-status.in-stock {

            background: #ecfdf5;

            color: #059669;
        }


        .stock-status.out-stock {

            background: #fef2f2;

            color: #dc2626;
        }


        .stock-dot {

            width: 5px;

            height: 5px;

            border-radius: 50%;

            background: currentColor;
        }


        .available-text {

            display: block;

            margin-top: 4px;

            color: #94a3b8;

            font-size: 10px;
        }


        /* =========================================
           ACTION BUTTONS
        ========================================= */

        .book-actions {

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .book-action-btn {

            width: 32px;

            height: 32px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s ease;
        }


        .edit-book-btn {

            background: #eff6ff;

            color: #2563eb;
        }


        .edit-book-btn:hover {

            background: #2563eb;

            color: #ffffff;
        }


        .delete-book-btn {

            background: #fef2f2;

            color: #dc2626;
        }


        .delete-book-btn:hover {

            background: #dc2626;

            color: #ffffff;
        }


        /* =========================================
           EMPTY STATE
        ========================================= */

        .books-empty {

            text-align: center;

            padding: 55px 20px !important;

            color: #94a3b8 !important;
        }


        .books-empty i {

            display: block;

            font-size: 40px;

            margin-bottom: 10px;

            color: #cbd5e1;
        }


        .books-empty strong {

            display: block;

            color: #64748b;

            font-size: 14px;

            margin-bottom: 4px;
        }


        .books-empty span {

            font-size: 12px;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1100px) {

            .book-stat-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .book-filter-form {

                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media (max-width: 768px) {

            .books-page {

                padding: 20px 15px;
            }


            .books-page-header {

                align-items: flex-start;

                gap: 15px;

                flex-direction: column;
            }


            .add-book-btn {

                width: 100%;

                justify-content: center;
            }


            .books-table-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 12px;
            }


            .book-table-search {

                width: 100%;
            }


            .book-filter-form {

                grid-template-columns: 1fr;
            }


            .book-filter-search-btn,
            .book-filter-reset-btn {

                width: 100%;
            }

        }


        @media (max-width: 576px) {

            .book-stat-grid {

                grid-template-columns: 1fr;
            }


            .books-page-title h2 {

                font-size: 21px;
            }


            .book-filter-card {

                padding: 16px;
            }

        }


        /* =========================================
           PRINT
        ========================================= */

        @media print {

            .admin-sidebar,
            .admin-navbar,
            .book-filter-card,
            .book-table-search,
            .book-actions {

                display: none !important;
            }


            .admin-main {

                margin-left: 0 !important;
            }


            .books-page {

                padding: 10px;
            }


            .books-table-card {

                box-shadow: none;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     ADMIN SIDEBAR
========================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="admin-main">


    <!-- ======================================
         ADMIN NAVBAR
    ======================================= -->

    <nav class="admin-navbar">


        <!-- LEFT -->

        <div class="navbar-left">


            <div class="navbar-title">


                <h5>
                    Manage Books
                </h5>


                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Books

                </span>


            </div>


        </div>


        <!-- RIGHT -->

        <div class="navbar-right">


            <!-- Notification -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >

                <i class="bi bi-bell"></i>

                <?php if ($totalIssued > 0): ?>

                    <span
                        class="notification-dot"
                    ></span>

                <?php endif; ?>

            </button>


            <!-- Divider -->

            <div class="header-divider"></div>


            <!-- Admin -->

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


            <!-- Logout -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
                title="Logout"
            >

                <i
                    class="bi bi-box-arrow-right"
                ></i>


                <span>

                    Logout

                </span>

            </a>


        </div>


    </nav>


    <!-- ======================================
         BOOKS PAGE
    ======================================= -->

    <div class="books-page">


        <!-- PAGE HEADER -->

        <div class="books-page-header">


            <div class="books-page-title">


                <h2>
                    Books
                </h2>


                <p>
                    Manage, update and organize all library books.
                </p>


            </div>


            <a
                href="add.php"
                class="add-book-btn"
            >

                <i
                    class="bi bi-plus-lg"
                ></i>

                Add New Book

            </a>


        </div>


        <!-- ==================================
             STATISTICS
        =================================== -->

        <div class="book-stat-grid">


            <!-- Total Books -->

            <div class="book-stat-card">


                <div
                    class="
                        book-stat-icon
                        book-stat-blue
                    "
                >

                    <i
                        class="bi bi-book"
                    ></i>

                </div>


                <div
                    class="book-stat-info"
                >

                    <span>
                        Total Books
                    </span>


                    <strong>

                        <?php
                        echo $totalBooks;
                        ?>

                    </strong>

                </div>


            </div>


            <!-- Total Copies -->

            <div class="book-stat-card">


                <div
                    class="
                        book-stat-icon
                        book-stat-green
                    "
                >

                    <i
                        class="bi bi-collection"
                    ></i>

                </div>


                <div
                    class="book-stat-info"
                >

                    <span>
                        Total Copies
                    </span>


                    <strong>

                        <?php
                        echo $totalCopies;
                        ?>

                    </strong>

                </div>


            </div>


            <!-- Available -->

            <div class="book-stat-card">


                <div
                    class="
                        book-stat-icon
                        book-stat-orange
                    "
                >

                    <i
                        class="
                            bi
                            bi-bookmark-check
                        "
                    ></i>

                </div>


                <div
                    class="book-stat-info"
                >

                    <span>
                        Available Copies
                    </span>


                    <strong>

                        <?php
                        echo $totalAvailable;
                        ?>

                    </strong>

                </div>


            </div>


            <!-- Issued -->

            <div class="book-stat-card">


                <div
                    class="
                        book-stat-icon
                        book-stat-purple
                    "
                >

                    <i
                        class="
                            bi
                            bi-journal-arrow-up
                        "
                    ></i>

                </div>


                <div
                    class="book-stat-info"
                >

                    <span>
                        Currently Issued
                    </span>


                    <strong>

                        <?php
                        echo $totalIssued;
                        ?>

                    </strong>

                </div>


            </div>


        </div>


        <!-- ==================================
             BOOK TABLE CARD
        =================================== -->

        <div class="books-table-card">


            <!-- =================================
                 TABLE HEADER
            ================================== -->

            <div class="books-table-header">


                <div>

                    <h5>
                        All Books
                    </h5>


                    <span>

                        <?php
                        echo $filteredBooks;
                        ?>

                        <?php
                        echo $filteredBooks === 1
                            ? ' matching book'
                            : ' matching books';
                        ?>

                    </span>

                </div>


                <!-- Quick Client Search -->

                <div
                    class="book-table-search"
                >

                    <i
                        class="bi bi-search"
                    ></i>


                    <input
                        type="text"
                        id="bookSearch"
                        placeholder="Quick search in results..."
                        autocomplete="off"
                    >

                </div>


            </div>


            <!-- =================================
                 ADVANCED SEARCH & FILTER
            ================================== -->

            <div
                class="book-filter-card"
            >


                <form
                    method="GET"
                    action=""
                    class="book-filter-form"
                >


                    <!-- SEARCH -->

                    <div
                        class="book-filter-group"
                    >

                        <label
                            for="search"
                        >

                            Search Books

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
                            placeholder="
                                Search title, author or ISBN...
                            "
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div
                        class="book-filter-group"
                    >

                        <label
                            for="category"
                        >

                            Category

                        </label>


                        <select
                            id="category"
                            name="category"
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
                                    echo (
                                        $selectedCategory ===
                                        (int)$category['id']
                                    )
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


                    <!-- SEARCH BUTTON -->

                    <button
                        type="submit"
                        class="book-filter-search-btn"
                    >

                        <i
                            class="bi bi-search"
                        ></i>

                        Search

                    </button>


                    <!-- RESET BUTTON -->

                    <a
                        href="<?php
                            echo BASE_URL;
                        ?>/admin/books/index.php"
                        class="book-filter-reset-btn"
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


                <!-- =================================
                     ACTIVE FILTERS
                ================================== -->

                <?php if (
                    $search !== '' ||
                    $selectedCategory > 0
                ): ?>


                    <div
                        class="book-active-filters"
                    >


                        <i
                            class="
                                bi
                                bi-info-circle-fill
                            "
                        ></i>


                        <span>
                            Active Filters:
                        </span>


                        <?php if (
                            $search !== ''
                        ): ?>


                            <span
                                class="
                                    book-filter-tag
                                "
                            >

                                <i
                                    class="
                                        bi
                                        bi-search
                                    "
                                ></i>


                                <?php

                                echo htmlspecialchars(
                                    $search
                                );

                                ?>

                            </span>


                        <?php endif; ?>


                        <?php if (
                            $selectedCategory > 0
                        ): ?>


                            <?php

                            $selectedCategoryName =
                                'Category';

                            foreach (
                                $categories
                                as $category
                            ) {

                                if (
                                    (int)
                                    $category['id']
                                    ===
                                    $selectedCategory
                                ) {

                                    $selectedCategoryName =
                                        $category[
                                            'category_name'
                                        ];

                                    break;

                                }

                            }

                            ?>


                            <span
                                class="
                                    book-filter-tag
                                "
                            >

                                <i
                                    class="
                                        bi
                                        bi-tag-fill
                                    "
                                ></i>


                                <?php

                                echo htmlspecialchars(
                                    $selectedCategoryName
                                );

                                ?>

                            </span>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


            <!-- ==================================
                 TABLE
            =================================== -->

            <div class="books-table-wrapper">


                <table class="books-table">


                    <thead>


                        <tr>


                            <th>
                                #
                            </th>


                            <th>
                                Book
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
                                Quantity
                            </th>


                            <th>
                                Availability
                            </th>


                            <th>
                                Actions
                            </th>


                        </tr>


                    </thead>


                    <tbody
                        id="booksTableBody"
                    >


                    <?php if (
                        $result &&
                        $result->num_rows > 0
                    ): ?>


                        <?php

                        $count = 1;

                        while (
                            $book =
                            $result->fetch_assoc()
                        ):

                        ?>


                            <?php

                            $available =
                                (int)
                                $book[
                                    'available_quantity'
                                ];


                            $quantity =
                                (int)
                                $book[
                                    'quantity'
                                ];


                            $isAvailable =
                                $available > 0;

                            ?>


                            <tr class="book-row">


                                <!-- NUMBER -->

                                <td>

                                    <span
                                        class="text-muted"
                                    >

                                        <?php
                                        echo $count++;
                                        ?>

                                    </span>

                                </td>


                                <!-- BOOK -->

                                <td>


                                    <div
                                        class="
                                            book-title-cell
                                        "
                                    >


                                        <div
                                            class="book-icon"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-book-half
                                                "
                                            ></i>

                                        </div>


                                        <span
                                            class="book-title"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $book[
                                                    'title'
                                                ]
                                            );

                                            ?>

                                        </span>


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
                                            book-category
                                        "
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $book[
                                                'category_name'
                                            ]
                                        );

                                        ?>

                                    </span>


                                </td>


                                <!-- ISBN -->

                                <td>


                                    <span
                                        class="book-isbn"
                                    >

                                        <?php

                                        echo !empty(
                                            $book['isbn']
                                        )
                                            ? htmlspecialchars(
                                                $book[
                                                    'isbn'
                                                ]
                                            )
                                            : 'N/A';

                                        ?>

                                    </span>


                                </td>


                                <!-- QUANTITY -->

                                <td>


                                    <strong>

                                        <?php

                                        echo $quantity;

                                        ?>

                                    </strong>


                                </td>


                                <!-- AVAILABILITY -->

                                <td>


                                    <?php if (
                                        $isAvailable
                                    ): ?>


                                        <span
                                            class="
                                                stock-status
                                                in-stock
                                            "
                                        >

                                            <span
                                                class="
                                                    stock-dot
                                                "
                                            ></span>

                                            In Stock

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                stock-status
                                                out-stock
                                            "
                                        >

                                            <span
                                                class="
                                                    stock-dot
                                                "
                                            ></span>

                                            Out of Stock

                                        </span>


                                    <?php endif; ?>


                                    <small
                                        class="
                                            available-text
                                        "
                                    >

                                        <?php
                                        echo $available;
                                        ?>

                                        of

                                        <?php
                                        echo $quantity;
                                        ?>

                                        available

                                    </small>


                                </td>


                                <!-- ACTIONS -->

                                <td>


                                    <div
                                        class="book-actions"
                                    >


                                        <!-- EDIT -->

                                        <a
                                            href="edit.php?id=<?php
                                                echo (int)
                                                    $book['id'];
                                            ?>"
                                            class="
                                                book-action-btn
                                                edit-book-btn
                                            "
                                            title="Edit Book"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-pencil
                                                "
                                            ></i>

                                        </a>


                                        <!-- DELETE -->

                                        <a
                                            href="delete.php?id=<?php
                                                echo (int)
                                                    $book['id'];
                                            ?>"
                                            class="
                                                book-action-btn
                                                delete-book-btn
                                            "
                                            title="Delete Book"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this book?'
                                                );
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-trash
                                                "
                                            ></i>

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- EMPTY STATE -->

                        <tr>


                            <td
                                colspan="8"
                                class="books-empty"
                            >


                                <i
                                    class="
                                        bi bi-search
                                    "
                                ></i>


                                <strong>

                                    No Books Found

                                </strong>


                                <span>

                                    <?php if (
                                        $search !== '' ||
                                        $selectedCategory > 0
                                    ): ?>

                                        No books match
                                        your current
                                        search or filter.

                                    <?php else: ?>

                                        Add your first book
                                        to the library.

                                    <?php endif; ?>

                                </span>


                                <?php if (
                                    $search !== '' ||
                                    $selectedCategory > 0
                                ): ?>


                                    <div
                                        style="
                                            margin-top:15px;
                                        "
                                    >

                                        <a
                                            href="<?php
                                                echo BASE_URL;
                                            ?>/admin/books/index.php"
                                            class="
                                                book-filter-reset-btn
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-arrow-clockwise
                                                "
                                            ></i>

                                            Clear Filters

                                        </a>

                                    </div>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </div>


</div>


<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>


/* =========================================
   SIDEBAR TOGGLE
========================================= */

const sidebarToggle =
    document.getElementById(
        "sidebarToggle"
    );


const sidebar =
    document.getElementById(
        "adminSidebar"
    );


if (
    sidebarToggle &&
    sidebar
) {

    sidebarToggle.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle(
                "show"
            );

        }
    );

}


/* =========================================
   QUICK SEARCH IN CURRENT RESULTS
========================================= */

const bookSearch =
    document.getElementById(
        "bookSearch"
    );


const bookRows =
    document.querySelectorAll(
        ".book-row"
    );


if (bookSearch) {

    bookSearch.addEventListener(
        "input",
        function () {

            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            bookRows.forEach(
                function (row) {

                    const rowText =
                        row.textContent
                            .toLowerCase();


                    if (
                        rowText.includes(
                            searchValue
                        )
                    ) {

                        row.style.display =
                            "";

                    } else {

                        row.style.display =
                            "none";

                    }

                }
            );

        }
    );

}


/* =========================================
   CTRL + K QUICK SEARCH
========================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            (event.ctrlKey ||
             event.metaKey) &&
            event.key.toLowerCase() === "k"
        ) {

            event.preventDefault();


            if (bookSearch) {

                bookSearch.focus();

            }

        }

    }
);

</script>


</body>

</html>