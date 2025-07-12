<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';
$conn = getMysqli();

$successMsg = '';
$errorMsg = '';

// Upload Blog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imgName = uniqid('blog_') . '_' . basename($_FILES['image']['name']);
        $targetDir = 'uploads/blogs/';
        if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        $targetFile = $targetDir . $imgName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $imgName;
        }
    }
    $stmt = $conn->prepare("INSERT INTO blogs (user_id, title, content, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $title, $content, $image);
    if ($stmt->execute()) {
        $_SESSION['successMsg'] = 'Blog uploaded successfully!';
        header('Location: blogs.php');
        exit();
    } else {
        $errorMsg = 'Failed to upload blog.';
    }
}

// Delete Blog
if (isset($_POST['delete_blog_id'])) {
    $delete_id = intval($_POST['delete_blog_id']);
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $delete_id, $user_id);
    $stmt->execute();
    $successMsg = 'Blog deleted successfully!';
}

// Like/Unlike functionality
if (isset($_POST['action']) && $_POST['action'] === 'like') {
    $blog_id = intval($_POST['blog_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $check = $conn->prepare("SELECT id FROM likes WHERE blog_id = ? AND user_id = ?");
    $check->bind_param('ii', $blog_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE blog_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $blog_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'unliked']);
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (blog_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $blog_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'liked']);
    }
    exit();
}

// Fetch Blogs with user information
$user_id = $_SESSION['user_id'];
$blogs = [];
$sql = "SELECT b.id, b.title, b.content, b.image, b.created_at, b.user_id, u.username, u.profile_image,
            (SELECT COUNT(*) FROM likes l WHERE l.blog_id = b.id) AS like_count,
            (SELECT COUNT(*) FROM likes l WHERE l.blog_id = b.id AND l.user_id = $user_id) AS user_liked
        FROM blogs b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $blogs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Blogs - MySite</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.2/dist/driver.min.css">
  <link rel="icon" href="fab.png" type="image/x-icon" />
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(120deg, #f6d365 0%, #fda085 100%);
      margin: 0;
      min-height: 100vh;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background: rgba(255,255,255,0.9);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .header h1 {
      margin: 0;
      font-size: 2rem;
      color: #ff6f61;
    }
    .logout-btn {
      background: #ff6f61;
      color: #fff;
      border: none;
      padding: 0.5rem 1.2rem;
      border-radius: 25px;
      font-size: 1rem;
      cursor: pointer;
    }
    .logout-btn:hover {
      background: #d84315;
    }
    .blog-container {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      justify-content: center;
      padding: 2rem 1rem 5rem 1rem;
    }
    .blog-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      width: 340px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      animation: fadeInUp 0.7s;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .blog-image {
      width: 100%;
      height: auto;
      max-height: 180px;
      object-fit: cover;
      cursor: pointer;
      transition: transform 0.3s;
    }
    .blog-image:hover {
      transform: scale(1.02);
    }
    .blog-content {
      padding: 1.2rem;
      flex: 1;
      word-break: break-word;
    }
    .blog-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: #ff6f61;
    }
    .blog-meta {
      font-size: 0.9rem;
      color: #888;
      margin-bottom: 0.7rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.7rem;
    }
    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }
    .username {
      font-weight: 500;
      color: #555;
    }
    .blog-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }
    .like-btn, .share-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.2rem;
      color: #ff6f61;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    .like-btn.liked i { color: #d84315 !important; }

    /* Image Modal */
    .image-modal {
      display: none;
      position: fixed;
      z-index: 3000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.9);
      justify-content: center;
      align-items: center;
    }
    .image-modal-content {
      max-width: 90%;
      max-height: 90%;
    }
    .close-image-modal {
      position: absolute;
      top: 20px;
      right: 30px;
      color: white;
      font-size: 35px;
      font-weight: bold;
      cursor: pointer;
    }

    /* Upload Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0; top: 0;
      width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.4);
      align-items: center;
      justify-content: center;
    }
    .modal.active {
      display: flex;
    }
    .modal-content {
      background: #fff;
      border-radius: 18px;
      padding: 2rem;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }
    .close-modal {
      position: absolute;
      top: 1rem;
      right: 1rem;
      font-size: 1.5rem;
      background: none;
      border: none;
      cursor: pointer;
      color: #888;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #555;
      font-weight: 500;
    }
    .form-group input[type="text"],
    .form-group textarea {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border 0.3s;
    }
    .form-group input[type="text"]:focus,
    .form-group textarea:focus {
      border-color: #ff6f61;
      outline: none;
    }
    .form-group textarea {
      min-height: 150px;
      resize: vertical;
    }
    .file-upload {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .file-upload-label {
      display: inline-block;
      padding: 0.8rem 1.5rem;
      background: #f0f0f0;
      color: #555;
      border-radius: 8px;
      cursor: pointer;
      text-align: center;
      transition: all 0.3s;
    }
    .file-upload-label:hover {
      background: #e0e0e0;
    }
    .file-upload input[type="file"] {
      display: none;
    }
    .submit-btn {
      background: linear-gradient(120deg, #ff6f61, #fda085);
      color: white;
      border: none;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
    }
    .submit-btn:hover {
      opacity: 0.9;
    }

    /* Responsive buttons (Home + Logout) */
    @media (max-width: 768px) {
      #guide-home, #guide-logout {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        padding: 0;
        justify-content: center;
        font-size: 1.2rem;
        position: fixed;
        top: 1rem;
        z-index: 999;
      }
      #guide-home {
        right: 4rem;
        background: #ff6f61;
      }
      #guide-logout {
        right: 1rem;
        background: #d84315;
      }
      #guide-home span,
      #guide-logout span {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1 id="guide-title"><i class="fa-solid fa-blog"></i> My Blogs</h1>
    <div style="display:flex;gap:1rem;align-items:center;">
      <a href="index.php" class="logout-btn" id="guide-home" title="Home" style="text-decoration:none;display:flex;align-items:center;gap:0.5rem;">
        <i class="fa-solid fa-house"></i><span>Home</span>
      </a>
      <form method="post" action="logout.php" style="margin:0;">
        <button class="logout-btn" type="submit" id="guide-logout" title="Logout" style="display:flex;align-items:center;gap:0.5rem;">
          <i class="fa-solid fa-sign-out-alt"></i><span>Logout</span>
        </button>
      </form>
    </div>
  </div>

  <?php if (isset($_SESSION['successMsg'])): ?>
    <div id="successMsg" style="background:#e0ffe0;color:#207520;padding:1em;margin:1em auto;border-radius:8px;text-align:center;max-width:500px;">
      <?= $_SESSION['successMsg']; unset($_SESSION['successMsg']); ?>
    </div>
  <?php elseif ($errorMsg): ?>
    <div style="background:#ffe0e0;color:#a00;padding:1em;margin:1em auto;border-radius:8px;text-align:center;max-width:500px;">
      <?= $errorMsg; ?>
    </div>
  <?php endif; ?>

  <div class="blog-container">
    <?php if (empty($blogs)): ?>
      <p style="font-size:1.2rem;color:#555;">No blogs yet. Be the first to upload!</p>
    <?php else: foreach ($blogs as $blog): ?>
      <div class="blog-card">
        <?php if (!empty($blog['image'])): ?>
          <img src="uploads/blogs/<?= htmlspecialchars($blog['image']); ?>" class="blog-image" alt="Blog" onclick="openImageModal('uploads/blogs/<?= htmlspecialchars($blog['image']); ?>')">
        <?php else: ?>
          <img src="https://source.unsplash.com/340x180/?blog,writing" class="blog-image" alt="Blog">
        <?php endif; ?>
        <div class="blog-content">
          <div class="user-info">
            <?php
              $profileImg = 'uploads/profile_images/' . htmlspecialchars($blog['profile_image']);
              if (empty($blog['profile_image']) || !file_exists($profileImg) || $blog['profile_image'] === 'default-profile.jpg') {
                  $profileImg = 'https://ui-avatars.com/api/?name=' . urlencode($blog['username']) . '&background=ff6f61&color=fff';
              }
            ?>
            <img src="<?= $profileImg; ?>" class="user-avatar" alt="User">
            <span class="username"><?= htmlspecialchars($blog['username']); ?></span>
          </div>
          <div class="blog-title"><?= htmlspecialchars($blog['title']); ?></div>
          <div class="blog-meta">
            <i class="far fa-calendar-alt"></i>
            <?= date('M d, Y', strtotime($blog['created_at'])); ?>
          </div>
          <div style="color:#444;max-height:70px;overflow:hidden;">
            <?= nl2br(htmlspecialchars(substr($blog['content'],0,120))); ?>...
          </div>
          <div class="blog-actions">
            <button class="like-btn <?= $blog['user_liked'] ? 'liked' : '' ?>" data-id="<?= $blog['id']; ?>">
              <i class="fa-solid fa-heart"></i>
              <span class="like-count"><?= $blog['like_count']; ?></span>
            </button>
            <button class="share-btn" data-id="<?= $blog['id']; ?>" onclick="shareBlog(<?= $blog['id']; ?>)">
              <i class="fa-solid fa-share-nodes"></i>
            </button>
            <?php if ($blog['user_id'] == $_SESSION['user_id']): ?>
              <form method="post" onsubmit="return confirm('Delete this blog?');" style="display:inline;">
                <input type="hidden" name="delete_blog_id" value="<?= $blog['id']; ?>">
                <button type="submit" title="Delete" style="color:#a00;background:none;border:none;cursor:pointer;">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <button class="upload-btn" id="openUploadModal" title="Upload Blog" style="
    position: fixed;
    bottom: 2.5rem;
    right: 2.5rem;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(120deg,rgb(216, 79, 67), #fda085);
    color: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    z-index: 1000;
    transition: background 0.3s, transform 0.2s;
    cursor: pointer;
  "
  onmouseover="this.style.background='linear-gradient(120deg,#fda085,#ff6f61)';this.style.transform='scale(1.08)';"
  onmouseout="this.style.background='linear-gradient(120deg,#ff6f61,#fda085)';this.style.transform='scale(1)';"
  >
    <i class="fa-solid fa-plus"></i>
  </button>

  <!-- Image Modal -->
  <div id="imageModal" class="image-modal">
    <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
    <img class="image-modal-content" id="expandedImg">
  </div>

  <!-- Upload Modal -->
  <div class="modal" id="uploadModal">
    <div class="modal-content">
      <button class="close-modal" id="closeUploadModal" title="Close">&times;</button>
      <h2 style="color: #ff6f61; text-align: center; margin-bottom: 1.5rem;">Create New Blog Post</h2>
      <form method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="blogTitle">Title</label>
          <input type="text" id="blogTitle" name="title" placeholder="Enter blog title..." required>
        </div>
        
        <div class="form-group">
          <label for="blogContent">Content</label>
          <textarea id="blogContent" name="content" placeholder="Write your blog content here..." required></textarea>
        </div>
        
        <div class="form-group file-upload">
          <label for="blogImage" class="file-upload-label">
            <i class="fas fa-image"></i> Choose Blog Image (Optional)
          </label>
          <input type="file" id="blogImage" name="image" accept="image/*">
          <span id="fileName" style="font-size: 0.9rem; color: #666;"></span>
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-upload"></i> Upload Blog
        </button>
      </form>
    </div>
  </div>

  <!-- JS Scripts -->
  <script>
    // Modal functionality
    const modal = document.getElementById('uploadModal');
    document.getElementById('openUploadModal').onclick = () => modal.classList.add('active');
    document.getElementById('closeUploadModal').onclick = () => modal.classList.remove('active');
    window.onclick = function(e) {
      if (e.target === modal) modal.classList.remove('active');
    };

    // Image modal functionality
    function openImageModal(src) {
      const modal = document.getElementById('imageModal');
      const img = document.getElementById('expandedImg');
      img.src = src;
      modal.style.display = 'flex';
    }

    function closeImageModal() {
      document.getElementById('imageModal').style.display = 'none';
    }

    // File name display
    document.getElementById('blogImage').addEventListener('change', function(e) {
      const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
      document.getElementById('fileName').textContent = fileName;
    });

    // Like functionality
    document.querySelectorAll('.like-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const blogId = this.getAttribute('data-id');
        const likeCount = this.querySelector('.like-count');
        
        fetch('blogs.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=like&blog_id=${blogId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'liked') {
            this.classList.add('liked');
            likeCount.textContent = parseInt(likeCount.textContent) + 1;
          } else {
            this.classList.remove('liked');
            likeCount.textContent = parseInt(likeCount.textContent) - 1;
          }
        });
      });
    });

    // Share functionality
    function shareBlog(blogId) {
      const url = window.location.href.split('?')[0] + '?blog=' + blogId;
      if (navigator.share) {
        navigator.share({
          title: 'Check out this blog post!',
          url: url
        }).catch(err => {
          copyToClipboard(url);
        });
      } else {
        copyToClipboard(url);
      }
    }

    function copyToClipboard(text) {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      alert('Link copied to clipboard!');
    }

    // --- Infinite Scroll for up to 5 pages ---
    let page = 1;
    let loading = false;
    let lastPage = false;
    let maxPages = 5; // Stop after 5 pages

    function fetchBlogs() {
      if (loading || lastPage || page > maxPages) return;
      loading = true;
      fetch('fetch_blogs.php?page=' + page)
        .then(res => res.text())
        .then(html => {
          if (html.trim() === '' || html.trim() === 'NO_MORE') {
            lastPage = true;
          } else {
            document.querySelector('.blog-container').insertAdjacentHTML('beforeend', html);
            page++;
            if (page > maxPages) lastPage = true;
          }
          loading = false;
        });
    }

    window.addEventListener('scroll', function() {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
        fetchBlogs();
      }
    });

    // Initial load
    fetchBlogs();
  </script>
</body>
</html>