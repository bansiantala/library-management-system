<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();


// =========================================================
// USER ID
// =========================================================

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user_login.php"
    );

    exit();
}


// =========================================================
// GET ISSUED BOOK ID
// =========================================================

$issued_book_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($issued_book_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}


// =========================================================
// FETCH ISSUED BOOK
// =========================================================

$stmt = $conn->prepare(
    "SELECT
        issued_books.id AS issued_book_id,
        issued_books.book_id,
        issued_books.user_id,
        issued_books.status,
        books.title,
        books.author
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     WHERE issued_books.id = ?
       AND issued_books.user_id = ?
     LIMIT 1"
);

if (!$stmt) {

    die("Database query preparation failed.");

}

$stmt->bind_param(
    "ii",
    $issued_book_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


// =========================================================
// BOOK NOT FOUND
// =========================================================

if (!$book) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}


// =========================================================
// ONLY RETURNED BOOK CAN BE REVIEWED
// =========================================================

if ($book['status'] !== 'Returned') {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}


// =========================================================
// CHECK EXISTING REVIEW
// =========================================================

$existingReview = null;

$stmt = $conn->prepare(
    "SELECT
        id,
        rating,
        review
     FROM book_reviews
     WHERE book_id = ?
       AND user_id = ?
     LIMIT 1"
);

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $book['book_id'],
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $existingReview = $result->fetch_assoc();

    $stmt->close();

}


// =========================================================
// MESSAGES
// =========================================================

$success = "";

$error = "";


// =========================================================
// SUBMIT REVIEW
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = isset($_POST['rating'])
        ? (int)$_POST['rating']
        : 0;

    $review = trim(
        $_POST['review'] ?? ''
    );


    // =====================================================
    // VALIDATE RATING
    // =====================================================

    if ($rating < 1 || $rating > 5) {

        $error =
            "Please select a rating between 1 and 5.";

    } else {


        // =================================================
        // UPDATE EXISTING REVIEW
        // =================================================

        if ($existingReview) {

            $stmt = $conn->prepare(
                "UPDATE book_reviews
                 SET rating = ?,
                     review = ?
                 WHERE id = ?
                   AND user_id = ?"
            );

            if (!$stmt) {

                $error =
                    "Database query preparation failed.";

            } else {

                $stmt->bind_param(
                    "isii",
                    $rating,
                    $review,
                    $existingReview['id'],
                    $user_id
                );

                if ($stmt->execute()) {

                    $success =
                        "Your review has been updated successfully.";

                    $existingReview['rating'] =
                        $rating;

                    $existingReview['review'] =
                        $review;

                } else {

                    $error =
                        "Unable to update your review.";

                }

                $stmt->close();

            }


        } else {


            // =================================================
            // INSERT NEW REVIEW
            // =================================================

            $stmt = $conn->prepare(
                "INSERT INTO book_reviews
                    (book_id, user_id, rating, review)
                 VALUES (?, ?, ?, ?)"
            );

            if (!$stmt) {

                $error =
                    "Database query preparation failed.";

            } else {

                $stmt->bind_param(
                    "iiis",
                    $book['book_id'],
                    $user_id,
                    $rating,
                    $review
                );

                if ($stmt->execute()) {

                    $success =
                        "Your review has been submitted successfully.";

                    $existingReview = [

                        'id' =>
                            $stmt->insert_id,

                        'rating' =>
                            $rating,

                        'review' =>
                            $review

                    ];

                } else {

                    $error =
                        "Unable to submit your review.";

                }

                $stmt->close();

            }

        }

    }

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
        Rate & Review
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


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <style>

        .review-page {

            max-width: 800px;

            margin: 40px auto;

        }


        .review-card {

            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 20px;

            padding: 35px;

            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.06);

        }


        .review-book-icon {

            width: 65px;

            height: 65px;

            border-radius: 16px;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

            margin-bottom: 18px;

        }


        .review-title {

            color: #172033;

            font-size: 27px;

            font-weight: 800;

            margin-bottom: 6px;

        }


        .review-author {

            color: #64748b;

            margin-bottom: 28px;

        }


        .review-label {

            display: block;

            color: #172033;

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 10px;

        }


        .star-rating {

            display: flex;

            flex-direction: row-reverse;

            justify-content: flex-end;

            gap: 5px;

            margin-bottom: 28px;

        }


        .star-rating input {

            display: none;

        }


        .star-rating label {

            color: #d1d5db;

            font-size: 40px;

            cursor: pointer;

            transition: 0.2s ease;

        }


        .star-rating label:hover,

        .star-rating label:hover ~ label,

        .star-rating input:checked ~ label {

            color: #f59e0b;

        }


        .review-textarea {

            min-height: 150px;

            border-radius: 12px;

            resize: vertical;

            border: 1px solid #dbe2ea;

        }


        .review-textarea:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 0.15rem
                rgba(37, 99, 235, 0.10);

        }


        .review-buttons {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

            margin-top: 20px;

        }


        .submit-review-btn {

            border: none;

            background: #2563eb;

            color: #ffffff;

            border-radius: 10px;

            padding: 11px 20px;

            font-weight: 700;

        }


        .submit-review-btn:hover {

            background: #1d4ed8;

            color: #ffffff;

        }


        .back-review-btn {

            text-decoration: none;

            background: #f1f5f9;

            color: #334155;

            border-radius: 10px;

            padding: 11px 20px;

            font-weight: 700;

        }


        .back-review-btn:hover {

            background: #e2e8f0;

            color: #1e293b;

        }


        @media (max-width: 576px) {

            .review-card {

                padding: 25px 20px;

            }


            .review-title {

                font-size: 23px;

            }


            .star-rating label {

                font-size: 34px;

            }


            .review-buttons {

                flex-direction: column;

            }


            .submit-review-btn,

            .back-review-btn {

                width: 100%;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<?php require_once "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- NAVBAR -->

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
                Rate & Review
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


    <!-- CONTENT -->

    <div class="dashboard-content">


        <div class="review-page">


            <!-- SUCCESS MESSAGE -->

            <?php if (!empty($success)): ?>

                <div class="alert alert-success">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    <?php

                    echo htmlspecialchars(
                        $success
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if (!empty($error)): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <?php

                    echo htmlspecialchars(
                        $error
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- REVIEW CARD -->

            <div class="review-card">


                <div class="review-book-icon">

                    <i class="bi bi-book-half"></i>

                </div>


                <h1 class="review-title">

                    <?php

                    echo htmlspecialchars(
                        $book['title']
                    );

                    ?>

                </h1>


                <div class="review-author">

                    <i class="bi bi-person-fill me-1"></i>

                    <?php

                    echo htmlspecialchars(
                        $book['author']
                    );

                    ?>

                </div>


                <form
                    method="POST"
                    action=""
                >


                    <!-- RATING -->

                    <label class="review-label">

                        Your Rating

                    </label>


                    <div class="star-rating">


                        <input
                            type="radio"
                            name="rating"
                            id="star5"
                            value="5"

                            <?php

                            if (
                                isset(
                                    $existingReview['rating']
                                ) &&
                                (int)$existingReview['rating'] === 5
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        <label
                            for="star5"
                            title="5 Stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            name="rating"
                            id="star4"
                            value="4"

                            <?php

                            if (
                                isset(
                                    $existingReview['rating']
                                ) &&
                                (int)$existingReview['rating'] === 4
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        <label
                            for="star4"
                            title="4 Stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            name="rating"
                            id="star3"
                            value="3"

                            <?php

                            if (
                                isset(
                                    $existingReview['rating']
                                ) &&
                                (int)$existingReview['rating'] === 3
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        <label
                            for="star3"
                            title="3 Stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            name="rating"
                            id="star2"
                            value="2"

                            <?php

                            if (
                                isset(
                                    $existingReview['rating']
                                ) &&
                                (int)$existingReview['rating'] === 2
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        <label
                            for="star2"
                            title="2 Stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            name="rating"
                            id="star1"
                            value="1"

                            <?php

                            if (
                                isset(
                                    $existingReview['rating']
                                ) &&
                                (int)$existingReview['rating'] === 1
                            ) {

                                echo "checked";

                            }

                            ?>
                        >

                        <label
                            for="star1"
                            title="1 Star"
                        >
                            ★
                        </label>


                    </div>


                    <!-- REVIEW -->

                    <label
                        for="review"
                        class="review-label"
                    >

                        Your Review

                    </label>


                    <textarea
                        name="review"
                        id="review"
                        class="form-control review-textarea"
                        placeholder="Write your experience about this book..."
                    ><?php

                    echo htmlspecialchars(
                        $existingReview['review'] ?? ''
                    );

                    ?></textarea>


                    <!-- BUTTONS -->

                    <div class="review-buttons">


                        <button
                            type="submit"
                            class="submit-review-btn"
                        >

                            <i class="bi bi-send me-1"></i>

                            <?php

                            echo $existingReview
                                ? "Update Review"
                                : "Submit Review";

                            ?>

                        </button>


                        <a
                            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                            class="back-review-btn"
                        >

                            <i class="bi bi-arrow-left me-1"></i>

                            Back to My Books

                        </a>


                    </div>


                </form>


            </div>


        </div>


    </div>


</div>


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