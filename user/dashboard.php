<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireUser();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ? AND status = 'Issued'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$issued_books = $stmt->get_result()->fetch_assoc()['total'];


$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM issued_books
     WHERE user_id = ? AND status = 'Returned'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$returned_books = $stmt->get_result()->fetch_assoc()['total'];


$total_books = $conn->query(
    "SELECT COUNT(*) AS total FROM books"
)->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>User Dashboard</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css">


    <!-- User CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css">

</head>


<body>


<?php include "../includes/user_sidebar.php"; ?>


<div class="user-main">


    <!-- TOP NAVBAR -->

   <nav class="user-navbar">

    <!-- LEFT -->
    <div class="user-nav-left">

        <div class="user-welcome-icon">
            <i class="bi bi-book-half"></i>
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

        <!-- Search -->
        <a
            href="<?php echo BASE_URL; ?>/user/books/search.php"
            class="nav-action"
            title="Search Books"
        >
            <i class="bi bi-search"></i>
        </a>


        <!-- Favorite Books -->
        <a
            href="<?php echo BASE_URL; ?>/user/favorites/index.php"
            class="nav-action favorite-nav-action"
            title="Favorite Books"
            aria-label="Favorite Books"
        >
            <i class="bi bi-heart-fill"></i>
        </a>


        <!-- My Books -->
        <a
            href="<?php echo BASE_URL; ?>/user/my_books/index.php"
            class="nav-action"
            title="My Books"
        >
            <i class="bi bi-journal-bookmark"></i>
        </a>


        <!-- Divider -->
        <div class="nav-separator"></div>


        <!-- User -->
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

                <small>
                    Member
                </small>

            </div>


        </div>


        <!-- Logout -->
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
   FAVORITE NAV ACTION
========================================= */

.favorite-nav-action {
    color: #e11d48;
}

.favorite-nav-action:hover {
    background: #fff1f2;

    border-color: #fecdd3;

    color: #e11d48;

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

    .nav-action:nth-child(3) {
        display: none;
    }

}
</style>

    <!-- DASHBOARD -->

    <div class="dashboard-content">


        <!-- WELCOME -->

        <div class="welcome-box">

            <h2>
                Welcome,
                <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
            </h2>

            <p>
                Explore books, check your issued books,
                and manage your library account.
            </p>

        </div>



        <!-- STAT CARDS -->

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
                                <?php echo $total_books; ?>
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
                                <?php echo $issued_books; ?>
                            </h2>

                        </div>

                        <div class="stat-icon green">

                            <i class="bi bi-journal-bookmark"></i>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Returned -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-card-content">

                        <div>

                            <h6>
                                Returned Books
                            </h6>

                            <h2>
                                <?php echo $returned_books; ?>
                            </h2>

                        </div>

                        <div class="stat-icon orange">

                            <i class="bi bi-clock-history"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- QUICK ACTIONS -->

        <h4 class="section-title">
            Quick Actions
        </h4>


        <div class="row g-4">


            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/books/index.php"
                    class="quick-card">

                    <i class="bi bi-book"></i>

                    <h6>
                        Browse Books
                    </h6>

                    <p>
                        View all available books
                    </p>

                </a>

            </div>



            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/books/search.php"
                    class="quick-card">

                    <i class="bi bi-search"></i>

                    <h6>
                        Search Books
                    </h6>

                    <p>
                        Find your favorite book
                    </p>

                </a>

            </div>



            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/my_books/index.php"
                    class="quick-card">

                    <i class="bi bi-journal-bookmark"></i>

                    <h6>
                        My Books
                    </h6>

                    <p>
                        View currently issued books
                    </p>

                </a>

            </div>



            <div class="col-lg-3 col-md-6">

                <a
                    href="<?php echo BASE_URL; ?>/user/profile.php"
                    class="quick-card">

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

    </div>

</div>



<script>

function toggleSidebar()
{
    document
        .querySelector('.user-sidebar')
        .classList.toggle('show');
}

</script>


</body>

</html>