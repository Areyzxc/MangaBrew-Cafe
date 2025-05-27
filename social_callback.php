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
    // Handle the OAuth callback
    $success = false;
    
    switch ($provider) {
        case 'google':
        case 'facebook':
            if (!isset($_GET['code'])) {
                throw new Exception('Authorization code not received');
            }
            $success = $auth->handleCallback($provider, $_GET['code']);
            break;

        case 'twitter':
            if (!isset($_GET['oauth_verifier'])) {
                throw new Exception('OAuth verifier not received');
            }
            $success = $auth->handleCallback($provider, null);
            break;

        default:
            throw new Exception('Invalid provider');
    }

    if ($success) {
        // Redirect to dashboard on success
        header('Location: dashboard.php?welcome=1');
        exit;
    } else {
        throw new Exception('Authentication failed');
    }

} catch (Exception $e) {
    error_log('Social callback error: ' . $e->getMessage());
    header('Location: index.php?error=social_auth_failed');
    exit;
}
?> 