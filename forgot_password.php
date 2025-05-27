<?php
// forgot_password.php
session_start();
require 'db_connection.php'; // Database connection

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize errors array
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid security token';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $errors[] = 'Please enter a valid email address';
        } else {
            try {
                // Check if email exists and is verified
                $stmt = $conn->prepare("
                    SELECT u.id, u.email, u.full_name, ev.verified 
                    FROM users u 
                    LEFT JOIN email_verifications ev ON u.id = ev.user_id 
                    WHERE u.email = ? AND ev.verified = 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Generate reset token
                    $reset_token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store reset token
                    $stmt = $conn->prepare("
                        INSERT INTO password_resets (user_id, token, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user['id'], $reset_token, $expires]);

                    // Send reset email
                    $reset_link = "http://{$_SERVER['HTTP_HOST']}/reset_password.php?token=" . $reset_token;
                    $to = $user['email'];
                    $subject = "MangaBrew Cafe - Password Reset Request";
                    $message = "Hello {$user['full_name']},\n\n";
                    $message .= "We received a request to reset your password. Click the link below to reset your password:\n\n";
                    $message .= $reset_link . "\n\n";
                    $message .= "This link will expire in 1 hour.\n\n";
                    $message .= "If you didn't request this, please ignore this email.\n\n";
                    $message .= "Best regards,\nMangaBrew Cafe Team";
                    $headers = "From: noreply@mangabrewcafe.com";

                    if (mail($to, $subject, $message, $headers)) {
                        $success = true;
                    } else {
                        $errors[] = 'Failed to send reset email. Please try again later.';
                    }
                } else {
                    // Don't reveal if email exists or not
                    $success = true;
                }
            } catch (PDOException $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $errors[] = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - MangaBrew Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ffc107;
            --secondary-color: #2c3e50;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control {
            border-radius: 10px;
            padding: 0.8rem 1rem;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }

        .btn {
            border-radius: 10px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .back-to-login {
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-to-login:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4">
                <h1 class="text-center mb-4">
                    <i class="fas fa-key text-warning"></i> Forgot Password
                </h1>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> If your email is registered and verified, 
                        you will receive password reset instructions shortly.
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="forgot_password.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" name="email" class="form-control" 
                               id="email" required>
                        <div class="form-text">
                            Enter the email address you used to register. We'll send you a link to reset your password.
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="index.php" class="back-to-login">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
</body>
</html>
