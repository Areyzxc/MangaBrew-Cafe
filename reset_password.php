<?php
// reset_password.php
session_start();
require 'db_connection.php'; // Database connection

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$errors = [];
$token_valid = false;
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (!$token) {
    $_SESSION['error'] = 'Invalid reset link';
    header('Location: forgot_password.php');
    exit();
}

// Verify token
try {
    $stmt = $conn->prepare("
        SELECT pr.*, u.email, u.full_name 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? 
        AND pr.used = 0
        AND pr.expires_at > CURRENT_TIMESTAMP
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset) {
        $token_valid = true;
    } else {
        $_SESSION['error'] = 'Invalid or expired reset link';
        header('Location: forgot_password.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Password reset verification error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again later.';
    header('Location: forgot_password.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid security token';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate password
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain both letters and numbers';
        }

        // Validate password confirmation
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        if (empty($errors)) {
            try {
                // Start transaction
                $conn->beginTransaction();

                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET password = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$hashed_password, $reset['user_id']]);

                // Mark reset token as used
                $stmt = $conn->prepare("
                    UPDATE password_resets 
                    SET used = 1, 
                        used_at = CURRENT_TIMESTAMP 
                    WHERE token = ?
                ");
                $stmt->execute([$token]);

                // Commit transaction
                $conn->commit();

                // Send confirmation email
                $to = $reset['email'];
                $subject = "MangaBrew Cafe - Password Reset Successful";
                $message = "Hello {$reset['full_name']},\n\n";
                $message .= "Your password has been successfully reset.\n\n";
                $message .= "If you did not make this change, please contact us immediately.\n\n";
                $message .= "Best regards,\nMangaBrew Cafe Team";
                $headers = "From: noreply@mangabrewcafe.com";

                mail($to, $subject, $message, $headers);

                // Set success message and redirect
                $_SESSION['success_message'] = 'Password reset successful! You can now log in with your new password.';
                header('Location: index.php');
                exit();

            } catch (Exception $e) {
                // Rollback transaction on error
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
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
    <title>Reset Password - MangaBrew Cafe</title>
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

        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .password-requirements li.valid {
            color: #28a745;
        }

        .password-requirements li.invalid {
            color: #dc3545;
        }

        .password-requirements li i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4">
                <h1 class="text-center mb-4">
                    <i class="fas fa-key text-warning"></i> Reset Password
                </h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" 
                      method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" 
                                   id="password" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength"></div>
                        <div class="password-requirements">
                            <ul>
                                <li id="length"><i class="fas fa-times"></i> At least 8 characters</li>
                                <li id="letter"><i class="fas fa-times"></i> Contains a letter</li>
                                <li id="number"><i class="fas fa-times"></i> Contains a number</li>
                            </ul>
                        </div>
                        <div class="invalid-feedback">Please enter a valid password</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" 
                                   id="confirm_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Passwords do not match</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Reset Password
                        </button>
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

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Password strength meter
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthMeter = document.querySelector('.password-strength');
    const requirements = {
        length: document.getElementById('length'),
        letter: document.getElementById('letter'),
        number: document.getElementById('number')
    };

    password.addEventListener('input', function() {
        const value = this.value;
        const length = value.length >= 8;
        const letter = /[A-Za-z]/.test(value);
        const number = /\d/.test(value);
        const strength = (length + letter + number) / 3;

        // Update requirements
        requirements.length.classList.toggle('valid', length);
        requirements.letter.classList.toggle('valid', letter);
        requirements.number.classList.toggle('valid', number);

        // Update icons
        requirements.length.querySelector('i').className = length ? 'fas fa-check' : 'fas fa-times';
        requirements.letter.querySelector('i').className = letter ? 'fas fa-check' : 'fas fa-times';
        requirements.number.querySelector('i').className = number ? 'fas fa-check' : 'fas fa-times';

        // Update strength meter
        strengthMeter.style.width = (strength * 100) + '%';
        strengthMeter.style.backgroundColor = strength < 0.3 ? '#dc3545' : 
                                            strength < 0.7 ? '#ffc107' : '#28a745';
    });

    // Password confirmation validation
    confirmPassword.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
</body>
</html>
