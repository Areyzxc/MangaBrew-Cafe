<?php
session_start();
require 'db_connection.php';
require 'social_auth.php';

// Check if provider is specified
if (!isset($_GET['provider'])) {
    header('Location: index.php?error=invalid_provider');
    exit;
}

$provider = $_GET['provider'];
$auth = new SocialAuth($conn);

try {
    // Get the authorization URL for the selected provider
    $authUrl = $auth->getAuthUrl($provider);
    
    // Store the provider in session for callback
    $_SESSION['oauth_provider'] = $provider;
    
    // Redirect to the provider's login page
    header('Location: ' . $authUrl);
    exit;
    
} catch (Exception $e) {
    error_log('Social auth initiation error: ' . $e->getMessage());
    header('Location: index.php?error=social_auth_failed');
    exit;
}
?> 