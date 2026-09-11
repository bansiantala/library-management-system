<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


/* ==========================================
   CHECK BOOK ID
========================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: index.php");
    exit();

}

$id = (int) $_GET['id'];


/* ==========================================
   GET BOOK
========================================== */

$stmt = $conn->prepare(
    "SELECT *
     FROM books
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");
    exit();

}

$book = $result->fetch_assoc();

$stmt->close();


/* ==========================================
   GET CATEGORIES
========================================== */

$categoryResult = $conn->query(
    "SELECT *
     FROM categories
     ORDER BY category_name ASC"
);


$message = "";


/* ==========================================
   UPDATE BOOK
========================================== */

if (isset($_POST['update_book'])) {

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $isbn = trim($_POST['isbn'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);


    if ($title === "" || $author === "") {

        $message = "Book title and author are required.";

    } elseif ($categoryId <= 0) {

        $message = "Please select a category.";

    } elseif ($quantity <= 0) {

        $message = "Quantity must be greater than 0.";

    } else {


        /* ==================================
           CURRENTLY ISSUED COPIES
        ================================== */

        $issuedQuantity =
            (int)$book['quantity']
            -
            (int)$book['available_quantity'];


        /* ==================================
           QUANTITY VALIDATION
        ================================== */

        if ($quantity < $issuedQuantity) {

            $message =
                "Quantity cannot be less than currently issued books ("
                . $issuedQuantity
                . ").";

        } else {


            /*
             * Available copies =
             * New total quantity - currently issued copies
             */

            $availableQuantity =
                $quantity - $issuedQuantity;


            /* ==================================
               UPDATE
            ================================== */

            $update = $conn->prepare(
                "UPDATE books
                 SET
                    title = ?,
                    author = ?,
                    category_id = ?,
                    isbn = ?,
                    quantity = ?,
                    available_quantity = ?
                 WHERE id = ?"
            );


            $update->bind_param(
                "ssisiii",
                $title,
                $author,
                $categoryId,
                $isbn,
                $quantity,
                $availableQuantity,
                $id
            );


            if ($update->execute()) {

                $update->close();

                header("Location: index.php");
                exit();

            } else {

                $message =
                    "Failed to update book. Please try again.";

            }

            if ($update) {
                $update->close();
            }

        }

    }

}


/* ==========================================
   CURRENT ISSUED COUNT
========================================== */

$currentIssued =
    (int)$book['quantity']
    -
    (int)$book['available_quantity'];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Book | Library Management System</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >


    <style>

        /* =========================================
           EDIT BOOK PAGE
        ========================================= */

        .edit-book-page {
            padding: 28px;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .edit-book-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 25px;
        }


        .edit-book-title h2 {
            margin: 0;

            font-size: 25px;

            font-weight: 700;

            color: #172033;
        }


        .edit-book-title p {
            margin: 6px 0 0;

            color: #8b95a7;

            font-size: 13px;
        }


        /* =========================================
           BACK BUTTON
        ========================================= */

        .back-books-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 9px 14px;

            background: #ffffff;

            border: 1px solid #e3e7ed;

            border-radius: 8px;

            color: #475569;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .back-books-btn:hover {

            background: #f8fafc;

            border-color: #cbd5e1;

            color: #2563eb;
        }


        /* =========================================
           MAIN LAYOUT
        ========================================= */

        .edit-book-layout {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr) 300px;

            gap: 22px;

            align-items: start;
        }


        /* =========================================
           FORM CARD
        ========================================= */

        .edit-book-card {

            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.03);
        }


        /* =========================================
           CARD HEADER
        ========================================= */

        .edit-book-card-header {

            padding: 19px 22px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .form-header-icon {

            width: 40px;

            height: 40px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 18px;
        }


        .edit-book-card-header h5 {

            margin: 0;

            color: #172033;

            font-size: 15px;

            font-weight: 700;
        }


        .edit-book-card-header span {

            display: block;

            margin-top: 3px;

            font-size: 11px;

            color: #94a3b8;
        }


        /* =========================================
           CARD BODY
        ========================================= */

        .edit-book-card-body {

            padding: 23px;
        }


        /* =========================================
           ERROR
        ========================================= */

        .book-error {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 11px 13px;

            margin-bottom: 20px;

            background: #fef2f2;

            border: 1px solid #fecaca;

            border-radius: 8px;

            color: #b91c1c;

            font-size: 12px;
        }


        .book-error i {

            font-size: 15px;
        }


        /* =========================================
           FORM GROUP
        ========================================= */

        .book-form-group {

            margin-bottom: 19px;
        }


        .book-form-label {

            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;

            font-weight: 600;
        }


        .required-star {

            color: #ef4444;
        }


        /* =========================================
           INPUT
        ========================================= */

        .book-input-wrapper {

            position: relative;
        }


        .book-input-icon {

            position: absolute;

            left: 12px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 15px;

            pointer-events: none;

            z-index: 2;
        }


        .book-form-control {

            width: 100%;

            height: 43px;

            border: 1px solid #e1e6ed;

            border-radius: 8px;

            background: #ffffff;

            padding: 0 12px 0 38px;

            outline: none;

            color: #334155;

            font-size: 13px;

            transition: 0.2s ease;

            box-sizing: border-box;
        }


        .book-form-control:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.08);
        }


        /* =========================================
           SELECT
        ========================================= */

        .book-form-select {

            width: 100%;

            height: 43px;

            border: 1px solid #e1e6ed;

            border-radius: 8px;

            background-color: #ffffff;

            padding: 0 35px 0 38px;

            outline: none;

            color: #334155;

            font-size: 13px;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .book-form-select:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.08);
        }


        /* =========================================
           TWO COLUMN
        ========================================= */

        .book-form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;
        }


        /* =========================================
           QUANTITY INFO
        ========================================= */

        .quantity-info {

            margin-top: 8px;

            padding: 10px 12px;

            display: flex;

            align-items: center;

            gap: 8px;

            background: #f8fafc;

            border: 1px solid #edf0f4;

            border-radius: 7px;

            color: #64748b;

            font-size: 11px;
        }


        .quantity-info i {

            color: #2563eb;

            font-size: 14px;
        }


        .quantity-info strong {

            color: #334155;
        }


        /* =========================================
           FORM ACTIONS
        ========================================= */

        .book-form-actions {

            display: flex;

            align-items: center;

            gap: 9px;

            padding-top: 20px;

            margin-top: 20px;

            border-top: 1px solid #edf0f4;
        }


        .update-book-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            min-width: 135px;

            height: 40px;

            padding: 0 16px;

            background: #2563eb;

            border: none;

            border-radius: 8px;

            color: #ffffff;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .update-book-btn:hover {

            background: #1d4ed8;

            box-shadow:
                0 5px 12px rgba(37, 99, 235, 0.18);

            transform: translateY(-1px);
        }


        .cancel-book-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            height: 40px;

            padding: 0 16px;

            background: #ffffff;

            border: 1px solid #e1e6ed;

            border-radius: 8px;

            color: #64748b;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .cancel-book-btn:hover {

            background: #f8fafc;

            color: #334155;

            border-color: #cbd5e1;
        }


        /* =========================================
           BOOK DETAILS CARD
        ========================================= */

        .book-details-card {

            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.03);
        }


        .book-details-header {

            padding: 18px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .book-details-header-icon {

            width: 34px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 16px;
        }


        .book-details-header h6 {

            margin: 0;

            color: #172033;

            font-size: 13px;

            font-weight: 700;
        }


        .book-details-body {

            padding: 18px;
        }


        /* =========================================
           BOOK DETAIL
        ========================================= */

        .book-detail-item {

            padding-bottom: 14px;

            margin-bottom: 14px;

            border-bottom: 1px solid #f0f2f5;
        }


        .book-detail-item:last-child {

            padding-bottom: 0;

            margin-bottom: 0;

            border-bottom: none;
        }


        .book-detail-label {

            display: block;

            margin-bottom: 4px;

            color: #94a3b8;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            font-weight: 600;
        }


        .book-detail-value {

            display: block;

            color: #334155;

            font-size: 12px;

            font-weight: 600;

            word-break: break-word;
        }


        /* =========================================
           STOCK SUMMARY
        ========================================= */

        .stock-summary {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 9px;

            margin-top: 18px;
        }


        .stock-box {

            padding: 11px;

            border-radius: 8px;

            text-align: center;
        }


        .stock-box.available {

            background: #ecfdf5;

            color: #059669;
        }


        .stock-box.issued {

            background: #fff7ed;

            color: #ea580c;
        }


        .stock-box strong {

            display: block;

            font-size: 18px;

            font-weight: 700;
        }


        .stock-box span {

            display: block;

            margin-top: 2px;

            font-size: 9px;

            font-weight: 600;

            text-transform: uppercase;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1000px) {

            .edit-book-layout {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .edit-book-page {

                padding: 20px 15px;
            }


            .edit-book-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 12px;
            }


            .back-books-btn {

                width: 100%;

                justify-content: center;
            }


            .book-form-row {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 576px) {

            .edit-book-title h2 {

                font-size: 21px;
            }


            .edit-book-card-body {

                padding: 18px;
            }


            .book-form-actions {

                flex-direction: column;
            }


            .update-book-btn,
            .cancel-book-btn {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     ADMIN SIDEBAR
========================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- ==========================================
     MAIN
========================================== -->

<div class="admin-main">


    <!-- ======================================
         ADMIN NAVBAR
    ======================================= -->

    <nav class="admin-navbar">


        <!-- LEFT -->

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Edit Book
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Books

                    <i class="bi bi-chevron-right"></i>

                    Edit Book

                </span>

            </div>

        </div>


        <!-- RIGHT -->

        <div class="navbar-right">


            <!-- Notification -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >

                <i class="bi bi-bell"></i>

            </button>


            <!-- Divider -->

            <div class="header-divider"></div>


            <!-- Admin -->

            <div class="nav-admin">

                <div class="nav-avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <div class="nav-admin-info">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? 'Admin'
                        );

                        ?>

                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>


            <!-- Logout -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </nav>



    <!-- ======================================
         PAGE CONTENT
    ======================================= -->

    <div class="edit-book-page">


        <!-- PAGE HEADER -->

        <div class="edit-book-header">


            <div class="edit-book-title">

                <h2>
                    Edit Book
                </h2>

                <p>
                    Update the information and availability
                    of this library book.
                </p>

            </div>


            <a
                href="index.php"
                class="back-books-btn"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Books

            </a>

        </div>



        <!-- ==================================
             MAIN LAYOUT
        =================================== -->

        <div class="edit-book-layout">


            <!-- =================================
                 FORM
            ================================== -->

            <div class="edit-book-card">


                <!-- CARD HEADER -->

                <div class="edit-book-card-header">

                    <div class="form-header-icon">

                        <i class="bi bi-pencil-square"></i>

                    </div>

                    <div>

                        <h5>
                            Book Information
                        </h5>

                        <span>
                            Modify the details of this book.
                        </span>

                    </div>

                </div>


                <!-- CARD BODY -->

                <div class="edit-book-card-body">


                    <!-- ERROR -->

                    <?php if ($message !== ""): ?>

                        <div class="book-error">

                            <i class="bi bi-exclamation-circle"></i>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $message
                                );
                                ?>

                            </span>

                        </div>

                    <?php endif; ?>



                    <!-- FORM -->

                    <form
                        method="POST"
                        id="editBookForm"
                    >


                        <!-- TITLE -->

                        <div class="book-form-group">

                            <label
                                class="book-form-label"
                                for="title"
                            >

                                Book Title

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="book-input-wrapper">

                                <i
                                    class="bi bi-book book-input-icon"
                                ></i>

                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    class="book-form-control"
                                    placeholder="Enter book title"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $book['title']
                                    );

                                    ?>"
                                    maxlength="200"
                                    required
                                >

                            </div>

                        </div>



                        <!-- AUTHOR -->

                        <div class="book-form-group">

                            <label
                                class="book-form-label"
                                for="author"
                            >

                                Author

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="book-input-wrapper">

                                <i
                                    class="bi bi-person book-input-icon"
                                ></i>

                                <input
                                    type="text"
                                    id="author"
                                    name="author"
                                    class="book-form-control"
                                    placeholder="Enter author name"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $book['author']
                                    );

                                    ?>"
                                    maxlength="150"
                                    required
                                >

                            </div>

                        </div>



                        <!-- CATEGORY + ISBN -->

                        <div class="book-form-row">


                            <!-- CATEGORY -->

                            <div class="book-form-group">

                                <label
                                    class="book-form-label"
                                    for="category_id"
                                >

                                    Category

                                    <span class="required-star">
                                        *
                                    </span>

                                </label>


                                <div class="book-input-wrapper">

                                    <i
                                        class="bi bi-grid book-input-icon"
                                    ></i>


                                    <select
                                        name="category_id"
                                        id="category_id"
                                        class="book-form-select"
                                        required
                                    >


                                        <option value="">
                                            Select Category
                                        </option>


                                        <?php

                                        if (
                                            $categoryResult &&
                                            $categoryResult->num_rows > 0
                                        ) {

                                            while (
                                                $category =
                                                $categoryResult
                                                ->fetch_assoc()
                                            ) {

                                                $selected =
                                                    (
                                                        $category['id']
                                                        ==
                                                        $book['category_id']
                                                    )
                                                    ? 'selected'
                                                    : '';

                                        ?>

                                            <option
                                                value="<?php
                                                echo $category['id'];
                                                ?>"
                                                <?php
                                                echo $selected;
                                                ?>
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $category[
                                                        'category_name'
                                                    ]
                                                );

                                                ?>

                                            </option>

                                        <?php

                                            }

                                        }

                                        ?>

                                    </select>

                                </div>

                            </div>



                            <!-- ISBN -->

                            <div class="book-form-group">

                                <label
                                    class="book-form-label"
                                    for="isbn"
                                >

                                    ISBN

                                    <span
                                        style="
                                            color:#94a3b8;
                                            font-weight:400;
                                        "
                                    >
                                        (Optional)
                                    </span>

                                </label>


                                <div class="book-input-wrapper">

                                    <i
                                        class="bi bi-upc-scan book-input-icon"
                                    ></i>

                                    <input
                                        type="text"
                                        id="isbn"
                                        name="isbn"
                                        class="book-form-control"
                                        placeholder="Enter ISBN"
                                        value="<?php

                                        echo htmlspecialchars(
                                            $book['isbn'] ?? ''
                                        );

                                        ?>"
                                        maxlength="50"
                                    >

                                </div>

                            </div>

                        </div>



                        <!-- QUANTITY -->

                        <div class="book-form-group">

                            <label
                                class="book-form-label"
                                for="quantity"
                            >

                                Total Quantity

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="book-input-wrapper">

                                <i
                                    class="bi bi-123 book-input-icon"
                                ></i>

                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    class="book-form-control"
                                    min="<?php
                                    echo max(
                                        1,
                                        $currentIssued
                                    );
                                    ?>"
                                    max="9999"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $book['quantity']
                                    );

                                    ?>"
                                    required
                                >

                            </div>


                            <!-- Issued Information -->

                            <div class="quantity-info">

                                <i class="bi bi-info-circle"></i>

                                <span>

                                    Currently issued:

                                    <strong>
                                        <?php
                                        echo $currentIssued;
                                        ?>
                                    </strong>

                                    <?php
                                    echo
                                        $currentIssued == 1
                                        ? 'copy'
                                        : 'copies';
                                    ?>

                                </span>

                            </div>

                        </div>



                        <!-- ACTIONS -->

                        <div class="book-form-actions">


                            <!-- UPDATE -->

                            <button
                                type="submit"
                                name="update_book"
                                class="update-book-btn"
                            >

                                <i class="bi bi-check-lg"></i>

                                Update Book

                            </button>


                            <!-- CANCEL -->

                            <a
                                href="index.php"
                                class="cancel-book-btn"
                            >

                                <i class="bi bi-x-lg"></i>

                                Cancel

                            </a>

                        </div>


                    </form>

                </div>

            </div>



            <!-- =================================
                 BOOK DETAILS
            ================================== -->

            <div class="book-details-card">


                <!-- HEADER -->

                <div class="book-details-header">

                    <div class="book-details-header-icon">

                        <i class="bi bi-book-half"></i>

                    </div>

                    <h6>
                        Current Book Details
                    </h6>

                </div>


                <!-- BODY -->

                <div class="book-details-body">


                    <!-- Title -->

                    <div class="book-detail-item">

                        <span class="book-detail-label">
                            Book Title
                        </span>

                        <span class="book-detail-value">

                            <?php

                            echo htmlspecialchars(
                                $book['title']
                            );

                            ?>

                        </span>

                    </div>



                    <!-- Author -->

                    <div class="book-detail-item">

                        <span class="book-detail-label">
                            Author
                        </span>

                        <span class="book-detail-value">

                            <?php

                            echo htmlspecialchars(
                                $book['author']
                            );

                            ?>

                        </span>

                    </div>



                    <!-- ISBN -->

                    <div class="book-detail-item">

                        <span class="book-detail-label">
                            ISBN
                        </span>

                        <span class="book-detail-value">

                            <?php

                            echo !empty($book['isbn'])
                                ? htmlspecialchars(
                                    $book['isbn']
                                )
                                : 'Not Provided';

                            ?>

                        </span>

                    </div>



                    <!-- Quantity -->

                    <div class="book-detail-item">

                        <span class="book-detail-label">
                            Total Quantity
                        </span>

                        <span class="book-detail-value">

                            <?php
                            echo $book['quantity'];
                            ?>
                            copies

                        </span>

                    </div>



                    <!-- Stock Summary -->

                    <div class="stock-summary">


                        <div class="stock-box available">

                            <strong>

                                <?php
                                echo $book[
                                    'available_quantity'
                                ];
                                ?>

                            </strong>

                            <span>
                                Available
                            </span>

                        </div>


                        <div class="stock-box issued">

                            <strong>

                                <?php
                                echo $currentIssued;
                                ?>

                            </strong>

                            <span>
                                Issued
                            </span>

                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>

</div>



<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>


/* =========================================
   SIDEBAR TOGGLE
========================================= */

const sidebarToggle =
    document.getElementById("sidebarToggle");

const sidebar =
    document.getElementById("adminSidebar");


if (sidebarToggle && sidebar) {

    sidebarToggle.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle("show");

        }
    );

}


/* =========================================
   CLOSE SIDEBAR OUTSIDE
========================================= */

document.addEventListener(
    "click",
    function (event) {

        if (
            window.innerWidth <= 992 &&
            sidebar &&
            sidebar.classList.contains("show") &&
            !sidebar.contains(event.target) &&
            !event.target.closest("#sidebarToggle")
        ) {

            sidebar.classList.remove("show");

        }

    }
);


/* =========================================
   FORM VALIDATION
========================================= */

const editBookForm =
    document.getElementById("editBookForm");


if (editBookForm) {

    editBookForm.addEventListener(
        "submit",
        function (event) {

            const title =
                document
                    .getElementById("title")
                    .value
                    .trim();


            const author =
                document
                    .getElementById("author")
                    .value
                    .trim();


            const category =
                document
                    .getElementById("category_id")
                    .value;


            const quantity =
                parseInt(
                    document
                        .getElementById("quantity")
                        .value
                );


            const minimumQuantity =
                <?php
                echo max(
                    1,
                    $currentIssued
                );
                ?>;


            if (title === "") {

                event.preventDefault();

                alert(
                    "Please enter the book title."
                );

                document
                    .getElementById("title")
                    .focus();

                return;

            }


            if (author === "") {

                event.preventDefault();

                alert(
                    "Please enter the author name."
                );

                document
                    .getElementById("author")
                    .focus();

                return;

            }


            if (category === "") {

                event.preventDefault();

                alert(
                    "Please select a category."
                );

                document
                    .getElementById("category_id")
                    .focus();

                return;

            }


            if (
                isNaN(quantity) ||
                quantity < minimumQuantity
            ) {

                event.preventDefault();

                alert(
                    "Quantity cannot be less than "
                    + minimumQuantity
                    + "."
                );

                document
                    .getElementById("quantity")
                    .focus();

                return;

            }

        }
    );

}

</script>


</body>

</html>