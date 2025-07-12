<?php 
require_once 'config.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (isLoggedIn()) {
    redirect('profile.php');
}

// Initialize Google Client
$showGoogleLogin = false;
$googleAuthUrl = '';
if (defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET') && 
    !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET)) {
    $googleLibPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($googleLibPath)) {
        require_once $googleLibPath;
        try {
            $client = new Google_Client();
            $client->setClientId(GOOGLE_CLIENT_ID);
            $client->setClientSecret(GOOGLE_CLIENT_SECRET);
            $client->setRedirectUri(GOOGLE_REDIRECT_URI);
            $client->addScope("email");
            $client->addScope("profile");
            $googleAuthUrl = $client->createAuthUrl();
            $showGoogleLogin = true;
        } catch (Exception $e) {
            error_log("Google Client Error: " . $e->getMessage());
        }
    } else {
        error_log("Google API Client library not found at: $googleLibPath");
    }
}

// Rest of your HTML and form code remains the same...
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="fab.png" type="image/x-icon">
  <title>Login | <?php echo htmlspecialchars(SITE_NAME); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #7c3aed;
      --secondary: #10b981;
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.18);
    }
    .gradient-bg {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      transition: all 0.3s;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px -5px rgba(124, 58, 237, 0.4);
    }
    .btn-google {
      background: white;
      border: 1px solid #e2e8f0;
      transition: all 0.3s;
    }
    .btn-google:hover {
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .input-field:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
    }
    .divider::before, .divider::after {
      content: "";
      flex: 1;
      border-bottom: 1px solid #e2e8f0;
    }
    .divider-text {
      padding: 0 1rem;
      color: #64748b;
      font-size: 0.875rem;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-emerald-50 min-h-screen">
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="glass-card p-8 shadow-xl">
        <div class="text-center mb-8">
          <div class="w-16 h-16 gradient-bg rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
            <i class="fas fa-camera text-white text-2xl"></i>
          </div>
          <h1 class="text-3xl font-bold text-gray-800 mb-1">Welcome Back</h1>
          <p class="text-gray-600">Sign in to your account</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
          <i class="fas fa-check-circle mr-2"></i>
          <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <form action="authenticate.php" method="POST" class="space-y-5">
          <div>
            <label for="email" class="block text-gray-700 mb-2 font-medium">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com"
              class="w-full px-4 py-3 input-field rounded-lg border focus:outline-none"
              value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
          </div>
          <div>
            <label for="password" class="block text-gray-700 mb-2 font-medium">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" required placeholder="••••••••"
                class="w-full px-4 py-3 input-field rounded-lg border focus:outline-none pr-10">
              <button type="button" onclick="togglePasswordVisibility()" 
                      class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                <i class="far fa-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2 text-sm text-gray-600">
              <input type="checkbox" name="remember" class="h-4 w-4 text-purple-600 rounded border-gray-300">
              <span>Remember me</span>
            </label>
            <a href="forgot-password.php" class="text-sm text-purple-600 hover:underline">Forgot password?</a>
          </div>
          <button type="submit"
            class="w-full btn-primary text-white py-3 px-4 rounded-lg font-semibold">Sign In</button>
        </form>

        <?php if ($showGoogleLogin): ?>
        <div class="divider"><span class="divider-text">OR CONTINUE WITH</span></div>
        <div class="mb-4">
          <a href="<?php echo $googleAuthUrl; ?>" 
             class="flex items-center justify-center w-full btn-google py-3 px-4 rounded-lg font-medium">
            <img src="google.png"
              class="w-5 h-5 mr-3" alt="Google Logo">
            <span>Continue with Google</span>
          </a>
        </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
          <p class="text-sm text-gray-500 mb-2">Want to explore without logging in?</p>
          <a href="index.php" class="inline-block px-4 py-2 border bg-green-200 border-gray-100 rounded-lg text-black hover:bg-green-600 transition">
            Continue without login
          </a>
        </div>

        <div class="text-center pt-6 border-t border-gray-100 mt-6">
          <p class="text-gray-600">Don't have an account?
            <a href="register.php" class="text-purple-600 font-medium hover:underline">Sign up now</a>
          </p>
        </div>
      </div>

      <div class="text-center mt-6 text-sm text-gray-500">
        <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
        <div class="flex justify-center space-x-4 mt-2">
          <a href="terms.html" class="hover:underline">Terms</a>
          <a href="privacy.html" class="hover:underline">Privacy</a>
          <a href="contact.php" class="hover:underline">Contact</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('toggleIcon');
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      toggleIcon.classList.toggle('fa-eye');
      toggleIcon.classList.toggle('fa-eye-slash');
    }
  </script>
</body>
</html>
