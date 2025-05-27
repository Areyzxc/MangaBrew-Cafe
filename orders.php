<?php
// order.php
session_start();
require 'db_connection.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate and sanitize cart data
$cart = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

// Function to validate pickup time
function validatePickupTime($time) {
    $currentTime = new DateTime();
    $pickupTime = new DateTime($time);
    $minTime = (clone $currentTime)->modify('+30 minutes');
    $maxTime = (clone $currentTime)->modify('+24 hours');
    
    return $pickupTime >= $minTime && $pickupTime <= $maxTime;
}

// Handle AJAX quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $index = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    
    if ($index !== false && $quantity !== false && $quantity > 0 && isset($cart[$index])) {
        // Here you would typically check stock availability
        // For now, we'll just update the quantity
        $cart[$index]['quantity'] = $quantity;
        $_SESSION['cart'] = $cart;
        
        // Calculate new subtotal
        $subtotal = $cart[$index]['price'] * $quantity;
        $total = array_reduce($cart, function($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);
        
        echo json_encode([
            'success' => true,
            'subtotal' => $subtotal,
            'total' => $total,
            'totalItems' => count($cart)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Order | MangaBrew Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sticky-summary {
            position: sticky;
            top: 80px;
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
                <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link active" href="order.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4 text-center">🛒 My Order</h2>

    <div class="row">
        <!-- Cart Table -->
        <div class="col-md-8">
            <table class="table table-bordered bg-white shadow-sm">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Item</th>
                        <th style="width: 120px;">Price (₱)</th>
                        <th style="width: 130px;">Quantity</th>
                        <th style="width: 120px;">Subtotal (₱)</th>
                        <th style="width: 80px;">Remove</th>
                    </tr>
                </thead>
                <tbody id="cartTable">
                    <?php foreach ($cart as $index => $item): ?>
                    <tr data-index="<?php echo $index; ?>">
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['price']); ?></td>
                    <td class="text-center">
                        <input type="number" 
                               class="form-control quantity-input" 
                               min="1" 
                               max="99"
                               data-index="<?php echo $index; ?>"
                               value="<?php echo isset($item['quantity']) ? htmlspecialchars($item['quantity']) : 1; ?>">
                    </td>
                    <td class="text-center subtotal">
                        <?php 
                        $price = (int) $item['price']; 
                        $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1; 
                        echo $price * $quantity; 
                        ?>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-item" data-index="<?php echo $index; ?>">×</button>
                    </td>
                    </tr>
    <?php endforeach; ?>
</tbody>

            </table>
        </div>

        <!-- Sticky Summary Card -->
        <div class="col-md-4">
            <div class="card sticky-summary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <hr>
                    <p>Total Items: <span id="totalItems"><?php echo count($cart); ?></span></p>
                    <h4>Total Cost: <span id="totalCost">0.00</span></h4>
                    <hr>

                    <!-- Checkout Form -->
                    <form action="process_order.php" method="POST" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Pickup Time</label>
                            <input type="datetime-local" 
                                   name="pickup_time" 
                                   class="form-control" 
                                   min="<?php echo date('Y-m-d\TH:i', strtotime('+30 minutes')); ?>"
                                   max="<?php echo date('Y-m-d\TH:i', strtotime('+24 hours')); ?>"
                                   required>
                            <div class="form-text">Please select a time between 30 minutes and 24 hours from now.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div id="cardDetails" class="mb-3 d-none">
                            <div class="mb-2">
                                <label class="form-label">Card Number</label>
                                <input type="text" name="card_number" class="form-control" pattern="[0-9]{16}" maxlength="16">
                            </div>
                            <div class="row">
                                <div class="col">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" maxlength="5">
                                </div>
                                <div class="col">
                                    <label class="form-label">CVV</label>
                                    <input type="text" name="card_cvv" class="form-control" pattern="[0-9]{3,4}" maxlength="4">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100" id="confirmOrderBtn" disabled>Confirm Order</button>
                    </form>

                    <!-- Clear Cart Form -->
                    <form action="clear_cart.php" method="POST" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn btn-outline-danger w-100">Clear Cart</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// CSRF token for AJAX requests
const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

// Format currency
function formatCurrency(amount) {
    return '₱' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Calculate and update total cost and subtotals
function updateTotals() {
    let total = 0;
    let totalItems = 0;
    document.querySelectorAll('#cartTable tr').forEach(row => {
        const price = parseFloat(row.querySelector('td:nth-child(2)').textContent);
        const qtyInput = row.querySelector('.quantity-input');
        const quantity = parseInt(qtyInput.value);
        const subtotal = price * quantity;
        row.querySelector('.subtotal').textContent = formatCurrency(subtotal);
        total += subtotal;
        totalItems++;
    });
    document.getElementById('totalCost').textContent = formatCurrency(total);
    document.getElementById('totalItems').textContent = totalItems;
    // Disable confirm order if cart is empty
    document.getElementById('confirmOrderBtn').disabled = (totalItems === 0);
}

// Update cart item quantity via AJAX with stock validation
async function updateQuantity(index, quantity) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_quantity',
                csrf_token: csrfToken,
                index: index,
                quantity: quantity
            })
        });
        const data = await response.json();
        if (data.success) {
            updateTotals();
        } else {
            alert('Error updating quantity: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating quantity. Please try again.');
    }
}

// Remove item from cart
async function removeItem(index) {
    if (!confirm('Are you sure you want to remove this item?')) return;
    try {
        const response = await fetch('remove_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                csrf_token: csrfToken,
                remove_index: index
            })
        });
        const data = await response.json();
        if (data.success) {
            const row = document.querySelector(`tr[data-index="${index}"]`);
            row.remove();
            updateTotals();
        } else {
            alert('Error removing item: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error removing item. Please try again.');
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Quantity input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', () => {
            const quantity = parseInt(input.value);
            const index = input.dataset.index;
            
            if (quantity < 1) input.value = 1;
            if (quantity > 99) input.value = 99;
            
            updateQuantity(index, input.value);
        });
    });

    // Remove item buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = e.target.dataset.index;
            removeItem(index);
        });
    });

    // Payment method change
    const paymentMethod = document.querySelector('select[name="payment_method"]');
    const cardDetails = document.getElementById('cardDetails');
    
    paymentMethod.addEventListener('change', (e) => {
        if (e.target.value === 'card') {
            cardDetails.classList.remove('d-none');
            cardDetails.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            cardDetails.classList.add('d-none');
            cardDetails.querySelectorAll('input').forEach(input => input.required = false);
        }
    });

    // On page load, update totals
    updateTotals();

    // Form validation
    const checkoutForm = document.getElementById('checkoutForm');
    checkoutForm.addEventListener('submit', (e) => {
        const pickupTime = new Date(document.querySelector('input[name="pickup_time"]').value);
        const currentTime = new Date();
        const minTime = new Date(currentTime.getTime() + 30 * 60000); // 30 minutes
        const maxTime = new Date(currentTime.getTime() + 24 * 60 * 60000); // 24 hours

        if (pickupTime < minTime || pickupTime > maxTime) {
            e.preventDefault();
            alert('Please select a pickup time between 30 minutes and 24 hours from now.');
            return;
        }

        if (paymentMethod.value === 'card') {
            const cardNumber = document.querySelector('input[name="card_number"]').value;
            const cardExpiry = document.querySelector('input[name="card_expiry"]').value;
            const cardCvv = document.querySelector('input[name="card_cvv"]').value;

            if (!/^\d{16}$/.test(cardNumber)) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number.');
                return;
            }

            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry)) {
                e.preventDefault();
                alert('Please enter a valid expiry date (MM/YY).');
                return;
            }

            if (!/^\d{3,4}$/.test(cardCvv)) {
                e.preventDefault();
                alert('Please enter a valid CVV (3 or 4 digits).');
                return;
            }
        }

        setTimeout(() => {
            alert('Order confirmed! Thank you for your purchase.');
        }, 100);
    });

    // Show feedback after confirming or clearing cart
    document.querySelector('form[action="clear_cart.php"]').addEventListener('submit', function(e) {
        setTimeout(() => {
            alert('Cart cleared.');
        }, 100);
    });
});
</script>

</body>
</html>

