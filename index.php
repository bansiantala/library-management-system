<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Library Management System
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

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f7f9fc;
            color: #172033;
        }

        /* =========================
           NAVBAR
        ========================= */

        .custom-navbar {
            background: rgba(10, 23, 52, 0.97);
            padding: 16px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .navbar-brand {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff !important;
        }

        .navbar-brand i {
            color: #65a4ff;
            margin-right: 7px;
        }

        .nav-link {
            color: rgba(255,255,255,0.75) !important;
            font-size: 13px;
            font-weight: 600;
            margin-left: 18px;
            transition: 0.25s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #ffffff !important;
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            min-height: calc(100vh - 72px);

            display: flex;
            align-items: center;

            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(52,125,255,0.25),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 15% 80%,
                    rgba(86,55,190,0.22),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #09162f,
                    #112956 55%,
                    #172f68
                );

            color: white;

            position: relative;

            overflow: hidden;

            padding: 80px 20px;
        }

        .hero::before {
            content: "";

            position: absolute;

            width: 400px;
            height: 400px;

            border: 1px solid rgba(255,255,255,0.08);

            border-radius: 50%;

            right: -180px;
            top: -160px;
        }

        .hero::after {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            border: 1px solid rgba(255,255,255,0.06);

            border-radius: 50%;

            left: -150px;
            bottom: -150px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 8px 14px;

            border-radius: 30px;

            background: rgba(255,255,255,0.08);

            border:
                1px solid rgba(255,255,255,0.13);

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;

            margin-bottom: 20px;
        }

        .hero-badge i {
            color: #70a9ff;
        }

        .hero h1 {
            font-size: 58px;
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero h1 span {
            color: #69a7ff;
        }

        .hero p {
            max-width: 680px;

            color: rgba(255,255,255,0.75);

            font-size: 16px;

            line-height: 1.8;

            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;

            gap: 12px;

            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 13px 24px;

            border-radius: 10px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            transition: 0.25s;
        }

        .btn-admin {
            background: #ffffff;
            color: #183b75;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            color: #183b75;
            box-shadow:
                0 10px 25px rgba(0,0,0,0.18);
        }

        .btn-user {
            color: #ffffff;

            border:
                1px solid rgba(255,255,255,0.3);

            background:
                rgba(255,255,255,0.05);
        }

        .btn-user:hover {
            background: rgba(255,255,255,0.12);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* HERO BOOK CARD */

        .hero-visual {
            position: relative;

            display: flex;

            justify-content: center;

            align-items: center;
        }

        .book-visual {
            width: 350px;
            height: 350px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.16),
                    rgba(255,255,255,0.04)
                );

            border:
                1px solid rgba(255,255,255,0.14);

            border-radius: 35px;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow:
                0 30px 80px rgba(0,0,0,0.25);

            backdrop-filter: blur(10px);
        }

        .book-visual i {
            font-size: 150px;
            color: #ffffff;
            opacity: 0.95;
        }

        .floating-card {
            position: absolute;

            background: #ffffff;

            color: #172033;

            padding: 14px 17px;

            border-radius: 13px;

            box-shadow:
                0 15px 35px rgba(0,0,0,0.18);

            font-size: 11px;

            font-weight: 700;
        }

        .floating-card.one {
            top: 25px;
            left: -20px;
        }

        .floating-card.two {
            bottom: 30px;
            right: -15px;
        }

        .floating-card i {
            color: #2c72e8;
            margin-right: 5px;
        }

        /* =========================
           STATS
        ========================= */

        .stats-section {
            margin-top: -45px;

            position: relative;

            z-index: 5;

            padding: 0 20px;
        }

        .stats-box {
            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 15px 45px rgba(24,39,75,0.10);

            padding: 28px 15px;
        }

        .stat-item {
            text-align: center;

            padding: 10px;

            border-right:
                1px solid #edf0f5;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 29px;

            font-weight: 800;

            color: #2058b7;

            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 10px;

            font-weight: 600;

            color: #7b8495;

            text-transform: uppercase;

            letter-spacing: 0.7px;
        }

        /* =========================
           SECTION
        ========================= */

        .section {
            padding: 90px 20px;
        }

        .section-title {
            text-align: center;

            margin-bottom: 50px;
        }

        .section-title span {
            display: block;

            color: #3978df;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1.4px;

            margin-bottom: 10px;
        }

        .section-title h2 {
            font-size: 34px;

            font-weight: 800;

            margin-bottom: 10px;
        }

        .section-title p {
            max-width: 600px;

            margin: auto;

            color: #7b8495;

            font-size: 13px;

            line-height: 1.7;
        }

        /* =========================
           ABOUT
        ========================= */

        .about-card {
            background: #ffffff;

            border-radius: 20px;

            padding: 35px;

            box-shadow:
                0 8px 30px rgba(20,40,80,0.07);
        }

        .about-icon {
            width: 58px;
            height: 58px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #edf4ff;

            color: #2d70d9;

            font-size: 25px;

            margin-bottom: 20px;
        }

        .about-card h3 {
            font-size: 21px;

            font-weight: 800;

            margin-bottom: 12px;
        }

        .about-card p {
            color: #6e7787;

            font-size: 13px;

            line-height: 1.8;

            margin-bottom: 0;
        }

        /* =========================
           FEATURES
        ========================= */

        .feature-card {
            height: 100%;

            background: #ffffff;

            border:
                1px solid #edf0f5;

            border-radius: 18px;

            padding: 30px;

            transition: 0.3s;

            box-shadow:
                0 7px 25px rgba(20,40,80,0.04);
        }

        .feature-card:hover {
            transform: translateY(-7px);

            box-shadow:
                0 15px 35px rgba(20,40,80,0.10);
        }

        .feature-icon {
            width: 52px;
            height: 52px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eff5ff;

            color: #2d70d9;

            font-size: 22px;

            margin-bottom: 18px;
        }

        .feature-card h4 {
            font-size: 17px;

            font-weight: 800;

            margin-bottom: 10px;
        }

        .feature-card p {
            color: #778092;

            font-size: 12px;

            line-height: 1.7;

            margin-bottom: 0;
        }

        /* =========================
           CTA
        ========================= */

        .cta-section {
            padding: 30px 20px 90px;
        }

        .cta-box {
            background:
                linear-gradient(
                    135deg,
                    #0d2450,
                    #1c4c99
                );

            color: white;

            border-radius: 22px;

            padding: 50px 35px;

            text-align: center;

            position: relative;

            overflow: hidden;
        }

        .cta-box::before {
            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            border:
                1px solid rgba(255,255,255,0.08);

            border-radius: 50%;

            right: -100px;
            top: -120px;
        }

        .cta-box h2 {
            font-size: 29px;

            font-weight: 800;

            margin-bottom: 10px;
        }

        .cta-box p {
            color:
                rgba(255,255,255,0.72);

            font-size: 13px;

            margin-bottom: 25px;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #09162f;

            color: rgba(255,255,255,0.65);

            padding: 35px 20px;
        }

        .footer-brand {
            color: #ffffff;

            font-size: 17px;

            font-weight: 800;

            margin-bottom: 8px;
        }

        .footer-brand i {
            color: #69a7ff;

            margin-right: 6px;
        }

        footer p {
            font-size: 11px;

            margin-bottom: 0;
        }

        .footer-line {
            border-top:
                1px solid rgba(255,255,255,0.08);

            margin-top: 25px;

            padding-top: 20px;

            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 992px) {

            .hero {
                padding: 65px 20px;
            }

            .hero h1 {
                font-size: 45px;
            }

            .hero-visual {
                margin-top: 50px;
            }

            .stat-item {
                border-right: none;
            }
        }

        @media (max-width: 768px) {

            .hero {
                text-align: center;
            }

            .hero h1 {
                font-size: 37px;
            }

            .hero p {
                font-size: 14px;
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                justify-content: center;
            }

            .book-visual {
                width: 280px;
                height: 280px;
            }

            .book-visual i {
                font-size: 110px;
            }

            .floating-card.one {
                left: 10px;
            }

            .floating-card.two {
                right: 10px;
            }

            .section-title h2 {
                font-size: 28px;
            }
        }

        @media (max-width: 576px) {

            .navbar-brand {
                font-size: 16px;
            }

            .hero {
                padding: 55px 15px;
            }

            .hero h1 {
                font-size: 31px;
            }

            .hero p {
                font-size: 13px;
            }

            .hero-btn {
                width: 100%;
                text-align: center;
            }

            .book-visual {
                width: 240px;
                height: 240px;
                border-radius: 25px;
            }

            .book-visual i {
                font-size: 90px;
            }

            .section {
                padding: 70px 15px;
            }

            .about-card,
            .feature-card {
                padding: 25px;
            }
        }

    </style>

</head>

<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">

    <div class="container">

        <a
            class="navbar-brand"
            href="index.php"
        >

            <i class="bi bi-book-half"></i>

            Library Management System

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="#home"
                    >
                        Home
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#about"
                    >
                        About
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#features"
                    >
                        Features
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="login.php"
                    >
                        Admin Login
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="user_login.php"
                    >
                        User Login
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- =========================
     HERO
========================= -->

<section
    class="hero"
    id="home"
>

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <div class="hero-content">

                    <div class="hero-badge">

                        <i class="bi bi-stars"></i>

                        Smart Library Management

                    </div>


                    <h1>

                        Manage Your Library

                        <span>Smarter.</span>

                    </h1>


                    <p>

                        A modern web-based Library Management System
                        designed to simplify book management, user
                        activities, issue requests, returns and
                        library records through one centralized platform.

                    </p>


                    <div class="hero-buttons">

                        <a
                            href="login.php"
                            class="hero-btn btn-admin"
                        >

                            <i class="bi bi-shield-lock me-1"></i>

                            Admin Login

                        </a>


                        <a
                            href="user_login.php"
                            class="hero-btn btn-user"
                        >

                            <i class="bi bi-person me-1"></i>

                            User Login

                        </a>


                        <a
                            href="register.php"
                            class="hero-btn btn-user"
                        >

                            <i class="bi bi-person-plus me-1"></i>

                            Register

                        </a>

                    </div>

                </div>

            </div>


            <div class="col-lg-5">

                <div class="hero-visual">

                    <div class="book-visual">

                        <i class="bi bi-bookshelf"></i>

                    </div>


                    <div class="floating-card one">

                        <i class="bi bi-search"></i>

                        Easy Book Search

                    </div>


                    <div class="floating-card two">

                        <i class="bi bi-check-circle"></i>

                        Easy Issue & Return

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     STATS
========================= -->

<section class="stats-section">

    <div class="container">

        <div class="stats-box">

            <div class="row g-0">


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-number">
                            5+
                        </div>

                        <div class="stat-label">
                            Core Modules
                        </div>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-number">
                            2
                        </div>

                        <div class="stat-label">
                            User Roles
                        </div>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-number">
                            5
                        </div>

                        <div class="stat-label">
                            Database Tables
                        </div>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-number">
                            100%
                        </div>

                        <div class="stat-label">
                            Digital Records
                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</section>


<!-- =========================
     ABOUT
========================= -->

<section
    class="section"
    id="about"
>

    <div class="container">

        <div class="section-title">

            <span>
                About Project
            </span>

            <h2>
                Simple. Secure. Efficient.
            </h2>

            <p>
                A centralized digital solution for managing
                everyday library activities efficiently.
            </p>

        </div>


        <div class="row g-4">

            <div class="col-lg-6">

                <div class="about-card h-100">

                    <div class="about-icon">

                        <i class="bi bi-building"></i>

                    </div>

                    <h3>
                        Library Management System
                    </h3>

                    <p>

                        The system automates important library
                        activities including book management,
                        category management, user management,
                        issue requests, book returns and
                        borrowing records. It reduces manual
                        paperwork and helps maintain organized
                        and accurate information.

                    </p>

                </div>

            </div>


            <div class="col-lg-6">

                <div class="about-card h-100">

                    <div class="about-icon">

                        <i class="bi bi-shield-check"></i>

                    </div>

                    <h3>
                        Secure Role-Based Access
                    </h3>

                    <p>

                        The system provides separate access for
                        Admin and Users. Administrators can manage
                        library operations, while users can browse
                        books, submit issue requests, manage issued
                        books and view their borrowing history.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     FEATURES
========================= -->

<section
    class="section bg-white"
    id="features"
>

    <div class="container">

        <div class="section-title">

            <span>
                System Features
            </span>

            <h2>
                Everything Your Library Needs
            </h2>

            <p>
                Important features designed for administrators
                and library users.
            </p>

        </div>


        <div class="row g-4">


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-bookshelf"></i>

                    </div>

                    <h4>
                        Book Management
                    </h4>

                    <p>
                        Add, edit, delete and manage books
                        with quantity and availability details.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-tags"></i>

                    </div>

                    <h4>
                        Category Management
                    </h4>

                    <p>
                        Organize books into categories for
                        easier management and browsing.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-people"></i>

                    </div>

                    <h4>
                        User Management
                    </h4>

                    <p>
                        Manage registered users and their
                        library account information.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-journal-plus"></i>

                    </div>

                    <h4>
                        Issue Requests
                    </h4>

                    <p>
                        Users can request books while Admins
                        can approve or reject requests.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-arrow-left-right"></i>

                    </div>

                    <h4>
                        Issue & Return
                    </h4>

                    <p>
                        Track issued books, return dates,
                        actual returns and availability.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-bar-chart"></i>

                    </div>

                    <h4>
                        Reports & Records
                    </h4>

                    <p>
                        Maintain organized library records
                        and generate useful management reports.
                    </p>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =========================
     CTA
========================= -->

<section class="cta-section">

    <div class="container">

        <div class="cta-box">

            <h2>
                Ready to Manage Your Library?
            </h2>

            <p>
                Access the Library Management System
                and manage your library activities easily.
            </p>

            <div>

                <a
                    href="login.php"
                    class="hero-btn btn-admin"
                >

                    Admin Login

                </a>


                <a
                    href="user_login.php"
                    class="hero-btn btn-user"
                >

                    User Login

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <div class="container">

        <div class="text-center">

            <div class="footer-brand">

                <i class="bi bi-book-half"></i>

                Library Management System

            </div>

            <p>
                Smart, Simple & Efficient Library Management
            </p>

        </div>


        <div class="footer-line">

            <p>

                © <?php echo date("Y"); ?>

                Library Management System.

                All Rights Reserved.

            </p>

        </div>

    </div>

</footer>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>