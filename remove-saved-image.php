<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    $image_id = filter_var($_POST['image_id'], FILTER_SANITIZE_NUMBER_INT);
    $username = $_SESSION['username'];

    try {
        $stmt = $pdo->prepare("DELETE FROM saved_img WHERE id = ? AND username = ?");
        $stmt->execute([$image_id, $username]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Image not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
exit;