<?php
include 'header.php';
require_once 'config.php';
require_once 'functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        redirect("forgot-password.php?error=Invalid email address");
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        redirect("forgot-password.php?success=If this email exists, you'll receive an OTP");
    }

    // Generate OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now

    // Store OTP
    $pdo->prepare("DELETE FROM otp_verification WHERE email = ?")->execute([$email]);
    $pdo->prepare("INSERT INTO otp_verification (email, otp_code, expires_at, is_used) VALUES (?, ?, ?, FALSE)")
        ->execute([$email, $otp, $expires]);

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kushwahav912@gmail.com';
        $mail->Password = 'recskdinbkpbpsoj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('kushwahav912@gmail.com', 'Unsnap');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP';
        $mail->Body = "
            <h2>Password Reset OTP</h2>
            <p>Your one-time password (OTP) for password reset is:</p>
            <h3 style='font-size: 24px; letter-spacing: 3px;'>$otp</h3>
            <p>This OTP is valid for 5 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        $mail->AltBody = "Your OTP is: $otp";

        $mail->send();
        redirect("verify-otp.php?email=" . urlencode($email) . "&success=OTP sent to your email");
    } catch (Exception $e) {
        redirect("forgot-password.php?error=Failed to send OTP. Please try again.");
    }
} else {
    redirect("forgot-password.php");
}