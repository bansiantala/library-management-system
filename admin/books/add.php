<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$message = "";


/* ==========================================
   GET CATEGORIES
========================================== */

$categoryResult = $conn->query(
    "SELECT *
     FROM categories
     ORDER BY category_name ASC"
);


/* ==========================================
   ADD BOOK
========================================== */

if (isset($_POST['add_book'])) {

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

        $stmt = $conn->prepare(
            "INSERT INTO books
            (
                title,
                author,
                category_id,
                isbn,
                quantity,
                available_quantity
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $availableQuantity = $quantity;

        $stmt->bind_param(
            "ssisii",
            $title,
            $author,
            $categoryId,
            $isbn,
            $quantity,
            $availableQuantity
        );


        if ($stmt->execute()) {

            header("Location: index.php");
            exit();

        } else {

            $message = "Failed to add book. Please try again.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Book | Library Management System</title>


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
           ADD BOOK PAGE
        ========================================= */

        .add-book-page {
            padding: 28px;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .add-book-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 25px;
        }

        .add-book-title h2 {
            margin: 0;

            font-size: 25px;
            font-weight: 700;

            color: #172033;
        }

        .add-book-title p {
            margin: 6px 0 0;

            color: #8b95a7;

            font-size: 13px;
        }


        /* Back Button */

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
           FORM LAYOUT
        ========================================= */

        .add-book-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr) 300px;

            gap: 22px;

            align-items: start;
        }


        /* =========================================
           FORM CARD
        ========================================= */

        .add-book-card {
            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.03);
        }


        .add-book-card-header {
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


        .add-book-card-header h5 {
            margin: 0;

            color: #172033;

            font-size: 15px;

            font-weight: 700;
        }


        .add-book-card-header span {
            display: block;

            margin-top: 3px;

            font-size: 11px;

            color: #94a3b8;
        }


        /* =========================================
           FORM BODY
        ========================================= */

        .add-book-card-body {
            padding: 23px;
        }


        /* =========================================
           FORM GROUP
        ========================================= */

        .book-form-group {
            margin-bottom: 19px;
        }

        .book-form-group:last-child {
            margin-bottom: 0;
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


        .book-form-control::placeholder {
            color: #a1a9b6;
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
           TEXTAREA
        ========================================= */

        .book-help-text {
            display: block;

            margin-top: 5px;

            color: #9aa3b2;

            font-size: 10px;
        }


        /* =========================================
           TWO COLUMN FORM
        ========================================= */

        .book-form-row {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;
        }


        /* =========================================
           ALERT
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


        .save-book-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            min-width: 125px;

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

        .save-book-btn:hover {
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
           INFORMATION CARD
        ========================================= */

        .book-info-card {
            background: #ffffff;

            border: 1px solid #e9edf3;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.03);
        }


        .book-info-header {
            padding: 18px;

            border-bottom: 1px solid #edf0f4;

            display: flex;
            align-items: center;

            gap: 10px;
        }


        .book-info-header i {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #f0fdf4;

            color: #059669;

            font-size: 16px;
        }


        .book-info-header h6 {
            margin: 0;

            color: #172033;

            font-size: 13px;

            font-weight: 700;
        }


        .book-info-body {
            padding: 18px;
        }


        .book-info-item {
            display: flex;

            align-items: flex-start;

            gap: 10px;

            margin-bottom: 17px;
        }

        .book-info-item:last-child {
            margin-bottom: 0;
        }


        .book-info-number {
            width: 23px;
            height: 23px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #eff6ff;

            color: #2563eb;

            font-size: 10px;

            font-weight: 700;
        }


        .book-info-item strong {
            display: block;

            color: #475569;

            font-size: 11px;

            margin-bottom: 3px;
        }


        .book-info-item span {
            display: block;

            color: #94a3b8;

            font-size: 10px;

            line-height: 1.5;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1000px) {

            .add-book-layout {
                grid-template-columns: 1fr;
            }

            .book-info-card {
                order: 2;
            }

        }


        @media (max-width: 768px) {

            .add-book-page {
                padding: 20px 15px;
            }

            .add-book-header {
                align-items: flex-start;

                flex-direction: column;

                gap: 12px;
            }

            .book-form-row {
                grid-template-columns: 1fr;
            }

            .back-books-btn {
                width: 100%;

                justify-content: center;
            }

        }


        @media (max-width: 576px) {

            .add-book-title h2 {
                font-size: 21px;
            }

            .add-book-card-body {
                padding: 18px;
            }

            .book-form-actions {
                flex-direction: column;
            }

            .save-book-btn,
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
     ADMIN MAIN
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
                    Add Book
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Books

                    <i class="bi bi-chevron-right"></i>

                    Add Book

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

                <?php if ($totalIssued ?? 0 > 0): ?>

                    <span class="notification-dot"></span>

                <?php endif; ?>

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
         PAGE
    ======================================= -->

    <div class="add-book-page">


        <!-- PAGE HEADER -->

        <div class="add-book-header">

            <div class="add-book-title">

                <h2>
                    Add New Book
                </h2>

                <p>
                    Add a new book to your library collection.
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
             LAYOUT
        =================================== -->

        <div class="add-book-layout">


            <!-- =================================
                 FORM CARD
            ================================== -->

            <div class="add-book-card">


                <!-- HEADER -->

                <div class="add-book-card-header">

                    <div class="form-header-icon">

                        <i class="bi bi-book-half"></i>

                    </div>

                    <div>

                        <h5>
                            Book Information
                        </h5>

                        <span>
                            Enter the details of the new book.
                        </span>

                    </div>

                </div>


                <!-- BODY -->

                <div class="add-book-card-body">


                    <!-- ERROR -->

                    <?php if ($message !== ""): ?>

                        <div class="book-error">

                            <i class="bi bi-exclamation-circle"></i>

                            <span>
                                <?php
                                echo htmlspecialchars($message);
                                ?>
                            </span>

                        </div>

                    <?php endif; ?>



                    <!-- FORM -->

                    <form
                        method="POST"
                        id="addBookForm"
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
                                        $_POST['title'] ?? ''
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
                                        $_POST['author'] ?? ''
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


                                <div
                                    class="book-input-wrapper"
                                >

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
                                                        isset(
                                                            $_POST[
                                                                'category_id'
                                                            ]
                                                        ) &&
                                                        $_POST[
                                                            'category_id'
                                                        ] ==
                                                        $category['id']
                                                    )
                                                    ? 'selected'
                                                    : '';

                                        ?>

                                            <option
                                                value="<?php
                                                echo $category['id'];
                                                ?>"
                                                <?php echo $selected; ?>
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


                                <div
                                    class="book-input-wrapper"
                                >

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
                                            $_POST['isbn'] ?? ''
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

                                Quantity

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div
                                class="book-input-wrapper"
                            >

                                <i
                                    class="bi bi-123 book-input-icon"
                                ></i>

                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    class="book-form-control"
                                    placeholder="Enter quantity"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_POST['quantity'] ?? '1'
                                    );
                                    ?>"
                                    min="1"
                                    max="9999"
                                    required
                                >

                            </div>


                            <small class="book-help-text">

                                All copies will initially be marked as
                                available.

                            </small>

                        </div>



                        <!-- ACTIONS -->

                        <div class="book-form-actions">


                            <!-- SAVE -->

                            <button
                                type="submit"
                                name="add_book"
                                class="save-book-btn"
                            >

                                <i class="bi bi-check-lg"></i>

                                Save Book

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
                 INFORMATION CARD
            ================================== -->

            <div class="book-info-card">


                <div class="book-info-header">

                    <i class="bi bi-info-lg"></i>

                    <h6>
                        Adding a Book
                    </h6>

                </div>


                <div class="book-info-body">


                    <div class="book-info-item">

                        <div class="book-info-number">
                            1
                        </div>

                        <div>

                            <strong>
                                Enter book title
                            </strong>

                            <span>
                                Use the complete and correct title
                                of the book.
                            </span>

                        </div>

                    </div>



                    <div class="book-info-item">

                        <div class="book-info-number">
                            2
                        </div>

                        <div>

                            <strong>
                                Select category
                            </strong>

                            <span>
                                Choose the appropriate category
                                from the list.
                            </span>

                        </div>

                    </div>



                    <div class="book-info-item">

                        <div class="book-info-number">
                            3
                        </div>

                        <div>

                            <strong>
                                Add ISBN
                            </strong>

                            <span>
                                ISBN is optional but useful for
                                identifying books.
                            </span>

                        </div>

                    </div>



                    <div class="book-info-item">

                        <div class="book-info-number">
                            4
                        </div>

                        <div>

                            <strong>
                                Set quantity
                            </strong>

                            <span>
                                Enter the total number of copies
                                available in the library.
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
       CLOSE SIDEBAR ON OUTSIDE CLICK
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

    const addBookForm =
        document.getElementById("addBookForm");

    if (addBookForm) {

        addBookForm.addEventListener(
            "submit",
            function (event) {

                const title =
                    document.getElementById("title").value.trim();

                const author =
                    document.getElementById("author").value.trim();

                const category =
                    document.getElementById("category_id").value;

                const quantity =
                    parseInt(
                        document.getElementById("quantity").value
                    );


                if (title === "") {

                    event.preventDefault();

                    alert("Please enter the book title.");

                    document.getElementById("title").focus();

                    return;

                }


                if (author === "") {

                    event.preventDefault();

                    alert("Please enter the author name.");

                    document.getElementById("author").focus();

                    return;

                }


                if (category === "") {

                    event.preventDefault();

                    alert("Please select a category.");

                    document
                        .getElementById("category_id")
                        .focus();

                    return;

                }


                if (
                    isNaN(quantity) ||
                    quantity <= 0
                ) {

                    event.preventDefault();

                    alert(
                        "Quantity must be greater than 0."
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