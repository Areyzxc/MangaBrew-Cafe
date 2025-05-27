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

// Clear the cart
$_SESSION['cart'] = [];

// Set success message
$_SESSION['success'] = 'Cart cleared successfully';

// Redirect back to orders page
header('Location: orders.php');
exit;
?>
