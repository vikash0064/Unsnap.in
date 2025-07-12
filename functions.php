<?php
// functions.php

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['google_user']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}

function getGoogleAuthUrl() {
    if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_REDIRECT_URI')) {
        throw new Exception('Google OAuth credentials not configured');
    }
    
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
}

function validatePassword($password) {
    // At least 8 characters, one uppercase, one lowercase, one number, one special char
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

function getCachedImages($keyword, $accessKey, $perPage = 12, $page = 1) {
    $cacheDir = "cache/";
    $cacheFile = $cacheDir . md5($keyword . $perPage . $page) . ".json";
    $cacheTime = 3600; // 1 hour cache
    
    // Create cache directory if it doesn't exist
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Use cached data if available and fresh
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if (isset($cacheData['images'])) {
            return $cacheData['images'];
        }
    }
    
    // If no cache or expired, fetch from API
    $url = "https://api.unsplash.com/search/photos?" . http_build_query([
        'query' => $keyword,
        'client_id' => $accessKey,
        'per_page' => $perPage,
        'page' => $page
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true
    ]);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("Unsplash API error: " . curl_error($ch));
        curl_close($ch);
        return [];
    }
    
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        $images = $data['results'] ?? [];
        
        // Save to cache
        file_put_contents($cacheFile, json_encode([
            'timestamp' => time(),
            'images' => $images
        ]));
        
        return $images;
    }
    
    return [];
}

function handleLogin($email, $password, $conn) {
    $email = sanitizeInput($email);
    
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            return true;
        }
    }
    return false;
}

function validateToken($token, $hashedToken) {
    return password_verify($token, $hashedToken);
}

function checkRememberMe($pdo) {
    if (!empty($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token IS NOT NULL");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            if (validateToken($token, $user['remember_token'])) {
                // Valid token found - log user in
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['profile_image'] = $user['profile_image'] ?? null;
                $_SESSION['login_method'] = 'cookie';
                $_SESSION['last_login'] = time();
                
                return true;
            }
        }
    }
    return false;
}
?>