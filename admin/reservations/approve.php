<?php

require_once "../../config/database.php";
require_once "../../config/auth.php";

requireAdmin();


// =========================================================
// GET RESERVATION ID
// =========================================================

$reservation_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($reservation_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=error"
    );

    exit();
}


// =========================================================
// GET RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "SELECT
        reservations.id,
        reservations.user_id,
        reservations.book_id,
        reservations.status,
        books.title,
        books.available_quantity
     FROM reservations
     INNER JOIN books
        ON reservations.book_id = books.id
     WHERE reservations.id = ?
     LIMIT 1"
);


if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=error"
    );

    exit();
}


$stmt->bind_param(
    "i",
    $reservation_id
);


$stmt->execute();


$result = $stmt->get_result();


$reservation = $result->fetch_assoc();


$stmt->close();


// =========================================================
// RESERVATION NOT FOUND
// =========================================================

if (!$reservation) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=error"
    );

    exit();
}


// =========================================================
// ONLY PENDING CAN BE APPROVED
// =========================================================

if ($reservation['status'] !== 'Pending') {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=error"
    );

    exit();
}


// =========================================================
// CHECK BOOK AVAILABILITY
// =========================================================

if ((int)$reservation['available_quantity'] > 0) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=available"
    );

    exit();
}


// =========================================================
// APPROVE RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "UPDATE reservations
     SET status = 'Approved'
     WHERE id = ?
       AND status = 'Pending'"
);


if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=error"
    );

    exit();
}


$stmt->bind_param(
    "i",
    $reservation_id
);


if (
    $stmt->execute() &&
    $stmt->affected_rows > 0
) {

    $stmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/admin/reservations/index.php?status=approved"
    );

    exit();
}


$stmt->close();


header(
    "Location: " .
    BASE_URL .
    "/admin/reservations/index.php?status=error"
);

exit();

?>