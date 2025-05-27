<?php
// login.php — Handles login logic securely and efficiently

session_start();
require 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize errors array
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $email_or_username = filter_input(INPUT_POST, 'email_or_username', FILTER_SANITIZE_STRING);
    $password = $_POST['login_password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Debug log
    error_log("Login attempt - Email/Username: " . $email_or_username);

    // Check for login attempts
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
        FROM login_attempts 
        WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = $result->fetch_assoc();

    if ($attempts['attempts'] >= 5) {
        $time_left = strtotime($attempts['last_attempt']) + 900 - time(); // 15 minutes in seconds
        $errors[] = "Too many login attempts. Please try again in " . ceil($time_left / 60) . " minutes.";
    } else {
        try {
            // Check if user exists (removed email verification check)
            $stmt = $conn->prepare("
                SELECT * FROM users 
                WHERE email = ? OR username = ?
            ");
            $stmt->bind_param("ss", $email_or_username, $email_or_username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Debug log
            error_log("User lookup result: " . ($user ? "Found" : "Not found"));

            if ($user && password_verify($password, $user['password'])) {
                // Debug log
                error_log("Password verification successful for user: " . $user['username']);

                // Clear login attempts
                $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                $stmt->bind_param("s", $ip_address);
                $stmt->execute();

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];

                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Store token in database
                    $stmt = $conn->prepare("
                        INSERT INTO remember_tokens (user_id, token, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->bind_param("iss", $user['id'], $token, $expires);
                    $stmt->execute();

                    // Set secure cookie
                    setcookie(
                        'remember_token',
                        $token,
                        strtotime('+30 days'),
                        '/',
                        '',
                        true, // Secure
                        true  // HttpOnly
                    );
                }

                // Update last login
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET last_login = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();

                // Debug log
                error_log("Login successful for user: " . $user['username']);

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();

            } else {
                // Debug log
                if ($user) {
                    error_log("Login failed - Password verification failed for user: " . $user['username']);
                } else {
                    error_log("Login failed - User not found: " . $email_or_username);
                }

                // Log failed attempt
                $stmt = $conn->prepare("
                    INSERT INTO login_attempts (ip_address, attempt_time) 
                    VALUES (?, CURRENT_TIMESTAMP)
                ");
                $stmt->bind_param("s", $ip_address);
                $stmt->execute();

                $errors[] = 'Invalid username/email or password';
            }

        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors[] = 'An error occurred. Please try again later.';
        }
    }
}

// Store errors in session if any
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header("Location: index.php#login-tab-pane");
    exit();
}
?>
