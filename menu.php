<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize cart array in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle "Add to Order" POST with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: menu.php');
        exit;
    }

    // Validate and sanitize input
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 99]
    ]);

    if (!$item_id || !$quantity) {
        $_SESSION['error'] = 'Invalid item or quantity';
        header('Location: menu.php');
        exit;
    }

    try {
        // Fetch item details from database
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM menu_items WHERE id = ? AND is_available = 1");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();

        if (!$item) {
            throw new Exception('Item not found or not available');
        }

        // Check stock availability
        if ($item['stock'] < $quantity) {
            throw new Exception('Not enough stock available');
        }

        // Check if item already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] === $item_id) {
                $new_quantity = $cart_item['quantity'] + $quantity;
                if ($new_quantity > $item['stock']) {
                    throw new Exception('Not enough stock available');
                }
                $cart_item['quantity'] = $new_quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $quantity
            ];
        }

        $_SESSION['success'] = "{$item['name']} added to your order!";
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect to prevent form resubmission
    header('Location: menu.php');
    exit;
}

// Fetch menu items from database with categories
try {
    $query = "
        SELECT m.*, c.name as category_name 
        FROM menu_items m 
        JOIN categories c ON m.category_id = c.id 
        WHERE m.is_available = 1 
        ORDER BY c.name, m.name
    ";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $menu_items = [];
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }

    // Get unique categories
    $categories = array_unique(array_column($menu_items, 'category_name'));
    sort($categories);

} catch (Exception $e) {
    error_log('Menu fetch error: ' . $e->getMessage());
    $menu_items = [];
    $categories = [];
    $_SESSION['error'] = 'Error loading menu. Please try again later.';
}

// Get user info for the navbar
$username = htmlspecialchars($_SESSION['username']);
$full_name = htmlspecialchars($_SESSION['full_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu | MangaBrew Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .menu-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .menu-card img { 
            height: 200px; 
            object-fit: cover; 
            border-bottom: 1px solid #ddd;
        }
        .card-title {
            font-weight: bold;
            color: #2c3e50;
        }
        .card-subtitle {
            color: #28a745;
            font-weight: bold;
        }
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
        }
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">MangaBrew Café</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (count($_SESSION['cart']) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo count($_SESSION['cart']); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <span class="nav-link">
                        <i class="fas fa-user"></i> <?php echo $username; ?>
                    </span>
                </li>
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

<div class="container py-5">
    <h2 class="mb-4 text-center">☕ Our Menu</h2>

    <!-- Category Filter -->
    <ul class="nav nav-pills justify-content-center mb-4" id="categoryTabs">
        <li class="nav-item"><a class="nav-link active" data-category="All" href="#">All</a></li>
        <?php foreach ($categories as $cat): ?>
            <li class="nav-item"><a class="nav-link" data-category="<?php echo htmlspecialchars($cat); ?>" href="#"><?php echo htmlspecialchars($cat); ?></a></li>
        <?php endforeach; ?>
    </ul>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="input-group w-50 mx-auto">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search menu items...">
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="row" id="menuGrid">
        <?php foreach ($menu_items as $item): ?>
            <div class="col-md-4 mb-4 menu-item" data-category="<?php echo htmlspecialchars($item['category_name']); ?>">
                <div class="card menu-card h-100">
                    <span class="badge bg-primary category-badge"><?php echo htmlspecialchars($item['category_name']); ?></span>
                    
                    <?php if ($item['stock'] <= 5): ?>
                        <span class="badge bg-warning stock-badge">Only <?php echo $item['stock']; ?> left!</span>
                    <?php endif; ?>
                    
                    <img src="menu/<?php echo htmlspecialchars($item['image']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         onerror="this.onerror=null; this.src='menu/default.jpg';">
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="card-text small text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                        <h6 class="card-subtitle mb-2">₱<?php echo number_format($item['price'], 2); ?></h6>

                        <div class="mt-auto">
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                                    <input type="number" name="quantity" class="form-control quantity-input" value="1" min="1" max="<?php echo $item['stock']; ?>" required>
                                    <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="increase">+</button>
                                </div>
                                
                                <button type="submit" class="btn btn-primary flex-grow-1" <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                    <?php echo $item['stock'] <= 0 ? 'Out of Stock' : 'Add to Order'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity button handlers
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const max = parseInt(input.max);
            
            if (this.dataset.action === 'increase' && currentValue < max) {
                input.value = currentValue + 1;
            } else if (this.dataset.action === 'decrease' && currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    });

    // Category filtering
    const categoryTabs = document.querySelectorAll('#categoryTabs a');
    const menuItems = document.querySelectorAll('.menu-item');
    const searchInput = document.getElementById('searchInput');

    categoryTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            categoryTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            filterItems();
        });
    });

    // Search filtering
    searchInput.addEventListener('input', filterItems);

    function filterItems() {
        const query = searchInput.value.toLowerCase();
        const activeCategory = document.querySelector('#categoryTabs a.active').dataset.category;

        menuItems.forEach(item => {
            const itemName = item.querySelector('.card-title').textContent.toLowerCase();
            const itemDesc = item.querySelector('.card-text').textContent.toLowerCase();
            const itemCategory = item.dataset.category;

            const matchesSearch = itemName.includes(query) || itemDesc.includes(query);
            const matchesCategory = activeCategory === 'All' || itemCategory === activeCategory;

            item.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
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
