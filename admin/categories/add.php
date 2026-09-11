<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$message = "";
$messageType = "";


// =====================================================
// ADD CATEGORY
// =====================================================

if (isset($_POST['add_category'])) {

    $categoryName = trim($_POST['category_name']);


    // Required validation

    if ($categoryName == "") {

        $message = "Category name is required.";
        $messageType = "danger";

    } else {

        // Check duplicate

        $check = $conn->prepare(
            "SELECT id
             FROM categories
             WHERE category_name = ?"
        );

        $check->bind_param(
            "s",
            $categoryName
        );

        $check->execute();

        $checkResult = $check->get_result();


        if ($checkResult->num_rows > 0) {

            $message = "Category already exists.";
            $messageType = "danger";

        } else {

            // Insert category

            $stmt = $conn->prepare(
                "INSERT INTO categories
                 (category_name)
                 VALUES (?)"
            );

            $stmt->bind_param(
                "s",
                $categoryName
            );


            if ($stmt->execute()) {

                header("Location: index.php");
                exit();

            } else {

                $message = "Failed to add category.";
                $messageType = "danger";

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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Category | Library Management System</title>


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


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >


    <style>

        /* =====================================================
           ADD CATEGORY PAGE
        ===================================================== */

        .add-category-page {
            padding: 28px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .add-page-header {
            margin-bottom: 25px;
        }

        .add-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #8a96a3;
            font-size: 13px;
        }

        .add-breadcrumb a {
            color: #5664d2;
            text-decoration: none;
            font-weight: 600;
        }

        .add-breadcrumb a:hover {
            color: #4352c5;
        }

        .add-breadcrumb i {
            font-size: 9px;
        }

        .add-page-header h2 {
            margin: 0;
            color: #182230;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.4px;
        }

        .add-page-header p {
            margin: 7px 0 0;
            color: #7b8794;
            font-size: 14px;
        }


        /* =====================================================
           MAIN GRID
        ===================================================== */

        .add-category-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .add-form-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30, 41, 59, 0.05);
        }

        .add-card-header {
            padding: 20px 23px;
            border-bottom: 1px solid #edf0f4;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .add-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef0ff;
            color: #5664d2;
            font-size: 19px;
        }

        .add-card-header h5 {
            margin: 0;
            color: #202938;
            font-size: 16px;
            font-weight: 800;
        }

        .add-card-header span {
            display: block;
            margin-top: 3px;
            color: #8b96a3;
            font-size: 12px;
        }

        .add-form-body {
            padding: 25px 23px;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .add-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            border: 1px solid #ffd5d5;
            background: #fff2f2;
            color: #c73d3d;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
        }

        .add-alert i {
            font-size: 17px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group-custom {
            margin-bottom: 22px;
        }

        .form-label-custom {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 750;
        }

        .required-star {
            color: #e04f5f;
        }

        .category-input-wrapper {
            position: relative;
        }

        .category-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8d97a4;
            font-size: 16px;
            pointer-events: none;
        }

        .category-input {
            width: 100%;
            height: 48px;
            padding: 0 15px 0 43px;
            border: 1px solid #dfe4ea;
            border-radius: 9px;
            background: #fff;
            color: #273142;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .category-input::placeholder {
            color: #a2aab4;
        }

        .category-input:focus {
            border-color: #5664d2;
            box-shadow: 0 0 0 3px rgba(86, 100, 210, 0.10);
        }

        .category-help {
            margin-top: 7px;
            color: #929ba7;
            font-size: 11px;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .add-form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 5px;
        }

        .save-category-btn {
            height: 43px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(
                135deg,
                #5664d2,
                #4352c5
            );
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 14px rgba(86, 100, 210, 0.20);
            transition: all 0.2s ease;
        }

        .save-category-btn:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 7px 17px rgba(86, 100, 210, 0.28);
        }

        .cancel-category-btn {
            height: 43px;
            padding: 0 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid #dfe4ea;
            border-radius: 8px;
            background: #fff;
            color: #687383;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .cancel-category-btn:hover {
            background: #f7f8fa;
            border-color: #cfd5dc;
            color: #414b59;
        }


        /* =====================================================
           INFORMATION CARD
        ===================================================== */

        .category-info-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30, 41, 59, 0.05);
        }

        .info-card-header {
            padding: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .info-card-header h5 {
            margin: 0;
            color: #202938;
            font-size: 15px;
            font-weight: 800;
        }

        .info-card-header p {
            margin: 5px 0 0;
            color: #929ba7;
            font-size: 12px;
        }

        .info-card-body {
            padding: 20px;
        }


        /* =====================================================
           PREVIEW
        ===================================================== */

        .category-preview {
            padding: 18px;
            background: #f8f9fc;
            border: 1px solid #edf0f4;
            border-radius: 11px;
            text-align: center;
            margin-bottom: 20px;
        }

        .preview-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(
                135deg,
                #eef0ff,
                #e5e8ff
            );
            color: #5664d2;
            font-size: 25px;
        }

        .category-preview small {
            display: block;
            color: #929ba7;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .category-preview strong {
            display: block;
            color: #273142;
            font-size: 15px;
            font-weight: 750;
            word-break: break-word;
        }


        /* =====================================================
           INFO ITEMS
        ===================================================== */

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f1f3ff;
            color: #5664d2;
            font-size: 14px;
        }

        .info-item-text strong {
            display: block;
            color: #354052;
            font-size: 12px;
            font-weight: 750;
        }

        .info-item-text span {
            display: block;
            margin-top: 3px;
            color: #929ba7;
            font-size: 11px;
            line-height: 1.5;
        }


        /* =====================================================
           ADMIN NAVBAR
        ===================================================== */

        .admin-navbar {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid #e9edf2;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .navbar-left {
            display: flex;
            align-items: center;
        }

        .navbar-title h5 {
            margin: 0;
            color: #202938;
            font-size: 17px;
            font-weight: 750;
        }

        .navbar-title span {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 4px;
            color: #98a1ad;
            font-size: 11px;
        }

        .navbar-title span i {
            font-size: 9px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-btn {
            width: 39px;
            height: 39px;
            border: 1px solid #e9edf2;
            background: #fff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #657080;
            font-size: 17px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .notification-btn:hover {
            background: #f7f8fc;
            color: #5664d2;
        }

        .header-divider {
            width: 1px;
            height: 34px;
            background: #e9edf2;
        }

        .nav-admin {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-avatar {
            width: 39px;
            height: 39px;
            border-radius: 10px;
            background: linear-gradient(
                135deg,
                #5664d2,
                #4352c5
            );
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .nav-admin-info strong {
            display: block;
            color: #273142;
            font-size: 13px;
            line-height: 1.2;
        }

        .nav-admin-info small {
            color: #98a1ad;
            font-size: 10px;
        }


        /* =====================================================
           LOGOUT
        ===================================================== */

        .admin-logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            height: 39px;
            padding: 0 13px;
            border-radius: 9px;
            background: #fff2f2;
            border: 1px solid #ffdcdc;
            color: #d84d4d;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .admin-logout-btn:hover {
            background: #ffe2e2;
            border-color: #ffcaca;
            color: #c73535;
        }

        .admin-logout-btn i {
            font-size: 15px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 992px) {

            .add-category-grid {
                grid-template-columns: 1fr;
            }

            .category-info-card {
                order: 2;
            }

            .add-category-page {
                padding: 22px;
            }

        }


        @media (max-width: 768px) {

            .admin-navbar {
                padding: 0 18px;
            }

            .nav-admin-info {
                display: none;
            }

            .header-divider {
                display: none;
            }

            .add-category-page {
                padding: 18px;
            }

        }


        @media (max-width: 576px) {

            .admin-navbar {
                height: 68px;
            }

            .navbar-title h5 {
                font-size: 14px;
            }

            .navbar-title span {
                display: none;
            }

            .notification-btn {
                width: 35px;
                height: 35px;
            }

            .nav-avatar {
                width: 35px;
                height: 35px;
            }

            .admin-logout-btn {
                width: 35px;
                height: 35px;
                padding: 0;
            }

            .admin-logout-btn span {
                display: none;
            }

            .add-page-header h2 {
                font-size: 23px;
            }

            .add-card-header {
                padding: 17px;
            }

            .add-form-body {
                padding: 20px 17px;
            }

            .add-form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .save-category-btn,
            .cancel-category-btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     ADMIN SIDEBAR
===================================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- =====================================================
     MAIN AREA
===================================================== -->

<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================= -->

    <nav class="admin-navbar">


        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Add Category
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Categories

                    <i class="bi bi-chevron-right"></i>

                    Add Category

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <!-- Notification -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >

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


    <!-- =================================================
         PAGE CONTENT
    ================================================= -->

    <div class="add-category-page">


        <!-- =================================================
             PAGE HEADER
        ================================================= -->

        <div class="add-page-header">


            <div class="add-breadcrumb">

                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">

                    <i class="bi bi-house-door"></i>

                    Dashboard

                </a>

                <i class="bi bi-chevron-right"></i>


                <a href="index.php">

                    Categories

                </a>

                <i class="bi bi-chevron-right"></i>


                <span>
                    Add Category
                </span>

            </div>


            <h2>
                Add New Category
            </h2>


            <p>
                Create a new category to organize books in your library.
            </p>


        </div>


        <!-- =================================================
             CONTENT GRID
        ================================================= -->

        <div class="add-category-grid">


            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="add-form-card">


                <div class="add-card-header">


                    <div class="add-card-icon">

                        <i class="bi bi-folder-plus"></i>

                    </div>


                    <div>

                        <h5>
                            Category Information
                        </h5>

                        <span>
                            Enter the details for your new category
                        </span>

                    </div>


                </div>


                <div class="add-form-body">


                    <!-- Error Message -->

                    <?php if ($message != "") { ?>

                        <div class="add-alert">

                            <i class="bi bi-exclamation-circle-fill"></i>

                            <span>

                                <?php
                                echo htmlspecialchars($message);
                                ?>

                            </span>

                        </div>

                    <?php } ?>


                    <!-- Form -->

                    <form
                        method="POST"
                        id="addCategoryForm"
                    >


                        <!-- Category Name -->

                        <div class="form-group-custom">


                            <label
                                for="category_name"
                                class="form-label-custom"
                            >

                                Category Name

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="category-input-wrapper">


                                <i class="bi bi-folder category-input-icon"></i>


                                <input
                                    type="text"
                                    name="category_name"
                                    id="category_name"
                                    class="category-input"
                                    placeholder="Enter category name"
                                    maxlength="100"
                                    autocomplete="off"
                                    required
                                >


                            </div>


                            <div class="category-help">

                                <i class="bi bi-info-circle"></i>

                                Example: Programming, Database, Fiction, History

                            </div>


                        </div>


                        <!-- Buttons -->

                        <div class="add-form-actions">


                            <button
                                type="submit"
                                name="add_category"
                                class="save-category-btn"
                            >

                                <i class="bi bi-check2-circle"></i>

                                Save Category

                            </button>


                            <a
                                href="index.php"
                                class="cancel-category-btn"
                            >

                                <i class="bi bi-x-lg"></i>

                                Cancel

                            </a>


                        </div>


                    </form>


                </div>


            </div>


            <!-- =================================================
                 INFORMATION CARD
            ================================================= -->

            <div class="category-info-card">


                <div class="info-card-header">

                    <h5>
                        Category Preview
                    </h5>

                    <p>
                        Preview your category before saving
                    </p>

                </div>


                <div class="info-card-body">


                    <!-- Preview -->

                    <div class="category-preview">


                        <div class="preview-icon">

                            <i class="bi bi-folder-fill"></i>

                        </div>


                        <small>
                            New Category
                        </small>


                        <strong id="categoryPreview">
                            Category Name
                        </strong>


                    </div>


                    <!-- Information -->

                    <div class="info-item">


                        <div class="info-item-icon">

                            <i class="bi bi-check-circle"></i>

                        </div>


                        <div class="info-item-text">

                            <strong>
                                Unique Name
                            </strong>

                            <span>
                                Each category should have a unique name.
                            </span>

                        </div>


                    </div>


                    <div class="info-item">


                        <div class="info-item-icon">

                            <i class="bi bi-book"></i>

                        </div>


                        <div class="info-item-text">

                            <strong>
                                Organize Books
                            </strong>

                            <span>
                                Categories help users find books easily.
                            </span>

                        </div>


                    </div>


                    <div class="info-item">


                        <div class="info-item-icon">

                            <i class="bi bi-search"></i>

                        </div>


                        <div class="info-item-text">

                            <strong>
                                Easy Searching
                            </strong>

                            <span>
                                Well-defined categories improve book browsing.
                            </span>

                        </div>


                    </div>


                </div>


            </div>


        </div>


    </div>


</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =================================================
       SIDEBAR
    ================================================= */

    const sidebar =
        document.getElementById("adminSidebar");

    const toggleButton =
        document.getElementById("sidebarToggle");


    if (toggleButton && sidebar) {

        toggleButton.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle("show");

            }
        );

    }


    /* Close Sidebar */

    document.addEventListener(
        "click",
        function (event) {

            if (
                window.innerWidth <= 768 &&
                sidebar &&
                sidebar.classList.contains("show") &&
                !sidebar.contains(event.target) &&
                toggleButton &&
                !toggleButton.contains(event.target)
            ) {

                sidebar.classList.remove("show");

            }

        }
    );


    /* =================================================
       CATEGORY PREVIEW
    ================================================= */

    const categoryInput =
        document.getElementById("category_name");

    const categoryPreview =
        document.getElementById("categoryPreview");


    if (categoryInput && categoryPreview) {

        categoryInput.addEventListener(
            "input",
            function () {

                const value =
                    this.value.trim();


                if (value !== "") {

                    categoryPreview.textContent =
                        value;

                } else {

                    categoryPreview.textContent =
                        "Category Name";

                }

            }
        );

    }


    /* =================================================
       FORM VALIDATION
    ================================================= */

    const form =
        document.getElementById("addCategoryForm");


    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const category =
                    categoryInput.value.trim();


                if (category === "") {

                    event.preventDefault();

                    alert(
                        "Please enter category name."
                    );

                    categoryInput.focus();

                    return false;

                }


                if (category.length < 2) {

                    event.preventDefault();

                    alert(
                        "Category name must contain at least 2 characters."
                    );

                    categoryInput.focus();

                    return false;

                }

            }
        );

    }

});

</script>


</body>

</html>