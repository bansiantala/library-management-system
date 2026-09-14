<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| SEARCH + FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$sort = $_GET['sort'] ?? 'newest';


/*
|--------------------------------------------------------------------------
| TOTAL CATEGORIES
|--------------------------------------------------------------------------
*/

$totalCategories = 0;

$totalCategoryResult = $conn->query(
    "SELECT COUNT(*) AS total FROM categories"
);

if ($totalCategoryResult) {

    $totalCategoryData =
        $totalCategoryResult->fetch_assoc();

    $totalCategories =
        (int)($totalCategoryData['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| ALLOWED SORT OPTIONS
|--------------------------------------------------------------------------
*/

$allowedSorts = [

    'newest',

    'oldest',

    'az',

    'za'

];


if (!in_array($sort, $allowedSorts, true)) {

    $sort = 'newest';

}


/*
|--------------------------------------------------------------------------
| SORT QUERY
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'oldest':

        $orderBy = "categories.id ASC";

        break;


    case 'az':

        $orderBy = "categories.category_name ASC";

        break;


    case 'za':

        $orderBy = "categories.category_name DESC";

        break;


    case 'newest':

    default:

        $orderBy = "categories.id DESC";

        break;

}


/*
|--------------------------------------------------------------------------
| GET FILTERED CATEGORIES
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT
        categories.id,
        categories.category_name

    FROM categories

    WHERE 1 = 1

";


$params = [];

$types = "";


if ($search !== '') {

    $sql .= "

        AND categories.category_name LIKE ?

    ";


    $searchValue =
        "%" . $search . "%";


    $params[] =
        $searchValue;


    $types .= "s";

}


$sql .= "

    ORDER BY " . $orderBy;

    
$stmt = $conn->prepare($sql);


if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }


    $stmt->execute();


    $result =
        $stmt->get_result();


} else {

    $result = false;

}


$filteredCategories = 0;


if ($result) {

    $filteredCategories =
        $result->num_rows;

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
        Manage Categories | Library Management System
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
           CATEGORY PAGE
        ===================================================== */

        .category-page {

            padding: 28px;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .category-page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .category-title-area h2 {

            margin: 0;

            font-size: 27px;

            font-weight: 800;

            color: #182230;

            letter-spacing: -0.4px;

        }


        .category-title-area p {

            margin: 7px 0 0;

            color: #7b8794;

            font-size: 14px;

        }


        .category-breadcrumb {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 8px;

            color: #8a96a3;

            font-size: 13px;

        }


        .category-breadcrumb a {

            color: #5664d2;

            text-decoration: none;

            font-weight: 600;

        }


        .category-breadcrumb i {

            font-size: 10px;

        }


        /* =====================================================
           ADD BUTTON
        ===================================================== */

        .add-category-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 9px;

            border: none;

            background:
                linear-gradient(
                    135deg,
                    #5664d2,
                    #4352c5
                );

            color: #fff;

            font-size: 14px;

            font-weight: 700;

            text-decoration: none;

            box-shadow:
                0 6px 16px
                rgba(86,100,210,.22);

            transition: all 0.25s ease;

            white-space: nowrap;

        }


        .add-category-btn:hover {

            color: #fff;

            transform: translateY(-2px);

            box-shadow:
                0 9px 20px
                rgba(86,100,210,.30);

        }


        .add-category-btn i {

            font-size: 17px;

        }


        /* =====================================================
           STATS
        ===================================================== */

        .category-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 24px;

        }


        .category-stat-card {

            background: #fff;

            border: 1px solid #edf0f4;

            border-radius: 14px;

            padding: 18px 20px;

            display: flex;

            align-items: center;

            gap: 15px;

            box-shadow:
                0 4px 16px
                rgba(30,41,59,.04);

        }


        .category-stat-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eef0ff;

            color: #5664d2;

            font-size: 22px;

        }


        .category-stat-info small {

            display: block;

            color: #8a96a3;

            font-size: 12px;

            margin-bottom: 3px;

        }


        .category-stat-info strong {

            color: #202938;

            font-size: 21px;

            font-weight: 800;

        }


        /* =====================================================
           SEARCH + FILTER
        ===================================================== */

        .category-filter-card {

            background: #fff;

            border: 1px solid #edf0f4;

            border-radius: 15px;

            padding: 20px 22px;

            margin-bottom: 24px;

            box-shadow:
                0 5px 20px
                rgba(30,41,59,.05);

        }


        .category-filter-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 16px;

        }


        .category-filter-title {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .category-filter-title i {

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


        .category-filter-title h5 {

            margin: 0;

            color: #202938;

            font-size: 15px;

            font-weight: 750;

        }


        .category-filter-title span {

            display: block;

            margin-top: 2px;

            color: #8b96a3;

            font-size: 12px;

        }


        .category-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                220px
                auto
                auto;

            gap: 12px;

            align-items: end;

        }


        .category-filter-field label {

            display: block;

            margin-bottom: 7px;

            color: #586474;

            font-size: 12px;

            font-weight: 700;

        }


        .category-filter-input,
        .category-filter-select {

            width: 100%;

            height: 43px;

            border: 1px solid #dfe4ea;

            border-radius: 9px;

            background: #fff;

            color: #303a49;

            font-size: 13px;

            outline: none;

            padding: 0 13px;

            transition: all .2s ease;

        }


        .category-filter-input {

            padding-left: 39px;

        }


        .category-filter-input:focus,
        .category-filter-select:focus {

            border-color: #5664d2;

            box-shadow:
                0 0 0 3px
                rgba(86,100,210,.10);

        }


        .category-search-wrapper {

            position: relative;

        }


        .category-search-wrapper i {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #98a1ad;

            font-size: 15px;

        }


        .category-search-btn,
        .category-reset-btn {

            height: 43px;

            padding: 0 17px;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            transition: all .2s ease;

            white-space: nowrap;

        }


        .category-search-btn {

            border: 1px solid #5664d2;

            background: #5664d2;

            color: #fff;

        }


        .category-search-btn:hover {

            background: #4352c5;

            border-color: #4352c5;

            color: #fff;

            transform: translateY(-1px);

        }


        .category-reset-btn {

            border: 1px solid #dfe4ea;

            background: #fff;

            color: #667180;

        }


        .category-reset-btn:hover {

            background: #f7f8fa;

            color: #3f4855;

            border-color: #cfd6de;

        }


        /* =====================================================
           ACTIVE FILTERS
        ===================================================== */

        .category-active-filters {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 15px;

            padding-top: 14px;

            border-top: 1px solid #eef1f4;

        }


        .category-filter-label {

            color: #8994a1;

            font-size: 12px;

            font-weight: 700;

            margin-right: 2px;

        }


        .category-filter-tag {

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


        .category-filter-tag i {

            font-size: 11px;

        }


        /* =====================================================
           TABLE CARD
        ===================================================== */

        .category-table-card {

            background: #fff;

            border: 1px solid #edf0f4;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 20px
                rgba(30,41,59,.05);

        }


        .category-table-top {

            padding: 20px 22px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .category-table-title {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .category-table-title i {

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


        .category-table-title h5 {

            margin: 0;

            font-size: 16px;

            font-weight: 750;

            color: #202938;

        }


        .category-table-title span {

            display: block;

            margin-top: 2px;

            font-size: 12px;

            color: #8b96a3;

        }


        .category-count-badge {

            background: #f1f3ff;

            color: #5664d2;

            border-radius: 20px;

            padding: 6px 11px;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .category-table {

            margin: 0;

        }


        .category-table thead th {

            background: #f8f9fc;

            border-bottom: 1px solid #e9edf2;

            color: #6d7785;

            font-size: 12px;

            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .4px;

            padding: 15px 20px;

            white-space: nowrap;

        }


        .category-table tbody td {

            padding: 16px 20px;

            border-bottom: 1px solid #f0f2f5;

            color: #3c4654;

            font-size: 14px;

            vertical-align: middle;

        }


        .category-table tbody tr:last-child td {

            border-bottom: none;

        }


        .category-table tbody tr {

            transition: background .2s ease;

        }


        .category-table tbody tr:hover {

            background: #fafbff;

        }


        /* =====================================================
           NUMBER
        ===================================================== */

        .category-number {

            width: 34px;

            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #f5f6f8;

            color: #6e7886;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 750;

        }


        /* =====================================================
           CATEGORY NAME
        ===================================================== */

        .category-name-wrapper {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .category-icon {

            width: 39px;

            height: 39px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #eef0ff,
                    #e5e8ff
                );

            color: #5664d2;

            font-size: 17px;

        }


        .category-name {

            font-weight: 700;

            color: #273142;

        }


        .category-id {

            font-size: 11px;

            color: #98a1ad;

            margin-top: 2px;

        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

        .category-actions {

            display: flex;

            align-items: center;

            gap: 7px;

        }


        .category-action-btn {

            width: 34px;

            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            text-decoration: none;

            transition: all .2s ease;

            border: 1px solid transparent;

        }


        .category-edit-btn {

            background: #fff7e6;

            color: #d99400;

            border-color: #ffe5ad;

        }


        .category-edit-btn:hover {

            background: #ffedc2;

            color: #b87900;

            transform: translateY(-1px);

        }


        .category-delete-btn {

            background: #fff0f0;

            color: #dc4c4c;

            border-color: #ffd8d8;

        }


        .category-delete-btn:hover {

            background: #ffe0e0;

            color: #c93737;

            transform: translateY(-1px);

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .category-empty {

            padding: 65px 20px !important;

            text-align: center;

        }


        .category-empty-icon {

            width: 65px;

            height: 65px;

            margin: 0 auto 14px;

            border-radius: 16px;

            background: #f3f4f7;

            color: #9aa3ae;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

        }


        .category-empty h5 {

            margin: 0 0 6px;

            color: #374151;

            font-size: 16px;

            font-weight: 750;

        }


        .category-empty p {

            margin: 0;

            color: #929ba7;

            font-size: 13px;

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


        /* =====================================================
           NOTIFICATION
        ===================================================== */

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

            transition: all .2s ease;

            padding: 0;

        }


        .notification-btn:hover {

            background: #f7f8fc;

            color: #5664d2;

        }


        .notification-dot {

            width: 7px;

            height: 7px;

            background: #e04f5f;

            border: 2px solid #fff;

            border-radius: 50%;

            position: absolute;

            top: 7px;

            right: 7px;

        }


        /* =====================================================
           THEME TOGGLE
        ===================================================== */

        .theme-toggle-btn {

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

            cursor: pointer;

            transition: all .2s ease;

            padding: 0;

        }


        .theme-toggle-btn:hover {

            background: #f7f8fc;

            color: #5664d2;

        }


        .header-divider {

            width: 1px;

            height: 34px;

            background: #e9edf2;

        }


        /* =====================================================
           ADMIN PROFILE
        ===================================================== */

        .nav-admin {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .nav-avatar {

            width: 39px;

            height: 39px;

            border-radius: 10px;

            background:
                linear-gradient(
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

            transition: all .2s ease;

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
           DARK MODE
        ===================================================== */

        body.library-dark-mode {

            background: #0f172a !important;

            color: #e2e8f0;

        }


        body.library-dark-mode .admin-main {

            background: #0f172a !important;

        }


        body.library-dark-mode .admin-navbar {

            background: #111827 !important;

            border-bottom-color: #334155 !important;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,.20);

        }


        body.library-dark-mode .navbar-title h5 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .navbar-title span,
        body.library-dark-mode .navbar-title span i {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .notification-btn,
        body.library-dark-mode .theme-toggle-btn {

            background: #1e293b !important;

            border-color: #475569 !important;

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .notification-btn:hover,
        body.library-dark-mode .theme-toggle-btn:hover {

            background: #334155 !important;

            color: #60a5fa !important;

        }


        body.library-dark-mode .theme-toggle-btn {

            color: #facc15 !important;

        }


        body.library-dark-mode .notification-dot {

            border-color: #1e293b;

        }


        body.library-dark-mode .header-divider {

            background: #475569 !important;

        }


        body.library-dark-mode .nav-avatar {

            background: #334155 !important;

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .nav-admin-info strong {

            color: #f8fafc !important;

        }


        body.library-dark-mode .nav-admin-info small {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .admin-logout-btn {

            background: #3f1d2a !important;

            border-color: #7f1d3c !important;

            color: #fb7185 !important;

        }


        body.library-dark-mode .admin-logout-btn:hover {

            background: #4c1d2c !important;

            color: #fda4af !important;

        }


        body.library-dark-mode .category-page {

            color: #e2e8f0;

        }


        body.library-dark-mode .category-title-area h2 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .category-title-area p {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-breadcrumb {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-breadcrumb a {

            color: #a5b4fc !important;

        }


        body.library-dark-mode .category-stat-card,
        body.library-dark-mode .category-filter-card,
        body.library-dark-mode .category-table-card {

            background: #1e293b !important;

            border-color: #334155 !important;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.18);

        }


        body.library-dark-mode .category-stat-icon,
        body.library-dark-mode .category-filter-title i,
        body.library-dark-mode .category-table-title i {

            background: #273449 !important;

            color: #a5b4fc !important;

        }


        body.library-dark-mode .category-stat-info small {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-stat-info strong {

            color: #f8fafc !important;

        }


        body.library-dark-mode .category-filter-title h5,
        body.library-dark-mode .category-table-title h5 {

            color: #f8fafc !important;

        }


        body.library-dark-mode .category-filter-title span,
        body.library-dark-mode .category-table-title span {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-filter-field label {

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .category-filter-input,
        body.library-dark-mode .category-filter-select {

            background: #111827 !important;

            border-color: #475569 !important;

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .category-filter-input::placeholder {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-search-wrapper i {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-reset-btn {

            background: #111827 !important;

            border-color: #475569 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .category-reset-btn:hover {

            background: #334155 !important;

            color: #ffffff !important;

        }


        body.library-dark-mode .category-active-filters {

            border-top-color: #334155 !important;

        }


        body.library-dark-mode .category-filter-label {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-filter-tag,
        body.library-dark-mode .category-count-badge {

            background: #273449 !important;

            border-color: #475569 !important;

            color: #a5b4fc !important;

        }


        body.library-dark-mode .category-table-top {

            border-bottom-color: #334155 !important;

        }


        body.library-dark-mode .category-table thead th {

            background: #273449 !important;

            border-bottom-color: #475569 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .category-table tbody td {

            background: #1e293b !important;

            border-bottom-color: #334155 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .category-table tbody tr:hover {

            background: #273449 !important;

        }


        body.library-dark-mode .category-table tbody tr:hover td {

            background: #273449 !important;

        }


        body.library-dark-mode .category-number {

            background: #273449 !important;

            color: #cbd5e1 !important;

        }


        body.library-dark-mode .category-icon {

            background: #273449 !important;

            color: #a5b4fc !important;

        }


        body.library-dark-mode .category-name {

            color: #f8fafc !important;

        }


        body.library-dark-mode .category-id {

            color: #94a3b8 !important;

        }


        body.library-dark-mode .category-edit-btn {

            background: #3a2e16 !important;

            border-color: #66501e !important;

            color: #fbbf24 !important;

        }


        body.library-dark-mode .category-delete-btn {

            background: #3f1d2a !important;

            border-color: #7f1d3c !important;

            color: #fb7185 !important;

        }


        body.library-dark-mode .category-empty-icon {

            background: #273449 !important;

            color: #64748b !important;

        }


        body.library-dark-mode .category-empty h5 {

            color: #e2e8f0 !important;

        }


        body.library-dark-mode .category-empty p {

            color: #94a3b8 !important;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .category-filter-grid {

                grid-template-columns:
                    1fr 220px;

            }


            .category-search-btn,
            .category-reset-btn {

                width: 100%;

            }

        }


        @media (max-width: 992px) {

            .category-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .category-page {

                padding: 22px;

            }


            .category-filter-grid {

                grid-template-columns: 1fr;

            }


            .nav-admin-info {

                display: none;

            }


            .admin-navbar {

                padding: 0 18px;

            }

        }


        @media (max-width: 768px) {

            .category-page-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .add-category-btn {

                width: 100%;

                justify-content: center;

            }


            .category-stats {

                grid-template-columns: 1fr;

            }


            .header-divider {

                display: none;

            }


            .category-page {

                padding: 18px;

            }


            .category-filter-card {

                padding: 16px;

            }


            .category-filter-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .category-table-top {

                align-items: flex-start;

                gap: 12px;

                flex-direction: column;

            }


            .category-count-badge {

                align-self: flex-start;

            }

        }


        @media (max-width: 576px) {

            .admin-navbar {

                height: 68px;

                padding: 0 12px;

            }


            .navbar-title h5 {

                font-size: 14px;

            }


            .navbar-title span {

                display: none;

            }


            .notification-btn,
            .theme-toggle-btn {

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


            .category-title-area h2 {

                font-size: 23px;

            }


            .category-table-top {

                padding: 16px;

            }


            .category-table thead th,
            .category-table tbody td {

                padding: 13px 14px;

            }


            .category-filter-input,
            .category-filter-select {

                height: 41px;

            }


            .category-search-btn,
            .category-reset-btn {

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


<!-- =====================================================
     ADMIN SIDEBAR
====================================================== -->

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- =====================================================
     MAIN AREA
====================================================== -->

<div class="admin-main">


    <!-- =================================================
         ADMIN NAVBAR
    ================================================= -->

    <nav class="admin-navbar">


        <div class="navbar-left">


            <div class="navbar-title">


                <h5>

                    Admin Dashboard

                </h5>


                <span>


                    <i class="bi bi-house-door"></i>


                    Home


                    <i class="bi bi-chevron-right"></i>


                    Categories


                </span>


            </div>


        </div>



        <div class="navbar-right">


            <!-- NOTIFICATION -->

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
                aria-label="Notifications"
            >

                <i class="bi bi-bell"></i>


            </button>


            <!-- THEME TOGGLE -->

            


            <div class="header-divider"></div>


            <!-- ADMIN PROFILE -->

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


            <!-- LOGOUT -->

            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
                title="Logout"
            >

                <i
                    class="bi bi-box-arrow-right"
                ></i>


                <span>

                    Logout

                </span>


            </a>


        </div>


    </nav>



    <!-- =================================================
         PAGE CONTENT
    ================================================= -->

    <div class="category-page">


        <!-- PAGE HEADER -->

        <div class="category-page-header">


            <div class="category-title-area">


                <div class="category-breadcrumb">


                    <a
                        href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                    >

                        Admin

                    </a>


                    <i class="bi bi-chevron-right"></i>


                    <span>

                        Categories

                    </span>


                </div>


                <h2>

                    Manage Categories

                </h2>


                <p>

                    Create, update and manage your library categories.

                </p>


            </div>


            <!-- ADD CATEGORY -->

            <a
                href="add.php"
                class="add-category-btn"
            >

                <i class="bi bi-plus-lg"></i>

                Add Category

            </a>


        </div>


        <!-- =================================================
             CATEGORY STATS
        ================================================= -->

        <div class="category-stats">


            <!-- TOTAL CATEGORIES -->

            <div class="category-stat-card">


                <div class="category-stat-icon">

                    <i class="bi bi-folder2-open"></i>

                </div>


                <div class="category-stat-info">

                    <small>

                        Total Categories

                    </small>


                    <strong>

                        <?php

                        echo $totalCategories;

                        ?>

                    </strong>


                </div>


            </div>


            <!-- MANAGEMENT -->

            <div class="category-stat-card">


                <div class="category-stat-icon">

                    <i class="bi bi-tags"></i>

                </div>


                <div class="category-stat-info">

                    <small>

                        Management

                    </small>


                    <strong>

                        Active

                    </strong>


                </div>


            </div>


            <!-- ORGANIZATION -->

            <div class="category-stat-card">


                <div class="category-stat-icon">

                    <i class="bi bi-diagram-3"></i>

                </div>


                <div class="category-stat-info">

                    <small>

                        Library Organization

                    </small>


                    <strong>

                        Organized

                    </strong>


                </div>


            </div>


        </div>


        <!-- =================================================
             SEARCH + FILTER
        ================================================= -->

        <div class="category-filter-card">


            <div class="category-filter-header">


                <div class="category-filter-title">


                    <i class="bi bi-funnel"></i>


                    <div>


                        <h5>

                            Search & Filter Categories

                        </h5>


                        <span>

                            Search category names and sort your category list.

                        </span>


                    </div>


                </div>


            </div>



            <form
                method="GET"
                action=""
            >


                <div class="category-filter-grid">


                    <!-- SEARCH -->

                    <div class="category-filter-field">


                        <label
                            for="categorySearch"
                        >

                            Search Category

                        </label>


                        <div
                            class="category-search-wrapper"
                        >


                            <i
                                class="bi bi-search"
                            ></i>


                            <input
                                type="text"
                                id="categorySearch"
                                name="search"
                                class="category-filter-input"
                                placeholder="Search category name..."
                                value="<?php
                                    echo htmlspecialchars(
                                        $search
                                    );
                                ?>"
                            >


                        </div>


                    </div>


                    <!-- SORT -->

                    <div class="category-filter-field">


                        <label
                            for="categorySort"
                        >

                            Sort By

                        </label>


                        <select
                            id="categorySort"
                            name="sort"
                            class="category-filter-select"
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


                            <option
                                value="az"
                                <?php
                                echo $sort === 'az'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                A - Z

                            </option>


                            <option
                                value="za"
                                <?php
                                echo $sort === 'za'
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                Z - A

                            </option>


                        </select>


                    </div>


                    <!-- SEARCH BUTTON -->

                    <div class="category-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <button
                            type="submit"
                            class="category-search-btn"
                        >

                            <i class="bi bi-search"></i>

                            Search

                        </button>


                    </div>


                    <!-- RESET -->

                    <div class="category-filter-field">


                        <label>
                            &nbsp;
                        </label>


                        <a
                            href="index.php"
                            class="category-reset-btn"
                        >

                            <i
                                class="bi bi-arrow-counterclockwise"
                            ></i>

                            Reset

                        </a>


                    </div>


                </div>



                <!-- ACTIVE FILTERS -->

                <?php if (
                    $search !== '' ||
                    $sort !== 'newest'
                ): ?>


                    <div class="category-active-filters">


                        <span
                            class="category-filter-label"
                        >

                            Active Filters:

                        </span>


                        <?php if (
                            $search !== ''
                        ): ?>


                            <span
                                class="category-filter-tag"
                            >

                                <i
                                    class="bi bi-search"
                                ></i>


                                Search:


                                <?php

                                echo htmlspecialchars(
                                    $search
                                );

                                ?>


                            </span>


                        <?php endif; ?>


                        <?php if (
                            $sort === 'oldest'
                        ): ?>


                            <span
                                class="category-filter-tag"
                            >

                                <i
                                    class="bi bi-sort-down"
                                ></i>


                                Oldest First

                            </span>


                        <?php elseif (
                            $sort === 'az'
                        ): ?>


                            <span
                                class="category-filter-tag"
                            >

                                <i
                                    class="bi bi-sort-alpha-down"
                                ></i>


                                A - Z

                            </span>


                        <?php elseif (
                            $sort === 'za'
                        ): ?>


                            <span
                                class="category-filter-tag"
                            >

                                <i
                                    class="bi bi-sort-alpha-down-alt"
                                ></i>


                                Z - A

                            </span>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </form>


        </div>


        <!-- =================================================
             CATEGORY TABLE
        ================================================= -->

        <div class="category-table-card">


            <!-- TABLE HEADER -->

            <div class="category-table-top">


                <div class="category-table-title">


                    <i class="bi bi-folder2"></i>


                    <div>


                        <h5>

                            All Categories

                        </h5>


                        <span>


                            Showing

                            <?php

                            echo $filteredCategories;

                            ?>


                            of


                            <?php

                            echo $totalCategories;

                            ?>


                            categories


                        </span>


                    </div>


                </div>


                <div class="category-count-badge">


                    <?php

                    echo $filteredCategories;

                    ?>


                    Categories


                </div>


            </div>


            <!-- TABLE -->

            <div class="table-responsive">


                <table
                    class="table category-table align-middle"
                >


                    <thead>


                        <tr>


                            <th
                                style="width:90px;"
                            >

                                #

                            </th>


                            <th>

                                Category

                            </th>


                            <th
                                style="width:180px;"
                            >

                                Actions

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
                            $row =
                            $result->fetch_assoc()
                        ) {

                    ?>


                        <tr>


                            <!-- NUMBER -->

                            <td>


                                <span
                                    class="category-number"
                                >

                                    <?php

                                    echo $count++;

                                    ?>

                                </span>


                            </td>


                            <!-- CATEGORY -->

                            <td>


                                <div
                                    class="category-name-wrapper"
                                >


                                    <div
                                        class="category-icon"
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-folder-fill
                                            "
                                        ></i>

                                    </div>


                                    <div>


                                        <div
                                            class="category-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $row[
                                                    'category_name'
                                                ]
                                            );

                                            ?>

                                        </div>


                                        <div
                                            class="category-id"
                                        >

                                            Category ID:

                                            #

                                            <?php

                                            echo
                                                (int)$row['id'];

                                            ?>

                                        </div>


                                    </div>


                                </div>


                            </td>


                            <!-- ACTIONS -->

                            <td>


                                <div
                                    class="category-actions"
                                >


                                    <!-- EDIT -->

                                    <a
                                        href="edit.php?id=<?php echo (int)$row['id']; ?>"
                                        class="
                                            category-action-btn
                                            category-edit-btn
                                        "
                                        title="Edit Category"
                                    >

                                        <i
                                            class="bi bi-pencil"
                                        ></i>

                                    </a>


                                    <!-- DELETE -->

                                    <a
                                        href="delete.php?id=<?php echo (int)$row['id']; ?>"
                                        class="
                                            category-action-btn
                                            category-delete-btn
                                        "
                                        title="Delete Category"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this category?'
                                            );
                                        "
                                    >

                                        <i
                                            class="bi bi-trash3"
                                        ></i>

                                    </a>


                                </div>


                            </td>


                        </tr>


                    <?php

                        }

                    } else {

                    ?>


                        <!-- EMPTY -->

                        <tr>


                            <td
                                colspan="3"
                                class="category-empty"
                            >


                                <div
                                    class="category-empty-icon"
                                >

                                    <i
                                        class="bi bi-folder-x"
                                    ></i>

                                </div>


                                <h5>

                                    No Categories Found

                                </h5>


                                <p>


                                    <?php

                                    if (
                                        $search !== ''
                                    ) {

                                        echo
                                            "No category matches your search. Try another category name.";

                                    } else {

                                        echo
                                            "Start by adding your first library category.";

                                    }

                                    ?>


                                </p>


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


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =====================================================
     SIDEBAR TOGGLE
====================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const sidebar =
            document.getElementById(
                "adminSidebar"
            );


        const toggleButton =
            document.getElementById(
                "sidebarToggle"
            );


        if (
            toggleButton &&
            sidebar
        ) {


            toggleButton.addEventListener(
                "click",
                function () {

                    sidebar.classList.toggle(
                        "show"
                    );

                }
            );


            /* CLOSE SIDEBAR OUTSIDE ON MOBILE */

            document.addEventListener(
                "click",
                function (event) {


                    if (

                        window.innerWidth <= 768 &&

                        sidebar.classList.contains(
                            "show"
                        ) &&

                        !sidebar.contains(
                            event.target
                        ) &&

                        !toggleButton.contains(
                            event.target
                        )

                    ) {


                        sidebar.classList.remove(
                            "show"
                        );


                    }

                }
            );

        }

    }
);

</script>


<!-- =====================================================
     GLOBAL THEME TOGGLE
====================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const body =
            document.body;


        const adminThemeButton =
            document.getElementById(
                "adminThemeToggle"
            );


        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );


        /* UPDATE ICON */

        function updateThemeButton() {


            if (!adminThemeButton) {

                return;

            }


            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );


            adminThemeButton.innerHTML =
                isDark
                    ? '<i class="bi bi-sun-fill"></i>'
                    : '<i class="bi bi-moon-fill"></i>';


            adminThemeButton.title =
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode";


            adminThemeButton.setAttribute(
                "aria-label",
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode"
            );

        }


        /* LOAD SAVED GLOBAL THEME */

        if (
            savedTheme === "dark"
        ) {


            body.classList.add(
                "library-dark-mode"
            );


        } else {


            body.classList.remove(
                "library-dark-mode"
            );


        }


        updateThemeButton();


        /* TOGGLE THEME */

        if (
            adminThemeButton
        ) {


            adminThemeButton.addEventListener(
                "click",
                function () {


                    body.classList.toggle(
                        "library-dark-mode"
                    );


                    const isDark =
                        body.classList.contains(
                            "library-dark-mode"
                        );


                    localStorage.setItem(
                        "library_theme",
                        isDark
                            ? "dark"
                            : "light"
                    );


                    updateThemeButton();


                }
            );


        }


    }
);

</script>


</body>

</html>