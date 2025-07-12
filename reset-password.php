<?php
require_once 'config.php';
require_once 'functions.php';
include 'header.php';

$token = $_GET['token'] ?? null;
if (!$token) {
    redirect("forgot-password.php?error=Invalid token");
}

// Verify token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    redirect("forgot-password.php?error=Invalid or expired token");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        redirect("reset-password.php?token=$token&error=Passwords do not match");
    }
    
    if (strlen($password) < 8) {
        redirect("reset-password.php?token=$token&error=Password must be at least 8 characters");
    }
    
    // Update password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $reset['email']]);
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset['email']]);
    
    redirect("login.php?success=Password updated successfully");
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="fab.png" type="image/x-icon">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-4 text-center">Reset Password</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <label class="block mb-2 text-sm">New Password (min 8 characters):</label>
            <input type="password" name="password" required minlength="8"
                   class="w-full p-2 border border-gray-300 rounded mb-3">
            
            <label class="block mb-2 text-sm">Confirm Password:</label>
            <input type="password" name="confirm_password" required minlength="8"
                   class="w-full p-2 border border-gray-300 rounded mb-4">
            
            <button type="submit" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">
                Reset Password
            </button>
        </form>
    </div>
</body>
</html>