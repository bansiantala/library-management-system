<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$issue_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


if ($issue_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php"
    );

    exit();
}


// =====================================================
// GET ISSUED BOOK
// =====================================================

$stmt = $conn->prepare("
    SELECT
        issued_books.id,
        issued_books.book_id,
        issued_books.issue_date,
        issued_books.return_date,
        issued_books.status,

        books.title

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    WHERE issued_books.id = ?

    AND issued_books.status = 'Issued'
");

$stmt->bind_param(
    "i",
    $issue_id
);

$stmt->execute();

$result = $stmt->get_result();

$issued_book = $result->fetch_assoc();

$stmt->close();


if (!$issued_book) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
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
    $issued_book['return_date']
);

$return_date = new DateTime(
    $actual_return_date
);


$fine = 0;


// If returned after due date
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

        AND status = 'Issued'
    ");

    $stmt->bind_param(
        "sdi",
        $actual_return_date,
        $fine,
        $issue_id
    );

    $stmt->execute();


    if ($stmt->affected_rows <= 0) {

        throw new Exception(
            "Return failed"
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
        $issued_book['book_id']
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
        "/admin/issue/index.php?message=returned"
    );

    exit();


} catch (Exception $e) {


    // =================================================
    // ROLLBACK
    // =================================================

    $conn->rollback();


    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
    );

    exit();
}