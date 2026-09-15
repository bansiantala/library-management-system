<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$current_path = $_SERVER['PHP_SELF'] ?? '';


/*
|--------------------------------------------------------------------------
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$adminName = $_SESSION['user_name'] ?? 'Administrator';

$adminInitial = strtoupper(
    substr($adminName, 0, 1)
);


/*
|--------------------------------------------------------------------------
| ACTIVE MENU FUNCTION
|--------------------------------------------------------------------------
|
| $path should be the full BASE_URL path.
|
| Examples:
| /library_management/admin/books
| /library_management/admin/books/index.php
|
|--------------------------------------------------------------------------
*/

function isAdminActive($path)
{
    global $current_path;

    $currentPath = rtrim($current_path, '/');

    $menuPath = rtrim($path, '/');


    /*
    |---------------------------------------------------------
    | Exact page match
    |---------------------------------------------------------
    */

    if ($currentPath === $menuPath) {
        return 'active';
    }


    /*
    |---------------------------------------------------------
    | Folder match
    |---------------------------------------------------------
    |
    | Example:
    | /admin/books/edit.php
    |
    | becomes active for:
    | /admin/books
    |
    */

    if (
        strpos(
            $currentPath,
            $menuPath . '/'
        ) === 0
    ) {

        return 'active';

    }


    return '';
}

?>

<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<aside
    class="admin-sidebar"
    id="adminSidebar"
>


    <!-- =====================================================
         LOGO
    ====================================================== -->

    <div class="admin-sidebar-logo">


        <a
            href="<?php echo BASE_URL; ?>/admin/dashboard.php"
        >


            <div class="admin-logo-icon">

                <i class="bi bi-book-half"></i>

            </div>


            <div class="admin-logo-content">

                <h4>
                    Library
                </h4>

                <span>
                    Management System
                </span>

            </div>


        </a>


    </div>


    <!-- =====================================================
         SIDEBAR MENU
    ====================================================== -->

    <div class="admin-sidebar-menu">


        <!-- =================================================
             MAIN
        ================================================== -->

        <div class="sidebar-section-title">

            MAIN

        </div>


        <!-- =================================================
             DASHBOARD
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/dashboard.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/dashboard.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-grid-1x2-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Dashboard

            </span>


        </a>


        <!-- =================================================
             BOOKS
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/books/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/books'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-book-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Books

            </span>


        </a>


        <!-- =================================================
             CATEGORIES
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/categories/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/categories'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-tags-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Categories

            </span>


        </a>


        <!-- =================================================
             REVIEWS
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reviews/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reviews'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-star-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Reviews

            </span>


        </a>


        <!-- =================================================
             RESERVATIONS
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reservations/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reservations'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-bookmark-star-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Reservations

            </span>


            <?php
            if (
                isset($totalPendingReservations) &&
                $totalPendingReservations > 0
            ):
            ?>

                <span class="sidebar-badge">

                    <?php
                    echo (int)$totalPendingReservations;
                    ?>

                </span>

            <?php endif; ?>


        </a>


        <!-- =================================================
             USERS
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/users'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-people-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Users

            </span>


        </a>


        <!-- =================================================
             TRANSACTIONS
        ================================================== -->

        <div class="sidebar-section-title">

            TRANSACTIONS

        </div>


        <!-- =================================================
             ISSUE & RETURN
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/issue/index.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/issue'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-arrow-left-right"></i>

            </span>


            <span class="sidebar-menu-text">

                Issue & Return

            </span>


        </a>


        <!-- =================================================
             REPORTS
        ================================================== -->

        <div class="sidebar-section-title">

            REPORTS

        </div>


        <!-- =================================================
             BOOKS REPORT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/books_report.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reports/books_report.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-book"></i>

            </span>


            <span class="sidebar-menu-text">

                Books Report

            </span>


        </a>


        <!-- =================================================
             USERS REPORT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/users_report.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reports/users_report.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-person-lines-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Users Report

            </span>


        </a>


        <!-- =================================================
             ISSUE REPORT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/issue_report.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reports/issue_report.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-bar-chart-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Issue Report

            </span>


        </a>


        <!-- =================================================
             FINE REPORT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/fine_report.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reports/fine_report.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-currency-rupee"></i>

            </span>


            <span class="sidebar-menu-text">

                Fine Report

            </span>


        </a>


        <!-- =================================================
             RESERVATION REPORT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/reports/reservation_report.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/reports/reservation_report.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-bookmark-star-fill"></i>

            </span>


            <span class="sidebar-menu-text">

                Reservation Report

            </span>


        </a>


        <!-- =================================================
             ACCOUNT
        ================================================== -->

        <div class="sidebar-section-title">

            ACCOUNT

        </div>


        <!-- =================================================
             PROFILE
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/admin/profile.php"
            class="
                sidebar-menu-item
                <?php
                echo isAdminActive(
                    BASE_URL . '/admin/profile.php'
                );
                ?>
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-person-circle"></i>

            </span>


            <span class="sidebar-menu-text">

                My Profile

            </span>


        </a>


        <!-- =================================================
             LOGOUT
        ================================================== -->

        <a
            href="<?php echo BASE_URL; ?>/logout.php"
            class="
                sidebar-menu-item
                sidebar-logout
            "
        >


            <span class="sidebar-menu-icon">

                <i class="bi bi-box-arrow-right"></i>

            </span>


            <span class="sidebar-menu-text">

                Logout

            </span>


        </a>


    </div>


</aside>