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
if (!isset($_POST['action']) || $_POST['action'] !== 'change_password') {
    $_SESSION['error'] = 'Invalid action';
    header('Location: profile.php');
    exit;
}

try {
    // Get and validate input
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate current password
    if (empty($current_password)) {
        throw new Exception('Current password is required');
    }

    // Validate new password
    if (empty($new_password)) {
        throw new Exception('New password is required');
    }

    // Validate password strength
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $new_password)) {
        throw new Exception('Password must be at least 8 characters long and include both letters and numbers');
    }

    // Validate password confirmation
    if ($new_password !== $confirm_password) {
        throw new Exception('New passwords do not match');
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("
        UPDATE users 
        SET password = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([$hashed_password, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Password updated successfully';
        
        // Optional: Send email notification
        // send_password_change_notification($user['email']);
    } else {
        throw new Exception('Failed to update password');
    }

} catch (Exception $e) {
    error_log('Password change error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: profile.php');
exit;
