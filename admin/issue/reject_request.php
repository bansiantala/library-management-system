<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$request_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

if ($request_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/admin/issue/index.php"
    );

    exit();
}


$stmt = $conn->prepare("
    UPDATE issue_requests
    SET status = 'Rejected'
    WHERE id = ?
    AND status = 'Pending'
");

$stmt->bind_param(
    "i",
    $request_id
);

$stmt->execute();

$stmt->close();


header(
    "Location: " .
    BASE_URL .
    "/admin/issue/index.php?message=rejected"
);

exit();