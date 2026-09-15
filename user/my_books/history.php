```php
<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Search & Filter Values
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

/*
|--------------------------------------------------------------------------
| Validate Status
|--------------------------------------------------------------------------
*/

$allowed_statuses = [
    'Issued',
    'Returned'
];

if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = '';
}

/*
|--------------------------------------------------------------------------
| Validate Sort
|--------------------------------------------------------------------------
*/

$allowed_sorts = [
    'newest',
    'oldest'
];

if (!in_array($sort, $allowed_sorts, true)) {
    $sort = 'newest';
}

/*
|--------------------------------------------------------------------------
| Validate Dates
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
| Swap Dates If From > To
|--------------------------------------------------------------------------
*/

if (
    $date_from !== '' &&
    $date_to !== '' &&
    $date_from > $date_to
) {
    $temporary_date = $date_from;
    $date_from = $date_to;
    $date_to = $temporary_date;
}

/*
|--------------------------------------------------------------------------
| Sort Order
|--------------------------------------------------------------------------
*/

if ($sort === 'oldest') {

    $orderBy = "
        issued_books.issue_date ASC,
        issued_books.id ASC
    ";

} else {

    $orderBy = "
        issued_books.issue_date DESC,
        issued_books.id DESC
    ";

}

/*
|--------------------------------------------------------------------------
| Fetch User Book History
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        issued_books.*,
        books.title,
        books.author,
        categories.category_name

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    INNER JOIN categories
        ON books.category_id = categories.id

    WHERE issued_books.user_id = ?
";

$params = [$user_id];
$types = "i";

/*
|--------------------------------------------------------------------------
| Search By Book Title / Author
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ss";
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($filter_status !== '') {

    $sql .= "
        AND issued_books.status = ?
    ";

    $params[] = $filter_status;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Issue Date From
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
| Issue Date To
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
| Final Query
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY $orderBy
";

/*
|--------------------------------------------------------------------------
| Prepare Statement
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}

$stmt->bind_param($types, ...$params);

$stmt->execute();

$result = $stmt->get_result();

$totalHistory = $result->num_rows;

/*
|--------------------------------------------------------------------------
| Active Filters
|--------------------------------------------------------------------------
*/

$hasActiveFilters =
    $search !== '' ||
    $filter_status !== '' ||
    $date_from !== '' ||
    $date_to !== '' ||
    $sort !== 'newest';

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
        Book History - Library Management System
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


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <style>
           /* =========================================
   USER NAVBAR - UNIQUE DESIGN
========================================= */
 
.user-navbar {
    height: 78px;
    background: #ffffff;

    padding: 0 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    border-bottom: 1px solid #edf0f5;

    position: sticky;
    top: 0;
    z-index: 900;

    box-shadow: 0 3px 15px rgba(15, 23, 42, 0.035);
}


/* =========================================
   LEFT
========================================= */

.user-nav-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-welcome-icon {
    width: 43px;
    height: 43px;

    border-radius: 13px;

    background: #eff6ff;
    color: #2563eb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.user-welcome span {
    display: block;

    color: #94a3b8;

    font-size: 10px;
    font-weight: 600;

    margin-bottom: 2px;
}

.user-welcome h5 {
    margin: 0;

    color: #172033;

    font-size: 15px;
    font-weight: 800;
}


/* =========================================
   CENTER STATUS
========================================= */

.user-nav-center {
    position: absolute;

    left: 50%;

    transform: translateX(-50%);
}

.library-status {
    display: flex;
    align-items: center;
    gap: 8px;

    padding: 8px 14px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    color: #64748b;

    font-size: 11px;
    font-weight: 600;
}

.status-circle {
    width: 8px;
    height: 8px;

    background: #22c55e;

    border-radius: 50%;

    box-shadow: 0 0 0 4px rgba(34,197,94,.10);
}


/* =========================================
   RIGHT
========================================= */

.user-nav-right {
    display: flex;
    align-items: center;

    gap: 10px;
}


/* =========================================
   ACTION BUTTONS
========================================= */

.nav-action {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    color: #64748b;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.nav-action:hover {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #2563eb;

    transform: translateY(-1px);
}


/* =========================================
   FAVORITE NAV ACTION
========================================= */

.favorite-nav-action {
    color: #e11d48;
}

.favorite-nav-action:hover {
    background: #fff1f2;

    border-color: #fecdd3;

    color: #e11d48;

    transform: translateY(-1px);
}


/* =========================================
   SEPARATOR
========================================= */

.nav-separator {
    width: 1px;
    height: 34px;

    background: #e5e7eb;

    margin: 0 5px;
}


/* =========================================
   USER PROFILE PILL
========================================= */

.user-profile-pill {
    display: flex;
    align-items: center;

    gap: 9px;

    padding: 5px 10px 5px 5px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    cursor: default;
}

.user-avatar {
    width: 35px;
    height: 35px;

    border-radius: 50%;

    background: #2563eb;
    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 13px;
    font-weight: 800;
}

.user-profile-name strong {
    display: block;

    color: #334155;

    font-size: 11px;
    font-weight: 700;

    max-width: 110px;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-name small {
    display: block;

    color: #94a3b8;

    font-size: 9px;

    margin-top: 1px;
}

.profile-arrow {
    color: #94a3b8;

    font-size: 10px;

    margin-left: 2px;
}


/* =========================================
   LOGOUT
========================================= */

.user-logout {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #fff5f5;

    border: 1px solid #fee2e2;

    color: #ef4444;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.user-logout:hover {
    background: #ef4444;

    color: #ffffff;

    border-color: #ef4444;
}

        

        * {
            box-sizing: border-box;
        }


        /* ==========================================
           PAGE
        ========================================== */

        .history-page {
            padding: 35px;
            width: 100%;
        }


        .history-container {
            width: 100%;
            max-width: 1350px;
            margin: 0 auto;
        }


        /* ==========================================
           HEADER
        ========================================== */

        .history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 27px;
            flex-wrap: wrap;
        }


        .history-title-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }


        .history-title-icon {
            width: 55px;
            height: 55px;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.20);
        }


        .history-title h2 {
            margin: 0;
            color: #172033;
            font-size: 27px;
            font-weight: 800;
        }


        .history-title p {
            margin: 5px 0 0;
            color: #7b8798;
            font-size: 13px;
        }


        /* ==========================================
           HISTORY COUNT
        ========================================== */

        .history-count {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 10px 15px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            border-radius: 12px;

            color: #2563eb;

            font-size: 13px;

            font-weight: 700;
        }


        .history-count-number {
            width: 28px;
            height: 28px;

            border-radius: 8px;

            background: #2563eb;

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 12px;
        }


        /* ==========================================
           SEARCH & FILTER
        ========================================== */

        .history-filter-card {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 16px;

            padding: 20px 22px;

            margin-bottom: 23px;

            box-shadow:
                0 5px 20px
                rgba(15, 23, 42, 0.04);
        }


        .history-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 18px;
        }


        .history-filter-title {
            display: flex;
            align-items: center;
            gap: 9px;

            margin: 0;

            color: #253044;

            font-size: 15px;

            font-weight: 800;
        }


        .history-filter-title i {
            color: #2563eb;
            font-size: 18px;
        }


        .history-filter-result {
            color: #8994a4;
            font-size: 12px;
            font-weight: 600;
        }


        .history-filter-grid {
            display: grid;

            grid-template-columns:
                minmax(220px, 1.6fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                minmax(150px, 1fr)
                auto
                auto;

            gap: 12px;

            align-items: end;
        }


        .history-filter-field label {
            display: block;

            color: #788494;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            margin-bottom: 6px;
        }


        .history-search-wrapper {
            position: relative;
        }


        .history-search-wrapper i {
            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 15px;

            pointer-events: none;
        }


        .history-filter-input,
        .history-filter-select {
            width: 100%;

            height: 42px;

            padding: 0 12px;

            border: 1px solid #cbd5e1;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            font-size: 12px;

            outline: none;

            transition: 0.2s ease;
        }


        .history-search-wrapper
        .history-filter-input {
            padding-left: 38px;
        }


        .history-filter-input:focus,
        .history-filter-select:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }


        .history-search-btn,
        .history-reset-btn {
            height: 42px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 0 15px;

            border-radius: 9px;

            font-size: 12px;

            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;

            transition: 0.2s ease;
        }


        .history-search-btn {
            background: #2563eb;

            border: 1px solid #2563eb;

            color: #ffffff;

            cursor: pointer;
        }


        .history-search-btn:hover {
            background: #1d4ed8;

            border-color: #1d4ed8;

            color: #ffffff;
        }


        .history-reset-btn {
            background: #f8fafc;

            border: 1px solid #cbd5e1;

            color: #475569;
        }


        .history-reset-btn:hover {
            background: #e2e8f0;

            color: #1e293b;
        }


        /* ==========================================
           ACTIVE FILTER TAGS
        ========================================== */

        .history-active-filters {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #edf0f4;
        }


        .history-filter-label {
            color: #64748b;

            font-size: 11px;

            font-weight: 800;
        }


        .history-filter-tag {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            background: #eff6ff;

            border: 1px solid #bfdbfe;

            border-radius: 20px;

            color: #2563eb;

            font-size: 10px;

            font-weight: 700;
        }


        /* ==========================================
           SUMMARY BAR
        ========================================== */

        .history-summary {
            background: #ffffff;

            border: 1px solid #e4e9ef;

            border-radius: 16px;

            padding: 19px 22px;

            margin-bottom: 23px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            box-shadow:
                0 5px 20px
                rgba(15, 23, 42, 0.04);
        }


        .history-summary-left {
            display: flex;

            align-items: center;

            gap: 13px;
        }


        .history-summary-icon {
            width: 44px;
            height: 44px;

            border-radius: 11px;

            background: #f1f5f9;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .history-summary-text strong {
            display: block;

            color: #253044;

            font-size: 14px;
        }


        .history-summary-text span {
            color: #8994a4;

            font-size: 12px;
        }


        .history-summary-right {
            color: #8994a4;

            font-size: 12px;

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .history-summary-right i {
            color: #2563eb;
        }


        /* ==========================================
           TABLE CARD
        ========================================== */

        .history-table-card {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 8px 28px
                rgba(15, 23, 42, 0.055);
        }


        /* ==========================================
           TABLE HEADER
        ========================================== */

        .history-table-header {
            padding: 20px 23px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid #edf0f4;
        }


        .history-table-heading {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .history-table-heading i {
            color: #2563eb;

            font-size: 18px;
        }


        .history-table-heading h5 {
            margin: 0;

            color: #253044;

            font-size: 15px;

            font-weight: 800;
        }


        .history-table-heading span {
            color: #8a95a5;

            font-size: 11px;
        }


        /* ==========================================
           TABLE
        ========================================== */

        .history-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        .history-table {
            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;

            margin: 0;
        }


        .history-table thead th {
            background: #f8fafc;

            color: #788494;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.55px;

            padding: 15px 16px;

            border-bottom: 1px solid #e7ebf0;

            white-space: nowrap;
        }


        .history-table tbody td {
            padding: 16px;

            color: #475467;

            font-size: 12px;

            border-bottom: 1px solid #eef1f4;

            vertical-align: middle;

            white-space: nowrap;
        }


        .history-table tbody tr:last-child td {
            border-bottom: none;
        }


        .history-table tbody tr {
            transition: 0.2s;
        }


        .history-table tbody tr:hover {
            background: #fbfcfe;
        }


        /* ==========================================
           NUMBER
        ========================================== */

        .history-number {
            width: 32px;
            height: 32px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #f1f5f9;

            border-radius: 8px;

            color: #64748b;

            font-size: 11px;

            font-weight: 800;
        }


        /* ==========================================
           BOOK CELL
        ========================================== */

        .history-book {
            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 210px;
        }


        .history-book-icon {
            width: 43px;
            height: 43px;

            min-width: 43px;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .history-book-info strong {
            display: block;

            color: #253044;

            font-size: 12px;

            font-weight: 800;

            max-width: 180px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .history-book-info span {
            display: block;

            color: #98a2b3;

            font-size: 10px;

            margin-top: 3px;
        }


        /* ==========================================
           AUTHOR
        ========================================== */

        .author-cell {
            color: #667085 !important;
        }


        .author-cell i {
            color: #94a3b8;

            margin-right: 5px;
        }


        /* ==========================================
           CATEGORY
        ========================================== */

        .category-badge {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border-radius: 7px;

            background: #f8fafc;

            border: 1px solid #e6eaf0;

            color: #475467;

            font-size: 10px;

            font-weight: 700;
        }


        .category-badge i {
            color: #2563eb;
        }


        /* ==========================================
           DATE
        ========================================== */

        .date-cell {
            color: #475467;

            font-weight: 600;
        }


        .date-cell i {
            color: #94a3b8;

            margin-right: 5px;
        }


        /* ==========================================
           RETURNED / NOT RETURNED
        ========================================== */

        .returned-date {
            color: #15803d !important;

            font-weight: 700;
        }


        .returned-date i {
            color: #16a34a;
        }


        .not-returned {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            background: #f8fafc;

            border-radius: 7px;

            color: #98a2b3;

            font-size: 10px;

            font-weight: 600;
        }


        /* ==========================================
           FINE
        ========================================== */

        .fine-paid {
            color: #16a34a;

            font-weight: 800;
        }


        .fine-due {
            color: #dc2626;

            font-weight: 800;
        }


        /* ==========================================
           STATUS
        ========================================== */

        .status-badge {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 10px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;
        }


        .status-returned {
            background: #ecfdf3;

            border: 1px solid #bbf7d0;

            color: #15803d;
        }


        .status-issued {
            background: #eff6ff;

            border: 1px solid #bfdbfe;

            color: #2563eb;
        }


        /* ==========================================
           EMPTY STATE
        ========================================== */

        .empty-history {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 18px;

            padding: 70px 30px;

            text-align: center;

            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, 0.05);
        }


        .empty-history-icon {
            width: 82px;
            height: 82px;

            margin: 0 auto 18px;

            border-radius: 22px;

            background: #f1f5f9;

            color: #94a3b8;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 38px;
        }


        .empty-history h3 {
            margin: 0 0 8px;

            color: #253044;

            font-size: 21px;

            font-weight: 800;
        }


        .empty-history p {
            margin: 0 auto 23px;

            max-width: 430px;

            color: #8994a4;

            font-size: 13px;

            line-height: 1.6;
        }


        .browse-history-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 11px 19px;

            border-radius: 9px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s;
        }


        .browse-history-btn:hover {
            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* ==========================================
           MOBILE NAV
        ========================================== */

        @media (max-width: 900px) {

            .user-nav-center {
                display: none !important;
            }

        }


        /* ==========================================
           FILTER RESPONSIVE
        ========================================== */

        @media (max-width: 1350px) {

            .history-filter-grid {
                grid-template-columns:
                    minmax(210px, 1.5fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr);
            }

            .history-search-btn,
            .history-reset-btn {
                width: 100%;
            }

        }


        @media (max-width: 1100px) {

            .history-filter-grid {
                grid-template-columns:
                    1fr
                    1fr
                    1fr;
            }

        }


        @media (max-width: 767px) {

            .history-page {
                padding: 22px 18px;
            }

            .history-header {
                align-items: flex-start;
            }

            .history-title h2 {
                font-size: 23px;
            }

            .history-summary {
                flex-direction: column;

                align-items: flex-start;
            }

            .history-filter-grid {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 576px) {

            .history-page {
                padding: 18px 12px;
            }

            .history-header {
                flex-direction: column;
            }

            .history-title-icon {
                width: 46px;
                height: 46px;

                font-size: 20px;
            }

            .history-title h2 {
                font-size: 21px;
            }

            .history-count {
                width: 100%;

                justify-content: center;
            }

            .history-summary {
                padding: 16px;
            }

            .history-table-header {
                padding: 17px;
            }

            .history-filter-grid {
                grid-template-columns: 1fr;
            }

            .empty-history {
                padding: 50px 20px;
            }

        }


        /* ==========================================
           SCROLLBAR
        ========================================== */

        .history-table-wrapper::-webkit-scrollbar {
            height: 7px;
        }


        .history-table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
        }


        .history-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;

            border-radius: 10px;
        }


        /* ==========================================
           PRINT
        ========================================== */

        @media print {

            .history-filter-card,
            .history-summary-right,
            .user-navbar,
            .user-sidebar {
                display: none !important;
            }

            .history-page {
                padding: 10px;
            }

            .history-table-card {
                box-shadow: none;
            }

            .history-table tbody tr {
                break-inside: avoid;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     USER SIDEBAR
========================================== -->

<?php include "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- ==========================================
         USER NAVBAR
    ========================================== -->

    <nav class="user-navbar">


        <div class="user-nav-left">


            <button
                class="sidebar-toggle"
                onclick="toggleSidebar()"
                type="button"
                aria-label="Toggle sidebar"
            >

                <i class="bi bi-list"></i>

            </button>


            <div class="user-welcome-icon">

                <i class="bi bi-clock-history"></i>

            </div>


            <div class="user-welcome">

                <span>
                    Welcome back user
                </span>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['user_name'] ?? 'User'
                    );

                    ?>

                </h5>

            </div>


        </div>


        <div class="user-nav-center">

            <div class="library-status">

                <span class="status-circle"></span>

                <span>
                    Library is Open
                </span>

            </div>

        </div>


        <div class="user-nav-right">


           


          


            <div class="nav-separator"></div>


            <div class="user-profile-pill">

                <div class="user-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION['user_name'] ?? 'U',
                            0,
                            1
                        )
                    );

                    ?>

                </div>


                <div class="user-profile-name">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? 'User'
                        );

                        ?>

                    </strong>

                    <small>
                        Member
                    </small>

                </div>

            </div>


            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="user-logout"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>


        </div>


    </nav>


    <!-- ==========================================
         PAGE CONTENT
    ========================================== -->

    <main class="history-page">


        <div class="history-container">


            <!-- ======================================
                 PAGE HEADER
            ======================================= -->

            <div class="history-header">


                <div class="history-title-area">


                  


                   


                </div>


                <div class="history-count">

                    <span class="history-count-number">

                        <?php
                        echo $totalHistory;
                        ?>

                    </span>

                    Total Records

                </div>


            </div>


            <!-- ======================================
                 SEARCH & FILTER
            ======================================= -->

            <div class="history-filter-card">


                <div class="history-filter-header">


                    <h3 class="history-filter-title">

                        <i class="bi bi-funnel-fill"></i>

                        Search & Filter History

                    </h3>


                    <span class="history-filter-result">

                        <?php
                        echo $totalHistory;
                        ?>

                        result<?php
                        echo $totalHistory != 1
                            ? 's'
                            : '';
                        ?>

                        found

                    </span>


                </div>


                <form method="GET" action="">


                    <div class="history-filter-grid">


                        <!-- Search -->

                        <div class="history-filter-field">

                            <label for="search">
                                Search
                            </label>

                            <div class="history-search-wrapper">

                                <i class="bi bi-search"></i>

                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="history-filter-input"
                                    placeholder="Book title or author..."
                                    value="<?php
                                    echo htmlspecialchars(
                                        $search
                                    );
                                    ?>"
                                >

                            </div>

                        </div>


                        <!-- Status -->

                        <div class="history-filter-field">

                            <label for="filter_status">
                                Status
                            </label>

                            <select
                                id="filter_status"
                                name="filter_status"
                                class="history-filter-select"
                            >

                                <option value="">
                                    All Status
                                </option>

                                <option
                                    value="Issued"
                                    <?php
                                    echo $filter_status === 'Issued'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Issued
                                </option>

                                <option
                                    value="Returned"
                                    <?php
                                    echo $filter_status === 'Returned'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Returned
                                </option>

                            </select>

                        </div>


                        <!-- Date From -->

                        <div class="history-filter-field">

                            <label for="date_from">
                                Issue Date From
                            </label>

                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                class="history-filter-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $date_from
                                );
                                ?>"
                            >

                        </div>


                        <!-- Date To -->

                        <div class="history-filter-field">

                            <label for="date_to">
                                Issue Date To
                            </label>

                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                class="history-filter-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $date_to
                                );
                                ?>"
                            >

                        </div>


                        <!-- Sort -->

                        <div class="history-filter-field">

                            <label for="sort">
                                Sort
                            </label>

                            <select
                                id="sort"
                                name="sort"
                                class="history-filter-select"
                            >

                                <option
                                    value="newest"
                                    <?php
                                    echo $sort === 'newest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Newest First
                                </option>

                                <option
                                    value="oldest"
                                    <?php
                                    echo $sort === 'oldest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Oldest First
                                </option>

                            </select>

                        </div>


                        <!-- Search Button -->

                        <div class="history-filter-field">

                            <label>
                                &nbsp;
                            </label>

                            <button
                                type="submit"
                                class="history-search-btn"
                            >

                                <i class="bi bi-search"></i>

                                Search

                            </button>

                        </div>


                        <!-- Reset Button -->

                        <div class="history-filter-field">

                            <label>
                                &nbsp;
                            </label>

                            <a
                                href="<?php
                                echo BASE_URL;
                                ?>/user/my_books/history.php"
                                class="history-reset-btn"
                            >

                                <i
                                    class="bi bi-arrow-counterclockwise"
                                ></i>

                                Reset

                            </a>

                        </div>


                    </div>


                </form>


                <!-- ==================================
                     ACTIVE FILTERS
                =================================== -->

                <?php if ($hasActiveFilters): ?>


                    <div class="history-active-filters">


                        <span class="history-filter-label">
                            Active Filters:
                        </span>


                        <?php if ($search !== ''): ?>

                            <span class="history-filter-tag">

                                <i class="bi bi-search"></i>

                                Search:
                                <?php
                                echo htmlspecialchars(
                                    $search
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($filter_status !== ''): ?>

                            <span class="history-filter-tag">

                                <i class="bi bi-circle-fill"></i>

                                Status:
                                <?php
                                echo htmlspecialchars(
                                    $filter_status
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($date_from !== ''): ?>

                            <span class="history-filter-tag">

                                <i class="bi bi-calendar"></i>

                                From:
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($date_from)
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($date_to !== ''): ?>

                            <span class="history-filter-tag">

                                <i class="bi bi-calendar"></i>

                                To:
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($date_to)
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($sort === 'oldest'): ?>

                            <span class="history-filter-tag">

                                <i class="bi bi-sort-down"></i>

                                Oldest First

                            </span>

                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


            <?php if ($totalHistory > 0): ?>


                <!-- ==================================
                     SUMMARY
                =================================== -->

                <div class="history-summary">


                    <div class="history-summary-left">


                        <div class="history-summary-icon">

                            <i class="bi bi-journal-text"></i>

                        </div>


                        <div class="history-summary-text">

                            <strong>
                                Your Borrowing Activity
                            </strong>

                            <span>
                                A complete record of your issued and returned books
                            </span>

                        </div>


                    </div>


                    <div class="history-summary-right">

                        <i class="bi bi-info-circle"></i>

                        Keep your library activity organized.

                    </div>


                </div>


                <!-- ==================================
                     HISTORY TABLE
                =================================== -->

                <div class="history-table-card">


                    <div class="history-table-header">


                        <div class="history-table-heading">


                            <i class="bi bi-list-ul"></i>


                            <div>

                                <h5>
                                    Borrowing Records
                                </h5>

                                <span>

                                    <?php
                                    echo $totalHistory;
                                    ?>

                                    record(s) found

                                </span>

                            </div>


                        </div>


                    </div>


                    <div class="history-table-wrapper">


                        <table class="history-table">


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
                                        Issue Date
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Return Date
                                    </th>

                                    <th>
                                        Fine
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php

                                $count = 1;

                                ?>


                                <?php while (
                                    $book =
                                    $result->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <!-- NUMBER -->

                                        <td>

                                            <div
                                                class="history-number"
                                            >

                                                <?php
                                                echo $count++;
                                                ?>

                                            </div>

                                        </td>


                                        <!-- BOOK -->

                                        <td>


                                            <div
                                                class="history-book"
                                            >


                                                <div
                                                    class="
                                                        history-book-icon
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-book-half
                                                        "
                                                    ></i>

                                                </div>


                                                <div
                                                    class="
                                                        history-book-info
                                                    "
                                                >

                                                    <strong>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $book['title']
                                                        );

                                                        ?>

                                                    </strong>


                                                    <span>
                                                        Book Record
                                                    </span>

                                                </div>


                                            </div>


                                        </td>


                                        <!-- AUTHOR -->

                                        <td
                                            class="author-cell"
                                        >

                                            <i
                                                class="bi bi-person"
                                            ></i>

                                            <?php

                                            echo htmlspecialchars(
                                                $book['author']
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

                                                <i
                                                    class="bi bi-tag-fill"
                                                ></i>

                                                <?php

                                                echo htmlspecialchars(
                                                    $book['category_name']
                                                );

                                                ?>

                                            </span>


                                        </td>


                                        <!-- ISSUE DATE -->

                                        <td
                                            class="date-cell"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-calendar-plus
                                                "
                                            ></i>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book['issue_date']
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- DUE DATE -->

                                        <td
                                            class="date-cell"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-calendar-check
                                                "
                                            ></i>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book['return_date']
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- ACTUAL RETURN -->

                                        <td>


                                            <?php if (
                                                !empty(
                                                    $book[
                                                        'actual_return_date'
                                                    ]
                                                )
                                            ): ?>


                                                <span
                                                    class="
                                                        returned-date
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-check-circle-fill
                                                        "
                                                    ></i>

                                                    <?php

                                                    echo date(
                                                        "d M Y",
                                                        strtotime(
                                                            $book[
                                                                'actual_return_date'
                                                            ]
                                                        )
                                                    );

                                                    ?>

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="not-returned"
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-clock
                                                        "
                                                    ></i>

                                                    Not Returned

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <!-- FINE -->

                                        <td>


                                            <?php if (
                                                $book['fine'] > 0
                                            ): ?>


                                                <span
                                                    class="fine-due"
                                                >

                                                    ₹<?php

                                                    echo number_format(
                                                        $book['fine'],
                                                        2
                                                    );

                                                    ?>

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="fine-paid"
                                                >

                                                    ₹0.00

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <?php if (
                                                $book['status']
                                                === 'Returned'
                                            ): ?>


                                                <span
                                                    class="
                                                        status-badge
                                                        status-returned
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-check-circle-fill
                                                        "
                                                    ></i>

                                                    Returned

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="
                                                        status-badge
                                                        status-issued
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-bookmark-fill
                                                        "
                                                    ></i>

                                                    Issued

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            <?php else: ?>


                <!-- ==================================
                     EMPTY HISTORY
                =================================== -->

                <div class="empty-history">


                    <div class="empty-history-icon">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <h3>

                        <?php if ($hasActiveFilters): ?>

                            No Matching History

                        <?php else: ?>

                            No Book History

                        <?php endif; ?>

                    </h3>


                    <p>

                        <?php if ($hasActiveFilters): ?>

                            No borrowing records match your
                            current search or filters.
                            Try changing your search criteria.

                        <?php else: ?>

                            You haven't borrowed any books yet.
                            Once you issue a book, your complete
                            borrowing activity will appear here.

                        <?php endif; ?>

                    </p>


                    <a
                        href="<?php echo BASE_URL; ?>/user/books/index.php"
                        class="browse-history-btn"
                    >

                        <i class="bi bi-book"></i>

                        Browse Books

                    </a>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector('.user-sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }

}

</script>


</body>

</html>
