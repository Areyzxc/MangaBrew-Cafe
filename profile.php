<?php
// profile.php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Fetch user data from database
    $stmt = $conn->prepare("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as total_orders,
               COUNT(DISTINCT m.id) as total_manga,
               COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        LEFT JOIN user_manga m ON u.id = m.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Fetch recent orders
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_orders = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch manga library
    $stmt = $conn->prepare("
        SELECT m.*, um.status, um.last_read
        FROM user_manga um
        JOIN manga m ON um.manga_id = m.id
        WHERE um.user_id = ?
        ORDER BY um.last_read DESC
        LIMIT 5
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $manga_library = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log('Profile error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error loading profile data. Please try again.';
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: profile.php');
        exit;
    }

    try {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Please upload JPG, PNG, or GIF');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('File too large. Maximum size is 5MB');
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/avatars/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_') . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Error saving file');
        }

        // Update database
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param('si', $filename, $_SESSION['user_id']);
        $stmt->execute();

        $_SESSION['success'] = 'Profile picture updated successfully';
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | MangaBrew Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f5f1; }
        .profile-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ffc107;
            cursor: pointer;
            transition: filter 0.3s ease;
        }
        .profile-avatar:hover {
            filter: brightness(0.8);
        }
        .avatar-upload {
            position: relative;
            display: inline-block;
        }
        .avatar-upload input[type="file"] {
            display: none;
        }
        .avatar-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .avatar-upload:hover .avatar-overlay {
            opacity: 1;
        }
        .profile-stats {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .profile-stats .stat-item {
            text-align: center;
            padding: 10px;
        }
        .profile-stats .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .profile-stats .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .tab-content {
            background: #fff;
            border-radius: 0 0 10px 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #2c3e50;
            font-weight: bold;
        }
        .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        .order-item {
            border-left: 4px solid #ffc107;
        }
        .manga-item {
            border-left: 4px solid #28a745;
        }
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-warning shadow-sm">
    <div class="container">
        <a class="navbar-brand text-dark" href="dashboard.php">
            <i class="bi bi-cup-hot"></i> MangaBrew Cafe
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="flash-message alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']); 
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="flash-message alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']); 
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="container my-4">
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="avatar-upload">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="upload_avatar">
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" onchange="this.form.submit()">
                        <label for="avatarInput">
                            <img src="<?php echo !empty($user['avatar']) ? 'uploads/avatars/' . htmlspecialchars($user['avatar']) : 'images/default-avatar.jpg'; ?>" 
                                 alt="Profile Avatar" 
                                 class="profile-avatar mb-3">
                            <div class="avatar-overlay">
                                <i class="bi bi-camera-fill"></i>
                                <div>Change Photo</div>
                            </div>
                        </label>
                    </form>
                    
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="small text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>

                    <!-- Profile Stats -->
                    <div class="profile-stats mt-3">
                        <div class="row">
                            <div class="col-4 stat-item">
                                <div class="stat-value"><?php echo $user['total_orders']; ?></div>
                                <div class="stat-label">Orders</div>
                            </div>
                            <div class="col-4 stat-item">
                                <div class="stat-value"><?php echo $user['total_manga']; ?></div>
                                <div class="stat-label">Manga</div>
                            </div>
                            <div class="col-4 stat-item">
                                <div class="stat-value"><?php echo number_format($user['points']); ?></div>
                                <div class="stat-label">Points</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs nav-justified" id="profileTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">
                                <i class="bi bi-person"></i> Profile
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
                                <i class="bi bi-shield-lock"></i> Security
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                                <i class="bi bi-cart"></i> Orders
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="library-tab" data-bs-toggle="tab" data-bs-target="#library" type="button">
                                <i class="bi bi-book"></i> Library
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="rewards-tab" data-bs-toggle="tab" data-bs-target="#rewards" type="button">
                                <i class="bi bi-gift"></i> Rewards
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-4" id="profileTabsContent">
                        <!-- Profile Info -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <form action="update_profile.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                           pattern="[A-Za-z\s]{2,50}" required>
                                    <div class="invalid-feedback">Please enter a valid name (2-50 characters, letters only)</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           required>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                           pattern="[0-9]{11}" placeholder="09XXXXXXXXX">
                                    <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <form action="change_password.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" class="form-control" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" class="form-control" 
                                               pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Password must be at least 8 characters long and include both letters and numbers
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" class="form-control" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-lock"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <!-- Order History -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <?php if (empty($recent_orders)): ?>
                                <p class="text-muted text-center">No orders yet</p>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="card mb-3 order-item">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Order #<?php echo $order['id']; ?></h6>
                                                    <p class="mb-0 text-muted small">
                                                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?> • 
                                                        <?php echo $order['item_count']; ?> items
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <h6 class="mb-1">₱<?php echo number_format($order['total_amount'], 2); ?></h6>
                                                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <a href="orders.php" class="btn btn-outline-primary w-100">View All Orders</a>
                            <?php endif; ?>
                        </div>

                        <!-- Manga Library -->
                        <div class="tab-pane fade" id="library" role="tabpanel">
                            <?php if (empty($manga_library)): ?>
                                <p class="text-muted text-center">No manga in your library yet</p>
                            <?php else: ?>
                                <?php foreach ($manga_library as $manga): ?>
                                    <div class="card mb-3 manga-item">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($manga['title']); ?></h6>
                                                    <p class="mb-0 text-muted small">
                                                        Volume <?php echo $manga['volume']; ?> • 
                                                        Last read: <?php echo date('M j, Y', strtotime($manga['last_read'])); ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-<?php echo $manga['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($manga['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <a href="library.php" class="btn btn-outline-primary w-100">View Full Library</a>
                            <?php endif; ?>
                        </div>

                        <!-- Rewards -->
                        <div class="tab-pane fade" id="rewards" role="tabpanel">
                            <div class="text-center mb-4">
                                <h3 class="display-4"><?php echo number_format($user['points']); ?></h3>
                                <p class="text-muted">Total Points</p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-cup-hot display-4 text-warning"></i>
                                            <h5 class="mt-3">Free Coffee</h5>
                                            <p class="text-muted">Redeem 100 points</p>
                                            <button class="btn btn-outline-warning" <?php echo $user['points'] < 100 ? 'disabled' : ''; ?>>
                                                Redeem
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-book display-4 text-success"></i>
                                            <h5 class="mt-3">Manga Rental</h5>
                                            <p class="text-muted">Redeem 200 points</p>
                                            <button class="btn btn-outline-success" <?php echo $user['points'] < 200 ? 'disabled' : ''; ?>>
                                                Redeem
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });

    // Password confirmation validation
    const passwordForm = document.querySelector('#password form');
    if (passwordForm) {
        const newPassword = passwordForm.querySelector('input[name="new_password"]');
        const confirmPassword = passwordForm.querySelector('input[name="confirm_password"]');

        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
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
