<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    header("Location: " . BASE_URL . "/user/books/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// =========================================================
// GET BOOK
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

$stmt->bind_param("i", $book_id);
$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


if (!$book) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php"
    );

    exit();
}


// =========================================================
// CHECK AVAILABILITY
// =========================================================

$available = (int)$book['available_quantity'];

if ($available <= 0) {

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

$stmt->bind_param(
    "ii",
    $user_id,
    $book_id
);

$stmt->execute();

$existing = $stmt->get_result()->fetch_assoc();

$stmt->close();


if ($existing) {

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
// INSERT REQUEST
// =========================================================

$stmt = $conn->prepare("
    INSERT INTO issue_requests
    (
        user_id,
        book_id,
        status
    )
    VALUES
    (
        ?,
        ?,
        'Pending'
    )
");

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
        "/user/books/details.php?id=" .
        $book_id .
        "&request=success"
    );

    exit();

} else {

    $stmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/user/books/details.php?id=" .
        $book_id .
        "&request=error"
    );

    exit();
}