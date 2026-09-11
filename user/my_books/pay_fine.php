<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

$book_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($book_id <= 0) {
    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );
    exit();
}


/* =========================================
   GET BOOK
========================================= */

$stmt = $conn->prepare(
    "SELECT
        issued_books.*,
        books.title,
        books.author
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     WHERE issued_books.id = ?
       AND issued_books.user_id = ?
       AND issued_books.status = 'Issued'
     LIMIT 1"
);

$stmt->bind_param(
    "ii",
    $book_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}

$book = $result->fetch_assoc();

$stmt->close();


/* =========================================
   CALCULATE FINE
========================================= */

$today = new DateTime();

$return_date = new DateTime(
    $book['return_date']
);

if ($today <= $return_date) {

    $fine = 0;

} else {

    $difference = $today->diff(
        $return_date
    );

    $overdue_days = $difference->days;

    $fine = $overdue_days * 10;
}


/* =========================================
   ALREADY PAID
========================================= */

if ($book['payment_status'] === 'Paid') {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
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
        Pay Fine | Library Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    >

    <style>

        body {

            min-height: 100vh;

            margin: 0;

            background:
                linear-gradient(
                    135deg,
                    #eef4ff,
                    #f7f9fc
                );

            font-family:
                Arial,
                sans-serif;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }

        .payment-card {

            width: 100%;

            max-width: 480px;

            background: #ffffff;

            border-radius: 20px;

            padding: 35px;

            box-shadow:
                0 20px 50px
                rgba(15,23,42,.10);
        }

        .payment-icon {

            width: 65px;
            height: 65px;

            margin: 0 auto 18px;

            border-radius: 18px;

            background: #fff7ed;

            color: #ea580c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }

        .payment-card h2 {

            text-align: center;

            color: #172033;

            font-size: 25px;

            font-weight: 800;

            margin-bottom: 7px;
        }

        .payment-card > p {

            text-align: center;

            color: #7b8798;

            font-size: 12px;

            margin-bottom: 25px;
        }

        .book-info {

            background: #f8fafc;

            border: 1px solid #e5eaf0;

            border-radius: 12px;

            padding: 15px;

            margin-bottom: 15px;
        }

        .book-info strong {

            display: block;

            color: #253044;

            font-size: 14px;

            margin-bottom: 4px;
        }

        .book-info span {

            color: #7b8798;

            font-size: 11px;
        }

        .fine-box {

            background: #fff7ed;

            border: 1px solid #fed7aa;

            border-radius: 13px;

            padding: 18px;

            text-align: center;

            margin-bottom: 20px;
        }

        .fine-box small {

            display: block;

            color: #9a3412;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            margin-bottom: 5px;
        }

        .fine-amount {

            color: #ea580c;

            font-size: 30px;

            font-weight: 800;
        }

        .pay-button {

            width: 100%;

            height: 46px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;
        }

        .back-link {

            display: block;

            text-align: center;

            margin-top: 15px;

            color: #64748b;

            font-size: 11px;

            text-decoration: none;
        }

        .back-link:hover {

            color: #2563eb;
        }

    </style>

</head>

<body>

<div class="payment-card">

    <div class="payment-icon">

        <i class="bi bi-credit-card"></i>

    </div>


    <h2>
        Pay Library Fine
    </h2>

    <p>
        Please pay the overdue fine before returning the book.
    </p>


    <div class="book-info">

        <strong>

            <?php
            echo htmlspecialchars(
                $book['title']
            );
            ?>

        </strong>

        <span>

            Author:
            <?php
            echo htmlspecialchars(
                $book['author']
            );
            ?>

        </span>

    </div>


    <div class="fine-box">

        <small>
            Total Fine
        </small>

        <div class="fine-amount">

            ₹<?php echo number_format($fine, 2); ?>

        </div>

    </div>


    <form
        method="POST"
        action="process_payment.php"
    >

        <input
            type="hidden"
            name="issued_book_id"
            value="<?php echo $book['id']; ?>"
        >

        <input
            type="hidden"
            name="fine"
            value="<?php echo $fine; ?>"
        >

        <button
            type="submit"
            class="pay-button"
        >

            <i class="bi bi-check2-circle me-1"></i>

            Pay Fine

        </button>

    </form>


    <a
        href="<?php echo BASE_URL; ?>/user/my_books/index.php"
        class="back-link"
    >

        ← Back to My Books

    </a>

</div>

</body>

</html>