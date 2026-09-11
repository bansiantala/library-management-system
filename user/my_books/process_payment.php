<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}

$user_id = $_SESSION['user_id'];

$issued_book_id = (int)(
    $_POST['issued_book_id'] ?? 0
);

$fine = (float)(
    $_POST['fine'] ?? 0
);

if ($issued_book_id <= 0 || $fine <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php"
    );

    exit();
}


/* =========================================
   VERIFY BOOK
========================================= */

$stmt = $conn->prepare(
    "SELECT id
     FROM issued_books
     WHERE id = ?
       AND user_id = ?
       AND status = 'Issued'
       AND payment_status = 'Unpaid'
     LIMIT 1"
);

$stmt->bind_param(
    "ii",
    $issued_book_id,
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

$stmt->close();


/* =========================================
   PAYMENT SUCCESS
========================================= */

$stmt = $conn->prepare(
    "UPDATE issued_books
     SET
        fine = ?,
        fine_paid = ?,
        payment_status = 'Paid',
        payment_date = NOW()
     WHERE id = ?
       AND user_id = ?"
);

$stmt->bind_param(
    "ddii",
    $fine,
    $fine,
    $issued_book_id,
    $user_id
);

if ($stmt->execute()) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php?payment=success"
    );

    exit();

}

$stmt->close();

header(
    "Location: " .
    BASE_URL .
    "/user/my_books/index.php?payment=error"
);

exit();