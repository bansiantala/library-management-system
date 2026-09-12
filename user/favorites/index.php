```php
<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = (int)($_SESSION['user_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Status Message
|--------------------------------------------------------------------------
*/

$status_message = "";
$status_type = "";

$favorite_status = $_GET['favorite'] ?? '';

if ($favorite_status === 'added') {

    $status_message = "Book added to your favorites.";
    $status_type = "success";

} elseif ($favorite_status === 'removed') {

    $status_message = "Book removed from your favorites.";
    $status_type = "success";

} elseif ($favorite_status === 'error') {

    $status_message = "Unable to update favorite. Please try again.";
    $status_type = "danger";

} elseif ($favorite_status === 'not_found') {

    $status_message = "Book not found.";
    $status_type = "warning";

}


/*
|--------------------------------------------------------------------------
| Search & Filter Values
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$availability = $_GET['availability'] ?? '';
$sort = $_GET['sort'] ?? 'newest';


/*
|--------------------------------------------------------------------------
| Validate Availability
|--------------------------------------------------------------------------
*/

$allowed_availability = [
    'available',
    'unavailable'
];

if (!in_array($availability, $allowed_availability, true)) {
    $availability = '';
}


/*
|--------------------------------------------------------------------------
| Validate Sort
|--------------------------------------------------------------------------
*/

$allowed_sorts = [
    'newest',
    'oldest',
    'title_asc',
    'title_desc'
];

if (!in_array($sort, $allowed_sorts, true)) {
    $sort = 'newest';
}


/*
|--------------------------------------------------------------------------
| Sort Order
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'oldest':

        $orderBy = "
            favorites.created_at ASC,
            favorites.id ASC
        ";

        break;


    case 'title_asc':

        $orderBy = "
            books.title ASC,
            favorites.id DESC
        ";

        break;


    case 'title_desc':

        $orderBy = "
            books.title DESC,
            favorites.id DESC
        ";

        break;


    case 'newest':
    default:

        $orderBy = "
            favorites.created_at DESC,
            favorites.id DESC
        ";

        break;

}


/*
|--------------------------------------------------------------------------
| Fetch User Favorites
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        favorites.id AS favorite_id,
        favorites.book_id,
        favorites.created_at AS favorite_date,

        books.title,
        books.author,
        books.isbn,
        books.quantity,
        books.available_quantity,

        categories.category_name

    FROM favorites

    INNER JOIN books
        ON favorites.book_id = books.id

    LEFT JOIN categories
        ON books.category_id = categories.id

    WHERE favorites.user_id = ?
";

$params = [$user_id];
$types = "i";


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
            OR categories.category_name LIKE ?
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
| Availability Filter
|--------------------------------------------------------------------------
*/

if ($availability === 'available') {

    $sql .= "
        AND books.available_quantity > 0
    ";

} elseif ($availability === 'unavailable') {

    $sql .= "
        AND books.available_quantity <= 0
    ";

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
| Prepare Query
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

$favorites = [];

while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

$stmt->close();

$totalFavorites = count($favorites);


/*
|--------------------------------------------------------------------------
| Active Filters
|--------------------------------------------------------------------------
*/

$hasActiveFilters =
    $search !== '' ||
    $availability !== '' ||
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
        Favorite Books | Library Management System
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


        /* =========================================================
           PAGE
        ========================================================= */

        .favorite-page {
            width: 100%;
            padding: 35px;
        }


        .favorite-container {
            width: 100%;
            max-width: 1350px;
            margin: 0 auto;
        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .favorite-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 26px;

            flex-wrap: wrap;
        }


        .favorite-title-area {
            display: flex;
            align-items: center;

            gap: 14px;
        }


        .favorite-title-icon {
            width: 55px;
            height: 55px;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #ef4444,
                    #dc2626
                );

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;

            box-shadow:
                0 8px 20px
                rgba(239, 68, 68, 0.20);
        }


        .favorite-title h2 {
            margin: 0;

            color: #172033;

            font-size: 27px;

            font-weight: 800;
        }


        .favorite-title p {
            margin: 5px 0 0;

            color: #7b8798;

            font-size: 13px;
        }


        /* =========================================================
           COUNT
        ========================================================= */

        .favorite-count {
            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 10px 15px;

            background: #fff1f2;

            border: 1px solid #fecdd3;

            border-radius: 12px;

            color: #dc2626;

            font-size: 13px;

            font-weight: 700;
        }


        .favorite-count-number {
            min-width: 28px;
            height: 28px;

            padding: 0 7px;

            border-radius: 8px;

            background: #dc2626;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;
        }


        /* =========================================================
           ALERT
        ========================================================= */

        .favorite-alert {
            border-radius: 11px;

            font-size: 13px;

            margin-bottom: 20px;
        }


        /* =========================================================
           SEARCH & FILTER
        ========================================================= */

        .favorite-filter-card {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 16px;

            padding: 20px 22px;

            margin-bottom: 23px;

            box-shadow:
                0 5px 20px
                rgba(15, 23, 42, 0.04);
        }


        .favorite-filter-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 18px;
        }


        .favorite-filter-title {
            margin: 0;

            display: flex;

            align-items: center;

            gap: 9px;

            color: #253044;

            font-size: 15px;

            font-weight: 800;
        }


        .favorite-filter-title i {
            color: #dc2626;

            font-size: 18px;
        }


        .favorite-filter-result {
            color: #8994a4;

            font-size: 12px;

            font-weight: 600;
        }


        .favorite-filter-grid {
            display: grid;

            grid-template-columns:
                minmax(250px, 1.8fr)
                minmax(170px, 1fr)
                minmax(170px, 1fr)
                auto
                auto;

            gap: 12px;

            align-items: end;
        }


        .favorite-filter-field label {
            display: block;

            color: #788494;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            margin-bottom: 6px;
        }


        .favorite-search-wrapper {
            position: relative;
        }


        .favorite-search-wrapper i {
            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            pointer-events: none;

            font-size: 15px;
        }


        .favorite-filter-input,
        .favorite-filter-select {
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


        .favorite-search-wrapper
        .favorite-filter-input {
            padding-left: 38px;
        }


        .favorite-filter-input:focus,
        .favorite-filter-select:focus {
            border-color: #dc2626;

            box-shadow:
                0 0 0 3px
                rgba(220, 38, 38, 0.09);
        }


        .favorite-search-btn,
        .favorite-reset-btn {
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


        .favorite-search-btn {
            border: 1px solid #dc2626;

            background: #dc2626;

            color: #ffffff;

            cursor: pointer;
        }


        .favorite-search-btn:hover {
            background: #b91c1c;

            border-color: #b91c1c;

            color: #ffffff;
        }


        .favorite-reset-btn {
            border: 1px solid #cbd5e1;

            background: #f8fafc;

            color: #475569;
        }


        .favorite-reset-btn:hover {
            background: #e2e8f0;

            color: #1e293b;
        }


        /* =========================================================
           ACTIVE FILTERS
        ========================================================= */

        .favorite-active-filters {
            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #edf0f4;
        }


        .favorite-filter-label {
            color: #64748b;

            font-size: 11px;

            font-weight: 800;
        }


        .favorite-filter-tag {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            background: #fff1f2;

            border: 1px solid #fecdd3;

            border-radius: 20px;

            color: #dc2626;

            font-size: 10px;

            font-weight: 700;
        }


        /* =========================================================
           FAVORITE GRID
        ========================================================= */

        .favorite-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;
        }


        .favorite-card {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 17px;

            padding: 19px;

            box-shadow:
                0 7px 25px
                rgba(15, 23, 42, 0.05);

            transition: 0.22s ease;

            position: relative;

            overflow: hidden;
        }


        .favorite-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 12px 30px
                rgba(15, 23, 42, 0.09);

            border-color: #fecdd3;
        }


        /* =========================================================
           FAVORITE CARD TOP
        ========================================================= */

        .favorite-card-top {
            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 16px;
        }


        .favorite-book-icon {
            width: 53px;
            height: 53px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #fff1f2,
                    #ffe4e6
                );

            color: #dc2626;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;
        }


        .favorite-heart-icon {
            width: 34px;
            height: 34px;

            border-radius: 9px;

            background: #fff1f2;

            color: #dc2626;

            border: 1px solid #fecdd3;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;
        }


        /* =========================================================
           BOOK INFO
        ========================================================= */

        .favorite-book-title {
            margin: 0 0 6px;

            color: #253044;

            font-size: 16px;

            font-weight: 800;

            line-height: 1.35;

            display: -webkit-box;

            -webkit-line-clamp: 2;

            -webkit-box-orient: vertical;

            overflow: hidden;

            min-height: 43px;
        }


        .favorite-book-author {
            margin: 0;

            color: #7b8798;

            font-size: 12px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .favorite-book-author i {
            color: #94a3b8;

            margin-right: 4px;
        }


        /* =========================================================
           BOOK DETAILS
        ========================================================= */

        .favorite-details {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 9px;

            margin-top: 17px;

            padding-top: 15px;

            border-top: 1px solid #edf0f4;
        }


        .favorite-detail {
            background: #f8fafc;

            border-radius: 8px;

            padding: 10px;
        }


        .favorite-detail label {
            display: block;

            color: #94a3b8;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.35px;

            margin-bottom: 4px;
        }


        .favorite-detail span {
            display: block;

            color: #475467;

            font-size: 11px;

            font-weight: 700;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        /* =========================================================
           AVAILABILITY
        ========================================================= */

        .favorite-available {
            color: #15803d !important;
        }


        .favorite-unavailable {
            color: #dc2626 !important;
        }


        /* =========================================================
           CARD FOOTER
        ========================================================= */

        .favorite-card-footer {
            margin-top: 17px;

            padding-top: 15px;

            border-top: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            flex-wrap: wrap;
        }


        .favorite-date {
            color: #8994a4;

            font-size: 10px;

            font-weight: 600;
        }


        .favorite-date i {
            color: #94a3b8;

            margin-right: 4px;
        }


        .favorite-actions {
            display: flex;

            gap: 7px;

            flex-wrap: wrap;
        }


        .favorite-view-btn,
        .favorite-remove-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 8px 11px;

            border-radius: 8px;

            font-size: 11px;

            font-weight: 700;

            text-decoration: none;

            transition: 0.2s ease;
        }


        .favorite-view-btn {
            background: #eff6ff;

            border: 1px solid #bfdbfe;

            color: #2563eb;
        }


        .favorite-view-btn:hover {
            background: #2563eb;

            border-color: #2563eb;

            color: #ffffff;
        }


        .favorite-remove-btn {
            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #dc2626;
        }


        .favorite-remove-btn:hover {
            background: #dc2626;

            border-color: #dc2626;

            color: #ffffff;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-favorites {
            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 18px;

            padding: 75px 25px;

            text-align: center;

            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, 0.05);
        }


        .empty-favorites-icon {
            width: 85px;
            height: 85px;

            margin: 0 auto 18px;

            border-radius: 23px;

            background: #fff1f2;

            color: #f43f5e;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 38px;
        }


        .empty-favorites h3 {
            margin: 0 0 8px;

            color: #253044;

            font-size: 21px;

            font-weight: 800;
        }


        .empty-favorites p {
            max-width: 470px;

            margin: 0 auto 23px;

            color: #8994a4;

            font-size: 13px;

            line-height: 1.6;
        }


        .browse-favorites-btn {
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

            transition: 0.2s ease;
        }


        .browse-favorites-btn:hover {
            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {

            .favorite-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .favorite-filter-grid {
                grid-template-columns:
                    1.5fr
                    1fr
                    1fr;
            }


            .favorite-search-btn,
            .favorite-reset-btn {
                width: 100%;
            }

        }


        @media (max-width: 900px) {

            .favorite-page {
                padding: 25px 20px;
            }


            .favorite-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .favorite-filter-grid {
                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media (max-width: 767px) {

            .favorite-header {
                align-items: flex-start;
            }


            .favorite-title h2 {
                font-size: 23px;
            }


            .favorite-title-icon {
                width: 48px;
                height: 48px;

                font-size: 21px;
            }


            .favorite-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 576px) {

            .favorite-page {
                padding: 20px 13px;
            }


            .favorite-header {
                flex-direction: column;
            }


            .favorite-count {
                width: 100%;

                justify-content: center;
            }


            .favorite-filter-grid {
                grid-template-columns: 1fr;
            }


            .favorite-filter-card {
                padding: 17px;
            }


            .favorite-card {
                padding: 16px;
            }


            .favorite-card-footer {
                align-items: flex-start;

                flex-direction: column;
            }


            .favorite-actions {
                width: 100%;
            }


            .favorite-view-btn,
            .favorite-remove-btn {
                flex: 1;
            }


            .empty-favorites {
                padding: 55px 18px;
            }

        }


        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            .favorite-filter-card,
            .favorite-actions,
            .user-navbar,
            .user-sidebar {
                display: none !important;
            }


            .favorite-page {
                padding: 10px;
            }


            .favorite-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .favorite-card {
                box-shadow: none;

                break-inside: avoid;
            }

        }

    

        /* Active Favorite Navbar Button */

        .favorite-nav-action {
            background: #fff1f2 !important;
            border-color: #fecdd3 !important;
            color: #dc2626 !important;
        }

        .favorite-nav-action:hover {
            background: #ffe4e6 !important;
            border-color: #fda4af !important;
            color: #dc2626 !important;
        }
        /* =========================================================
   USER NAVBAR
========================================================= */

.user-navbar {
    width: 100%;
    min-height: 78px;

    background: #ffffff;

    padding: 0 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    border-bottom: 1px solid #edf0f5;

    position: sticky;
    top: 0;
    z-index: 900;

    box-shadow:
        0 3px 15px
        rgba(15, 23, 42, 0.035);

    box-sizing: border-box;
}


/* =========================================================
   LEFT
========================================================= */

.user-nav-left {
    display: flex;
    align-items: center;

    gap: 12px;

    flex: 0 0 auto;

    min-width: 0;
}


/* Sidebar Toggle */

.sidebar-toggle {
    width: 40px;
    height: 40px;

    border: none;
    border-radius: 10px;

    background: #f8fafc;
    color: #64748b;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 18px;

    cursor: pointer;

    flex-shrink: 0;

    transition: 0.2s ease;
}

.sidebar-toggle:hover {
    background: #eff6ff;
    color: #2563eb;
}


/* Welcome Icon */

.user-welcome-icon {
    width: 43px;
    height: 43px;

    border-radius: 13px;

    background: #fff1f2;
    color: #dc2626;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 19px;

    flex-shrink: 0;
}


/* Welcome Text */

.user-welcome {
    min-width: 0;
}

.user-welcome span {
    display: block;

    color: #94a3b8;

    font-size: 10px;
    font-weight: 600;

    margin-bottom: 2px;

    white-space: nowrap;
}

.user-welcome h5 {
    margin: 0;

    color: #172033;

    font-size: 15px;
    font-weight: 800;

    max-width: 200px;

    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}


/* =========================================================
   CENTER
========================================================= */

.user-nav-center {
    position: absolute;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    pointer-events: none;
}

.library-status {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 8px;

    padding: 8px 14px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    color: #64748b;

    font-size: 11px;
    font-weight: 600;

    white-space: nowrap;
}

.status-circle {
    width: 8px;
    height: 8px;

    background: #22c55e;

    border-radius: 50%;

    flex-shrink: 0;

    box-shadow:
        0 0 0 4px
        rgba(34, 197, 94, 0.10);
}


/* =========================================================
   RIGHT
========================================================= */

.user-nav-right {
    display: flex;
    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    flex: 0 0 auto;

    min-width: max-content;

    white-space: nowrap;
}


/* =========================================================
   ICON BUTTONS
========================================================= */

.nav-action {
    width: 40px;
    height: 40px;

    flex: 0 0 40px;

    border-radius: 11px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    color: #64748b;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all 0.25s ease;
}

.nav-action:hover {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #2563eb;

    transform: translateY(-1px);
}


/* =========================================================
   ACTIVE FAVORITE BUTTON
========================================================= */

.favorite-nav-action {
    background: #fff1f2 !important;

    border-color: #fecdd3 !important;

    color: #dc2626 !important;
}

.favorite-nav-action:hover {
    background: #fee2e2 !important;

    border-color: #fda4af !important;

    color: #dc2626 !important;
}


/* =========================================================
   SEPARATOR
========================================================= */

.nav-separator {
    width: 1px;

    height: 34px;

    background: #e5e7eb;

    margin: 0 5px;

    flex: 0 0 1px;
}


/* =========================================================
   PROFILE
========================================================= */

.user-profile-pill {
    height: 48px;

    display: inline-flex;

    align-items: center;

    gap: 9px;

    padding: 5px 12px 5px 5px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    flex: 0 0 auto;

    white-space: nowrap;
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

    flex: 0 0 35px;
}


.user-profile-name {
    min-width: 0;
}

.user-profile-name strong {
    display: block;

    color: #334155;

    font-size: 11px;

    font-weight: 700;

    max-width: 120px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.user-profile-name small {
    display: block;

    color: #94a3b8;

    font-size: 9px;

    margin-top: 1px;

    white-space: nowrap;
}


/* =========================================================
   LOGOUT
========================================================= */

.user-logout {
    width: 40px;
    height: 40px;

    flex: 0 0 40px;

    border-radius: 11px;

    background: #fff5f5;

    border: 1px solid #fee2e2;

    color: #ef4444;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all 0.25s ease;
}

.user-logout:hover {
    background: #ef4444;

    color: #ffffff;

    border-color: #ef4444;
}


/* =========================================================
   LARGE SCREEN
========================================================= */

@media (max-width: 1200px) {

    .user-navbar {
        padding: 0 20px;
    }

    .user-nav-right {
        gap: 7px;
    }

    .user-profile-name strong {
        max-width: 100px;
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 900px) {

    .user-navbar {
        min-height: 72px;

        padding: 0 18px;
    }

    .user-nav-center {
        display: none;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 650px) {

    .user-navbar {
        min-height: 70px;

        padding: 0 12px;

        gap: 8px;
    }

    .user-nav-left {
        gap: 7px;
    }

    .sidebar-toggle {
        width: 36px;
        height: 36px;

        font-size: 16px;
    }

    .user-welcome-icon {
        width: 37px;
        height: 37px;

        border-radius: 10px;

        font-size: 16px;
    }

    .user-welcome span {
        font-size: 8px;
    }

    .user-welcome h5 {
        font-size: 12px;

        max-width: 100px;
    }

    .nav-action {
        width: 36px;
        height: 36px;

        flex-basis: 36px;

        font-size: 15px;

        border-radius: 9px;
    }

    .user-logout {
        width: 36px;
        height: 36px;

        flex-basis: 36px;

        font-size: 15px;

        border-radius: 9px;
    }

    .user-profile-pill {
        width: 36px;
        height: 36px;

        padding: 2px;

        border-radius: 50%;
    }

    .user-avatar {
        width: 30px;
        height: 30px;

        flex-basis: 30px;

        font-size: 11px;
    }

    .user-profile-name {
        display: none;
    }

    .nav-separator {
        height: 28px;

        margin: 0 2px;
    }

}


/* =========================================================
   VERY SMALL MOBILE
========================================================= */

@media (max-width: 450px) {

    .user-navbar {
        padding: 0 8px;
    }

    .user-welcome-icon {
        display: none;
    }

    .user-welcome h5 {
        max-width: 85px;
    }

    .user-nav-right {
        gap: 5px;
    }

    .nav-separator {
        display: none;
    }

}
        
</style>

</head>


<body>


<!-- =========================================================
     USER SIDEBAR
========================================================= -->

<?php include "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =========================================================
         USER NAVBAR
    ========================================================== -->

    <!-- =========================================================
     USER NAVBAR
========================================================= -->

<nav class="user-navbar">

    <!-- LEFT -->
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
            <i class="bi bi-heart-fill"></i>
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


    <!-- CENTER -->
    <div class="user-nav-center">

        <div class="library-status">

            <span class="status-circle"></span>

            <span>
                Library is Open
            </span>

        </div>

    </div>


    <!-- RIGHT -->
    <div class="user-nav-right">

        <!-- Search -->
        <a
            href="<?php echo BASE_URL; ?>/user/books/search.php"
            class="nav-action"
            title="Search Books"
            aria-label="Search Books"
        >
            <i class="bi bi-search"></i>
        </a>


        <!-- Favorite -->
        <a
            href="<?php echo BASE_URL; ?>/user/favorites/index.php"
            class="nav-action favorite-nav-action active-nav"
            title="Favorite Books"
            aria-label="Favorite Books"
        >
            <i class="bi bi-heart-fill"></i>
        </a>


        <!-- My Books -->
        <a
            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
            class="nav-action"
            title="My Books"
            aria-label="My Books"
        >
            <i class="bi bi-journal-bookmark"></i>
        </a>


        <!-- Divider -->
        <div class="nav-separator"></div>


        <!-- Profile -->
        <div class="user-profile-pill">

            <div class="user-avatar">

                <?php

                $userName =
                    $_SESSION['user_name'] ?? 'User';

                echo strtoupper(
                    substr(
                        $userName,
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
                        $userName
                    );
                    ?>
                </strong>

                <small>
                    Member
                </small>

            </div>

        </div>


        <!-- Logout -->
        <a
            href="<?php echo BASE_URL; ?>/logout.php"
            class="user-logout"
            title="Logout"
            aria-label="Logout"
        >
            <i class="bi bi-box-arrow-right"></i>
        </a>

    </div>

</nav>

    <!-- =========================================================
         PAGE CONTENT
    ========================================================== -->

    <main class="favorite-page">


        <div class="favorite-container">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="favorite-header">


                <div class="favorite-title-area">


                    <div class="favorite-title-icon">

                        <i class="bi bi-heart-fill"></i>

                    </div>


                    <div class="favorite-title">

                        <h2>
                            Favorite Books
                        </h2>

                        <p>
                            Manage the books you have saved as favorites.
                        </p>

                    </div>


                </div>


                <div class="favorite-count">

                    <span class="favorite-count-number">

                        <?php
                        echo $totalFavorites;
                        ?>

                    </span>

                    Favorite
                    <?php
                    echo $totalFavorites != 1
                        ? 'Books'
                        : 'Book';
                    ?>

                </div>


            </div>


            <!-- =================================================
                 STATUS ALERT
            ================================================== -->

            <?php if ($status_message !== ''): ?>

                <div
                    class="alert alert-<?php
                    echo htmlspecialchars($status_type);
                    ?>
                    alert-dismissible fade show favorite-alert"
                    role="alert"
                >

                    <i
                        class="bi
                        <?php
                        echo $status_type === 'success'
                            ? 'bi-check-circle-fill'
                            : 'bi-exclamation-triangle-fill';
                        ?>"
                    ></i>

                    <?php
                    echo htmlspecialchars($status_message);
                    ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SEARCH & FILTER
            ================================================== -->

            <div class="favorite-filter-card">


                <div class="favorite-filter-header">


                    <h3 class="favorite-filter-title">

                        <i class="bi bi-funnel-fill"></i>

                        Search & Filter Favorites

                    </h3>


                    <span class="favorite-filter-result">

                        <?php
                        echo $totalFavorites;
                        ?>

                        result<?php
                        echo $totalFavorites != 1
                            ? 's'
                            : '';
                        ?>

                        found

                    </span>


                </div>


                <form
                    method="GET"
                    action=""
                >


                    <div class="favorite-filter-grid">


                        <!-- Search -->

                        <div class="favorite-filter-field">

                            <label for="search">
                                Search
                            </label>


                            <div class="favorite-search-wrapper">

                                <i class="bi bi-search"></i>

                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="favorite-filter-input"
                                    placeholder="Book title, author, ISBN or category..."
                                    value="<?php
                                    echo htmlspecialchars(
                                        $search
                                    );
                                    ?>"
                                >

                            </div>

                        </div>


                        <!-- Availability -->

                        <div class="favorite-filter-field">

                            <label for="availability">
                                Availability
                            </label>


                            <select
                                id="availability"
                                name="availability"
                                class="favorite-filter-select"
                            >

                                <option value="">
                                    All Books
                                </option>


                                <option
                                    value="available"
                                    <?php
                                    echo $availability === 'available'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Available
                                </option>


                                <option
                                    value="unavailable"
                                    <?php
                                    echo $availability === 'unavailable'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Not Available
                                </option>

                            </select>

                        </div>


                        <!-- Sort -->

                        <div class="favorite-filter-field">

                            <label for="sort">
                                Sort
                            </label>


                            <select
                                id="sort"
                                name="sort"
                                class="favorite-filter-select"
                            >

                                <option
                                    value="newest"
                                    <?php
                                    echo $sort === 'newest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Recently Added
                                </option>


                                <option
                                    value="oldest"
                                    <?php
                                    echo $sort === 'oldest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Oldest Added
                                </option>


                                <option
                                    value="title_asc"
                                    <?php
                                    echo $sort === 'title_asc'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Title A-Z
                                </option>


                                <option
                                    value="title_desc"
                                    <?php
                                    echo $sort === 'title_desc'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Title Z-A
                                </option>

                            </select>

                        </div>


                        <!-- Search -->

                        <div class="favorite-filter-field">

                            <label>
                                &nbsp;
                            </label>


                            <button
                                type="submit"
                                class="favorite-search-btn"
                            >

                                <i class="bi bi-search"></i>

                                Search

                            </button>

                        </div>


                        <!-- Reset -->

                        <div class="favorite-filter-field">

                            <label>
                                &nbsp;
                            </label>


                            <a
                                href="<?php
                                echo BASE_URL;
                                ?>/user/favorites/index.php"
                                class="favorite-reset-btn"
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


                </form>


                <!-- =================================================
                     ACTIVE FILTERS
                ================================================== -->

                <?php if ($hasActiveFilters): ?>


                    <div class="favorite-active-filters">


                        <span class="favorite-filter-label">

                            Active Filters:

                        </span>


                        <?php if ($search !== ''): ?>


                            <span class="favorite-filter-tag">

                                <i class="bi bi-search"></i>

                                Search:
                                <?php
                                echo htmlspecialchars(
                                    $search
                                );
                                ?>

                            </span>


                        <?php endif; ?>


                        <?php if ($availability !== ''): ?>


                            <span class="favorite-filter-tag">

                                <i class="bi bi-box-seam"></i>

                                Availability:

                                <?php
                                echo $availability === 'available'
                                    ? 'Available'
                                    : 'Not Available';
                                ?>

                            </span>


                        <?php endif; ?>


                        <?php if ($sort === 'oldest'): ?>


                            <span class="favorite-filter-tag">

                                <i class="bi bi-sort-down"></i>

                                Oldest Added

                            </span>


                        <?php elseif ($sort === 'title_asc'): ?>


                            <span class="favorite-filter-tag">

                                <i class="bi bi-sort-alpha-down"></i>

                                Title A-Z

                            </span>


                        <?php elseif ($sort === 'title_desc'): ?>


                            <span class="favorite-filter-tag">

                                <i class="bi bi-sort-alpha-up"></i>

                                Title Z-A

                            </span>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 FAVORITES
            ================================================== -->

            <?php if (!empty($favorites)): ?>


                <div class="favorite-grid">


                    <?php foreach ($favorites as $favorite): ?>


                        <?php

                        $availableQuantity =
                            (int)$favorite['available_quantity'];

                        $totalQuantity =
                            (int)$favorite['quantity'];

                        ?>


                        <div class="favorite-card">


                            <!-- Card Top -->

                            <div class="favorite-card-top">


                                <div class="favorite-book-icon">

                                    <i class="bi bi-book-half"></i>

                                </div>


                                <div
                                    class="favorite-heart-icon"
                                    title="Favorite"
                                >

                                    <i
                                        class="bi bi-heart-fill"
                                    ></i>

                                </div>


                            </div>


                            <!-- Book -->

                            <h3 class="favorite-book-title">

                                <?php

                                echo htmlspecialchars(
                                    $favorite['title']
                                );

                                ?>

                            </h3>


                            <p class="favorite-book-author">

                                <i class="bi bi-person"></i>

                                <?php

                                echo htmlspecialchars(
                                    $favorite['author']
                                );

                                ?>

                            </p>


                            <!-- Details -->

                            <div class="favorite-details">


                                <!-- Category -->

                                <div class="favorite-detail">

                                    <label>
                                        Category
                                    </label>

                                    <span>

                                        <?php

                                        echo htmlspecialchars(
                                            $favorite['category_name']
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </span>

                                </div>


                                <!-- ISBN -->

                                <div class="favorite-detail">

                                    <label>
                                        ISBN
                                    </label>

                                    <span>

                                        <?php

                                        echo htmlspecialchars(
                                            $favorite['isbn']
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </span>

                                </div>


                                <!-- Availability -->

                                <div class="favorite-detail">

                                    <label>
                                        Availability
                                    </label>


                                    <?php if (
                                        $availableQuantity > 0
                                    ): ?>

                                        <span
                                            class="
                                                favorite-available
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-check-circle-fill
                                                "
                                            ></i>

                                            Available

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                favorite-unavailable
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


                                <!-- Quantity -->

                                <div class="favorite-detail">

                                    <label>
                                        Copies
                                    </label>

                                    <span>

                                        <?php
                                        echo $availableQuantity;
                                        ?>

                                        /

                                        <?php
                                        echo $totalQuantity;
                                        ?>

                                        Available

                                    </span>

                                </div>


                            </div>


                            <!-- Footer -->

                            <div class="favorite-card-footer">


                                <div class="favorite-date">

                                    <i
                                        class="
                                            bi
                                            bi-calendar-heart
                                        "
                                    ></i>

                                    Added

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $favorite[
                                                'favorite_date'
                                            ]
                                        )
                                    );

                                    ?>

                                </div>


                                <div class="favorite-actions">


                                    <!-- View Details -->

                                    <a
                                        href="<?php
                                        echo BASE_URL;
                                        ?>/user/books/details.php?id=<?php
                                        echo (int)$favorite['book_id'];
                                        ?>"
                                        class="favorite-view-btn"
                                    >

                                        <i
                                            class="bi bi-eye"
                                        ></i>

                                        Details

                                    </a>


                                    <!-- Remove Favorite -->

                                    <a
                                        href="<?php
                                        echo BASE_URL;
                                        ?>/user/favorites/toggle.php?book_id=<?php
                                        echo (int)$favorite['book_id'];
                                        ?>"
                                        class="favorite-remove-btn"
                                        onclick="return confirm(
                                            'Are you sure you want to remove this book from favorites?'
                                        );"
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-heartbreak-fill
                                            "
                                        ></i>

                                        Remove

                                    </a>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================== -->

                <div class="empty-favorites">


                    <div class="empty-favorites-icon">

                        <i
                            class="bi bi-heart"
                        ></i>

                    </div>


                    <h3>

                        <?php if ($hasActiveFilters): ?>

                            No Favorite Books Found

                        <?php else: ?>

                            No Favorite Books Yet

                        <?php endif; ?>

                    </h3>


                    <p>

                        <?php if ($hasActiveFilters): ?>

                            No favorite books match your current
                            search or filter. Try changing your
                            search criteria.

                        <?php else: ?>

                            You haven't added any books to your
                            favorites yet. Browse the library and
                            save books you would like to read later.

                        <?php endif; ?>

                    </p>


                    <a
                        href="<?php
                        echo BASE_URL;
                        ?>/user/books/index.php"
                        class="browse-favorites-btn"
                    >

                        <i class="bi bi-book"></i>

                        Browse Books

                    </a>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector('.user-sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }

}

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>