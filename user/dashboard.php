<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}


// =====================================================
// 🤖 BOOK RECOMMENDATIONS
// =====================================================

$recommendedBooks = [];
$categoryIds = [];

// -----------------------------------------------------
// FIND CATEGORIES FROM USER'S PREVIOUSLY ISSUED BOOKS
// -----------------------------------------------------

$stmtRecommendation = $conn->prepare("
    SELECT DISTINCT b.category_id
    FROM issued_books ib
    INNER JOIN books b
        ON ib.book_id = b.id
    WHERE ib.user_id = ?
      AND b.category_id IS NOT NULL
");

if ($stmtRecommendation) {

    $stmtRecommendation->bind_param("i", $user_id);
    $stmtRecommendation->execute();

    $recommendationResult = $stmtRecommendation->get_result();

    while ($row = $recommendationResult->fetch_assoc()) {

        $categoryIds[] = (int)$row['category_id'];
    }

    $stmtRecommendation->close();
}


// -----------------------------------------------------
// FIND BOOKS FROM USER'S READING CATEGORIES
// -----------------------------------------------------

if (!empty($categoryIds)) {

    $placeholders = implode(
        ",",
        array_fill(0, count($categoryIds), "?")
    );

    $types = str_repeat(
        "i",
        count($categoryIds)
    );

    $sqlRecommended = "
        SELECT
            b.id,
            b.title,
            b.author,
            b.isbn,
            b.available_quantity,
            c.category_name

        FROM books b

        INNER JOIN categories c
            ON b.category_id = c.id

        WHERE b.category_id IN ($placeholders)

          AND b.available_quantity > 0

          AND b.id NOT IN (
              SELECT book_id
              FROM issued_books
              WHERE user_id = ?
          )

        ORDER BY b.created_at DESC

        LIMIT 6
    ";

    $stmtRecommended = $conn->prepare($sqlRecommended);

    if ($stmtRecommended) {

        $params = $categoryIds;
        $params[] = $user_id;

        $bindParams = [];
        $bindParams[] = $types . "i";

        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }

        call_user_func_array(
            [$stmtRecommended, "bind_param"],
            $bindParams
        );

        $stmtRecommended->execute();

        $recommendedResult =
            $stmtRecommended->get_result();

        while ($book = $recommendedResult->fetch_assoc()) {

            $recommendedBooks[] = $book;
        }

        $stmtRecommended->close();
    }
}


// -----------------------------------------------------
// FALLBACK RECOMMENDATIONS
// -----------------------------------------------------
// If user has no borrowing history, or no matching
// available books exist, show available books.
// -----------------------------------------------------

if (empty($recommendedBooks)) {

    $fallbackResult = $conn->query("
        SELECT
            b.id,
            b.title,
            b.author,
            b.isbn,
            b.available_quantity,
            c.category_name

        FROM books b

        INNER JOIN categories c
            ON b.category_id = c.id

        WHERE b.available_quantity > 0

        ORDER BY b.created_at DESC

        LIMIT 6
    ");

    if ($fallbackResult) {

        while ($book = $fallbackResult->fetch_assoc()) {

            $recommendedBooks[] = $book;
        }

        $fallbackResult->free();
    }
}


// =====================================================
// CURRENTLY ISSUED BOOKS
// =====================================================

$issued_books = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE user_id = ?
      AND status = 'Issued'
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $issued_books = (int)($row['total'] ?? 0);

    $stmt->close();
}


// =====================================================
// RETURNED BOOKS
// =====================================================

$returned_books = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE user_id = ?
      AND status = 'Returned'
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $returned_books = (int)($row['total'] ?? 0);

    $stmt->close();
}


// =====================================================
// TOTAL BOOKS
// =====================================================

$total_books = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM books
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_books = (int)($row['total'] ?? 0);

    $result->free();
}


// =====================================================
// DUE DATE NOTIFICATIONS
// =====================================================

$notifications = [];

$today = new DateTime(date("Y-m-d"));

$stmtNotification = $conn->prepare("
    SELECT
        ib.id,
        ib.book_id,
        ib.issue_date,
        ib.return_date,
        ib.status,
        b.title

    FROM issued_books ib

    INNER JOIN books b
        ON ib.book_id = b.id

    WHERE ib.user_id = ?
      AND ib.status = 'Issued'

    ORDER BY ib.return_date ASC
");

if ($stmtNotification) {

    $stmtNotification->bind_param(
        "i",
        $user_id
    );

    $stmtNotification->execute();

    $notificationResult =
        $stmtNotification->get_result();

    while (
        $row =
        $notificationResult->fetch_assoc()
    ) {

        if (
            empty($row['issue_date']) ||
            empty($row['return_date'])
        ) {
            continue;
        }

        $issueTimestamp =
            strtotime($row['issue_date']);

        if ($issueTimestamp === false) {
            continue;
        }

        $issueDate = new DateTime(
            date(
                "Y-m-d",
                $issueTimestamp
            )
        );

        $dueTimestamp =
            strtotime($row['return_date']);

        if ($dueTimestamp === false) {
            continue;
        }

        $dueDate = new DateTime(
            date(
                "Y-m-d",
                $dueTimestamp
            )
        );


        // -------------------------------------------------
        // BORROWING PERIOD
        // -------------------------------------------------

        $borrowingPeriod =
            (int)$issueDate
                ->diff($dueDate)
                ->format("%r%a");


        // -------------------------------------------------
        // DAYS REMAINING
        // -------------------------------------------------

        $daysRemaining =
            (int)$today
                ->diff($dueDate)
                ->format("%r%a");


        // -------------------------------------------------
        // NOTIFICATION
        // -------------------------------------------------

        if (
            $daysRemaining >= 0 &&
            $daysRemaining <= 3
        ) {

            if ($daysRemaining === 0) {

                $notificationTitle =
                    "Book Due Today";

            } else {

                $notificationTitle =
                    "Book Due Soon";
            }


            $notifications[] = [

                "id" =>
                    (int)$row['id'],

                "title" =>
                    $row['title'],

                "issue_date" =>
                    $issueDate->format("d M Y"),

                "due_date" =>
                    $dueDate->format("d M Y"),

                "borrowing_period" =>
                    $borrowingPeriod,

                "days_remaining" =>
                    $daysRemaining,

                "notification_title" =>
                    $notificationTitle
            ];
        }
    }

    $stmtNotification->close();
}

$totalNotifications =
    count($notifications);


// =====================================================
// RESERVATION AVAILABILITY MESSAGES
// =====================================================

$availabilityMessages = [];

$stmtAvailability = $conn->prepare("
    SELECT
        ir.id,
        b.title

    FROM issue_requests ir

    INNER JOIN books b
        ON ir.book_id = b.id

    WHERE ir.user_id = ?
      AND ir.status = 'Pending'
      AND b.available_quantity > 0

    ORDER BY ir.request_date ASC
");

if ($stmtAvailability) {

    $stmtAvailability->bind_param(
        "i",
        $user_id
    );

    $stmtAvailability->execute();

    $availabilityResult =
        $stmtAvailability->get_result();

    while (
        $row =
        $availabilityResult->fetch_assoc()
    ) {

        $availabilityMessages[] = [

            "id" =>
                (int)$row['id'],

            "title" =>
                $row['title']
        ];
    }

    $stmtAvailability->close();
}


// =====================================================
// USER NAME
// =====================================================

$userName =
    $_SESSION['user_name'] ?? 'User';

$userInitial =
    strtoupper(
        substr(
            $userName,
            0,
            1
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Dashboard</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >


    <!-- =====================================================
         USER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css"
    >


    <!-- =====================================================
         APPLY SAVED THEME
    ====================================================== -->

    <script>

        (function () {

            const savedTheme =
                localStorage.getItem(
                    "library_theme"
                );

            if (savedTheme === "dark") {

                document.documentElement.classList.add(
                    "library-dark-mode"
                );
            }

        })();

    </script>


    <style>

        /* =====================================================
           GLOBAL DARK MODE
        ===================================================== */

        html.library-dark-mode,
        body.library-dark-mode {

            background: #0f172a !important;
            color: #e2e8f0 !important;
        }

        body.library-dark-mode {

            background: #0f172a !important;
        }


        /* =====================================================
           USER NAVBAR
        ===================================================== */

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

            box-shadow:
                0 3px 15px
                rgba(15, 23, 42, 0.035);

            transition:
                background .25s ease,
                border-color .25s ease,
                box-shadow .25s ease;
        }


        /* =====================================================
           LEFT
        ===================================================== */

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


        /* =====================================================
           CENTER STATUS
        ===================================================== */

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

            box-shadow:
                0 0 0 4px
                rgba(34, 197, 94, .10);
        }


        /* =====================================================
           RIGHT
        ===================================================== */

        .user-nav-right {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

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

            position: relative;

            cursor: pointer;
        }


        .nav-action:hover {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;

            transform: translateY(-1px);
        }


        /* =====================================================
           FAVORITE
        ===================================================== */

        .favorite-nav-action {

            color: #e11d48;
        }


        .favorite-nav-action:hover {

            background: #fff1f2;

            border-color: #fecdd3;

            color: #e11d48;
        }


        /* =====================================================
           NOTIFICATION
        ===================================================== */

        .notification-wrapper {

            position: relative;
        }


        .notification-nav-action {

            position: relative;

            cursor: pointer;
        }


        .notification-nav-action.active {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;
        }


        /* =====================================================
           NOTIFICATION BADGE
        ===================================================== */

        .notification-badge {

            position: absolute;

            top: -5px;

            right: -5px;

            min-width: 19px;

            height: 19px;

            padding: 0 5px;

            border-radius: 50px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ef4444;

            color: #ffffff;

            border: 2px solid #ffffff;

            font-size: 9px;

            font-weight: 800;

            line-height: 1;

            z-index: 5;
        }


        /* =====================================================
           NOTIFICATION POPUP
        ===================================================== */

        .notification-popup {

            position: absolute;

            top: calc(100% + 12px);

            right: -80px;

            width: 350px;

            max-width: calc(100vw - 30px);

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 15px 40px
                rgba(15, 23, 42, 0.15);

            opacity: 0;

            visibility: hidden;

            pointer-events: none;

            transform:
                translateY(-8px)
                scale(.98);

            transition:
                opacity .2s ease,
                transform .2s ease,
                visibility .2s ease;

            z-index: 2000;

            overflow: hidden;
        }


        .notification-popup.show {

            opacity: 1;

            visibility: visible;

            pointer-events: auto;

            transform:
                translateY(0)
                scale(1);
        }


        /* =====================================================
           POPUP HEADER
        ===================================================== */

        .notification-popup-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 15px 16px;

            border-bottom: 1px solid #edf0f5;

            background: #ffffff;
        }


        .notification-popup-title {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .notification-popup-title i {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 14px;
        }


        .notification-popup-title strong {

            color: #172033;

            font-size: 13px;

            font-weight: 800;
        }


        .notification-count {

            min-width: 24px;

            height: 24px;

            padding: 0 7px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50px;

            background: #2563eb;

            color: #ffffff;

            font-size: 9px;

            font-weight: 800;
        }


        /* =====================================================
           NOTIFICATION BODY
        ===================================================== */

        .notification-popup-body {

            max-height: 330px;

            overflow-y: auto;

            padding: 8px;
        }


        /* =====================================================
           SINGLE NOTIFICATION
        ===================================================== */

        .notification-item {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 11px;

            border-radius: 10px;

            text-decoration: none;

            transition: background .2s ease;
        }


        .notification-item:hover {

            background: #f8fafc;
        }


        .notification-item-icon {

            width: 35px;

            height: 35px;

            min-width: 35px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #fff7ed;

            color: #f59e0b;

            font-size: 15px;
        }


        .notification-item-content {

            min-width: 0;

            flex: 1;
        }


        .notification-item-content strong {

            display: block;

            color: #1e293b;

            font-size: 11px;

            font-weight: 800;

            line-height: 1.4;

            margin-bottom: 3px;
        }


        .notification-item-content p {

            margin: 0;

            color: #64748b;

            font-size: 10px;

            line-height: 1.5;
        }


        .notification-item-content .due-date {

            display: inline-block;

            margin-top: 5px;

            padding: 3px 7px;

            border-radius: 20px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 8px;

            font-weight: 700;
        }


        /* =====================================================
           NO NOTIFICATIONS
        ===================================================== */

        .no-notifications {

            padding: 30px 15px;

            text-align: center;

            color: #94a3b8;
        }


        .no-notifications i {

            display: block;

            font-size: 28px;

            margin-bottom: 8px;

            color: #cbd5e1;
        }


        .no-notifications strong {

            display: block;

            color: #64748b;

            font-size: 11px;

            margin-bottom: 3px;
        }


        .no-notifications span {

            font-size: 9px;

            color: #94a3b8;
        }


        /* =====================================================
           POPUP FOOTER
        ===================================================== */

        .notification-popup-footer {

            padding: 9px 14px;

            border-top: 1px solid #edf0f5;

            text-align: center;

            background: #fafbfc;
        }


        .notification-popup-footer a {

            color: #2563eb;

            font-size: 9px;

            font-weight: 600;

            text-decoration: none;
        }


        .notification-popup-footer a:hover {

            text-decoration: underline;
        }


        /* =====================================================
           RESERVATION MESSAGE
        ===================================================== */

        .reservation-message-area {

            margin-bottom: 22px;
        }


        .reservation-message {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 16px 18px;

            background: #eff6ff;

            border: 1px solid #bfdbfe;

            border-left: 4px solid #2563eb;

            border-radius: 14px;

            box-shadow:
                0 5px 18px
                rgba(37, 99, 235, 0.08);

            margin-bottom: 10px;
        }


        .reservation-message-icon {

            width: 38px;

            height: 38px;

            min-width: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #ffffff;

            color: #2563eb;

            font-size: 18px;
        }


        .reservation-message-content {

            flex: 1;

            min-width: 0;
        }


        .reservation-message-content strong {

            display: block;

            color: #1e3a8a;

            font-size: 13px;

            font-weight: 800;

            line-height: 1.6;
        }


        /* =====================================================
           🤖 RECOMMENDATION SECTION
        ===================================================== */

        .recommendation-section {

            margin-top: 32px;

            padding: 28px;

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 18px;

            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, 0.05);
        }


        .recommendation-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }


        .recommendation-label {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 6px 11px;

            border-radius: 20px;

            background: #e0e6fa;

            color: #1f56ed;

            font-size: 11px;

            font-weight: 700;
        }


        .recommendation-header h4 {

            margin: 10px 0 5px;

            color: #172033;

            font-size: 23px;

            font-weight: 800;
        }


        .recommendation-header p {

            margin: 0;

            color: #94a3b8;

            font-size: 13px;
        }


        .recommendation-view-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            border-radius: 10px;

            background: #1f56ed;

            color: #ffffff;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

            transition: .2s ease;
        }


        .recommendation-view-btn:hover {

            color: #ffffff;

            transform: translateY(-1px);

            opacity: .92;
        }


        .recommendation-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;
        }


        .recommendation-card {

            display: flex;

            gap: 14px;

            padding: 18px;

            background: #ffffff;

            border: 1px solid #edf0f5;

            border-radius: 15px;

            transition: .25s ease;
        }


        .recommendation-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 25px
                rgba(15, 23, 42, 0.08);
        }


        .recommendation-icon {

            width: 48px;

            height: 58px;

            min-width: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 11px;

            background: #e0e6fa;

            color: #1f56ed;

            font-size: 22px;
        }


        .recommendation-content {

            min-width: 0;

            flex: 1;
        }


        .recommendation-category {

            display: block;

            color: #1f56ed;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;
        }


        .recommendation-content h3 {

            margin: 5px 0 7px;

            color: #172033;

            font-size: 15px;

            font-weight: 800;

            line-height: 1.4;
        }


        .recommendation-author {

            margin: 0 0 5px;

            color: #64748b;

            font-size: 11px;
        }


        .recommendation-isbn {

            margin: 0;

            color: #94a3b8;

            font-size: 10px;
        }


        .recommendation-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 8px;

            margin-top: 14px;
        }


        .available-book {

            color: #198754;

            font-size: 10px;

            font-weight: 700;
        }


        .recommendation-btn {

            padding: 7px 10px;

            border-radius: 8px;

            background: #1f56ed;

            color: #ffffff;

            text-decoration: none;

            font-size: 10px;

            font-weight: 700;

            transition: .2s ease;
        }


        .recommendation-btn:hover {

            color: #ffffff;

            opacity: .9;
        }


        .no-recommendations {

            padding: 40px 20px;

            text-align: center;

            border: 1px dashed #dce2e8;

            border-radius: 14px;
        }


        .no-recommendations > i {

            font-size: 35px;

            color: #f07800;
        }


        .no-recommendations h3 {

            margin: 12px 0 6px;

            color: #172033;

            font-size: 18px;

            font-weight: 700;
        }


        .no-recommendations p {

            margin-bottom: 15px;

            color: #94a3b8;

            font-size: 12px;
        }


        .no-recommendations a {

            display: inline-block;

            padding: 9px 15px;

            border-radius: 9px;

            background: #f07800;

            color: #ffffff;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;
        }


        /* =====================================================
           DARK MODE - RESERVATION
        ===================================================== */

        body.library-dark-mode
        .reservation-message {

            background: #172554;

            border-color: #1e40af;

            border-left-color: #60a5fa;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.20);
        }


        body.library-dark-mode
        .reservation-message-icon {

            background: #1e293b;

            color: #60a5fa;
        }


        body.library-dark-mode
        .reservation-message-content strong {

            color: #dbeafe;
        }


        /* =====================================================
           DARK MODE - NOTIFICATION
        ===================================================== */

        body.library-dark-mode
        .notification-popup {

            background: #111827;

            border-color: #334155;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.40);
        }


        body.library-dark-mode
        .notification-popup-header {

            background: #111827;

            border-bottom-color: #334155;
        }


        body.library-dark-mode
        .notification-popup-title strong {

            color: #f8fafc;
        }


        body.library-dark-mode
        .notification-popup-title i {

            background: #172554;

            color: #60a5fa;
        }


        body.library-dark-mode
        .notification-item:hover {

            background: #1e293b;
        }


        body.library-dark-mode
        .notification-item-content strong {

            color: #f1f5f9;
        }


        body.library-dark-mode
        .notification-item-content p {

            color: #94a3b8;
        }


        body.library-dark-mode
        .notification-item-icon {

            background: #422006;

            color: #fbbf24;
        }


        body.library-dark-mode
        .notification-item-content .due-date {

            background: #172554;

            color: #60a5fa;
        }


        body.library-dark-mode
        .no-notifications i {

            color: #475569;
        }


        body.library-dark-mode
        .no-notifications strong {

            color: #cbd5e1;
        }


        body.library-dark-mode
        .no-notifications span {

            color: #64748b;
        }


        body.library-dark-mode
        .notification-popup-footer {

            background: #0f172a;

            border-top-color: #334155;
        }


        /* =====================================================
           DARK MODE - RECOMMENDATIONS
        ===================================================== */

        body.library-dark-mode
        .recommendation-section {

            background: #1e293b;

            border-color: #334155;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.20);
        }


        body.library-dark-mode
        .recommendation-header h4 {

            color: #f8fafc;
        }


        body.library-dark-mode
        .recommendation-header p {

            color: #94a3b8;
        }


        body.library-dark-mode
        .recommendation-card {

            background: #172033;

            border-color: #334155;
        }


        body.library-dark-mode
        .recommendation-card:hover {

            box-shadow:
                0 10px 25px
                rgba(0, 0, 0, 0.25);
        }


        body.library-dark-mode
        .recommendation-icon {

            background: #422006;

            color: #fbbf24;
        }


        body.library-dark-mode
        .recommendation-content h3 {

            color: #f8fafc;
        }


        body.library-dark-mode
        .recommendation-author {

            color: #94a3b8;
        }


        body.library-dark-mode
        .no-recommendations {

            border-color: #475569;
        }


        body.library-dark-mode
        .no-recommendations h3 {

            color: #f8fafc;
        }


        /* =====================================================
           DARK MODE - PAGE
        ===================================================== */

        body.library-dark-mode
        .dashboard-content {

            color: #e2e8f0;
        }


        body.library-dark-mode
        .welcome-box {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #e2e8f0 !important;
        }


        body.library-dark-mode
        .welcome-box h2 {

            color: #f8fafc !important;
        }


        body.library-dark-mode
        .welcome-box p {

            color: #94a3b8 !important;
        }


        body.library-dark-mode
        .stat-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.20) !important;
        }


        body.library-dark-mode
        .stat-card h6 {

            color: #94a3b8 !important;
        }


        body.library-dark-mode
        .stat-card h2 {

            color: #f8fafc !important;
        }


        body.library-dark-mode
        .section-title {

            color: #f8fafc !important;
        }


        body.library-dark-mode
        .quick-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #e2e8f0 !important;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.18) !important;
        }


        body.library-dark-mode
        .quick-card h6 {

            color: #f8fafc !important;
        }


        body.library-dark-mode
        .quick-card p {

            color: #94a3b8 !important;
        }


        /* =====================================================
           DARK MODE - NAVBAR
        ===================================================== */

        body.library-dark-mode
        .user-navbar {

            background: #111827 !important;

            border-bottom-color: #263449 !important;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.25);
        }


        body.library-dark-mode
        .user-welcome h5 {

            color: #f8fafc !important;
        }


        body.library-dark-mode
        .user-welcome-icon {

            background: #172554 !important;

            color: #60a5fa !important;
        }


        body.library-dark-mode
        .library-status {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #94a3b8 !important;
        }


        body.library-dark-mode
        .nav-action {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #cbd5e1 !important;
        }


        body.library-dark-mode
        .nav-action:hover {

            background: #172554 !important;

            border-color: #3b82f6 !important;

            color: #60a5fa !important;
        }


        body.library-dark-mode
        .favorite-nav-action {

            color: #fb7185 !important;
        }


        body.library-dark-mode
        .favorite-nav-action:hover {

            background: #3f172a !important;

            border-color: #881337 !important;

            color: #fb7185 !important;
        }


        body.library-dark-mode
        .theme-toggle-btn {

            background: #1e293b !important;

            border-color: #334155 !important;

            color: #facc15 !important;
        }


        body.library-dark-mode
        .theme-toggle-btn:hover {

            background: #422006 !important;

            border-color: #92400e !important;

            color: #fde68a !important;
        }


        body.library-dark-mode
        .nav-separator {

            background: #334155 !important;
        }


        body.library-dark-mode
        .user-profile-pill {

            background: #1e293b !important;

            border-color: #334155 !important;
        }


        body.library-dark-mode
        .user-profile-name strong {

            color: #f1f5f9 !important;
        }


        body.library-dark-mode
        .user-profile-name small {

            color: #94a3b8 !important;
        }


        body.library-dark-mode
        .user-logout {

            background: #3f172a !important;

            border-color: #7f1d1d !important;

            color: #f87171 !important;
        }


        body.library-dark-mode
        .user-logout:hover {

            background: #ef4444 !important;

            border-color: #ef4444 !important;

            color: #ffffff !important;
        }


        /* =====================================================
           THEME TOGGLE
        ===================================================== */

        .theme-toggle-btn {

            width: 40px;

            height: 40px;

            border-radius: 11px;

            background: #f8fafc;

            border: 1px solid #e8edf3;

            color: #64748b;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            font-size: 17px;

            transition: all .25s ease;
        }


        .theme-toggle-btn:hover {

            background: #eff6ff;

            border-color: #bfdbfe;

            color: #2563eb;

            transform: translateY(-1px);
        }


        /* =====================================================
           SEPARATOR
        ===================================================== */

        .nav-separator {

            width: 1px;

            height: 34px;

            background: #e5e7eb;

            margin: 0 5px;
        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .user-profile-pill {

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 5px 10px 5px 5px;

            background: #f8fafc;

            border: 1px solid #e8edf3;

            border-radius: 30px;
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


        /* =====================================================
           LOGOUT
        ===================================================== */

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


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 992px) {

            .recommendation-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 900px) {

            .user-nav-center {

                display: none;
            }

            .user-navbar {

                padding: 0 20px;
            }

            .notification-popup {

                right: -60px;
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


            .nav-action,
            .theme-toggle-btn {

                width: 37px;

                height: 37px;

                font-size: 15px;
            }


            .user-profile-name {

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


            .notification-popup {

                position: fixed;

                top: 75px;

                right: 12px;

                width: 350px;

                max-width:
                    calc(100vw - 24px);
            }


            .recommendation-section {

                padding: 20px;
            }


            .recommendation-header {

                flex-direction: column;
            }


            .recommendation-view-btn {

                width: 100%;

                justify-content: center;
            }


            .recommendation-grid {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 450px) {

            .user-nav-right {

                gap: 5px;
            }


            .user-nav-left {

                gap: 8px;
            }


            .notification-badge {

                top: -4px;

                right: -4px;
            }


            .notification-popup {

                right: 10px;

                width:
                    calc(100vw - 20px);

                max-width: none;
            }


            .reservation-message {

                padding: 13px;
            }


            .reservation-message-content strong {

                font-size: 11px;

                line-height: 1.5;
            }
        }

    </style>

</head>


<body>


<?php include "../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- =====================================================
         TOP NAVBAR
    ===================================================== -->

    <nav class="user-navbar">


        <!-- LEFT -->

        <div class="user-nav-left">

            <div class="user-welcome-icon">

                <i class="bi bi-book-half"></i>

            </div>


            <div class="user-welcome">

                <span>
                    Welcome back user
                </span>


                <h5>

                    <?php
                    echo htmlspecialchars(
                        $userName
                    );
                    ?>

                </h5>

            </div>

        </div>


        <!-- CENTER -->

        <div class="user-nav-center">

            <div class="library-status">

                <span class="status-circle"></span>

                <span>
                    Library is Open
                </span>

            </div>

        </div>


        <!-- RIGHT -->

        <div class="user-nav-right">


            <!-- FAVORITE -->

            <a
                href="<?php echo BASE_URL; ?>/user/favorites/index.php"
                class="nav-action favorite-nav-action"
                title="Favorite Books"
                aria-label="Favorite Books"
            >

                <i class="bi bi-heart"></i>

            </a>


            <!-- NOTIFICATION -->

            <div class="notification-wrapper">


                <button
                    type="button"
                    id="notificationButton"
                    class="nav-action notification-nav-action"
                    title="Notifications"
                    aria-label="Notifications"
                    aria-expanded="false"
                >

                    <i class="bi bi-bell-fill"></i>


                    <?php if ($totalNotifications > 0): ?>

                        <span class="notification-badge">

                            <?php
                            echo $totalNotifications;
                            ?>

                        </span>

                    <?php endif; ?>

                </button>


                <!-- NOTIFICATION POPUP -->

                <div
                    id="notificationPopup"
                    class="notification-popup"
                    role="dialog"
                    aria-label="Notifications"
                >


                    <!-- POPUP HEADER -->

                    <div class="notification-popup-header">

                        <div class="notification-popup-title">

                            <i class="bi bi-bell-fill"></i>

                            <strong>
                                Notifications
                            </strong>

                        </div>


                        <span class="notification-count">

                            <?php
                            echo $totalNotifications;
                            ?>

                        </span>

                    </div>


                    <!-- POPUP BODY -->

                    <div class="notification-popup-body">


                        <?php if ($totalNotifications > 0): ?>


                            <?php foreach (
                                $notifications
                                as $notification
                            ): ?>


                                <div class="notification-item">


                                    <div class="notification-item-icon">

                                        <?php if (
                                            $notification['days_remaining'] == 0
                                        ): ?>

                                            <i class="bi bi-exclamation-circle-fill"></i>

                                        <?php else: ?>

                                            <i class="bi bi-clock-fill"></i>

                                        <?php endif; ?>

                                    </div>


                                    <div class="notification-item-content">


                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $notification[
                                                    'notification_title'
                                                ]
                                            );
                                            ?>

                                        </strong>


                                        <p>

                                            <b>

                                                <?php
                                                echo htmlspecialchars(
                                                    $notification['title']
                                                );
                                                ?>

                                            </b>


                                            <?php if (
                                                $notification['days_remaining'] == 0
                                            ): ?>

                                                is due today.

                                            <?php elseif (
                                                $notification['days_remaining'] == 1
                                            ): ?>

                                                is due tomorrow.

                                            <?php else: ?>

                                                is due in

                                                <?php
                                                echo (int)$notification[
                                                    'days_remaining'
                                                ];
                                                ?>

                                                days.

                                            <?php endif; ?>

                                        </p>


                                        <span class="due-date">

                                            <i class="bi bi-calendar-event"></i>

                                            Due:

                                            <?php
                                            echo htmlspecialchars(
                                                $notification['due_date']
                                            );
                                            ?>

                                        </span>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="no-notifications">

                                <i class="bi bi-bell-slash"></i>

                                <strong>
                                    No new notifications
                                </strong>

                                <span>
                                    You don't have any due-date reminders right now.
                                </span>

                            </div>


                        <?php endif; ?>

                    </div>


                    <!-- POPUP FOOTER -->

                    <div class="notification-popup-footer">

                        <a
                            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                        >
                            View My Books
                        </a>

                    </div>

                </div>

            </div>


            <!-- LIGHT / DARK MODE -->

            <button
                type="button"
                id="darkModeToggle"
                class="theme-toggle-btn"
                title="Switch to Dark Mode"
                aria-label="Switch to Dark Mode"
            >

                <i class="bi bi-moon-fill"></i>

            </button>


            <!-- DIVIDER -->

            <div class="nav-separator"></div>


            <!-- PROFILE -->

            <div class="user-profile-pill">


                <div class="user-avatar">

                    <?php
                    echo htmlspecialchars(
                        $userInitial
                    );
                    ?>

                </div>


                <div class="user-profile-name">

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $userName
                        );
                        ?>

                    </strong>


                    <small>
                        Member
                    </small>

                </div>

            </div>


            <!-- LOGOUT -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="user-logout"
                title="Logout"
                aria-label="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>

        </div>

    </nav>


    <!-- =====================================================
         DASHBOARD
    ===================================================== -->

    <div class="dashboard-content">


        <!-- =================================================
             RESERVATION AVAILABILITY MESSAGE
        ================================================== -->

        <?php if (!empty($availabilityMessages)): ?>

            <div class="reservation-message-area">


                <?php foreach (
                    $availabilityMessages
                    as $availabilityMessage
                ): ?>


                    <div class="reservation-message">


                        <div class="reservation-message-icon">

                            <i class="bi bi-book-half"></i>

                        </div>


                        <div class="reservation-message-content">

                            <strong>

                                📚

                                <?php
                                echo htmlspecialchars(
                                    $availabilityMessage['title']
                                );
                                ?>

                                is now available!

                                Your reservation is waiting for admin approval.

                            </strong>

                        </div>

                    </div>


                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             WELCOME
        ================================================== -->

        <div class="welcome-box">

            <h2>

                Welcome,

                <?php
                echo htmlspecialchars(
                    $userName
                );
                ?>

            </h2>


            <p>

                Explore books, check your issued books,
                and manage your library account.

            </p>

        </div>


        <!-- =================================================
             STAT CARDS
        ================================================== -->

        <div class="row g-4">


            <!-- Total Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-card-content">

                        <div>

                            <h6>
                                Total Books
                            </h6>

                            <h2>

                                <?php
                                echo $total_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon blue">

                            <i class="bi bi-book"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Issued Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-card-content">

                        <div>

                            <h6>
                                Currently Issued
                            </h6>

                            <h2>

                                <?php
                                echo $issued_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon green">

                            <i class="bi bi-journal-bookmark"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Returned Books -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-card-content">

                        <div>

                            <h6>
                                Returned Books
                            </h6>

                            <h2>

                                <?php
                                echo $returned_books;
                                ?>

                            </h2>

                        </div>


                        <div class="stat-icon orange">

                            <i class="bi bi-clock-history"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <h4 class="section-title">

            Quick Actions

        </h4>


        <div class="row g-4">


            <!-- Browse Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                    class="quick-card"
                >

                    <i class="bi bi-book"></i>

                    <h6>
                        Browse Books
                    </h6>

                    <p>
                        View all available books
                    </p>

                </a>

            </div>


            <!-- History Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/my_books/history.php"
                    class="quick-card"
                >

                    <i class="bi bi-clock-history"></i>

                    <h6>
                        History Book
                    </h6>

                    <p>
                        Find your History
                    </p>

                </a>

            </div>


            <!-- My Books -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                    class="quick-card"
                >

                    <i class="bi bi-journal-bookmark"></i>

                    <h6>
                        My Books
                    </h6>

                    <p>
                        View currently issued books
                    </p>

                </a>

            </div>


            <!-- My Profile -->

            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/profile.php"
                    class="quick-card"
                >

                    <i class="bi bi-person"></i>

                    <h6>
                        My Profile
                    </h6>

                    <p>
                        Manage your account
                    </p>

                </a>

            </div>

        </div>


        <!-- =================================================
             🤖 BOOK RECOMMENDATIONS
        ================================================== -->

        <section class="recommendation-section">


            <div class="recommendation-header">


                <div>

                    <span class="recommendation-label">

                        <i class="bi bi-robot"></i>

                        Smart Recommendation

                    </span>


                    <h4>
                        Recommended For You
                    </h4>


                    <p>

                        Books recommended based on your
                        reading history and categories.

                    </p>

                </div>


                <a
                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                    class="recommendation-view-btn"
                >

                    View All

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


            <?php if (!empty($recommendedBooks)): ?>


                <div class="recommendation-grid">


                    <?php foreach (
                        $recommendedBooks
                        as $book
                    ): ?>


                        <div class="recommendation-card">


                            <div class="recommendation-icon">

                                <i class="bi bi-book-half"></i>

                            </div>


                            <div class="recommendation-content">


                                <span class="recommendation-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $book['category_name']
                                    );
                                    ?>

                                </span>


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $book['title']
                                    );
                                    ?>

                                </h3>


                                <p class="recommendation-author">

                                    <i class="bi bi-person"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $book['author']
                                    );
                                    ?>

                                </p>


                                <?php if (
                                    !empty($book['isbn'])
                                ): ?>

                                    <p class="recommendation-isbn">

                                        ISBN:

                                        <?php
                                        echo htmlspecialchars(
                                            $book['isbn']
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>


                                <div class="recommendation-footer">


                                    <span class="available-book">

                                        <i class="bi bi-check-circle-fill"></i>

                                        Available

                                    </span>


                                   <a
    href="<?php echo BASE_URL; ?>/user/books/details.php?id=<?php echo (int)$book['id']; ?>"
    class="recommendation-btn"
>
    <i class="bi bi-eye"></i>
    View Book
</a>

                                </div>

                            </div>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="no-recommendations">

                    <i class="bi bi-book"></i>


                    <h3>
                        No recommendations yet
                    </h3>


                    <p>

                        Start borrowing books and
                        we'll recommend similar books for you.

                    </p>


                    <a
                        href="<?php echo BASE_URL; ?>/user/books/index.php"
                    >

                        Browse Books

                    </a>

                </div>


            <?php endif; ?>


        </section>


    </div>

</div>


<!-- =====================================================
     SIDEBAR + NOTIFICATION + THEME SCRIPT
===================================================== -->

<script>


/* =====================================================
   SIDEBAR
===================================================== */

function toggleSidebar()
{
    const sidebar =
        document.querySelector(
            ".user-sidebar"
        );

    if (sidebar) {

        sidebar.classList.toggle(
            "show"
        );
    }
}


/* =====================================================
   NOTIFICATION POPUP
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function ()
    {

        const notificationButton =
            document.getElementById(
                "notificationButton"
            );


        const notificationPopup =
            document.getElementById(
                "notificationPopup"
            );


        if (
            !notificationButton ||
            !notificationPopup
        ) {

            return;
        }


        /* OPEN / CLOSE */

        notificationButton.addEventListener(
            "click",
            function (event)
            {

                event.stopPropagation();


                const isOpen =
                    notificationPopup.classList.contains(
                        "show"
                    );


                if (isOpen) {

                    notificationPopup.classList.remove(
                        "show"
                    );

                    notificationButton.classList.remove(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                } else {

                    notificationPopup.classList.add(
                        "show"
                    );

                    notificationButton.classList.add(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "true"
                    );
                }

            }
        );


        /* PREVENT POPUP CLOSE */

        notificationPopup.addEventListener(
            "click",
            function (event)
            {

                event.stopPropagation();

            }
        );


        /* CLOSE OUTSIDE */

        document.addEventListener(
            "click",
            function ()
            {

                notificationPopup.classList.remove(
                    "show"
                );

                notificationButton.classList.remove(
                    "active"
                );

                notificationButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }
        );


        /* ESCAPE */

        document.addEventListener(
            "keydown",
            function (event)
            {

                if (event.key === "Escape") {

                    notificationPopup.classList.remove(
                        "show"
                    );

                    notificationButton.classList.remove(
                        "active"
                    );

                    notificationButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );
                }

            }
        );

    }
);


/* =====================================================
   LIGHT / DARK MODE
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function ()
    {

        const body =
            document.body;


        const themeButton =
            document.getElementById(
                "darkModeToggle"
            );


        function updateThemeButton()
        {

            if (!themeButton) {

                return;
            }


            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );


            if (isDark) {

                themeButton.innerHTML =
                    '<i class="bi bi-sun-fill"></i>';

                themeButton.title =
                    "Switch to Light Mode";

                themeButton.setAttribute(
                    "aria-label",
                    "Switch to Light Mode"
                );

            } else {

                themeButton.innerHTML =
                    '<i class="bi bi-moon-fill"></i>';

                themeButton.title =
                    "Switch to Dark Mode";

                themeButton.setAttribute(
                    "aria-label",
                    "Switch to Dark Mode"
                );
            }

        }


        function applyTheme(theme)
        {

            const isDark =
                theme === "dark";


            body.classList.toggle(
                "library-dark-mode",
                isDark
            );


            document.documentElement.classList.toggle(
                "library-dark-mode",
                isDark
            );


            localStorage.setItem(
                "library_theme",
                isDark
                    ? "dark"
                    : "light"
            );


            updateThemeButton();

        }


        /* LOAD SAVED THEME */

        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );


        if (savedTheme === "dark") {

            applyTheme("dark");

        } else {

            applyTheme("light");
        }


        /* TOGGLE */

        if (themeButton) {

            themeButton.addEventListener(
                "click",
                function ()
                {

                    const isDark =
                        body.classList.contains(
                            "library-dark-mode"
                        );


                    applyTheme(
                        isDark
                            ? "light"
                            : "dark"
                    );

                }
            );
        }

    }
);

</script>


</body>

</html>