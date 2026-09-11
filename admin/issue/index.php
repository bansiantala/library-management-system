<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();
// =====================================================
// STEP 6 : APPROVE / REJECT MESSAGES
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
}// =====================================================
// GET ALL ISSUED BOOKS
// =====================================================

$sql = "SELECT
            issued_books.*,
            books.title,
            users.name,
            users.email
        FROM issued_books

        INNER JOIN books
            ON issued_books.book_id = books.id

        INNER JOIN users
            ON issued_books.user_id = users.id

        ORDER BY issued_books.id DESC";

$result = $conn->query($sql);


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


// Count pending requests
$pending_requests = 0;

if ($request_result && $request_result->num_rows > 0) {

    $request_result->data_seek(0);

    while ($request_count = $request_result->fetch_assoc()) {

        if ($request_count['status'] === 'Pending') {
            $pending_requests++;
        }
    }

    // Reset pointer for displaying requests
    $request_result->data_seek(0);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Issue & Return Books</title>

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

            <button
                type="button"
                class="notification-btn"
                title="Notifications">

                <i class="bi bi-bell"></i>

                <?php if ($pending_requests > 0) { ?>

                    <span class="notification-dot"></span>

                <?php } ?>

            </button>


            <div class="header-divider"></div>


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



    <!-- =================================================
         PAGE CONTENT
    ================================================== -->

    <div class="dashboard-content">
       <?php if (!empty($success_message)) { ?>

    <div class="alert alert-success alert-dismissible fade show"
         role="alert">

        <i class="bi bi-check-circle-fill me-2"></i>

        <?php
        echo htmlspecialchars($success_message);
        ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php } ?>


<?php if (!empty($error_message)) { ?>

    <div class="alert alert-danger alert-dismissible fade show"
         role="alert">

        <i class="bi bi-exclamation-triangle-fill me-2"></i>

        <?php
        echo htmlspecialchars($error_message);
        ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

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
                        <?php echo $pending_requests; ?>
                    </span>

                    Pending Requests

                </div>

            </div>



            <?php if ($request_result && $request_result->num_rows > 0) { ?>


                <div class="issue-request-list">


                    <?php while ($request = $request_result->fetch_assoc()) { ?>


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


                                    <?php if (!empty($request['user_phone'])) { ?>

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
                                            $request['category_name'] ?? 'N/A'
                                        );

                                        ?>

                                    </p>


                                    <p>

                                        <strong>
                                            ISBN:
                                        </strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $request['book_isbn'] ?? '-'
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
                                            $request['request_date']
                                        )
                                    );

                                    ?>

                                </strong>

                            </div>



                            <!-- STATUS -->

                            <div>

                                <?php

                                if ($request['status'] === 'Pending') {

                                    echo '
                                    <span class="request-status pending">
                                        <i class="bi bi-clock"></i>
                                        Pending
                                    </span>';

                                } elseif ($request['status'] === 'Approved') {

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


                                <?php if ($request['status'] === 'Pending') { ?>


                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/issue/approve_request.php?id=<?php echo $request['id']; ?>"
                                        class="approve-btn"
                                        onclick="return confirm('Approve this book issue request?');">

                                        <i class="bi bi-check-lg"></i>

                                        Approve

                                    </a>


                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/issue/reject_request.php?id=<?php echo $request['id']; ?>"
                                        class="reject-btn"
                                        onclick="return confirm('Reject this book issue request?');">

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
                        There are currently no book issue requests from users.
                    </p>

                </div>


            <?php } ?>


        </div>



        <!-- =================================================
             ISSUED BOOKS TABLE
        ================================================== -->

        <div class="admin-table-section">


            <div class="admin-section-header">

                <div>

                    <h4>
                        <i class="bi bi-journal-bookmark"></i>
                        Issued Books
                    </h4>

                    <p>
                        Manage issued and returned books.
                    </p>

                </div>

            </div>



            <div class="admin-table">

                <div class="table-responsive">


                    <table class="table table-hover align-middle">


                        <thead>

                            <tr>

                                <th>#</th>

                                <th>User</th>

                                <th>Book</th>

                                <th>Issue Date</th>

                                <th>Return Date</th>

                                <th>Actual Return</th>

                                <th>Fine</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>



                        <tbody>


                        <?php

                        if ($result && $result->num_rows > 0) {

                            $count = 1;

                            while ($row = $result->fetch_assoc()) {

                        ?>


                            <tr>


                                <td>

                                    <?php echo $count++; ?>

                                </td>



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



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['title']
                                    );

                                    ?>

                                </td>



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



                                <td>

                                    <?php

                                    if (
                                        $row['actual_return_date']
                                    ) {

                                        echo date(
                                            "d-m-Y",
                                            strtotime(
                                                $row['actual_return_date']
                                            )
                                        );

                                    } else {

                                        echo "-";

                                    }

                                    ?>

                                </td>



                                <td>

                                    ₹<?php

                                    echo number_format(
                                        $row['fine'],
                                        2
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    if (
                                        $row['status'] === 'Issued'
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



                                <td>


                                    <?php

                                    if (
                                        $row['status'] === 'Issued'
                                    ) {

                                    ?>


                                        <a
                                            href="return_book.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Are you sure you want to return this book?');">

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
                                    class="text-center text-muted py-5">

                                    <i class="bi bi-book fs-1"></i>

                                    <br>

                                    No books have been issued.

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
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>