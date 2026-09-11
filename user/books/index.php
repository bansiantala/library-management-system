<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$sql = "SELECT 
            books.*,
            categories.category_name
        FROM books
        INNER JOIN categories
            ON books.category_id = categories.id
        ORDER BY books.id DESC";

$result = $conn->query($sql);

$totalBooks = $result ? $result->num_rows : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Browse Books | Library Management System</title>

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


    <style>

        /* =========================================
           BROWSE BOOKS PAGE
        ========================================= */

        .books-page {
            padding: 32px;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .books-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 30px;
        }

        .books-heading {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .books-heading-icon {
            width: 54px;
            height: 54px;

            border-radius: 15px;

            background: #eff6ff;
            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;
        }

        .books-heading h2 {
            margin: 0;

            color: #172033;

            font-size: 25px;
            font-weight: 800;
        }

        .books-heading p {
            margin: 5px 0 0;

            color: #94a3b8;

            font-size: 13px;
        }


        /* =========================================
           SEARCH BUTTON
        ========================================= */

        .search-books-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 12px 19px;

            background: #2563eb;
            color: #ffffff;

            border-radius: 11px;

            text-decoration: none;

            font-size: 13px;
            font-weight: 700;

            box-shadow: 0 5px 15px rgba(37,99,235,.18);

            transition: .25s ease;
        }

        .search-books-btn:hover {
            background: #1d4ed8;
            color: #ffffff;

            transform: translateY(-2px);

            box-shadow: 0 8px 20px rgba(37,99,235,.25);
        }


        /* =========================================
           BOOK COUNT
        ========================================= */

        .books-count-bar {
            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 13px;

            padding: 13px 17px;

            margin-bottom: 25px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .books-count-left {
            display: flex;
            align-items: center;
            gap: 9px;

            color: #64748b;

            font-size: 12px;
            font-weight: 600;
        }

        .books-count-left i {
            color: #2563eb;
            font-size: 16px;
        }

        .books-count-number {
            background: #eff6ff;
            color: #2563eb;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 11px;
            font-weight: 800;
        }


        /* =========================================
           BOOK CARD
        ========================================= */

        .book-card {
            height: 100%;

            background: #ffffff;

            border: 1px solid #e8edf3;

            border-radius: 18px;

            overflow: hidden;

            box-shadow: 0 5px 18px rgba(15,23,42,.04);

            transition: all .3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);

            border-color: #cbdcfb;

            box-shadow: 0 15px 30px rgba(15,23,42,.09);
        }


        /* =========================================
           BOOK TOP
        ========================================= */

        .book-card-top {
            position: relative;

            min-height: 155px;

            padding: 25px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fbff
                );

            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* Book icon */

        .book-icon-box {
            width: 82px;
            height: 82px;

            border-radius: 20px;

            background: #ffffff;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 37px;

            box-shadow:
                0 8px 20px rgba(37,99,235,.12);
        }


        /* Category */

        .book-category {
            position: absolute;

            top: 15px;
            right: 15px;

            background: #ffffff;

            color: #2563eb;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 9px;
            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .4px;

            box-shadow: 0 3px 10px rgba(15,23,42,.06);
        }


        /* =========================================
           BOOK BODY
        ========================================= */

        .book-card-body {
            padding: 23px;
        }

        .book-title {
            margin: 0 0 8px;

            color: #172033;

            font-size: 17px;
            font-weight: 800;

            line-height: 1.4;

            min-height: 47px;
        }

        .book-author {
            display: flex;
            align-items: center;
            gap: 7px;

            color: #64748b;

            font-size: 12px;

            margin-bottom: 19px;
        }

        .book-author i {
            color: #94a3b8;
        }


        /* =========================================
           BOOK DETAILS
        ========================================= */

        .book-details {
            padding-top: 17px;

            border-top: 1px solid #eef2f7;
        }

        .book-detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;

            margin-bottom: 11px;
        }

        .book-detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: #94a3b8;

            font-size: 11px;
            font-weight: 500;
        }

        .detail-value {
            color: #334155;

            font-size: 11px;
            font-weight: 700;

            text-align: right;

            max-width: 60%;

            word-break: break-word;
        }


        /* =========================================
           AVAILABILITY
        ========================================= */

        .availability-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 9px;
            font-weight: 800;
        }

        .availability-badge.available {
            background: #ecfdf5;
            color: #059669;
        }

        .availability-badge.out {
            background: #fef2f2;
            color: #dc2626;
        }


        /* =========================================
           VIEW BUTTON
        ========================================= */

        .view-book-btn {
            width: 100%;

            margin-top: 20px;

            padding: 11px 15px;

            border-radius: 10px;

            background: #f8fafc;

            border: 1px solid #dbe3ed;

            color: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            text-decoration: none;

            font-size: 12px;
            font-weight: 700;

            transition: .25s ease;
        }

        .view-book-btn:hover {
            background: #2563eb;
            border-color: #2563eb;

            color: #ffffff;

            transform: translateY(-1px);
        }


        /* =========================================
           EMPTY STATE
        ========================================= */

        .empty-books {
            background: #ffffff;

            border: 1px dashed #cbd5e1;

            border-radius: 18px;

            padding: 65px 30px;

            text-align: center;
        }

        .empty-books-icon {
            width: 75px;
            height: 75px;

            margin: 0 auto 17px;

            border-radius: 20px;

            background: #f1f5f9;

            color: #94a3b8;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 31px;
        }

        .empty-books h4 {
            margin: 0 0 7px;

            color: #334155;

            font-size: 18px;
            font-weight: 800;
        }

        .empty-books p {
            margin: 0;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 768px) {

            .books-page {
                padding: 22px 18px;
            }

            .books-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .search-books-btn {
                width: 100%;

                justify-content: center;
            }

            .books-heading h2 {
                font-size: 21px;
            }

        }


        @media (max-width: 480px) {

            .books-page {
                padding: 17px 13px;
            }

            .books-heading-icon {
                width: 46px;
                height: 46px;

                font-size: 20px;
            }

            .books-heading p {
                font-size: 11px;
            }

            .book-card-body {
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================
     USER SIDEBAR
========================================= -->

<?php include "../../includes/user_sidebar.php"; ?>


<!-- =========================================
     MAIN
========================================= -->

<div class="user-main">


    <!-- =====================================
         SAME USER NAVBAR
    ====================================== -->

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


            <!-- User Profile -->

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

    .nav-action:nth-child(2) {
        display: none;
    }

}
    </style>


    <!-- =====================================
         CONTENT
    ====================================== -->

    <div class="books-page">


        <!-- =================================
             PAGE HEADER
        ================================== -->

        <div class="books-header">


            <div class="books-heading">

                <div class="books-heading-icon">

                    <i class="bi bi-collection"></i>

                </div>


                <div>

                    <h2>
                        Browse Books
                    </h2>

                    <p>
                        Explore and discover books available in our library.
                    </p>

                </div>

            </div>


            <a
                href="search.php"
                class="search-books-btn"
            >

                <i class="bi bi-search"></i>

                Search Books

            </a>

        </div>



        <!-- =================================
             BOOK COUNT
        ================================== -->

        <div class="books-count-bar">

            <div class="books-count-left">

                <i class="bi bi-bookshelf"></i>

                <span>
                    Books available in library
                </span>

            </div>


            <span class="books-count-number">

                <?php echo $totalBooks; ?> Books

            </span>

        </div>



        <!-- =================================
             BOOK GRID
        ================================== -->

        <div class="row g-4">


            <?php if ($result && $result->num_rows > 0): ?>


                <?php while ($book = $result->fetch_assoc()): ?>


                    <div class="col-xl-4 col-lg-6 col-md-6">


                        <div class="book-card">


                            <!-- BOOK TOP -->

                            <div class="book-card-top">


                                <div class="book-icon-box">

                                    <i class="bi bi-book-half"></i>

                                </div>


                                <span class="book-category">

                                    <?php

                                    echo htmlspecialchars(
                                        $book['category_name']
                                    );

                                    ?>

                                </span>


                            </div>



                            <!-- BOOK BODY -->

                            <div class="book-card-body">


                                <!-- Title -->

                                <h5 class="book-title">

                                    <?php

                                    echo htmlspecialchars(
                                        $book['title']
                                    );

                                    ?>

                                </h5>


                                <!-- Author -->

                                <div class="book-author">

                                    <i class="bi bi-person"></i>

                                    <span>

                                        <?php

                                        echo htmlspecialchars(
                                            $book['author']
                                        );

                                        ?>

                                    </span>

                                </div>



                                <!-- Details -->

                                <div class="book-details">


                                    <!-- Category -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">
                                            Category
                                        </span>

                                        <span class="detail-value">

                                            <?php

                                            echo htmlspecialchars(
                                                $book['category_name']
                                            );

                                            ?>

                                        </span>

                                    </div>


                                    <!-- ISBN -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">
                                            ISBN
                                        </span>

                                        <span class="detail-value">

                                            <?php

                                            echo !empty($book['isbn'])
                                                ? htmlspecialchars($book['isbn'])
                                                : 'Not available';

                                            ?>

                                        </span>

                                    </div>


                                    <!-- Quantity -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">
                                            Total Copies
                                        </span>

                                        <span class="detail-value">

                                            <?php
                                            echo (int)$book['quantity'];
                                            ?>

                                        </span>

                                    </div>


                                    <!-- Availability -->

                                    <div class="book-detail-row">

                                        <span class="detail-label">
                                            Availability
                                        </span>


                                        <?php if (
                                            $book['available_quantity'] > 0
                                        ): ?>

                                            <span
                                                class="availability-badge available"
                                            >

                                                <i class="bi bi-check-circle-fill"></i>

                                                Available
                                                (
                                                <?php
                                                echo (int)$book[
                                                    'available_quantity'
                                                ];
                                                ?>
                                                )

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="availability-badge out"
                                            >

                                                <i class="bi bi-x-circle-fill"></i>

                                                Not Available

                                            </span>

                                        <?php endif; ?>

                                    </div>


                                </div>



                                <!-- View Details -->

                                <a
                                    href="details.php?id=<?php
                                    echo (int)$book['id'];
                                    ?>"
                                    class="view-book-btn"
                                >

                                    <i class="bi bi-eye"></i>

                                    View Book Details

                                    <i class="bi bi-arrow-right"></i>

                                </a>


                            </div>

                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <!-- EMPTY -->

                <div class="col-12">

                    <div class="empty-books">

                        <div class="empty-books-icon">

                            <i class="bi bi-book"></i>

                        </div>

                        <h4>
                            No Books Found
                        </h4>

                        <p>
                            There are currently no books available in the library.
                        </p>

                    </div>

                </div>


            <?php endif; ?>


        </div>

    </div>

</div>



<!-- =========================================
     SIDEBAR SCRIPT
========================================= -->

<script>

function toggleSidebar()
{
    const sidebar =
        document.querySelector('.user-sidebar');

    if (sidebar) {

        sidebar.classList.toggle('show');

    }
}

</script>


</body>

</html>