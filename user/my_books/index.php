<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();
$return_status = $_GET['return'] ?? '';
$payment_status = $_GET['payment'] ?? '';

$return_message = '';
$return_type = '';

/* =========================================
   RETURN MESSAGES
========================================= */

if ($return_status === 'success') {

    $return_message =
        "Book returned successfully.";

    $return_type = "success";
}

if ($return_status === 'invalid') {

    $return_message =
        "This book cannot be returned.";

    $return_type = "danger";
}

if ($return_status === 'error') {

    $return_message =
        "Something went wrong while returning the book.";

    $return_type = "danger";
}

if ($return_status === 'payment_required') {

    $return_message =
        "Please pay the fine before returning this book.";

    $return_type = "warning";
}


/* =========================================
   PAYMENT MESSAGES
========================================= */

$payment_message = '';
$payment_type = '';

if ($payment_status === 'success') {

    $payment_message =
        "Fine paid successfully. You can now return the book.";

    $payment_type = "success";
}

if ($payment_status === 'error') {

    $payment_message =
        "Fine payment failed. Please try again.";

    $payment_type = "danger";
}
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT
        issued_books.*,
        books.title,
        books.author,
        categories.category_name
     FROM issued_books
     INNER JOIN books
        ON issued_books.book_id = books.id
     INNER JOIN categories
        ON books.category_id = categories.id
     WHERE issued_books.user_id = ?
       AND issued_books.status = 'Issued'
     ORDER BY issued_books.return_date ASC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$totalIssuedBooks = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Books - Library Management System</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >
<?php if (!empty($return_message)) { ?>

    <div class="alert alert-<?php echo $return_type; ?> alert-dismissible fade show">

        <?php if ($return_type === 'success') { ?>

            <i class="bi bi-check-circle-fill me-2"></i>

        <?php } else { ?>

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

        <?php } ?>

        <?php echo htmlspecialchars($return_message); ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php } ?>

    <style>

        * {
            box-sizing: border-box;
        }


        /* ==========================================
           PAGE
        ========================================== */

        .my-books-page {
            padding: 35px;
            width: 100%;
        }


        .my-books-container {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }


        /* ==========================================
           PAGE HEADER
        ========================================== */

        .my-books-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }


        .my-books-title-area {
            display: flex;
            align-items: center;

            gap: 15px;
        }


        .my-books-title-icon {
            width: 54px;
            height: 54px;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.20);
        }


        .my-books-title h2 {
            margin: 0;

            color: #172033;

            font-size: 27px;

            font-weight: 800;
        }


        .my-books-title p {
            margin: 5px 0 0;

            color: #7b8798;

            font-size: 13px;
        }


        /* ==========================================
           COUNT BADGE
        ========================================== */

        .issued-count {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 11px 16px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            border-radius: 12px;

            color: #2563eb;

            font-size: 13px;
            font-weight: 700;
        }


        .issued-count-number {
            width: 27px;
            height: 27px;

            border-radius: 8px;

            background: #2563eb;

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 12px;
        }


        /* ==========================================
           SUMMARY BAR
        ========================================== */

        .summary-card {
            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 16px;

            padding: 20px 22px;

            margin-bottom: 25px;

            display: flex;
            align-items: center;

            justify-content: space-between;

            gap: 20px;

            box-shadow:
                0 5px 20px
                rgba(15, 23, 42, 0.04);
        }


        .summary-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }


        .summary-icon {
            width: 44px;
            height: 44px;

            border-radius: 11px;

            background: #f1f5f9;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }


        .summary-text strong {
            display: block;

            color: #253044;

            font-size: 14px;
        }


        .summary-text span {
            color: #8994a4;

            font-size: 12px;
        }


        .summary-tip {
            display: flex;
            align-items: center;
            gap: 7px;

            color: #64748b;

            font-size: 12px;
        }


        .summary-tip i {
            color: #2563eb;
        }


        /* ==========================================
           BOOK GRID
        ========================================== */

        .issued-books-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 22px;
        }


        /* ==========================================
           BOOK CARD
        ========================================== */

        .issued-book-card {
            background: #ffffff;

            border: 1px solid #e4e9ef;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 7px 25px
                rgba(15, 23, 42, 0.055);

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }


        .issued-book-card:hover {
            transform: translateY(-4px);

            border-color: #d4deeb;

            box-shadow:
                0 15px 35px
                rgba(15, 23, 42, 0.09);
        }


        /* ==========================================
           CARD TOP
        ========================================== */

        .issued-book-top {
            padding: 22px;

            display: flex;
            align-items: flex-start;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid #eef1f5;
        }


        .issued-book-heading {
            display: flex;
            align-items: center;

            gap: 14px;

            min-width: 0;
        }


        .book-icon {
            width: 58px;
            height: 58px;

            min-width: 58px;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 26px;
        }


        .issued-book-title {
            color: #1f2937;

            font-size: 17px;

            font-weight: 800;

            line-height: 1.35;

            margin: 0 0 5px;

            word-break: break-word;
        }


        .issued-book-author {
            color: #7b8794;

            font-size: 12px;

            margin: 0;
        }


        /* ==========================================
           STATUS
        ========================================== */

        .book-status {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            white-space: nowrap;

            padding: 7px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;
        }


        .book-status.issued {
            background: #ecfdf3;

            color: #15803d;

            border: 1px solid #bbf7d0;
        }


        .book-status.overdue {
            background: #fef2f2;

            color: #dc2626;

            border: 1px solid #fecaca;
        }


        /* ==========================================
           BOOK BODY
        ========================================== */

        .issued-book-body {
            padding: 21px 22px 22px;
        }


        .category-row {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 17px;

            padding-bottom: 16px;

            border-bottom: 1px dashed #e5e9ef;
        }


        .category-label {
            color: #8792a2;

            font-size: 12px;

            display: flex;
            align-items: center;

            gap: 7px;
        }


        .category-label i {
            color: #2563eb;
        }


        .category-value {
            padding: 6px 10px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 7px;

            color: #475467;

            font-size: 11px;

            font-weight: 700;
        }


        /* ==========================================
           DATE INFORMATION
        ========================================== */

        .book-info-list {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 18px;
        }


        .book-info-box {
            background: #f8fafc;

            border: 1px solid #edf0f4;

            border-radius: 11px;

            padding: 13px;
        }


        .book-info-box-label {
            color: #8994a4;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            margin-bottom: 5px;
        }


        .book-info-box-value {
            color: #344054;

            font-size: 13px;

            font-weight: 800;
        }


        .book-info-box-value.due {
            color: #2563eb;
        }


        .book-info-box-value.late {
            color: #dc2626;
        }


        /* ==========================================
           FINE
        ========================================== */

        .fine-row {
            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 13px 14px;

            border-radius: 10px;

            margin-bottom: 15px;

            background: #fafafa;

            border: 1px solid #edf0f4;
        }


        .fine-label {
            color: #697586;

            font-size: 12px;

            display: flex;
            align-items: center;

            gap: 7px;
        }


        .fine-label i {
            color: #64748b;
        }


        .fine-value {
            font-size: 14px;

            font-weight: 800;
        }


        .fine-value.no-fine {
            color: #16a34a;
        }


        .fine-value.has-fine {
            color: #dc2626;
        }


        /* ==========================================
           ALERT
        ========================================== */

        .return-alert {
            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 12px 13px;

            border-radius: 10px;

            margin: 0;

            font-size: 11px;

            line-height: 1.5;
        }


        .return-alert i {
            margin-top: 1px;
        }


        .return-alert.success {
            color: #166534;

            background: #f0fdf4;

            border: 1px solid #bbf7d0;
        }


        .return-alert.danger {
            color: #991b1b;

            background: #fef2f2;

            border: 1px solid #fecaca;
        }


        /* ==========================================
           EMPTY STATE
        ========================================== */

        .empty-books {
            background: #ffffff;

            border: 1px solid #e4e9ef;

            border-radius: 18px;

            padding: 70px 30px;

            text-align: center;

            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, 0.05);
        }


        .empty-icon {
            width: 82px;
            height: 82px;

            margin: 0 auto 18px;

            border-radius: 22px;

            background: #f1f5f9;

            color: #94a3b8;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 38px;
        }


        .empty-books h3 {
            margin: 0 0 8px;

            color: #253044;

            font-size: 21px;

            font-weight: 800;
        }


        .empty-books p {
            margin: 0 auto 22px;

            max-width: 430px;

            color: #8994a4;

            font-size: 13px;

            line-height: 1.6;
        }


        .browse-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 9px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s;
        }


        .browse-btn:hover {
            background: #1d4ed8;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1100px) {

            .my-books-page {
                padding: 28px;
            }

            .issued-books-grid {
                gap: 18px;
            }

        }


        @media (max-width: 900px) {

            .user-nav-center {
                display: none !important;
            }

            .issued-books-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 767px) {

            .my-books-page {
                padding: 22px 18px;
            }

            .my-books-header {
                align-items: flex-start;
            }

            .my-books-title h2 {
                font-size: 23px;
            }

            .issued-count {
                padding: 9px 12px;
            }

            .summary-card {
                flex-direction: column;
                align-items: flex-start;
            }

        }


        @media (max-width: 576px) {

            .my-books-page {
                padding: 18px 12px;
            }

            .my-books-header {
                flex-direction: column;
            }

            .my-books-title-icon {
                width: 46px;
                height: 46px;
                font-size: 20px;
            }

            .my-books-title h2 {
                font-size: 21px;
            }

            .issued-count {
                width: 100%;
                justify-content: center;
            }

            .summary-card {
                padding: 16px;
            }

            .issued-book-top {
                padding: 17px;
            }

            .issued-book-body {
                padding: 17px;
            }

            .book-status {
                font-size: 10px;
                padding: 6px 8px;
            }

            .book-icon {
                width: 50px;
                height: 50px;
                min-width: 50px;
                font-size: 22px;
            }

            .issued-book-title {
                font-size: 15px;
            }

            .book-info-list {
                grid-template-columns: 1fr;
            }

            .empty-books {
                padding: 50px 20px;
            }

        }


        @media (max-width: 430px) {

            .issued-book-top {
                flex-direction: column;
            }

            .book-status {
                align-self: flex-start;
            }

            .category-row {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     USER SIDEBAR
========================================== -->

<?php include "../../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- ==========================================
         SAME UNIQUE USER NAVBAR
    ========================================== -->

    <nav class="user-navbar">


        <div class="user-nav-left">


            <button
                class="sidebar-toggle"
                onclick="toggleSidebar()"
                type="button"
                aria-label="Toggle sidebar"
            >

                <i class="bi bi-list"></i>

            </button>


            <div class="user-welcome-icon">

                <i class="bi bi-journal-bookmark"></i>

            </div>


            <div class="user-welcome">

                <span>Welcome back user</span>

                <h5>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['user_name'] ?? 'User'
                    );

                    ?>

                </h5>

            </div>


        </div>


        <div class="user-nav-center">


            <div class="library-status">

                <span class="status-circle"></span>

                <span>Library is Open</span>

            </div>


        </div>


        <div class="user-nav-right">


            <a
                href="<?php echo BASE_URL; ?>/user/books/search.php"
                class="nav-action"
                title="Search Books"
            >

                <i class="bi bi-search"></i>

            </a>


            <a
                href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                class="nav-action"
                title="My Books"
            >

                <i class="bi bi-journal-bookmark"></i>

            </a>


            <div class="nav-separator"></div>


            <div class="user-profile-pill">


                <div class="user-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION['user_name'] ?? 'U',
                            0,
                            1
                        )
                    );

                    ?>

                </div>


                <div class="user-profile-name">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? 'User'
                        );

                        ?>

                    </strong>

                    <small>Member</small>

                </div>



            </div>


            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="user-logout"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>


        </div>


    </nav>
    <style>
     /* =========================================
   USER NAVBAR - UNIQUE DESIGN
========================================= */

.user-navbar {
    height: 78px;
    background: #ffffff;

    padding: 0 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    border-bottom: 1px solid #edf0f5;

    position: sticky;
    top: 0;
    z-index: 900;

    box-shadow: 0 3px 15px rgba(15, 23, 42, 0.035);
}


/* =========================================
   LEFT
========================================= */

.user-nav-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-welcome-icon {
    width: 43px;
    height: 43px;

    border-radius: 13px;

    background: #eff6ff;
    color: #2563eb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.user-welcome span {
    display: block;

    color: #94a3b8;

    font-size: 10px;
    font-weight: 600;

    margin-bottom: 2px;
}

.user-welcome h5 {
    margin: 0;

    color: #172033;

    font-size: 15px;
    font-weight: 800;
}


/* =========================================
   CENTER STATUS
========================================= */

.user-nav-center {
    position: absolute;

    left: 50%;

    transform: translateX(-50%);
}

.library-status {
    display: flex;
    align-items: center;
    gap: 8px;

    padding: 8px 14px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    color: #64748b;

    font-size: 11px;
    font-weight: 600;
}

.status-circle {
    width: 8px;
    height: 8px;

    background: #22c55e;

    border-radius: 50%;

    box-shadow: 0 0 0 4px rgba(34,197,94,.10);
}


/* =========================================
   RIGHT
========================================= */

.user-nav-right {
    display: flex;
    align-items: center;

    gap: 10px;
}


/* =========================================
   ACTION BUTTONS
========================================= */

.nav-action {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    color: #64748b;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.nav-action:hover {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #2563eb;

    transform: translateY(-1px);
}


/* =========================================
   SEPARATOR
========================================= */

.nav-separator {
    width: 1px;
    height: 34px;

    background: #e5e7eb;

    margin: 0 5px;
}


/* =========================================
   USER PROFILE PILL
========================================= */

.user-profile-pill {
    display: flex;
    align-items: center;

    gap: 9px;

    padding: 5px 10px 5px 5px;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 30px;

    cursor: default;
}

.user-avatar {
    width: 35px;
    height: 35px;

    border-radius: 50%;

    background: #2563eb;
    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 13px;
    font-weight: 800;
}

.user-profile-name strong {
    display: block;

    color: #334155;

    font-size: 11px;
    font-weight: 700;

    max-width: 110px;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-name small {
    display: block;

    color: #94a3b8;

    font-size: 9px;

    margin-top: 1px;
}

.profile-arrow {
    color: #94a3b8;

    font-size: 10px;

    margin-left: 2px;
}


/* =========================================
   LOGOUT
========================================= */

.user-logout {
    width: 40px;
    height: 40px;

    border-radius: 11px;

    background: #fff5f5;

    border: 1px solid #fee2e2;

    color: #ef4444;

    display: flex;
    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 17px;

    transition: all .25s ease;
}

.user-logout:hover {
    background: #ef4444;

    color: #ffffff;

    border-color: #ef4444;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 900px) {

    .user-nav-center {
        display: none;
    }

    .user-navbar {
        padding: 0 20px;
    }

}


@media (max-width: 650px) {

    .user-navbar {
        height: 70px;

        padding: 0 14px;
    }

    .user-welcome-icon {
        width: 39px;
        height: 39px;
    }

    .user-welcome span {
        font-size: 9px;
    }

    .user-welcome h5 {
        font-size: 13px;
    }

    .nav-action {
        width: 37px;
        height: 37px;

        font-size: 15px;
    }

    .user-profile-name,
    .profile-arrow {
        display: none;
    }

    .user-profile-pill {
        padding: 3px;
        border-radius: 50%;
    }

    .user-avatar {
        width: 34px;
        height: 34px;
    }

    .user-logout {
        width: 37px;
        height: 37px;
    }

}


@media (max-width: 450px) {

    .user-nav-right {
        gap: 6px;
    }

    .user-nav-left {
        gap: 8px;
    }

    .nav-action:nth-child(2) {
        display: none;
    }

}

/* =========================================
   FINE PAYMENT BOX
========================================= */

.fine-payment-box {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px;

    margin-bottom: 15px;

    background: #fff7ed;

    border: 1px solid #fed7aa;

    border-radius: 12px;
}

.fine-payment-icon {

    width: 40px;
    height: 40px;

    flex-shrink: 0;

    border-radius: 10px;

    background: #ffedd5;

    color: #ea580c;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 18px;
}

.fine-payment-content {

    flex: 1;
}

.fine-payment-content strong {

    display: block;

    color: #9a3412;

    font-size: 12px;

    margin-bottom: 3px;
}

.fine-payment-content span {

    display: block;

    color: #7c2d12;

    font-size: 10px;

    line-height: 1.5;
}

.pay-fine-btn {

    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    padding: 9px 14px;

    border-radius: 9px;

    background: #ea580c;

    color: #ffffff;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    transition: .2s;
}

.pay-fine-btn:hover {

    background: #c2410c;

    color: #ffffff;

    transform: translateY(-1px);
}


/* =========================================
   RETURN BUTTON
========================================= */

.return-book-action {

    margin-bottom: 15px;
}

.return-book-btn {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 11px 15px;

    border-radius: 10px;

    background: #2563eb;

    color: #ffffff;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;

    transition: .2s;
}

.return-book-btn:hover {

    background: #1d4ed8;

    color: #ffffff;

    transform: translateY(-1px);
}


/* MOBILE */

@media (max-width: 576px) {

    .fine-payment-box {

        align-items: flex-start;

        flex-wrap: wrap;
    }

    .fine-payment-content {

        min-width: 0;
    }

    .pay-fine-btn {

        width: 100%;
    }
}
    </style>





    <!-- ==========================================
         PAGE CONTENT
    ========================================== -->

    <main class="my-books-page">


        <div class="my-books-container">


            <!-- ======================================
                 PAGE HEADER
            ======================================= -->

            <div class="my-books-header">


                <div class="my-books-title-area">


                    <div class="my-books-title-icon">

                        <i class="bi bi-journal-bookmark-fill"></i>

                    </div>


                    <div class="my-books-title">

                        <h2>My Books</h2>

                        <p>
                            Manage and track your currently issued books
                        </p>

                    </div>


                </div>


                <div class="issued-count">

                    <span class="issued-count-number">

                        <?php echo $totalIssuedBooks; ?>

                    </span>

                    Currently Issued

                </div>


            </div>


            <?php if ($result->num_rows > 0): ?>


                <!-- ==================================
                     SUMMARY
                =================================== -->

                <div class="summary-card">


                    <div class="summary-left">


                        <div class="summary-icon">

                            <i class="bi bi-book-half"></i>

                        </div>


                        <div class="summary-text">

                            <strong>
                                Your Current Library Books
                            </strong>

                            <span>
                                Keep track of your issue and return dates
                            </span>

                        </div>


                    </div>


                    <div class="summary-tip">

                        <i class="bi bi-info-circle"></i>

                        Return books before the due date to avoid fines.

                    </div>


                </div>


                <!-- ==================================
                     BOOKS
                =================================== -->

                <div class="issued-books-grid">


                    <?php while ($book = $result->fetch_assoc()): ?>


                        <?php

                        $today = new DateTime();

                        $return_date = new DateTime(
                            $book['return_date']
                        );

                        $difference = $today->diff(
                            $return_date
                        );


                        if ($today <= $return_date) {

                            $days_remaining =
                                $difference->days;

                            $overdue = false;

                        } else {

                            $days_remaining =
                                $difference->days;

                            $overdue = true;

                        }

                        ?>


                        <article class="issued-book-card">


                            <!-- =================================
                                 CARD HEADER
                            ================================== -->

                            <div class="issued-book-top">


                                <div class="issued-book-heading">


                                    <div class="book-icon">

                                        <i class="bi bi-book-half"></i>

                                    </div>


                                    <div>

                                        <h3
                                            class="issued-book-title"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $book['title']
                                            );

                                            ?>

                                        </h3>


                                        <p
                                            class="issued-book-author"
                                        >

                                            <i
                                                class="bi bi-person me-1"
                                            ></i>

                                            <?php

                                            echo htmlspecialchars(
                                                $book['author']
                                            );

                                            ?>

                                        </p>

                                    </div>


                                </div>


                                <?php if ($overdue): ?>


                                    <span
                                        class="book-status overdue"
                                    >

                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>

                                        Overdue

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="book-status issued"
                                    >

                                        <i
                                            class="bi bi-check-circle-fill"
                                        ></i>

                                        Issued

                                    </span>


                                <?php endif; ?>


                            </div>


                            <!-- =================================
                                 CARD BODY
                            ================================== -->

                            <div class="issued-book-body">


                                <!-- CATEGORY -->

                                <div class="category-row">


                                    <div class="category-label">

                                        <i class="bi bi-tag-fill"></i>

                                        Category

                                    </div>


                                    <div class="category-value">

                                        <?php

                                        echo htmlspecialchars(
                                            $book['category_name']
                                        );

                                        ?>

                                    </div>


                                </div>


                                <!-- DATES -->

                                <div class="book-info-list">


                                    <!-- ISSUE DATE -->

                                    <div class="book-info-box">


                                        <div
                                            class="book-info-box-label"
                                        >

                                            Issue Date

                                        </div>


                                        <div
                                            class="book-info-box-value"
                                        >

                                            <i
                                                class="bi bi-calendar-plus me-1"
                                            ></i>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book['issue_date']
                                                )
                                            );

                                            ?>

                                        </div>


                                    </div>


                                    <!-- RETURN DATE -->

                                    <div class="book-info-box">


                                        <div
                                            class="book-info-box-label"
                                        >

                                            Return Date

                                        </div>


                                        <div
                                            class="
                                                book-info-box-value
                                                <?php
                                                echo $overdue
                                                    ? 'late'
                                                    : 'due';
                                                ?>
                                            "
                                        >

                                            <i
                                                class="bi bi-calendar-check me-1"
                                            ></i>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $book['return_date']
                                                )
                                            );

                                            ?>

                                        </div>


                                    </div>


                                    <!-- DAYS -->

                                    <div class="book-info-box">


                                        <div
                                            class="book-info-box-label"
                                        >

                                            <?php

                                            echo $overdue
                                                ? "Days Overdue"
                                                : "Days Remaining";

                                            ?>

                                        </div>


                                        <div
                                            class="
                                                book-info-box-value
                                                <?php
                                                echo $overdue
                                                    ? 'late'
                                                    : 'due';
                                                ?>
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    <?php
                                                    echo $overdue
                                                        ? 'bi-exclamation-triangle'
                                                        : 'bi-hourglass-split';
                                                    ?>
                                                    me-1
                                                "
                                            ></i>

                                            <?php
                                            echo $days_remaining;
                                            ?>

                                            days

                                        </div>


                                    </div>


                                    <!-- STATUS -->

                                    <div class="book-info-box">


                                        <div
                                            class="book-info-box-label"
                                        >

                                            Status

                                        </div>


                                        <div
                                            class="book-info-box-value"
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-bookmark-check
                                                    me-1
                                                "
                                            ></i>

                                            Issued

                                        </div>


                                    </div>


                                </div>


                                <!-- FINE -->

                                <div class="fine-row">


                                    <div class="fine-label">

                                        <i
                                            class="bi bi-currency-rupee"
                                        ></i>

                                        Current Fine

                                    </div>


                                    <?php if ($overdue): ?>


                                        <div
                                            class="fine-value has-fine"
                                        >

                                            ₹<?php

                                            echo $days_remaining * 10;

                                            ?>

                                        </div>


                                    <?php else: ?>


                                        <div
                                            class="fine-value no-fine"
                                        >

                                            ₹0

                                        </div>


                                    <?php endif; ?>


                                </div>


                                <!-- ALERT -->

                                <?php if ($overdue): ?>


                                    <div
                                        class="
                                            return-alert
                                            danger
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-exclamation-triangle-fill
                                            "
                                        ></i>

                                        <span>

                                            This book is overdue.
                                            Please return it as soon
                                            as possible to avoid
                                            additional fine.

                                        </span>

                                    </div>


                                <?php else: ?>
                                    <!-- RETURN BOOK BUTTON -->

<?php if ($book['status'] === 'Issued') { ?>

    <div class="return-book-action">

      <?php if ($overdue): ?>

    <?php if ($book['payment_status'] === 'Paid'): ?>

        <div class="return-book-action">

            <a
                href="<?php echo BASE_URL; ?>/user/my_books/return_book.php?id=<?php echo $book['id']; ?>"
                class="return-book-btn"
                onclick="return confirm('Are you sure you want to return this book?');"
            >

                <i class="bi bi-arrow-return-left"></i>

                Return Book

            </a>

        </div>

    <?php else: ?>

        <div class="fine-payment-box">

            <div class="fine-payment-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            <div class="fine-payment-content">

                <strong>
                    Fine Payment Required
                </strong>

                <span>
                    This book is overdue.
                    Please pay ₹<?php echo $current_fine; ?>
                    before returning the book.
                </span>

            </div>

            <a
                href="<?php echo BASE_URL; ?>/user/my_books/pay_fine.php?id=<?php echo $book['id']; ?>"
                class="pay-fine-btn"
            >

                <i class="bi bi-credit-card"></i>

                Pay Fine

            </a>

        </div>

    <?php endif; ?>

<?php else: ?>

    <div class="return-book-action">

        <a
            href="<?php echo BASE_URL; ?>/user/my_books/return_book.php?id=<?php echo $book['id']; ?>"
            class="return-book-btn"
            onclick="return confirm('Are you sure you want to return this book?');"
        >

            <i class="bi bi-arrow-return-left"></i>

            Return Book

        </a>

    </div>

<?php endif; ?>

    </div>

<?php } ?>


                                    <div
                                        class="
                                            return-alert
                                            success
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-check-circle-fill
                                            "
                                        ></i>

                                        <span>

                                            Please return this book
                                            before the due date to
                                            avoid any late fine.

                                        </span>

                                    </div>


                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- ==================================
                     EMPTY STATE
                =================================== -->

                <div class="empty-books">


                    <div class="empty-icon">

                        <i class="bi bi-journal-x"></i>

                    </div>


                    <h3>
                        No Books Issued
                    </h3>


                    <p>

                        You currently don't have any books
                        issued to your account. Browse the
                        library collection and find a book
                        you'd like to read.

                    </p>


                    <a
                        href="<?php echo BASE_URL; ?>/user/books/index.php"
                        class="browse-btn"
                    >

                        <i class="bi bi-book"></i>

                        Browse Books

                    </a>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<script>

function toggleSidebar() {

    const sidebar =
        document.querySelector('.user-sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }

}

</script>


</body>

</html>