<?php
require_once 'config.php';
require_once 'functions.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);

    // Debug: Log the received values
    error_log("Email: $email, OTP: $otp");

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect("verify-otp.php?email=".urlencode($email)."&error=Invalid email");
    }

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        redirect("verify-otp.php?email=".urlencode($email)."&error=OTP must be 6 digits");
    }

    // Get current timestamp in same format as database
    $current_time = date('Y-m-d H:i:s');
    
    // Debug: Log current time
    error_log("Current time: $current_time");

    // Fetch the most recent valid OTP
    $stmt = $pdo->prepare("SELECT * FROM otp_verification 
                          WHERE email = ? 
                          AND is_used = FALSE
                          ORDER BY created_at DESC 
                          LIMIT 1");
    $stmt->execute([$email]);
    $otpRecord = $stmt->fetch();

    if (!$otpRecord) {
        error_log("No OTP record found for email: $email");
        redirect("verify-otp.php?email=".urlencode($email)."&error=No OTP found");
    }

    // Debug: Log database values
    error_log("DB OTP: ".$otpRecord['otp_code']);
    error_log("DB Expiry: ".$otpRecord['expires_at']);
    error_log("Is Used: ".$otpRecord['is_used']);

    // Check if OTP matches and is not expired
    if ($otpRecord['otp_code'] !== $otp) {
        error_log("OTP mismatch: DB has '{$otpRecord['otp_code']}', user entered '$otp'");
        redirect("verify-otp.php?email=".urlencode($email)."&error=Invalid OTP");
    }

    if (strtotime($current_time) > strtotime($otpRecord['expires_at'])) {
        error_log("OTP expired: Current $current_time > Expiry {$otpRecord['expires_at']}");
        redirect("verify-otp.php?email=".urlencode($email)."&error=OTP expired");
    }

    // Mark OTP as used
    $pdo->prepare("UPDATE otp_verification SET is_used = TRUE WHERE id = ?")
        ->execute([$otpRecord['id']]);

    // Generate password reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
    
    // Store reset token
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
        ->execute([$email, $token, $expires]);

    redirect("reset-password.php?token=".urlencode($token));
} else {
    redirect("forgot-password.php");
}