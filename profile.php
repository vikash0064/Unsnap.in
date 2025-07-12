<?php
include 'header.php';



error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$totalImages = 0;
$totalPages = 0;
$saved_images = [];

// ✅ Define imageExists here


// Use Google user data if logged in via Google
if (isset($_SESSION['google_user'])) {
    $userData = [
        'username' => $_SESSION['google_user']['name'] ?? 'Google User',
        'email' => $_SESSION['google_user']['email'] ?? '',
        'profile_image' => $_SESSION['google_user']['picture'] ?? ''
    ];
} else {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
        header("Location: login.php");
        exit;
    }

    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    try {
        // Get user profile
        $userStmt = $pdo->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            throw new Exception("User profile not found");
        }

        // Saved image logic (only for email-registered users)
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]);

        $limit = 12;
        $offset = ($page - 1) * $limit;
        $sort = isset($_GET['sort']) && in_array($_GET['sort'], ['newest', 'oldest']) ? $_GET['sort'] : 'newest';
        $order = $sort === 'newest' ? 'DESC' : 'ASC';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM saved_images WHERE user_id = ?");
        $countStmt->execute([$_SESSION['user_id']]);
        $totalImages = $countStmt->fetchColumn();
        $totalPages = ceil($totalImages / $limit);

        $stmt = $pdo->prepare("SELECT * FROM saved_images WHERE user_id = ? ORDER BY uploaded_at {$order} LIMIT ? OFFSET ?");
        $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
        $saved_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

function imageExists($path) {
    if (empty($path)) return false;
    return file_exists($path) || filter_var($path, FILTER_VALIDATE_URL);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="fab.png" type="image/x-icon">
    <title>My Profile | <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #5f2eea 40%, #00c9a7 60%);
        }
        .gradient-text {
            background: linear-gradient(90deg, #5f2eea, #00c9a7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .profile-header {
            background: linear-gradient(rgba(95, 46, 234, 0.8), rgba(0, 201, 167, 0.8)), 
                        url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }
        .initial-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 128px;
            height: 128px;
            font-size: 3rem;
            font-weight: bold;
            background: #5f2eea;
            color: #fff;
            border-radius: 9999px;
            border: 4px solid #fff;
            margin: 0 auto 1.5rem auto;
        }
    </style>
</head>
<body class="bg-gray-50">
<!-- Remaining HTML structure stays the same -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="fab.png" type="image/x-icon">
    <title>My Profile | <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #5f2eea 40%, #00c9a7 60%);
        }
        .gradient-text {
            background: linear-gradient(90deg, #5f2eea, #00c9a7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .profile-header {
            background: linear-gradient(rgba(95, 46, 234, 0.8), rgba(0, 201, 167, 0.8)), 
                        url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }
    .image-card {
    transition: all 0.3s ease;
    min-height: 300px; /* added */
}
.image-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

        .pagination .active {
            background: linear-gradient(135deg, #5f2eea 40%, #00c9a7 60%);
            color: white;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .initial-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 128px;
            height: 128px;
            font-size: 3rem;
            font-weight: bold;
            background: #5f2eea;
            color: #fff;
            border-radius: 9999px;
            border: 4px solid #fff;
            margin: 0 auto 1.5rem auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <!-- Site Name: Left aligned and bigger -->
                <div class="flex-shrink-1">
                    <a href="index.php" class="text-2xl font-extrabold  tracking-wide ">
                        <?php echo htmlspecialchars(SITE_NAME); ?>
                    </a>
                </div>
                <!-- Spacer to push links to the end -->
                <div class="flex-1"></div>
                <!-- Navigation Links: Right aligned at end -->
                <div class="flex items-center space-x-5">
                    <a href="profile.php" class="text-black hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user mr-1"></i> Profile
                    </a>
                    <a href="index.php" class="text-black hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="upload.php" class="text-black hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-upload mr-1"></i> Upload
                    </a>
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                            <span class="text-gray-700 text-sm font-medium"><?php echo htmlspecialchars($userData['username']); ?></span>
                            <?php if (!empty($userData['profile_image']) && imageExists($userData['profile_image']) && filter_var($userData['profile_image'], FILTER_VALIDATE_URL)): ?>
    <img class="h-8 w-8 rounded-full object-cover"
         src="<?php echo htmlspecialchars($userData['profile_image']); ?>"
         alt="Profile">
<?php else: ?>
    <span class="initial-circle" style="width:32px;height:32px;font-size:1rem;margin:0;">
        <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
    </span>
<?php endif; ?>

                        </button>
                        <!-- Dropdown menu -->
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Your Profile
                            </a>
                            <a href="edit-profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header text-white py-16 px-6 text-center">
        <div class="max-w-4xl mx-auto">
           <?php if (!empty($userData['profile_image']) && imageExists($userData['profile_image']) && filter_var($userData['profile_image'], FILTER_VALIDATE_URL)): ?>
    <div class="w-32 h-32 mx-auto rounded-full border-4 border-white overflow-hidden mb-6">
        <img src="<?php echo htmlspecialchars($userData['profile_image']); ?>" 
             alt="Profile Picture" 
             class="w-full h-full object-cover">
    </div>
<?php else: ?>
    <div class="initial-circle">
        <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
    </div>
<?php endif; ?>

            
            <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($userData['username']); ?></h1>
            <p class="text-lg opacity-90"><?php echo htmlspecialchars($userData['email']); ?></p>
            <div class="mt-6 flex justify-center space-x-4">
                <a href="edit-profile.php" class="inline-block px-6 py-2 bg-white text-purple-700 rounded-full font-medium hover:bg-gray-100 transition">
                    <i class="fas fa-user-edit mr-2"></i>Edit Profile
                </a>
                <a href="reset-password.php" class="inline-block px-6 py-2 bg-white text-purple-700 rounded-full font-medium hover:bg-gray-100 transition">
                    <i class="fas fa-lock mr-2"></i>Change Password
                </a>
                <a href="index.php" class="inline-block px-6 py-2 bg-white text-purple-700 rounded-full font-medium hover:bg-gray-100 transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
            </div>
        </div>
    </div>

    <!-- Saved Images Section -->
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-heart text-red-500 mr-2"></i>
                My Saved Images
                <span class="ml-2 text-xs sm:text-sm bg-purple-100 text-purple-800 py-1 px-2 sm:px-3 rounded-full">
                    <?php echo $totalImages; ?> images
                </span>
            </h2>
            <?php if (!empty($saved_images)): ?>
                <div class="flex items-center space-x-2 mt-4 sm:mt-0">
                    <span class="text-xs sm:text-sm text-gray-600">Sort by:</span>
                    <select id="sort-options" class="border rounded px-2 py-1 text-xs sm:text-sm">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($saved_images)): ?>
            <div class="text-center py-10 bg-white rounded-lg shadow-sm">
                <i class="fas fa-image text-3xl text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-700 mb-1">No saved images yet</h3>
                <p class="text-gray-500 mb-4">Start exploring and save your favorite images</p>
                <a href="index.php" class="inline-block px-5 py-2 gradient-bg text-white rounded-full font-medium hover:opacity-90 transition">
                    <i class="fas fa-search mr-1"></i> Explore Images
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <?php foreach ($saved_images as $image): ?>
                    <div class="image-card bg-white rounded-lg overflow-hidden shadow-md">
                        <div class="relative group">
                            <?php $imgSrc = imageExists($image['thumbnail_url']) ? $image['thumbnail_url'] : 'assets/default-image.jpg'; ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                 alt="<?php echo htmlspecialchars($image['description'] ?? 'Saved image'); ?>"
                                 class="w-full h-32 sm:h-40 object-cover transition duration-300 group-hover:opacity-90"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <a href="<?php echo htmlspecialchars($image['image_url']); ?>"
                                   target="_blank"
                                   class="text-white bg-black bg-opacity-50 rounded-full p-2 mr-1 hover:bg-opacity-70 transition"
                                   title="View Full Size">
                                    <i class="fas fa-expand"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars($image['image_url']); ?>"
                                   download
                                   class="text-white bg-black bg-opacity-50 rounded-full p-2 mr-1 hover:bg-opacity-70 transition"
                                   title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-gray-800 mb-1 text-sm truncate"><?php echo htmlspecialchars($image['title'] ?? 'Untitled'); ?></h3>
                            <p class="text-gray-600 mb-1 text-xs line-clamp-2"><?php echo htmlspecialchars($image['description'] ?? 'No description'); ?></p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <?php
                                    // Make creator profile image small and circular
                                    $creatorImg = !empty($image['creator_profile']) && imageExists($image['creator_profile']) ? $image['creator_profile'] : 'assets/default-avatar.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($creatorImg); ?>"
                                         alt="<?php echo htmlspecialchars($image['creator_name'] ?? 'Unknown creator'); ?>"
                                         class="w-8 h-8 rounded-full object-cover border-2 border-white shadow"
                                         style="width:32px;height:32px;min-width:32px;min-height:32px;">
                                    <span class="text-xs text-gray-500 truncate ml-2" style="max-width: 80px;">
                                        <?php echo htmlspecialchars($image['creator_name'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                                    <span class="text-xs text-gray-400">
                                        <?php echo date('M j, Y', strtotime($image['uploaded_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-12">
                    <div class="pagination flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>" 
                               class="px-4 py-2 border rounded hover:bg-gray-100">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1) {
                            echo '<a href="?page=1&sort='.$sort.'" class="px-4 py-2 border rounded hover:bg-gray-100">1</a>';
                            if ($start > 2) echo '<span class="px-4 py-2">...</span>';
                        }
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>" 
                               class="px-4 py-2 border rounded <?php echo $i == $page ? 'active font-bold' : 'hover:bg-gray-100'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;
                        
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="px-4 py-2">...</span>';
                            echo '<a href="?page='.$totalPages.'&sort='.$sort.'" class="px-4 py-2 border rounded hover:bg-gray-100">'.$totalPages.'</a>';
                        }
                        ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>" 
                               class="px-4 py-2 border rounded hover:bg-gray-100">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="gradient-bg text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="privacy.html" class="hover:underline">Privacy Policy</a>
                    <a href="terms.html" class="hover:underline">Terms</a>
                    <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>" class="hover:underline">Contact</a>
                </div>
            </div>
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
        
        // Sort functionality
        const sortSelect = document.getElementById('sort-options');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('sort', this.value);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });
        }
    </script>
</body>
</html>