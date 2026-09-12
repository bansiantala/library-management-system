<?php

require_once "../../config/database.php";
require_once "../../config/auth.php";

requireAdmin();


/* =========================================================
   GET REVIEW ID
========================================================= */

$review_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($review_id <= 0) {

    header(
        "Location: " . BASE_URL . "/admin/reviews/index.php"
    );

    exit();
}


/* =========================================================
   DELETE REVIEW
========================================================= */

$stmt = $conn->prepare(
    "DELETE FROM book_reviews
     WHERE id = ?"
);


if ($stmt) {

    $stmt->bind_param(
        "i",
        $review_id
    );

    $stmt->execute();

    $stmt->close();

}


/* =========================================================
   REDIRECT
========================================================= */

header(
    "Location: " .
    BASE_URL .
    "/admin/reviews/index.php"
);

exit();

?>