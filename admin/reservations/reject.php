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
// CHECK RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "SELECT
        id,
        status
     FROM reservations
     WHERE id = ?
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
// NOT FOUND
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
// ONLY PENDING CAN BE REJECTED
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
// REJECT RESERVATION
// =========================================================
//
// Your reservations table has:
// Pending / Approved / Cancelled
//
// So "Rejected" is represented as Cancelled.
// =========================================================

$stmt = $conn->prepare(
    "UPDATE reservations
     SET status = 'Cancelled'
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
        "/admin/reservations/index.php?status=rejected"
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