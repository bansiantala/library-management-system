<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

$message = "";
$messageType = "";

// Check whether an email is available in session
if (!isset($_SESSION["reset_email"]) || empty($_SESSION["reset_email"])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION["reset_email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"] ?? "");

    // Validate OTP
    if ($otp === "") {

        $message = "Please enter the OTP.";
        $messageType = "danger";

    } elseif (!preg_match("/^[0-9]{6}$/", $otp)) {

        $message = "OTP must contain exactly 6 digits.";
        $messageType = "danger";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get latest OTP
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id, otp_hash, expires_at, verified
            FROM password_otps
            WHERE email = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        if (!$stmt) {

            $message = "Something went wrong. Please try again.";
            $messageType = "danger";

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {

                $message =
                    "No OTP found. Please request a new OTP.";

                $messageType = "danger";

            } else {

                $otpData = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | Check whether OTP is already verified
                |--------------------------------------------------------------------------
                */

                if ((int)$otpData["verified"] === 1) {

                    $message =
                        "This OTP has already been verified.";

                    $messageType = "danger";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Check OTP expiry
                    |--------------------------------------------------------------------------
                    */

                    $currentTime = new DateTime();
                    $expiryTime = new DateTime($otpData["expires_at"]);

                    if ($currentTime > $expiryTime) {

                        $message =
                            "OTP has expired. Please request a new OTP.";

                        $messageType = "danger";

                    } elseif (!password_verify(
                        $otp,
                        $otpData["otp_hash"]
                    )) {

                        $message =
                            "Invalid OTP. Please check the OTP and try again.";

                        $messageType = "danger";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | OTP is correct
                        |--------------------------------------------------------------------------
                        */

                        $updateStmt = $conn->prepare("
                            UPDATE password_otps
                            SET verified = 1
                            WHERE id = ?
                        ");

                        if ($updateStmt) {

                            $otpId = (int)$otpData["id"];

                            $updateStmt->bind_param(
                                "i",
                                $otpId
                            );

                            if ($updateStmt->execute()) {

                                /*
                                |--------------------------------------------------------------------------
                                | Save verification status in session
                                |--------------------------------------------------------------------------
                                */

                                $_SESSION["otp_verified"] = true;

                                header(
                                    "Location: reset_password.php"
                                );
                                exit();

                            } else {

                                $message =
                                    "Unable to verify OTP. Please try again.";

                                $messageType = "danger";
                            }

                            $updateStmt->close();

                        } else {

                            $message =
                                "Unable to verify OTP. Please try again.";

                            $messageType = "danger";
                        }
                    }
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
        Verify OTP - Library Management System
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

        .otp-card {
            width: 100%;
            max-width: 450px;

            background: #ffffff;

            border-radius: 18px;

            padding: 35px;

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.10);
        }

        .otp-icon {
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

        .otp-card h2 {
            font-weight: 700;
            color: #212529;
        }

        .otp-card p {
            color: #6c757d;
        }

        .email-text {
            color: #212529 !important;
            font-weight: 600;
            word-break: break-word;
        }

        .otp-input {
            height: 55px;

            text-align: center;

            font-size: 24px;

            font-weight: 700;

            letter-spacing: 8px;

            border-radius: 10px;
        }

        .otp-input::placeholder {
            letter-spacing: 3px;
            font-size: 16px;
            font-weight: 400;
        }

        .btn-verify {
            height: 48px;

            border-radius: 10px;

            font-weight: 600;
        }

        .back-link {
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 500px) {

            .otp-card {
                margin: 20px;
                padding: 25px;
            }

            .otp-input {
                letter-spacing: 5px;
            }
        }

    </style>

</head>

<body>

<div class="otp-card">

    <div class="otp-icon">
        <i class="bi bi-shield-check"></i>
    </div>

    <h2 class="text-center mb-2">
        Verify OTP
    </h2>

    <p class="text-center mb-1">
        We sent a 6-digit verification code to
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

        <div class="mb-3">

            <label
                for="otp"
                class="form-label fw-semibold"
            >
                Enter OTP
            </label>

            <input
                type="text"
                class="form-control otp-input"
                id="otp"
                name="otp"
                placeholder="000000"
                maxlength="6"
                minlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
            >

        </div>

        <button
            type="submit"
            class="btn btn-primary btn-verify w-100"
        >
            <i class="bi bi-check-circle me-2"></i>
            Verify OTP
        </button>

    </form>

    <div class="text-center mt-4">

        <a
            href="forgot_password.php"
            class="back-link"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Request New OTP
        </a>

    </div>

</div>

<script>

    const otpInput = document.getElementById("otp");

    otpInput.addEventListener("input", function () {

        this.value = this.value
            .replace(/[^0-9]/g, "")
            .slice(0, 6);

    });

</script>

</body>

</html>