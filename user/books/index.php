<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = (int)($_SESSION['user_id'] ?? 0);


/* =====================================================
   RESERVATION MESSAGE
===================================================== */

$reservation_status = $_GET['reservation'] ?? '';

$reservation_message = '';
$reservation_type = '';

if ($reservation_status === 'success') {

    $reservation_message =
        "Book reserved successfully.";

    $reservation_type = "success";

}

if ($reservation_status === 'exists') {

    $reservation_message =
        "You have already reserved this book.";

    $reservation_type = "warning";

}

if ($reservation_status === 'error') {

    $reservation_message =
        "Unable to reserve this book. Please try again.";

    $reservation_type = "danger";

}


/* =====================================================
   FAVORITE MESSAGE
===================================================== */

$favorite_status = $_GET['favorite'] ?? '';

$favorite_message = '';
$favorite_type = '';

if ($favorite_status === 'added') {

    $favorite_message =
        "Book added to your favorites.";

    $favorite_type = "success";

} elseif ($favorite_status === 'removed') {

    $favorite_message =
        "Book removed from your favorites.";

    $favorite_type = "success";

} elseif ($favorite_status === 'error') {

    $favorite_message =
        "Unable to update favorite. Please try again.";

    $favorite_type = "danger";

} elseif ($favorite_status === 'not_found') {

    $favorite_message =
        "Book not found.";

    $favorite_type = "warning";

}


/* =====================================================
   ADVANCED SEARCH & FILTER
===================================================== */

$search = trim($_GET['search'] ?? '');

$selected_category =
    (int)($_GET['category'] ?? 0);


/* =====================================================
   GET CATEGORIES
===================================================== */

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

    while ($category = $categoryResult->fetch_assoc()) {

        $categories[] = $category;

    }

    $categoryStmt->close();

}


/* =====================================================
   GET BOOKS WITH SEARCH & FILTER
===================================================== */

$sql = "
    SELECT
        books.*,
        categories.category_name

    FROM books

    INNER JOIN categories
        ON books.category_id = categories.id

    LEFT JOIN favorites
        ON favorites.book_id = books.id
        AND favorites.user_id = ?

    WHERE 1 = 1
";

$params = [$user_id];
$types = "i";


/* =====================================================
   SEARCH BY TITLE / AUTHOR / ISBN
===================================================== */

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


/* =====================================================
   CATEGORY FILTER
===================================================== */

if ($selected_category > 0) {

    $sql .= "
        AND books.category_id = ?
    ";

    $params[] =
        $selected_category;

    $types .= "i";

}


/* =====================================================
   ORDER
===================================================== */

$sql .= "
    ORDER BY books.id DESC
";


/* =====================================================
   PREPARE BOOK QUERY
===================================================== */

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


/* =====================================================
   BIND SEARCH / FILTER VALUES
===================================================== */

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


/* =====================================================
   EXECUTE
===================================================== */

$stmt->execute();


$result = $stmt->get_result();


$totalBooks =
    $result->num_rows;

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
        Browse Books | Library Management System
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
           BROWSE BOOKS PAGE
        ========================================= */

        .books-page {

            padding: 32px;
        }


        /* =========================================
           RESERVATION ALERT
        ========================================= */

        .reservation-alert {

            position: fixed;

            top: 95px;

            right: 25px;

            z-index: 2000;

            min-width: 320px;

            max-width: 430px;

            border-radius: 12px;

            padding: 12px 15px;

            font-size: 12px;

            font-weight: 600;

            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.12);
        }


        @media (max-width: 576px) {

            .reservation-alert {

                left: 15px;

                right: 15px;

                top: 80px;

                min-width: auto;

                max-width: none;
            }

        }


        .favorite-alert {

            position: fixed;

            top: 95px;

            right: 25px;

            z-index: 2000;

            min-width: 320px;

            max-width: 430px;

            border-radius: 12px;

            padding: 12px 15px;

            font-size: 12px;

            font-weight: 600;

            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.12);
        }


        @media (max-width: 576px) {

            .favorite-alert {

                left: 15px;

                right: 15px;

                top: 80px;

                min-width: auto;

                max-width: none;
            }

        }


        /* =========================================
           FAVORITE BUTTON
        ========================================= */

        .favorite-btn {

            position: absolute;

            top: 15px;

            left: 15px;

            min-height: 34px;

            padding: 7px 11px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            border-radius: 20px;

            background: #ffffff;

            border: 1px solid #fecdd3;

            color: #dc2626;

            text-decoration: none;

            font-size: 10px;

            font-weight: 800;

            box-shadow:
                0 4px 12px
                rgba(15,23,42,.06);

            transition: all .2s ease;

            z-index: 2;
        }


        .favorite-btn:hover {

            background: #dc2626;

            border-color: #dc2626;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .favorite-btn.is-favorite {

            background: #fff1f2;

            border-color: #fecdd3;

            color: #dc2626;
        }


        .favorite-btn.is-favorite:hover {

            background: #dc2626;

            border-color: #dc2626;

            color: #ffffff;
        }


        @media (max-width: 480px) {

            .favorite-btn span {

                display: none;
            }

            .favorite-btn {

                width: 34px;

                height: 34px;

                padding: 0;
            }

        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .books-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .books-heading {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .books-heading-icon {

            width: 54px;

            height: 54px;

            border-radius: 15px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;
        }


        .books-heading h2 {

            margin: 0;

            color: #172033;

            font-size: 25px;

            font-weight: 800;
        }


        .books-heading p {

            margin: 5px 0 0;

            color: #94a3b8;

            font-size: 13px;
        }


        /* =========================================
           SEARCH BUTTON
        ========================================= */

        .search-books-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 12px 19px;

            background: #2563eb;

            color: #ffffff;

            border-radius: 11px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            box-shadow:
                0 5px 15px
                rgba(37,99,235,.18);

            transition: .25s ease;
        }


        .search-books-btn:hover {

            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(37,99,235,.25);
        }


        /* =========================================
           ADVANCED SEARCH & FILTER
        ========================================= */

        .advanced-filter-card {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 15px;

            padding: 20px;

            margin-bottom: 24px;

            box-shadow:
                0 5px 18px
                rgba(15,23,42,.04);
        }


        .advanced-filter-header {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 16px;
        }


        .advanced-filter-icon {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;
        }


        .advanced-filter-header strong {

            color: #334155;

            font-size: 14px;

            font-weight: 800;
        }


        .advanced-filter-header span {

            display: block;

            color: #94a3b8;

            font-size: 11px;

            margin-top: 2px;
        }


        .advanced-filter-form {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                250px
                auto
                auto;

            gap: 12px;

            align-items: end;
        }


        .filter-group {

            min-width: 0;
        }


        .filter-group label {

            display: block;

            margin-bottom: 6px;

            color: #475569;

            font-size: 11px;

            font-weight: 700;
        }


        .filter-input,
        .filter-select {

            width: 100%;

            height: 44px;

            border: 1px solid #d9e1ea;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            padding: 0 13px;

            font-size: 12px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .filter-input:focus,
        .filter-select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }


        .filter-input::placeholder {

            color: #a1aab8;
        }


        .filter-search-btn,
        .filter-reset-btn {

            height: 44px;

            padding: 0 17px;

            border-radius: 9px;

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


        .filter-search-btn {

            border: 1px solid #2563eb;

            background: #2563eb;

            color: #ffffff;

            cursor: pointer;
        }


        .filter-search-btn:hover {

            background: #1d4ed8;

            border-color: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .filter-reset-btn {

            border: 1px solid #dbe3ed;

            background: #f8fafc;

            color: #64748b;
        }


        .filter-reset-btn:hover {

            background: #eef2f7;

            color: #334155;
        }


        /* =========================================
           ACTIVE FILTER INFO
        ========================================= */

        .active-filter-info {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-top: 13px;

            color: #64748b;

            font-size: 11px;
        }


        .active-filter-info i {

            color: #2563eb;
        }


        .active-filter-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            color: #2563eb;

            font-size: 10px;

            font-weight: 700;
        }


        /* =========================================
           BOOK COUNT
        ========================================= */

        .books-count-bar {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 13px;

            padding: 13px 17px;

            margin-bottom: 25px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;
        }


        .books-count-left {

            display: flex;

            align-items: center;

            gap: 9px;

            color: #64748b;

            font-size: 12px;

            font-weight: 600;
        }


        .books-count-left i {

            color: #2563eb;

            font-size: 16px;
        }


        .books-count-number {

            background: #eff6ff;

            color: #2563eb;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;
        }


        /* =========================================
           BOOK CARD
        ========================================= */

        .book-card {

            height: 100%;

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 5px 18px
                rgba(15,23,42,.04);

            transition: all .3s ease;
        }


        .book-card:hover {

            transform: translateY(-5px);

            border-color: #cbdcfb;

            box-shadow:
                0 15px 30px
                rgba(15,23,42,.09);
        }


        /* =========================================
           BOOK TOP
        ========================================= */

        .book-card-top {

            position: relative;

            min-height: 155px;

            padding: 25px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fbff
                );

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .book-icon-box {

            width: 82px;

            height: 82px;

            border-radius: 20px;

            background: #ffffff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 37px;

            box-shadow:
                0 8px 20px
                rgba(37,99,235,.12);
        }


        .book-category {

            position: absolute;

            top: 15px;

            right: 15px;

            background: #ffffff;

            color: #2563eb;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .4px;

            box-shadow:
                0 3px 10px
                rgba(15,23,42,.06);
        }


        /* =========================================
           BOOK BODY
        ========================================= */

        .book-card-body {

            padding: 23px;
        }


        .book-title {

            margin: 0 0 8px;

            color: #172033;

            font-size: 17px;

            font-weight: 800;

            line-height: 1.4;

            min-height: 47px;
        }


        .book-author {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #64748b;

            font-size: 12px;

            margin-bottom: 19px;
        }


        .book-author i {

            color: #94a3b8;
        }


        /* =========================================
           BOOK DETAILS
        ========================================= */

        .book-details {

            padding-top: 17px;

            border-top: 1px solid #eef2f7;
        }


        .book-detail-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 11px;
        }


        .book-detail-row:last-child {

            margin-bottom: 0;
        }


        .detail-label {

            color: #94a3b8;

            font-size: 11px;

            font-weight: 500;
        }


        .detail-value {

            color: #334155;

            font-size: 11px;

            font-weight: 700;

            text-align: right;

            max-width: 60%;

            word-break: break-word;
        }


        /* =========================================
           AVAILABILITY
        ========================================= */

        .availability-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 9px;

            font-weight: 800;
        }


        .availability-badge.available {

            background: #ecfdf5;

            color: #059669;
        }


        .availability-badge.out {

            background: #fef2f2;

            color: #dc2626;
        }


        /* =========================================
           BOOK ACTION BUTTON
        ========================================= */

        .book-action-btn {

            width: 100%;

            margin-top: 20px;

            padding: 11px 15px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            transition: all .25s ease;
        }


        /* REQUEST BOOK */

        .request-btn {

            background: #2563eb;

            border: 1px solid #2563eb;

            color: #ffffff;
        }


        .request-btn:hover {

            background: #1d4ed8;

            border-color: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 7px 15px
                rgba(37,99,235,.18);
        }


        /* RESERVE BOOK */

        .reserve-btn {

            background: #fff7ed;

            border: 1px solid #fed7aa;

            color: #ea580c;
        }


        .reserve-btn:hover {

            background: #ea580c;

            border-color: #ea580c;

            color: #ffffff;

            transform: translateY(-1px);

            box-shadow:
                0 7px 15px
                rgba(234,88,12,.18);
        }


        /* =========================================
           VIEW BUTTON
        ========================================= */

        .view-book-btn {

            width: 100%;

            margin-top: 12px;

            padding: 11px 15px;

            border-radius: 10px;

            background: #f8fafc;

            border: 1px solid #dbe3ed;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            transition: .25s ease;
        }


        .view-book-btn:hover {

            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* =========================================
           EMPTY STATE
        ========================================= */

        .empty-books {

            background: #ffffff;

            border: 1px dashed #cbd5e1;

            border-radius: 18px;

            padding: 65px 30px;

            text-align: center;
        }


        .empty-books-icon {

            width: 75px;

            height: 75px;

            margin: 0 auto 17px;

            border-radius: 20px;

            background: #f1f5f9;

            color: #94a3b8;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 31px;
        }


        .empty-books h4 {

            margin: 0 0 7px;

            color: #334155;

            font-size: 18px;

            font-weight: 800;
        }


        .empty-books p {

            margin: 0;

            color: #94a3b8;

            font-size: 12px;

            line-height: 1.6;
        }


        /* =========================================
           NAVBAR
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

            box-shadow:
                0 3px 15px
                rgba(15,23,42,.035);
        }


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


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1000px) {

            .advanced-filter-form {

                grid-template-columns:
                    1fr 1fr;

            }

        }


        @media (max-width: 900px) {

            .user-nav-center {

                display: none;
            }

            .user-navbar {

                padding: 0 20px;
            }

        }


        @media (max-width: 768px) {

            .books-page {

                padding: 22px 18px;
            }

            .books-header {

                align-items: flex-start;

                flex-direction: column;
            }

            .search-books-btn {

                width: 100%;

                justify-content: center;
            }

            .books-heading h2 {

                font-size: 21px;
            }

            .advanced-filter-form {

                grid-template-columns: 1fr;
            }

            .advanced-filter-card {

                padding: 17px;
            }

        }


        @media (max-width: 650px) {

            .user-navbar {

                height: 70px;

                padding: 0 14px;
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


        @media (max-width: 480px) {

            .books-page {

                padding: 17px 13px;
            }

            .books-heading-icon {

                width: 46px;

                height: 46px;

                font-size: 20px;
            }

            .books-heading p {

                font-size: 11px;
            }

            .book-card-body {

                padding: 20px;
            }

            .books-count-bar {

                align-items: flex-start;

                flex-direction: column;
            }

        }


        @media (max-width: 450px) {

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


        /* =========================================
           PRINT
        ========================================= */

        @media print {

            .user-navbar,
            .user-sidebar,
            .advanced-filter-card,
            .search-books-btn,
            .book-action-btn,
            .view-book-btn,
            .favorite-btn,
            .reservation-alert,
            .favorite-alert {

                display: none !important;
            }

            .books-page {

                padding: 0;
            }

            .book-card {

                box-shadow: none;

                border: 1px solid #ddd;
            }

        }

    </style>

</head>


<body>


<!-- =================================================
     RESERVATION MESSAGE
================================================== -->

<?php if (!empty($reservation_message)) { ?>

    <div
        class="reservation-alert alert alert-<?php echo $reservation_type; ?> alert-dismissible fade show"
        role="alert"
    >

        <?php if ($reservation_type === 'success') { ?>

            <i class="bi bi-check-circle-fill me-2"></i>

        <?php } elseif ($reservation_type === 'warning') { ?>

            <i class="bi bi-exclamation-circle-fill me-2"></i>

        <?php } else { ?>

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

        <?php } ?>


        <?php

        echo htmlspecialchars(
            $reservation_message
        );

        ?>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>

    </div>

<?php } ?>


<!-- =========================================
     FAVORITE MESSAGE
========================================= -->

<?php if (!empty($favorite_message)) { ?>

    <div
        class="favorite-alert alert alert-<?php echo $favorite_type; ?> alert-dismissible fade show"
        role="alert"
    >

        <?php if ($favorite_type === 'success') { ?>

            <i class="bi bi-heart-fill me-2"></i>

        <?php } elseif ($favorite_type === 'warning') { ?>

            <i class="bi bi-exclamation-circle-fill me-2"></i>

        <?php } else { ?>

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

        <?php } ?>


        <?php echo htmlspecialchars($favorite_message); ?>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>

    </div>

<?php } ?>


<!-- =========================================
     USER SIDEBAR
========================================= -->

<?php include "../../includes/user_sidebar.php"; ?>


<!-- =========================================
     MAIN
========================================= -->

<div class="user-main">


    <!-- =====================================
         USER NAVBAR
    ====================================== -->

    <!-- =====================================
         CONTENT
    ====================================== -->

    <div class="books-page">


        <!-- =================================
             PAGE HEADER
        ================================== -->

        <div class="books-header">


            <div class="books-heading">

                <div class="books-heading-icon">

                    <i class="bi bi-collection"></i>

                </div>


                <div>

                    <h2>
                        Browse Books
                    </h2>

                    <p>
                        Explore and discover books available in our library.
                    </p>

                </div>

            </div>


            <a
                href="search.php"
                class="search-books-btn"
            >

                <i class="bi bi-search"></i>

                Search Books

            </a>

        </div>


        <!-- =================================
             ADVANCED SEARCH & FILTER
        ================================== -->

        <div class="advanced-filter-card">


            <div class="advanced-filter-header">

                <div class="advanced-filter-icon">

                    <i class="bi bi-funnel-fill"></i>

                </div>


                <div>

                    <strong>
                        Advanced Search & Filter
                    </strong>

                    <span>
                        Search by title, author, ISBN or category
                    </span>

                </div>

            </div>


            <form
                method="GET"
                action=""
                class="advanced-filter-form"
            >


                <!-- SEARCH -->

                <div class="filter-group">

                    <label for="search">
                        Search Books
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="filter-input"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by title, author or ISBN..."
                    >

                </div>


                <!-- CATEGORY -->

                <div class="filter-group">

                    <label for="category">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        class="filter-select"
                    >

                        <option value="0">
                            All Categories
                        </option>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option
                                value="<?php echo (int)$category['id']; ?>"
                                <?php
                                echo (
                                    $selected_category ===
                                    (int)$category['id']
                                )
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


                <!-- SEARCH BUTTON -->

                <button
                    type="submit"
                    class="filter-search-btn"
                >

                    <i class="bi bi-search"></i>

                    Search

                </button>


                <!-- RESET -->

                <a
                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                    class="filter-reset-btn"
                >

                    <i class="bi bi-arrow-clockwise"></i>

                    Reset

                </a>


            </form>


            <!-- ACTIVE FILTER INFORMATION -->

            <?php if (
                $search !== '' ||
                $selected_category > 0
            ): ?>

                <div class="active-filter-info">

                    <i class="bi bi-info-circle-fill"></i>

                    <span>
                        Active filters:
                    </span>


                    <?php if ($search !== ''): ?>

                        <span class="active-filter-tag">

                            <i class="bi bi-search"></i>

                            <?php
                            echo htmlspecialchars(
                                $search
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <?php if (
                        $selected_category > 0
                    ): ?>

                        <?php

                        $selectedCategoryName =
                            'Selected Category';

                        foreach (
                            $categories
                            as $category
                        ) {

                            if (
                                (int)$category['id'] ===
                                $selected_category
                            ) {

                                $selectedCategoryName =
                                    $category[
                                        'category_name'
                                    ];

                                break;

                            }

                        }

                        ?>

                        <span class="active-filter-tag">

                            <i class="bi bi-tag-fill"></i>

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


        <!-- =================================
             BOOK COUNT
        ================================== -->

        <div class="books-count-bar">

            <div class="books-count-left">

                <i class="bi bi-bookshelf"></i>

                <span>

                    <?php if (
                        $search !== '' ||
                        $selected_category > 0
                    ): ?>

                        Matching books found

                    <?php else: ?>

                        Books available in library

                    <?php endif; ?>

                </span>

            </div>


            <span class="books-count-number">

                <?php

                echo $totalBooks;

                ?>

                Book<?php echo $totalBooks != 1 ? 's' : ''; ?>

            </span>

        </div>


        <!-- =================================
             BOOK GRID
        ================================== -->

        <div class="row g-4">


            <?php if (
                $result &&
                $result->num_rows > 0
            ): ?>


                <?php while (
                    $book =
                    $result->fetch_assoc()
                ): ?>


                    <div class="col-xl-4 col-lg-6 col-md-6">


                        <div class="book-card">


                            <!-- =================================
                                 BOOK TOP
                            ================================== -->

                            <div class="book-card-top">


                                <div class="book-icon-box">

                                    <i class="bi bi-book-half"></i>

                                </div>


                                <?php
                                $isFavorite =
                                    !empty($book['favorite_id']);
                                ?>

                                <a
                                    href="<?php
                                    echo BASE_URL;
                                    ?>/user/favorites/toggle.php?book_id=<?php
                                    echo (int)$book['id'];
                                    ?>"
                                    class="favorite-btn <?php
                                    echo $isFavorite
                                        ? 'is-favorite'
                                        : '';
                                    ?>"
                                    title="<?php
                                    echo $isFavorite
                                        ? 'Remove from Favorites'
                                        : 'Add to Favorites';
                                    ?>"
                                    onclick="return confirm(
                                        '<?php
                                        echo $isFavorite
                                            ? 'Remove this book from favorites?'
                                            : 'Add this book to favorites?';
                                        ?>'
                                    );"
                                >

                                    <i class="bi <?php
                                        echo $isFavorite
                                            ? 'bi-heart-fill'
                                            : 'bi-heart';
                                    ?>"></i>

                                    <span>
                                        <?php
                                        echo $isFavorite
                                            ? 'Favorite'
                                            : 'Add Favorite';
                                        ?>
                                    </span>

                                </a>


                                <span class="book-category">

                                    <?php

                                    echo htmlspecialchars(
                                        $book[
                                            'category_name'
                                        ]
                                    );

                                    ?>

                                </span>


                            </div>


                            <!-- =================================
                                 BOOK BODY
                            ================================== -->

                            <div class="book-card-body">


                                <!-- TITLE -->

                                <h5 class="book-title">

                                    <?php

                                    echo htmlspecialchars(
                                        $book['title']
                                    );

                                    ?>

                                </h5>


                                <!-- AUTHOR -->

                                <div class="book-author">

                                    <i class="bi bi-person"></i>

                                    <span>

                                        <?php

                                        echo htmlspecialchars(
                                            $book['author']
                                        );

                                        ?>

                                    </span>

                                </div>


                                <!-- DETAILS -->

                                <div class="book-details">


                                    <!-- CATEGORY -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">

                                            Category

                                        </span>

                                        <span class="detail-value">

                                            <?php

                                            echo htmlspecialchars(
                                                $book[
                                                    'category_name'
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </div>


                                    <!-- ISBN -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">

                                            ISBN

                                        </span>

                                        <span class="detail-value">

                                            <?php

                                            echo !empty(
                                                $book['isbn']
                                            )
                                                ? htmlspecialchars(
                                                    $book['isbn']
                                                )
                                                : 'Not available';

                                            ?>

                                        </span>

                                    </div>


                                    <!-- TOTAL QUANTITY -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">

                                            Total Copies

                                        </span>

                                        <span class="detail-value">

                                            <?php

                                            echo (int)
                                                $book[
                                                    'quantity'
                                                ];

                                            ?>

                                        </span>

                                    </div>


                                    <!-- AVAILABILITY -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">

                                            Availability

                                        </span>


                                        <?php if (
                                            (int)$book[
                                                'available_quantity'
                                            ] > 0
                                        ): ?>

                                            <span
                                                class="
                                                    availability-badge
                                                    available
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>

                                                Available

                                                (

                                                <?php

                                                echo (int)
                                                    $book[
                                                        'available_quantity'
                                                    ];

                                                ?>

                                                )

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    availability-badge
                                                    out
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-x-circle-fill
                                                    "
                                                ></i>

                                                Not Available

                                            </span>

                                        <?php endif; ?>

                                    </div>


                                </div>


                                <!-- =================================
                                     REQUEST / RESERVE
                                ================================== -->

                                <?php if (
                                    (int)$book[
                                        'available_quantity'
                                    ] > 0
                                ): ?>


                                    <!-- BOOK AVAILABLE -->

                                    <a
                                        href="<?php
                                            echo BASE_URL;
                                        ?>/user/books/issue_request.php?id=<?php
                                            echo (int)$book['id'];
                                        ?>"
                                        class="
                                            book-action-btn
                                            request-btn
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-journal-plus
                                            "
                                        ></i>

                                        Request Book

                                    </a>


                                <?php else: ?>


                                    <!-- BOOK NOT AVAILABLE -->

                                    <a
                                        href="<?php
                                            echo BASE_URL;
                                        ?>/user/books/reserve.php?id=<?php
                                            echo (int)$book['id'];
                                        ?>"
                                        class="
                                            book-action-btn
                                            reserve-btn
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-bookmark-plus
                                            "
                                        ></i>

                                        Reserve Book

                                    </a>


                                <?php endif; ?>


                                <!-- =================================
                                     VIEW DETAILS
                                ================================== -->

                                <a
                                    href="<?php
                                        echo BASE_URL;
                                    ?>/user/books/details.php?id=<?php
                                        echo (int)$book['id'];
                                    ?>"
                                    class="view-book-btn"
                                >

                                    <i
                                        class="bi bi-eye"
                                    ></i>

                                    View Book Details

                                    <i
                                        class="bi bi-arrow-right"
                                    ></i>

                                </a>


                            </div>

                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <!-- =================================
                     EMPTY STATE
                ================================== -->

                <div class="col-12">


                    <div class="empty-books">


                        <div class="empty-books-icon">

                            <i class="bi bi-search"></i>

                        </div>


                        <h4>
                            No Books Found
                        </h4>


                        <?php if (
                            $search !== '' ||
                            $selected_category > 0
                        ): ?>

                            <p>

                                No books match your
                                search or filter criteria.

                                Try changing the search
                                or category filter.

                            </p>


                            <a
                                href="<?php echo BASE_URL; ?>/user/books/index.php"
                                class="filter-reset-btn"
                                style="
                                    margin-top:18px;
                                    display:inline-flex;
                                "
                            >

                                <i
                                    class="bi bi-arrow-clockwise"
                                ></i>

                                Clear Filters

                            </a>

                        <?php else: ?>

                            <p>

                                There are currently no books
                                available in the library.

                            </p>

                        <?php endif; ?>


                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


<!-- =========================================
     SIDEBAR SCRIPT
========================================= -->

<script>

function toggleSidebar()
{
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


/* =========================================
   AUTO HIDE RESERVATION MESSAGE
========================================= */

setTimeout(function () {

    const alert =
        document.querySelector(
            '.reservation-alert'
        );

    if (alert) {

        alert.classList.remove(
            'show'
        );

    }

}, 5000);


/* =========================================
   AUTO HIDE FAVORITE MESSAGE
========================================= */

setTimeout(function () {

    const alert =
        document.querySelector(
            '.favorite-alert'
        );

    if (alert) {

        alert.classList.remove(
            'show'
        );

    }

}, 5000);

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>