<?php

require_once "../../config/database.php";
require_once "../../config/auth.php";

requireAdmin();


/* =========================================================
   SEARCH + FILTER
========================================================= */

$search = trim($_GET['search'] ?? '');

$rating = $_GET['rating'] ?? '';

$sort = $_GET['sort'] ?? 'newest';


/* =========================================================
   VALIDATE RATING
========================================================= */

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


/* =========================================================
   VALIDATE SORT
========================================================= */

$allowedSorts = [
    'newest',
    'oldest'
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}


/* =========================================================
   SORT
========================================================= */

if ($sort === 'oldest') {

    $orderBy = "book_reviews.id ASC";

} else {

    $orderBy = "book_reviews.id DESC";

}


/* =========================================================
   GET FILTERED REVIEWS
========================================================= */

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


/* =========================================================
   SEARCH
========================================================= */

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


/* =========================================================
   RATING FILTER
========================================================= */

if ($rating !== '') {

    $sql .= "
        AND book_reviews.rating = ?
    ";

    $ratingValue = (int)$rating;

    $params[] = $ratingValue;

    $types .= "i";
}


/* =========================================================
   ORDER
========================================================= */

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


/* =========================================================
   RESULT COUNT
========================================================= */

$reviewCount = 0;

if ($reviews) {

    $reviewCount = $reviews->num_rows;

}


/* =========================================================
   TOTAL REVIEWS
========================================================= */

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


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >


    <style>

        /* =====================================================
           REVIEW FILTER CARD
        ===================================================== */

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
        ===================================================== */

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
                0 0 0 3px rgba(86, 100, 210, 0.10);

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

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
        ===================================================== */

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
        ===================================================== */

        .admin-review-stars {

            white-space: nowrap;

        }


        .admin-review-stars i {

            font-size: 14px;

            margin-right: 1px;

        }


        /* =====================================================
           REVIEW TEXT
        ===================================================== */

        .review-text-cell {

            max-width: 320px;

            line-height: 1.5;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

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

        }

    </style>

</head>


<body>


<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================= -->

    <nav class="admin-navbar">


        <div class="navbar-left">


            <div class="navbar-title">


                <h5>

                    Book Reviews

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



            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
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
    ================================================= -->

    <main class="dashboard-content">


        <!-- =================================================
             SEARCH + FILTER
        ================================================= -->

        <div class="review-filter-card">


            <div class="review-filter-header">


                <div class="review-filter-title">


                    <i class="bi bi-funnel"></i>


                    <div>


                        <h5>

                            Search & Filter Reviews

                        </h5>


                        <span>

                            Search reviews by book or user and filter by rating.

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


                    <!-- Search -->

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



                    <!-- Rating -->

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
                                <?php echo $rating === '' ? 'selected' : ''; ?>
                            >

                                All Ratings

                            </option>


                            <option
                                value="5"
                                <?php echo $rating === '5' ? 'selected' : ''; ?>
                            >

                                ★★★★★ 5 Stars

                            </option>


                            <option
                                value="4"
                                <?php echo $rating === '4' ? 'selected' : ''; ?>
                            >

                                ★★★★☆ 4 Stars

                            </option>


                            <option
                                value="3"
                                <?php echo $rating === '3' ? 'selected' : ''; ?>
                            >

                                ★★★☆☆ 3 Stars

                            </option>


                            <option
                                value="2"
                                <?php echo $rating === '2' ? 'selected' : ''; ?>
                            >

                                ★★☆☆☆ 2 Stars

                            </option>


                            <option
                                value="1"
                                <?php echo $rating === '1' ? 'selected' : ''; ?>
                            >

                                ★☆☆☆☆ 1 Star

                            </option>


                        </select>


                    </div>



                    <!-- Sort -->

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
                                <?php echo $sort === 'newest' ? 'selected' : ''; ?>
                            >

                                Newest First

                            </option>


                            <option
                                value="oldest"
                                <?php echo $sort === 'oldest' ? 'selected' : ''; ?>
                            >

                                Oldest First

                            </option>


                        </select>


                    </div>



                    <!-- Search Button -->

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



                    <!-- Reset -->

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

                                Star<?php echo $rating === '1' ? '' : 's'; ?>


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
        ================================================= -->

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


                                    <!-- Number -->

                                    <td>


                                        <?php

                                        echo $count++;

                                        ?>


                                    </td>



                                    <!-- Book -->

                                    <td>


                                        <strong>


                                            <?php

                                            echo htmlspecialchars(
                                                $row['title']
                                            );

                                            ?>


                                        </strong>


                                    </td>



                                    <!-- User -->

                                    <td>


                                        <?php

                                        echo htmlspecialchars(
                                            $row['user_name']
                                        );

                                        ?>


                                        <br>


                                        <small class="text-muted">


                                            <?php

                                            echo htmlspecialchars(
                                                $row['email']
                                            );

                                            ?>


                                        </small>


                                    </td>



                                    <!-- Rating -->

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



                                    <!-- Review -->

                                    <td>


                                        <div class="review-text-cell">


                                            <?php

                                            echo htmlspecialchars(
                                                $row['review']
                                                ?: 'No review'
                                            );

                                            ?>


                                        </div>


                                    </td>



                                    <!-- Date -->

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



                                    <!-- Action -->

                                    <td>


                                        <a
                                            href="delete.php?id=<?php echo (int)$row['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete Review"
                                            onclick="return confirm('Are you sure you want to delete this review?');"
                                        >


                                            <i class="bi bi-trash"></i>


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
                                        class="bi bi-chat-square-text fs-1 text-muted"
                                    ></i>


                                    <br><br>


                                    <strong>

                                        No Reviews Found

                                    </strong>


                                    <br>


                                    <small class="text-muted">

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



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Sidebar Toggle -->

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


</body>

</html>