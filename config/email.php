<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Gmail SMTP Configuration
|--------------------------------------------------------------------------
| Replace ONLY the values marked below.
|--------------------------------------------------------------------------
*/

function sendOTPEmail($recipientEmail, $otp)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bansiantala14@gmail.com';
        $mail->Password   = 'YOUR_16_CHARACTER_APP_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender
        $mail->setFrom(
            'bansiantala14@gmail.com',
            'Library Management System'
        );

        // Receiver
        $mail->addAddress($recipientEmail);

        // Email format
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - Library Management System';

        $mail->Body = '
            <div style="
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 25px;
                border: 1px solid #ddd;
                border-radius: 10px;
            ">
                <h2 style="text-align:center;">
                    Library Management System
                </h2>

                <p>Hello,</p>

                <p>
                    We received a request to reset your password.
                    Use the OTP below to continue.
                </p>

                <div style="
                    text-align:center;
                    margin:25px 0;
                ">
                    <span style="
                        display:inline-block;
                        padding:15px 30px;
                        background:#0d6efd;
                        color:white;
                        font-size:28px;
                        font-weight:bold;
                        letter-spacing:6px;
                        border-radius:8px;
                    ">
                        ' . htmlspecialchars($otp) . '
                    </span>
                </div>

                <p>
                    This OTP is valid for <strong>5 minutes</strong>.
                </p>

                <p>
                    If you did not request a password reset,
                    you can safely ignore this email.
                </p>

                <hr>

                <p style="font-size:12px;color:#777;">
                    Library Management System
                </p>
            </div>
        ';

        $mail->AltBody =
            "Your Library Management System password reset OTP is: "
            . $otp
            . ". This OTP is valid for 5 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}