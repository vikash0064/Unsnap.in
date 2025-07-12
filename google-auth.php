<?php
require_once 'config.php';
require_once 'functions.php';
require_once __DIR__ . '/vendor/autoload.php';

// ✅ Always initialize PDO before use
$pdo = getPDO();

if (!isset($_GET['code'])) {
    die('Authorization code not received');
}

// Set up Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

try {
    // Exchange code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception($token['error_description']);
    }

    $client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    $googleId = $userInfo->getId();
    $name = $userInfo->getName();
    $email = $userInfo->getEmail();
    $picture = $userInfo->getPicture();

    // If no image from Google, create placeholder using first letter of name
    if (empty($picture)) {
        $firstLetter = strtoupper(substr($name, 0, 1));
        $picture = "https://ui-avatars.com/api/?name={$firstLetter}&background=random&color=fff";
    }

    // Only allow real emails (no fake/temporary domains)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/@(tempmail|10minutemail|mailinator|guerrillamail|yopmail|fake|discard|sharklasers|maildrop|trashmail|mailnesia|spamgourmet|getnada|mintemail|mailnull|spambog|spambox|spam4|spamex|spamfree24|spamgourmet|spamherelots|spaminator|spamspot|trashmail|yopmail|zippymail)\./i', $email)) {
        die('Fake or temporary email addresses are not allowed.');
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, profile_image, google_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $picture, $googleId]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];

        // Update missing fields if needed
        if (empty($user['google_id']) || empty($user['profile_image'])) {
            $stmt = $pdo->prepare("UPDATE users SET google_id = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$googleId, $picture, $userId]);
        }
    }

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['google_user'] = [
        'id' => $googleId,
        'name' => $name,
        'email' => $email,
        'picture' => $picture
    ];

    // Redirect to previous page or index
    $redirect = $_SESSION['redirect_url'] ?? 'index.php';
    unset($_SESSION['redirect_url']);
    header("Location: $redirect");
    exit;

} catch (Exception $e) {
    die('Google Login Failed: ' . $e->getMessage());
}
