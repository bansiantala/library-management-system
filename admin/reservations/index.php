<?php

require_once "../../config/database.php";
require_once "../../config/auth.php";

requireAdmin();


// =========================================================
// STATUS MESSAGE
// =========================================================

$status = $_GET['status'] ?? '';

$message = '';
$message_type = '';

if ($status === 'approved') {

    $message = "Reservation approved successfully.";
    $message_type = "success";

} elseif ($status === 'rejected') {

    $message = "Reservation rejected successfully.";
    $message_type = "success";

} elseif ($status === 'error') {

    $message = "Something went wrong. Please try again.";
    $message_type = "danger";

} elseif ($status === 'available') {

    $message =
        "This book is now available. Please review the reservation.";

    $message_type = "warning";
}


// =========================================================
// GET ALL RESERVATIONS
// =========================================================
//
// IMPORTANT:
// New reservation system uses issue_requests.
// requested_days = 3 / 6 / 10
// due_date = actual due date after approval
//
// =========================================================

$sql = "
    SELECT
        ir.id,
        ir.user_id,
        ir.book_id,
        ir.request_date,
        ir.status,
        ir.requested_days,
        ir.due_date,

        u.name AS user_name,
        u.email AS user_email,

        b.title AS book_title,
        b.author AS book_author,
        b.isbn,
        b.available_quantity,

        c.category_name

    FROM issue_requests ir

    INNER JOIN users u
        ON ir.user_id = u.id

    INNER JOIN books b
        ON ir.book_id = b.id

    LEFT JOIN categories c
        ON b.category_id = c.id

    ORDER BY
        CASE
            WHEN ir.status = 'Pending' THEN 1
            WHEN ir.status = 'Approved' THEN 2
            WHEN ir.status = 'Rejected' THEN 3
            ELSE 4
        END,
        ir.id DESC
";


$reservations = $conn->query($sql);


// =========================================================
// COUNTS
// =========================================================

$totalReservations = 0;
$pendingReservations = 0;
$approvedReservations = 0;
$rejectedReservations = 0;


$countSql = "
    SELECT
        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending,

        SUM(
            CASE
                WHEN status = 'Approved'
                THEN 1
                ELSE 0
            END
        ) AS approved,

        SUM(
            CASE
                WHEN status = 'Rejected'
                THEN 1
                ELSE 0
            END
        ) AS rejected

    FROM issue_requests
";


$countResult = $conn->query($countSql);


if ($countResult) {

    $countData =
        $countResult->fetch_assoc();

    $totalReservations =
        (int)($countData['total'] ?? 0);

    $pendingReservations =
        (int)($countData['pending'] ?? 0);

    $approvedReservations =
        (int)($countData['approved'] ?? 0);

    $rejectedReservations =
        (int)($countData['rejected'] ?? 0);
}


// =========================================================
// HELPER
// =========================================================

function safeDate($date, $format = 'd M Y')
{
    if (
        empty($date) ||
        strtotime($date) === false
    ) {
        return '—';
    }

    return date(
        $format,
        strtotime($date)
    );
}


function getRequestedDays($days)
{
    $days = (int)$days;

    if ($days === 3) {
        return '3 Days';
    }

    if ($days === 6) {
        return '6 Days';
    }

    if ($days === 10) {
        return '10 Days';
    }

    return '3 Days';
}


function getExpectedDueDate(
    $requestDate,
    $requestedDays
) {

    $requestedDays =
        (int)$requestedDays;

    if (
        !in_array(
            $requestedDays,
            [3, 6, 10],
            true
        )
    ) {

        $requestedDays = 3;
    }


    if (
        empty($requestDate) ||
        strtotime($requestDate) === false
    ) {

        return null;
    }


    $date =
        new DateTime(
            date(
                'Y-m-d',
                strtotime($requestDate)
            )
        );


    $date->modify(
        '+' . $requestedDays . ' days'
    );


    return $date->format('Y-m-d');
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
        Reservation Management - Admin
    </title>


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


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >


    <style>

        /* =====================================================
           PAGE
        ====================================================== */

        .reservation-admin-page {

            padding: 30px;

            width: 100%;
        }


        .reservation-admin-container {

            width: 100%;

            max-width: 1500px;

            margin: 0 auto;
        }


        /* =====================================================
           ALERT
        ====================================================== */

        .reservation-admin-alert {

            border-radius: 12px;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 20px;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .reservation-admin-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .reservation-admin-title-area {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .reservation-admin-title-icon {

            width: 54px;

            height: 54px;

            border-radius: 14px;

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

            font-size: 23px;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, .18);
        }


        .reservation-admin-title h2 {

            margin: 0;

            color: #172033;

            font-size: 26px;

            font-weight: 800;
        }


        .reservation-admin-title p {

            margin: 5px 0 0;

            color: #8994a4;

            font-size: 12px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .reservation-summary {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 25px;
        }


        .reservation-stat {

            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 15px;

            padding: 18px;

            display: flex;

            align-items: center;

            gap: 13px;

            box-shadow:
                0 6px 20px
                rgba(15,23,42,.045);
        }


        .reservation-stat-icon {

            width: 46px;

            height: 46px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            flex-shrink: 0;
        }


        .reservation-stat-icon.total {

            background: #eff6ff;

            color: #2563eb;
        }


        .reservation-stat-icon.pending {

            background: #fff7ed;

            color: #ea580c;
        }


        .reservation-stat-icon.approved {

            background: #ecfdf5;

            color: #059669;
        }


        .reservation-stat-icon.rejected {

            background: #fef2f2;

            color: #dc2626;
        }


        .reservation-stat-info span {

            display: block;

            color: #8994a4;

            font-size: 10px;

            font-weight: 700;

            margin-bottom: 3px;
        }


        .reservation-stat-info h3 {

            margin: 0;

            color: #172033;

            font-size: 22px;

            font-weight: 800;
        }


        /* =====================================================
           TABLE CARD
        ====================================================== */

        .reservation-admin-card {

            background: #ffffff;

            border: 1px solid #e5eaf0;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 7px 25px
                rgba(15,23,42,.05);
        }


        .reservation-admin-card-header {

            padding: 19px 22px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .reservation-admin-card-header h5 {

            margin: 0;

            color: #172033;

            font-size: 16px;

            font-weight: 800;
        }


        .reservation-admin-card-header span {

            color: #8994a4;

            font-size: 11px;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .reservation-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1150px;
        }


        .reservation-table th {

            background: #f8fafc;

            color: #64748b;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .35px;

            padding: 14px 15px;

            white-space: nowrap;

            border-bottom: 1px solid #e5e7eb;
        }


        .reservation-table td {

            padding: 16px 15px;

            color: #475467;

            font-size: 12px;

            vertical-align: middle;

            border-bottom: 1px solid #f0f2f5;
        }


        .reservation-table tbody tr {

            transition: background .2s ease;
        }


        .reservation-table tbody tr:hover {

            background: #fafcff;
        }


        /* =====================================================
           RESERVATION ID
        ====================================================== */

        .reservation-id {

            color: #2563eb;

            font-weight: 800;
        }


        /* =====================================================
           USER
        ====================================================== */

        .reservation-user strong {

            display: block;

            color: #1f2937;

            font-size: 12px;

            margin-bottom: 3px;
        }


        .reservation-user small {

            color: #8994a4;

            font-size: 10px;
        }


        /* =====================================================
           BOOK
        ====================================================== */

        .reservation-book strong {

            display: block;

            color: #1f2937;

            font-size: 12px;

            margin-bottom: 3px;

            max-width: 210px;

            line-height: 1.4;
        }


        .reservation-book small {

            color: #8994a4;

            font-size: 10px;
        }


        /* =====================================================
           CATEGORY
        ====================================================== */

        .reservation-category {

            display: inline-flex;

            align-items: center;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            color: #475569;

            padding: 5px 8px;

            border-radius: 7px;

            font-size: 10px;

            font-weight: 700;
        }


        /* =====================================================
           REQUESTED PERIOD
        ====================================================== */

        .reservation-duration {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 10px;

            border-radius: 9px;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            color: #2563eb;

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }


        .reservation-duration i {

            font-size: 12px;
        }


        /* =====================================================
           DATE
        ====================================================== */

        .reservation-date-main {

            color: #334155;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        .reservation-date-time {

            display: block;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 9px;
        }


        /* =====================================================
           DUE DATE
        ====================================================== */

        .reservation-due-date {

            display: inline-flex;

            flex-direction: column;

            gap: 2px;

            padding: 7px 9px;

            border-radius: 9px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            min-width: 115px;
        }


        .reservation-due-date strong {

            color: #334155;

            font-size: 10px;

            font-weight: 800;
        }


        .reservation-due-date small {

            color: #94a3b8;

            font-size: 9px;
        }


        .reservation-due-date.pending {

            background: #fff7ed;

            border-color: #fed7aa;
        }


        .reservation-due-date.pending strong {

            color: #c2410c;
        }


        .reservation-due-date.approved {

            background: #ecfdf5;

            border-color: #bbf7d0;
        }


        .reservation-due-date.approved strong {

            color: #15803d;
        }


        /* =====================================================
           AVAILABILITY
        ====================================================== */

        .reservation-availability {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;

            white-space: nowrap;
        }


        .reservation-availability.available {

            background: #ecfdf5;

            color: #15803d;

            border: 1px solid #bbf7d0;
        }


        .reservation-availability.unavailable {

            background: #fef2f2;

            color: #dc2626;

            border: 1px solid #fecaca;
        }


        /* =====================================================
           STATUS
        ====================================================== */

        .reservation-admin-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;

            white-space: nowrap;
        }


        .reservation-admin-status.pending {

            background: #fff7ed;

            color: #c2410c;

            border: 1px solid #fed7aa;
        }


        .reservation-admin-status.approved {

            background: #ecfdf5;

            color: #15803d;

            border: 1px solid #bbf7d0;
        }


        .reservation-admin-status.rejected {

            background: #fef2f2;

            color: #dc2626;

            border: 1px solid #fecaca;
        }


        /* =====================================================
           ACTIONS
        ====================================================== */

        .reservation-admin-actions {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .reservation-approve-btn,
        .reservation-reject-btn {

            width: 34px;

            height: 34px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 14px;

            transition: .2s ease;
        }


        /* APPROVE */

        .reservation-approve-btn {

            background: #ecfdf5;

            color: #059669;

            border: 1px solid #bbf7d0;
        }


        .reservation-approve-btn:hover {

            background: #059669;

            color: #ffffff;

            border-color: #059669;

            transform: translateY(-1px);
        }


        /* REJECT */

        .reservation-reject-btn {

            background: #fef2f2;

            color: #dc2626;

            border: 1px solid #fecaca;
        }


        .reservation-reject-btn:hover {

            background: #dc2626;

            color: #ffffff;

            border-color: #dc2626;

            transform: translateY(-1px);
        }


        .reservation-no-action {

            color: #cbd5e1;

            font-size: 12px;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .reservation-empty {

            text-align: center;

            padding: 60px 20px;

            color: #94a3b8;
        }


        .reservation-empty i {

            display: block;

            font-size: 35px;

            margin-bottom: 10px;
        }


        .reservation-empty strong {

            display: block;

            color: #64748b;

            font-size: 15px;

            margin-bottom: 4px;
        }


        .reservation-empty span {

            font-size: 12px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .reservation-summary {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }


        @media (max-width: 767px) {

            .reservation-admin-page {

                padding: 20px;
            }


            .reservation-admin-header {

                align-items: flex-start;
            }
        }


        @media (max-width: 576px) {

            .reservation-admin-page {

                padding: 15px;
            }


            .reservation-admin-header {

                flex-direction: column;
            }


            .reservation-summary {

                grid-template-columns: 1fr;
            }


            .reservation-admin-card-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .reservation-admin-actions {

                gap: 5px;
            }


            .reservation-approve-btn,
            .reservation-reject-btn {

                width: 32px;

                height: 32px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     ADMIN SIDEBAR
====================================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================== -->

    <nav class="admin-navbar">


        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Reservation Management
                </h5>


                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Reservations

                </span>

            </div>

        </div>


        <div class="navbar-right">


            <div class="nav-admin">

                <div class="nav-avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <div class="nav-admin-info">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION['user_name']
                            ?? 'Admin'
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
         CONTENT
    ================================================== -->

    <main class="reservation-admin-page">


        <div class="reservation-admin-container">


            <!-- =================================================
                 ALERT
            ================================================== -->

            <?php if (!empty($message)): ?>

                <div
                    class="
                        reservation-admin-alert
                        alert
                        alert-<?php echo htmlspecialchars($message_type); ?>
                        alert-dismissible
                        fade
                        show
                    "
                    role="alert"
                >


                    <?php if (
                        $message_type === 'success'
                    ): ?>

                        <i
                            class="bi bi-check-circle-fill me-2"
                        ></i>


                    <?php elseif (
                        $message_type === 'warning'
                    ): ?>

                        <i
                            class="bi bi-exclamation-circle-fill me-2"
                        ></i>


                    <?php else: ?>

                        <i
                            class="bi bi-exclamation-triangle-fill me-2"
                        ></i>

                    <?php endif; ?>


                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>


                </div>

            <?php endif; ?>


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="reservation-admin-header">


                <div
                    class="
                        reservation-admin-title-area
                    "
                >


                    <div
                        class="
                            reservation-admin-title-icon
                        "
                    >

                        <i
                            class="bi bi-bookmark-star-fill"
                        ></i>

                    </div>


                    <div
                        class="
                            reservation-admin-title
                        "
                    >

                        <h2>
                            Reservation Management
                        </h2>


                        <p>
                            Manage user book reservations
                        </p>

                    </div>


                </div>


            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div
                class="
                    reservation-summary
                "
            >


                <!-- TOTAL -->

                <div
                    class="
                        reservation-stat
                    "
                >

                    <div
                        class="
                            reservation-stat-icon
                            total
                        "
                    >

                        <i
                            class="bi bi-bookmark-fill"
                        ></i>

                    </div>


                    <div
                        class="
                            reservation-stat-info
                        "
                    >

                        <span>
                            Total Reservations
                        </span>


                        <h3>
                            <?php
                            echo $totalReservations;
                            ?>
                        </h3>

                    </div>

                </div>


                <!-- PENDING -->

                <div
                    class="
                        reservation-stat
                    "
                >

                    <div
                        class="
                            reservation-stat-icon
                            pending
                        "
                    >

                        <i
                            class="bi bi-clock-history"
                        ></i>

                    </div>


                    <div
                        class="
                            reservation-stat-info
                        "
                    >

                        <span>
                            Pending
                        </span>


                        <h3>
                            <?php
                            echo $pendingReservations;
                            ?>
                        </h3>

                    </div>

                </div>


                <!-- APPROVED -->

                <div
                    class="
                        reservation-stat
                    "
                >

                    <div
                        class="
                            reservation-stat-icon
                            approved
                        "
                    >

                        <i
                            class="bi bi-check-circle-fill"
                        ></i>

                    </div>


                    <div
                        class="
                            reservation-stat-info
                        "
                    >

                        <span>
                            Approved
                        </span>


                        <h3>
                            <?php
                            echo $approvedReservations;
                            ?>
                        </h3>

                    </div>

                </div>


                <!-- REJECTED -->

                <div
                    class="
                        reservation-stat
                    "
                >

                    <div
                        class="
                            reservation-stat-icon
                            rejected
                        "
                    >

                        <i
                            class="bi bi-x-circle-fill"
                        ></i>

                    </div>


                    <div
                        class="
                            reservation-stat-info
                        "
                    >

                        <span>
                            Rejected
                        </span>


                        <h3>
                            <?php
                            echo $rejectedReservations;
                            ?>
                        </h3>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 RESERVATION TABLE
            ================================================== -->

            <div
                class="
                    reservation-admin-card
                "
            >


                <div
                    class="
                        reservation-admin-card-header
                    "
                >

                    <div>

                        <h5>
                            All Reservations
                        </h5>

                        <span>
                            User reservation requests
                        </span>

                    </div>


                    <span>

                        <i
                            class="bi bi-info-circle me-1"
                        ></i>

                        Borrowing periods:
                        3 / 6 / 10 Days

                    </span>

                </div>


                <div class="table-responsive">


                    <table
                        class="
                            reservation-table
                        "
                    >


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
                                    Category
                                </th>


                                <th>
                                    Borrowing Period
                                </th>


                                <th>
                                    Reserved On
                                </th>


                                <th>
                                    Due Date
                                </th>


                                <th>
                                    Availability
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


                        <?php if (
                            $reservations &&
                            $reservations->num_rows > 0
                        ): ?>


                            <?php while (
                                $row =
                                $reservations->fetch_assoc()
                            ): ?>


                                <?php

                                /*
                                 * Requested days.
                                 *
                                 * Old records without
                                 * requested_days will
                                 * automatically use 3 Days.
                                 */

                                $requestedDays =
                                    (int)(
                                        $row['requested_days']
                                        ?? 3
                                    );


                                if (
                                    !in_array(
                                        $requestedDays,
                                        [3, 6, 10],
                                        true
                                    )
                                ) {

                                    $requestedDays = 3;
                                }


                                $durationLabel =
                                    getRequestedDays(
                                        $requestedDays
                                    );


                                /*
                                 * Actual due date.
                                 *
                                 * For approved records,
                                 * use database due_date.
                                 *
                                 * For pending records,
                                 * show expected date based
                                 * on reservation date.
                                 */

                                $storedDueDate =
                                    $row['due_date']
                                    ?? null;


                                $expectedDueDate =
                                    getExpectedDueDate(
                                        $row['request_date'],
                                        $requestedDays
                                    );


                                if (
                                    $row['status'] ===
                                    'Approved' &&
                                    !empty($storedDueDate)
                                ) {

                                    $displayDueDate =
                                        $storedDueDate;

                                    $dueLabel =
                                        'Actual due date';

                                    $dueClass =
                                        'approved';

                                } elseif (
                                    $row['status'] ===
                                    'Pending'
                                ) {

                                    $displayDueDate =
                                        $expectedDueDate;

                                    $dueLabel =
                                        'Expected after approval';

                                    $dueClass =
                                        'pending';

                                } else {

                                    $displayDueDate =
                                        $storedDueDate;

                                    $dueLabel =
                                        !empty(
                                            $storedDueDate
                                        )
                                            ? 'Due date'
                                            : 'Not applicable';

                                    $dueClass =
                                        '';

                                }

                                ?>


                                <tr>


                                    <!-- =================================================
                                         ID
                                    ================================================== -->

                                    <td>

                                        <span
                                            class="
                                                reservation-id
                                            "
                                        >

                                            #

                                            <?php

                                            echo
                                                (int)$row['id'];

                                            ?>

                                        </span>

                                    </td>


                                    <!-- =================================================
                                         USER
                                    ================================================== -->

                                    <td>

                                        <div
                                            class="
                                                reservation-user
                                            "
                                        >

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row['user_name']
                                                    ?? 'Unknown User'
                                                );

                                                ?>

                                            </strong>


                                            <small>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row['user_email']
                                                    ?? ''
                                                );

                                                ?>

                                            </small>

                                        </div>

                                    </td>


                                    <!-- =================================================
                                         BOOK
                                    ================================================== -->

                                    <td>

                                        <div
                                            class="
                                                reservation-book
                                            "
                                        >

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row['book_title']
                                                    ?? 'Unknown Book'
                                                );

                                                ?>

                                            </strong>


                                            <small>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row['book_author']
                                                    ?? 'Unknown Author'
                                                );

                                                ?>

                                            </small>

                                        </div>

                                    </td>


                                    <!-- =================================================
                                         CATEGORY
                                    ================================================== -->

                                    <td>

                                        <span
                                            class="
                                                reservation-category
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $row['category_name']
                                                ??
                                                'Uncategorized'
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- =================================================
                                         BORROWING PERIOD
                                    ================================================== -->

                                    <td>

                                        <span
                                            class="
                                                reservation-duration
                                            "
                                        >

                                            <i
                                                class="bi bi-calendar3"
                                            ></i>


                                            <?php

                                            echo
                                                htmlspecialchars(
                                                    $durationLabel
                                                );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- =================================================
                                         RESERVED ON
                                    ================================================== -->

                                    <td>


                                        <span
                                            class="
                                                reservation-date-main
                                            "
                                        >

                                            <?php

                                            echo safeDate(
                                                $row['request_date']
                                                ?? null
                                            );

                                            ?>

                                        </span>


                                        <small
                                            class="
                                                reservation-date-time
                                            "
                                        >

                                            <?php

                                            echo safeDate(
                                                $row['request_date']
                                                ?? null,
                                                'h:i A'
                                            );

                                            ?>

                                        </small>


                                    </td>


                                    <!-- =================================================
                                         DUE DATE
                                    ================================================== -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $displayDueDate
                                            )
                                        ): ?>


                                            <div
                                                class="
                                                    reservation-due-date
                                                    <?php
                                                    echo
                                                        htmlspecialchars(
                                                            $dueClass
                                                        );
                                                    ?>
                                                "
                                            >

                                                <strong>

                                                    <?php

                                                    echo safeDate(
                                                        $displayDueDate
                                                    );

                                                    ?>

                                                </strong>


                                                <small>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $dueLabel
                                                    );

                                                    ?>

                                                </small>

                                            </div>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    text-muted
                                                "
                                            >

                                                —

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- =================================================
                                         AVAILABILITY
                                    ================================================== -->

                                    <td>


                                        <?php if (
                                            (int)$row[
                                                'available_quantity'
                                            ] > 0
                                        ): ?>


                                            <span
                                                class="
                                                    reservation-availability
                                                    available
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>


                                                Available


                                                (
                                                <?php

                                                echo (int)
                                                    $row[
                                                        'available_quantity'
                                                    ];

                                                ?>
                                                )

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    reservation-availability
                                                    unavailable
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-x-circle-fill
                                                    "
                                                ></i>


                                                Unavailable

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- =================================================
                                         STATUS
                                    ================================================== -->

                                    <td>


                                        <?php if (
                                            $row['status'] ===
                                            'Approved'
                                        ): ?>


                                            <span
                                                class="
                                                    reservation-admin-status
                                                    approved
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-circle-fill
                                                    "
                                                ></i>


                                                Approved

                                            </span>


                                        <?php elseif (
                                            $row['status'] ===
                                            'Rejected'
                                        ): ?>


                                            <span
                                                class="
                                                    reservation-admin-status
                                                    rejected
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-x-circle-fill
                                                    "
                                                ></i>


                                                Rejected

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    reservation-admin-status
                                                    pending
                                                "
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-clock-fill
                                                    "
                                                ></i>


                                                Pending

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- =================================================
                                         ACTION
                                    ================================================== -->

                                    <td>


                                        <?php if (
                                            $row['status'] ===
                                            'Pending'
                                        ): ?>


                                            <div
                                                class="
                                                    reservation-admin-actions
                                                "
                                            >


                                                <!-- APPROVE -->

                                                <a
                                                    href="approve.php?id=<?php echo (int)$row['id']; ?>"
                                                    class="
                                                        reservation-approve-btn
                                                    "
                                                    title="Approve Reservation"
                                                    onclick="
                                                        return confirm(
                                                            'Are you sure you want to approve this reservation?'
                                                        );
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-check-lg
                                                        "
                                                    ></i>

                                                </a>


                                                <!-- REJECT -->

                                                <a
                                                    href="reject.php?id=<?php echo (int)$row['id']; ?>"
                                                    class="
                                                        reservation-reject-btn
                                                    "
                                                    title="Reject Reservation"
                                                    onclick="
                                                        return confirm(
                                                            'Are you sure you want to reject this reservation?'
                                                        );
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-x-lg
                                                        "
                                                    ></i>

                                                </a>


                                            </div>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    reservation-no-action
                                                "
                                            >

                                                —

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="10"
                                    class="p-0"
                                >

                                    <div
                                        class="
                                            reservation-empty
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-bookmark-x
                                            "
                                        ></i>


                                        <strong>
                                            No Reservations Found
                                        </strong>


                                        <span>
                                            There are currently
                                            no book reservations.
                                        </span>

                                    </div>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- =================================================
     SIDEBAR SCRIPT
================================================== -->

<script>

    const sidebarToggle =
        document.getElementById(
            "sidebarToggle"
        );


    const adminSidebar =
        document.getElementById(
            "adminSidebar"
        );


    if (
        sidebarToggle &&
        adminSidebar
    ) {

        sidebarToggle.addEventListener(
            "click",
            function () {

                adminSidebar.classList.toggle(
                    "show"
                );

            }
        );


        document.addEventListener(
            "click",
            function (event) {

                if (
                    window.innerWidth <= 992 &&
                    adminSidebar.classList.contains(
                        "show"
                    ) &&
                    !adminSidebar.contains(
                        event.target
                    ) &&
                    !sidebarToggle.contains(
                        event.target
                    )
                ) {

                    adminSidebar.classList.remove(
                        "show"
                    );
            

                }

            }
        );

    }

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>