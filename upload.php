<?php
session_start();

require_once 'config.php';

// Ensure user is logged in via either standard or Google login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['google_user']['email'])) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: login.php");
    exit;
}

// Get or fetch user ID
$userId = $_SESSION['user_id'] ?? null;

// If Google user is logged in but user_id not yet fetched
if (!$userId && isset($_SESSION['google_user']['email'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['google_user']['email']]);
    $row = $stmt->fetch();

    if ($row) {
        $userId = $row['id'];
        $_SESSION['user_id'] = $userId;
    } else {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Fetch logged-in user data
$userStmt = $pdo->prepare("SELECT username, profile_image FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Set fallback for initial circle if profile image is not available
$username = $userData['username'] ?? ($_SESSION['google_user']['name'] ?? 'U');
$initial = strtoupper(substr($username, 0, 1));

// CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $imagesStmt = $pdo->prepare("SELECT * FROM saved_images WHERE user_id = ? ORDER BY id DESC");
    $imagesStmt->execute([$userId]);
    $uploadedImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    try {
        $required = ['title', 'description', 'category_id', 'creator_name'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }

        if (empty($_FILES['image']['tmp_name'])) {
            throw new Exception("Please select an image file");
        }

        if ($_FILES['image']['size'] > 5242880) {
            throw new Exception("Image file size exceeds 5MB limit");
        }

        $uploadDirs = ['uploads/images', 'uploads/thumbnails', 'uploads/profiles'];
        foreach ($uploadDirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $image = $_FILES['image'];
        $imageName = 'img_' . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        $imagePath = 'uploads/images/' . $imageName;

        if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
            throw new Exception("Failed to upload image");
        }

        $thumbnailName = 'thumb_' . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        $thumbnailPath = 'uploads/thumbnails/' . $thumbnailName;

        if (!createThumbnail($imagePath, $thumbnailPath, 400, 300)) {
            throw new Exception("Failed to create thumbnail");
        }

        $creatorProfilePath = null;
        if (!empty($_FILES['creator_profile']['tmp_name'])) {
            $creatorName = 'creator_' . uniqid() . '.' . pathinfo($_FILES['creator_profile']['name'], PATHINFO_EXTENSION);
            $creatorProfilePath = 'uploads/profiles/' . $creatorName;
            if (!move_uploaded_file($_FILES['creator_profile']['tmp_name'], $creatorProfilePath)) {
                throw new Exception("Failed to upload creator profile");
            }
        } else {
            // No profile uploaded: generate placeholder image with initial letter
            $creatorInitial = strtoupper(substr($_POST['creator_name'], 0, 1));
            $placeholderPath = 'uploads/profiles/placeholder_' . uniqid() . '.png';
            generatePlaceholderImage($creatorInitial, $placeholderPath);
            $creatorProfilePath = $placeholderPath;
        }

        $stmt = $pdo->prepare("INSERT INTO saved_images (user_id, image_url, thumbnail_url, title, description, category_id, creator_name, creator_profile, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $userId,
            $imagePath,
            $thumbnailPath,
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['creator_name'],
            $creatorProfilePath,
            $_POST['tags'] ?? ''
        ]);

        $_SESSION['success'] = "Image uploaded successfully!";
        header("Location: upload.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: upload.php");
        exit;
    }
}

function createThumbnail($src, $dest, $targetWidth, $targetHeight) {
    $type = exif_imagetype($src);
    switch ($type) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG: $image = imagecreatefrompng($src); break;
        case IMAGETYPE_GIF: $image = imagecreatefromgif($src); break;
        case IMAGETYPE_WEBP: $image = imagecreatefromwebp($src); break;
        default: return false;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $srcRatio = $width / $height;
    $destRatio = $targetWidth / $targetHeight;

    if ($destRatio > $srcRatio) {
        $newHeight = $targetHeight;
        $newWidth = $targetHeight * $srcRatio;
    } else {
        $newWidth = $targetWidth;
        $newHeight = $targetWidth / $srcRatio;
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $dest, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $dest); break;
        case IMAGETYPE_GIF: imagegif($thumb, $dest); break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $dest, 85); break;
    }
    imagedestroy($image);
    imagedestroy($thumb);
    return true;
}

function generatePlaceholderImage($initial, $destPath) {
    $img = imagecreatetruecolor(100, 100);
    $bgColor = imagecolorallocate($img, 95, 46, 234);
    $textColor = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0, 100, 100, $bgColor);

    $fontFile = __DIR__ . '/assets/fonts/arial.ttf';
    if (!file_exists($fontFile)) {
        imagestring($img, 5, 35, 35, $initial, $textColor);
    } else {
        imagettftext($img, 40, 0, 25, 65, $textColor, $fontFile, $initial);
    }

    imagepng($img, $destPath);
    imagedestroy($img);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="fab.png" type="image/x-icon">
    <title>Upload Image | <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  </style>
</head>
<body class="bg-gray-50 font-[Poppins]">

<!-- ✅ Navbar -->
<nav class="bg-white shadow-md ">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
    <div class="flex justify-between items-center h-16 space-x-12">
      <!-- Logo -->
      <div class="flex items-center space-x-2">
        <i class="fas fa-camera text-6xl text-black "></i>
        <h1 class="text-5xl font-bold text-white logo">Unsnap</h1>
      </div>

      <!-- Hamburger (for mobile - optional logic) -->
      <div class="sm:hidden">
        <button id="mobile-menu-button" class="text-gray-700 focus:outline-none">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>

      <!-- Navigation Links -->
      <ul class="hidden sm:flex space-x-12 items-center text-sm font-medium">
        <li>
          <a href="index.php" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
            <i class="fas fa-home"></i><span>Home</span>
          </a>
        </li>
        <li
          <a href="upload.php" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
            <i class="fas fa-upload"></i><span>Upload</span>
          </a>
        </li>
        <li>
          <a href="about.html" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
            <i class="fas fa-info-circle"></i><span>About</span>
          </a>
        </li>

        <?php if (isset($_SESSION['username'])): ?>
          <li>
            <a href="profile.php" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
              <i class="fas fa-user-circle"></i><span>Profile</span>
            </a>
          </li>
          <li>
            <a href="logout.php" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
              <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
          </li>
          <li>
            <div class="initial-circle"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
          </li>
        <?php else: ?>
          <li>
            <a href="login.php" class="text-black hover:text-white transition duration-200 flex items-center space-x-1">
              <i class="fas fa-sign-in-alt"></i><span>Login</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>


    <!-- Main Content -->
   <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-8 rounded-lg shadow-sm" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3 text-emerald-500"></i>
                <p class="font-medium"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow-sm" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                <p class="font-medium"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Upload Card -->
    <div class="bg-white rounded-xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-5 px-6 sm:px-8">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-white/10 backdrop-blur-sm mr-4">
                    <i class="fas fa-cloud-upload-alt text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Upload New Image</h1>
                    <p class="text-purple-100 text-sm mt-1">Share your creativity with the community</p>
                </div>
            </div>
        </div>
        
        <!-- Card Body -->
        <div class="p-6 sm:p-8">
            <form id="upload-form" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars((string)$_SESSION['csrf_token']) : ''; ?>">
                
                <!-- Image Upload -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-3 text-lg">Image*</label>
                    <div id="dropzone" class="dropzone border-2 border-dashed border-gray-300 hover:border-purple-400 rounded-2xl p-10 text-center cursor-pointer transition-all duration-300 bg-gray-50 hover:bg-purple-50">
                        <input type="file" name="image" id="image" class="hidden" accept="image/*" required>
                        <div id="dropzone-content" class="space-y-3">
                            <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-700 font-medium">Drag & drop your image here</p>
                                <p class="text-gray-500 text-sm mt-1">or click to browse files</p>
                            </div>
                            <div class="text-xs text-gray-400 bg-gray-100 rounded-full py-1 px-3 inline-block">
                                JPG, PNG, GIF, or WebP (Max 5MB)
                            </div>
                        </div>
                        <img id="image-preview" class="image-preview hidden mt-6 mx-auto rounded-lg max-h-60 shadow-md">
                    </div>
                </div>
                
                <!-- Image Details -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-gray-700 font-semibold mb-2">Title*</label>
                            <input type="text" name="title" id="title" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-700 placeholder-gray-400 transition-all" placeholder="Enter a descriptive title" required>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-gray-700 font-semibold mb-2">Description*</label>
                            <textarea name="description" id="description" rows="4" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-700 placeholder-gray-400 transition-all" placeholder="Tell us about your image" required></textarea>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="category_id" class="block text-gray-700 font-semibold mb-2">Category*</label>
                            <select name="category_id" id="category_id" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-700 transition-all appearance-none bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjd2hpdGUgIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiPjwvcG9seWxpbmU+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem] bg-[length:1.5rem]" required>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>" class="text-gray-700">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tags" class="block text-gray-700 font-semibold mb-2">Tags</label>
                            <div class="relative">
                                <input type="text" name="tags" id="tags" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-700 placeholder-gray-400 transition-all" placeholder="nature, landscape, sunset">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-tags text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Separate tags with commas</p>
                        </div>
                    </div>
                </div>
                
                <!-- Creator Information -->
                <div class="border-t border-gray-200 pt-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                        <span class="bg-purple-100 text-purple-800 p-2 rounded-full mr-3">
                            <i class="fas fa-user-edit"></i>
                        </span>
                        Creator Information
                    </h3>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <label for="creator_name" class="block text-gray-700 font-semibold mb-2">Creator Name*</label>
                            <div class="relative">
                                <input type="text" name="creator_name" id="creator_name" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-700 placeholder-gray-400 transition-all" placeholder="Who created this image?" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Creator Profile Image</label>
                            <div class="flex items-center space-x-6">
                                <div class="flex-1">
                                    <input type="file" name="creator_profile" id="creator_profile" class="hidden" accept="image/*">
                                    <button type="button" id="upload-creator-btn" class="w-full px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-all flex items-center justify-center space-x-2 border border-gray-300">
                                        <i class="fas fa-upload"></i>
                                        <span>Upload Profile</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <img id="creator-preview" src="assets/default-avatar.jpg" class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-md">
                                    <div class="absolute -bottom-1 -right-1 bg-purple-500 rounded-full p-1 shadow-sm">
                                        <i class="fas fa-camera text-white text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-6">
                    <button type="submit" name="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-cloud-upload-alt mr-2"></i> 
                        Upload Image
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Uploaded Images Section -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <span class="bg-purple-100 text-purple-800 p-3 rounded-full mr-4">
                    <i class="fas fa-images"></i>
                </span>
                Your Uploaded Images
            </h2>
            <div class="mt-4 sm:mt-0">
                <div class="relative">
                    <select class="appearance-none bg-white border border-gray-300 rounded-xl pl-5 pr-10 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option>Sort by Newest</option>
                        <option>Sort by Oldest</option>
                        <option>Sort by Popular</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
        
    <?php if (!empty($uploadedImages)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($uploadedImages as $img): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 group">
                <div class="relative overflow-hidden">
                    <img src="<?php echo htmlspecialchars($img['thumbnail_url'] ?? 'path/to/default-image.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($img['title'] ?? 'Untitled'); ?>" 
                         class="w-full h-60 object-cover transform group-hover:scale-105 transition-transform duration-500"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent flex items-end p-5">
                        <h3 class="text-white font-bold text-lg truncate"><?php echo htmlspecialchars($img['title'] ?? 'Untitled'); ?></h3>
                    </div>
                    <div class="absolute top-3 right-3 flex space-x-2">
                        <button class="p-2 bg-white/90 text-purple-600 rounded-full hover:bg-white transition">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                </div>
                <div class="p-5">
                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($img['description'] ?? 'No description available'); ?></p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <?php if (!empty($img['creator_profile'])): ?>
                                <img src="<?php echo htmlspecialchars($img['creator_profile']); ?>" 
                                     alt="<?php echo htmlspecialchars($img['creator_name'] ?? 'Creator'); ?>"
                                     class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm mr-3">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm border-2 border-white shadow-sm mr-3">
                                    <?php echo strtoupper(substr($img['creator_name'] ?? 'U', 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($img['creator_name'] ?? 'Unknown'); ?></span>
                        </div>
                        <span class="text-xs text-gray-500">
                            <?php 
                            // Safely handle created_at date
                            if (!empty($img['created_at']) && strtotime($img['created_at']) !== false) {
                                echo date('M j, Y', strtotime($img['created_at']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Pagination -->
    <div class="mt-12 flex justify-center">
        <nav class="flex items-center space-x-2">
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </a>
            <a href="#" class="px-4 py-2 border border-purple-500 bg-purple-50 text-purple-600 rounded-lg font-medium">1</a>
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">2</a>
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">3</a>
            <span class="px-2 text-gray-500">...</span>
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">8</a>
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
        </nav>
    </div>
<?php else: ?>
    <div class="bg-gray-50 rounded-2xl p-12 text-center border-2 border-dashed border-gray-200">
        <div class="mx-auto w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mb-6">
            <i class="fas fa-image text-3xl text-purple-600"></i>
        </div>
        <h3 class="text-xl font-medium text-gray-800 mb-2">No images uploaded yet</h3>
        <p class="text-gray-600 max-w-md mx-auto mb-6">Get started by uploading your first image using the form above</p>
        <a href="#upload-form" class="inline-block px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fas fa-cloud-upload-alt mr-2"></i> Upload Now
        </a>
    </div>
<?php endif; ?>
</div>

<script>
    // Enhanced dropzone functionality
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('image');
    const preview = document.getElementById('image-preview');
    const dropzoneContent = document.getElementById('dropzone-content');

    if (dropzone) {
        // Highlight dropzone when dragging over
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('border-purple-500', 'bg-purple-50');
                dropzone.classList.remove('border-gray-300');
            });
        });

        // Remove highlight when dragging leaves
        ['dragleave', 'dragend'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('border-purple-500', 'bg-purple-50');
                dropzone.classList.add('border-gray-300');
            });
        });

        // Handle file drop
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-purple-500', 'bg-purple-50');
            dropzone.classList.add('border-gray-300');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updatePreview(fileInput.files[0]);
            }
        });

        // Handle click to browse
        dropzone.addEventListener('click', () => fileInput.click());

        // Handle file selection via input
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                updatePreview(fileInput.files[0]);
            }
        });

        // Update preview function
        function updatePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                dropzoneContent.classList.add('hidden');
                dropzone.classList.remove('border-dashed');
            };
            reader.readAsDataURL(file);
        }
    }

    // Creator profile image upload
    const creatorUploadBtn = document.getElementById('upload-creator-btn');
    const creatorInput = document.getElementById('creator_profile');
    const creatorPreview = document.getElementById('creator-preview');

    if (creatorUploadBtn) {
        creatorUploadBtn.addEventListener('click', () => creatorInput.click());
        
        creatorInput.addEventListener('change', () => {
            if (creatorInput.files.length) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    creatorPreview.src = e.target.result;
                };
                reader.readAsDataURL(creatorInput.files[0]);
            }
        });
    }
</script>
    <!-- Footer -->
   <footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>Unsnap</h3>
      <p>The internet's source of freely usable images. Powered by creators everywhere.</p>
      <div class="social-icons">
        <a href="https://github.com/vikash0064"><i class="fab fa-github"></i></a>
        <a href="https://www.linkedin.com/in/vikash-kushwaha-b831a028b/"><i class="fab fa-linkedin"></i></a>
        <a href="https://www.instagram.com/vikash0064"><i class="fab fa-instagram"></i></a>
        <a href="https://in.pinterest.com/kushwahav912/"><i class="fab fa-pinterest-p"></i></a>
      </div>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="#">Popular Images</a></li>
        <li><a href="#">Featured Collections</a></li>
        <li><a href="#">Photographers</a></li>
        <li><a href="#">License</a></li>
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

    <script>
        // Toggle user dropdown menu
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', () => {
                userMenu.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }
        
        // Image upload dropzone functionality
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const dropzoneContent = document.getElementById('dropzone-content');
        
        if (dropzone) {
            // Click event
            dropzone.addEventListener('click', () => {
                fileInput.click();
            });
            
            // Drag and drop events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropzone.classList.add('active');
            }
            
            function unhighlight() {
                dropzone.classList.remove('active');
            }
            
            // Handle dropped files
            dropzone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    handleFiles(files);
                }
            }
            
            // Handle selected files
            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    handleFiles(this.files);
                }
            });
            
            function handleFiles(files) {
                const file = files[0];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        dropzoneContent.classList.add('hidden');
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    alert('Please select an image file.');
                }
            }
        }
        
        // Creator profile image upload
        const creatorProfileInput = document.getElementById('creator_profile');
        const uploadCreatorBtn = document.getElementById('upload-creator-btn');
        const creatorPreview = document.getElementById('creator-preview');
        
        if (uploadCreatorBtn && creatorProfileInput) {
            uploadCreatorBtn.addEventListener('click', () => {
                creatorProfileInput.click();
            });
            
            creatorProfileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        creatorPreview.src = e.target.result;
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        // Form validation
        const form = document.getElementById('upload-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!fileInput.files.length) {
                    e.preventDefault();
                    alert('Please select an image to upload.');
                }
            });
        }
    </script>
</body>
</html>