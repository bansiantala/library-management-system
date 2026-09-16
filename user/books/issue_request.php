<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();


// =========================================================
// GET BOOK ID
// =========================================================

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php"
    );

    exit();
}

$user_id = $_SESSION['user_id'];


// =========================================================
// GET BOOK DETAILS
// =========================================================

$stmt = $conn->prepare("
    SELECT
        books.id,
        books.title,
        books.author,
        books.available_quantity,
        categories.category_name
    FROM books

    LEFT JOIN categories
        ON books.category_id = categories.id

    WHERE books.id = ?
");

if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id .
        "&request=error"
    );

    exit();
}

$stmt->bind_param(
    "i",
    $book_id
);

$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


// =========================================================
// CHECK BOOK EXISTS
// =========================================================

if (!$book) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php"
    );

    exit();
}


// =========================================================
// CHECK BOOK AVAILABILITY
// =========================================================

$available_quantity = (int)$book['available_quantity'];

if ($available_quantity <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id .
        "&request=unavailable"
    );

    exit();
}


// =========================================================
// CHECK EXISTING PENDING REQUEST
// =========================================================

$stmt = $conn->prepare("
    SELECT id
    FROM issue_requests
    WHERE user_id = ?
    AND book_id = ?
    AND status = 'Pending'
    LIMIT 1
");

if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id .
        "&request=error"
    );

    exit();
}

$stmt->bind_param(
    "ii",
    $user_id,
    $book_id
);

$stmt->execute();

$existing_result = $stmt->get_result();

$existing_request = $existing_result->fetch_assoc();

$stmt->close();


// =========================================================
// ALREADY REQUESTED
// =========================================================

if ($existing_request) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id .
        "&request=already"
    );

    exit();
}


// =========================================================
// BORROWING PERIOD
// =========================================================

$error = "";

$requested_days = 3;


// =========================================================
// HANDLE FORM SUBMISSION
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $requested_days = isset($_POST['requested_days'])
        ? intval($_POST['requested_days'])
        : 0;


    // =====================================================
    // VALIDATE BORROWING PERIOD
    // =====================================================

    if (!in_array(
        $requested_days,
        [3, 6, 10],
        true
    )) {

        $error = "Please select a valid borrowing period.";
    }


    // =====================================================
    // CHECK AVAILABILITY AGAIN
    // =====================================================

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT available_quantity
            FROM books
            WHERE id = ?
        ");

        if (!$stmt) {

            $error = "Unable to check book availability.";

        } else {

            $stmt->bind_param(
                "i",
                $book_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $latest_book = $result->fetch_assoc();

            $stmt->close();


            if (
                !$latest_book ||
                (int)$latest_book['available_quantity'] <= 0
            ) {

                $error = "This book is currently unavailable.";
            }
        }
    }


    // =====================================================
    // CHECK PENDING REQUEST AGAIN
    // =====================================================

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM issue_requests
            WHERE user_id = ?
            AND book_id = ?
            AND status = 'Pending'
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Unable to check existing request.";

        } else {

            $stmt->bind_param(
                "ii",
                $user_id,
                $book_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $pending_request = $result->fetch_assoc();

            $stmt->close();


            if ($pending_request) {

                $error = "You already have a pending request for this book.";
            }
        }
    }


    // =====================================================
    // INSERT ISSUE REQUEST
    // =====================================================

    if ($error === "") {

        /*
         * Important:
         *
         * due_date is NULL while the request is Pending.
         *
         * The final due date will be calculated by
         * admin/issue/approve_request.php using:
         *
         * actual issue/approval date + requested_days
         */

        $stmt = $conn->prepare("
            INSERT INTO issue_requests
            (
                user_id,
                book_id,
                request_date,
                status,
                requested_days,
                due_date
            )
            VALUES
            (
                ?,
                ?,
                NOW(),
                'Pending',
                ?,
                NULL
            )
        ");

        if (!$stmt) {

            $error = "Unable to create book request.";

        } else {

            $stmt->bind_param(
                "iii",
                $user_id,
                $book_id,
                $requested_days
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: " .
                    BASE_URL .
                    "/user/books/details.php?id=" .
                    $book_id .
                    "&request=success"
                );

                exit();

            } else {

                $stmt->close();

                $error = "Unable to submit book request.";
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

    <title>Request Book - Library Management System</title>

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

        body {
            background: #f7f8fa;
        }

        .request-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .request-card {
            width: 100%;
            max-width: 650px;
            background: #ffffff;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .request-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            background: #eaf3ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .request-title {
            text-align: center;
            font-size: 25px;
            font-weight: 700;
            color: #211a16;
            margin-bottom: 8px;
        }

        .request-subtitle {
            text-align: center;
            color: #77716d;
            margin-bottom: 28px;
        }

        .book-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 25px;
        }

        .book-title {
            font-size: 18px;
            font-weight: 700;
            color: #211a16;
            margin-bottom: 6px;
        }

        .book-author {
            color: #77716d;
            margin-bottom: 4px;
        }

        .book-category {
            color: #77716d;
            font-size: 14px;
        }

        .availability {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 6px 12px;
            border-radius: 20px;
            background: #eaf8ef;
            color: #198754;
            font-size: 13px;
            font-weight: 600;
        }

        .period-title {
            font-size: 16px;
            font-weight: 700;
            color: #211a16;
            margin-bottom: 14px;
        }

        .period-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .period-option {
            position: relative;
        }

        .period-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .period-label {
            display: block;
            text-align: center;
            padding: 18px 10px;
            border: 2px solid #e3e6ea;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .period-label:hover {
            border-color: #0d6efd;
            background: #f5f9ff;
        }

        .period-days {
            display: block;
            font-size: 22px;
            font-weight: 700;
            color: #211a16;
        }

        .period-text {
            display: block;
            margin-top: 3px;
            color: #77716d;
            font-size: 13px;
        }

        .period-option input:checked + .period-label {
            border-color: #0d6efd;
            background: #eaf3ff;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.08);
        }

        .period-option input:checked + .period-label .period-days {
            color: #0d6efd;
        }

        .info-box {
            background: #fff8e8;
            border: 1px solid #ffe5a3;
            color: #765b16;
            border-radius: 10px;
            padding: 13px 15px;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .btn-request {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 13px 18px;
            background: #0d6efd;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-request:hover {
            background: #0b5ed7;
            color: #ffffff;
        }

        .btn-cancel {
            width: 100%;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 600;
            margin-top: 10px;
        }

        .error-box {
            background: #fff0f0;
            border: 1px solid #f5c2c7;
            color: #842029;
            border-radius: 10px;
            padding: 13px 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 576px) {

            .request-card {
                padding: 25px 20px;
            }

            .period-options {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="request-wrapper">

    <div class="request-card">

        <!-- ICON -->

        <div class="request-icon">

            <i class="bi bi-book"></i>

        </div>


        <!-- TITLE -->

        <h1 class="request-title">
            Request Book
        </h1>

        <p class="request-subtitle">
            Select how long you would like to borrow this book.
        </p>


        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="error-box">

                <i class="bi bi-exclamation-circle me-2"></i>

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- BOOK INFORMATION -->

        <div class="book-info">

            <div class="book-title">

                <?php
                echo htmlspecialchars($book['title']);
                ?>

            </div>

            <div class="book-author">

                <i class="bi bi-person me-1"></i>

                <?php
                echo htmlspecialchars($book['author']);
                ?>

            </div>

            <div class="book-category">

                <i class="bi bi-tag me-1"></i>

                <?php
                echo htmlspecialchars(
                    $book['category_name'] ?? 'Uncategorized'
                );
                ?>

            </div>


            <div class="availability">

                <i class="bi bi-check-circle-fill"></i>

                <?php
                echo $available_quantity;
                ?>

                available

            </div>

        </div>


        <!-- REQUEST FORM -->

        <form
            method="POST"
            action=""
        >

            <!-- BORROWING PERIOD -->

            <div class="period-title">

                <i class="bi bi-calendar3 me-2"></i>

                Select Borrowing Period

            </div>


            <div class="period-options">


                <!-- 3 DAYS -->

                <div class="period-option">

                    <input
                        type="radio"
                        id="days3"
                        name="requested_days"
                        value="3"
                        <?php
                        echo ($requested_days === 3)
                            ? 'checked'
                            : '';
                        ?>
                    >

                    <label
                        for="days3"
                        class="period-label"
                    >

                        <span class="period-days">
                            3
                        </span>

                        <span class="period-text">
                            Days
                        </span>

                    </label>

                </div>


                <!-- 6 DAYS -->

                <div class="period-option">

                    <input
                        type="radio"
                        id="days6"
                        name="requested_days"
                        value="6"
                        <?php
                        echo ($requested_days === 6)
                            ? 'checked'
                            : '';
                        ?>
                    >

                    <label
                        for="days6"
                        class="period-label"
                    >

                        <span class="period-days">
                            6
                        </span>

                        <span class="period-text">
                            Days
                        </span>

                    </label>

                </div>


                <!-- 10 DAYS -->

                <div class="period-option">

                    <input
                        type="radio"
                        id="days10"
                        name="requested_days"
                        value="10"
                        <?php
                        echo ($requested_days === 10)
                            ? 'checked'
                            : '';
                        ?>
                    >

                    <label
                        for="days10"
                        class="period-label"
                    >

                        <span class="period-days">
                            10
                        </span>

                        <span class="period-text">
                            Days
                        </span>

                    </label>

                </div>

            </div>


            <!-- INFORMATION -->

            <div class="info-box">

                <i class="bi bi-info-circle me-2"></i>

                Your selected borrowing period will be sent to the
                administrator. The final due date will be calculated
                when your request is approved.

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn-request"
            >

                <i class="bi bi-send me-2"></i>

                Submit Book Request

            </button>


            <!-- CANCEL -->

            <a
                href="<?php echo BASE_URL; ?>/user/books/details.php?id=<?php echo $book_id; ?>"
                class="btn btn-outline-secondary btn-cancel"
            >

                <i class="bi bi-arrow-left me-2"></i>

                Cancel

            </a>

        </form>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>