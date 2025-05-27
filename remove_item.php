<?php
session_start();
require 'db_connection.php';

// Set JSON response header
header('Content-Type: application/json');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// Validate input
$index = filter_input(INPUT_POST, 'remove_index', FILTER_VALIDATE_INT);
if ($index === false || $index === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item index'
    ]);
    exit;
}

// Verify cart exists and index is valid
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || !isset($_SESSION['cart'][$index])) {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found in cart'
    ]);
    exit;
}

// Remove the item
unset($_SESSION['cart'][$index]);
// Reindex array to prevent gaps
$_SESSION['cart'] = array_values($_SESSION['cart']);

// Calculate new total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * (isset($item['quantity']) ? $item['quantity'] : 1);
}

// Return success response
echo json_encode([
    'success' => true,
    'total' => $total,
    'totalItems' => count($_SESSION['cart']),
    'message' => 'Item removed successfully'
]);
