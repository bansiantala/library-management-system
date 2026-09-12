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
// GET RESERVATION ID
// =========================================================

$reservation_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($reservation_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/reservations/index.php?status=error"
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
       AND user_id = ?
     LIMIT 1"
);


if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/reservations/index.php?status=error"
    );

    exit();
}


$stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
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
        "/user/reservations/index.php?status=error"
    );

    exit();
}


// =========================================================
// ONLY PENDING RESERVATION CAN BE CANCELLED
// =========================================================

if ($reservation['status'] !== 'Pending') {

    header(
        "Location: " .
        BASE_URL .
        "/user/reservations/index.php?status=error"
    );

    exit();
}


// =========================================================
// CANCEL RESERVATION
// =========================================================

$stmt = $conn->prepare(
    "UPDATE reservations
     SET status = 'Cancelled'
     WHERE id = ?
       AND user_id = ?
       AND status = 'Pending'"
);


if (!$stmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/reservations/index.php?status=error"
    );

    exit();
}


$stmt->bind_param(
    "ii",
    $reservation_id,
    $user_id
);


if ($stmt->execute() && $stmt->affected_rows > 0) {

    $stmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/user/reservations/index.php?status=cancelled"
    );

    exit();

}


$stmt->close();


header(
    "Location: " .
    BASE_URL .
    "/user/reservations/index.php?status=error"
);

exit();

?>