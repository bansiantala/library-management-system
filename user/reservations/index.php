```php
<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

$status_message = "";
$status_type = "";

$status = $_GET['status'] ?? '';

if ($status === 'cancelled') {
    $status_message = "Reservation cancelled successfully.";
    $status_type = "success";
} elseif ($status === 'error') {
    $status_message = "Unable to cancel reservation. Please try again.";
    $status_type = "danger";
}

/*
|--------------------------------------------------------------------------
| Search & Filter Values
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['filter_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

/*
|--------------------------------------------------------------------------
| Validate Status
|--------------------------------------------------------------------------
*/

$allowed_statuses = [
    'Pending',
    'Approved',
    'Cancelled'
];

if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = '';
}

/*
|--------------------------------------------------------------------------
| Validate Sort
|--------------------------------------------------------------------------
*/

$allowed_sorts = [
    'newest',
    'oldest'
];

if (!in_array($sort, $allowed_sorts, true)) {
    $sort = 'newest';
}

/*
|--------------------------------------------------------------------------
| Validate Dates
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Swap Dates If From > To
|--------------------------------------------------------------------------
*/

if (
    $date_from !== '' &&
    $date_to !== '' &&
    $date_from > $date_to
) {
    $temporary_date = $date_from;
    $date_from = $date_to;
    $date_to = $temporary_date;
}

/*
|--------------------------------------------------------------------------
| Sort Order
|--------------------------------------------------------------------------
*/

if ($sort === 'oldest') {
    $orderBy = "
        reservations.reservation_date ASC,
        reservations.id ASC
    ";
} else {
    $orderBy = "
        reservations.reservation_date DESC,
        reservations.id DESC
    ";
}

/*
|--------------------------------------------------------------------------
| Fetch User Reservations With Search & Filters
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        reservations.id,
        reservations.book_id,
        reservations.reservation_date,
        reservations.status,

        books.title,
        books.author,
        books.isbn,
        books.available_quantity,

        categories.category_name

    FROM reservations

    INNER JOIN books
        ON reservations.book_id = books.id

    LEFT JOIN categories
        ON books.category_id = categories.id

    WHERE reservations.user_id = ?
";

$params = [$user_id];
$types = "i";

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR books.isbn LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($filter_status !== '') {

    $sql .= "
        AND reservations.status = ?
    ";

    $params[] = $filter_status;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Date From
|--------------------------------------------------------------------------
*/

if ($date_from !== '') {

    $sql .= "
        AND DATE(reservations.reservation_date) >= ?
    ";

    $params[] = $date_from;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Date To
|--------------------------------------------------------------------------
*/

if ($date_to !== '') {

    $sql .= "
        AND DATE(reservations.reservation_date) <= ?
    ";

    $params[] = $date_to;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Final Query
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY $orderBy
";

/*
|--------------------------------------------------------------------------
| Prepare Statement
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );
}

$stmt->bind_param($types, ...$params);

$stmt->execute();

$result = $stmt->get_result();

$reservations = [];

while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

$stmt->close();

$filteredReservationCount = count($reservations);

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
        My Reservations | Library Management System
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

    <style>

        .reservation-page {
            padding: 30px;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .reservation-title h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .reservation-title p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .reservation-count {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* =========================================================
           FILTER CARD
        ========================================================= */

        .reservation-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }

        .reservation-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .reservation-filter-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .reservation-filter-title i {
            color: #2563eb;
            font-size: 18px;
        }

        .reservation-filter-count {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }

        .reservation-filter-grid {
            display: grid;
            grid-template-columns:
                minmax(220px, 1.7fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                auto
                auto;
            gap: 12px;
            align-items: end;
        }

        .reservation-filter-field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .reservation-search-wrapper {
            position: relative;
        }

        .reservation-search-wrapper i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
        }

        .reservation-filter-input,
        .reservation-filter-select {
            width: 100%;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 0 12px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            outline: none;
            transition: 0.2s ease;
        }

        .reservation-filter-input:focus,
        .reservation-filter-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .reservation-search-wrapper
        .reservation-filter-input {
            padding-left: 38px;
        }

        .reservation-search-btn,
        .reservation-reset-btn {
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 15px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .reservation-search-btn {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .reservation-search-btn:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .reservation-reset-btn {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
        }

        .reservation-reset-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* =========================================================
           ACTIVE FILTERS
        ========================================================= */

        .reservation-active-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .reservation-filter-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .reservation-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* =========================================================
           RESERVATION CARD
        ========================================================= */

        .reservation-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
            transition: 0.2s ease;
        }

        .reservation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .reservation-book {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .reservation-book-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            flex-shrink: 0;
        }

        .reservation-book-info h4 {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .reservation-book-info p {
            margin: 3px 0;
            font-size: 14px;
            color: #64748b;
        }

        /* =========================================================
           DETAILS
        ========================================================= */

        .reservation-details {
            margin-top: 18px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .reservation-detail {
            background: #f8fafc;
            padding: 12px;
            border-radius: 9px;
        }

        .reservation-detail label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .reservation-detail span {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .reservation-footer {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .reservation-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .reservation-status.pending {
            background: #fff7ed;
            color: #ea580c;
        }

        .reservation-status.approved {
            background: #ecfdf5;
            color: #059669;
        }

        .reservation-status.cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .reservation-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .reservation-view-btn,
        .reservation-cancel-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .reservation-view-btn {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .reservation-view-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        .reservation-cancel-btn {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .reservation-cancel-btn:hover {
            background: #dc2626;
            color: #fff;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-reservations {
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-reservations i {
            font-size: 52px;
            color: #94a3b8;
        }

        .empty-reservations h4 {
            margin-top: 15px;
            color: #334155;
            font-weight: 700;
        }

        .empty-reservations p {
            color: #64748b;
            margin-bottom: 20px;
        }

        .browse-books-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
        }

        .browse-books-btn:hover {
            background: #1d4ed8;
            color: white;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1350px) {

            .reservation-filter-grid {
                grid-template-columns:
                    minmax(200px, 1.5fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr)
                    minmax(130px, 1fr);
            }

            .reservation-search-btn,
            .reservation-reset-btn {
                width: 100%;
            }

        }

        @media (max-width: 1100px) {

            .reservation-filter-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }

        }

        @media (max-width: 900px) {

            .reservation-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .reservation-filter-grid {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .reservation-page {
                padding: 20px 15px;
            }

            .reservation-title h2 {
                font-size: 23px;
            }

            .reservation-filter-grid {
                grid-template-columns: 1fr;
            }

            .reservation-details {
                grid-template-columns: 1fr;
            }

            .reservation-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .reservation-actions {
                width: 100%;
            }

            .reservation-view-btn,
            .reservation-cancel-btn {
                flex: 1;
                justify-content: center;
            }

        }

        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            .reservation-filter-card {
                display: none !important;
            }

            .reservation-actions {
                display: none !important;
            }

            .reservation-card {
                box-shadow: none;
                break-inside: avoid;
            }

        }

    </style>

</head>

<body>

<div class="user-layout">

    <!-- Sidebar -->
    <?php include "../../includes/user_sidebar.php"; ?>

    <div class="user-main">

        <!-- Navbar -->
        <?php include "../../includes/navbar.php"; ?>

        <main class="reservation-page">

            <!-- =====================================================
                 PAGE HEADER
            ====================================================== -->

            <div class="reservation-header">

                <div class="reservation-title">

                    <h2>

                        <i class="bi bi-bookmark-star-fill"></i>

                        My Reservations

                    </h2>

                    <p>
                        View and manage your reserved books.
                    </p>

                </div>

                <div class="reservation-count">

                    <i class="bi bi-bookmark-check"></i>

                    <?php
                    echo $filteredReservationCount;
                    ?>

                    Reservation<?php
                    echo $filteredReservationCount != 1
                        ? 's'
                        : '';
                    ?>

                </div>

            </div>


            <!-- =====================================================
                 STATUS MESSAGE
            ====================================================== -->

            <?php if (!empty($status_message)): ?>

                <div
                    class="alert alert-<?php echo $status_type; ?>
                    alert-dismissible fade show"
                    role="alert"
                >

                    <?php
                    echo htmlspecialchars(
                        $status_message
                    );
                    ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    >
                    </button>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 SEARCH & FILTER
            ====================================================== -->

            <div class="reservation-filter-card">

                <div class="reservation-filter-header">

                    <h3 class="reservation-filter-title">

                        <i class="bi bi-funnel-fill"></i>

                        Search & Filter Reservations

                    </h3>

                    <div class="reservation-filter-count">

                        Showing
                        <?php
                        echo $filteredReservationCount;
                        ?>
                        result<?php
                        echo $filteredReservationCount != 1
                            ? 's'
                            : '';
                        ?>

                    </div>

                </div>


                <form
                    method="GET"
                    action=""
                >

                    <div class="reservation-filter-grid">

                        <!-- Search -->
                        <div class="reservation-filter-field">

                            <label for="search">
                                Search
                            </label>

                            <div class="reservation-search-wrapper">

                                <i class="bi bi-search"></i>

                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="reservation-filter-input"
                                    placeholder="Book title, author or ISBN..."
                                    value="<?php
                                    echo htmlspecialchars(
                                        $search
                                    );
                                    ?>"
                                >

                            </div>

                        </div>


                        <!-- Status -->
                        <div class="reservation-filter-field">

                            <label for="filter_status">
                                Status
                            </label>

                            <select
                                name="filter_status"
                                id="filter_status"
                                class="reservation-filter-select"
                            >

                                <option value="">
                                    All Status
                                </option>

                                <option
                                    value="Pending"
                                    <?php
                                    echo $filter_status === 'Pending'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="Approved"
                                    <?php
                                    echo $filter_status === 'Approved'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Approved
                                </option>

                                <option
                                    value="Cancelled"
                                    <?php
                                    echo $filter_status === 'Cancelled'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Cancelled
                                </option>

                            </select>

                        </div>


                        <!-- Date From -->
                        <div class="reservation-filter-field">

                            <label for="date_from">
                                Reservation From
                            </label>

                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                class="reservation-filter-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $date_from
                                );
                                ?>"
                            >

                        </div>


                        <!-- Date To -->
                        <div class="reservation-filter-field">

                            <label for="date_to">
                                Reservation To
                            </label>

                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                class="reservation-filter-input"
                                value="<?php
                                echo htmlspecialchars(
                                    $date_to
                                );
                                ?>"
                            >

                        </div>


                        <!-- Sort -->
                        <div class="reservation-filter-field">

                            <label for="sort">
                                Sort
                            </label>

                            <select
                                name="sort"
                                id="sort"
                                class="reservation-filter-select"
                            >

                                <option
                                    value="newest"
                                    <?php
                                    echo $sort === 'newest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Newest First
                                </option>

                                <option
                                    value="oldest"
                                    <?php
                                    echo $sort === 'oldest'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Oldest First
                                </option>

                            </select>

                        </div>


                        <!-- Search Button -->
                        <div class="reservation-filter-field">

                            <label>&nbsp;</label>

                            <button
                                type="submit"
                                class="reservation-search-btn"
                            >

                                <i class="bi bi-search"></i>

                                Search

                            </button>

                        </div>


                        <!-- Reset Button -->
                        <div class="reservation-filter-field">

                            <label>&nbsp;</label>

                            <a
                                href="<?php
                                echo BASE_URL;
                                ?>/user/reservations/index.php"
                                class="reservation-reset-btn"
                            >

                                <i class="bi bi-arrow-counterclockwise"></i>

                                Reset

                            </a>

                        </div>

                    </div>

                </form>


                <!-- =================================================
                     ACTIVE FILTER TAGS
                ================================================== -->

                <?php
                $hasActiveFilters =
                    $search !== '' ||
                    $filter_status !== '' ||
                    $date_from !== '' ||
                    $date_to !== '' ||
                    $sort !== 'newest';
                ?>

                <?php if ($hasActiveFilters): ?>

                    <div class="reservation-active-filters">

                        <span class="reservation-filter-label">
                            Active Filters:
                        </span>


                        <?php if ($search !== ''): ?>

                            <span class="reservation-filter-tag">

                                <i class="bi bi-search"></i>

                                Search:
                                <?php
                                echo htmlspecialchars(
                                    $search
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($filter_status !== ''): ?>

                            <span class="reservation-filter-tag">

                                <i class="bi bi-circle-fill"></i>

                                Status:
                                <?php
                                echo htmlspecialchars(
                                    $filter_status
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($date_from !== ''): ?>

                            <span class="reservation-filter-tag">

                                <i class="bi bi-calendar"></i>

                                From:
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($date_from)
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($date_to !== ''): ?>

                            <span class="reservation-filter-tag">

                                <i class="bi bi-calendar"></i>

                                To:
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($date_to)
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($sort === 'oldest'): ?>

                            <span class="reservation-filter-tag">

                                <i class="bi bi-sort-down"></i>

                                Oldest First

                            </span>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =====================================================
                 RESERVATIONS
            ====================================================== -->

            <?php if (empty($reservations)): ?>

                <div class="empty-reservations">

                    <i class="bi bi-bookmark-x"></i>

                    <h4>
                        No Reservations Found
                    </h4>

                    <p>

                        <?php if ($hasActiveFilters): ?>

                            No reservations match your
                            current search or filters.

                        <?php else: ?>

                            You have not reserved any books yet.

                        <?php endif; ?>

                    </p>

                    <a
                        href="<?php
                        echo BASE_URL;
                        ?>/user/books/index.php"
                        class="browse-books-btn"
                    >

                        <i class="bi bi-book"></i>

                        Browse Books

                    </a>

                </div>

            <?php else: ?>


                <!-- =================================================
                     RESERVATION LIST
                ================================================== -->

                <?php foreach ($reservations as $reservation): ?>

                    <?php

                    $status_class = strtolower(
                        $reservation['status']
                    );

                    ?>

                    <div class="reservation-card">

                        <!-- Book -->
                        <div class="reservation-book">

                            <div class="reservation-book-icon">

                                <i class="bi bi-book-half"></i>

                            </div>


                            <div class="reservation-book-info">

                                <h4>

                                    <?php
                                    echo htmlspecialchars(
                                        $reservation['title']
                                    );
                                    ?>

                                </h4>

                                <p>

                                    <strong>
                                        Author:
                                    </strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $reservation['author']
                                    );
                                    ?>

                                </p>

                            </div>

                        </div>


                        <!-- Details -->
                        <div class="reservation-details">


                            <div class="reservation-detail">

                                <label>
                                    Category
                                </label>

                                <span>

                                    <?php
                                    echo htmlspecialchars(
                                        $reservation['category_name']
                                        ?? 'N/A'
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="reservation-detail">

                                <label>
                                    ISBN
                                </label>

                                <span>

                                    <?php
                                    echo htmlspecialchars(
                                        $reservation['isbn']
                                        ?? 'N/A'
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="reservation-detail">

                                <label>
                                    Reservation Date
                                </label>

                                <span>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $reservation[
                                                'reservation_date'
                                            ]
                                        )
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="reservation-detail">

                                <label>
                                    Availability
                                </label>

                                <span>

                                    <?php if (
                                        (int)$reservation[
                                            'available_quantity'
                                        ] > 0
                                    ): ?>

                                        Available

                                    <?php else: ?>

                                        Not Available

                                    <?php endif; ?>

                                </span>

                            </div>

                        </div>


                        <!-- Footer -->
                        <div class="reservation-footer">


                            <!-- Status -->
                            <span
                                class="reservation-status
                                <?php
                                echo $status_class;
                                ?>"
                            >

                                <?php if (
                                    $reservation['status']
                                    === 'Pending'
                                ): ?>

                                    <i
                                        class="bi bi-hourglass-split"
                                    ></i>

                                <?php elseif (
                                    $reservation['status']
                                    === 'Approved'
                                ): ?>

                                    <i
                                        class="bi bi-check-circle-fill"
                                    ></i>

                                <?php else: ?>

                                    <i
                                        class="bi bi-x-circle-fill"
                                    ></i>

                                <?php endif; ?>


                                <?php
                                echo htmlspecialchars(
                                    $reservation['status']
                                );
                                ?>

                            </span>


                            <!-- Actions -->
                            <div class="reservation-actions">


                                <!-- View -->
                                <a
                                    href="<?php
                                    echo BASE_URL;
                                    ?>/user/books/details.php?id=<?php
                                    echo (int)$reservation['book_id'];
                                    ?>"
                                    class="reservation-view-btn"
                                >

                                    <i
                                        class="bi bi-eye"
                                    ></i>

                                    View Book

                                </a>


                                <!-- Cancel -->
                                <?php if (
                                    $reservation['status']
                                    === 'Pending'
                                ): ?>

                                    <a
                                        href="<?php
                                        echo BASE_URL;
                                        ?>/user/reservations/cancel.php?id=<?php
                                        echo (int)$reservation['id'];
                                        ?>"
                                        class="reservation-cancel-btn"
                                        onclick="return confirm(
                                            'Are you sure you want to cancel this reservation?'
                                        );"
                                    >

                                        <i
                                            class="bi bi-x-lg"
                                        ></i>

                                        Cancel

                                    </a>

                                <?php endif; ?>


                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


            <?php endif; ?>

        </main>

    </div>

</div>


<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>
