<?php
session_start();
require 'db_connection.php';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid security token';
    header('Location: orders.php');
    exit;
}

// Validate cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: orders.php');
    exit;
}

// Validate pickup time
$pickup_time = filter_input(INPUT_POST, 'pickup_time', FILTER_SANITIZE_STRING);
if (!$pickup_time) {
    $_SESSION['error'] = 'Invalid pickup time';
    header('Location: orders.php');
    exit;
}

// Validate pickup time is within allowed range
$current_time = new DateTime();
$pickup_datetime = new DateTime($pickup_time);
$min_time = (clone $current_time)->modify('+30 minutes');
$max_time = (clone $current_time)->modify('+24 hours');

if ($pickup_datetime < $min_time || $pickup_datetime > $max_time) {
    $_SESSION['error'] = 'Pickup time must be between 30 minutes and 24 hours from now';
    header('Location: orders.php');
    exit;
}

// Validate payment method
$payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
if (!in_array($payment_method, ['cash', 'card'])) {
    $_SESSION['error'] = 'Invalid payment method';
    header('Location: orders.php');
    exit;
}

// Validate card details if payment method is card
if ($payment_method === 'card') {
    $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
    $card_expiry = filter_input(INPUT_POST, 'card_expiry', FILTER_SANITIZE_STRING);
    $card_cvv = filter_input(INPUT_POST, 'card_cvv', FILTER_SANITIZE_STRING);

    if (!$card_number || !preg_match('/^\d{16}$/', $card_number)) {
        $_SESSION['error'] = 'Invalid card number';
        header('Location: orders.php');
        exit;
    }

    if (!$card_expiry || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $card_expiry)) {
        $_SESSION['error'] = 'Invalid expiry date';
        header('Location: orders.php');
        exit;
    }

    if (!$card_cvv || !preg_match('/^\d{3,4}$/', $card_cvv)) {
        $_SESSION['error'] = 'Invalid CVV';
        header('Location: orders.php');
        exit;
    }

    // In a real application, you would process the card payment here
    // For security, never store actual card details in the database
    $card_details = [
        'last4' => substr($card_number, -4),
        'expiry' => $card_expiry
    ];
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * (isset($item['quantity']) ? $item['quantity'] : 1);
    }

    // Get user ID from session (assuming user is logged in)
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User not logged in');
    }

    // Insert order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, pickup_time, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $total,
        $pickup_datetime->format('Y-m-d H:i:s'),
        $payment_method
    ]);
    
    $order_id = $conn->lastInsertId();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_name, price, quantity)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([
            $order_id,
            $item['name'],
            $item['price'],
            isset($item['quantity']) ? $item['quantity'] : 1
        ]);
    }

    // If card payment, store masked card details
    if ($payment_method === 'card' && isset($card_details)) {
        $stmt = $conn->prepare("
            INSERT INTO payment_details (order_id, payment_method, card_last4, card_expiry)
            VALUES (?, 'card', ?, ?)
        ");
        $stmt->execute([$order_id, $card_details['last4'], $card_details['expiry']]);
    }

    // Commit transaction
    $conn->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    // Set success message
    $_SESSION['success'] = 'Order placed successfully! Your order number is #' . $order_id;

    // Redirect to order confirmation page
    header('Location: order_confirmation.php?id=' . $order_id);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log error (in a real application, you would log this properly)
    error_log('Order processing error: ' . $e->getMessage());

    $_SESSION['error'] = 'An error occurred while processing your order. Please try again.';
    header('Location: orders.php');
    exit;
} 