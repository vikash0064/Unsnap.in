<?php
require_once 'config.php';
require_once 'functions.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

$successMsg = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $dt = date('Y-m-d H:i:s');

    // Store in DB
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        $errorMsg = 'Database connection failed.';
    } else {
        $stmt = $mysqli->prepare('INSERT INTO contact_messages (name, email, message, created_at) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $message, $dt);
        if ($stmt->execute()) {
            $successMsg = 'Your message has been sent!';
        } else {
            $errorMsg = 'Failed to save your message.';
        }
        $stmt->close();
        $mysqli->close();
    }

    // Send Email
    if (empty($errorMsg)) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            // Set sender (same as OTP mail)
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            // Send to admin
            $mail->addAddress('vikashkushwaha726@gmail.com', 'Admin');
            // Send a copy to the user (if you want user to get confirmation)
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email, $name);
            }
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = 'Thank you for contacting Unsnap';
            $mail->Body = "Hello <b>{$name}</b>,<br><br>Thank you for contacting us!<br><br><b>Your Message:</b><br>" . nl2br($message) . "<br><br>We will get back to you soon.<br><br>--<br>Unsnap Team";
            $mail->AltBody = "Hello {$name},\n\nThank you for contacting us!\n\nYour Message:\n{$message}\n\nWe will get back to you soon.\n-- Unsnap Team";

            if ($mail->send()) {
                $successMsg = 'Your message has been sent!';
                // Log sent email to a file for proof
                $logMsg = "[" . date('Y-m-d H:i:s') . "] To: vikashkushwaha726@gmail.com | From: $email | Name: $name\nMessage: $message\n---\n";
                file_put_contents(__DIR__ . '/sent_emails.log', $logMsg, FILE_APPEND);
            } else {
                $errorMsg = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                // Log error
                $logMsg = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $mail->ErrorInfo . "\n";
                file_put_contents(__DIR__ . '/sent_emails.log', $logMsg, FILE_APPEND);
            }
        } catch (Exception $e) {
            $errorMsg = 'Message could not be sent. Exception: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="fab.png" type="image/x-icon">
  <title>Unsnap | About | Image Search Engine</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

  <!-- Header with Navigation -->
  <header class="bg-purple-700 text-white py-5 shadow-md">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
      <div class="flex items-center space-x-6">
        <h1 class="text-2xl font-bold tracking-wide">Unsnap</h1>
        <nav class="hidden md:flex space-x-4 text-sm">
          <a href="index.php" class="hover:underline">Home</a>
          <a href="about.html" class="hover:underline">About</a>
          <a href="privacy.html" class="hover:underline">Privacy</a>
          <a href="terms.html" class="hover:underline">Terms</a>
          <a href="contact.php" class="hover:underline">Contact</a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Contact Section -->
  <main class="py-16 px-6 md:px-20">
    <div class="max-w-3xl mx-auto text-center">
      <h2 class="text-4xl font-bold text-purple-700 mb-6">Contact Us</h2>
      <p class="text-lg text-gray-700 mb-10">
        Have questions, suggestions, or feedback? Fill out the form below or email us at 
        <a href="mailto:vikashkushwaha726@gmail.com" class="text-purple-600 underline">vikashkushwaha726@gmail.com</a>.
      </p>
      
      <?php if ($successMsg): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded"> <?= $successMsg ?> </div>
      <?php elseif ($errorMsg): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded"> <?= $errorMsg ?> </div>
      <?php endif; ?>
      <form action="#" method="post" class="bg-white rounded-lg shadow-md p-8 space-y-6">
        <div>
          <label class="block text-left text-gray-700 mb-2 font-semibold">Your Name</label>
          <input type="text" name="name" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-2 focus:ring-purple-500" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-left text-gray-700 mb-2 font-semibold">Your Email</label>
          <input type="email" name="email" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-2 focus:ring-purple-500" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-left text-gray-700 mb-2 font-semibold">Your Message</label>
          <textarea name="message" rows="5" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="w-full bg-purple-700 text-white font-semibold py-3 rounded hover:bg-purple-800 transition">
          📩 Send Message
        </button>
      </form>
    </div>
  </main>

  <!-- Footer -->
    </body>
</html> 
  <footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>Unsnap</h3>
      <p>The internet's source of freely usable images. Powered by creators everywhere.</p>
      <p class="text-xs mt-2 text-gray-200">
        All images are provided by the 
        <a href="https://unsplash.com" class="underline" target="_blank">Unsplash API</a> under the 
        <a href="https://unsplash.com/license" target="_blank" class="underline">Unsplash License</a>.
      </p>
      <div class="social-icons mt-2">
        <a href="https://github.com/vikash0064"><i class="fab fa-github"></i></a>
        <a href="https://www.linkedin.com/in/vikash-kushwaha-b831a028b/"><i class="fab fa-linkedin"></i></a>
        <a href="https://www.instagram.com/vikash0064"><i class="fab fa-instagram"></i></a>
        <a href="https://in.pinterest.com/kushwahav912/"><i class="fab fa-pinterest"></i></a>
      </div>
    </div>

    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="#">Popular Images</a></li>
        <li><a href="#">Featured Collections</a></li>
        <li><a href="#">Photographers</a></li>
        <li><a href="#">License</a></li>
        <li><a href="/sitemap.xml">Sitemap</a></li>
        <li><a href="/robots.txt">Robots.txt</a></li>
      </ul>
    </div>

    <div class="footer-section">
      <h3>Company</h3>
      <ul>
        <li><a href="about.html">About Us</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Careers</a></li>
        <li><a href="mailto:vikashkushwaha726@gmail.com">Contact</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; 2025 Unsnap. All images from Unsplash API.</p>
  </div>
</footer>


</body>
</html>