<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Security Check
|--------------------------------------------------------------------------
| User must verify OTP before accessing this page.
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["reset_email"]) ||
    empty($_SESSION["reset_email"]) ||
    !isset($_SESSION["otp_verified"]) ||
    $_SESSION["otp_verified"] !== true
) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION["reset_email"];

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Validate Password
    |--------------------------------------------------------------------------
    */

    if ($password === "") {

        $message = "Please enter a new password.";
        $messageType = "danger";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "danger";

    } elseif ($confirmPassword === "") {

        $message = "Please confirm your new password.";
        $messageType = "danger";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "danger";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get User
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if (!$stmt) {

            $message = "Something went wrong. Please try again.";
            $messageType = "danger";

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {

                $message = "User account was not found.";
                $messageType = "danger";

            } else {

                $user = $result->fetch_assoc();
                $userId = (int)$user["id"];

                /*
                |--------------------------------------------------------------------------
                | Hash New Password
                |--------------------------------------------------------------------------
                */

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /*
                |--------------------------------------------------------------------------
                | Update Password
                |--------------------------------------------------------------------------
                */

                $updateStmt = $conn->prepare(
                    "UPDATE users SET password = ? WHERE id = ?"
                );

                if (!$updateStmt) {

                    $message =
                        "Unable to reset password. Please try again.";

                    $messageType = "danger";

                } else {

                    $updateStmt->bind_param(
                        "si",
                        $passwordHash,
                        $userId
                    );

                    if ($updateStmt->execute()) {

                        /*
                        |--------------------------------------------------------------------------
                        | Delete Used OTP
                        |--------------------------------------------------------------------------
                        */

                        $deleteOtpStmt = $conn->prepare(
                            "DELETE FROM password_otps WHERE email = ?"
                        );

                        if ($deleteOtpStmt) {
                            $deleteOtpStmt->bind_param(
                                "s",
                                $email
                            );
                            $deleteOtpStmt->execute();
                            $deleteOtpStmt->close();
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Clear Password Reset Session
                        |--------------------------------------------------------------------------
                        */

                        unset($_SESSION["reset_email"]);
                        unset($_SESSION["otp_verified"]);

                        /*
                        |--------------------------------------------------------------------------
                        | Redirect to Login
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION["password_reset_success"] =
                            "Password reset successfully. You can now login.";

                        header("Location: login.php");
                        exit();

                    } else {

                        $message =
                            "Unable to update password. Please try again.";

                        $messageType = "danger";
                    }

                    $updateStmt->close();
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
        Reset Password - Library Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            margin: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #eef4ff,
                #f8fbff
            );

            font-family: Arial, sans-serif;
        }

        .reset-card {
            width: 100%;
            max-width: 450px;

            background: #ffffff;

            border-radius: 18px;

            padding: 35px;

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.10);
        }

        .reset-icon {
            width: 70px;
            height: 70px;

            margin: 0 auto 20px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eaf2ff;

            color: #0d6efd;

            font-size: 30px;
        }

        .reset-card h2 {
            font-weight: 700;
            color: #212529;
        }

        .reset-card p {
            color: #6c757d;
        }

        .email-text {
            color: #212529 !important;
            font-weight: 600;
            word-break: break-word;
        }

        .form-label {
            font-weight: 600;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;

            right: 12px;
            top: 50%;

            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #6c757d;

            cursor: pointer;

            font-size: 18px;
        }

        .form-control {
            height: 48px;
            border-radius: 10px;
        }

        .btn-reset {
            height: 48px;

            border-radius: 10px;

            font-weight: 600;
        }

        .password-info {
            font-size: 13px;
            color: #6c757d;
        }

        @media (max-width: 500px) {

            .reset-card {
                margin: 20px;
                padding: 25px;
            }
        }

    </style>

</head>

<body>

<div class="reset-card">

    <div class="reset-icon">
        <i class="bi bi-key-fill"></i>
    </div>

    <h2 class="text-center mb-2">
        Reset Password
    </h2>

    <p class="text-center mb-1">
        Create a new password for
    </p>

    <p class="text-center email-text mb-4">
        <?php echo htmlspecialchars($email); ?>
    </p>

    <?php if ($message !== ""): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($messageType); ?>"
            role="alert"
        >
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="">

        <!-- New Password -->

        <div class="mb-3">

            <label
                for="password"
                class="form-label"
            >
                New Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Enter new password"
                    minlength="6"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword('password', 'passwordIcon')"
                    aria-label="Show password"
                >
                    <i
                        class="bi bi-eye"
                        id="passwordIcon"
                    ></i>
                </button>

            </div>

            <div class="password-info mt-1">
                Password must be at least 6 characters.
            </div>

        </div>

        <!-- Confirm Password -->

        <div class="mb-4">

            <label
                for="confirm_password"
                class="form-label"
            >
                Confirm Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm new password"
                    minlength="6"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword(
                        'confirm_password',
                        'confirmPasswordIcon'
                    )"
                    aria-label="Show password"
                >
                    <i
                        class="bi bi-eye"
                        id="confirmPasswordIcon"
                    ></i>
                </button>

            </div>

        </div>

        <button
            type="submit"
            class="btn btn-primary btn-reset w-100"
        >
            <i class="bi bi-check-circle me-2"></i>
            Reset Password
        </button>

    </form>

</div>

<script>

function togglePassword(inputId, iconId) {

    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}

</script>

</body>

</html>