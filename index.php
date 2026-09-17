<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Management System</title>

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

        /* =====================================================
           GLOBAL
        ===================================================== */

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
            background: #f5f7fb;
            color: #172033;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .main-navbar {
            background: #ffffff;
            border-bottom: 1px solid #edf0f5;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 3px 20px rgba(20, 35, 70, 0.05);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #14213d !important;
            font-size: 19px;
            font-weight: 800;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2364d2, #4388f5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 8px 20px rgba(35, 100, 210, 0.22);
        }

        .brand-text span {
            color: #3677df;
        }

        .nav-link {
            color: #626d80 !important;
            font-size: 13px;
            font-weight: 600;
            margin-left: 22px;
            padding: 8px 0 !important;
            transition: 0.25s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #2465d3 !important;
        }

        .nav-login {
            background: #edf4ff;
            color: #2364d2 !important;
            padding: 9px 17px !important;
            border-radius: 9px;
            margin-left: 22px;
        }

        .nav-login:hover {
            background: #2364d2;
            color: #ffffff !important;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero-section {
            position: relative;
            min-height: 650px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background:
                linear-gradient(
                    135deg,
                    #f8fbff 0%,
                    #eef5ff 55%,
                    #e5efff 100%
                );
        }

        .hero-section::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(59, 125, 230, 0.08);
            right: -180px;
            top: -200px;
        }

        .hero-section::after {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(80, 55, 190, 0.06);
            left: -180px;
            bottom: -180px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #dfe9fa;
            color: #3475d7;
            border-radius: 30px;
            padding: 9px 15px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 22px;
            box-shadow: 0 6px 18px rgba(35, 80, 150, 0.07);
        }

        .hero-tag i {
            font-size: 13px;
        }

        .hero-title {
            font-size: 56px;
            line-height: 1.08;
            font-weight: 800;
            color: #13213a;
            margin-bottom: 22px;
            max-width: 650px;
        }

        .hero-title span {
            color: #286bd5;
        }

        .hero-description {
            color: #68748a;
            font-size: 15px;
            line-height: 1.9;
            max-width: 650px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #2466d2;
            color: #ffffff;
            padding: 13px 22px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(36, 102, 210, 0.22);
            transition: 0.25s ease;
        }

        .primary-btn:hover {
            color: #ffffff;
            transform: translateY(-3px);
            background: #1957bd;
            box-shadow: 0 14px 30px rgba(36, 102, 210, 0.28);
        }

        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #ffffff;
            color: #285fae;
            border: 1px solid #dce5f3;
            padding: 13px 22px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            transition: 0.25s ease;
        }

        .secondary-btn:hover {
            color: #2466d2;
            border-color: #2466d2;
            transform: translateY(-3px);
        }


        /* =====================================================
           HERO VISUAL
        ===================================================== */

        .hero-visual {
            position: relative;
            height: 470px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .library-card {
            width: 350px;
            min-height: 400px;
            background: #ffffff;
            border-radius: 25px;
            padding: 30px;
            box-shadow:
                0 30px 70px rgba(35, 67, 120, 0.15);
            border: 1px solid #e5ebf4;
            position: relative;
            overflow: hidden;
        }

        .library-card::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            background: #edf5ff;
            border-radius: 50%;
            right: -80px;
            top: -80px;
        }

        .library-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .mini-logo {
            width: 45px;
            height: 45px;
            background: #edf4ff;
            color: #286bd5;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ecf9f1;
            color: #1d9554;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 800;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: #29ad67;
            border-radius: 50%;
        }

        .book-area {
            text-align: center;
            padding: 35px 0 25px;
        }

        .book-icon-box {
            width: 145px;
            height: 165px;
            margin: auto;
            border-radius: 16px;
            background: linear-gradient(145deg, #286bd5, #5794ed);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 18px 35px rgba(40, 107, 213, 0.25);
            transform: rotate(-3deg);
        }

        .book-icon-box i {
            color: #ffffff;
            font-size: 72px;
        }

        .library-card h3 {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: #1a2942;
            margin-top: 20px;
        }

        .library-card p {
            text-align: center;
            color: #7a8495;
            font-size: 11px;
            line-height: 1.6;
        }

        .card-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 22px;
        }

        .mini-stat {
            background: #f6f8fc;
            border-radius: 11px;
            padding: 11px 5px;
            text-align: center;
        }

        .mini-stat strong {
            display: block;
            color: #2466d2;
            font-size: 15px;
            font-weight: 800;
        }

        .mini-stat span {
            display: block;
            color: #8992a2;
            font-size: 8px;
            margin-top: 3px;
        }

        .floating-info {
            position: absolute;
            background: #ffffff;
            border-radius: 13px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 15px 35px rgba(20, 40, 80, 0.13);
            z-index: 5;
        }

        .floating-info.one {
            left: -10px;
            top: 75px;
        }

        .floating-info.two {
            right: -5px;
            bottom: 70px;
        }

        .floating-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: #edf4ff;
            color: #286bd5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .floating-text strong {
            display: block;
            font-size: 10px;
            color: #25334b;
        }

        .floating-text span {
            display: block;
            font-size: 8px;
            color: #8790a0;
            margin-top: 2px;
        }


        /* =====================================================
           STATS
        ===================================================== */

        .stats-wrapper {
            margin-top: -55px;
            position: relative;
            z-index: 10;
            padding: 0 15px;
        }

        .stats-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(27, 47, 82, 0.09);
            border: 1px solid #edf0f5;
            padding: 24px 10px;
        }

        .stat-item {
            text-align: center;
            border-right: 1px solid #edf0f5;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 9px;
            border-radius: 11px;
            background: #edf4ff;
            color: #286bd5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .stat-number {
            font-size: 25px;
            font-weight: 800;
            color: #1c58b1;
        }

        .stat-label {
            color: #858e9f;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-top: 3px;
        }


        /* =====================================================
           COMMON SECTION
        ===================================================== */

        .section {
            padding: 95px 20px;
        }

        .section-label {
            color: #3275d9;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 34px;
            font-weight: 800;
            color: #17243c;
            margin-bottom: 13px;
        }

        .section-description {
            color: #7b8494;
            font-size: 13px;
            line-height: 1.8;
            max-width: 620px;
        }


        /* =====================================================
           ABOUT
        ===================================================== */

        .about-section {
            background: #ffffff;
        }

        .about-image {
            min-height: 420px;
            border-radius: 24px;
            background:
                linear-gradient(
                    145deg,
                    #0f2f68,
                    #286bd5
                );
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-image::before {
            content: "";
            position: absolute;
            width: 330px;
            height: 330px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50%;
        }

        .about-image::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 50%;
        }

        .about-book {
            position: relative;
            z-index: 2;
            width: 180px;
            height: 220px;
            border-radius: 12px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .about-book i {
            color: #ffffff;
            font-size: 90px;
        }

        .about-content {
            padding-left: 25px;
        }

        .about-list {
            margin-top: 25px;
        }

        .about-list-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
        }

        .check-icon {
            min-width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #edf7f1;
            color: #249458;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .about-list-item strong {
            display: block;
            font-size: 13px;
            color: #29364c;
            margin-bottom: 3px;
        }

        .about-list-item span {
            display: block;
            font-size: 11px;
            line-height: 1.6;
            color: #858e9e;
        }


        /* =====================================================
           FEATURES
        ===================================================== */

        .features-section {
            background: #f5f7fb;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid #e8edf4;
            border-radius: 17px;
            padding: 28px;
            height: 100%;
            transition: 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(30, 50, 90, 0.10);
            border-color: #dbe7fa;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            background: #edf4ff;
            color: #286bd5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .feature-card h4 {
            font-size: 16px;
            font-weight: 800;
            color: #27354d;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: #7d8798;
            font-size: 11px;
            line-height: 1.75;
            margin: 0;
        }


        /* =====================================================
           PROCESS
        ===================================================== */

        .process-section {
            background: #ffffff;
        }

        .process-card {
            text-align: center;
            padding: 20px;
            position: relative;
        }

        .process-number {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #edf4ff;
            color: #286bd5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 17px;
            font-size: 14px;
            font-weight: 800;
        }

        .process-card h4 {
            color: #27354c;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .process-card p {
            color: #828b9b;
            font-size: 11px;
            line-height: 1.7;
        }

        .process-line {
            position: absolute;
            width: 100%;
            height: 1px;
            background: #e3eaf4;
            top: 46px;
            left: 50%;
            z-index: 0;
        }

        .process-number {
            position: relative;
            z-index: 2;
        }


        /* =====================================================
           CTA
        ===================================================== */

        .cta-section {
            padding: 20px 20px 90px;
            background: #ffffff;
        }

        .cta-box {
            background:
                linear-gradient(
                    135deg,
                    #102b5f,
                    #286bd5
                );
            border-radius: 23px;
            padding: 55px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-box::before,
        .cta-box::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 50%;
        }

        .cta-box::before {
            width: 320px;
            height: 320px;
            right: -150px;
            top: -180px;
        }

        .cta-box::after {
            width: 250px;
            height: 250px;
            left: -120px;
            bottom: -150px;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-box h2 {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 11px;
        }

        .cta-box p {
            color: rgba(255,255,255,0.73);
            font-size: 12px;
            margin-bottom: 25px;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .cta-btn-white {
            background: #ffffff;
            color: #205cae;
            padding: 12px 21px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
            transition: 0.25s;
        }

        .cta-btn-white:hover {
            color: #205cae;
            transform: translateY(-2px);
        }

        .cta-btn-outline {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.35);
            color: #ffffff;
            padding: 12px 21px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
            transition: 0.25s;
        }

        .cta-btn-outline:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.15);
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {
            background: #09162f;
            color: rgba(255,255,255,0.65);
            padding: 40px 20px 25px;
        }

        .footer-brand {
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .footer-brand i {
            color: #6ba4f5;
            margin-right: 6px;
        }

        .footer-text {
            font-size: 10px;
            line-height: 1.7;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 22px;
            margin-top: 18px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.55);
            font-size: 10px;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        .footer-line {
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 25px;
            padding-top: 20px;
            text-align: center;
        }

        .footer-line p {
            font-size: 9px;
            margin: 0;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 992px) {

            .hero-section {
                padding: 70px 0 80px;
            }

            .hero-title {
                font-size: 45px;
            }

            .hero-visual {
                margin-top: 50px;
            }

            .about-content {
                padding-left: 0;
                margin-top: 35px;
            }

            .stat-item {
                border-right: none;
                margin-bottom: 15px;
            }

            .process-line {
                display: none;
            }

        }


        @media (max-width: 768px) {

            .hero-section {
                text-align: center;
            }

            .hero-title {
                font-size: 37px;
            }

            .hero-description {
                margin-left: auto;
                margin-right: auto;
                font-size: 13px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-visual {
                height: 430px;
            }

            .library-card {
                width: 310px;
            }

            .floating-info.one {
                left: 0;
            }

            .floating-info.two {
                right: 0;
            }

            .section-title {
                font-size: 28px;
            }

            .about-image {
                min-height: 330px;
            }

        }


        @media (max-width: 576px) {

            .brand {
                font-size: 15px;
            }

            .brand-icon {
                width: 37px;
                height: 37px;
                font-size: 17px;
            }

            .hero-section {
                padding: 55px 15px 70px;
            }

            .hero-title {
                font-size: 31px;
            }

            .hero-description {
                font-size: 12px;
            }

            .hero-buttons a {
                width: 100%;
            }

            .hero-visual {
                height: 380px;
            }

            .library-card {
                width: 270px;
                min-height: 350px;
                padding: 23px;
            }

            .book-icon-box {
                width: 115px;
                height: 135px;
            }

            .book-icon-box i {
                font-size: 55px;
            }

            .floating-info {
                padding: 9px 11px;
            }

            .floating-info.one {
                left: -5px;
                top: 45px;
            }

            .floating-info.two {
                right: -5px;
                bottom: 45px;
            }

            .floating-text strong {
                font-size: 8px;
            }

            .floating-text span {
                font-size: 7px;
            }

            .section {
                padding: 70px 15px;
            }

            .cta-section {
                padding: 15px 15px 70px;
            }

            .cta-box {
                padding: 45px 20px;
            }

            .cta-box h2 {
                font-size: 25px;
            }

            .footer-links {
                gap: 14px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg main-navbar">

    <div class="container">

        <a
            href="index.php"
            class="brand"
        >

            <div class="brand-icon">

                <i class="bi bi-book-half"></i>

            </div>

            <div class="brand-text">

                Library <span>Management</span>

            </div>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
            aria-controls="navbarMenu"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

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
                        href="#process"
                    >
                        How It Works
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link nav-login"
                        href="login.php"
                    >

                        <i class="bi bi-shield-lock me-1"></i>

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



<!-- =====================================================
     HERO
===================================================== -->

<section
    class="hero-section"
    id="home"
>

    <div class="container">

        <div class="row align-items-center">


            <div class="col-lg-7">

                <div class="hero-content">

                    <div class="hero-tag">

                        <i class="bi bi-stars"></i>

                        Smart Library Management System

                    </div>


                    <h1 class="hero-title">

                        Your Library,

                        <span>Smarter & Simpler.</span>

                    </h1>


                    <p class="hero-description">

                        Manage books, categories, users, issue requests,
                        returns and borrowing records from one centralized
                        digital platform. Make everyday library operations
                        faster, organized and easier to manage.

                    </p>


                    <div class="hero-buttons">

                        <a
                            href="user_login.php"
                            class="primary-btn"
                        >

                            <i class="bi bi-person"></i>

                            User Login

                        </a>


                        <a
                            href="login.php"
                            class="secondary-btn"
                        >

                            <i class="bi bi-shield-lock"></i>

                            Admin Login

                        </a>


                        <a
                            href="register.php"
                            class="secondary-btn"
                        >

                            <i class="bi bi-person-plus"></i>

                            Create Account

                        </a>

                    </div>

                </div>

            </div>


            <div class="col-lg-5">

                <div class="hero-visual">


                    <div class="library-card">

                        <div class="library-card-header">

                            <div class="mini-logo">

                                <i class="bi bi-book-half"></i>

                            </div>


                            
                        </div>


                        <div class="book-area">

                            <div class="book-icon-box">

                                <i class="bi bi-bookshelf"></i>

                            </div>


                            <h3>
                                Digital Library
                            </h3>


                            <p>
                                Manage your complete library
                                from one platform.
                            </p>

                        </div>


                        <div class="card-stats">

                            <div class="mini-stat">

                                <strong>Books</strong>

                                <span>Management</span>

                            </div>


                            <div class="mini-stat">

                                <strong>Users</strong>

                                <span>Accounts</span>

                            </div>


                            <div class="mini-stat">

                                <strong>Issues</strong>

                                <span>Tracking</span>

                            </div>

                        </div>

                    </div>


                    <div class="floating-info one">

                        <div class="floating-icon">

                            <i class="bi bi-search"></i>

                        </div>

                        <div class="floating-text">

                            <strong>Easy Book Search</strong>

                            <span>Find books quickly</span>

                        </div>

                    </div>


                    <div class="floating-info two">

                        <div class="floating-icon">

                            <i class="bi bi-arrow-left-right"></i>

                        </div>

                        <div class="floating-text">

                            <strong>Issue & Return</strong>

                            <span>Track library activity</span>

                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     STATS
===================================================== -->

<section class="stats-wrapper">

    <div class="container">

        <div class="stats-card">

            <div class="row g-0">


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-icon">

                            <i class="bi bi-grid"></i>

                        </div>

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

                        <div class="stat-icon">

                            <i class="bi bi-people"></i>

                        </div>

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

                        <div class="stat-icon">

                            <i class="bi bi-database"></i>

                        </div>

                        <div class="stat-number">
                            5
                        </div>

                        <div class="stat-label">
                            Main Tables
                        </div>

                    </div>

                </div>


                <div class="col-6 col-md-3">

                    <div class="stat-item">

                        <div class="stat-icon">

                            <i class="bi bi-laptop"></i>

                        </div>

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



<!-- =====================================================
     ABOUT
===================================================== -->

<section
    class="section about-section"
    id="about"
>

    <div class="container">

        <div class="row align-items-center g-5">


            <div class="col-lg-5">

                <div class="about-image">

                    <div class="about-book">

                        <i class="bi bi-book-half"></i>

                    </div>

                </div>

            </div>


            <div class="col-lg-7">

                <div class="about-content">

                    <div class="section-label">
                        About The System
                    </div>


                    <h2 class="section-title">

                        Everything Your Library
                        Needs In One Place

                    </h2>


                    <p class="section-description">

                        The Library Management System provides a centralized
                        solution for handling everyday library activities.
                        It helps administrators maintain organized records
                        while giving users a simple way to access library
                        services.

                    </p>


                    <div class="about-list">


                        <div class="about-list-item">

                            <div class="check-icon">

                                <i class="bi bi-check-lg"></i>

                            </div>

                            <div>

                                <strong>
                                    Centralized Management
                                </strong>

                                <span>
                                    Manage books, users, categories and
                                    borrowing records from one platform.
                                </span>

                            </div>

                        </div>


                        <div class="about-list-item">

                            <div class="check-icon">

                                <i class="bi bi-check-lg"></i>

                            </div>

                            <div>

                                <strong>
                                    Role-Based Access
                                </strong>

                                <span>
                                    Separate Admin and User access keeps
                                    library operations organized.
                                </span>

                            </div>

                        </div>


                        <div class="about-list-item">

                            <div class="check-icon">

                                <i class="bi bi-check-lg"></i>

                            </div>

                            <div>

                                <strong>
                                    Easy Issue & Return
                                </strong>

                                <span>
                                    Track issue requests, due dates,
                                    returns and book availability.
                                </span>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     FEATURES
===================================================== -->

<section
    class="section features-section"
    id="features"
>

    <div class="container">


        <div class="text-center mb-5">

            <div class="section-label">
                System Features
            </div>


            <h2 class="section-title">
                Powerful Library Features
            </h2>


            <p class="section-description mx-auto">

                Designed to simplify library management and provide
                users with a smooth digital experience.

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
                        Add, update and manage books with quantity,
                        category and availability information.
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
                        Organize books into categories so users can
                        easily find and browse library resources.
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
                        Manage user accounts and maintain organized
                        information about registered library members.
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
                        Users can request books while administrators
                        can approve or reject issue requests.
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
                        Track issued books, due dates, actual returns
                        and book availability automatically.
                    </p>

                </div>

            </div>


            <div class="col-md-6 col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <i class="bi bi-bell"></i>

                    </div>

                    <h4>
                        Due Date Notifications
                    </h4>

                    <p>
                        Users can receive return reminders based on
                        their selected borrowing period.
                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     HOW IT WORKS
===================================================== -->

<section
    class="section process-section"
    id="process"
>

    <div class="container">


        <div class="text-center mb-5">

            <div class="section-label">
                How It Works
            </div>


            <h2 class="section-title">
                Simple Library Workflow
            </h2>


            <p class="section-description mx-auto">

                A straightforward process for managing books
                and borrowing activities.

            </p>

        </div>


        <div class="row g-4">


            <div class="col-md-3">

                <div class="process-card">

                    <div class="process-line"></div>

                    <div class="process-number">
                        01
                    </div>

                    <h4>
                        Browse Books
                    </h4>

                    <p>
                        Users browse available books and
                        search for required resources.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="process-card">

                    <div class="process-line"></div>

                    <div class="process-number">
                        02
                    </div>

                    <h4>
                        Send Request
                    </h4>

                    <p>
                        Users select a borrowing period and
                        submit an issue request.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="process-card">

                    <div class="process-line"></div>

                    <div class="process-number">
                        03
                    </div>

                    <h4>
                        Admin Approval
                    </h4>

                    <p>
                        The administrator reviews and approves
                        or rejects the request.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="process-card">

                    <div class="process-number">
                        04
                    </div>

                    <h4>
                        Return Book
                    </h4>

                    <p>
                        Users return books before the due date
                        and the availability is updated.
                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     CTA
===================================================== -->

<section class="cta-section">

    <div class="container">

        <div class="cta-box">

            <div class="cta-content">

                <h2>
                    Ready to Manage Your Library?
                </h2>


                <p>
                    Access the system and start managing your
                    library activities digitally.
                </p>


                <div class="cta-buttons">

                    <a
                        href="user_login.php"
                        class="cta-btn-white"
                    >

                        <i class="bi bi-person me-1"></i>

                        User Login

                    </a>


                    <a
                        href="login.php"
                        class="cta-btn-outline"
                    >

                        <i class="bi bi-shield-lock me-1"></i>

                        Admin Login

                    </a>


                    <a
                        href="register.php"
                        class="cta-btn-outline"
                    >

                        <i class="bi bi-person-plus me-1"></i>

                        Register

                    </a>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="container">

        <div class="text-center">

            <div class="footer-brand">

                <i class="bi bi-book-half"></i>

                Library Management System

            </div>


            <p class="footer-text">

                Smart, simple and efficient digital library management
                for administrators and users.

            </p>


            <div class="footer-links">

                <a href="#home">
                    Home
                </a>

                <a href="#about">
                    About
                </a>

                <a href="#features">
                    Features
                </a>

                <a href="#process">
                    How It Works
                </a>

                <a href="user_login.php">
                    User Login
                </a>

            </div>

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



<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>