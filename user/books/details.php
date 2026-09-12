<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();


// =========================
// GET BOOK ID
// =========================

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    header("Location: " . BASE_URL . "/user/books/index.php");
    exit();
}


// =========================
// FETCH BOOK
// =========================

$stmt = $conn->prepare(
    "SELECT
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
     WHERE books.id = ?"
);

if (!$stmt) {
    die("Database query preparation failed.");
}

$stmt->bind_param("i", $book_id);

$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


// =========================
// BOOK NOT FOUND
// =========================

if (!$book) {
    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php"
    );
    exit();
}


// =========================
// AVAILABILITY
// =========================

$available = (int)$book['available_quantity'];

$total = (int)$book['quantity'];

$is_available = $available > 0;


// =========================
// DATE
// =========================

$added_date = date(
    "d M Y",
    strtotime($book['created_at'])
);


// =========================================================
// STEP 8.5
// BOOK RATING
// =========================================================

$averageRating = 0;

$totalReviews = 0;


$ratingStmt = $conn->prepare(
    "SELECT
        COALESCE(AVG(rating), 0) AS average_rating,
        COUNT(*) AS total_reviews
     FROM book_reviews
     WHERE book_id = ?"
);


if ($ratingStmt) {

    $ratingStmt->bind_param(
        "i",
        $book_id
    );

    $ratingStmt->execute();

    $ratingResult = $ratingStmt->get_result();

    $ratingData = $ratingResult->fetch_assoc();


    if ($ratingData) {

        $averageRating = round(
            (float)$ratingData['average_rating'],
            1
        );

        $totalReviews = (int)$ratingData['total_reviews'];

    }

    $ratingStmt->close();
}


// =========================================================
// STEP 8.6
// FETCH BOOK REVIEWS
// =========================================================

$reviews = null;


$reviewStmt = $conn->prepare(
    "SELECT
        book_reviews.id,
        book_reviews.rating,
        book_reviews.review,
        book_reviews.created_at,
        users.name AS user_name
     FROM book_reviews
     INNER JOIN users
        ON book_reviews.user_id = users.id
     WHERE book_reviews.book_id = ?
     ORDER BY book_reviews.id DESC"
);


if ($reviewStmt) {

    $reviewStmt->bind_param(
        "i",
        $book_id
    );

    $reviewStmt->execute();

    $reviews = $reviewStmt->get_result();

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
        <?php echo htmlspecialchars($book['title']); ?>
        - Book Details
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


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <style>


        /* =====================================
           MAIN BOOK DETAILS WRAPPER
        ===================================== */

        .book-details-wrapper {

            width: 100%;

            max-width: 1100px;

            margin: 0 auto;

        }


        /* =====================================
           BOOK DETAILS CARD
        ===================================== */

        .book-details-card {

            width: 100%;

            background: #ffffff;

            border-radius: 18px;

            border: 1px solid #e8edf3;

            overflow: hidden;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.06);

        }


        /* =====================================
           LEFT IMAGE SECTION
        ===================================== */

        .book-cover-section {

            width: 100%;

            min-height: 500px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff 0%,
                    #dbeafe 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px;

        }


        /* =====================================
           IMAGE / BOOK BOX
        ===================================== */

        .book-cover {

            width: 240px;

            height: 330px;

            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    #2563eb 0%,
                    #1e3a8a 100%
                );

            color: #ffffff;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 25px;

            position: relative;

            overflow: hidden;

            box-shadow:
                0 18px 35px
                rgba(30, 64, 175, 0.28);

            margin: 0 auto;

        }


        /* Decorative Circle */

        .book-cover::before {

            content: "";

            position: absolute;

            width: 190px;

            height: 190px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, 0.08);

            top: -70px;

            right: -70px;

        }


        .book-cover::after {

            content: "";

            position: absolute;

            width: 130px;

            height: 130px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, 0.05);

            bottom: -50px;

            left: -40px;

        }


        /* Book Icon */

        .book-cover i {

            position: relative;

            z-index: 2;

            font-size: 70px;

            margin-bottom: 20px;

        }


        /* Book Title */

        .book-cover h4 {

            position: relative;

            z-index: 2;

            font-size: 20px;

            line-height: 1.4;

            font-weight: 700;

            margin: 0;

            max-width: 190px;

        }


        .book-cover small {

            position: relative;

            z-index: 2;

            margin-top: 12px;

            color: #dbeafe;

            font-size: 13px;

        }


        /* =====================================
           RIGHT INFORMATION SECTION
        ===================================== */

        .book-info-section {

            padding: 40px;

        }


        /* Category */

        .book-category {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            background: #eff6ff;

            color: #2563eb;

            padding: 7px 14px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 15px;

        }


        /* Title */

        .book-title {

            font-size: 30px;

            font-weight: 700;

            color: #1e293b;

            margin-bottom: 8px;

            line-height: 1.3;

        }


        /* Author */

        .book-author {

            color: #64748b;

            font-size: 15px;

            margin-bottom: 12px;

        }


        .book-author i {

            color: #2563eb;

        }


        /* =====================================
           STEP 8.5
           RATING SUMMARY
        ===================================== */

        .book-rating-summary {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 25px;

        }


        .rating-stars {

            color: #f59e0b;

            font-size: 18px;

            letter-spacing: 2px;

            display: inline-flex;

            gap: 2px;

        }


        .rating-score {

            color: #1e293b;

            font-size: 15px;

            font-weight: 800;

        }


        .rating-count {

            color: #64748b;

            font-size: 13px;

        }


        .rating-empty {

            color: #94a3b8;

            font-size: 13px;

        }


        /* =====================================
           INFORMATION GRID
        ===================================== */

        .book-info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px;

            margin-bottom: 25px;

        }


        .book-info-item {

            background: #f8fafc;

            border: 1px solid #e8edf3;

            border-radius: 11px;

            padding: 15px;

        }


        .book-info-item .label {

            color: #64748b;

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 5px;

        }


        .book-info-item .value {

            color: #1e293b;

            font-size: 15px;

            font-weight: 700;

        }


        /* =====================================
           AVAILABILITY
        ===================================== */

        .availability-box {

            border-radius: 12px;

            padding: 17px;

            margin-bottom: 25px;

            display: flex;

            align-items: center;

            gap: 14px;

        }


        .availability-box.available {

            background: #f0fdf4;

            border: 1px solid #bbf7d0;

        }


        .availability-box.unavailable {

            background: #fef2f2;

            border: 1px solid #fecaca;

        }


        .availability-icon {

            width: 45px;

            height: 45px;

            min-width: 45px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .availability-box.available
        .availability-icon {

            background: #dcfce7;

            color: #16a34a;

        }


        .availability-box.unavailable
        .availability-icon {

            background: #fee2e2;

            color: #dc2626;

        }


        .availability-box h6 {

            margin: 0 0 3px;

            font-weight: 700;

        }


        .availability-box p {

            margin: 0;

            font-size: 13px;

            color: #64748b;

        }


        .availability-box.available h6 {

            color: #15803d;

        }


        .availability-box.unavailable h6 {

            color: #dc2626;

        }


        /* =====================================
           BUTTONS
        ===================================== */

        .book-actions {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .btn-back {

            background: #f1f5f9;

            color: #334155;

            border: none;

            padding: 11px 20px;

            border-radius: 9px;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 7px;

        }


        .btn-back:hover {

            background: #e2e8f0;

            color: #1e293b;

        }


        .btn-search {

            background: #2563eb;

            color: #ffffff;

            border: none;

            padding: 11px 20px;

            border-radius: 9px;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 7px;

        }


        .btn-search:hover {

            background: #1d4ed8;

            color: #ffffff;

        }


        /* =====================================
           STEP 8.6
           BOOK REVIEWS SECTION
        ===================================== */

        .book-reviews-section {

            width: 100%;

            max-width: 1100px;

            margin: 25px auto 40px;

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 18px;

            padding: 28px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.04);

        }


        .reviews-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 20px;

        }


        .reviews-heading h4 {

            margin: 0;

            color: #1e293b;

            font-size: 21px;

            font-weight: 800;

        }


        .reviews-heading span {

            background: #eff6ff;

            color: #2563eb;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        .review-item {

            padding: 20px 0;

            border-bottom: 1px solid #edf0f4;

        }


        .review-item:first-of-type {

            padding-top: 5px;

        }


        .review-item:last-child {

            border-bottom: none;

            padding-bottom: 0;

        }


        .review-user {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .review-user-avatar {

            width: 44px;

            height: 44px;

            min-width: 44px;

            border-radius: 50%;

            background:
                #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;

            font-weight: 800;

        }


        .review-user-info strong {

            display: block;

            color: #1e293b;

            font-size: 14px;

            margin-bottom: 3px;

        }


        .review-stars {

            display: flex;

            gap: 2px;

            color: #f59e0b;

            font-size: 13px;

        }


        .review-stars .empty {

            color: #d1d5db;

        }


        .review-text {

            margin: 13px 0 8px;

            color: #475569;

            font-size: 14px;

            line-height: 1.7;

            white-space: normal;

        }


        .review-text.no-text {

            color: #94a3b8;

            font-style: italic;

        }


        .review-date {

            color: #94a3b8;

            font-size: 12px;

        }


        .no-reviews {

            text-align: center;

            padding: 35px 20px;

            color: #94a3b8;

        }


        .no-reviews i {

            display: block;

            font-size: 35px;

            margin-bottom: 10px;

        }


        .no-reviews strong {

            display: block;

            color: #64748b;

            margin-bottom: 4px;

        }


        .no-reviews span {

            font-size: 13px;

        }


        /* =====================================
           TABLET
        ===================================== */

        @media (max-width: 991px) {

            .book-cover-section {

                min-height: 430px;

            }

            .book-cover {

                width: 220px;

                height: 300px;

            }

            .book-info-section {

                padding: 30px;

            }

        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 767px) {

            .book-details-wrapper {

                max-width: 100%;

            }

            .book-cover-section {

                min-height: 360px;

                padding: 30px 20px;

            }

            .book-cover {

                width: 190px;

                height: 260px;

            }

            .book-cover i {

                font-size: 55px;

            }

            .book-cover h4 {

                font-size: 17px;

            }

            .book-info-section {

                padding: 25px 20px;

            }

            .book-title {

                font-size: 25px;

            }

            .book-reviews-section {

                padding: 20px;

                border-radius: 14px;

            }

        }


        /* =====================================
           SMALL MOBILE
        ===================================== */

        @media (max-width: 576px) {

            .book-info-grid {

                grid-template-columns: 1fr;

            }

            .book-cover-section {

                min-height: 320px;

                padding: 25px;

            }

            .book-cover {

                width: 170px;

                height: 230px;

            }

            .book-cover i {

                font-size: 48px;

                margin-bottom: 14px;

            }

            .book-cover h4 {

                font-size: 15px;

            }

            .book-cover small {

                font-size: 11px;

            }

            .book-actions {

                flex-direction: column;

            }

            .btn-back,
            .btn-search {

                justify-content: center;

            }

            .book-rating-summary {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;

            }

            .reviews-heading {

                align-items: flex-start;

                flex-direction: column;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     USER SIDEBAR
========================== -->

<?php require_once "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =========================
         NAVBAR
    ========================== -->

    <div class="user-navbar">


        <div class="d-flex align-items-center gap-3">


            <button
                class="sidebar-toggle"
                onclick="toggleSidebar()"
                type="button"
            >

                <i class="bi bi-list"></i>

            </button>


            <h5>
                Book Details
            </h5>


        </div>


        <div class="user-info">


            <div class="user-info-icon">

                <i class="bi bi-person"></i>

            </div>


            <span>

                <?php

                echo htmlspecialchars(
                    $_SESSION['user_name'] ?? 'User'
                );

                ?>

            </span>


        </div>


    </div>



    <!-- =========================
         PAGE CONTENT
    ========================== -->

    <div class="dashboard-content">


        <!-- =====================================
             ISSUE REQUEST MESSAGE
        ====================================== -->

        <?php if (isset($_GET['request'])): ?>


            <?php if ($_GET['request'] === 'success'): ?>


                <div class="alert alert-success issue-message">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    Your book issue request has been
                    sent to the Admin successfully.

                </div>


            <?php elseif ($_GET['request'] === 'already'): ?>


                <div class="alert alert-warning issue-message">

                    <i class="bi bi-exclamation-circle-fill me-2"></i>

                    You have already requested this book.

                </div>


            <?php elseif ($_GET['request'] === 'unavailable'): ?>


                <div class="alert alert-danger issue-message">

                    <i class="bi bi-x-circle-fill me-2"></i>

                    This book is currently unavailable.

                </div>


            <?php elseif ($_GET['request'] === 'error'): ?>


                <div class="alert alert-danger issue-message">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    Something went wrong. Please try again.

                </div>


            <?php endif; ?>


        <?php endif; ?>


        <!-- =====================================
             BOOK DETAILS
        ====================================== -->

        <div class="book-details-wrapper">


            <div class="book-details-card">


                <div class="row g-0">


                    <!-- =========================
                         CENTERED BOOK IMAGE BOX
                    ========================== -->

                    <div class="col-lg-5">


                        <div class="book-cover-section">


                            <div class="book-cover">


                                <i class="bi bi-book-half"></i>


                                <h4>

                                    <?php

                                    echo htmlspecialchars(
                                        $book['title']
                                    );

                                    ?>

                                </h4>


                                <small>
                                    Library Book
                                </small>


                            </div>


                        </div>


                    </div>



                    <!-- =========================
                         BOOK INFORMATION
                    ========================== -->

                    <div class="col-lg-7">


                        <div class="book-info-section">


                            <!-- CATEGORY -->

                            <div class="book-category">

                                <i class="bi bi-tag"></i>

                                <?php

                                echo htmlspecialchars(
                                    $book['category_name']
                                    ?? 'Uncategorized'
                                );

                                ?>

                            </div>



                            <!-- TITLE -->

                            <h1 class="book-title">

                                <?php

                                echo htmlspecialchars(
                                    $book['title']
                                );

                                ?>

                            </h1>



                            <!-- AUTHOR -->

                            <div class="book-author">

                                <i
                                    class="bi bi-person-fill me-1"
                                ></i>

                                Written by

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $book['author']
                                    );

                                    ?>

                                </strong>

                            </div>



                            <!-- =================================
                                 STEP 8.5
                                 RATING SUMMARY
                            ================================== -->

                            <div class="book-rating-summary">


                                <?php if ($totalReviews > 0): ?>


                                    <span class="rating-stars">


                                        <?php

                                        $fullStars =
                                            floor($averageRating);

                                        $hasHalfStar =
                                            (
                                                $averageRating -
                                                $fullStars
                                            ) >= 0.5;


                                        for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ) {

                                            if (
                                                $i <= $fullStars
                                            ) {

                                                echo
                                                '<i class="bi bi-star-fill"></i>';

                                            } elseif (
                                                $i ===
                                                $fullStars + 1 &&
                                                $hasHalfStar
                                            ) {

                                                echo
                                                '<i class="bi bi-star-half"></i>';

                                            } else {

                                                echo
                                                '<i class="bi bi-star"></i>';

                                            }

                                        }

                                        ?>

                                    </span>


                                    <span class="rating-score">

                                        <?php

                                        echo number_format(
                                            $averageRating,
                                            1
                                        );

                                        ?>

                                    </span>


                                    <span class="rating-count">

                                        (
                                        <?php echo $totalReviews; ?>

                                        <?php

                                        echo
                                            $totalReviews === 1
                                            ? 'review'
                                            : 'reviews';

                                        ?>

                                        )

                                    </span>


                                <?php else: ?>


                                    <span class="rating-stars">

                                        <i class="bi bi-star"></i>

                                        <i class="bi bi-star"></i>

                                        <i class="bi bi-star"></i>

                                        <i class="bi bi-star"></i>

                                        <i class="bi bi-star"></i>

                                    </span>


                                    <span class="rating-empty">

                                        No reviews yet

                                    </span>


                                <?php endif; ?>


                            </div>



                            <!-- INFORMATION GRID -->

                            <div class="book-info-grid">


                                <!-- ISBN -->

                                <div class="book-info-item">

                                    <div class="label">
                                        ISBN
                                    </div>

                                    <div class="value">

                                        <?php

                                        echo htmlspecialchars(
                                            $book['isbn']
                                            ?: 'Not Available'
                                        );

                                        ?>

                                    </div>

                                </div>



                                <!-- TOTAL COPIES -->

                                <div class="book-info-item">

                                    <div class="label">
                                        Total Copies
                                    </div>

                                    <div class="value">

                                        <?php
                                        echo $total;
                                        ?>

                                    </div>

                                </div>



                                <!-- AVAILABLE COPIES -->

                                <div class="book-info-item">

                                    <div class="label">
                                        Available Copies
                                    </div>

                                    <div class="value">

                                        <?php
                                        echo $available;
                                        ?>

                                    </div>

                                </div>



                                <!-- ADDED DATE -->

                                <div class="book-info-item">

                                    <div class="label">
                                        Added On
                                    </div>

                                    <div class="value">

                                        <?php
                                        echo $added_date;
                                        ?>

                                    </div>

                                </div>


                            </div>



                            <!-- =========================
                                 AVAILABILITY
                            ========================== -->

                            <?php if ($is_available): ?>


                                <div
                                    class="availability-box available"
                                >


                                    <div
                                        class="availability-icon"
                                    >

                                        <i
                                            class="bi bi-check-circle-fill"
                                        ></i>

                                    </div>


                                    <div>

                                        <h6>
                                            Available
                                        </h6>


                                        <p>

                                            <?php
                                            echo $available;
                                            ?>

                                            copy/copies currently
                                            available in the library.

                                        </p>

                                    </div>


                                </div>


                            <?php else: ?>


                                <div
                                    class="availability-box unavailable"
                                >


                                    <div
                                        class="availability-icon"
                                    >

                                        <i
                                            class="bi bi-x-circle-fill"
                                        ></i>

                                    </div>


                                    <div>

                                        <h6>
                                            Out of Stock
                                        </h6>


                                        <p>

                                            This book is currently
                                            unavailable.

                                        </p>

                                    </div>


                                </div>


                            <?php endif; ?>



                            <!-- =========================
                                 ACTION BUTTONS
                            ========================== -->

                            <div class="book-actions">


                                <a
                                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                                    class="btn-back"
                                >

                                    <i class="bi bi-arrow-left"></i>

                                    Back to Books

                                </a>


                                <?php if ($is_available): ?>


                                    <a
                                        href="<?php echo BASE_URL; ?>/user/books/issue_request.php?id=<?php echo $book_id; ?>"
                                        class="btn-search"
                                    >

                                        <i
                                            class="bi bi-journal-plus"
                                        ></i>

                                        Issue Book

                                    </a>


                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>



        <!-- =====================================
             STEP 8.6
             BOOK REVIEWS
        ====================================== -->

        <div class="book-reviews-section">


            <div class="reviews-heading">


                <h4>

                    <i
                        class="bi bi-chat-square-text me-2"
                    ></i>

                    Book Reviews

                </h4>


                <span>

                    <?php echo $totalReviews; ?>

                    <?php

                    echo
                        $totalReviews === 1
                        ? 'Review'
                        : 'Reviews';

                    ?>

                </span>


            </div>



            <?php if ($reviews && $reviews->num_rows > 0): ?>


                <?php while ($review = $reviews->fetch_assoc()): ?>


                    <div class="review-item">


                        <!-- USER -->

                        <div class="review-user">


                            <div class="review-user-avatar">


                                <?php

                                $reviewUserName =
                                    $review['user_name']
                                    ?? 'User';


                                echo strtoupper(
                                    substr(
                                        trim($reviewUserName),
                                        0,
                                        1
                                    )
                                );

                                ?>


                            </div>


                            <div class="review-user-info">


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $reviewUserName
                                    );

                                    ?>

                                </strong>


                                <!-- STARS -->

                                <div class="review-stars">


                                    <?php

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ) {

                                        if (
                                            $i <=
                                            (int)$review['rating']
                                        ) {

                                            echo
                                            '<i class="bi bi-star-fill"></i>';

                                        } else {

                                            echo
                                            '<i class="bi bi-star empty"></i>';

                                        }

                                    }

                                    ?>

                                </div>


                            </div>


                        </div>



                        <!-- REVIEW TEXT -->

                        <?php if (
                            !empty(
                                trim(
                                    $review['review'] ?? ''
                                )
                            )
                        ): ?>


                            <div class="review-text">

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $review['review']
                                    )
                                );

                                ?>

                            </div>


                        <?php else: ?>


                            <div
                                class="review-text no-text"
                            >

                                No written review provided.

                            </div>


                        <?php endif; ?>


                        <!-- DATE -->

                        <div class="review-date">

                            <i
                                class="bi bi-calendar3 me-1"
                            ></i>

                            <?php

                            echo date(
                                "d M Y",
                                strtotime(
                                    $review['created_at']
                                )
                            );

                            ?>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="no-reviews">


                    <i class="bi bi-chat-square-text"></i>


                    <strong>
                        No reviews yet
                    </strong>


                    <span>

                        Be the first user to rate and
                        review this book.

                    </span>


                </div>


            <?php endif; ?>


        </div>


    </div>


</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- SIDEBAR -->

<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector(".user-sidebar");

    if (sidebar) {

        sidebar.classList.toggle("show");

    }

}

</script>


</body>

</html>