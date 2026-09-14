<?php

require_once "../../config/database.php";
require_once "../../config/auth.php";

requireAdmin();


// =========================================================
// SEARCH + FILTER
// =========================================================

$search = trim($_GET['search'] ?? '');

$rating = $_GET['rating'] ?? '';

$sort = $_GET['sort'] ?? 'newest';


// =========================================================
// VALIDATE RATING
// =========================================================

$allowedRatings = [
    '1',
    '2',
    '3',
    '4',
    '5'
];

if (!in_array($rating, $allowedRatings, true)) {
    $rating = '';
}


// =========================================================
// VALIDATE SORT
// =========================================================

$allowedSorts = [
    'newest',
    'oldest'
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}


// =========================================================
// SORT
// =========================================================

if ($sort === 'oldest') {

    $orderBy = "book_reviews.id ASC";

} else {

    $orderBy = "book_reviews.id DESC";

}


// =========================================================
// GET FILTERED REVIEWS
// =========================================================

$sql = "
    SELECT
        book_reviews.id,
        book_reviews.rating,
        book_reviews.review,
        book_reviews.created_at,

        books.title,

        users.name AS user_name,
        users.email

    FROM book_reviews

    INNER JOIN books
        ON book_reviews.book_id = books.id

    INNER JOIN users
        ON book_reviews.user_id = users.id

    WHERE 1 = 1
";


$params = [];

$types = "";


// =========================================================
// SEARCH
// =========================================================

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR users.name LIKE ?
            OR users.email LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}


// =========================================================
// RATING FILTER
// =========================================================

if ($rating !== '') {

    $sql .= "
        AND book_reviews.rating = ?
    ";

    $ratingValue = (int)$rating;

    $params[] = $ratingValue;

    $types .= "i";
}


// =========================================================
// ORDER
// =========================================================

$sql .= "
    ORDER BY $orderBy
";


$stmt = $conn->prepare($sql);

$reviews = false;

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }

    $stmt->execute();

    $reviews = $stmt->get_result();
}


// =========================================================
// RESULT COUNT
// =========================================================

$reviewCount = 0;

if ($reviews) {

    $reviewCount = $reviews->num_rows;
}


// =========================================================
// TOTAL REVIEWS
// =========================================================

$totalReviews = 0;

$totalReviewResult = $conn->query(
    "SELECT COUNT(*) AS total FROM book_reviews"
);

if ($totalReviewResult) {

    $totalReviewData =
        $totalReviewResult->fetch_assoc();

    $totalReviews =
        (int)($totalReviewData['total'] ?? 0);
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
        Book Reviews - Admin
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

        /* =====================================================
           ADMIN NAVBAR
        ====================================================== */

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

            transition: .2s ease;
        }


        .notification-btn:hover,
        .theme-toggle-btn:hover {

            background: #f8fafc;

            color: #2563eb;

            border-color: #cbd5e1;
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


        /* =====================================================
           REVIEW FILTER CARD
        ====================================================== */

        .review-filter-card {

            background: #ffffff;

            border: 1px solid #edf0f4;

            border-radius: 15px;

            padding: 20px 22px;

            margin-bottom: 24px;

            box-shadow:
                0 5px 20px rgba(30, 41, 59, 0.05);
        }


        .review-filter-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 16px;
        }


        .review-filter-title {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .review-filter-title i {

            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #f1f3ff;

            color: #5664d2;

            font-size: 17px;
        }


        .review-filter-title h5 {

            margin: 0;

            color: #202938;

            font-size: 15px;

            font-weight: 750;
        }


        .review-filter-title span {

            display: block;

            margin-top: 2px;

            color: #8b96a3;

            font-size: 12px;
        }


        .review-result-count {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            background: #f1f3ff;

            color: #5664d2;

            border-radius: 20px;

            padding: 6px 11px;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           FILTER GRID
        ====================================================== */

        .review-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                180px
                180px
                auto
                auto;

            gap: 12px;

            align-items: end;
        }


        .review-filter-field label {

            display: block;

            margin-bottom: 7px;

            color: #586474;

            font-size: 12px;

            font-weight: 700;
        }


        .review-search-wrapper {

            position: relative;
        }


        .review-search-wrapper i {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #98a1ad;

            font-size: 15px;
        }


        .review-filter-input,
        .review-filter-select {

            width: 100%;

            height: 43px;

            border: 1px solid #dfe4ea;

            border-radius: 9px;

            background: #ffffff;

            color: #303a49;

            font-size: 13px;

            outline: none;

            padding: 0 13px;

            transition: all 0.2s ease;
        }


        .review-filter-input {

            padding-left: 39px;
        }


        .review-filter-input:focus,
        .review-filter-select:focus {

            border-color: #5664d2;

            box-shadow:
                0 0 0 3px
                rgba(86, 100, 210, 0.10);
        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .review-search-btn,
        .review-reset-btn {

            height: 43px;

            padding: 0 16px;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            transition: all 0.2s ease;

            white-space: nowrap;
        }


        .review-search-btn {

            border: 1px solid #5664d2;

            background: #5664d2;

            color: #ffffff;
        }


        .review-search-btn:hover {

            background: #4352c5;

            border-color: #4352c5;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .review-reset-btn {

            border: 1px solid #dfe4ea;

            background: #ffffff;

            color: #667180;
        }


        .review-reset-btn:hover {

            background: #f7f8fa;

            color: #3f4855;

            border-color: #cfd6de;
        }


        /* =====================================================
           ACTIVE FILTERS
        ====================================================== */

        .review-active-filters {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 15px;

            padding-top: 14px;

            border-top: 1px solid #eef1f4;
        }


        .review-filter-label {

            color: #8994a1;

            font-size: 12px;

            font-weight: 700;

            margin-right: 2px;
        }


        .review-filter-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            background: #f1f3ff;

            color: #5664d2;

            border: 1px solid #e1e4ff;

            border-radius: 20px;

            padding: 5px 10px;

            font-size: 11px;

            font-weight: 700;
        }


        .review-filter-tag i {

            font-size: 11px;
        }


        /* =====================================================
           REVIEW STARS
        ====================================================== */

        .admin-review-stars {

            white-space: nowrap;
        }


        .admin-review-stars i {

            font-size: 14px;

            margin-right: 1px;

            color: #f59e0b;
        }


        /* =====================================================
           REVIEW TEXT
        ====================================================== */

        .review-text-cell {

            max-width: 320px;

            line-height: 1.5;
        }


        /* =====================================================
           DARK MODE
        ====================================================== */

        body.library-dark-mode {

            background: #0f172a !important;

            color: #e2e8f0;
        }


        body.library-dark-mode .admin-navbar {

            background: #111827;

            border-bottom-color: #334155;
        }


        body.library-dark-mode .navbar-title h5 {

            color: #f1f5f9;
        }


        body.library-dark-mode .navbar-title span {

            color: #94a3b8;
        }


        body.library-dark-mode .notification-btn,
        body.library-dark-mode .theme-toggle-btn {

            background: #1e293b;

            border-color: #475569;

            color: #cbd5e1;
        }


        body.library-dark-mode .notification-btn:hover,
        body.library-dark-mode .theme-toggle-btn:hover {

            background: #334155;

            color: #93c5fd;
        }


        body.library-dark-mode .header-divider {

            background: #334155;
        }


        body.library-dark-mode .nav-avatar {

            background: #1e3a8a;

            color: #93c5fd;
        }


        body.library-dark-mode .nav-admin-info strong {

            color: #f1f5f9;
        }


        body.library-dark-mode .nav-admin-info small {

            color: #94a3b8;
        }


        body.library-dark-mode .review-filter-card,
        body.library-dark-mode .dashboard-card {

            background: #111827;

            border-color: #334155;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.15);
        }


        body.library-dark-mode .review-filter-title h5 {

            color: #f1f5f9;
        }


        body.library-dark-mode .review-filter-title span {

            color: #94a3b8;
        }


        body.library-dark-mode .review-filter-title i {

            background: #1e293b;

            color: #a5b4fc;
        }


        body.library-dark-mode .review-filter-field label {

            color: #cbd5e1;
        }


        body.library-dark-mode .review-filter-input,
        body.library-dark-mode .review-filter-select {

            background: #1e293b;

            border-color: #475569;

            color: #e2e8f0;
        }


        body.library-dark-mode .review-filter-input::placeholder {

            color: #94a3b8;
        }


        body.library-dark-mode .review-reset-btn {

            background: #1e293b;

            border-color: #475569;

            color: #cbd5e1;
        }


        body.library-dark-mode .review-reset-btn:hover {

            background: #334155;

            color: #ffffff;
        }


        body.library-dark-mode .review-active-filters {

            border-top-color: #334155;
        }


        body.library-dark-mode .review-filter-label {

            color: #94a3b8;
        }


        body.library-dark-mode .review-filter-tag,
        body.library-dark-mode .review-result-count {

            background: #1e293b;

            border-color: #475569;

            color: #a5b4fc;
        }


        body.library-dark-mode .dashboard-card .card-header {

            background: #111827;

            border-bottom-color: #334155;
        }


        body.library-dark-mode .dashboard-card .card-header h5 {

            color: #f1f5f9;
        }


        body.library-dark-mode .dashboard-card .card-body {

            background: #111827;
        }


        body.library-dark-mode .admin-table th {

            background: #1e293b;

            color: #cbd5e1;

            border-color: #334155;
        }


        body.library-dark-mode .admin-table td {

            color: #cbd5e1;

            border-color: #334155;
        }


        body.library-dark-mode .admin-table tbody tr:hover {

            background: #1e293b;
        }


        body.library-dark-mode .admin-table strong {

            color: #f1f5f9;
        }


        body.library-dark-mode .admin-table .text-muted {

            color: #94a3b8 !important;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1200px) {

            .review-filter-grid {

                grid-template-columns:
                    minmax(0, 1fr)
                    170px
                    170px;
            }


            .review-search-btn,
            .review-reset-btn {

                width: 100%;
            }

        }


        @media (max-width: 992px) {

            .review-filter-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .nav-admin-info {

                display: none;
            }


            .admin-navbar {

                padding: 0 18px;
            }

        }


        @media (max-width: 768px) {

            .review-filter-card {

                padding: 16px;
            }


            .review-filter-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .review-filter-grid {

                grid-template-columns: 1fr;
            }


            .review-result-count {

                margin-top: 4px;
            }


            .review-text-cell {

                max-width: 220px;
            }

        }


        @media (max-width: 576px) {

            .review-filter-input,
            .review-filter-select {

                height: 41px;
            }


            .review-search-btn,
            .review-reset-btn {

                height: 41px;
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

    </style>

</head>


<body>


<!-- =====================================================
     ADMIN SIDEBAR
====================================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================== -->

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

                    Reviews

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <!-- NOTIFICATION -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
                aria-label="Notifications"
            >

                <i class="bi bi-bell"></i>

            </button>


            <!-- THEME TOGGLE -->

           
            </button>


            <div class="header-divider"></div>


            <!-- ADMIN -->

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

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </nav>


    <!-- =================================================
         PAGE CONTENT
    ================================================== -->

    <main class="dashboard-content">


        <!-- =================================================
             SEARCH + FILTER
        ================================================== -->

        <div class="review-filter-card">


            <div class="review-filter-header">


                <div class="review-filter-title">


                    <i class="bi bi-funnel"></i>


                    <div>


                        <h5>
                            Search & Filter Reviews
                        </h5>


                        <span>
                            Search reviews by book or user
                            and filter by rating.
                        </span>


                    </div>


                </div>


                <div class="review-result-count">


                    <i class="bi bi-chat-left-text"></i>


                    <?php echo $reviewCount; ?>


                    Reviews


                </div>


            </div>



            <form
                method="GET"
                action=""
            >


                <div class="review-filter-grid">


                    <!-- SEARCH -->

                    <div class="review-filter-field">


                        <label for="reviewSearch">
                            Search
                        </label>


                        <div class="review-search-wrapper">


                            <i class="bi bi-search"></i>


                            <input
                                type="text"
                                id="reviewSearch"
                                name="search"
                                class="review-filter-input"
                                placeholder="Book title, user name or email..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >


                        </div>


                    </div>


                    <!-- RATING -->

                    <div class="review-filter-field">


                        <label for="reviewRating">
                            Rating
                        </label>


                        <select
                            id="reviewRating"
                            name="rating"
                            class="review-filter-select"
                        >


                            <option
                                value=""
                                <?php
                                echo $rating === ''
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                All Ratings
                            </option>


                            <option
                                value="5"
                                <?php
                                echo $rating === '5'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ★★★★★ 5 Stars
                            </option>


                            <option
                                value="4"
                                <?php
                                echo $rating === '4'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ★★★★☆ 4 Stars
                            </option>


                            <option
                                value="3"
                                <?php
                                echo $rating === '3'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ★★★☆☆ 3 Stars
                            </option>


                            <option
                                value="2"
                                <?php
                                echo $rating === '2'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ★★☆☆☆ 2 Stars
                            </option>


                            <option
                                value="1"
                                <?php
                                echo $rating === '1'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                ★☆☆☆☆ 1 Star
                            </option>


                        </select>


                    </div>


                    <!-- SORT -->

                    <div class="review-filter-field">


                        <label for="reviewSort">
                            Sort By
                        </label>


                        <select
                            id="reviewSort"
                            name="sort"
                            class="review-filter-select"
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


                    <!-- SEARCH BUTTON -->

                    <div class="review-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <button
                            type="submit"
                            class="review-search-btn"
                        >

                            <i class="bi bi-search"></i>

                            Search

                        </button>


                    </div>


                    <!-- RESET -->

                    <div class="review-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <a
                            href="index.php"
                            class="review-reset-btn"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                            Reset

                        </a>


                    </div>


                </div>



                <!-- =================================================
                     ACTIVE FILTERS
                ================================================== -->

                <?php if (
                    $search !== '' ||
                    $rating !== '' ||
                    $sort !== 'newest'
                ) { ?>


                    <div class="review-active-filters">


                        <span class="review-filter-label">

                            Active Filters:

                        </span>


                        <?php if ($search !== '') { ?>


                            <span class="review-filter-tag">


                                <i class="bi bi-search"></i>


                                Search:

                                <?php

                                echo htmlspecialchars(
                                    $search
                                );

                                ?>


                            </span>


                        <?php } ?>


                        <?php if ($rating !== '') { ?>


                            <span class="review-filter-tag">


                                <i class="bi bi-star-fill"></i>


                                Rating:

                                <?php

                                echo (int)$rating;

                                ?>

                                Star<?php
                                echo $rating === '1'
                                    ? ''
                                    : 's';
                                ?>


                            </span>


                        <?php } ?>


                        <?php if ($sort === 'oldest') { ?>


                            <span class="review-filter-tag">


                                <i class="bi bi-sort-down"></i>


                                Oldest First


                            </span>


                        <?php } ?>


                    </div>


                <?php } ?>


            </form>


        </div>



        <!-- =================================================
             REVIEWS CARD
        ================================================== -->

        <div class="dashboard-card">


            <div class="card-header">


                <div
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:15px;
                        width:100%;
                    "
                >


                    <h5>
                        Book Ratings & Reviews
                    </h5>


                    <span
                        class="review-result-count"
                    >

                        Showing

                        <?php echo $reviewCount; ?>

                        of

                        <?php echo $totalReviews; ?>

                    </span>


                </div>


            </div>



            <div class="card-body">


                <div class="table-responsive">


                    <table class="admin-table">


                        <thead>


                            <tr>


                                <th>
                                    #
                                </th>


                                <th>
                                    Book
                                </th>


                                <th>
                                    User
                                </th>


                                <th>
                                    Rating
                                </th>


                                <th>
                                    Review
                                </th>


                                <th>
                                    Date
                                </th>


                                <th>
                                    Action
                                </th>


                            </tr>


                        </thead>



                        <tbody>


                        <?php if (
                            $reviews &&
                            $reviews->num_rows > 0
                        ): ?>


                            <?php

                            $count = 1;

                            ?>


                            <?php while (
                                $row = $reviews->fetch_assoc()
                            ): ?>


                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <?php

                                        echo $count++;

                                        ?>

                                    </td>


                                    <!-- BOOK -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $row['title']
                                            );

                                            ?>

                                        </strong>

                                    </td>


                                    <!-- USER -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['user_name']
                                        );

                                        ?>

                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $row['email']
                                            );

                                            ?>

                                        </small>

                                    </td>


                                    <!-- RATING -->

                                    <td>

                                        <span
                                            class="admin-review-stars"
                                            title="<?php echo (int)$row['rating']; ?> out of 5 stars"
                                        >

                                            <?php

                                            for (
                                                $i = 1;
                                                $i <= 5;
                                                $i++
                                            ) {

                                                if (
                                                    $i <=
                                                    (int)$row['rating']
                                                ) {

                                                    echo '
                                                        <i class="bi bi-star-fill"></i>
                                                    ';

                                                } else {

                                                    echo '
                                                        <i class="bi bi-star"></i>
                                                    ';

                                                }

                                            }

                                            ?>

                                        </span>

                                    </td>


                                    <!-- REVIEW -->

                                    <td>

                                        <div
                                            class="review-text-cell"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $row['review']
                                                ?: 'No review'
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?php

                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $row['created_at']
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="delete.php?id=<?php echo (int)$row['id']; ?>"
                                            class="
                                                btn
                                                btn-sm
                                                btn-outline-danger
                                            "
                                            title="Delete Review"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this review?'
                                                );
                                            "
                                        >

                                            <i
                                                class="bi bi-trash"
                                            ></i>

                                        </a>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>


                                <td
                                    colspan="7"
                                    class="text-center py-5"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-chat-square-text
                                            fs-1
                                            text-muted
                                        "
                                    ></i>


                                    <br><br>


                                    <strong>
                                        No Reviews Found
                                    </strong>


                                    <br>


                                    <small
                                        class="text-muted"
                                    >

                                        Try changing your search
                                        or filter criteria.

                                    </small>

                                </td>


                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </main>


</div>



<!-- =================================================
     BOOTSTRAP JS
================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- =================================================
     SIDEBAR TOGGLE
================================================== -->

<script>

const sidebarToggle =
    document.getElementById("sidebarToggle");


const adminSidebar =
    document.getElementById("adminSidebar");


if (
    sidebarToggle &&
    adminSidebar
) {

    sidebarToggle.addEventListener(
        "click",
        function () {

            adminSidebar.classList.toggle("show");

        }
    );


    document.addEventListener(
        "click",
        function (event) {

            if (

                window.innerWidth <= 992 &&

                adminSidebar.classList.contains("show") &&

                !adminSidebar.contains(event.target) &&

                !sidebarToggle.contains(event.target)

            ) {

                adminSidebar.classList.remove("show");

            }

        }
    );

}

</script>



<!-- =================================================
     GLOBAL THEME TOGGLE
================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const body = document.body;


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


        /* LOAD SAVED THEME */

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


</body>

</html>