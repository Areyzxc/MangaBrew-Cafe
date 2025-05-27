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
if (!isset($_POST['action']) || $_POST['action'] !== 'redeem_reward') {
    $_SESSION['error'] = 'Invalid action';
    header('Location: profile.php');
    exit;
}

try {
    // Get and validate reward type
    $reward_type = filter_input(INPUT_POST, 'reward_type', FILTER_SANITIZE_STRING);
    if (!in_array($reward_type, ['coffee', 'manga_rental'])) {
        throw new Exception('Invalid reward type');
    }

    // Start transaction
    $conn->beginTransaction();

    // Get user's current points
    $stmt = $conn->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Define reward costs
    $reward_costs = [
        'coffee' => 100,
        'manga_rental' => 200
    ];

    $cost = $reward_costs[$reward_type];

    // Check if user has enough points
    if ($user['points'] < $cost) {
        throw new Exception('Not enough points to redeem this reward');
    }

    // Deduct points and create reward record
    $stmt = $conn->prepare("
        UPDATE users 
        SET points = points - ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$cost, $_SESSION['user_id']]);

    // Insert reward record
    $stmt = $conn->prepare("
        INSERT INTO user_rewards (
            user_id, 
            reward_type, 
            points_cost, 
            status, 
            created_at
        ) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$_SESSION['user_id'], $reward_type, $cost]);

    // Commit transaction
    $conn->commit();

    // Set success message based on reward type
    $reward_messages = [
        'coffee' => 'Free coffee reward redeemed! Show this to the barista.',
        'manga_rental' => 'Manga rental reward redeemed! Visit the library to claim your rental.'
    ];
    $_SESSION['success'] = $reward_messages[$reward_type];

    // Optional: Send email notification
    // send_reward_redemption_notification($user['email'], $reward_type);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log('Reward redemption error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: profile.php');
exit; 