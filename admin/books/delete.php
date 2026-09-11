<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


if (!isset($_GET['id'])) {

    header("Location: index.php");

    exit();

}


$id = (int) $_GET['id'];


// Check if book has issue history

$check = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE book_id = ?"
);

$check->bind_param(
    "i",
    $id
);

$check->execute();

$result = $check->get_result();

$data = $result->fetch_assoc();


if ($data['total'] > 0) {

    echo "<script>

        alert(
            'This book cannot be deleted because it has issue/return history.'
        );

        window.location.href = 'index.php';

    </script>";

    exit();

}


// Delete book

$stmt = $conn->prepare(
    "DELETE FROM books
     WHERE id = ?"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();


header("Location: index.php");

exit();

?>