<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


// =====================================================
// GET REQUEST ID
// =====================================================

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
    );
    exit();
}


// =====================================================
// START TRANSACTION
// =====================================================

$conn->begin_transaction();

try {

    // =================================================
    // GET ISSUE REQUEST + LOCK BOOK ROW
    // =================================================

    $stmt = $conn->prepare("
        SELECT
            issue_requests.id,
            issue_requests.user_id,
            issue_requests.book_id,
            issue_requests.status,
            issue_requests.requested_days,
            books.title,
            books.available_quantity

        FROM issue_requests

        INNER JOIN books
            ON issue_requests.book_id = books.id

        WHERE issue_requests.id = ?

        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception("Failed to prepare request query.");
    }

    $stmt->bind_param("i", $request_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute request query.");
    }

    $result = $stmt->get_result();

    $request = $result->fetch_assoc();

    $stmt->close();


    // =================================================
    // CHECK REQUEST EXISTS
    // =================================================

    if (!$request) {
        throw new Exception("Issue request not found.");
    }


    // =================================================
    // CHECK REQUEST STATUS
    // =================================================

    if ($request['status'] !== 'Pending') {

        throw new Exception(
            "This request has already been processed."
        );
    }


    // =================================================
    // GET REQUESTED BORROWING DAYS
    // =================================================

    $requested_days = isset($request['requested_days'])
        ? (int)$request['requested_days']
        : 3;


    // Only allow 3, 6 or 10 days

    if (!in_array($requested_days, [3, 6, 10], true)) {

        $requested_days = 3;
    }


    // =================================================
    // CHECK BOOK AVAILABILITY
    // =================================================

    $available_quantity =
        (int)$request['available_quantity'];

    if ($available_quantity <= 0) {

        throw new Exception(
            "Book unavailable."
        );
    }


    // =================================================
    // ISSUE DATE
    // =================================================
    // The actual issue date is the date when admin
    // approves the request.
    // =================================================

    $issue_date = date("Y-m-d");


    // =================================================
    // CALCULATE DUE / RETURN DATE
    // =================================================
    // Based on selected borrowing period:
    //
    // 3 Days  -> issue date + 3 days
    // 6 Days  -> issue date + 6 days
    // 10 Days -> issue date + 10 days
    // =================================================

    $return_date = date(
        "Y-m-d",
        strtotime(
            "+" . $requested_days . " days",
            strtotime($issue_date)
        )
    );


    // =================================================
    // INSERT INTO ISSUED BOOKS
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
        (
            ?,
            ?,
            ?,
            ?,
            NULL,
            0.00,
            'Issued'
        )
    ");

    if (!$stmt) {
        throw new Exception(
            "Failed to prepare issued book query."
        );
    }

    $book_id = (int)$request['book_id'];
    $user_id = (int)$request['user_id'];

    $stmt->bind_param(
        "iiss",
        $book_id,
        $user_id,
        $issue_date,
        $return_date
    );


    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to issue book."
        );
    }

    $stmt->close();


    // =================================================
    // DECREASE AVAILABLE BOOK QUANTITY
    // =================================================

    $stmt = $conn->prepare("
        UPDATE books

        SET available_quantity =
            available_quantity - 1

        WHERE id = ?

        AND available_quantity > 0
    ");

    if (!$stmt) {
        throw new Exception(
            "Failed to prepare book update query."
        );
    }

    $stmt->bind_param(
        "i",
        $book_id
    );


    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to update book quantity."
        );
    }


    // Make sure quantity was actually decreased

    if ($stmt->affected_rows <= 0) {

        throw new Exception(
            "Book unavailable."
        );
    }

    $stmt->close();


    // =================================================
    // UPDATE ISSUE REQUEST
    // =================================================
    // Store:
    // - Approved status
    // - Final due date
    // =================================================

    $stmt = $conn->prepare("
        UPDATE issue_requests

        SET
            status = 'Approved',
            due_date = ?

        WHERE id = ?

        AND status = 'Pending'
    ");

    if (!$stmt) {
        throw new Exception(
            "Failed to prepare request update query."
        );
    }

    $stmt->bind_param(
        "si",
        $return_date,
        $request_id
    );


    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to update issue request."
        );
    }


    // Make sure request was updated

    if ($stmt->affected_rows <= 0) {

        throw new Exception(
            "Issue request was already processed."
        );
    }

    $stmt->close();


    // =================================================
    // COMMIT TRANSACTION
    // =================================================

    $conn->commit();


    // =================================================
    // SUCCESS
    // =================================================

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?message=approved"
    );

    exit();


} catch (Exception $e) {


    // =================================================
    // ROLLBACK TRANSACTION
    // =================================================

    $conn->rollback();


    // =================================================
    // CHECK IF BOOK WAS UNAVAILABLE
    // =================================================

    if (
        $e->getMessage() === "Book unavailable."
    ) {

        header(
            "Location: " .
            BASE_URL .
            "/admin/issue/index.php?error=unavailable"
        );

        exit();
    }


    // =================================================
    // OTHER ERRORS
    // =================================================

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php?error=failed"
    );

    exit();
}
?>