<?php
require 'db_connection.php';

try {
    // Clear login attempts
    $stmt = $conn->prepare("TRUNCATE TABLE login_attempts");
    $stmt->execute();
    echo "Login attempts cleared successfully.";
} catch (Exception $e) {
    echo "Error clearing login attempts: " . $e->getMessage();
}
?> 