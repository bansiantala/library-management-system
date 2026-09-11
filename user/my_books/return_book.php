<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$issued_book_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

$user_id = $_SESSION['user_id'];

if ($issued_book_id <= 0) {
    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php?return=error"
    );
    exit();
}


// =====================================================
// GET USER'S ISSUED BOOK
// =====================================================

$stmt = $conn->prepare("
    SELECT
        issued_books.id,
        issued_books.book_id,
        issued_books.return_date,
        issued_books.status,
        books.title

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    WHERE issued_books.id = ?
    AND issued_books.user_id = ?
    AND issued_books.status = 'Issued'
");

$stmt->bind_param(
    "ii",
    $issued_book_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


if (!$book) {

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php?return=invalid"
    );

    exit();
}


// =====================================================
// ACTUAL RETURN DATE
// =====================================================

$actual_return_date = date("Y-m-d");


// =====================================================
// CALCULATE FINE
// ₹10 PER DAY AFTER DUE DATE
// =====================================================

$due_date = new DateTime(
    $book['return_date']
);

$return_date = new DateTime(
    $actual_return_date
);

$fine = 0;


// Only fine if returned after due date
if ($return_date > $due_date) {

    $difference = $due_date->diff(
        $return_date
    );

    $overdue_days = $difference->days;

    $fine = $overdue_days * 10;
}


// =====================================================
// START TRANSACTION
// =====================================================

$conn->begin_transaction();

try {


    // =================================================
    // UPDATE ISSUED BOOK
    // =================================================

    $stmt = $conn->prepare("
        UPDATE issued_books

        SET
            actual_return_date = ?,
            fine = ?,
            status = 'Returned'

        WHERE id = ?
        AND user_id = ?
        AND status = 'Issued'
    ");

    $stmt->bind_param(
        "sdii",
        $actual_return_date,
        $fine,
        $issued_book_id,
        $user_id
    );

    $stmt->execute();


    if ($stmt->affected_rows <= 0) {

        throw new Exception(
            "Book return failed"
        );
    }

    $stmt->close();


    // =================================================
    // INCREASE AVAILABLE QUANTITY
    // =================================================

    $stmt = $conn->prepare("
        UPDATE books

        SET available_quantity =
            available_quantity + 1

        WHERE id = ?
    ");

    $stmt->bind_param(
        "i",
        $book['book_id']
    );

    $stmt->execute();

    $stmt->close();


    // =================================================
    // COMMIT
    // =================================================

    $conn->commit();


    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php?return=success"
    );

    exit();


} catch (Exception $e) {

    $conn->rollback();

    header(
        "Location: " .
        BASE_URL .
        "/user/my_books/index.php?return=error"
    );

    exit();
}