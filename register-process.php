<?php
require_once 'config.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        redirect('register.php?error=All fields are required');
    }

    if ($password !== $confirm_password) {
        redirect('register.php?error=Passwords do not match');
    }

    if (!validatePassword($password)) {
        redirect('register.php?error=Password must be at least 8 characters with uppercase, lowercase, and number');
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            redirect('register.php?error=Email already registered');
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            redirect('register.php?error=Username already taken');
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);

        // Log user in
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['profile_image'] = 'default-profile.jpg';

        redirect('index.php?success=Registration successful!');
    } catch (PDOException $e) {
        redirect('register.php?error=Database error. Please try again later.');
    }
} else {
    redirect('register.php');
}
?>