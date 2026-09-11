<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/* ==========================================
   FETCH BOOKS
========================================== */

$sql = "SELECT 
            books.*,
            categories.category_name
        FROM books
        INNER JOIN categories
            ON books.category_id = categories.id
        ORDER BY books.id DESC";

$result = $conn->query($sql);


/* ==========================================
   BOOK STATISTICS
========================================== */

$totalBooks = 0;
$totalCopies = 0;
$totalAvailable = 0;
$totalIssued = 0;

$countSql = "SELECT 
                COUNT(*) AS total_books,
                COALESCE(SUM(quantity), 0) AS total_copies,
                COALESCE(SUM(available_quantity), 0) AS total_available
             FROM books";

$countResult = $conn->query($countSql);

if ($countResult && $countRow = $countResult->fetch_assoc()) {

    $totalBooks = $countRow['total_books'];
    $totalCopies = $countRow['total_copies'];
    $totalAvailable = $countRow['total_available'];

    $totalIssued = $totalCopies - $totalAvailable;
}


/* ==========================================
   ACTIVE ISSUED BOOKS
========================================== */

$issuedSql = "SELECT COUNT(*) AS total
              FROM issued_books
              WHERE status = 'Issued'";

$issuedResult = $conn->query($issuedSql);

if ($issuedResult && $issuedRow = $issuedResult->fetch_assoc()) {
    $totalIssued = $issuedRow['total'];
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

    <title>Manage Books | Library Management System</title>


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

            box-shadow: 0 5px 12px rgba(37, 99, 235, 0.2);
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
                0 2px 8px rgba(15, 23, 42, 0.03);

            transition: 0.2s ease;
        }

        .book-stat-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 7px 20px rgba(15, 23, 42, 0.06);
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
                0 2px 8px rgba(15, 23, 42, 0.03);
        }


        /* =========================================
           TABLE HEADER
        ========================================= */

        .books-table-header {
            padding: 18px 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom: 1px solid #edf0f4;
        }

        .books-table-header h5 {
            margin: 0;

            font-size: 15px;

            font-weight: 700;

            color: #172033;
        }

        .books-table-header span {
            font-size: 12px;

            color: #94a3b8;
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
        }


        /* =========================================
           ISBN
        ========================================= */

        .book-isbn {
            font-family: monospace;

            color: #64748b;

            font-size: 12px;
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


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1100px) {

            .book-stat-grid {
                grid-template-columns:
                    repeat(2, 1fr);
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

            .books-table-header {
                align-items: flex-start;

                flex-direction: column;

                gap: 12px;
            }

            .book-table-search {
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

            .add-book-btn {
                width: 100%;

                justify-content: center;
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

                    <span class="notification-dot"></span>

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

                <i class="bi bi-box-arrow-right"></i>

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

                <i class="bi bi-plus-lg"></i>

                Add New Book

            </a>

        </div>



        <!-- ==================================
             STATISTICS
        =================================== -->

        <div class="book-stat-grid">


            <!-- Total Books -->

            <div class="book-stat-card">

                <div class="book-stat-icon book-stat-blue">

                    <i class="bi bi-book"></i>

                </div>

                <div class="book-stat-info">

                    <span>
                        Total Books
                    </span>

                    <strong>
                        <?php echo $totalBooks; ?>
                    </strong>

                </div>

            </div>


            <!-- Total Copies -->

            <div class="book-stat-card">

                <div class="book-stat-icon book-stat-green">

                    <i class="bi bi-collection"></i>

                </div>

                <div class="book-stat-info">

                    <span>
                        Total Copies
                    </span>

                    <strong>
                        <?php echo $totalCopies; ?>
                    </strong>

                </div>

            </div>


            <!-- Available -->

            <div class="book-stat-card">

                <div class="book-stat-icon book-stat-orange">

                    <i class="bi bi-bookmark-check"></i>

                </div>

                <div class="book-stat-info">

                    <span>
                        Available Copies
                    </span>

                    <strong>
                        <?php echo $totalAvailable; ?>
                    </strong>

                </div>

            </div>


            <!-- Issued -->

            <div class="book-stat-card">

                <div class="book-stat-icon book-stat-purple">

                    <i class="bi bi-journal-arrow-up"></i>

                </div>

                <div class="book-stat-info">

                    <span>
                        Currently Issued
                    </span>

                    <strong>
                        <?php echo $totalIssued; ?>
                    </strong>

                </div>

            </div>

        </div>



        <!-- ==================================
             BOOK TABLE
        =================================== -->

        <div class="books-table-card">


            <!-- TABLE HEADER -->

            <div class="books-table-header">

                <div>

                    <h5>
                        All Books
                    </h5>

                    <span>
                        <?php echo $totalBooks; ?> books in library
                    </span>

                </div>


                <!-- Search -->

                <div class="book-table-search">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        id="bookSearch"
                        placeholder="Search books..."
                        autocomplete="off"
                    >

                </div>

            </div>



            <!-- TABLE -->

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


                    <tbody id="booksTableBody">


                    <?php

                    if ($result && $result->num_rows > 0) {

                        $count = 1;

                        while (
                            $book = $result->fetch_assoc()
                        ) {

                            $available =
                                (int)$book['available_quantity'];

                            $quantity =
                                (int)$book['quantity'];

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

                                <div class="book-title-cell">

                                    <div class="book-icon">

                                        <i class="bi bi-book-half"></i>

                                    </div>

                                    <span class="book-title">

                                        <?php
                                        echo htmlspecialchars(
                                            $book['title']
                                        );
                                        ?>

                                    </span>

                                </div>

                            </td>


                            <!-- AUTHOR -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $book['author']
                                );
                                ?>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="book-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $book['category_name']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- ISBN -->

                            <td>

                                <span class="book-isbn">

                                    <?php

                                    echo !empty(
                                        $book['isbn']
                                    )
                                        ? htmlspecialchars(
                                            $book['isbn']
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

                                <?php

                                if ($isAvailable) {

                                ?>

                                    <span
                                        class="stock-status in-stock"
                                    >

                                        <span
                                            class="stock-dot"
                                        ></span>

                                        In Stock

                                    </span>

                                <?php

                                } else {

                                ?>

                                    <span
                                        class="stock-status out-stock"
                                    >

                                        <span
                                            class="stock-dot"
                                        ></span>

                                        Out of Stock

                                    </span>

                                <?php

                                }

                                ?>

                                <small
                                    class="available-text"
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

                                <div class="book-actions">


                                    <!-- EDIT -->

                                    <a
                                        href="edit.php?id=<?php
                                        echo $book['id'];
                                        ?>"
                                        class="book-action-btn edit-book-btn"
                                        title="Edit Book"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <!-- DELETE -->

                                    <a
                                        href="delete.php?id=<?php
                                        echo $book['id'];
                                        ?>"
                                        class="book-action-btn delete-book-btn"
                                        title="Delete Book"
                                        onclick="return confirm(
                                            'Are you sure you want to delete this book?'
                                        );"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </a>

                                </div>

                            </td>

                        </tr>


                    <?php

                        }

                    } else {

                    ?>

                        <tr>

                            <td
                                colspan="8"
                                class="books-empty"
                            >

                                <i class="bi bi-book"></i>

                                <strong>
                                    No Books Found
                                </strong>

                                <span>
                                    Add your first book to the library.
                                </span>

                            </td>

                        </tr>

                    <?php

                    }

                    ?>

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
        document.getElementById("sidebarToggle");

    const sidebar =
        document.getElementById("adminSidebar");

    if (sidebarToggle && sidebar) {

        sidebarToggle.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle("show");

            }
        );

    }


    /* =========================================
       BOOK SEARCH
    ========================================= */

    const bookSearch =
        document.getElementById("bookSearch");

    const bookRows =
        document.querySelectorAll(".book-row");


    if (bookSearch) {

        bookSearch.addEventListener(
            "keyup",
            function () {

                const searchValue =
                    this.value.toLowerCase().trim();


                bookRows.forEach(
                    function (row) {

                        const rowText =
                            row.textContent.toLowerCase();

                        if (
                            rowText.includes(searchValue)
                        ) {

                            row.style.display = "";

                        } else {

                            row.style.display = "none";

                        }

                    }
                );

            }
        );

    }


    /* =========================================
       CTRL + K SEARCH
    ========================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                (event.ctrlKey || event.metaKey) &&
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