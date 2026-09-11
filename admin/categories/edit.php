<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


// =====================================================
// CHECK CATEGORY ID
// =====================================================

if (!isset($_GET['id'])) {

    header("Location: index.php");
    exit();

}

$id = (int) $_GET['id'];


// =====================================================
// GET CATEGORY
// =====================================================

$stmt = $conn->prepare(
    "SELECT *
     FROM categories
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");
    exit();

}

$category = $result->fetch_assoc();

$message = "";


// =====================================================
// UPDATE CATEGORY
// =====================================================

if (isset($_POST['update_category'])) {

    $categoryName = trim($_POST['category_name']);


    // Required validation

    if ($categoryName == "") {

        $message = "Category name is required.";

    } else {

        // Check duplicate except current category

        $check = $conn->prepare(
            "SELECT id
             FROM categories
             WHERE category_name = ?
             AND id != ?"
        );

        $check->bind_param(
            "si",
            $categoryName,
            $id
        );

        $check->execute();

        $checkResult = $check->get_result();


        if ($checkResult->num_rows > 0) {

            $message = "Category already exists.";

        } else {

            // Update category

            $update = $conn->prepare(
                "UPDATE categories
                 SET category_name = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "si",
                $categoryName,
                $id
            );


            if ($update->execute()) {

                header("Location: index.php");
                exit();

            } else {

                $message = "Failed to update category.";

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

    <title>Edit Category | Library Management System</title>


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
           EDIT CATEGORY PAGE
        ===================================================== */

        .edit-category-page {
            padding: 28px;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .edit-page-header {
            margin-bottom: 25px;
        }

        .edit-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #8a96a3;
            font-size: 13px;
        }

        .edit-breadcrumb a {
            color: #5664d2;
            text-decoration: none;
            font-weight: 600;
        }

        .edit-breadcrumb a:hover {
            color: #4352c5;
        }

        .edit-breadcrumb i {
            font-size: 9px;
        }

        .edit-page-header h2 {
            margin: 0;
            color: #182230;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.4px;
        }

        .edit-page-header p {
            margin: 7px 0 0;
            color: #7b8794;
            font-size: 14px;
        }


        /* =====================================================
           MAIN GRID
        ===================================================== */

        .edit-category-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 22px;
            align-items: start;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .edit-form-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30, 41, 59, 0.05);
        }

        .edit-card-header {
            padding: 20px 23px;
            border-bottom: 1px solid #edf0f4;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .edit-card-icon {
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

        .edit-card-header h5 {
            margin: 0;
            color: #202938;
            font-size: 16px;
            font-weight: 800;
        }

        .edit-card-header span {
            display: block;
            margin-top: 3px;
            color: #8b96a3;
            font-size: 12px;
        }

        .edit-form-body {
            padding: 25px 23px;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .edit-alert {
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

        .edit-alert i {
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

        .edit-form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 5px;
        }

        .update-category-btn {
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

        .update-category-btn:hover {
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
           DETAILS CARD
        ===================================================== */

        .category-details-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30, 41, 59, 0.05);
        }

        .details-header {
            padding: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .details-header h5 {
            margin: 0;
            color: #202938;
            font-size: 15px;
            font-weight: 800;
        }

        .details-header p {
            margin: 5px 0 0;
            color: #929ba7;
            font-size: 12px;
        }

        .details-body {
            padding: 22px 20px;
        }

        .category-preview {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8f9fc;
            border: 1px solid #edf0f4;
            border-radius: 11px;
        }

        .category-preview-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: linear-gradient(
                135deg,
                #eef0ff,
                #e5e8ff
            );
            color: #5664d2;
            font-size: 20px;
        }

        .category-preview-info small {
            display: block;
            margin-bottom: 3px;
            color: #929ba7;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .category-preview-info strong {
            display: block;
            color: #273142;
            font-size: 14px;
            font-weight: 750;
            word-break: break-word;
        }


        /* =====================================================
           DETAIL ITEMS
        ===================================================== */

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #8a95a2;
            font-size: 12px;
        }

        .detail-value {
            color: #354052;
            font-size: 12px;
            font-weight: 700;
            text-align: right;
        }

        .category-id-badge {
            padding: 4px 8px;
            background: #f1f3ff;
            color: #5664d2;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 750;
        }


        /* =====================================================
           INFO BOX
        ===================================================== */

        .category-info-box {
            margin-top: 18px;
            padding: 13px;
            background: #f8f9fc;
            border-radius: 9px;
            display: flex;
            align-items: flex-start;
            gap: 9px;
        }

        .category-info-box i {
            color: #5664d2;
            font-size: 15px;
            margin-top: 1px;
        }

        .category-info-box p {
            margin: 0;
            color: #7d8794;
            font-size: 11px;
            line-height: 1.6;
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

            .edit-category-grid {
                grid-template-columns: 1fr;
            }

            .category-details-card {
                order: 2;
            }

            .edit-category-page {
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

            .edit-category-page {
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

            .edit-page-header h2 {
                font-size: 23px;
            }

            .edit-card-header {
                padding: 17px;
            }

            .edit-form-body {
                padding: 20px 17px;
            }

            .edit-form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .update-category-btn,
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
                    Edit Category
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Categories

                    <i class="bi bi-chevron-right"></i>

                    Edit Category

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

    <div class="edit-category-page">


        <!-- =================================================
             PAGE HEADER
        ================================================= -->

        <div class="edit-page-header">


            <div class="edit-breadcrumb">

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
                    Edit Category
                </span>

            </div>


            <h2>
                Edit Category
            </h2>


            <p>
                Update the category information for your library.
            </p>


        </div>


        <!-- =================================================
             CONTENT GRID
        ================================================= -->

        <div class="edit-category-grid">


            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="edit-form-card">


                <div class="edit-card-header">


                    <div class="edit-card-icon">

                        <i class="bi bi-pencil-square"></i>

                    </div>


                    <div>

                        <h5>
                            Category Information
                        </h5>

                        <span>
                            Modify the category name below
                        </span>

                    </div>


                </div>


                <div class="edit-form-body">


                    <!-- Error Message -->

                    <?php if ($message != "") { ?>

                        <div class="edit-alert">

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
                        id="editCategoryForm"
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
                                    value="<?php
                                    echo htmlspecialchars(
                                        $category['category_name']
                                    );
                                    ?>"
                                    placeholder="Enter category name"
                                    maxlength="100"
                                    required
                                >


                            </div>


                            <div class="category-help">

                                <i class="bi bi-info-circle"></i>

                                Category name should be unique and easy to identify.

                            </div>


                        </div>


                        <!-- Buttons -->

                        <div class="edit-form-actions">


                            <button
                                type="submit"
                                name="update_category"
                                class="update-category-btn"
                            >

                                <i class="bi bi-check2-circle"></i>

                                Update Category

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
                 DETAILS CARD
            ================================================= -->

            <div class="category-details-card">


                <div class="details-header">

                    <h5>
                        Category Details
                    </h5>

                    <p>
                        Current category information
                    </p>

                </div>


                <div class="details-body">


                    <!-- Preview -->

                    <div class="category-preview">


                        <div class="category-preview-icon">

                            <i class="bi bi-folder-fill"></i>

                        </div>


                        <div class="category-preview-info">

                            <small>
                                Category
                            </small>

                            <strong id="categoryPreview">

                                <?php
                                echo htmlspecialchars(
                                    $category['category_name']
                                );
                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- ID -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Category ID
                        </span>

                        <span class="category-id-badge">

                            #<?php echo $category['id']; ?>

                        </span>

                    </div>


                    <!-- Status -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Status
                        </span>

                        <span class="detail-value">
                            Active
                        </span>

                    </div>


                    <!-- Type -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Type
                        </span>

                        <span class="detail-value">
                            Book Category
                        </span>

                    </div>


                    <!-- Information -->

                    <div class="category-info-box">

                        <i class="bi bi-lightbulb"></i>

                        <p>

                            Categories help organize books in the library
                            and make them easier for users to find.

                        </p>

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


    const sidebar =
        document.getElementById("adminSidebar");

    const toggleButton =
        document.getElementById("sidebarToggle");


    /* Sidebar Toggle */

    if (toggleButton && sidebar) {

        toggleButton.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle("show");

            }
        );

    }


    /* Close Sidebar Outside */

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


    /* Live Category Preview */

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


    /* Form Validation */

    const form =
        document.getElementById("editCategoryForm");


    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const name =
                    categoryInput.value.trim();


                if (name === "") {

                    event.preventDefault();

                    alert(
                        "Please enter a category name."
                    );

                    categoryInput.focus();

                    return false;

                }


                if (name.length < 2) {

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