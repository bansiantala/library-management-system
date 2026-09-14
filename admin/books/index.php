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
           ADMIN NAVBAR
        ========================================= */

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
                rgba(15, 23, 42, .04);

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

            gap: 12px;

        }


        .notification-btn,
        .theme-toggle-btn {

            width: 38px;

            height: 38px;

            border: 1px solid #e2e8f0;

            background: #ffffff;

            color: #475569;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            transition: all .2s ease;

            position: relative;

            padding: 0;

        }


        .notification-btn:hover,
        .theme-toggle-btn:hover {

            background: #f8fafc;

            color: #2563eb;

            border-color: #cbd5e1;

        }


        .notification-dot {

            position: absolute;

            top: 7px;

            right: 7px;

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #ef4444;

            border: 2px solid #ffffff;

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

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

        }


        .nav-admin-info {

            display: flex;

            flex-direction: column;

            line-height: 1.2;

        }


        .nav-admin-info strong {

            color: #1e293b;

            font-size: 13px;

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

            transition: .2s ease;

        }


        .admin-logout-btn:hover {

            background: #fee2e2;

            color: #b91c1c;

        }


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
           DARK MODE
        ========================================= */

        body.library-dark-mode {

            background: #0f172a !important;

            color: #e2e8f0;

        }


        body.library-dark-mode .admin-main {

            background: #0f172a !important;

        }


        body.library-dark-mode .admin-navbar {

            background: #111827 !important;

            border-bottom-color: #334155 !important;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, .20);

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


        body.library-dark-mode .notification-btn:hover,
        body.library-dark-mode .theme-toggle-btn:hover {

            background: #334155 !important;

            color: #60a5fa !important;

        }


        body.library-dark-mode .theme-toggle-btn {

            color: #facc15 !important;

        }


        body.library-dark-mode .header-divider {

            background: #475569 !important;

        }


        body.library-dark-mode .nav-avatar {

            background: #334155 !important;

            color: #e2e8f0 !important;

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


        body.library-dark-mode .admin-logout-btn:hover {

            background: #4c1d2c !important;

            color: #fda4af !important;

        }


        body.library-dark-mode .books-page-title h2 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .books-page-title p {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-stat-card,
        body.library-dark-mode .books-table-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #e2e8f0 !important;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, .18);

        }


        body.library-dark-mode .book-stat-info span {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-stat-info strong {

            color: #f8fafc !important;

        }


        body.library-dark-mode .books-table-header {

            border-bottom-color: #334155 !important;

        }


        body.library-dark-mode .books-table-header h5 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .books-table-header span {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-filter-card {

            background: #172033 !important;

            border-bottom-color: #334155 !important;

        }


        body.library-dark-mode .book-filter-group label {

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .book-filter-group input,
        body.library-dark-mode .book-filter-group select {

            background: #1e293b !important;

            border-color: #475569 !important;

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .book-filter-group input::placeholder {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-filter-reset-btn {

            background: #1e293b !important;

            border-color: #475569 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .book-filter-reset-btn:hover {

            background: #334155 !important;

            color: #ffffff !important;

        }


        body.library-dark-mode .book-active-filters {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-filter-tag {

            background: #1e3a8a !important;

            border-color: #1d4ed8 !important;

            color: #bfdbfe !important;

        }


        body.library-dark-mode .book-table-search {

            background: #1e293b !important;

            border-color: #475569 !important;

        }


        body.library-dark-mode .book-table-search i {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .book-table-search input {

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .book-table-search input::placeholder {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .books-table thead th {

            background: #273449 !important;

            color: #cbd5e1 !important;

            border-bottom-color: #475569 !important;

        }


        body.library-dark-mode .books-table tbody td {

            color: #cbd5e1 !important;

            background: #1e293b !important;

            border-bottom-color: #334155 !important;

        }


        body.library-dark-mode .books-table tbody tr:hover td {

            background: #273449 !important;

        }


        body.library-dark-mode .book-title {

            color: #f8fafc !important;

        }


        body.library-dark-mode .book-icon {

            background: #1e3a8a !important;

            color: #93c5fd !important;

        }


        body.library-dark-mode .book-category {

            background: #273449 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .book-isbn {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .available-text {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .books-empty {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .books-empty strong {

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .books-empty i {

            color: #64748b !important;

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


        @media (max-width: 992px) {

            .nav-admin-info {

                display: none;

            }


            .admin-navbar {

                padding: 0 18px;

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


            .admin-navbar {

                padding: 0 12px;

            }


            .navbar-title span {

                display: none;

            }


            .admin-logout-btn span {

                display: none;

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
                    Admin Dashboard
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


            <!-- NOTIFICATION -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
                aria-label="Notifications"
            >

                <i class="bi bi-bell"></i>


                <?php if ($totalIssued > 0): ?>

                    <span
                        class="notification-dot"
                    ></span>

                <?php endif; ?>


            </button>


            <!-- THEME TOGGLE -->

         


            <!-- DIVIDER -->

            <div class="header-divider"></div>


            <!-- ADMIN PROFILE -->

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
                    Manage Books
                </h2>


                <p>
                    Manage your library books, stock and categories.
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


            <!-- TOTAL BOOKS -->

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


            <!-- TOTAL COPIES -->

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


            <!-- AVAILABLE -->

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


            <!-- ISSUED -->

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


                <!-- QUICK CLIENT SEARCH -->

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
                            placeholder="Search title, author or ISBN..."
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


                            <tr
                                class="book-row"
                            >


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
                                        bi
                                        bi-search
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
     SIDEBAR TOGGLE
========================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

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


            document.addEventListener(
                "click",
                function (event) {

                    if (

                        window.innerWidth <= 992 &&

                        sidebar.classList.contains(
                            "show"
                        ) &&

                        !sidebar.contains(
                            event.target
                        ) &&

                        !sidebarToggle.contains(
                            event.target
                        )

                    ) {

                        sidebar.classList.remove(
                            "show"
                        );

                    }

                }
            );

        }

    }
);

</script>


<!-- ==========================================
     QUICK SEARCH IN CURRENT RESULTS
========================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const bookSearch =
            document.getElementById(
                "bookSearch"
            );


        const bookRows =
            document.querySelectorAll(
                ".book-row"
            );


        if (!bookSearch) {

            return;

        }


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
);

</script>


<!-- ==========================================
     CTRL + K QUICK SEARCH
========================================== -->

<script>

document.addEventListener(
    "keydown",
    function (event) {

        if (

            (event.ctrlKey ||
             event.metaKey) &&

            event.key.toLowerCase() === "k"

        ) {

            event.preventDefault();


            const bookSearch =
                document.getElementById(
                    "bookSearch"
                );


            if (bookSearch) {

                bookSearch.focus();

            }

        }

    }
);

</script>


<!-- ==========================================
     GLOBAL THEME TOGGLE
========================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const body =
            document.body;


        const adminThemeButton =
            document.getElementById(
                "adminThemeToggle"
            );


        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );


        function updateThemeButton() {


            if (!adminThemeButton) {

                return;

            }


            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );


            adminThemeButton.innerHTML =
                isDark
                    ? '<i class="bi bi-sun-fill"></i>'
                    : '<i class="bi bi-moon-fill"></i>';


            adminThemeButton.title =
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode";


            adminThemeButton.setAttribute(
                "aria-label",
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode"
            );

        }


        /* LOAD SAVED GLOBAL THEME */

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


        /* TOGGLE THEME */

        if (adminThemeButton) {

            adminThemeButton.addEventListener(
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


    }
);

</script>


<!-- ==========================================
     BOOTSTRAP JS
========================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>