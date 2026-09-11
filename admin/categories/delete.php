<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


if (!isset($_GET['id'])) {

    header("Location: index.php");

    exit();

}


$id = (int) $_GET['id'];


// Check whether category has books

$check = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM books
     WHERE category_id = ?"
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
            'This category cannot be deleted because books are assigned to it.'
        );

        window.location.href = 'index.php';

    </script>";

    exit();

}


// Delete category

$stmt = $conn->prepare(
    "DELETE FROM categories
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