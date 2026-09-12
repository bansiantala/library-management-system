<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();


// =========================================================
// GET USER ID
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
// GET BOOK ID
// =========================================================

$book_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($book_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php"
    );

    exit();
}


// =========================================================
// FETCH BOOK
// =========================================================

$stmt = $conn->prepare(
    "SELECT
        id,
        title,
        available_quantity
     FROM books
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=error"
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
// BOOK NOT FOUND
// =========================================================

if (!$book) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=error"
    );

    exit();
}


// =========================================================
// BOOK ALREADY AVAILABLE
// =========================================================

if ((int)$book['available_quantity'] > 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id
    );

    exit();
}


// =========================================================
// CHECK EXISTING PENDING RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "SELECT id
     FROM reservations
     WHERE user_id = ?
       AND book_id = ?
       AND status = 'Pending'
     LIMIT 1"
);

if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=error"
    );

    exit();
}

$stmt->bind_param(
    "ii",
    $user_id,
    $book_id
);

$stmt->execute();

$existingResult = $stmt->get_result();

$existingReservation =
    $existingResult->fetch_assoc();

$stmt->close();


// =========================================================
// ALREADY RESERVED
// =========================================================

if ($existingReservation) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=exists"
    );

    exit();
}


// =========================================================
// INSERT RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "INSERT INTO reservations
        (user_id, book_id, status)
     VALUES (?, ?, 'Pending')"
);

if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=error"
    );

    exit();
}


$stmt->bind_param(
    "ii",
    $user_id,
    $book_id
);


if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?reservation=success"
    );

    exit();

}


$stmt->close();


header(
    "Location: " .
    BASE_URL .
    "/user/books/index.php?reservation=error"
);

exit();

?>