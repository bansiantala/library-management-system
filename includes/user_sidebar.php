<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Current URL Path
|--------------------------------------------------------------------------
*/

$current_path = $_SERVER['PHP_SELF'];


/*
|--------------------------------------------------------------------------
| Active Menu Function
|--------------------------------------------------------------------------
*/

function isActivePath($path)
{
    global $current_path;

    return strpos($current_path, $path) !== false
        ? 'active'
        : '';
}

?>

<div class="user-sidebar">


    <!-- =========================
         LOGO
    ========================== -->

    <div class="sidebar-logo">

        <i class="bi bi-book-half"></i>

        <div>

            <h4>Library</h4>

            <small>User Panel</small>

        </div>

    </div>



    <!-- =========================
         MENU
    ========================== -->

    <div class="user-sidebar-menu">


        <!-- DASHBOARD -->

        <a
            href="<?php echo BASE_URL; ?>/user/dashboard.php"
            class="<?php echo isActivePath('/user/dashboard.php'); ?>"
        >

            <i class="bi bi-speedometer2"></i>

            <span>Dashboard</span>

        </a>



        <!-- BROWSE BOOKS -->

        <a
            href="<?php echo BASE_URL; ?>/user/books/index.php"
            class="<?php echo isActivePath('/user/books/index.php'); ?>"
        >

            <i class="bi bi-book"></i>

            <span>Browse Books</span>

        </a>



        <!-- SEARCH BOOKS -->



        <!-- MY BOOKS -->

        <a
            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
            class="<?php echo isActivePath('/user/my_books/index.php'); ?>"
        >

            <i class="bi bi-journal-bookmark"></i>

            <span>My Books</span>

        </a>



        <!-- HISTORY -->

        <a
            href="<?php echo BASE_URL; ?>/user/my_books/history.php"
            class="<?php echo isActivePath('/user/my_books/history.php'); ?>"
        >

            <i class="bi bi-clock-history"></i>

            <span>History</span>

        </a>



        <!-- PROFILE -->

        <a
            href="<?php echo BASE_URL; ?>/user/profile.php"
            class="<?php echo isActivePath('/user/profile.php'); ?>"
        >

            <i class="bi bi-person"></i>

            <span>My Profile</span>

        </a>



        <!-- CHANGE PASSWORD -->

        <a
            href="<?php echo BASE_URL; ?>/user/change_password.php"
            class="<?php echo isActivePath('/user/change_password.php'); ?>"
        >

            <i class="bi bi-key"></i>

            <span>Change Password</span>

        </a>



        <!-- LOGOUT -->

        <a
            href="<?php echo BASE_URL; ?>/logout.php"
            class="logout"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>Logout</span>

        </a>


    </div>

</div>