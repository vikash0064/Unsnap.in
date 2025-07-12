<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="fab.png" type="image/x-icon">
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
  <div class="bg-white p-8 rounded-lg shadow-lg w-96">
    <h2 class="text-2xl font-bold mb-4 text-center">Forgot Password</h2>

    <?php if (isset($_GET['error'])): ?>
      <div class="text-red-600 text-sm mb-3"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
      <div class="text-green-600 text-sm mb-3"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form action="send-otp.php" method="POST">
      <label class="block mb-2 text-sm">Enter your email:</label>
      <input type="email" name="email" required 
             class="w-full p-2 border border-gray-300 rounded mb-4" 
             placeholder="your@email.com">
      <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
        Send OTP
      </button>
    </form>

    <p class="mt-4 text-center">
      <a href="login.php" class="text-blue-600 text-sm hover:underline">Back to Login</a>
    </p>
  </div>
</body>
</html>