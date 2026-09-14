<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


// =====================================================
// STEP 6 : APPROVE / REJECT / RETURN MESSAGES
// =====================================================

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

$success_message = '';
$error_message = '';

if ($message === 'approved') {
    $success_message = "Book issue request approved successfully.";
}

if ($message === 'rejected') {
    $success_message = "Book issue request rejected successfully.";
}

if ($message === 'returned') {
    $success_message = "Book returned successfully.";
}

if ($error === 'unavailable') {
    $error_message = "This book is currently unavailable.";
}

if ($error === 'failed') {
    $error_message = "Something went wrong. Please try again.";
}


// =====================================================
// STEP 11.4.3 : SEARCH + FILTER
// ISSUED / RETURNED BOOKS
// =====================================================

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';


// Validate status

if (
    $status !== 'Issued' &&
    $status !== 'Returned'
) {
    $status = '';
}


// Validate dates

if (
    $date_from !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)
) {
    $date_from = '';
}

if (
    $date_to !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)
) {
    $date_to = '';
}


// =====================================================
// GET FILTERED ISSUED BOOKS
// =====================================================

$sql = "
    SELECT
        issued_books.*,
        books.title,
        books.author,
        users.name,
        users.email

    FROM issued_books

    INNER JOIN books
        ON issued_books.book_id = books.id

    INNER JOIN users
        ON issued_books.user_id = users.id

    WHERE 1 = 1
";

$params = [];
$types = "";


// Search

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR users.name LIKE ?
            OR users.email LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}


// Status

if ($status === 'Issued' || $status === 'Returned') {

    $sql .= "
        AND issued_books.status = ?
    ";

    $params[] = $status;

    $types .= "s";
}


// Date From

if ($date_from !== '') {

    $sql .= "
        AND issued_books.issue_date >= ?
    ";

    $params[] = $date_from;

    $types .= "s";
}


// Date To

if ($date_to !== '') {

    $sql .= "
        AND issued_books.issue_date <= ?
    ";

    $params[] = $date_to;

    $types .= "s";
}


// Latest records first

$sql .= "
    ORDER BY issued_books.id DESC
";


$stmt = $conn->prepare($sql);

$result = false;

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();
}


// Filtered result count

$filtered_issued_books = 0;

if ($result) {

    $filtered_issued_books = $result->num_rows;
}


// =====================================================
// GET ALL ISSUE REQUESTS
// STEP 5
// =====================================================

$request_sql = "
    SELECT
        issue_requests.id,
        issue_requests.request_date,
        issue_requests.status,

        users.id AS user_id,
        users.name AS user_name,
        users.email AS user_email,
        users.phone AS user_phone,

        books.id AS book_id,
        books.title AS book_title,
        books.author AS book_author,
        books.isbn AS book_isbn,
        books.available_quantity,

        categories.category_name

    FROM issue_requests

    INNER JOIN users
        ON issue_requests.user_id = users.id

    INNER JOIN books
        ON issue_requests.book_id = books.id

    LEFT JOIN categories
        ON books.category_id = categories.id

    ORDER BY issue_requests.id DESC
";

$request_result = $conn->query($request_sql);


// =====================================================
// COUNT PENDING REQUESTS
// =====================================================

$pending_requests = 0;

if (
    $request_result &&
    $request_result->num_rows > 0
) {

    $request_result->data_seek(0);

    while (
        $request_count = $request_result->fetch_assoc()
    ) {

        if (
            $request_count['status'] === 'Pending'
        ) {

            $pending_requests++;

        }

    }

    // Reset pointer

    $request_result->data_seek(0);
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

    <title>
        Issue & Return Books | Library Management System
    </title>


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
           ISSUE & RETURN SEARCH FILTER
        ===================================================== */

        .issue-filter-card {

            background: #ffffff;

            border: 1px solid #edf0f4;

            border-radius: 15px;

            padding: 20px 22px;

            margin-bottom: 24px;

            box-shadow:
                0 5px 20px rgba(30, 41, 59, 0.05);

        }


        .issue-filter-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 16px;

        }


        .issue-filter-title {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .issue-filter-title i {

            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background: #f1f3ff;

            color: #5664d2;

            font-size: 17px;

        }


        .issue-filter-title h5 {

            margin: 0;

            color: #202938;

            font-size: 15px;

            font-weight: 750;

        }


        .issue-filter-title span {

            display: block;

            margin-top: 2px;

            color: #8b96a3;

            font-size: 12px;

        }


        .issue-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                170px
                165px
                165px
                auto
                auto;

            gap: 12px;

            align-items: end;

        }


        .issue-filter-field label {

            display: block;

            margin-bottom: 7px;

            color: #586474;

            font-size: 12px;

            font-weight: 700;

        }


        .issue-search-wrapper {

            position: relative;

        }


        .issue-search-wrapper i {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #98a1ad;

            font-size: 15px;

        }


        .issue-filter-input,
        .issue-filter-select {

            width: 100%;

            height: 43px;

            border: 1px solid #dfe4ea;

            border-radius: 9px;

            background: #ffffff;

            color: #303a49;

            font-size: 13px;

            outline: none;

            padding: 0 13px;

            transition: all 0.2s ease;

        }


        .issue-filter-input {

            padding-left: 39px;

        }


        .issue-filter-input:focus,
        .issue-filter-select:focus {

            border-color: #5664d2;

            box-shadow:
                0 0 0 3px rgba(86, 100, 210, 0.10);

        }


        .issue-search-btn,
        .issue-reset-btn {

            height: 43px;

            padding: 0 16px;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            transition: all 0.2s ease;

            white-space: nowrap;

        }


        .issue-search-btn {

            border: 1px solid #5664d2;

            background: #5664d2;

            color: #ffffff;

        }


        .issue-search-btn:hover {

            background: #4352c5;

            border-color: #4352c5;

            color: #ffffff;

            transform: translateY(-1px);

        }


        .issue-reset-btn {

            border: 1px solid #dfe4ea;

            background: #ffffff;

            color: #667180;

        }


        .issue-reset-btn:hover {

            background: #f7f8fa;

            color: #3f4855;

            border-color: #cfd6de;

        }


        /* =====================================================
           ACTIVE FILTERS
        ===================================================== */

        .issue-active-filters {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 15px;

            padding-top: 14px;

            border-top: 1px solid #eef1f4;

        }


        .issue-filter-label {

            color: #8994a1;

            font-size: 12px;

            font-weight: 700;

            margin-right: 2px;

        }


        .issue-filter-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            background: #f1f3ff;

            color: #5664d2;

            border: 1px solid #e1e4ff;

            border-radius: 20px;

            padding: 5px 10px;

            font-size: 11px;

            font-weight: 700;

        }


        .issue-filter-tag i {

            font-size: 11px;

        }


        /* =====================================================
           ISSUED TABLE HEADER
        ===================================================== */

        .issue-result-count {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 11px;

            background: #f1f3ff;

            color: #5664d2;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           RESPONSIVE FILTER
        ===================================================== */

        @media (max-width: 1350px) {

            .issue-filter-grid {

                grid-template-columns:
                    minmax(0, 1fr)
                    170px
                    165px
                    165px;

            }

            .issue-search-btn,
            .issue-reset-btn {

                width: 100%;

            }

        }


        @media (max-width: 992px) {

            .issue-filter-grid {

                grid-template-columns:
                    1fr 1fr;

            }

        }


        @media (max-width: 768px) {

            .issue-filter-card {

                padding: 16px;

            }


            .issue-filter-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .issue-filter-grid {

                grid-template-columns: 1fr;

            }


            .issue-result-count {

                margin-top: 5px;

            }

        }


        @media (max-width: 576px) {

            .issue-filter-input,
            .issue-filter-select {

                height: 41px;

            }


            .issue-search-btn,
            .issue-reset-btn {

                height: 41px;

            }

        }
 .nav-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;

    background: #f0f6ff;
    color: #2563eb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 16px;
    flex-shrink: 0;

    box-shadow:
        0 3px 10px rgba(37, 99, 235, 0.12);
}

.nav-avatar i {
    color: #2563eb;
    font-size: 15px;
}
    </style>

</head>


<body>


<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================== -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Issue & Return / Admin Dashboard
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Issue & Return

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


                <?php if ($pending_requests > 0) { ?>

                    <span class="notification-dot"></span>

                <?php } ?>

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
    ================================================== -->

    <div class="dashboard-content">


        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if (!empty($success_message)) { ?>

            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                <?php

                echo htmlspecialchars(
                    $success_message
                );

                ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php } ?>


        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if (!empty($error_message)) { ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <?php

                echo htmlspecialchars(
                    $error_message
                );

                ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php } ?>


        <!-- =================================================
             STEP 5 : ISSUE REQUESTS
        ================================================== -->

        <div class="issue-request-section">


            <div class="issue-request-header">

                <div>

                    <h4>

                        <i class="bi bi-journal-plus"></i>

                        Book Issue Requests

                    </h4>

                    <p>

                        Users who requested books are shown here.

                    </p>

                </div>


                <div class="request-count">

                    <span>

                        <?php
                        echo $pending_requests;
                        ?>

                    </span>

                    Pending Requests

                </div>

            </div>


            <?php if (
                $request_result &&
                $request_result->num_rows > 0
            ) { ?>


                <div class="issue-request-list">


                    <?php while (
                        $request = $request_result->fetch_assoc()
                    ) { ?>


                        <div class="issue-request-card">


                            <!-- USER INFORMATION -->

                            <div class="request-user">


                                <div class="request-avatar">

                                    <?php

                                    echo strtoupper(
                                        substr(
                                            $request['user_name'],
                                            0,
                                            1
                                        )
                                    );

                                    ?>

                                </div>


                                <div>

                                    <h5>

                                        <?php

                                        echo htmlspecialchars(
                                            $request['user_name']
                                        );

                                        ?>

                                    </h5>


                                    <p>

                                        <i class="bi bi-envelope"></i>

                                        <?php

                                        echo htmlspecialchars(
                                            $request['user_email']
                                        );

                                        ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $request['user_phone']
                                        )
                                    ) { ?>

                                        <p>

                                            <i class="bi bi-telephone"></i>

                                            <?php

                                            echo htmlspecialchars(
                                                $request['user_phone']
                                            );

                                            ?>

                                        </p>

                                    <?php } ?>


                                </div>

                            </div>



                            <!-- BOOK INFORMATION -->

                            <div class="request-book">


                                <div class="request-book-icon">

                                    <i class="bi bi-book-half"></i>

                                </div>


                                <div>

                                    <h5>

                                        <?php

                                        echo htmlspecialchars(
                                            $request['book_title']
                                        );

                                        ?>

                                    </h5>


                                    <p>

                                        <strong>
                                            Author:
                                        </strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $request['book_author']
                                        );

                                        ?>

                                    </p>


                                    <p>

                                        <strong>
                                            Category:
                                        </strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $request[
                                                'category_name'
                                            ] ?? 'N/A'
                                        );

                                        ?>

                                    </p>


                                    <p>

                                        <strong>
                                            ISBN:
                                        </strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $request[
                                                'book_isbn'
                                            ] ?? '-'
                                        );

                                        ?>

                                    </p>


                                </div>

                            </div>



                            <!-- REQUEST DATE -->

                            <div class="request-date">

                                <span>
                                    Request Date
                                </span>

                                <strong>

                                    <?php

                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $request[
                                                'request_date'
                                            ]
                                        )
                                    );

                                    ?>

                                </strong>

                            </div>



                            <!-- STATUS -->

                            <div>

                                <?php

                                if (
                                    $request['status']
                                    === 'Pending'
                                ) {

                                    echo '
                                    <span class="request-status pending">
                                        <i class="bi bi-clock"></i>
                                        Pending
                                    </span>';

                                } elseif (
                                    $request['status']
                                    === 'Approved'
                                ) {

                                    echo '
                                    <span class="request-status approved">
                                        <i class="bi bi-check-circle"></i>
                                        Approved
                                    </span>';

                                } else {

                                    echo '
                                    <span class="request-status rejected">
                                        <i class="bi bi-x-circle"></i>
                                        Rejected
                                    </span>';

                                }

                                ?>

                            </div>



                            <!-- ACTION BUTTONS -->

                            <div class="request-buttons">


                                <?php if (
                                    $request['status']
                                    === 'Pending'
                                ) { ?>


                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/issue/approve_request.php?id=<?php echo (int)$request['id']; ?>"
                                        class="approve-btn"
                                        onclick="return confirm('Approve this book issue request?');"
                                    >

                                        <i class="bi bi-check-lg"></i>

                                        Approve

                                    </a>


                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/issue/reject_request.php?id=<?php echo (int)$request['id']; ?>"
                                        class="reject-btn"
                                        onclick="return confirm('Reject this book issue request?');"
                                    >

                                        <i class="bi bi-x-lg"></i>

                                        Reject

                                    </a>


                                <?php } else { ?>


                                    <span class="text-muted">

                                        Request Completed

                                    </span>


                                <?php } ?>


                            </div>


                        </div>


                    <?php } ?>


                </div>


            <?php } else { ?>


                <div class="no-requests">


                    <div class="no-request-icon">

                        <i class="bi bi-inbox"></i>

                    </div>


                    <h5>
                        No Issue Requests
                    </h5>


                    <p>

                        There are currently no book issue
                        requests from users.

                    </p>


                </div>


            <?php } ?>


        </div>



        <!-- =================================================
             ADVANCED SEARCH & FILTER
        ================================================== -->

        <div class="issue-filter-card">


            <div class="issue-filter-header">


                <div class="issue-filter-title">


                    <i class="bi bi-funnel"></i>


                    <div>


                        <h5>
                            Search & Filter Issued Books
                        </h5>


                        <span>

                            Search by book or user and filter
                            issued records by status and date.

                        </span>


                    </div>


                </div>


                <div class="issue-result-count">


                    <i class="bi bi-journal-bookmark"></i>


                    <?php echo $filtered_issued_books; ?>

                    Records

                </div>


            </div>



            <form
                method="GET"
                action=""
            >


                <div class="issue-filter-grid">


                    <!-- Search -->

                    <div class="issue-filter-field">


                        <label for="issueSearch">

                            Search

                        </label>


                        <div class="issue-search-wrapper">


                            <i class="bi bi-search"></i>


                            <input
                                type="text"
                                id="issueSearch"
                                name="search"
                                class="issue-filter-input"
                                placeholder="Book, author, user name or email..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >


                        </div>


                    </div>



                    <!-- Status -->

                    <div class="issue-filter-field">


                        <label for="issueStatus">

                            Status

                        </label>


                        <select
                            id="issueStatus"
                            name="status"
                            class="issue-filter-select"
                        >


                            <option
                                value=""
                                <?php echo $status === '' ? 'selected' : ''; ?>
                            >
                                All Status
                            </option>


                            <option
                                value="Issued"
                                <?php echo $status === 'Issued' ? 'selected' : ''; ?>
                            >
                                Issued
                            </option>


                            <option
                                value="Returned"
                                <?php echo $status === 'Returned' ? 'selected' : ''; ?>
                            >
                                Returned
                            </option>


                        </select>


                    </div>



                    <!-- Date From -->

                    <div class="issue-filter-field">


                        <label for="dateFrom">

                            Issue Date From

                        </label>


                        <input
                            type="date"
                            id="dateFrom"
                            name="date_from"
                            class="issue-filter-input"
                            value="<?php echo htmlspecialchars($date_from); ?>"
                        >


                    </div>



                    <!-- Date To -->

                    <div class="issue-filter-field">


                        <label for="dateTo">

                            Issue Date To

                        </label>


                        <input
                            type="date"
                            id="dateTo"
                            name="date_to"
                            class="issue-filter-input"
                            value="<?php echo htmlspecialchars($date_to); ?>"
                        >


                    </div>



                    <!-- Search -->

                    <div class="issue-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <button
                            type="submit"
                            class="issue-search-btn"
                        >


                            <i class="bi bi-search"></i>


                            Search


                        </button>


                    </div>



                    <!-- Reset -->

                    <div class="issue-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <a
                            href="index.php"
                            class="issue-reset-btn"
                        >


                            <i class="bi bi-arrow-counterclockwise"></i>


                            Reset


                        </a>


                    </div>


                </div>



                <!-- =================================================
                     ACTIVE FILTER TAGS
                ================================================== -->

                <?php if (
                    $search !== '' ||
                    $status !== '' ||
                    $date_from !== '' ||
                    $date_to !== ''
                ) { ?>


                    <div class="issue-active-filters">


                        <span class="issue-filter-label">

                            Active Filters:

                        </span>



                        <?php if ($search !== '') { ?>


                            <span class="issue-filter-tag">


                                <i class="bi bi-search"></i>


                                Search:

                                <?php

                                echo htmlspecialchars(
                                    $search
                                );

                                ?>


                            </span>


                        <?php } ?>



                        <?php if ($status !== '') { ?>


                            <span class="issue-filter-tag">


                                <i class="bi bi-filter"></i>


                                Status:

                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>


                            </span>


                        <?php } ?>



                        <?php if ($date_from !== '') { ?>


                            <span class="issue-filter-tag">


                                <i class="bi bi-calendar-event"></i>


                                From:

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($date_from)
                                );

                                ?>


                            </span>


                        <?php } ?>



                        <?php if ($date_to !== '') { ?>


                            <span class="issue-filter-tag">


                                <i class="bi bi-calendar-event"></i>


                                To:

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($date_to)
                                );

                                ?>


                            </span>


                        <?php } ?>


                    </div>


                <?php } ?>


            </form>


        </div>



        <!-- =================================================
             ISSUED BOOKS TABLE
        ================================================== -->

        <div class="admin-table-section">


            <div class="admin-section-header">


                <div>

                    
                </div>


                <div class="issue-result-count">

                    <i class="bi bi-list-check"></i>

                    Showing
                    <?php echo $filtered_issued_books; ?>
                    Records

                </div>


            </div>



            <div class="admin-table">


                <div class="table-responsive">


                    <table class="table table-hover align-middle">


                        <thead>

                            <tr>


                                <th>
                                    #
                                </th>


                                <th>
                                    User
                                </th>


                                <th>
                                    Book
                                </th>


                                <th>
                                    Issue Date
                                </th>


                                <th>
                                    Return Date
                                </th>


                                <th>
                                    Actual Return
                                </th>


                                <th>
                                    Fine
                                </th>


                                <th>
                                    Status
                                </th>


                                <th>
                                    Action
                                </th>


                            </tr>

                        </thead>



                        <tbody>


                        <?php

                        if (
                            $result &&
                            $result->num_rows > 0
                        ) {

                            $count = 1;


                            while (
                                $row = $result->fetch_assoc()
                            ) {

                        ?>


                            <tr>


                                <!-- Number -->

                                <td>

                                    <?php

                                    echo $count++;

                                    ?>

                                </td>



                                <!-- User -->

                                <td>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['name']
                                        );

                                        ?>

                                    </strong>


                                    <br>


                                    <small class="text-muted">

                                        <?php

                                        echo htmlspecialchars(
                                            $row['email']
                                        );

                                        ?>

                                    </small>


                                </td>



                                <!-- Book -->

                                <td>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['title']
                                        );

                                        ?>

                                    </strong>


                                    <br>


                                    <small class="text-muted">

                                        <?php

                                        echo htmlspecialchars(
                                            $row['author']
                                        );

                                        ?>

                                    </small>


                                </td>



                                <!-- Issue Date -->

                                <td>

                                    <?php

                                    echo date(
                                        "d-m-Y",
                                        strtotime(
                                            $row['issue_date']
                                        )
                                    );

                                    ?>

                                </td>



                                <!-- Return Date -->

                                <td>

                                    <?php

                                    echo date(
                                        "d-m-Y",
                                        strtotime(
                                            $row['return_date']
                                        )
                                    );

                                    ?>

                                </td>



                                <!-- Actual Return -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                'actual_return_date'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "d-m-Y",
                                            strtotime(
                                                $row[
                                                    'actual_return_date'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo "-";

                                    }

                                    ?>

                                </td>



                                <!-- Fine -->

                                <td>

                                    ₹<?php

                                    echo number_format(
                                        (float)$row['fine'],
                                        2
                                    );

                                    ?>

                                </td>



                                <!-- Status -->

                                <td>


                                    <?php

                                    if (
                                        $row['status']
                                        === 'Issued'
                                    ) {

                                        echo '
                                        <span class="badge bg-warning text-dark">
                                            Issued
                                        </span>';

                                    } else {

                                        echo '
                                        <span class="badge bg-success">
                                            Returned
                                        </span>';

                                    }

                                    ?>


                                </td>



                                <!-- Action -->

                                <td>


                                    <?php

                                    if (
                                        $row['status']
                                        === 'Issued'
                                    ) {

                                    ?>


                                        <a
                                            href="return_book.php?id=<?php echo (int)$row['id']; ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Are you sure you want to return this book?');"
                                        >

                                            <i class="bi bi-arrow-return-left"></i>

                                            Return

                                        </a>


                                    <?php

                                    } else {

                                        echo '
                                        <span class="text-muted">
                                            Completed
                                        </span>';

                                    }

                                    ?>


                                </td>


                            </tr>


                        <?php

                            }

                        } else {

                        ?>


                            <tr>


                                <td
                                    colspan="9"
                                    class="text-center text-muted py-5"
                                >


                                    <i class="bi bi-search fs-1"></i>


                                    <br><br>


                                    <strong>

                                        No Issue Records Found

                                    </strong>


                                    <br>


                                    <small>

                                        Try changing your search or
                                        filter criteria.

                                    </small>


                                </td>


                            </tr>


                        <?php

                        }

                        ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </div>


</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>