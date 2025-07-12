<?php
require_once 'config.php';
// No DB connection needed for logout

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
if (session_id()) {
    session_destroy();
}

// Delete PHP session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Delete remember_token (email login)
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Delete custom Google auth session cookie if used
if (isset($_COOKIE['gauth'])) {
    setcookie('gauth', '', time() - 3600, '/', '', true, true);
}

// OPTIONAL: If you stored Google auth in $_SESSION['google_user'], clear it
unset($_SESSION['google_user']);

// Redirect to home page after logout
header("Location: index.php");
exit;
