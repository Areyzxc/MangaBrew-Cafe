<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid security token';
    header('Location: profile.php');
    exit;
}

// Verify action
if (!isset($_POST['action']) || $_POST['action'] !== 'update_profile') {
    $_SESSION['error'] = 'Invalid action';
    header('Location: profile.php');
    exit;
}

try {
    // Validate and sanitize input
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

    // Validate full name (2-50 characters, letters and spaces only)
    if (!preg_match('/^[A-Za-z\s]{2,50}$/', $full_name)) {
        throw new Exception('Invalid name format. Use only letters and spaces (2-50 characters)');
    }

    // Validate email
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    // Validate phone (optional, but must be 11 digits if provided)
    if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
        throw new Exception('Invalid phone number format. Must be 11 digits');
    }

    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        throw new Exception('Email address is already in use');
    }

    // Update user profile
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name = ?, 
            email = ?, 
            phone = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([$full_name, $email, $phone, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Profile updated successfully';
    } else {
        $_SESSION['info'] = 'No changes were made to your profile';
    }

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: profile.php');
exit;
?>
