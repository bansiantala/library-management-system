<?php

require_once "../config/auth.php";
require_once "../config/database.php";

requireAdmin();

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

// =========================
// CHANGE PASSWORD
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check empty fields
    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {
        $error = "Please fill in all password fields.";
    }

    // Check password length
    elseif (strlen($new_password) < 6) {
        $error = "New password must contain at least 6 characters.";
    }

    // Check password match
    elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    }

    else {

        // Get current admin password
        $stmt = $conn->prepare(
            "SELECT password
             FROM users
             WHERE id = ? AND role = 'admin'"
        );

        if (!$stmt) {

            $error = "Database error. Please try again.";

        } else {

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if (!$admin) {

                $error = "Admin account not found.";

            }

            // Verify current password
            elseif (!password_verify(
                $current_password,
                $admin['password']
            )) {

                $error = "Current password is incorrect.";

            }

            // Prevent same password
            elseif (password_verify(
                $new_password,
                $admin['password']
            )) {

                $error = "New password must be different from current password.";

            }

            else {

                // Hash new password
                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                // Update admin password
                $update = $conn->prepare(
                    "UPDATE users
                     SET password = ?
                     WHERE id = ? AND role = 'admin'"
                );

                if (!$update) {

                    $error = "Unable to update password.";

                } else {

                    $update->bind_param(
                        "si",
                        $hashed_password,
                        $user_id
                    );

                    if ($update->execute()) {

                        $success = "Admin password changed successfully.";

                    } else {

                        $error = "Unable to change password. Please try again.";

                    }

                    $update->close();
                }
            }

            $stmt->close();
        }
    }
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
        Change Password - Admin
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f6fb;
            font-family: Arial, Helvetica, sans-serif;
        }

        .admin-password-page {
            min-height: calc(100vh - 70px);
            padding: 24px;
            background: #f3f6fb;
        }

        .admin-password-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* PAGE HEADER */

        .admin-password-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .admin-password-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color: #ffffff;
            font-size: 21px;

            box-shadow:
                0 6px 16px
                rgba(37, 99, 235, 0.20);
        }

        .admin-password-header-text h3 {
            margin: 0;
            color: #172033;
            font-size: 24px;
            font-weight: 800;
        }

        .admin-password-header-text p {
            margin: 3px 0 0;
            color: #718096;
            font-size: 13px;
        }

        /* CARD */

        .admin-password-card {
            background: #ffffff;
            border: 1px solid #e5ebf3;
            border-radius: 15px;
            overflow: hidden;

            box-shadow:
                0 6px 22px
                rgba(15, 23, 42, 0.06);
        }

        /* CARD HEADER */

        .admin-password-card-header {
            padding: 22px 26px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1e40af
                );

            color: #ffffff;
        }

        .admin-password-card-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-password-icon {
            width: 58px;
            height: 58px;
            min-width: 58px;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.30);

            font-size: 26px;
        }

        .admin-password-card-header h2 {
            margin: 0 0 3px;
            font-size: 21px;
            font-weight: 800;
        }

        .admin-password-card-header p {
            margin: 0;
            color: #dbeafe;
            font-size: 13px;
        }

        /* CARD BODY */

        .admin-password-card-body {
            padding: 24px 26px;
        }

        /* ALERT */

        .admin-password-alert {
            margin-bottom: 15px;
            padding: 11px 14px;
            border: none;
            border-radius: 9px;
            font-size: 13px;
        }

        /* SECTION */

        .admin-password-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .admin-password-section-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #eaf2ff;
            color: #2563eb;

            font-size: 17px;
        }

        .admin-password-section h5 {
            margin: 0;
            color: #172033;
            font-size: 17px;
            font-weight: 800;
        }

        .admin-password-section span {
            display: block;
            margin-top: 2px;
            color: #94a3b8;
            font-size: 11px;
        }

        /* LABEL */

        .admin-password-label {
            display: block;
            margin-bottom: 7px;

            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        /* INPUT */

        .admin-password-input-wrapper {
            position: relative;
        }

        .admin-password-input-icon {
            position: absolute;

            left: 13px;
            top: 50%;

            transform: translateY(-50%);

            color: #64748b;
            font-size: 15px;

            pointer-events: none;
            z-index: 2;
        }

        .admin-password-input {
            width: 100%;
            height: 44px;

            padding: 9px 43px 9px 39px;

            border: 1px solid #d9e1ec;
            border-radius: 9px;

            background: #ffffff;
            color: #1e293b;

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .admin-password-input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.08);
        }

        .admin-password-input::placeholder {
            color: #a0aec0;
            font-size: 12px;
        }

        /* PASSWORD TOGGLE */

        .admin-password-toggle {
            position: absolute;

            right: 7px;
            top: 50%;

            transform: translateY(-50%);

            width: 32px;
            height: 32px;

            border: none;
            border-radius: 7px;

            background: transparent;
            color: #64748b;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;

            font-size: 15px;
        }

        .admin-password-toggle:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        /* HELP TEXT */

        .admin-password-help {
            display: flex;
            align-items: center;
            gap: 6px;

            margin-top: 6px;

            color: #94a3b8;

            font-size: 10px;
        }

        .admin-password-help i {
            color: #2563eb;
        }

        /* REQUIREMENTS */

        .admin-password-requirements {
            margin-top: 20px;

            padding: 14px 16px;

            background: #f8fbff;

            border: 1px solid #dce6f4;
            border-radius: 10px;
        }

        .admin-requirements-title {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 10px;

            color: #2563eb;

            font-size: 13px;
            font-weight: 800;
        }

        .admin-requirements-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);

            gap: 10px 20px;

            list-style: none;

            margin: 0;
            padding: 0;
        }

        .admin-requirements-list li {
            display: flex;
            align-items: center;
            gap: 6px;

            color: #64748b;
            font-size: 10px;
        }

        .admin-requirements-list li i {
            color: #16a34a;
            font-size: 11px;
        }

        /* FOOTER */

        .admin-password-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 18px;
            padding-top: 15px;

            border-top: 1px solid #edf1f6;
        }

        .admin-secure-note {
            display: flex;
            align-items: center;
            gap: 7px;

            color: #94a3b8;
            font-size: 10px;
        }

        .admin-secure-note i {
            color: #16a34a;
            font-size: 14px;
        }

        .admin-change-password-btn {
            min-width: 220px;
            height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 0 22px;

            border: none;
            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 6px 14px
                rgba(37,99,235,0.18);

            transition: 0.2s ease;
        }

        .admin-change-password-btn:hover {
            color: #ffffff;
            transform: translateY(-1px);

            box-shadow:
                0 9px 18px
                rgba(37,99,235,0.24);
        }

        /* RESPONSIVE */

        @media (max-width: 768px) {

            .admin-password-page {
                padding: 16px 13px 22px;
            }

            .admin-password-card-body {
                padding: 18px;
            }

            .admin-password-card-header {
                padding: 18px;
            }

            .admin-password-header-text h3 {
                font-size: 21px;
            }

            .admin-requirements-list {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 650px) {

            .admin-password-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-secure-note {
                justify-content: center;
            }

            .admin-change-password-btn {
                width: 100%;
                min-width: 0;
            }

            .admin-requirements-list {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 480px) {

            .admin-password-header-icon {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }

            .admin-password-header-text h3 {
                font-size: 19px;
            }

            .admin-password-header-text p {
                font-size: 11px;
            }

            .admin-password-card-header h2 {
                font-size: 18px;
            }

            .admin-password-card-header p {
                font-size: 11px;
            }

            .admin-password-section h5 {
                font-size: 15px;
            }

            .admin-password-label {
                font-size: 12px;
            }

            .admin-password-input {
                font-size: 12px;
            }

        }

    </style>

</head>

<body>

<?php require_once "../includes/admin_sidebar.php"; ?>

<div class="admin-main">

    <!-- =====================================================
         ADMIN NAVBAR
    ===================================================== -->

    <nav class="admin-navbar">

        <div class="navbar-left">

            <div class="navbar-title">

                <h5>
                    Change Password
                </h5>

                <span>

                    <i class="bi bi-house-door"></i>

                    Home

                    <i class="bi bi-chevron-right"></i>

                    Profile

                    <i class="bi bi-chevron-right"></i>

                    Change Password

                </span>

            </div>

        </div>


        <div class="navbar-right">

            <!-- Notification -->
            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >
                <i class="bi bi-bell"></i>
            </button>

            <div class="header-divider"></div>


            <!-- Admin -->
            <div class="nav-admin">

                <div class="nav-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div class="nav-admin-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? 'Admin'
                        );
                        ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>


            <!-- Logout -->
            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="admin-logout-btn"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </nav>


    <!-- =====================================================
         CONTENT
    ===================================================== -->

    <main class="admin-password-page">

        <div class="admin-password-container">

            <!-- PAGE HEADER -->

            <div class="admin-password-header">

                <div class="admin-password-header-icon">

                    <i class="bi bi-key"></i>

                </div>

                <div class="admin-password-header-text">

                    <h3>
                        Change Password
                    </h3>

                    <p>
                        Secure your administrator account with a new password
                    </p>

                </div>

            </div>


            <!-- CARD -->

            <div class="admin-password-card">

                <!-- CARD HEADER -->

                <div class="admin-password-card-header">

                    <div class="admin-password-card-header-content">

                        <div class="admin-password-icon">

                            <i class="bi bi-shield-lock"></i>

                        </div>

                        <div>

                            <h2>
                                Secure Admin Account
                            </h2>

                            <p>
                                Update your password to keep your
                                administrator account protected.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- CARD BODY -->

                <div class="admin-password-card-body">

                    <!-- SUCCESS -->

                    <?php if ($success !== ""): ?>

                        <div
                            class="alert alert-success admin-password-alert"
                            role="alert"
                        >

                            <i class="bi bi-check-circle-fill me-2"></i>

                            <?php
                            echo htmlspecialchars($success);
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- ERROR -->

                    <?php if ($error !== ""): ?>

                        <div
                            class="alert alert-danger admin-password-alert"
                            role="alert"
                        >

                            <i class="bi bi-exclamation-circle-fill me-2"></i>

                            <?php
                            echo htmlspecialchars($error);
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- SECTION -->

                    <div class="admin-password-section">

                        <div class="admin-password-section-icon">

                            <i class="bi bi-lock"></i>

                        </div>

                        <div>

                            <h5>
                                Update Your Password
                            </h5>

                            <span>
                                Enter your current password and choose a new one
                            </span>

                        </div>

                    </div>


                    <!-- FORM -->

                    <form method="POST" action="">

                        <div class="row g-4">

                            <!-- CURRENT PASSWORD -->

                            <div class="col-12">

                                <label
                                    class="admin-password-label"
                                    for="current_password"
                                >

                                    <i class="bi bi-lock me-1"></i>

                                    Current Password

                                </label>


                                <div class="admin-password-input-wrapper">

                                    <i
                                        class="bi bi-lock admin-password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="current_password"
                                        id="current_password"
                                        class="admin-password-input"
                                        placeholder="Enter your current password"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="admin-password-toggle"
                                        onclick="toggleAdminPassword(
                                            'current_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>


                                <div class="admin-password-help">

                                    <i class="bi bi-info-circle"></i>

                                    Enter the password you currently use
                                    to sign in.

                                </div>

                            </div>


                            <!-- NEW PASSWORD -->

                            <div class="col-md-6">

                                <label
                                    class="admin-password-label"
                                    for="new_password"
                                >

                                    <i class="bi bi-key me-1"></i>

                                    New Password

                                </label>


                                <div class="admin-password-input-wrapper">

                                    <i
                                        class="bi bi-key admin-password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="new_password"
                                        id="new_password"
                                        class="admin-password-input"
                                        placeholder="Enter new password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        required
                                    >


                                    <button
                                        type="button"
                                        class="admin-password-toggle"
                                        onclick="toggleAdminPassword(
                                            'new_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>


                                <div class="admin-password-help">

                                    <i class="bi bi-check-circle"></i>

                                    Minimum 6 characters

                                </div>

                            </div>


                            <!-- CONFIRM PASSWORD -->

                            <div class="col-md-6">

                                <label
                                    class="admin-password-label"
                                    for="confirm_password"
                                >

                                    <i class="bi bi-check2-square me-1"></i>

                                    Confirm New Password

                                </label>


                                <div class="admin-password-input-wrapper">

                                    <i
                                        class="bi bi-check2-square admin-password-input-icon"
                                    ></i>

                                    <input
                                        type="password"
                                        name="confirm_password"
                                        id="confirm_password"
                                        class="admin-password-input"
                                        placeholder="Confirm new password"
                                        minlength="6"
                                        autocomplete="new-password"
                                        required
                                    >


                                    <button
                                        type="button"
                                        class="admin-password-toggle"
                                        onclick="toggleAdminPassword(
                                            'confirm_password',
                                            this
                                        )"
                                        title="Show password"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>


                                <div class="admin-password-help">

                                    <i class="bi bi-shield-check"></i>

                                    Must match new password

                                </div>

                            </div>

                        </div>


                        <!-- REQUIREMENTS -->

                        <div class="admin-password-requirements">

                            <div class="admin-requirements-title">

                                <i class="bi bi-list-check"></i>

                                Password Requirements

                            </div>


                            <ul class="admin-requirements-list">

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    At least 6 characters

                                </li>

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    Different from current password

                                </li>

                                <li>

                                    <i class="bi bi-check-circle-fill"></i>

                                    Confirm password must match

                                </li>

                            </ul>

                        </div>


                        <!-- FOOTER -->

                        <div class="admin-password-footer">

                            <div class="admin-secure-note">

                                <i class="bi bi-shield-check"></i>

                                <span>
                                    Your administrator password is securely encrypted.
                                </span>

                            </div>


                            <button
                                type="submit"
                                class="admin-change-password-btn"
                            >

                                <i class="bi bi-shield-check"></i>

                                Change Password

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </main>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

    // =====================================================
    // TOGGLE SIDEBAR
    // =====================================================

    function toggleSidebar() {

        const sidebar =
            document.querySelector(".admin-sidebar");

        if (sidebar) {

            sidebar.classList.toggle("show");

        }

    }


    // =====================================================
    // SHOW / HIDE PASSWORD
    // =====================================================

    function toggleAdminPassword(inputId, button) {

        const input =
            document.getElementById(inputId);

        const icon =
            button.querySelector("i");

        if (!input || !icon) {
            return;
        }

        if (input.type === "password") {

            input.type = "text";

            icon.classList.remove(
                "bi-eye"
            );

            icon.classList.add(
                "bi-eye-slash"
            );

            button.title = "Hide password";

        } else {

            input.type = "password";

            icon.classList.remove(
                "bi-eye-slash"
            );

            icon.classList.add(
                "bi-eye"
            );

            button.title = "Show password";

        }

    }

</script>

</body>

</html>