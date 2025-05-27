<?php
session_start();
require 'db_connection.php';

// Get token from URL
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!$token) {
    $_SESSION['error'] = 'Invalid verification link';
    header('Location: index.php');
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Find verification token
    $stmt = $conn->prepare("
        SELECT ev.*, u.email, u.full_name 
        FROM email_verifications ev
        JOIN users u ON ev.user_id = u.id
        WHERE ev.token = ? 
        AND ev.verified = 0
        AND ev.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$token]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        throw new Exception('Invalid or expired verification link');
    }

    // Mark email as verified
    $stmt = $conn->prepare("
        UPDATE email_verifications 
        SET verified = 1, 
            verified_at = CURRENT_TIMESTAMP 
        WHERE token = ?
    ");
    $stmt->execute([$token]);

    // Commit transaction
    $conn->commit();

    // Send welcome email
    $to = $verification['email'];
    $subject = "Welcome to MangaBrew Cafe!";
    $message = "Hello {$verification['full_name']},\n\n";
    $message .= "Thank you for verifying your email address. Your account is now fully activated!\n\n";
    $message .= "You can now log in and start enjoying our services:\n";
    $message .= "- Browse our menu and place orders\n";
    $message .= "- Access our manga library\n";
    $message .= "- Earn rewards points\n\n";
    $message .= "Best regards,\nMangaBrew Cafe Team";
    $headers = "From: noreply@mangabrewcafe.com";

    mail($to, $subject, $message, $headers);

    $_SESSION['success_message'] = 'Email verified successfully! You can now log in.';
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log('Email verification error: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: index.php');
exit();
?> 