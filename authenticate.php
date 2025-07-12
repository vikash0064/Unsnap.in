<?php
require_once 'config.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['email']) || empty($_POST['password'])) {
        redirect('login.php?error=Email and password are required');
    }

    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;

    // ✅ FIX: Initialize PDO before using it
    $pdo = getPDO();
    try {
        // Only allow real emails (no fake/temporary domains)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/@(tempmail|10minutemail|mailinator|guerrillamail|yopmail|fake|discard|sharklasers|maildrop|trashmail|mailnesia|spamgourmet|getnada|mintemail|mailnull|spambog|spambox|spam4|spamex|spamfree24|spamgourmet|spamherelots|spaminator|spamspot|trashmail|yopmail|zippymail)\./i', $email)) {
            redirect('login.php?error=Fake or temporary email addresses are not allowed');
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            redirect('login.php?error=Invalid email or password&email='.urlencode($email));
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            redirect('login.php?error=Invalid email or password&email='.urlencode($email));
        }

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? null;
        $_SESSION['login_method'] = 'email';
        $_SESSION['last_login'] = time();

        // Set remember me cookie if requested
        if ($remember) {
            $token = generateToken();
            $expiry = time() + 60 * 60 * 24 * 30; // 30 days
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
            // Store hashed token in database
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$hashedToken, $user['id']]);
        }

        // Redirect to intended page or default
        $redirect = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']);
        redirect($redirect);
        
    } catch (PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        redirect('login.php?error=System error. Please try again later.');
    }
} else {
    redirect('login.php');
}