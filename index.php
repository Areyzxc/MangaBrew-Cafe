<?php
session_start();
require 'db_connection.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve errors or messages
$login_errors = $_SESSION['login_errors'] ?? [];
$signup_errors = $_SESSION['signup_errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';

unset($_SESSION['login_errors'], $_SESSION['signup_errors'], $_SESSION['success_message']);

// Pre-fill remembered username
$remembered_username = $_COOKIE['remember_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MangaBrew Cafe - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ffc107;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
        }

        body, html { 
            height: 100%; 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .split-left {
            background: url('images/cafe-bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            overflow: hidden;
        }

        .split-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
        }

        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }

        .floating-element:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 40%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .form-switch label {
            cursor: pointer;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        .nav-tabs {
            border: none;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--secondary-color);
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            margin: 0 0.3rem;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(255, 193, 7, 0.1);
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
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

        .btn-success {
            background: var(--secondary-color);
            border: none;
        }

        .btn-success:hover {
            background: #1a252f;
            transform: translateY(-2px);
        }

        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .social-login {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-3px);
        }

        .google-btn { background: #DB4437; color: white; }
        .facebook-btn { background: #4267B2; color: white; }
        .twitter-btn { background: #1DA1F2; color: white; }

        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
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
<body class="bg-light">
<div class="container-fluid h-100">
    <div class="row h-100">
        <!-- Left Side (Art) -->
        <div class="col-md-6 split-left d-none d-md-block">
            <!-- Floating Elements -->
            <div class="floating-element">
                <img src="images/manga-float1.jpg" alt="Manga" style="width: 150px;">
            </div>
            <div class="floating-element">
                <img src="images/coffee-float.png" alt="Coffee" style="width: 120px;">
            </div>
            <div class="floating-element">
                <img src="images/manga-float2.jpg" alt="Manga" style="width: 130px;">
            </div>
            <div class="floating-element">
                <img src="images/croissant.jpg" alt="Manga" style="width: 130px;">
            </div>
        </div>

        <!-- Right Side (Forms) -->
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="card shadow-lg p-4 w-100" style="max-width: 450px;">
                <h1 class="text-center mb-3">
                    <i class="fas fa-coffee text-warning"></i> MangaBrew Cafe
                </h1>
                <p class="text-center text-muted mb-4">Welcome! Please log in or sign up to get started.</p>

                <!-- Flash Messages -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs justify-content-center" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="button" role="tab">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup-tab-pane" type="button" role="tab">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Login Form -->
                    <div class="tab-pane fade show active" id="login-tab-pane" role="tabpanel">
                        <?php if (!empty($login_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($login_errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="loginUser" class="form-label">
                                    <i class="fas fa-user"></i> Username or Email
                                </label>
                                <input type="text" name="email_or_username" class="form-control" 
                                       id="loginUser" value="<?= htmlspecialchars($remembered_username) ?>" required>
                                <div class="invalid-feedback">Please enter your username or email</div>
                            </div>

                            <div class="mb-3">
                                <label for="loginPass" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" name="login_password" class="form-control" 
                                           id="loginPass" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please enter your password</div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                <label class="form-check-label" for="rememberMe">Remember Me</label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="forgot_password.php" class="text-decoration-none small">
                                    <i class="fas fa-key"></i> Forgot password?
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Sign-up Form -->
                    <div class="tab-pane fade" id="signup-tab-pane" role="tabpanel">
                        <?php if (!empty($signup_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($signup_errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="signup.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label for="signupName" class="form-label">
                                    <i class="fas fa-user"></i> Full Name
                                </label>
                                <input type="text" name="full_name" class="form-control" 
                                       id="signupName" pattern="[A-Za-z\s]{2,50}" required>
                                <div class="invalid-feedback">Please enter a valid name (2-50 characters, letters only)</div>
                            </div>

                            <div class="mb-3">
                                <label for="signupUsername" class="form-label">
                                    <i class="fas fa-at"></i> Username
                                </label>
                                <input type="text" name="username" class="form-control" 
                                       id="signupUsername" pattern="[a-zA-Z0-9_]{3,20}" required>
                                <div class="invalid-feedback">Username must be 3-20 characters (letters, numbers, underscore)</div>
                            </div>

                            <div class="mb-3">
                                <label for="signupEmail" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" name="email" class="form-control" 
                                       id="signupEmail" required>
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>

                            <div class="mb-3">
                                <label for="signupPhone" class="form-label">
                                    <i class="fas fa-phone"></i> Phone Number (Optional)
                                </label>
                                <input type="tel" name="phone" class="form-control" 
                                       id="signupPhone" pattern="[0-9]{11}" placeholder="09XXXXXXXXX">
                                <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                            </div>

                            <div class="mb-3">
                                <label for="signupPass" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" 
                                           id="signupPass" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" required>
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
                            </div>

                            <div class="mb-3">
                                <label for="signupConfirm" class="form-label">
                                    <i class="fas fa-lock"></i> Confirm Password
                                </label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" class="form-control" 
                                           id="signupConfirm" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Passwords do not match</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus"></i> Sign Up
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Theme Toggle -->
                <div class="form-check form-switch mt-4 text-center">
                    <input class="form-check-input" type="checkbox" id="darkModeToggle">
                    <label class="form-check-label" for="darkModeToggle">
                        <i class="fas fa-moon"></i> Toggle Dark Mode
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
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
    const password = document.getElementById('signupPass');
    const confirmPassword = document.getElementById('signupConfirm');
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

    // Dark mode toggle
    const toggle = document.getElementById('darkModeToggle');
    const darkClass = ['bg-dark', 'text-light'];

    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add(...darkClass);
        toggle.checked = true;
    }

    toggle.addEventListener('change', function() {
        document.body.classList.toggle('bg-dark');
        document.body.classList.toggle('text-light');
        if (document.body.classList.contains('bg-dark')) {
            localStorage.setItem('darkMode', 'enabled');
        } else {
            localStorage.removeItem('darkMode');
        }
    });

    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(message => {
        setTimeout(() => {
            const alert = new bootstrap.Alert(message);
            alert.close();
        }, 5000);
    });
});
</script>
</body>
</html>
