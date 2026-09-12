<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();


// =====================================================
// STATUS MESSAGES
// =====================================================

$return_status = $_GET['return'] ?? '';
$payment_status = $_GET['payment'] ?? '';

$return_message = '';
$return_type = '';

$payment_message = '';
$payment_type = '';


// =====================================================
// RETURN MESSAGES
// =====================================================

if ($return_status === 'success') {

    $return_message =
        "Book returned successfully. You can now rate and review this book.";

    $return_type = "success";

}

if ($return_status === 'invalid') {

    $return_message =
        "This book cannot be returned.";

    $return_type = "danger";

}

if ($return_status === 'error') {

    $return_message =
        "Something went wrong while returning the book.";

    $return_type = "danger";

}

if ($return_status === 'payment_required') {

    $return_message =
        "Please pay the fine before returning this book.";

    $return_type = "warning";

}


// =====================================================
// PAYMENT MESSAGES
// =====================================================

if ($payment_status === 'success') {

    $payment_message =
        "Fine paid successfully. You can now return the book.";

    $payment_type = "success";

}

if ($payment_status === 'error') {

    $payment_message =
        "Fine payment failed. Please try again.";

    $payment_type = "danger";

}


// =====================================================
// GET USER
// =====================================================

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user_login.php"
    );

    exit();

}


// =====================================================
// SEARCH + FILTER VALUES
// =====================================================

$search = trim($_GET['search'] ?? '');

$filter_status = $_GET['filter_status'] ?? '';

$date_from = $_GET['date_from'] ?? '';

$date_to = $_GET['date_to'] ?? '';

$sort = $_GET['sort'] ?? 'newest';


// =====================================================
// VALIDATE STATUS
// =====================================================

$allowed_statuses = [
    'Issued',
    'Returned'
];

if (
    !in_array(
        $filter_status,
        $allowed_statuses,
        true
    )
) {

    $filter_status = '';

}


// =====================================================
// VALIDATE SORT
// =====================================================

$allowed_sorts = [
    'newest',
    'oldest'
];

if (
    !in_array(
        $sort,
        $allowed_sorts,
        true
    )
) {

    $sort = 'newest';

}


// =====================================================
// VALIDATE DATE FROM
// =====================================================

if (
    $date_from !== '' &&
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date_from
    )
) {

    $date_from = '';

}


// =====================================================
// VALIDATE DATE TO
// =====================================================

if (
    $date_to !== '' &&
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $date_to
    )
) {

    $date_to = '';

}


// =====================================================
// IF DATE RANGE IS REVERSED
// SWAP THE DATES
// =====================================================

if (
    $date_from !== '' &&
    $date_to !== '' &&
    $date_from > $date_to
) {

    $temporary_date = $date_from;

    $date_from = $date_to;

    $date_to = $temporary_date;

}


// =====================================================
// SORT ORDER
// =====================================================

if ($sort === 'oldest') {

    $orderBy =
        "issued_books.issue_date ASC,
         issued_books.id ASC";

} else {

    $orderBy =
        "issued_books.issue_date DESC,
         issued_books.id DESC";

}


// =====================================================
// GET USER BOOKS
// SEARCH + FILTER
// LOAD BOTH ISSUED AND RETURNED BOOKS
// =====================================================

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


// =====================================================
// PARAMETER ARRAYS
// =====================================================

$params = [
    $user_id
];

$types = "i";


// =====================================================
// SEARCH
// BOOK TITLE / AUTHOR
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $types .= "ss";

}


// =====================================================
// STATUS FILTER
// =====================================================

if ($filter_status !== '') {

    $sql .= "
        AND issued_books.status = ?
    ";

    $params[] =
        $filter_status;

    $types .= "s";

}


// =====================================================
// ISSUE DATE FROM
// =====================================================

if ($date_from !== '') {

    $sql .= "
        AND issued_books.issue_date >= ?
    ";

    $params[] =
        $date_from;

    $types .= "s";

}


// =====================================================
// ISSUE DATE TO
// =====================================================

if ($date_to !== '') {

    $sql .= "
        AND issued_books.issue_date <= ?
    ";

    $params[] =
        $date_to;

    $types .= "s";

}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY
    $orderBy
";


// =====================================================
// PREPARED STATEMENT
// =====================================================

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    $types,
    ...$params
);


$stmt->execute();

$result =
    $stmt->get_result();


// =====================================================
// FILTERED BOOK COUNT
// =====================================================

$filteredBooks =
    $result->num_rows;


// =====================================================
// TOTAL CURRENTLY ISSUED BOOKS
// =====================================================

$totalIssuedBooks = 0;

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?
       AND status = 'Issued'"
);


if ($countStmt) {

    $countStmt->bind_param(
        "i",
        $user_id
    );

    $countStmt->execute();

    $countResult =
        $countStmt->get_result();

    $countData =
        $countResult->fetch_assoc();

    $totalIssuedBooks =
        (int)(
            $countData['total']
            ?? 0
        );

    $countStmt->close();

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
        My Books - Library Management System
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

        * {
            box-sizing: border-box;
        }


        /* ==========================================
           PAGE
        ========================================== */

        .my-books-page {

            padding: 35px;

            width: 100%;
        }


        .my-books-container {

            width: 100%;

            max-width: 1250px;

            margin: 0 auto;
        }


        /* ==========================================
           TOP ALERT
        ========================================== */

        .page-alert {

            margin-bottom: 20px;

            border-radius: 12px;

            font-size: 12px;

            font-weight: 600;

            box-shadow:
                0 5px 18px
                rgba(15,23,42,.05);
        }


        /* ==========================================
           PAGE HEADER
        ========================================== */

        .my-books-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }


        .my-books-title-area {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .my-books-title-icon {

            width: 54px;

            height: 54px;

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
                rgba(37,99,235,.20);
        }


        .my-books-title h2 {

            margin: 0;

            color: #172033;

            font-size: 27px;

            font-weight: 800;
        }


        .my-books-title p {

            margin: 5px 0 0;

            color: #7b8798;

            font-size: 13px;
        }


        /* ==========================================
           COUNT
        ========================================== */

        .issued-count {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 11px 16px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            border-radius: 12px;

            color: #2563eb;

            font-size: 13px;

            font-weight: 700;
        }


        .issued-count-number {

            width: 27px;

            height: 27px;

            border-radius: 8px;

            background: #2563eb;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;
        }


        /* ==========================================
           SEARCH + FILTER
        ========================================== */

        .my-books-filter-card {

            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 16px;

            padding: 20px 22px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 20px
                rgba(15,23,42,.04);
        }


        .my-books-filter-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 16px;
        }


        .my-books-filter-title {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .my-books-filter-title > i {

            width: 36px;

            height: 36px;

            border-radius: 9px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;
        }


        .my-books-filter-title h5 {

            margin: 0;

            color: #172033;

            font-size: 15px;

            font-weight: 800;
        }


        .my-books-filter-title span {

            display: block;

            margin-top: 2px;

            color: #8994a4;

            font-size: 11px;
        }


        .my-books-filter-count {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 11px;

            background: #eff6ff;

            color: #2563eb;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }


        /* ==========================================
           FILTER GRID
        ========================================== */

        .my-books-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                150px
                165px
                165px
                145px
                auto
                auto;

            gap: 10px;

            align-items: end;
        }


        .my-books-filter-field label {

            display: block;

            margin-bottom: 7px;

            color: #586474;

            font-size: 11px;

            font-weight: 700;
        }


        .my-books-search-wrapper {

            position: relative;
        }


        .my-books-search-wrapper i {

            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #98a1ad;

            font-size: 14px;
        }


        .my-books-filter-input,
        .my-books-filter-select {

            width: 100%;

            height: 43px;

            border: 1px solid #dfe4ea;

            border-radius: 9px;

            background: #ffffff;

            color: #303a49;

            font-size: 12px;

            outline: none;

            padding: 0 12px;

            transition: all .2s ease;
        }


        .my-books-filter-input {

            padding-left: 37px;
        }


        .my-books-filter-input:focus,
        .my-books-filter-select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }


        /* ==========================================
           SEARCH BUTTON
        ========================================== */

        .my-books-search-btn,
        .my-books-reset-btn {

            height: 43px;

            padding: 0 15px;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 12px;

            font-weight: 800;

            text-decoration: none;

            transition: all .2s ease;

            white-space: nowrap;
        }


        .my-books-search-btn {

            border: 1px solid #2563eb;

            background: #2563eb;

            color: #ffffff;
        }


        .my-books-search-btn:hover {

            background: #1d4ed8;

            border-color: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .my-books-reset-btn {

            border: 1px solid #dfe4ea;

            background: #ffffff;

            color: #667180;
        }


        .my-books-reset-btn:hover {

            background: #f7f8fa;

            color: #3f4855;

            border-color: #cfd6de;
        }


        /* ==========================================
           ACTIVE FILTERS
        ========================================== */

        .my-books-active-filters {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 15px;

            padding-top: 14px;

            border-top: 1px solid #eef1f4;
        }


        .my-books-filter-label {

            color: #8994a4;

            font-size: 11px;

            font-weight: 800;
        }


        .my-books-filter-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            background: #eff6ff;

            color: #2563eb;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            padding: 5px 10px;

            font-size: 10px;

            font-weight: 800;
        }


        .my-books-filter-tag i {

            font-size: 10px;
        }


        /* ==========================================
           SUMMARY
        ========================================== */

        .summary-card {

            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 16px;

            padding: 20px 22px;

            margin-bottom: 25px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            box-shadow:
                0 5px 20px
                rgba(15,23,42,.04);
        }


        .summary-left {

            display: flex;

            align-items: center;

            gap: 13px;
        }


        .summary-icon {

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


        .summary-text strong {

            display: block;

            color: #253044;

            font-size: 14px;
        }


        .summary-text span {

            color: #8994a4;

            font-size: 12px;
        }


        .summary-tip {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #64748b;

            font-size: 12px;
        }


        .summary-tip i {

            color: #2563eb;
        }


        /* ==========================================
           BOOK GRID
        ========================================== */

        .issued-books-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 22px;
        }


        /* ==========================================
           BOOK CARD
        ========================================== */

        .issued-book-card {

            background: #ffffff;

            border: 1px solid #e4e9ef;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 7px 25px
                rgba(15,23,42,.055);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .issued-book-card:hover {

            transform: translateY(-4px);

            border-color: #d4deeb;

            box-shadow:
                0 15px 35px
                rgba(15,23,42,.09);
        }


        /* ==========================================
           CARD TOP
        ========================================== */

        .issued-book-top {

            padding: 22px;

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid #eef1f5;
        }


        .issued-book-heading {

            display: flex;

            align-items: center;

            gap: 14px;

            min-width: 0;
        }


        .book-icon {

            width: 58px;

            height: 58px;

            min-width: 58px;

            border-radius: 14px;

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

            font-size: 26px;
        }


        .issued-book-title {

            color: #1f2937;

            font-size: 17px;

            font-weight: 800;

            line-height: 1.35;

            margin: 0 0 5px;

            word-break: break-word;
        }


        .issued-book-author {

            color: #7b8794;

            font-size: 12px;

            margin: 0;
        }


        /* ==========================================
           STATUS
        ========================================== */

        .book-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            white-space: nowrap;

            padding: 7px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;
        }


        .book-status.issued {

            background: #ecfdf3;

            color: #15803d;

            border: 1px solid #bbf7d0;
        }


        .book-status.overdue {

            background: #fef2f2;

            color: #dc2626;

            border: 1px solid #fecaca;
        }


        .book-status.returned {

            background: #eff6ff;

            color: #2563eb;

            border: 1px solid #bfdbfe;
        }


        /* ==========================================
           BOOK BODY
        ========================================== */

        .issued-book-body {

            padding: 21px 22px 22px;
        }


        /* ==========================================
           CATEGORY
        ========================================== */

        .category-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 17px;

            padding-bottom: 16px;

            border-bottom:
                1px dashed #e5e9ef;
        }


        .category-label {

            color: #8792a2;

            font-size: 12px;

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .category-label i {

            color: #2563eb;
        }


        .category-value {

            padding: 6px 10px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 7px;

            color: #475467;

            font-size: 11px;

            font-weight: 700;
        }


        /* ==========================================
           DATE INFORMATION
        ========================================== */

        .book-info-list {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 18px;
        }


        .book-info-box {

            background: #f8fafc;

            border: 1px solid #edf0f4;

            border-radius: 11px;

            padding: 13px;
        }


        .book-info-box-label {

            color: #8994a4;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .4px;

            margin-bottom: 5px;
        }


        .book-info-box-value {

            color: #344054;

            font-size: 13px;

            font-weight: 800;
        }


        .book-info-box-value.due {

            color: #2563eb;
        }


        .book-info-box-value.late {

            color: #dc2626;
        }


        .book-info-box-value.returned-value {

            color: #16a34a;
        }


        /* ==========================================
           FINE
        ========================================== */

        .fine-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 13px 14px;

            border-radius: 10px;

            margin-bottom: 15px;

            background: #fafafa;

            border: 1px solid #edf0f4;
        }


        .fine-label {

            color: #697586;

            font-size: 12px;

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .fine-label i {

            color: #64748b;
        }


        .fine-value {

            font-size: 14px;

            font-weight: 800;
        }


        .fine-value.no-fine {

            color: #16a34a;
        }


        .fine-value.has-fine {

            color: #dc2626;
        }


        /* ==========================================
           DUE DATE REMINDER
        ========================================== */

        .due-notification {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 12px 13px;

            border-radius: 10px;

            margin-bottom: 15px;

            border: 1px solid;
        }


        .due-notification-icon {

            width: 38px;

            height: 38px;

            flex-shrink: 0;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;
        }


        .due-notification-content {

            flex: 1;
        }


        .due-notification-content strong {

            display: block;

            font-size: 11px;

            font-weight: 800;

            margin-bottom: 2px;
        }


        .due-notification-content span {

            display: block;

            font-size: 10px;

            line-height: 1.5;
        }


        /* ==========================================
           NORMAL REMINDER
        ========================================== */

        .due-notification.normal {

            background: #eff6ff;

            border-color: #dbeafe;
        }


        .due-notification.normal
        .due-notification-icon {

            background: #dbeafe;

            color: #2563eb;
        }


        .due-notification.normal
        .due-notification-content strong {

            color: #1d4ed8;
        }


        .due-notification.normal
        .due-notification-content span {

            color: #475569;
        }


        /* ==========================================
           DUE SOON
        ========================================== */

        .due-notification.urgent {

            background: #fff7ed;

            border-color: #fed7aa;
        }


        .due-notification.urgent
        .due-notification-icon {

            background: #ffedd5;

            color: #ea580c;
        }


        .due-notification.urgent
        .due-notification-content strong {

            color: #c2410c;
        }


        .due-notification.urgent
        .due-notification-content span {

            color: #7c2d12;
        }


        /* ==========================================
           DUE TODAY
        ========================================== */

        .due-notification.today {

            background: #fff7ed;

            border-color: #fdba74;
        }


        .due-notification.today
        .due-notification-icon {

            background: #ffedd5;

            color: #ea580c;
        }


        .due-notification.today
        .due-notification-content strong {

            color: #c2410c;
        }


        .due-notification.today
        .due-notification-content span {

            color: #7c2d12;
        }


        /* ==========================================
           OVERDUE
        ========================================== */

        .due-notification.overdue {

            background: #fef2f2;

            border-color: #fecaca;
        }


        .due-notification.overdue
        .due-notification-icon {

            background: #fee2e2;

            color: #dc2626;
        }


        .due-notification.overdue
        .due-notification-content strong {

            color: #b91c1c;
        }


        .due-notification.overdue
        .due-notification-content span {

            color: #7f1d1d;
        }


        /* ==========================================
           RETURNED
        ========================================== */

        .due-notification.returned {

            background: #f0fdf4;

            border-color: #bbf7d0;
        }


        .due-notification.returned
        .due-notification-icon {

            background: #dcfce7;

            color: #16a34a;
        }


        .due-notification.returned
        .due-notification-content strong {

            color: #15803d;
        }


        .due-notification.returned
        .due-notification-content span {

            color: #166534;
        }


        /* ==========================================
           FINE PAYMENT BOX
        ========================================== */

        .fine-payment-box {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 14px;

            margin-bottom: 15px;

            background: #fff7ed;

            border: 1px solid #fed7aa;

            border-radius: 12px;
        }


        .fine-payment-icon {

            width: 40px;

            height: 40px;

            flex-shrink: 0;

            border-radius: 10px;

            background: #ffedd5;

            color: #ea580c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .fine-payment-content {

            flex: 1;
        }


        .fine-payment-content strong {

            display: block;

            color: #9a3412;

            font-size: 12px;

            margin-bottom: 3px;
        }


        .fine-payment-content span {

            display: block;

            color: #7c2d12;

            font-size: 10px;

            line-height: 1.5;
        }


        .pay-fine-btn {

            flex-shrink: 0;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 9px 14px;

            border-radius: 9px;

            background: #ea580c;

            color: #ffffff;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            transition: .2s;
        }


        .pay-fine-btn:hover {

            background: #c2410c;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* ==========================================
           RETURN BUTTON
        ========================================== */

        .return-book-action {

            margin-bottom: 15px;
        }


        .return-book-btn {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 15px;

            border-radius: 10px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;
        }


        .return-book-btn:hover {

            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* ==========================================
           REVIEW BUTTON
        ========================================== */

        .review-book-action {

            margin-bottom: 15px;
        }


        .review-book-btn {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 15px;

            border-radius: 10px;

            background: #fff7ed;

            color: #ea580c;

            border: 1px solid #fed7aa;

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;

            transition: .2s;
        }


        .review-book-btn:hover {

            background: #ffedd5;

            color: #c2410c;

            border-color: #fdba74;

            transform: translateY(-1px);
        }


        /* ==========================================
           RETURN ALERT
        ========================================== */

        .return-alert {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 12px 13px;

            border-radius: 10px;

            margin: 0;

            font-size: 11px;

            line-height: 1.5;
        }


        .return-alert i {

            margin-top: 1px;
        }


        .return-alert.success {

            color: #166534;

            background: #f0fdf4;

            border: 1px solid #bbf7d0;
        }


        .return-alert.danger {

            color: #991b1b;

            background: #fef2f2;

            border: 1px solid #fecaca;
        }


        /* ==========================================
           EMPTY STATE
        ========================================== */

        .empty-books {

            background: #ffffff;

            border: 1px solid #e4e9ef;

            border-radius: 18px;

            padding: 70px 30px;

            text-align: center;

            box-shadow:
                0 8px 25px
                rgba(15,23,42,.05);
        }


        .empty-icon {

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


        .empty-books h3 {

            margin: 0 0 8px;

            color: #253044;

            font-size: 21px;

            font-weight: 800;
        }


        .empty-books p {

            margin: 0 auto 22px;

            max-width: 430px;

            color: #8994a4;

            font-size: 13px;

            line-height: 1.6;
        }


        .browse-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 9px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: .2s;
        }


        .browse-btn:hover {

            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* ==========================================
           NAVBAR
        ========================================== */

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

            box-shadow:
                0 3px 15px
                rgba(15,23,42,.035);
        }


        .user-nav-left {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .sidebar-toggle {

            width: 38px;

            height: 38px;

            border: none;

            border-radius: 10px;

            background: #f8fafc;

            color: #64748b;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            cursor: pointer;

            transition: .2s;
        }


        .sidebar-toggle:hover {

            background: #eff6ff;

            color: #2563eb;
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

            box-shadow:
                0 0 0 4px
                rgba(34,197,94,.10);
        }


        .user-nav-right {

            display: flex;

            align-items: center;

            gap: 10px;
        }


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


        .nav-separator {

            width: 1px;

            height: 34px;

            background: #e5e7eb;

            margin: 0 5px;
        }


        .user-profile-pill {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 5px 10px 5px 5px;

            background: #f8fafc;

            border: 1px solid #e8edf3;

            border-radius: 30px;
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


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1250px) {

            .my-books-filter-grid {

                grid-template-columns:
                    minmax(0, 1fr)
                    150px
                    150px
                    150px;

            }


            .my-books-search-btn,
            .my-books-reset-btn {

                width: 100%;
            }

        }


        @media (max-width: 1100px) {

            .my-books-page {

                padding: 28px;
            }

            .issued-books-grid {

                gap: 18px;
            }

        }


        @media (max-width: 1000px) {

            .my-books-filter-grid {

                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media (max-width: 900px) {

            .user-nav-center {

                display: none !important;
            }

            .issued-books-grid {

                grid-template-columns: 1fr;
            }

            .user-navbar {

                padding: 0 20px;
            }

        }


        @media (max-width: 767px) {

            .my-books-page {

                padding: 22px 18px;
            }

            .my-books-header {

                align-items: flex-start;
            }

            .my-books-title h2 {

                font-size: 23px;
            }

            .issued-count {

                padding: 9px 12px;
            }

            .summary-card {

                flex-direction: column;

                align-items: flex-start;
            }

        }


        @media (max-width: 650px) {

            .user-navbar {

                height: 70px;

                padding: 0 14px;
            }

            .sidebar-toggle {

                display: flex;
            }

            .user-welcome-icon {

                width: 39px;

                height: 39px;
            }

            .user-welcome span {

                font-size: 9px;
            }

            .user-welcome h5 {

                font-size: 13px;
            }

            .nav-action {

                width: 37px;

                height: 37px;

                font-size: 15px;
            }

            .user-profile-name {

                display: none;
            }

            .user-profile-pill {

                padding: 3px;

                border-radius: 50%;
            }

            .user-avatar {

                width: 34px;

                height: 34px;
            }

            .user-logout {

                width: 37px;

                height: 37px;
            }

        }


        @media (max-width: 576px) {

            .my-books-page {

                padding: 18px 12px;
            }

            .my-books-header {

                flex-direction: column;
            }

            .my-books-title-icon {

                width: 46px;

                height: 46px;

                font-size: 20px;
            }

            .my-books-title h2 {

                font-size: 21px;
            }

            .issued-count {

                width: 100%;

                justify-content: center;
            }

            .my-books-filter-card {

                padding: 16px;
            }

            .my-books-filter-header {

                align-items: flex-start;

                flex-direction: column;
            }

            .my-books-filter-grid {

                grid-template-columns: 1fr;
            }

            .summary-card {

                padding: 16px;
            }

            .issued-book-top {

                padding: 17px;
            }

            .issued-book-body {

                padding: 17px;
            }

            .book-status {

                font-size: 10px;

                padding: 6px 8px;
            }

            .book-icon {

                width: 50px;

                height: 50px;

                min-width: 50px;

                font-size: 22px;
            }

            .issued-book-title {

                font-size: 15px;
            }

            .book-info-list {

                grid-template-columns: 1fr;
            }

            .empty-books {

                padding: 50px 20px;
            }

        }


        @media (max-width: 450px) {

            .issued-book-top {

                flex-direction: column;
            }

            .book-status {

                align-self: flex-start;
            }

            .category-row {

                align-items: flex-start;

                flex-direction: column;
            }

            .fine-payment-box {

                align-items: flex-start;

                flex-wrap: wrap;
            }

            .pay-fine-btn {

                width: 100%;
            }

            .user-nav-right {

                gap: 6px;
            }

            .user-nav-left {

                gap: 8px;
            }

            .nav-action:nth-child(2) {

                display: none;
            }

        }


        /* ==========================================
           PRINT
        ========================================== */

        @media print {

            .user-navbar,
            .user-sidebar,
            .sidebar-toggle,
            .nav-action,
            .user-logout,
            .review-book-btn,
            .return-book-btn,
            .pay-fine-btn,
            .my-books-filter-card {

                display: none !important;
            }

            .my-books-page {

                padding: 0;
            }

            .issued-books-grid {

                grid-template-columns: 1fr 1fr;
            }

            .issued-book-card {

                box-shadow: none;
            }

        }

    </style>

</head>


<body>


<!-- =================================================
     USER SIDEBAR
================================================== -->

<?php include "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =================================================
         USER NAVBAR
    ================================================== -->

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

                <i class="bi bi-journal-bookmark"></i>

            </div>


            <div class="user-welcome">

                <span>
                    Welcome back user
                </span>


                <h5>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['user_name']
                        ?? 'User'
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


            <a
                href="<?php echo BASE_URL; ?>/user/books/search.php"
                class="nav-action"
                title="Search Books"
            >

                <i class="bi bi-search"></i>

            </a>


            <a
                href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                class="nav-action"
                title="My Books"
            >

                <i class="bi bi-journal-bookmark"></i>

            </a>


            <div class="nav-separator"></div>


            <div class="user-profile-pill">


                <div class="user-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION['user_name']
                            ?? 'U',
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
                            $_SESSION['user_name']
                            ?? 'User'
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

                <i
                    class="bi bi-box-arrow-right"
                ></i>

            </a>


        </div>


    </nav>



    <!-- =================================================
         PAGE CONTENT
    ================================================== -->

    <main class="my-books-page">


        <div class="my-books-container">


            <!-- =================================================
                 RETURN MESSAGE
            ================================================== -->

            <?php if (
                !empty($return_message)
            ) { ?>


                <div
                    class="
                        page-alert
                        alert
                        alert-<?php echo $return_type; ?>
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >


                    <?php if (
                        $return_type === 'success'
                    ) { ?>


                        <i
                            class="
                                bi
                                bi-check-circle-fill
                                me-2
                            "
                        ></i>


                    <?php } else { ?>


                        <i
                            class="
                                bi
                                bi-exclamation-triangle-fill
                                me-2
                            "
                        ></i>


                    <?php } ?>


                    <?php

                    echo htmlspecialchars(
                        $return_message
                    );

                    ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>


                </div>


            <?php } ?>



            <!-- =================================================
                 PAYMENT MESSAGE
            ================================================== -->

            <?php if (
                !empty($payment_message)
            ) { ?>


                <div
                    class="
                        page-alert
                        alert
                        alert-<?php echo $payment_type; ?>
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >


                    <?php if (
                        $payment_type === 'success'
                    ) { ?>


                        <i
                            class="
                                bi
                                bi-check-circle-fill
                                me-2
                            "
                        ></i>


                    <?php } else { ?>


                        <i
                            class="
                                bi
                                bi-exclamation-triangle-fill
                                me-2
                            "
                        ></i>


                    <?php } ?>


                    <?php

                    echo htmlspecialchars(
                        $payment_message
                    );

                    ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>


                </div>


            <?php } ?>



            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="my-books-header">


                <div class="my-books-title-area">


                    <div class="my-books-title-icon">

                        <i
                            class="
                                bi
                                bi-journal-bookmark-fill
                            "
                        ></i>

                    </div>


                    <div class="my-books-title">

                        <h2>
                            My Books
                        </h2>


                        <p>
                            Manage and track your currently issued and returned books
                        </p>

                    </div>


                </div>


                <div class="issued-count">


                    <span
                        class="issued-count-number"
                    >

                        <?php

                        echo $totalIssuedBooks;

                        ?>

                    </span>


                    Currently Issued


                </div>


            </div>



            <!-- =================================================
                 SEARCH + FILTER CARD
            ================================================== -->

            <div class="my-books-filter-card">


                <div
                    class="
                        my-books-filter-header
                    "
                >


                    <div
                        class="
                            my-books-filter-title
                        "
                    >


                        <i
                            class="
                                bi
                                bi-funnel-fill
                            "
                        ></i>


                        <div>


                            <h5>
                                Search & Filter My Books
                            </h5>


                            <span>

                                Search by book or author and
                                filter your issue history.

                            </span>


                        </div>


                    </div>



                    <div
                        class="
                            my-books-filter-count
                        "
                    >


                        <i
                            class="
                                bi
                                bi-journal-bookmark
                            "
                        ></i>


                        <?php

                        echo $filteredBooks;

                        ?>


                        Records


                    </div>


                </div>



                <form
                    method="GET"
                    action=""
                >


                    <div
                        class="
                            my-books-filter-grid
                        "
                    >


                        <!-- =================================
                             SEARCH
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label
                                for="myBooksSearch"
                            >

                                Search

                            </label>


                            <div
                                class="
                                    my-books-search-wrapper
                                "
                            >


                                <i
                                    class="bi bi-search"
                                ></i>


                                <input
                                    type="text"
                                    id="myBooksSearch"
                                    name="search"
                                    class="
                                        my-books-filter-input
                                    "
                                    placeholder="Book title or author..."
                                    value="<?php
                                        echo htmlspecialchars(
                                            $search
                                        );
                                    ?>"
                                >


                            </div>


                        </div>



                        <!-- =================================
                             STATUS
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label
                                for="myBooksStatus"
                            >

                                Status

                            </label>


                            <select
                                id="myBooksStatus"
                                name="filter_status"
                                class="
                                    my-books-filter-select
                                "
                            >


                                <option
                                    value=""
                                    <?php

                                    echo
                                        $filter_status === ''
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    All Status

                                </option>


                                <option
                                    value="Issued"
                                    <?php

                                    echo
                                        $filter_status === 'Issued'
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
                                        $filter_status === 'Returned'
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    Returned

                                </option>


                            </select>


                        </div>



                        <!-- =================================
                             DATE FROM
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label
                                for="myBooksDateFrom"
                            >

                                Issue Date From

                            </label>


                            <input
                                type="date"
                                id="myBooksDateFrom"
                                name="date_from"
                                class="
                                    my-books-filter-input
                                "
                                value="<?php

                                    echo htmlspecialchars(
                                        $date_from
                                    );

                                ?>"
                            >


                        </div>



                        <!-- =================================
                             DATE TO
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label
                                for="myBooksDateTo"
                            >

                                Issue Date To

                            </label>


                            <input
                                type="date"
                                id="myBooksDateTo"
                                name="date_to"
                                class="
                                    my-books-filter-input
                                "
                                value="<?php

                                    echo htmlspecialchars(
                                        $date_to
                                    );

                                ?>"
                            >


                        </div>



                        <!-- =================================
                             SORT
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label
                                for="myBooksSort"
                            >

                                Sort By

                            </label>


                            <select
                                id="myBooksSort"
                                name="sort"
                                class="
                                    my-books-filter-select
                                "
                            >


                                <option
                                    value="newest"
                                    <?php

                                    echo
                                        $sort === 'newest'
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    Newest First

                                </option>


                                <option
                                    value="oldest"
                                    <?php

                                    echo
                                        $sort === 'oldest'
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    Oldest First

                                </option>


                            </select>


                        </div>



                        <!-- =================================
                             SEARCH BUTTON
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label>
                                &nbsp;
                            </label>


                            <button
                                type="submit"
                                class="
                                    my-books-search-btn
                                "
                            >


                                <i
                                    class="bi bi-search"
                                ></i>


                                Search


                            </button>


                        </div>



                        <!-- =================================
                             RESET BUTTON
                        ================================== -->

                        <div
                            class="
                                my-books-filter-field
                            "
                        >


                            <label>
                                &nbsp;
                            </label>


                            <a
                                href="index.php"
                                class="
                                    my-books-reset-btn
                                "
                            >


                                <i
                                    class="
                                        bi
                                        bi-arrow-counterclockwise
                                    "
                                ></i>


                                Reset


                            </a>


                        </div>


                    </div>



                    <!-- =========================================
                         ACTIVE FILTER TAGS
                    ========================================== -->

                    <?php if (
                        $search !== '' ||
                        $filter_status !== '' ||
                        $date_from !== '' ||
                        $date_to !== '' ||
                        $sort !== 'newest'
                    ): ?>


                        <div
                            class="
                                my-books-active-filters
                            "
                        >


                            <span
                                class="
                                    my-books-filter-label
                                "
                            >

                                Active Filters:

                            </span>



                            <?php if (
                                $search !== ''
                            ): ?>


                                <span
                                    class="
                                        my-books-filter-tag
                                    "
                                >


                                    <i
                                        class="bi bi-search"
                                    ></i>


                                    Search:

                                    <?php

                                    echo htmlspecialchars(
                                        $search
                                    );

                                    ?>


                                </span>


                            <?php endif; ?>



                            <?php if (
                                $filter_status !== ''
                            ): ?>


                                <span
                                    class="
                                        my-books-filter-tag
                                    "
                                >


                                    <i
                                        class="bi bi-funnel"
                                    ></i>


                                    Status:

                                    <?php

                                    echo htmlspecialchars(
                                        $filter_status
                                    );

                                    ?>


                                </span>


                            <?php endif; ?>



                            <?php if (
                                $date_from !== ''
                            ): ?>


                                <span
                                    class="
                                        my-books-filter-tag
                                    "
                                >


                                    <i
                                        class="
                                            bi
                                            bi-calendar-event
                                        "
                                    ></i>


                                    From:

                                    <?php

                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $date_from
                                        )
                                    );

                                    ?>


                                </span>


                            <?php endif; ?>



                            <?php if (
                                $date_to !== ''
                            ): ?>


                                <span
                                    class="
                                        my-books-filter-tag
                                    "
                                >


                                    <i
                                        class="
                                            bi
                                            bi-calendar-event
                                        "
                                    ></i>


                                    To:

                                    <?php

                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $date_to
                                        )
                                    );

                                    ?>


                                </span>


                            <?php endif; ?>



                            <?php if (
                                $sort === 'oldest'
                            ): ?>


                                <span
                                    class="
                                        my-books-filter-tag
                                    "
                                >


                                    <i
                                        class="
                                            bi
                                            bi-sort-down
                                        "
                                    ></i>


                                    Oldest First


                                </span>


                            <?php endif; ?>


                        </div>


                    <?php endif; ?>


                </form>


            </div>



            <!-- =================================================
                 BOOKS FOUND
            ================================================== -->

            <?php if (
                $result &&
                $result->num_rows > 0
            ): ?>


                <!-- =================================================
                     SUMMARY
                ================================================== -->

                <div class="summary-card">


                    <div class="summary-left">


                        <div class="summary-icon">

                            <i class="bi bi-book-half"></i>

                        </div>


                        <div class="summary-text">

                            <strong>
                                Your Library Books
                            </strong>


                            <span>

                                Showing
                                <?php echo $filteredBooks; ?>
                                of your book records.

                            </span>


                        </div>


                    </div>


                    <div class="summary-tip">

                        <i class="bi bi-info-circle"></i>

                        Return books before the due date to avoid fines.

                    </div>


                </div>



                <!-- =================================================
                     BOOK GRID
                ================================================== -->

                <div
                    class="issued-books-grid"
                >


                    <?php while (
                        $book =
                        $result->fetch_assoc()
                    ): ?>


                        <?php

                        /* =========================================
                           BOOK STATUS
                        ========================================= */

                        $book_status =
                            $book['status']
                            ?? 'Issued';


                        $is_returned =
                            (
                                $book_status
                                ===
                                'Returned'
                            );


                        /* =========================================
                           DATE CALCULATION
                        ========================================= */

                        $todayDate =
                            new DateTime(
                                date('Y-m-d')
                            );


                        $returnDate =
                            new DateTime(
                                date(
                                    'Y-m-d',
                                    strtotime(
                                        $book[
                                            'return_date'
                                        ]
                                    )
                                )
                            );


                        $dateDifference =
                            (int)$todayDate->diff(
                                $returnDate
                            )->format('%r%a');


                        /* =========================================
                           DEFAULT VALUES
                        ========================================= */

                        $overdue = false;

                        $days_overdue = 0;

                        $days_remaining = 0;

                        $current_fine =
                            (float)(
                                $book['fine']
                                ?? 0
                            );


                        /* =========================================
                           ACTIVE BOOK FINE
                        ========================================= */

                        if (!$is_returned) {

                            if (
                                $dateDifference < 0
                            ) {

                                $overdue = true;

                                $days_overdue =
                                    abs(
                                        $dateDifference
                                    );

                                $days_remaining = 0;

                                /*
                                 * ₹10 per overdue day
                                 */

                                $current_fine =
                                    $days_overdue * 10;

                            } else {

                                $overdue = false;

                                $days_remaining =
                                    $dateDifference;

                                $days_overdue = 0;

                                $current_fine = 0;

                            }

                        }


                        /* =========================================
                           DUE NOTIFICATION
                        ========================================= */

                        if ($is_returned) {

                            $due_notification_class =
                                "returned";

                            $due_notification_icon =
                                "bi-check-circle-fill";

                            $due_notification_title =
                                "Book Returned";

                            $due_notification_text =
                                "This book has been successfully returned. You can now rate and review it.";

                        } elseif ($overdue) {

                            $due_notification_class =
                                "overdue";

                            $due_notification_icon =
                                "bi-exclamation-triangle-fill";

                            $due_notification_title =
                                "Book Overdue";


                            if (
                                $days_overdue === 1
                            ) {

                                $due_notification_text =
                                    "This book is overdue by 1 day. Please pay the fine and return it.";

                            } else {

                                $due_notification_text =
                                    "This book is overdue by " .
                                    $days_overdue .
                                    " days. Please pay the fine and return it.";

                            }

                        } elseif (
                            $days_remaining === 0
                        ) {

                            $due_notification_class =
                                "today";

                            $due_notification_icon =
                                "bi-alarm-fill";

                            $due_notification_title =
                                "Due Today";

                            $due_notification_text =
                                "Please return this book today to avoid a late fine.";

                        } elseif (
                            $days_remaining <= 3
                        ) {

                            $due_notification_class =
                                "urgent";

                            $due_notification_icon =
                                "bi-bell-fill";

                            $due_notification_title =
                                "Due Soon";


                            if (
                                $days_remaining === 1
                            ) {

                                $due_notification_text =
                                    "This book is due tomorrow. Please return it on time.";

                            } else {

                                $due_notification_text =
                                    "This book is due in " .
                                    $days_remaining .
                                    " days. Please return it on time.";

                            }

                        } else {

                            $due_notification_class =
                                "normal";

                            $due_notification_icon =
                                "bi-calendar-check-fill";

                            $due_notification_title =
                                "Return Reminder";

                            $due_notification_text =
                                "Your book is due in " .
                                $days_remaining .
                                " days.";

                        }

                        ?>


                        <!-- =================================================
                             BOOK CARD
                        ================================================== -->

                        <article
                            class="issued-book-card"
                        >


                            <!-- CARD TOP -->

                            <div
                                class="issued-book-top"
                            >


                                <div
                                    class="
                                        issued-book-heading
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


                                    <div>


                                        <h3
                                            class="
                                                issued-book-title
                                            "
                                        >


                                            <?php

                                            echo htmlspecialchars(
                                                $book[
                                                    'title'
                                                ]
                                            );

                                            ?>


                                        </h3>


                                        <p
                                            class="
                                                issued-book-author
                                            "
                                        >


                                            <i
                                                class="
                                                    bi
                                                    bi-person
                                                    me-1
                                                "
                                            ></i>


                                            <?php

                                            echo htmlspecialchars(
                                                $book[
                                                    'author'
                                                ]
                                            );

                                            ?>


                                        </p>


                                    </div>


                                </div>



                                <!-- STATUS -->

                                <?php if (
                                    $is_returned
                                ): ?>


                                    <span
                                        class="
                                            book-status
                                            returned
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


                                <?php elseif (
                                    $overdue
                                ): ?>


                                    <span
                                        class="
                                            book-status
                                            overdue
                                        "
                                    >


                                        <i
                                            class="
                                                bi
                                                bi-exclamation-circle-fill
                                            "
                                        ></i>


                                        Overdue


                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            book-status
                                            issued
                                        "
                                    >


                                        <i
                                            class="
                                                bi
                                                bi-check-circle-fill
                                            "
                                        ></i>


                                        Issued


                                    </span>


                                <?php endif; ?>


                            </div>



                            <!-- CARD BODY -->

                            <div
                                class="
                                    issued-book-body
                                "
                            >


                                <!-- CATEGORY -->

                                <div
                                    class="category-row"
                                >


                                    <div
                                        class="
                                            category-label
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-tag-fill
                                            "
                                        ></i>

                                        Category

                                    </div>


                                    <div
                                        class="
                                            category-value
                                        "
                                    >


                                        <?php

                                        echo htmlspecialchars(
                                            $book[
                                                'category_name'
                                            ]
                                        );

                                        ?>


                                    </div>


                                </div>



                                <!-- DATE INFORMATION -->

                                <div
                                    class="
                                        book-info-list
                                    "
                                >


                                    <!-- ISSUE DATE -->

                                    <div
                                        class="book-info-box"
                                    >


                                        <div
                                            class="
                                                book-info-box-label
                                            "
                                        >

                                            Issue Date

                                        </div>


                                        <div
                                            class="
                                                book-info-box-value
                                            "
                                        >


                                            <i
                                                class="
                                                    bi
                                                    bi-calendar-plus
                                                    me-1
                                                "
                                            ></i>


                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book[
                                                        'issue_date'
                                                    ]
                                                )
                                            );

                                            ?>


                                        </div>


                                    </div>



                                    <!-- DUE DATE -->

                                    <div
                                        class="book-info-box"
                                    >


                                        <div
                                            class="
                                                book-info-box-label
                                            "
                                        >

                                            Due Date

                                        </div>


                                        <div
                                            class="
                                                book-info-box-value

                                                <?php

                                                if (
                                                    $is_returned
                                                ) {

                                                    echo
                                                        "returned-value";

                                                } elseif (
                                                    $overdue
                                                ) {

                                                    echo
                                                        "late";

                                                } elseif (
                                                    $days_remaining <= 3
                                                ) {

                                                    echo
                                                        "late";

                                                } else {

                                                    echo
                                                        "due";

                                                }

                                                ?>
                                            "
                                        >


                                            <i
                                                class="
                                                    bi
                                                    bi-calendar-check
                                                    me-1
                                                "
                                            ></i>


                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book[
                                                        'return_date'
                                                    ]
                                                )
                                            );

                                            ?>


                                        </div>


                                    </div>



                                    <!-- DAYS / RETURNED -->

                                    <div
                                        class="book-info-box"
                                    >


                                        <div
                                            class="
                                                book-info-box-label
                                            "
                                        >


                                            <?php

                                            echo
                                                $is_returned
                                                    ? "Actual Return"
                                                    : (
                                                        $overdue
                                                            ? "Days Overdue"
                                                            : "Days Remaining"
                                                    );

                                            ?>


                                        </div>


                                        <div
                                            class="
                                                book-info-box-value

                                                <?php

                                                if (
                                                    $is_returned
                                                ) {

                                                    echo
                                                        "returned-value";

                                                } elseif (
                                                    $overdue
                                                ) {

                                                    echo
                                                        "late";

                                                } elseif (
                                                    $days_remaining <= 3
                                                ) {

                                                    echo
                                                        "late";

                                                } else {

                                                    echo
                                                        "due";

                                                }

                                                ?>
                                            "
                                        >


                                            <?php if (
                                                $is_returned
                                            ): ?>


                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle
                                                        me-1
                                                    "
                                                ></i>


                                                <?php if (
                                                    !empty(
                                                        $book[
                                                            'actual_return_date'
                                                        ]
                                                    )
                                                ): ?>


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


                                                <?php else: ?>


                                                    Returned


                                                <?php endif; ?>


                                            <?php else: ?>


                                                <i
                                                    class="
                                                        bi

                                                        <?php

                                                        echo
                                                            $overdue
                                                                ? 'bi-exclamation-triangle'
                                                                : (
                                                                    $days_remaining === 0
                                                                    ? 'bi-alarm'
                                                                    : 'bi-hourglass-split'
                                                                );

                                                        ?>

                                                        me-1
                                                    "
                                                ></i>


                                                <?php

                                                echo
                                                    $overdue
                                                        ? $days_overdue
                                                        : $days_remaining;

                                                ?>


                                                day<?php

                                                echo (
                                                    (
                                                        $overdue
                                                            ? $days_overdue
                                                            : $days_remaining
                                                    ) == 1
                                                )
                                                    ? ''
                                                    : 's';

                                                ?>


                                            <?php endif; ?>


                                        </div>


                                    </div>



                                    <!-- STATUS -->

                                    <div
                                        class="book-info-box"
                                    >


                                        <div
                                            class="
                                                book-info-box-label
                                            "
                                        >

                                            Status

                                        </div>


                                        <div
                                            class="
                                                book-info-box-value
                                            "
                                        >


                                            <?php if (
                                                $is_returned
                                            ): ?>


                                                <i
                                                    class="
                                                        bi
                                                        bi-bookmark-check-fill
                                                        me-1
                                                    "
                                                ></i>


                                                Returned


                                            <?php elseif (
                                                $overdue
                                            ): ?>


                                                <i
                                                    class="
                                                        bi
                                                        bi-exclamation-circle-fill
                                                        me-1
                                                    "
                                                ></i>


                                                Overdue


                                            <?php else: ?>


                                                <i
                                                    class="
                                                        bi
                                                        bi-bookmark-check
                                                        me-1
                                                    "
                                                ></i>


                                                Issued


                                            <?php endif; ?>


                                        </div>


                                    </div>


                                </div>



                                <!-- =================================================
                                     FINE
                                ================================================== -->

                                <div
                                    class="fine-row"
                                >


                                    <div
                                        class="fine-label"
                                    >


                                        <i
                                            class="
                                                bi
                                                bi-currency-rupee
                                            "
                                        ></i>


                                        Fine


                                    </div>


                                    <?php if (
                                        $is_returned
                                    ): ?>


                                        <div
                                            class="
                                                fine-value

                                                <?php

                                                echo
                                                    $current_fine > 0
                                                        ? 'has-fine'
                                                        : 'no-fine';

                                                ?>
                                            "
                                        >


                                            ₹<?php

                                            echo number_format(
                                                $current_fine,
                                                2
                                            );

                                            ?>


                                        </div>


                                    <?php elseif (
                                        $overdue
                                    ): ?>


                                        <div
                                            class="
                                                fine-value
                                                has-fine
                                            "
                                        >


                                            ₹<?php

                                            echo number_format(
                                                $current_fine,
                                                2
                                            );

                                            ?>


                                        </div>


                                    <?php else: ?>


                                        <div
                                            class="
                                                fine-value
                                                no-fine
                                            "
                                        >

                                            ₹0.00

                                        </div>


                                    <?php endif; ?>


                                </div>



                                <!-- =================================================
                                     DUE DATE REMINDER
                                ================================================== -->

                                <div
                                    class="
                                        due-notification
                                        <?php

                                        echo htmlspecialchars(
                                            $due_notification_class
                                        );

                                        ?>
                                    "
                                >


                                    <div
                                        class="
                                            due-notification-icon
                                        "
                                    >


                                        <i
                                            class="
                                                bi

                                                <?php

                                                echo htmlspecialchars(
                                                    $due_notification_icon
                                                );

                                                ?>
                                            "
                                        ></i>


                                    </div>


                                    <div
                                        class="
                                            due-notification-content
                                        "
                                    >


                                        <strong>


                                            <?php

                                            echo htmlspecialchars(
                                                $due_notification_title
                                            );

                                            ?>


                                        </strong>


                                        <span>


                                            <?php

                                            echo htmlspecialchars(
                                                $due_notification_text
                                            );

                                            ?>


                                        </span>


                                    </div>


                                </div>



                                <!-- =================================================
                                     ACTION AREA
                                ================================================== -->

                                <?php if (
                                    $is_returned
                                ): ?>


                                    <!-- RETURNED -->

                                    <div
                                        class="
                                            review-book-action
                                        "
                                    >


                                        <a
                                            href="<?php echo BASE_URL; ?>/user/my_books/review.php?id=<?php echo (int)$book['id']; ?>"
                                            class="
                                                review-book-btn
                                            "
                                        >


                                            <i
                                                class="
                                                    bi
                                                    bi-star-fill
                                                "
                                            ></i>


                                            Rate & Review


                                        </a>


                                    </div>


                                    <div
                                        class="
                                            return-alert
                                            success
                                        "
                                    >


                                        <i
                                            class="
                                                bi
                                                bi-check-circle-fill
                                            "
                                        ></i>


                                        <span>


                                            This book has been
                                            returned successfully.
                                            Share your experience
                                            by rating and reviewing it.


                                        </span>


                                    </div>


                                <?php else: ?>


                                    <!-- ACTIVE BOOK -->

                                    <?php if (
                                        $overdue
                                    ): ?>


                                        <?php

                                        $payment_is_paid =
                                            isset(
                                                $book[
                                                    'payment_status'
                                                ]
                                            )
                                            &&
                                            $book[
                                                'payment_status'
                                            ]
                                            === 'Paid';

                                        ?>


                                        <?php if (
                                            $payment_is_paid
                                        ): ?>


                                            <!-- FINE PAID -->

                                            <div
                                                class="
                                                    return-alert
                                                    success
                                                "
                                                style="
                                                    margin-bottom:15px;
                                                "
                                            >


                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>


                                                <span>

                                                    Fine paid successfully.
                                                    You can now return this book.

                                                </span>


                                            </div>



                                            <!-- RETURN BUTTON -->

                                            <div
                                                class="
                                                    return-book-action
                                                "
                                            >


                                                <a
                                                    href="<?php
                                                        echo BASE_URL;
                                                    ?>/user/my_books/return_book.php?id=<?php
                                                        echo (int)$book['id'];
                                                    ?>"
                                                    class="
                                                        return-book-btn
                                                    "
                                                    onclick="
                                                        return confirm(
                                                            'Are you sure you want to return this book?'
                                                        );
                                                    "
                                                >


                                                    <i
                                                        class="
                                                            bi
                                                            bi-arrow-return-left
                                                        "
                                                    ></i>


                                                    Return Book


                                                </a>


                                            </div>


                                        <?php else: ?>


                                            <!-- FINE NOT PAID -->

                                            <div
                                                class="
                                                    fine-payment-box
                                                "
                                            >


                                                <div
                                                    class="
                                                        fine-payment-icon
                                                    "
                                                >


                                                    <i
                                                        class="
                                                            bi
                                                            bi-exclamation-triangle-fill
                                                        "
                                                    ></i>


                                                </div>


                                                <div
                                                    class="
                                                        fine-payment-content
                                                    "
                                                >


                                                    <strong>

                                                        Fine Payment Required

                                                    </strong>


                                                    <span>

                                                        This book is overdue.
                                                        Please pay ₹<?php

                                                        echo number_format(
                                                            $current_fine,
                                                            2
                                                        );

                                                        ?>

                                                        before returning
                                                        the book.

                                                    </span>


                                                </div>


                                                <a
                                                    href="<?php
                                                        echo BASE_URL;
                                                    ?>/user/my_books/pay_fine.php?id=<?php
                                                        echo (int)$book['id'];
                                                    ?>"
                                                    class="
                                                        pay-fine-btn
                                                    "
                                                >


                                                    <i
                                                        class="
                                                            bi
                                                            bi-credit-card
                                                        "
                                                    ></i>


                                                    Pay Fine


                                                </a>


                                            </div>


                                            <!-- WARNING -->

                                            <div
                                                class="
                                                    return-alert
                                                    danger
                                                "
                                            >


                                                <i
                                                    class="
                                                        bi
                                                        bi-lock-fill
                                                    "
                                                ></i>


                                                <span>


                                                    You must pay the fine
                                                    before this book can
                                                    be returned.


                                                </span>


                                            </div>


                                        <?php endif; ?>


                                    <?php else: ?>


                                        <!-- NOT OVERDUE -->

                                        <div
                                            class="
                                                return-book-action
                                            "
                                        >


                                            <a
                                                href="<?php
                                                    echo BASE_URL;
                                                ?>/user/my_books/return_book.php?id=<?php
                                                    echo (int)$book['id'];
                                                ?>"
                                                class="
                                                    return-book-btn
                                                "
                                                onclick="
                                                    return confirm(
                                                        'Are you sure you want to return this book?'
                                                    );
                                                "
                                            >


                                                <i
                                                    class="
                                                        bi
                                                        bi-arrow-return-left
                                                    "
                                                ></i>


                                                Return Book


                                            </a>


                                        </div>


                                        <div
                                            class="
                                                return-alert
                                                success
                                            "
                                        >


                                            <i
                                                class="
                                                    bi
                                                    bi-check-circle-fill
                                                "
                                            ></i>


                                            <span>


                                                Return this book before
                                                the due date to avoid
                                                any late fine.


                                            </span>


                                        </div>


                                    <?php endif; ?>


                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- =================================================
                     EMPTY / NO RESULT
                ================================================== -->

                <div
                    class="empty-books"
                >


                    <div
                        class="empty-icon"
                    >

                        <i
                            class="
                                bi
                                <?php

                                echo (
                                    $search !== '' ||
                                    $filter_status !== '' ||
                                    $date_from !== '' ||
                                    $date_to !== ''
                                )
                                    ? 'bi-search'
                                    : 'bi-journal-x';

                                ?>
                            "
                        ></i>


                    </div>


                    <h3>

                        No Books Found

                    </h3>


                    <p>


                        <?php if (
                            $search !== '' ||
                            $filter_status !== '' ||
                            $date_from !== '' ||
                            $date_to !== ''
                        ): ?>


                            No books match your current
                            search or filter criteria.
                            Try changing your filters.


                        <?php else: ?>


                            You currently don't have any
                            issued or returned books associated
                            with your account.


                        <?php endif; ?>


                    </p>


                    <?php if (
                        $search !== '' ||
                        $filter_status !== '' ||
                        $date_from !== '' ||
                        $date_to !== '' ||
                        $sort !== 'newest'
                    ): ?>


                        <a
                            href="index.php"
                            class="browse-btn"
                        >


                            <i
                                class="
                                    bi
                                    bi-arrow-counterclockwise
                                "
                            ></i>


                            Clear Filters


                        </a>


                    <?php else: ?>


                        <a
                            href="<?php
                                echo BASE_URL;
                            ?>/user/books/index.php"
                            class="browse-btn"
                        >


                            <i
                                class="bi bi-book"
                            ></i>


                            Browse Books


                        </a>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>



<!-- =================================================
     SIDEBAR SCRIPT
================================================== -->

<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector(
            '.user-sidebar'
        );


    if (sidebar) {

        sidebar.classList.toggle(
            'show'
        );

    }

}

</script>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- =================================================
     AUTO HIDE TOP ALERTS
================================================== -->

<script>

setTimeout(function () {

    const alerts =
        document.querySelectorAll(
            '.page-alert'
        );


    alerts.forEach(function (alert) {

        alert.classList.remove(
            'show'
        );

    });


}, 5000);

</script>


</body>

</html>