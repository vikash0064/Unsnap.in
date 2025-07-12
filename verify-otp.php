<?php
require_once 'config.php';
require_once 'functions.php';
include 'header.php';

$email = $_GET['email'] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect("forgot-password.php?error=Invalid email address");
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="fab.png" type="image/x-icon">
    <title>Verify OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-4 text-center">Verify OTP</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="text-green-600 text-sm mb-3"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <form action="verify-otp-process.php" method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            
            <label class="block mb-2 text-sm">Enter OTP (6 digits):</label>
            <input type="text" name="otp" placeholder="123456" required pattern="\d{6}" maxlength="6"
                   class="w-full p-2 border border-gray-300 rounded mb-4">
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                Verify OTP
            </button>
        </form>
        
        <p class="mt-4 text-center text-sm">
            Didn't receive OTP? <a href="send-otp.php?email=<?= urlencode($email) ?>" 
            class="text-blue-600 hover:underline">Resend OTP</a>
        </p>
    </div>
</body>
</html>