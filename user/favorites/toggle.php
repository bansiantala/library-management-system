```php
<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = (int)($_SESSION['user_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Get Book ID
|--------------------------------------------------------------------------
*/

$book_id = isset($_GET['book_id'])
    ? (int)$_GET['book_id']
    : 0;

/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if ($user_id <= 0 || $book_id <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=error"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| Check Whether Book Exists
|--------------------------------------------------------------------------
*/

$bookStmt = $conn->prepare(
    "SELECT id
     FROM books
     WHERE id = ?
     LIMIT 1"
);

if (!$bookStmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=error"
    );

    exit();

}

$bookStmt->bind_param(
    "i",
    $book_id
);

$bookStmt->execute();

$bookResult = $bookStmt->get_result();

if ($bookResult->num_rows === 0) {

    $bookStmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=not_found"
    );

    exit();

}

$bookStmt->close();


/*
|--------------------------------------------------------------------------
| Check Existing Favorite
|--------------------------------------------------------------------------
*/

$favoriteStmt = $conn->prepare(
    "SELECT id
     FROM favorites
     WHERE user_id = ?
       AND book_id = ?
     LIMIT 1"
);

if (!$favoriteStmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=error"
    );

    exit();

}

$favoriteStmt->bind_param(
    "ii",
    $user_id,
    $book_id
);

$favoriteStmt->execute();

$favoriteResult = $favoriteStmt->get_result();


/*
|--------------------------------------------------------------------------
| REMOVE FAVORITE
|--------------------------------------------------------------------------
*/

if ($favoriteResult->num_rows > 0) {

    $favorite = $favoriteResult->fetch_assoc();

    $favorite_id = (int)$favorite['id'];

    $favoriteStmt->close();


    $deleteStmt = $conn->prepare(
        "DELETE FROM favorites
         WHERE id = ?
           AND user_id = ?
           AND book_id = ?"
    );

    if (!$deleteStmt) {

        header(
            "Location: " .
            BASE_URL .
            "/user/books/index.php?favorite=error"
        );

        exit();

    }

    $deleteStmt->bind_param(
        "iii",
        $favorite_id,
        $user_id,
        $book_id
    );

    if ($deleteStmt->execute()) {

        $deleteStmt->close();

        header(
            "Location: " .
            BASE_URL .
            "/user/books/index.php?favorite=removed"
        );

        exit();

    }


    $deleteStmt->close();


    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=error"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| ADD FAVORITE
|--------------------------------------------------------------------------
*/

$favoriteStmt->close();


$insertStmt = $conn->prepare(
    "INSERT INTO favorites
        (user_id, book_id)
     VALUES
        (?, ?)"
);

if (!$insertStmt) {

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=error"
    );

    exit();

}


$insertStmt->bind_param(
    "ii",
    $user_id,
    $book_id
);


if ($insertStmt->execute()) {

    $insertStmt->close();

    header(
        "Location: " .
        BASE_URL .
        "/user/books/index.php?favorite=added"
    );

    exit();

}


$insertStmt->close();


/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

header(
    "Location: " .
    BASE_URL .
    "/user/books/index.php?favorite=error"
);

exit();

?>
