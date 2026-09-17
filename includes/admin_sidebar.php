<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_path = $_SERVER['PHP_SELF'];

$adminName = $_SESSION['user_name'] ?? 'Administrator';
$adminInitial = strtoupper(substr($adminName, 0, 1));

/*
|--------------------------------------------------------------------------
| Active Menu
|--------------------------------------------------------------------------
*/
function isAdminActive($path)
{
    global $current_path;

    $path = rtrim($path, '/');

    if ($current_path === $path) {
        return 'active';
    }

    if (strpos($current_path, $path . '/') === 0) {
        return 'active';
    }

    return '';
}


?>

<!-- =========================================================
     ADMIN SIDEBAR
========================================================= -->

<aside class="admin-sidebar" id="adminSidebar">

    <!-- Logo -->
    <!-- Admin Sidebar Logo -->
<div class="admin-sidebar-logo">

    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">

        <div class="admin-logo-icon">
            <i class="bi bi-book-half"></i>
        </div>

        <div class="admin-logo-content">
            <h4>Library</h4>
            <span>Management System</span>
        </div>

    </a>

</div>

    <!-- Sidebar Menu -->
    <div class="admin-sidebar-menu">

        <!-- Main -->
        <div class="sidebar-section-title">
            MAIN
        </div>


        <!-- Dashboard -->
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/dashboard.php'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>

            <span class="sidebar-menu-text">
                Dashboard
            </span>

        </a>


        <!-- Books -->
        <a href="<?php echo BASE_URL; ?>/admin/books/index.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/books'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-book-fill"></i>
            </span>

            <span class="sidebar-menu-text">
                Books
            </span>

        </a>


        <!-- Categories -->
        <a href="<?php echo BASE_URL; ?>/admin/categories/index.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/categories'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-tags-fill"></i>
            </span>

            <span class="sidebar-menu-text">
                Categories
            </span>

        </a>


        <!-- Users -->
        <a href="<?php echo BASE_URL; ?>/admin/users/index.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/users'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-people-fill"></i>
            </span>

            <span class="sidebar-menu-text">
                Users
            </span>

        </a>

           <a
    href="<?php echo BASE_URL; ?>/admin/reviews/index.php"
    class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/reviews/index.php'); ?>"
    
>
    <i class="bi bi-star-fill"></i>

    <span>
        Reviews
    </span>
</a>
<a href="<?php echo BASE_URL; ?>/admin/reservations/index.php"   class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/reservations/index.php'); ?>">
    <i class="bi bi-bookmark-star-fill"></i>
    <span>Reservations</span>

    <?php if (isset($totalPendingReservations) && $totalPendingReservations > 0): ?>
        <span class="sidebar-badge">
            <?php echo $totalPendingReservations; ?>
        </span>
    <?php endif; ?>
</a>


        <!-- Transactions -->
        <div class="sidebar-section-title">
            TRANSACTIONS
        </div>


        <!-- Issue & Return -->
        <a href="<?php echo BASE_URL; ?>/admin/issue/index.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/issue'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-arrow-left-right"></i>
            </span>

            <span class="sidebar-menu-text">
                Issue & Return
            </span>
        </a>


        <!-- Reports -->
        <div class="sidebar-section-title">
            REPORTS
        </div>


        <!-- Books Report -->
        <a href="<?php echo BASE_URL; ?>/admin/reports/books_report.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/reports/books_report.php'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-book"></i>
            </span>

            <span class="sidebar-menu-text">
                Books Report
            </span>

        </a>

     



        <!-- Users Report -->
        <a href="<?php echo BASE_URL; ?>/admin/reports/users_report.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/reports/users_report.php'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-person-lines-fill"></i>
            </span>

            <span class="sidebar-menu-text">
                Users Report
            </span>

        </a>


        <!-- Issue Report -->
        <a href="<?php echo BASE_URL; ?>/admin/reports/issue_report.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/reports/issue_report.php'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-bar-chart-fill"></i>
            </span>


            <span class="sidebar-menu-text">
                Issue Report
            </span>

        </a>
        <a href="<?php echo BASE_URL; ?>/admin/reports/fine_report.php" class="sidebar-link">
    <i class="bi bi-currency-rupee"></i>
    <span>Fine Report</span>
</a>

<a href="<?php echo BASE_URL; ?>/admin/reports/reservation_report.php" class="sidebar-link">
    <i class="bi bi-bookmark-star-fill"></i>
    <span>Reservation Report</span>
</a>


        <!-- Account -->
        <div class="sidebar-section-title">
            ACCOUNT
        </div>


        <!-- Profile -->
        <a href="<?php echo BASE_URL; ?>/admin/profile.php"
           class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/profile.php'); ?>">

            <span class="sidebar-menu-icon">
                <i class="bi bi-person-circle"></i>
            </span>

            <span class="sidebar-menu-text">
                My Profile
            </span>

        </a>

        
        
        
<a
    href="<?php echo BASE_URL; ?>/admin/change_password.php"
    class="sidebar-menu-item <?php echo isAdminActive(BASE_URL . '/admin/change_password.php'); ?>">
               
    <span class="sidebar-menu-icon">
    <i class="bi bi-key"></i>
    </span>

    <span class="sidebar-menu-text">Change Password</span>
</a>


        <!-- Logout -->
        <a href="<?php echo BASE_URL; ?>/logout.php"
           class="sidebar-menu-item sidebar-logout">

            <span class="sidebar-menu-icon">
                <i class="bi bi-box-arrow-right"></i>
            </span>

            <span class="sidebar-menu-text">
                Logout
            </span>

        </a>

    </div>


   

    </div>

</aside>