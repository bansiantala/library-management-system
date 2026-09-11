<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


if (!isset($_GET['id'])) {

    header("Location: index.php");

    exit();

}


$id = (int) $_GET['id'];


// Check user exists

$stmt = $conn->prepare(
    "SELECT id
     FROM users
     WHERE id = ?
     AND role = 'user'"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");

    exit();

}


// Check active issued books

$check = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ?
     AND status = 'Issued'"
);

$check->bind_param(
    "i",
    $id
);

$check->execute();

$checkResult = $check->get_result();

$data = $checkResult->fetch_assoc();


if ($data['total'] > 0) {

    echo "<script>

        alert(
            'This user cannot be deleted because they currently have issued books.'
        );

        window.location.href = 'index.php';

    </script>";

    exit();

}


// Delete user

$delete = $conn->prepare(
    "DELETE FROM users
     WHERE id = ?
     AND role = 'user'"
);

$delete->bind_param(
    "i",
    $id
);

$delete->execute();


header("Location: index.php");

exit();

?>