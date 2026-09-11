<?php

include "config/database.php";

$message = "";
$messageType = "";

if (isset($_POST['reset_password'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $message = "Please fill all fields.";
        $messageType = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "danger";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "danger";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $messageType = "danger";

    } else {

        // Check email
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $message = "No account found with this email.";
            $messageType = "danger";

        } else {

            $user = $result->fetch_assoc();

            // Hash new password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Update password
            $update = $conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            $update->bind_param(
                "si",
                $hashedPassword,
                $user['id']
            );

            if ($update->execute()) {

                $message =
                    "Password reset successfully. You can now login.";

                $messageType = "success";

                $email = "";

            } else {

                $message =
                    "Password reset failed. Please try again.";

                $messageType = "danger";
            }

            $update->close();
        }

        $stmt->close();
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


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef4ff 0%,
                    #f7f5ff 50%,
                    #eef8ff 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

        }


        .forgot-wrapper {

            width: 100%;

            max-width: 440px;

        }


        .forgot-card {

            background: #ffffff;

            border-radius: 18px;

            padding: 30px;

            border: 1px solid #e9edf5;

            box-shadow:
                0 15px 45px
                rgba(31, 41, 55, 0.10);

        }


        /* Icon */

        .forgot-logo {

            width: 64px;

            height: 64px;

            margin: 0 auto 15px;

            border-radius: 16px;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #4f46c5
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 29px;

            box-shadow:
                0 8px 20px
                rgba(13, 110, 253, 0.22);

        }


        /* Title */

        .forgot-title {

            text-align: center;

            font-size: 25px;

            font-weight: 700;

            color: #1f2937;

            margin-bottom: 5px;

        }


        .forgot-subtitle {

            text-align: center;

            color: #6b7280;

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 24px;

        }


        /* Alert */

        .forgot-alert {

            display: flex;

            align-items: center;

            gap: 8px;

            border-radius: 9px;

            padding: 10px 12px;

            font-size: 13px;

            margin-bottom: 18px;

        }


        .forgot-alert.success {

            background: #ecfdf3;

            color: #087443;

            border: 1px solid #b7ebce;

        }


        .forgot-alert.danger {

            background: #fff1f2;

            color: #b42318;

            border: 1px solid #fecdd3;

        }


        /* Form */

        .form-group {

            margin-bottom: 17px;

        }


        .form-label {

            display: block;

            font-size: 13px;

            font-weight: 600;

            color: #374151;

            margin-bottom: 7px;

        }


        .input-wrapper {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #8a94a6;

            font-size: 16px;

            pointer-events: none;

            z-index: 2;

        }


        .forgot-input {

            width: 100%;

            height: 44px;

            border: 1px solid #dce1e9;

            border-radius: 9px;

            outline: none;

            padding:
                8px 13px 8px 40px;

            font-size: 13px;

            color: #1f2937;

            background: #ffffff;

            transition: all 0.2s ease;

        }


        .forgot-input.password-input {

            padding-right: 45px;

        }


        .forgot-input:focus {

            border-color: #0d6efd;

            box-shadow:
                0 0 0 3px
                rgba(13, 110, 253, 0.10);

        }


        .forgot-input::placeholder {

            color: #a0a7b4;

        }


        /* Password toggle */

        .password-toggle {

            position: absolute;

            right: 8px;

            top: 50%;

            transform: translateY(-50%);

            width: 34px;

            height: 34px;

            border: none;

            background: transparent;

            color: #7b8494;

            border-radius: 7px;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            font-size: 16px;

        }


        .password-toggle:hover {

            background: #f1f4f8;

            color: #0d6efd;

        }


        /* Button */

        .reset-button {

            width: 100%;

            height: 44px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #4f46c5
                );

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            cursor: pointer;

            transition: all 0.25s ease;

        }


        .reset-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 7px 18px
                rgba(13, 110, 253, 0.25);

        }


        /* Back Login */

        .back-login {

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 6px;

            margin-top: 18px;

            color: #0d6efd;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

        }


        .back-login:hover {

            text-decoration: underline;

        }


        .forgot-footer {

            text-align: center;

            font-size: 11px;

            color: #9ca3af;

            margin-top: 18px;

        }


        /* Mobile */

        @media (max-width: 576px) {

            body {

                padding: 15px;

            }

            .forgot-card {

                padding: 25px 20px;

                border-radius: 15px;

            }

            .forgot-logo {

                width: 58px;

                height: 58px;

                font-size: 26px;

            }

            .forgot-title {

                font-size: 23px;

            }

        }


        @media (max-width: 380px) {

            .forgot-card {

                padding: 22px 16px;

            }

            .forgot-title {

                font-size: 21px;

            }

        }

    </style>

</head>


<body>


<div class="forgot-wrapper">


    <div class="forgot-card">


        <!-- Icon -->

        <div class="forgot-logo">

            <i class="bi bi-shield-lock"></i>

        </div>


        <!-- Heading -->

        <h2 class="forgot-title">

            Reset Password

        </h2>


        <p class="forgot-subtitle">

            Enter your registered email and create a new password.

        </p>


        <!-- Message -->

        <?php if (!empty($message)) { ?>

            <div
                class="forgot-alert
                <?php echo $messageType; ?>"
            >

                <?php if ($messageType === "success") { ?>

                    <i class="bi bi-check-circle-fill"></i>

                <?php } else { ?>

                    <i class="bi bi-exclamation-circle-fill"></i>

                <?php } ?>


                <span>

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </span>

            </div>

        <?php } ?>


        <!-- Form -->

        <form
            method="POST"
            action="forgot_password.php"
            onsubmit="return validateResetForm();"
        >


            <!-- Email -->

            <div class="form-group">

                <label class="form-label">

                    Email Address

                </label>


                <div class="input-wrapper">

                    <i class="bi bi-envelope input-icon"></i>

                    <input
                        type="email"
                        name="email"
                        class="forgot-input"
                        placeholder="Enter your registered email"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >

                </div>

            </div>


            <!-- New Password -->

            <div class="form-group">

                <label class="form-label">

                    New Password

                </label>


                <div class="input-wrapper">

                    <i class="bi bi-lock input-icon"></i>


                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="forgot-input password-input"
                        placeholder="Minimum 6 characters"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('password', 'passwordIcon')"
                        title="Show password"
                    >

                        <i
                            class="bi bi-eye"
                            id="passwordIcon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label class="form-label">

                    Confirm New Password

                </label>


                <div class="input-wrapper">

                    <i class="bi bi-lock-fill input-icon"></i>


                    <input
                        type="password"
                        name="confirm_password"
                        id="confirm_password"
                        class="forgot-input password-input"
                        placeholder="Confirm your new password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('confirm_password', 'confirmIcon')"
                        title="Show password"
                    >

                        <i
                            class="bi bi-eye"
                            id="confirmIcon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- Reset Button -->

            <button
                type="submit"
                name="reset_password"
                class="reset-button"
            >

                <i class="bi bi-arrow-repeat"></i>

                Reset Password

            </button>


        </form>


        <!-- Back Login -->

        <a
            href="login.php"
            class="back-login"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Login

        </a>


    </div>


    <div class="forgot-footer">

        Library Management System

    </div>


</div>


<script>


/* =========================
   SHOW / HIDE PASSWORD
========================= */

function togglePassword(
    inputId,
    iconId
) {

    const input =
        document.getElementById(inputId);

    const icon =
        document.getElementById(iconId);


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove(
            "bi-eye"
        );

        icon.classList.add(
            "bi-eye-slash"
        );

    } else {

        input.type = "password";

        icon.classList.remove(
            "bi-eye-slash"
        );

        icon.classList.add(
            "bi-eye"
        );
    }

}


/* =========================
   VALIDATION
========================= */

function validateResetForm() {

    const password =
        document.getElementById(
            "password"
        ).value;

    const confirmPassword =
        document.getElementById(
            "confirm_password"
        ).value;


    if (password.length < 6) {

        alert(
            "Password must be at least 6 characters."
        );

        return false;
    }


    if (password !== confirmPassword) {

        alert(
            "Passwords do not match."
        );

        return false;
    }


    return true;

}

</script>


</body>

</html>