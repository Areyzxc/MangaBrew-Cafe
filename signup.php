<?php
// signup.php
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
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

    // Basic validation
    if (!$full_name || strlen($full_name) < 2 || strlen($full_name) > 50) {
        $errors[] = 'Full name must be between 2 and 50 characters';
    }

    if (!$username || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore)';
    }

    if (!$email) {
        $errors[] = 'Please enter a valid email address';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain both letters and numbers';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if ($phone && !preg_match('/^[0-9]{11}$/', $phone)) {
        $errors[] = 'Phone number must be 11 digits';
    }

    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = 'Username or email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user
                $insert_sql = "INSERT INTO users (username, email, full_name, password, phone, points, created_at) 
                              VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if (!$insert_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }

                $insert_stmt->bind_param("sssss", $username, $email, $full_name, $hashed_password, $phone);
                
                if ($insert_stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Set success message and redirect
                    $_SESSION['success_message'] = 'Registration successful! You can now log in.';
                    header('Location: index.php');
                    exit();
                } else {
                    throw new Exception("Error creating account: " . $insert_stmt->error);
                }
            }
        } catch (Exception $e) {
            error_log("Signup error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

// Store errors in session if any
if (!empty($errors)) {
    $_SESSION['signup_errors'] = $errors;
    header('Location: index.php');
    exit();
}
?>
