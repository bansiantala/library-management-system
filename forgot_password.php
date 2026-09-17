<?php

require_once "config/database.php";
require_once "config/email.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    // Validate email
    if ($email === "") {

        $message = "Please enter your email address.";
        $messageType = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "danger";

    } else {

        // Check whether email exists
        $stmt = $conn->prepare(
            "SELECT id, name FROM users WHERE email = ? LIMIT 1"
        );

        if (!$stmt) {

            $message = "Something went wrong. Please try again.";
            $messageType = "danger";

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {

                $message = "No account found with this email address.";
                $messageType = "danger";

            } else {

                $user = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | Generate 6-digit OTP
                |--------------------------------------------------------------------------
                */

                $otp = (string) random_int(100000, 999999);

                /*
                |--------------------------------------------------------------------------
                | Hash OTP before storing it
                |--------------------------------------------------------------------------
                */

                $otpHash = password_hash($otp, PASSWORD_DEFAULT);

                /*
                |--------------------------------------------------------------------------
                | OTP expires after 5 minutes
                |--------------------------------------------------------------------------
                */

                $expiresAt = date(
                    "Y-m-d H:i:s",
                    time() + (5 * 60)
                );

                /*
                |--------------------------------------------------------------------------
                | Remove previous OTPs for this email
                |--------------------------------------------------------------------------
                */

                $deleteStmt = $conn->prepare(
                    "DELETE FROM password_otps WHERE email = ?"
                );

                if ($deleteStmt) {
                    $deleteStmt->bind_param("s", $email);
                    $deleteStmt->execute();
                    $deleteStmt->close();
                }

                /*
                |--------------------------------------------------------------------------
                | Store new OTP hash
                |--------------------------------------------------------------------------
                */

                $insertStmt = $conn->prepare(
                    "INSERT INTO password_otps
                    (email, otp_hash, expires_at, verified)
                    VALUES (?, ?, ?, 0)"
                );

                if (!$insertStmt) {

                    $message = "Unable to create OTP. Please try again.";
                    $messageType = "danger";

                } else {

                    $insertStmt->bind_param(
                        "sss",
                        $email,
                        $otpHash,
                        $expiresAt
                    );

                    if ($insertStmt->execute()) {

                        /*
                        |--------------------------------------------------------------------------
                        | Send OTP email
                        |--------------------------------------------------------------------------
                        */

                        $emailSent = sendOTPEmail($email, $otp);

                        if ($emailSent) {

                            /*
                            | Save email in session so the next page
                            | knows which account is being verified.
                            */

                            if (session_status() === PHP_SESSION_NONE) {
                                session_start();
                            }

                            $_SESSION["reset_email"] = $email;

                            header(
                                "Location: verify_otp.php"
                            );
                            exit();

                        } else {

                            // If email could not be sent, remove OTP
                            $cleanupStmt = $conn->prepare(
                                "DELETE FROM password_otps WHERE email = ?"
                            );

                            if ($cleanupStmt) {
                                $cleanupStmt->bind_param("s", $email);
                                $cleanupStmt->execute();
                                $cleanupStmt->close();
                            }

                            $message =
                                "Unable to send OTP email. Please check your Gmail SMTP settings.";

                            $messageType = "danger";
                        }

                    } else {

                        $message =
                            "Unable to create OTP. Please try again.";

                        $messageType = "danger";
                    }

                    $insertStmt->close();
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

    <title>Forgot Password - Library Management System</title>

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

        .forgot-card {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.10);
        }

        .forgot-icon {
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

        .forgot-card h2 {
            font-weight: 700;
            color: #212529;
        }

        .forgot-card p {
            color: #6c757d;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control {
            height: 48px;
            border-radius: 10px;
        }

        .btn-send {
            height: 48px;
            border-radius: 10px;
            font-weight: 600;
        }

        .back-login {
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 500px) {

            .forgot-card {
                margin: 20px;
                padding: 25px;
            }
        }

    </style>

</head>

<body>

<div class="forgot-card">

    <div class="forgot-icon">
        <i class="bi bi-shield-lock-fill"></i>
    </div>

    <h2 class="text-center mb-2">
        Forgot Password?
    </h2>

    <p class="text-center mb-4">
        Enter your registered email address and we'll send you a verification OTP.
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

        <div class="mb-3">

            <label
                for="email"
                class="form-label"
            >
                Email Address
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                </span>

                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Enter your registered email"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                    required
                >

            </div>

        </div>

        <button
            type="submit"
            name="send_otp"
            class="btn btn-primary btn-send w-100"
        >
            <i class="bi bi-send me-2"></i>
            Send OTP
        </button>

    </form>

    <div class="text-center mt-4">

        <a
            href="user_login.php"
            class="back-login"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Back to Login
        </a>

    </div>

</div>

</body>

</html>