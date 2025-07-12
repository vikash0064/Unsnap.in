<?php
session_start();
require_once 'config.php'; // Your database connection

header('Content-Type: application/json');

$response = ['isSaved' => false];

if (!isset($_SESSION['user_id']) && !isset($_SESSION['google_user'])) {
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['google_user']['id'];
$image_id = filter_input(INPUT_GET, 'image_id', FILTER_SANITIZE_STRING);

if (!$image_id) {
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_img WHERE user_id = ? AND unsplash_id = ?"); // Changed here
    $stmt->execute([$user_id, $image_id]);
    if ($stmt->fetchColumn() > 0) {
        $response['isSaved'] = true;
    }
} catch (PDOException $e) {
    error_log("Error checking saved status: " . $e->getMessage());
}

echo json_encode($response);
?>