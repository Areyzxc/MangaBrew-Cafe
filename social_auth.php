<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_connection.php';

// Check if vendor/autoload.php exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Please run "composer install" to install required dependencies.');
}

require __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file if it exists
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use Abraham\TwitterOAuth\TwitterOAuth;

class SocialAuth {
    private $conn;
    private $providers = [];

    public function __construct($conn) {
        $this->conn = $conn;
        
        // Initialize OAuth providers with environment variables
        $this->providers['google'] = new Google([
            'clientId'     => getenv('GOOGLE_CLIENT_ID'),
            'clientSecret' => getenv('GOOGLE_CLIENT_SECRET'),
            'redirectUri'  => 'http://' . $_SERVER['HTTP_HOST'] . '/social_callback.php?provider=google',
        ]);

        $this->providers['facebook'] = new Facebook([
            'clientId'     => getenv('FACEBOOK_CLIENT_ID'),
            'clientSecret' => getenv('FACEBOOK_CLIENT_SECRET'),
            'redirectUri'  => 'http://' . $_SERVER['HTTP_HOST'] . '/social_callback.php?provider=facebook',
            'graphApiVersion' => 'v12.0',
        ]);

        // Twitter OAuth 1.0a
        $this->providers['twitter'] = [
            'consumer_key'    => getenv('TWITTER_API_KEY'),
            'consumer_secret' => getenv('TWITTER_API_SECRET'),
            'callback_url'    => 'http://' . $_SERVER['HTTP_HOST'] . '/social_callback.php?provider=twitter'
        ];
    }

    public function getAuthUrl($provider) {
        switch ($provider) {
            case 'google':
            case 'facebook':
                return $this->providers[$provider]->getAuthorizationUrl([
                    'scope' => ['email', 'profile']
                ]);

            case 'twitter':
                $connection = new TwitterOAuth(
                    $this->providers['twitter']['consumer_key'],
                    $this->providers['twitter']['consumer_secret']
                );
                $request_token = $connection->oauth('oauth/request_token', [
                    'oauth_callback' => $this->providers['twitter']['callback_url']
                ]);
                $_SESSION['oauth_token'] = $request_token['oauth_token'];
                $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
                return $connection->url('oauth/authorize', ['oauth_token' => $request_token['oauth_token']]);

            default:
                throw new Exception('Invalid provider');
        }
    }

    public function handleCallback($provider, $code) {
        try {
            switch ($provider) {
                case 'google':
                    $token = $this->providers['google']->getAccessToken('authorization_code', [
                        'code' => $code
                    ]);
                    $user = $this->providers['google']->getResourceOwner($token);
                    $email = $user->getEmail();
                    $name = $user->getName();
                    $social_id = $user->getId();
                    break;

                case 'facebook':
                    $token = $this->providers['facebook']->getAccessToken('authorization_code', [
                        'code' => $code
                    ]);
                    $user = $this->providers['facebook']->getResourceOwner($token);
                    $email = $user->getEmail();
                    $name = $user->getName();
                    $social_id = $user->getId();
                    break;

                case 'twitter':
                    if (!isset($_GET['oauth_verifier'])) {
                        throw new Exception('Invalid Twitter callback');
                    }
                    $connection = new TwitterOAuth(
                        $this->providers['twitter']['consumer_key'],
                        $this->providers['twitter']['consumer_secret'],
                        $_SESSION['oauth_token'],
                        $_SESSION['oauth_token_secret']
                    );
                    $token = $connection->oauth('oauth/access_token', [
                        'oauth_verifier' => $_GET['oauth_verifier']
                    ]);
                    $connection = new TwitterOAuth(
                        $this->providers['twitter']['consumer_key'],
                        $this->providers['twitter']['consumer_secret'],
                        $token['oauth_token'],
                        $token['oauth_token_secret']
                    );
                    $user = $connection->get('account/verify_credentials', ['include_email' => true]);
                    $email = $user->email;
                    $name = $user->name;
                    $social_id = $user->id_str;
                    break;

                default:
                    throw new Exception('Invalid provider');
            }

            // Check if user exists
            $stmt = $this->conn->prepare("
                SELECT u.*, s.social_id 
                FROM users u 
                LEFT JOIN social_logins s ON u.id = s.user_id AND s.provider = ?
                WHERE u.email = ? OR s.social_id = ?
            ");
            $stmt->execute([$provider, $email, $social_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update social login if needed
                if (!$user['social_id']) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO social_logins (user_id, provider, social_id) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user['id'], $provider, $social_id]);
                }

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Update last login
                $stmt = $this->conn->prepare("
                    UPDATE users 
                    SET last_login = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                return true;
            } else {
                // Create new user
                $this->conn->beginTransaction();

                try {
                    // Generate username from email
                    $username = explode('@', $email)[0];
                    $base_username = $username;
                    $counter = 1;

                    // Ensure unique username
                    while (true) {
                        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        if (!$stmt->fetch()) break;
                        $username = $base_username . $counter++;
                    }

                    // Insert user
                    $stmt = $this->conn->prepare("
                        INSERT INTO users (
                            username, 
                            email, 
                            full_name, 
                            password, 
                            points,
                            created_at
                        ) VALUES (?, ?, ?, '', 0, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$username, $email, $name]);
                    $user_id = $this->conn->lastInsertId();

                    // Insert social login
                    $stmt = $this->conn->prepare("
                        INSERT INTO social_logins (user_id, provider, social_id) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $provider, $social_id]);

                    // Mark email as verified
                    $stmt = $this->conn->prepare("
                        INSERT INTO email_verifications (user_id, verified, verified_at) 
                        VALUES (?, 1, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$user_id]);

                    $this->conn->commit();

                    // Set session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $name;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    return true;

                } catch (Exception $e) {
                    $this->conn->rollBack();
                    throw $e;
                }
            }
        } catch (Exception $e) {
            error_log('Social login error: ' . $e->getMessage());
            return false;
        }
    }
}

// Create social_logins table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS social_logins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider VARCHAR(20) NOT NULL,
    social_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_social_login (provider, social_id),
    INDEX idx_user_provider (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $conn->exec($sql);
} catch (PDOException $e) {
    error_log('Error creating social_logins table: ' . $e->getMessage());
}
?> 