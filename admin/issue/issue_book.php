<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$message = "";
$messageType = "";


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$usersResult = $conn->query(
    "SELECT id, name, email
     FROM users
     WHERE role = 'user'
     ORDER BY name ASC"
);


/*
|--------------------------------------------------------------------------
| Get Available Books
|--------------------------------------------------------------------------
*/

$booksResult = $conn->query(
    "SELECT
        books.id,
        books.title,
        books.author,
        books.available_quantity
     FROM books
     WHERE books.available_quantity > 0
     ORDER BY books.title ASC"
);


/*
|--------------------------------------------------------------------------
| Issue Book
|--------------------------------------------------------------------------
*/

if (isset($_POST['issue_book'])) {

    $userId = (int) $_POST['user_id'];
    $bookId = (int) $_POST['book_id'];
    $issueDate = $_POST['issue_date'];
    $returnDate = $_POST['return_date'];


    if ($userId <= 0) {

        $message = "Please select a user.";
        $messageType = "danger";

    } elseif ($bookId <= 0) {

        $message = "Please select a book.";
        $messageType = "danger";

    } elseif ($issueDate == "") {

        $message = "Please select issue date.";
        $messageType = "danger";

    } elseif ($returnDate == "") {

        $message = "Please select return date.";
        $messageType = "danger";

    } elseif ($returnDate < $issueDate) {

        $message = "Return date cannot be before issue date.";
        $messageType = "danger";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Check Book Availability Again
        |--------------------------------------------------------------------------
        */

        $checkBook = $conn->prepare(
            "SELECT available_quantity
             FROM books
             WHERE id = ?"
        );

        $checkBook->bind_param(
            "i",
            $bookId
        );

        $checkBook->execute();

        $bookResult = $checkBook->get_result();


        if ($bookResult->num_rows !== 1) {

            $message = "Book not found.";
            $messageType = "danger";

        } else {

            $bookData = $bookResult->fetch_assoc();


            if ($bookData['available_quantity'] <= 0) {

                $message = "This book is currently out of stock.";
                $messageType = "danger";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Check Duplicate Active Issue
                |--------------------------------------------------------------------------
                */

                $duplicate = $conn->prepare(
                    "SELECT id
                     FROM issued_books
                     WHERE user_id = ?
                     AND book_id = ?
                     AND status = 'Issued'"
                );

                $duplicate->bind_param(
                    "ii",
                    $userId,
                    $bookId
                );

                $duplicate->execute();

                $duplicateResult = $duplicate->get_result();


                if ($duplicateResult->num_rows > 0) {

                    $message = "This user already has this book.";
                    $messageType = "danger";

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | Start Transaction
                    |--------------------------------------------------------------------------
                    */

                    $conn->begin_transaction();


                    try {


                        /*
                        |--------------------------------------------------------------------------
                        | Insert Issue Record
                        |--------------------------------------------------------------------------
                        */

                        $insert = $conn->prepare(
                            "INSERT INTO issued_books
                            (
                                book_id,
                                user_id,
                                issue_date,
                                return_date,
                                status
                            )
                            VALUES (?, ?, ?, ?, 'Issued')"
                        );


                        $insert->bind_param(
                            "iiss",
                            $bookId,
                            $userId,
                            $issueDate,
                            $returnDate
                        );


                        if (!$insert->execute()) {

                            throw new Exception(
                                "Unable to issue book."
                            );

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Decrease Available Quantity
                        |--------------------------------------------------------------------------
                        */

                        $update = $conn->prepare(
                            "UPDATE books
                             SET available_quantity =
                                 available_quantity - 1
                             WHERE id = ?
                             AND available_quantity > 0"
                        );


                        $update->bind_param(
                            "i",
                            $bookId
                        );


                        if (!$update->execute()) {

                            throw new Exception(
                                "Unable to update book quantity."
                            );

                        }


                        if ($update->affected_rows !== 1) {

                            throw new Exception(
                                "Book is no longer available."
                            );

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Complete Transaction
                        |--------------------------------------------------------------------------
                        */

                        $conn->commit();


                        header(
                            "Location: index.php"
                        );

                        exit();


                    } catch (Exception $e) {

                        $conn->rollback();

                        $message = $e->getMessage();

                        $messageType = "danger";

                    }

                }

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Issue Book | Admin Dashboard</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css">


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css">


    <style>


        /* =========================================================
           ISSUE BOOK PAGE
        ========================================================= */

        .issue-form-page {

            padding: 30px;

        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .issue-form-header {

            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;

            margin-bottom: 25px;

        }


        .issue-form-title h2 {

            margin: 0 0 7px;

            color: #182230;

            font-size: 28px;

            font-weight: 800;

        }


        .issue-form-title p {

            margin: 0;

            color: #7b8794;

            font-size: 14px;

        }


        /* =========================================================
           BACK BUTTON
        ========================================================= */

        .back-issue-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            border: 1px solid #e2e8f0;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: all 0.2s ease;

        }


        .back-issue-btn:hover {

            background: #f8fafc;

            color: #2563eb;

            border-color: #bfdbfe;

        }


        /* =========================================================
           MAIN GRID
        ========================================================= */

        .issue-form-grid {

            display: grid;

            grid-template-columns: minmax(0, 1fr) 320px;

            gap: 22px;

            align-items: start;

        }


        /* =========================================================
           FORM CARD
        ========================================================= */

        .issue-form-card {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 15px;

            padding: 28px;

            box-shadow:
                0 4px 18px rgba(15, 23, 42, 0.05);

        }


        .form-card-heading {

            display: flex;

            align-items: center;

            gap: 13px;

            padding-bottom: 20px;

            margin-bottom: 22px;

            border-bottom: 1px solid #edf0f4;

        }


        .form-heading-icon {

            width: 46px;

            height: 46px;

            min-width: 46px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 21px;

        }


        .form-card-heading h4 {

            margin: 0;

            color: #1f2937;

            font-size: 18px;

            font-weight: 800;

        }


        .form-card-heading p {

            margin: 4px 0 0;

            color: #94a3b8;

            font-size: 12px;

        }


        /* =========================================================
           FORM GROUP
        ========================================================= */

        .issue-form-group {

            margin-bottom: 21px;

        }


        .issue-form-label {

            display: block;

            margin-bottom: 8px;

            color: #334155;

            font-size: 13px;

            font-weight: 750;

        }


        .issue-form-label .required {

            color: #dc2626;

        }


        .issue-input-wrap {

            position: relative;

        }


        .issue-input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 16px;

            pointer-events: none;

            z-index: 2;

        }


        .issue-form-control {

            width: 100%;

            min-height: 46px;

            padding: 10px 13px 10px 43px;

            border: 1px solid #dbe2ea;

            border-radius: 9px;

            background: #ffffff;

            color: #334155;

            font-size: 13px;

            outline: none;

            transition: all 0.2s ease;

        }


        .issue-form-control:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.10);

        }


        select.issue-form-control {

            cursor: pointer;

        }


        .issue-help-text {

            display: block;

            margin-top: 6px;

            color: #94a3b8;

            font-size: 11px;

        }


        /* =========================================================
           DATE GRID
        ========================================================= */

        .date-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;

        }


        /* =========================================================
           ALERT
        ========================================================= */

        .issue-alert {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            border-radius: 10px;

            margin-bottom: 22px;

            padding: 12px 14px;

            font-size: 13px;

        }


        .issue-alert i {

            font-size: 17px;

            margin-top: 1px;

        }


        /* =========================================================
           FORM ACTIONS
        ========================================================= */

        .issue-form-actions {

            display: flex;

            align-items: center;

            gap: 10px;

            padding-top: 5px;

        }


        .issue-submit-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 44px;

            padding: 10px 20px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-size: 13px;

            font-weight: 750;

            box-shadow:
                0 7px 17px
                rgba(37, 99, 235, 0.20);

            transition: all 0.2s ease;

        }


        .issue-submit-btn:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 22px
                rgba(37, 99, 235, 0.28);

        }


        .issue-cancel-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 44px;

            padding: 10px 18px;

            border: 1px solid #dbe2ea;

            border-radius: 9px;

            background: #ffffff;

            color: #475569;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: all 0.2s ease;

        }


        .issue-cancel-btn:hover {

            background: #f8fafc;

            color: #1f2937;

        }


        /* =========================================================
           INFORMATION CARD
        ========================================================= */

        .issue-info-card {

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 15px;

            padding: 22px;

            box-shadow:
                0 4px 18px
                rgba(15, 23, 42, 0.05);

        }


        .info-card-heading {

            display: flex;

            align-items: center;

            gap: 10px;

            padding-bottom: 16px;

            margin-bottom: 17px;

            border-bottom: 1px solid #edf0f4;

        }


        .info-card-heading i {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #fff7ed;

            color: #ea580c;

            font-size: 17px;

        }


        .info-card-heading h5 {

            margin: 0;

            color: #1f2937;

            font-size: 15px;

            font-weight: 800;

        }


        /* =========================================================
           INFO ITEMS
        ========================================================= */

        .issue-info-item {

            display: flex;

            align-items: flex-start;

            gap: 11px;

            padding: 13px 0;

            border-bottom: 1px solid #f1f5f9;

        }


        .issue-info-item:last-child {

            border-bottom: none;

        }


        .issue-info-item-icon {

            width: 31px;

            height: 31px;

            min-width: 31px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #f8fafc;

            color: #64748b;

            font-size: 14px;

        }


        .issue-info-item strong {

            display: block;

            color: #334155;

            font-size: 12px;

            font-weight: 750;

            margin-bottom: 3px;

        }


        .issue-info-item span {

            display: block;

            color: #94a3b8;

            font-size: 11px;

            line-height: 1.5;

        }


        /* =========================================================
           NOTE BOX
        ========================================================= */

        .issue-note {

            margin-top: 18px;

            padding: 13px;

            border-radius: 10px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

        }


        .issue-note-title {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #1d4ed8;

            font-size: 12px;

            font-weight: 800;

            margin-bottom: 5px;

        }


        .issue-note p {

            margin: 0;

            color: #64748b;

            font-size: 11px;

            line-height: 1.6;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .issue-form-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 992px) {

            .issue-form-page {

                padding: 25px 20px;

            }

        }


        @media (max-width: 768px) {

            .issue-form-page {

                padding: 20px 15px;

            }


            .issue-form-header {

                flex-direction: column;

            }


            .back-issue-btn {

                width: 100%;

                justify-content: center;

            }


            .issue-form-card {

                padding: 22px;

            }


            .date-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 576px) {

            .issue-form-title h2 {

                font-size: 23px;

            }


            .issue-form-card {

                padding: 18px;

            }


            .issue-form-actions {

                flex-direction: column;

            }


            .issue-submit-btn,
            .issue-cancel-btn {

                width: 100%;

            }

        }


        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            .admin-sidebar,
            .admin-navbar,
            .back-issue-btn,
            .issue-info-card,
            .issue-form-actions {

                display: none !important;

            }


            .admin-main {

                margin-left: 0 !important;

            }


            .issue-form-page {

                padding: 10px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Issue Book / Admin Dashboard
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Issue & Return

                    <i class="bi bi-chevron-right"></i>

                    Issue Book

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <!-- Notification -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications">

                <i class="bi bi-bell"></i>

            </button>


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
                class="admin-logout-btn">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </nav>


    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <main class="issue-form-page">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="issue-form-header">

            <div class="issue-form-title">

                <h2>
                    Issue New Book
                </h2>

                <p>
                    Issue an available library book to a registered user.
                </p>

            </div>


            <a
                href="index.php"
                class="back-issue-btn">

                <i class="bi bi-arrow-left"></i>

                Back to Issue Records

            </a>

        </div>


        <!-- =================================================
             MAIN GRID
        ================================================== -->

        <div class="issue-form-grid">


            <!-- =================================================
                 FORM CARD
            ================================================== -->

            <div class="issue-form-card">


                <div class="form-card-heading">

                    <div class="form-heading-icon">

                        <i class="bi bi-book-half"></i>

                    </div>

                    <div>

                        <h4>
                            Issue Book Details
                        </h4>

                        <p>
                            Select the user, book and issue period.
                        </p>

                    </div>

                </div>


                <!-- ALERT -->

                <?php if ($message != ""): ?>

                    <div
                        class="alert alert-<?php echo $messageType; ?> issue-alert">

                        <?php if ($messageType === "danger"): ?>

                            <i class="bi bi-exclamation-triangle-fill"></i>

                        <?php else: ?>

                            <i class="bi bi-check-circle-fill"></i>

                        <?php endif; ?>

                        <div>

                            <?php
                            echo htmlspecialchars($message);
                            ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    method="POST"
                    id="issueBookForm">


                    <!-- USER -->

                    <div class="issue-form-group">

                        <label class="issue-form-label">

                            Select User

                            <span class="required">*</span>

                        </label>


                        <div class="issue-input-wrap">

                            <i class="bi bi-person issue-input-icon"></i>

                            <select
                                name="user_id"
                                class="issue-form-control"
                                required>

                                <option value="">
                                    Select a user
                                </option>


                                <?php

                                if ($usersResult) {

                                    while (
                                        $user =
                                        $usersResult->fetch_assoc()
                                    ) {

                                ?>

                                    <option
                                        value="<?php echo $user['id']; ?>"
                                        <?php
                                        echo (
                                            isset($_POST['user_id']) &&
                                            $_POST['user_id'] == $user['id']
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>>

                                        <?php
                                        echo htmlspecialchars(
                                            $user['name']
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo htmlspecialchars(
                                            $user['email']
                                        );
                                        ?>

                                    </option>

                                <?php

                                    }

                                }

                                ?>

                            </select>

                        </div>

                        <span class="issue-help-text">

                            Select the registered library member who will receive the book.

                        </span>

                    </div>


                    <!-- BOOK -->

                    <div class="issue-form-group">

                        <label class="issue-form-label">

                            Select Book

                            <span class="required">*</span>

                        </label>


                        <div class="issue-input-wrap">

                            <i class="bi bi-book issue-input-icon"></i>

                            <select
                                name="book_id"
                                class="issue-form-control"
                                required>

                                <option value="">
                                    Select an available book
                                </option>


                                <?php

                                if ($booksResult) {

                                    while (
                                        $book =
                                        $booksResult->fetch_assoc()
                                    ) {

                                ?>

                                    <option
                                        value="<?php echo $book['id']; ?>"
                                        <?php
                                        echo (
                                            isset($_POST['book_id']) &&
                                            $_POST['book_id'] == $book['id']
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>>

                                        <?php
                                        echo htmlspecialchars(
                                            $book['title']
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo htmlspecialchars(
                                            $book['author']
                                        );
                                        ?>

                                        | Available:

                                        <?php
                                        echo $book[
                                            'available_quantity'
                                        ];
                                        ?>

                                    </option>

                                <?php

                                    }

                                }

                                ?>

                            </select>

                        </div>

                        <span class="issue-help-text">

                            Only books with available quantity are displayed.

                        </span>

                    </div>


                    <!-- DATES -->

                    <div class="date-grid">


                        <!-- ISSUE DATE -->

                        <div class="issue-form-group">

                            <label class="issue-form-label">

                                Issue Date

                                <span class="required">*</span>

                            </label>


                            <div class="issue-input-wrap">

                                <i class="bi bi-calendar-event issue-input-icon"></i>

                                <input
                                    type="date"
                                    name="issue_date"
                                    id="issueDate"
                                    class="issue-form-control"
                                    value="<?php
                                    echo isset($_POST['issue_date'])
                                        ? htmlspecialchars($_POST['issue_date'])
                                        : date('Y-m-d');
                                    ?>"
                                    required>

                            </div>

                        </div>


                        <!-- RETURN DATE -->

                        <div class="issue-form-group">

                            <label class="issue-form-label">

                                Expected Return Date

                                <span class="required">*</span>

                            </label>


                            <div class="issue-input-wrap">

                                <i class="bi bi-calendar-check issue-input-icon"></i>

                                <input
                                    type="date"
                                    name="return_date"
                                    id="returnDate"
                                    class="issue-form-control"
                                    value="<?php
                                    echo isset($_POST['return_date'])
                                        ? htmlspecialchars($_POST['return_date'])
                                        : '';
                                    ?>"
                                    required>

                            </div>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="issue-form-actions">

                        <button
                            type="submit"
                            name="issue_book"
                            class="issue-submit-btn">

                            <i class="bi bi-check-circle"></i>

                            Issue Book

                        </button>


                        <a
                            href="index.php"
                            class="issue-cancel-btn">

                            <i class="bi bi-x-circle me-1"></i>

                            Cancel

                        </a>

                    </div>


                </form>


            </div>


            <!-- =================================================
                 INFORMATION CARD
            ================================================== -->

            <div class="issue-info-card">


                <div class="info-card-heading">

                    <i class="bi bi-info-circle"></i>

                    <h5>
                        Issue Book Guidelines
                    </h5>

                </div>


                <!-- INFO 1 -->

                <div class="issue-info-item">

                    <div class="issue-info-item-icon">

                        <i class="bi bi-person-check"></i>

                    </div>

                    <div>

                        <strong>
                            Registered User
                        </strong>

                        <span>
                            Only registered users can receive library books.
                        </span>

                    </div>

                </div>


                <!-- INFO 2 -->

                <div class="issue-info-item">

                    <div class="issue-info-item-icon">

                        <i class="bi bi-book"></i>

                    </div>

                    <div>

                        <strong>
                            Available Books
                        </strong>

                        <span>
                            Only books with available quantity can be issued.
                        </span>

                    </div>

                </div>


                <!-- INFO 3 -->

                <div class="issue-info-item">

                    <div class="issue-info-item-icon">

                        <i class="bi bi-calendar-check"></i>

                    </div>

                    <div>

                        <strong>
                            Return Date
                        </strong>

                        <span>
                            The return date must be on or after the issue date.
                        </span>

                    </div>

                </div>


                <!-- INFO 4 -->

                <div class="issue-info-item">

                    <div class="issue-info-item-icon">

                        <i class="bi bi-arrow-down-circle"></i>

                    </div>

                    <div>

                        <strong>
                            Book Quantity
                        </strong>

                        <span>
                            Available quantity automatically decreases after issuing.
                        </span>

                    </div>

                </div>


                <!-- NOTE -->

                <div class="issue-note">

                    <div class="issue-note-title">

                        <i class="bi bi-lightbulb"></i>

                        Important

                    </div>

                    <p>

                        A user cannot have the same book issued more than once
                        while the previous issue is still active.

                    </p>

                </div>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /*
    |--------------------------------------------------------------------------
    | Date Validation
    |--------------------------------------------------------------------------
    */

    const issueDate =
        document.getElementById("issueDate");

    const returnDate =
        document.getElementById("returnDate");


    if (issueDate && returnDate) {

        function updateReturnDate() {

            returnDate.min = issueDate.value;

        }


        updateReturnDate();


        issueDate.addEventListener(
            "change",
            updateReturnDate
        );


        returnDate.addEventListener(
            "change",
            function () {

                if (
                    issueDate.value &&
                    returnDate.value &&
                    returnDate.value < issueDate.value
                ) {

                    alert(
                        "Return date cannot be before issue date."
                    );

                    returnDate.value = "";

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Form Submit Validation
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById("issueBookForm");


    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                if (
                    issueDate &&
                    returnDate &&
                    issueDate.value &&
                    returnDate.value &&
                    returnDate.value < issueDate.value
                ) {

                    event.preventDefault();

                    alert(
                        "Return date cannot be before issue date."
                    );

                    returnDate.focus();

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Sidebar Mobile Toggle
    |--------------------------------------------------------------------------
    */

    const sidebar =
        document.getElementById("adminSidebar");

    const overlay =
        document.querySelector(".sidebar-overlay");

    const toggleButton =
        document.querySelector(".sidebar-toggle");


    if (toggleButton && sidebar) {

        toggleButton.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle("show");

                if (overlay) {

                    overlay.classList.toggle("show");

                }

            }
        );

    }


    if (overlay && sidebar) {

        overlay.addEventListener(
            "click",
            function () {

                sidebar.classList.remove("show");

                overlay.classList.remove("show");

            }
        );

    }

});

</script>


</body>

</html>