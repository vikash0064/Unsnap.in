<?php
// ----------------------
// DATABASE CONFIGURATION (LOCALHOST)
// ----------------------
define('DB_HOST', 'localhost');              // ✅ Localhost
define('DB_USER', 'root');                   // ✅ Default XAMPP/WAMP username
define('DB_PASS', '');                       // ✅ Usually blank for XAMPP/WAMP
define('DB_NAME', 'image_explorer');                 // ✅ Your local database name (must match your phpMyAdmin DB)

// ----------------------
// GOOGLE OAUTH CONFIGURATION (LOCAL)
// ----------------------
define('GOOGLE_CLIENT_ID', 'YOUR_LOCAL_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_LOCAL_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/unsnap/google-auth.php'); // ✅ Local URL

// ----------------------
// SITE CONFIGURATION
// ----------------------
define('SITE_NAME', 'Unsnap');
define('SITE_URL', 'http://localhost/unsnap'); // ✅ Localhost site path
define('SITE_EMAIL', 'example@gmail.com');     // Dummy or your real email (optional)

// ----------------------
// OTP AND RESET SETTINGS
// ----------------------
define('OTP_EXPIRY_MINUTES', 5);
define('PASSWORD_RESET_EXPIRY_HOURS', 1);

// ----------------------
// EMAIL SMTP CONFIGURATION (Optional: Only if sending emails locally)
// ----------------------
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // 🔐 App-specific password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Unsnap');

// ----------------------
// TIMEZONE & ERROR REPORTING
// ----------------------
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------------
// SESSION
// ----------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------
// DATABASE CONNECTION FUNCTIONS
// ----------------------
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("❌ PDO connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

function getMysqli() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("❌ MySQLi connection failed: " . $conn->connect_error);
        }
    }
    return $conn;
}

// ----------------------
// AUTOLOAD COMPOSER & FUNCTIONS
// ----------------------
require_once __DIR__ . '/vendor/autoload.php'; // Google API client (OAuth)
require_once 'functions.php';                  // Your custom functions file

// ----------------------
// AUTO LOGIN VIA REMEMBER ME
// ----------------------
if (!isLoggedIn()) {
    $pdo = getPDO(); // Ensure $pdo is initialized
    checkRememberMe($pdo);
}
