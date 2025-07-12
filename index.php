
<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css?v=2" /> 
  <link rel="icon" href="fab.png" type="image/x-icon">

  <title>Unsnap - Stunning Free HD Images</title>
</head>

<body>
<!-- Navbar -->
<nav style="display: flex; align-items: center; padding: 0 2rem;">
  <div class="logo" style="display:flex; align-items:center;">
    <i class="fas fa-camera"></i>
    <h1 style="margin-left:8px;">Unsnap</h1>
  </div>
  <ul class="nav-links" style="display:flex;gap:1.2rem;align-items:center;list-style:none;margin:0; margin-left:auto; margin-right:0.5rem;">
    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
    <li><a href="about.html"><i class="fas fa-info-circle"></i> About</a></li>
    <li><a href="blogs.php" style="background:linear-gradient(90deg,#ff9800,#ff6a5b);color:white;border-radius:20px;padding:7px 18px;font-weight:600;box-shadow:0 2px 8px #ff980033;"> <i class="fas fa-blog"></i> Blogs</a></li>
    <?php if (isLoggedIn()): ?>
      <li><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
      <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      <?php
        if (isset($_SESSION['google_user']['picture'])) {
          $profileImg = htmlspecialchars($_SESSION['google_user']['picture']);
          echo '<li class="initial-circle"><img src="' . $profileImg . '" alt="Profile" style="width:32px;height:32px;border-radius:50%;object-fit:cover;"></li>';
        } elseif (!empty($_SESSION['username'])) {
          echo '<li class="initial-circle">' . strtoupper(substr($_SESSION['username'], 0, 1)) . '</li>';
        } elseif (isset($_SESSION['email'])) {
          echo '<li class="initial-circle">' . strtoupper(substr($_SESSION['email'], 0, 1)) . '</li>';
        }
      ?>
    <?php else: ?>
      <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
    <?php endif; ?>
  </ul>
   <div class="theme-toggle" style="margin-left:0; margin-right:2rem;">
    <input type="checkbox" id="theme-toggle-checkbox">
    <label for="theme-toggle-checkbox" class="theme-toggle-label">
       <i class="fas fa-sun"></i>
       <i class="fas fa-moon"></i>
       <span class="theme-toggle-ball"></span>
     </label>
   </div>
  <div class="menu-toggle" id="mobile-menu" style="margin-left:1rem;">
    <span></span><span></span><span></span>
  </div>
</nav>

<!-- Hero Section -->
<div class="hero">
  <div class="hero-content">
    <h2>Discover Stunning HD Images</h2>
    <p>Download high-resolution photos for free. Perfect for your projects and inspiration.</p>
  </div>
</div>

<div class="container">
  <form id="search-form">
    <div class="search-container">
      <input type="text" id="search-box" placeholder="Search for anything..." />
      <button type="submit"><i class="fas fa-search"></i> Search</button>
    </div>
  </form>

  <div class="trending-tags">
    <p>Trending searches:</p>
    <div class="tags">
      <span>Nature</span>
      <span>Wallpapers</span>
      <span>Travel</span>
      <span>Animals</span>
      <span>Food</span>
      <span>Technology</span>
    </div>
  </div>

  <div id="search-result" class="image-grid"></div>
  <div class="button-wrapper">
    <button id="show-more-btn"><i class="fas fa-plus"></i> Show more</button>
  </div>
</div>

<!-- Welcome Section for Google AdSense and SEO -->
<section class="welcome-section" style="background: linear-gradient(120deg, #5f2eea 0%, #00c9a7 100%); padding: 2rem 0 1.5rem 0; text-align: center; color: #fff;">
  <div style="max-width: 650px; margin: 0 auto;">
    <h2 style="font-size:1.7rem;font-weight:700;margin-bottom:0.5rem;letter-spacing:0.5px;">Welcome to Unsnap – Your Free HD Image Hub</h2>
    <p style="font-size:0.98rem;line-height:1.6;">Unsnap is your go-to platform for discovering, downloading, and sharing high-quality, copyright-free images. Whether you’re a blogger, designer, student, or business owner, you’ll find the perfect photo for every project. Our mission is to empower creativity by making beautiful images accessible to everyone, for free.</p>
    <p style="font-size:0.93rem;margin-top:1rem;">No login required to browse. No hidden fees. Just search, explore, and get inspired!</p>
  </div>
</section>

<!-- Blog Previews Section -->
<section class="blog-preview-section" style="background:#fff; padding:2rem 0 1.5rem 0;">
  <div style="max-width:1000px;margin:0 auto;">
    <h3 style="font-size:2rem;font-weight:700;text-align:center;color:#5f2eea;margin-bottom:1.2rem;letter-spacing:0.5px;">Latest from Our Blog</h3>
    <div style="display:flex;flex-wrap:wrap;gap:1.2rem;justify-content:center;">
      <div style="background:linear-gradient(120deg, #5f2eea , #00c9a7);border-radius:1rem;box-shadow:0 2px 8px #ff980033;padding:1.1rem 1rem;max-width:290px;flex:1 1 250px;color:#fff;">
        <h4 style="font-size:1rem;font-weight:600;">How to Find the Perfect Free Image for Your Project</h4>
        <p style="font-size:0.92rem;opacity:0.95;">Discover expert tips on searching, filtering, and choosing the best copyright-free images for blogs, websites, and social media. <a href="blogs.php" style="color:#fff;text-decoration:underline;">Read more</a></p>
      </div>
      <div style="background:linear-gradient(120deg,#5f2eea , #00c9a7);border-radius:1rem;box-shadow:0 2px 8px #ff980033;padding:1.1rem 1rem;max-width:290px;flex:1 1 250px;color:#fff;">
        <h4 style="font-size:1rem;font-weight:600;">Why Unsnap is the Best Choice for Free HD Images</h4>
        <p style="font-size:0.92rem;opacity:0.95;">Learn what makes Unsnap unique, from our huge collection to our easy-to-use interface and zero-cost downloads. <a href="blogs.php" style="color:#fff;text-decoration:underline;">Read more</a></p>
      </div>
      <div style="background:linear-gradient(120deg,#5f2eea , #00c9a7);border-radius:1rem;box-shadow:0 2px 8px #ff980033;padding:1.1rem 1rem;max-width:290px;flex:1 1 250px;color:#fff;">
        <h4 style="font-size:1rem;font-weight:600;">Creative Ways to Use Free Images Legally</h4>
        <p style="font-size:0.92rem;opacity:0.95;">Explore creative ideas for using Unsnap images in marketing, education, and personal projects—while staying copyright safe. <a href="blogs.php" style="color:#fff;text-decoration:underline;">Read more</a></p>
      </div>
    </div>
  </div>
</section>

<!-- Image Modal -->
<div id="image-modal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <img id="modal-image" src="" alt="Modal Image">
    <div class="modal-info">
      <div class="modal-creator">
        <img id="modal-creator-img" src="" alt="Creator">
        <span id="modal-creator-name"></span>
      </div>
      <p id="modal-description"></p>
      <div class="modal-actions">
        <button id="modal-download-btn"><i class="fas fa-download"></i> Download</button>
        <button id="modal-save-btn"><i class="far fa-heart"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
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


<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
// Ensure 6 images load per page
let imagesPerPage = 6;
if (typeof window !== 'undefined') {
  window.imagesPerPage = imagesPerPage;
}

</script>

<script src="script.js?v=2"></script>

</body>
</html>