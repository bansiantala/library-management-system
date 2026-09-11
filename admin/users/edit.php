<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];

/* =========================================
   GET USER
========================================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM users
     WHERE id = ?
     AND role = 'user'"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();

$message = "";

/* =========================================
   UPDATE USER
========================================= */

if (isset($_POST['update_user'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name == "" || $email == "") {

        $message = "Name and email are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email.";

    } else {

        /* Check duplicate email */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?"
        );

        $check->bind_param("si", $email, $id);
        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $message = "Email already exists.";

        } else {

            $update = $conn->prepare(
                "UPDATE users
                 SET
                    name = ?,
                    email = ?,
                    phone = ?
                 WHERE id = ?
                 AND role = 'user'"
            );

            $update->bind_param(
                "sssi",
                $name,
                $email,
                $phone,
                $id
            );

            if ($update->execute()) {

                header("Location: index.php");
                exit();

            } else {

                $message = "Failed to update user.";
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

    <title>Edit User | Library Management System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
   EDIT USER PAGE
========================================================= */

.edit-user-page {
    width: 100%;
    max-width: 1700px;
    margin: 0 auto;
    padding: 30px 35px 55px;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 25px;
    margin-bottom: 32px;
}

.page-heading h2 {
    margin: 0;
    font-size: 34px;
    line-height: 1.2;
    font-weight: 800;
    color: #172033;
}

.page-heading p {
    margin: 9px 0 0;
    color: #7b8498;
    font-size: 15px;
    line-height: 1.6;
}

.breadcrumb-custom {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 12px;
    color: #8b94a7;
    font-size: 13px;
}

.breadcrumb-custom i {
    font-size: 10px;
}

.breadcrumb-custom .current {
    color: #4f46e5;
    font-weight: 700;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;

    min-width: 155px;
    height: 48px;

    padding: 0 20px;

    border-radius: 11px;

    background: #ffffff;
    border: 1px solid #e1e5ec;

    color: #4b5563;
    text-decoration: none;

    font-size: 14px;
    font-weight: 700;

    transition: all 0.25s ease;
}

.back-btn:hover {
    background: #f8f9ff;
    color: #4f46e5;
    border-color: #c7d2fe;
    transform: translateY(-2px);
}


/* =========================================================
   MAIN LAYOUT
========================================================= */

.edit-user-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        390px;

    gap: 30px;

    align-items: start;
}


/* =========================================================
   COMMON CARD
========================================================= */

.edit-card,
.user-info-card {

    background: #ffffff;

    border: 1px solid #e7eaf0;

    border-radius: 18px;

    box-shadow:
        0 8px 30px rgba(30, 41, 59, 0.07);

}


/* =========================================================
   EDIT CARD
========================================================= */

.edit-card {
    padding: 38px;
}


/* =========================================================
   CARD TITLE
========================================================= */

.card-title-area {

    display: flex;
    align-items: center;

    gap: 17px;

    padding-bottom: 25px;
    margin-bottom: 28px;

    border-bottom: 1px solid #edf0f5;
}

.card-title-icon {

    width: 58px;
    height: 58px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #e0e7ff
        );

    color: #4f46e5;

    font-size: 26px;
}

.card-title-area h4 {

    margin: 0;

    color: #172033;

    font-size: 22px;

    font-weight: 800;
}

.card-title-area span {

    display: block;

    margin-top: 5px;

    color: #8a93a6;

    font-size: 14px;
}


/* =========================================================
   ALERT
========================================================= */

.custom-alert {

    display: flex;
    align-items: flex-start;

    gap: 13px;

    padding: 15px 17px;

    margin-bottom: 27px;

    border-radius: 11px;

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

    font-size: 14px;

    line-height: 1.5;
}

.custom-alert i {
    font-size: 18px;
    margin-top: 1px;
}


/* =========================================================
   FORM GRID
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 0 25px;
}


/* =========================================================
   FORM GROUP
========================================================= */

.form-group-custom {
    margin-bottom: 25px;
}

.form-label-custom {

    display: block;

    margin-bottom: 9px;

    color: #374151;

    font-size: 14px;

    font-weight: 700;
}

.required-star {
    color: #ef4444;
}


/* =========================================================
   INPUT
========================================================= */

.input-wrapper {
    position: relative;
}

.input-wrapper > i {

    position: absolute;

    left: 17px;
    top: 50%;

    transform: translateY(-50%);

    color: #9ca3af;

    font-size: 18px;

    z-index: 2;
}

.custom-input {

    width: 100%;

    height: 54px;

    padding:
        0 17px 0 49px;

    border:
        1px solid #dfe3eb;

    border-radius: 11px;

    background: #ffffff;

    color: #1f2937;

    font-size: 15px;

    outline: none;

    transition: all 0.25s ease;
}

.custom-input::placeholder {
    color: #a4acba;
}

.custom-input:hover {
    border-color: #cbd5e1;
}

.custom-input:focus {

    border-color: #818cf8;

    box-shadow:
        0 0 0 4px
        rgba(99, 102, 241, 0.10);
}

.field-help {

    margin-top: 7px;

    color: #8b94a7;

    font-size: 12px;

    line-height: 1.5;
}


/* =========================================================
   FORM ACTIONS
========================================================= */

.form-actions {

    display: flex;
    align-items: center;

    gap: 13px;

    padding-top: 27px;
    margin-top: 4px;

    border-top:
        1px solid #edf0f5;
}

.update-btn {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 9px;

    min-width: 165px;

    height: 50px;

    padding: 0 24px;

    border: none;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #6366f1
        );

    color: #ffffff;

    font-size: 14px;

    font-weight: 700;

    transition: all 0.25s ease;

    cursor: pointer;
}

.update-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 9px 22px
        rgba(79, 70, 229, 0.25);
}

.cancel-btn {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 9px;

    min-width: 115px;

    height: 50px;

    padding: 0 20px;

    border:
        1px solid #dfe3eb;

    border-radius: 11px;

    background: #ffffff;

    color: #5f687a;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

    transition: all 0.25s ease;
}

.cancel-btn:hover {

    background: #f8fafc;

    color: #374151;

    border-color: #cbd5e1;
}


/* =========================================================
   USER DETAILS CARD
========================================================= */

.user-info-card {
    overflow: hidden;
}


/* =========================================================
   INFO HEADER
========================================================= */

.info-card-header {

    padding: 27px 28px;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #6366f1
        );

    color: #ffffff;
}

.info-card-header h5 {

    margin: 0;

    font-size: 19px;

    font-weight: 800;
}

.info-card-header p {

    margin: 6px 0 0;

    color:
        rgba(255,255,255,0.78);

    font-size: 13px;
}


/* =========================================================
   USER PREVIEW
========================================================= */

.user-preview {

    padding: 32px 28px 28px;

    text-align: center;

    border-bottom:
        1px solid #edf0f5;
}

.user-preview-avatar {

    width: 100px;
    height: 100px;

    margin: 0 auto 17px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #e0e7ff
        );

    color: #4f46e5;

    font-size: 39px;

    font-weight: 800;

    border:
        5px solid #f8f9ff;

    box-shadow:
        0 6px 20px
        rgba(79,70,229,0.10);
}

.user-preview h4 {

    margin: 0;

    color: #1f2937;

    font-size: 21px;

    font-weight: 800;
}

.user-preview span {

    display: block;

    margin-top: 6px;

    color: #8b94a7;

    font-size: 13px;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   USER INFORMATION LIST
========================================================= */

.user-info-list {

    padding:
        10px 28px 22px;
}

.user-info-item {

    display: flex;

    align-items: center;

    gap: 14px;

    padding: 17px 0;

    border-bottom:
        1px solid #f0f2f6;
}

.user-info-item:last-child {
    border-bottom: none;
}


/* =========================================================
   INFO ICON
========================================================= */

.info-icon {

    width: 43px;
    height: 43px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background: #f5f7ff;

    color: #4f46e5;

    font-size: 18px;
}

.info-content {
    min-width: 0;
}

.info-content small {

    display: block;

    color: #9aa2b2;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}

.info-content strong {

    display: block;

    margin-top: 4px;

    color: #374151;

    font-size: 14px;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   STATUS
========================================================= */

.user-status {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 6px 12px;

    border-radius: 20px;

    background: #ecfdf5;

    color: #059669;

    font-size: 12px;

    font-weight: 700;
}

.user-status i {
    font-size: 8px;
}


/* =========================================================
   LARGE DESKTOP
========================================================= */

@media (min-width: 1500px) {

    .edit-user-layout {
        grid-template-columns:
            minmax(0, 1fr)
            420px;

        gap: 35px;
    }

    .edit-card {
        padding: 42px;
    }

    .user-info-list {
        padding-left: 32px;
        padding-right: 32px;
    }

    .info-card-header {
        padding: 30px 32px;
    }

}


/* =========================================================
   LAPTOP
========================================================= */

@media (max-width: 1200px) {

    .edit-user-page {
        padding-left: 25px;
        padding-right: 25px;
    }

    .edit-user-layout {
        grid-template-columns:
            minmax(0, 1fr)
            350px;

        gap: 24px;
    }

    .edit-card {
        padding: 30px;
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1000px) {

    .edit-user-layout {

        grid-template-columns: 1fr;

    }

    .user-info-card {

        width: 100%;

    }

    .page-header-section {
        align-items: flex-start;
    }

}


/* =========================================================
   SMALL TABLET
========================================================= */

@media (max-width: 768px) {

    .edit-user-page {

        padding:
            25px 20px 45px;
    }

    .page-header-section {

        flex-direction: column;

        align-items: flex-start;

        margin-bottom: 25px;
    }

    .page-heading h2 {
        font-size: 29px;
    }

    .page-heading p {
        font-size: 14px;
    }

    .back-btn {
        width: 100%;
    }

    .edit-card {
        padding: 25px;
    }

    .form-grid {

        grid-template-columns: 1fr;

    }

    .card-title-area h4 {
        font-size: 20px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 576px) {

    .edit-user-page {

        padding:
            20px 15px 35px;
    }

    .page-heading h2 {
        font-size: 25px;
    }

    .breadcrumb-custom {
        font-size: 12px;
    }

    .edit-card {
        padding: 20px;
        border-radius: 14px;
    }

    .user-info-card {
        border-radius: 14px;
    }

    .card-title-area {

        align-items: flex-start;

        gap: 12px;

        padding-bottom: 20px;
        margin-bottom: 22px;
    }

    .card-title-icon {

        width: 50px;
        height: 50px;

        border-radius: 12px;

        font-size: 22px;
    }

    .card-title-area h4 {
        font-size: 18px;
    }

    .card-title-area span {
        font-size: 12px;
    }

    .custom-input {
        height: 52px;
        font-size: 14px;
    }

    .form-actions {

        flex-direction: column;

        align-items: stretch;
    }

    .update-btn,
    .cancel-btn {

        width: 100%;
    }

    .info-card-header {
        padding: 23px;
    }

    .user-preview {
        padding:
            28px 20px 24px;
    }

    .user-preview-avatar {

        width: 88px;
        height: 88px;

        font-size: 34px;
    }

    .user-preview h4 {
        font-size: 19px;
    }

    .user-info-list {
        padding:
            8px 20px 18px;
    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    .admin-sidebar,
    .admin-navbar,
    .back-btn,
    .form-actions {
        display: none !important;
    }

    .admin-main {
        margin-left: 0 !important;
    }

    .edit-user-page {
        max-width: 100%;
        padding: 20px;
    }

    .edit-user-layout {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Edit User / Admin Dashboard
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Users

                    <i class="bi bi-chevron-right"></i>

                    Edit User

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

    <div class="admin-content edit-user-page">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header-section">


            <div class="page-heading">

                <h2>
                    Edit User
                </h2>

                <p>
                    Update the selected user's account information.
                </p>


                <div class="breadcrumb-custom">

                    <span>

                        <i class="bi bi-house-door"></i>

                        Home

                    </span>

                    <i class="bi bi-chevron-right"></i>

                    <span>
                        Users
                    </span>

                    <i class="bi bi-chevron-right"></i>

                    <span class="current">
                        Edit User
                    </span>

                </div>

            </div>


            <a
                href="index.php"
                class="back-btn">

                <i class="bi bi-arrow-left"></i>

                Back to Users

            </a>


        </div>



        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <div class="edit-user-layout">


            <!-- =================================================
                 EDIT FORM
            ================================================== -->

            <div class="edit-card">


                <!-- Card Header -->

                <div class="card-title-area">

                    <div class="card-title-icon">

                        <i class="bi bi-person-gear"></i>

                    </div>


                    <div>

                        <h4>
                            User Information
                        </h4>

                        <span>
                            Modify the user's basic account details
                        </span>

                    </div>

                </div>



                <!-- Error Message -->

                <?php if ($message != "") { ?>

                    <div class="custom-alert">

                        <i class="bi bi-exclamation-circle-fill"></i>

                        <div>

                            <?php

                            echo htmlspecialchars($message);

                            ?>

                        </div>

                    </div>

                <?php } ?>



                <!-- FORM -->

                <form
                    method="POST"
                    id="editUserForm">


                    <div class="form-grid">


                        <!-- =================================================
                             NAME
                        ================================================== -->

                        <div class="form-group-custom">

                            <label class="form-label-custom">

                                Full Name

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="input-wrapper">

                                <i class="bi bi-person"></i>

                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="custom-input"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $user['name']
                                    );

                                    ?>"
                                    placeholder="Enter full name"
                                    required>

                            </div>


                            <div class="field-help">

                                Enter the user's full name.

                            </div>

                        </div>



                        <!-- =================================================
                             EMAIL
                        ================================================== -->

                        <div class="form-group-custom">

                            <label class="form-label-custom">

                                Email Address

                                <span class="required-star">
                                    *
                                </span>

                            </label>


                            <div class="input-wrapper">

                                <i class="bi bi-envelope"></i>

                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="custom-input"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $user['email']
                                    );

                                    ?>"
                                    placeholder="Enter email address"
                                    required>

                            </div>


                            <div class="field-help">

                                This email must be unique in the system.

                            </div>

                        </div>



                        <!-- =================================================
                             PHONE
                        ================================================== -->

                        <div class="form-group-custom">

                            <label class="form-label-custom">

                                Phone Number

                            </label>


                            <div class="input-wrapper">

                                <i class="bi bi-telephone"></i>

                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    class="custom-input"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $user['phone'] ?? ''
                                    );

                                    ?>"
                                    placeholder="Enter phone number">

                            </div>


                            <div class="field-help">

                                Phone number is optional.

                            </div>

                        </div>


                    </div>



                    <!-- =================================================
                         BUTTONS
                    ================================================== -->

                    <div class="form-actions">


                        <button
                            type="submit"
                            name="update_user"
                            class="update-btn">

                            <i class="bi bi-check2-circle"></i>

                            Update User

                        </button>


                        <a
                            href="index.php"
                            class="cancel-btn">

                            <i class="bi bi-x-circle"></i>

                            Cancel

                        </a>


                    </div>


                </form>


            </div>



            <!-- =================================================
                 USER DETAILS
            ================================================== -->

            <div class="user-info-card">


                <!-- Header -->

                <div class="info-card-header">

                    <h5>
                        User Details
                    </h5>

                    <p>
                        Current account information
                    </p>

                </div>



                <!-- User Preview -->

                <div class="user-preview">


                    <div class="user-preview-avatar">

                        <?php

                        echo strtoupper(
                            substr(
                                $user['name'],
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <h4>

                        <?php

                        echo htmlspecialchars(
                            $user['name']
                        );

                        ?>

                    </h4>


                    <span>

                        <?php

                        echo htmlspecialchars(
                            $user['email']
                        );

                        ?>

                    </span>


                </div>



                <!-- Details -->

                <div class="user-info-list">


                    <!-- USER ID -->

                    <div class="user-info-item">

                        <div class="info-icon">

                            <i class="bi bi-hash"></i>

                        </div>


                        <div class="info-content">

                            <small>
                                User ID
                            </small>

                            <strong>

                                #

                                <?php

                                echo $user['id'];

                                ?>

                            </strong>

                        </div>

                    </div>



                    <!-- EMAIL -->

                    <div class="user-info-item">

                        <div class="info-icon">

                            <i class="bi bi-envelope"></i>

                        </div>


                        <div class="info-content">

                            <small>
                                Email
                            </small>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $user['email']
                                );

                                ?>

                            </strong>

                        </div>

                    </div>



                    <!-- PHONE -->

                    <div class="user-info-item">

                        <div class="info-icon">

                            <i class="bi bi-telephone"></i>

                        </div>


                        <div class="info-content">

                            <small>
                                Phone
                            </small>

                            <strong>

                                <?php

                                echo !empty($user['phone'])

                                    ? htmlspecialchars(
                                        $user['phone']
                                    )

                                    : 'Not provided';

                                ?>

                            </strong>

                        </div>

                    </div>



                    <!-- ROLE -->

                    <div class="user-info-item">

                        <div class="info-icon">

                            <i class="bi bi-shield-check"></i>

                        </div>


                        <div class="info-content">

                            <small>
                                Account Type
                            </small>

                            <strong>
                                Library User
                            </strong>

                        </div>

                    </div>



                    <!-- STATUS -->

                    <div class="user-info-item">

                        <div class="info-icon">

                            <i class="bi bi-person-check"></i>

                        </div>


                        <div class="info-content">

                            <small>
                                Status
                            </small>

                            <strong>

                                <span class="user-status">

                                    <i class="bi bi-circle-fill"></i>

                                    Active

                                </span>

                            </strong>

                        </div>

                    </div>


                </div>


            </div>


        </div>


    </div>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const form =
            document.getElementById("editUserForm");

        const name =
            document.getElementById("name");

        const email =
            document.getElementById("email");

        const phone =
            document.getElementById("phone");


        /* =========================================
           FORM VALIDATION
        ========================================= */

        if (form) {

            form.addEventListener(
                "submit",
                function (e) {

                    const nameValue =
                        name.value.trim();

                    const emailValue =
                        email.value.trim();


                    if (nameValue === "") {

                        e.preventDefault();

                        alert(
                            "Please enter the user's name."
                        );

                        name.focus();

                        return;
                    }


                    if (nameValue.length < 2) {

                        e.preventDefault();

                        alert(
                            "Name must contain at least 2 characters."
                        );

                        name.focus();

                        return;
                    }


                    if (emailValue === "") {

                        e.preventDefault();

                        alert(
                            "Please enter the user's email."
                        );

                        email.focus();

                        return;
                    }


                    const emailPattern =
                        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


                    if (
                        !emailPattern.test(
                            emailValue
                        )
                    ) {

                        e.preventDefault();

                        alert(
                            "Please enter a valid email address."
                        );

                        email.focus();

                        return;
                    }

                }
            );

        }


        /* =========================================
           PHONE VALIDATION
        ========================================= */

        if (phone) {

            phone.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9+\-\s()]/g,
                            ""
                        );

                }
            );

        }

    }
);

</script>


</body>

</html>