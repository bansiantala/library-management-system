<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    header("Location: " . BASE_URL . "/admin/issue/index.php");
    exit();
}


// =====================================================
// GET ISSUE REQUEST
// =====================================================

$stmt = $conn->prepare("
    SELECT
        issue_requests.id,
        issue_requests.user_id,
        issue_requests.book_id,
        issue_requests.status,
        books.title,
        books.available_quantity
    FROM issue_requests

    INNER JOIN books
        ON issue_requests.book_id = books.id

    WHERE issue_requests.id = ?
");

$stmt->bind_param("i", $request_id);
$stmt->execute();

$result = $stmt->get_result();

$request = $result->fetch_assoc();

$stmt->close();


if (!$request) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
    );

    exit();
}


// =====================================================
// CHECK REQUEST STATUS
// =====================================================

if ($request['status'] !== 'Pending') {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
    );

    exit();
}


// =====================================================
// CHECK BOOK AVAILABILITY
// =====================================================

$available_quantity =
    (int)$request['available_quantity'];

if ($available_quantity <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=unavailable"
    );

    exit();
}


// =====================================================
// ISSUE DATE & RETURN DATE
// 10 DAYS RETURN PERIOD
// =====================================================

$issue_date = date("Y-m-d");

$return_date = date(
    "Y-m-d",
    strtotime("+10 days")
);


// =====================================================
// START TRANSACTION
// =====================================================

$conn->begin_transaction();

try {


    // =================================================
    // ADD BOOK TO ISSUED BOOKS
    // =================================================

    $stmt = $conn->prepare("
        INSERT INTO issued_books
        (
            book_id,
            user_id,
            issue_date,
            return_date,
            actual_return_date,
            fine,
            status
        )
        VALUES
        (?, ?, ?, ?, NULL, 0, 'Issued')
    ");

    $stmt->bind_param(
        "iiss",
        $request['book_id'],
        $request['user_id'],
        $issue_date,
        $return_date
    );

    $stmt->execute();

    $stmt->close();


    // =================================================
    // DECREASE AVAILABLE QUANTITY
    // =================================================

    $stmt = $conn->prepare("
        UPDATE books

        SET available_quantity =
            available_quantity - 1

        WHERE id = ?

        AND available_quantity > 0
    ");

    $stmt->bind_param(
        "i",
        $request['book_id']
    );

    $stmt->execute();


    if ($stmt->affected_rows <= 0) {

        throw new Exception(
            "Book unavailable"
        );
    }

    $stmt->close();


    // =================================================
    // UPDATE REQUEST STATUS
    // =================================================

    $stmt = $conn->prepare("
        UPDATE issue_requests

        SET status = 'Approved'

        WHERE id = ?

        AND status = 'Pending'
    ");

    $stmt->bind_param(
        "i",
        $request_id
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
        "/admin/issue/index.php?message=approved"
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