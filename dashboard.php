<?php
// dashboard.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user info from session
$username = htmlspecialchars($_SESSION['username']);
$full_name = htmlspecialchars($_SESSION['full_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | MangaBrew Cafe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f5f1;
        }
        .card-hover:hover {
            transform: scale(1.03);
            transition: all 0.2s ease-in-out;
        }
        .navbar-brand {
            font-family: 'Georgia', serif;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .carousel-item {
            height: 300px;
            background-size: cover;
            background-position: center;
        }
        .carousel-caption {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 10px;
            padding: 15px;
        }
        .reading-corner {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe4cc 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .quick-stats .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .quick-stats .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #6c757d;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-warning shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand text-dark" href="#">MangaBrew Cafe ☕📚</a>
        <div class="d-flex">
            <span class="navbar-text me-3 text-dark">
                Welcome, <strong><?= $full_name; ?></strong>!
            </span>
            <a href="logout.php" class="btn btn-outline-dark">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container my-5">

    <!-- Cafe Announcement -->
    <div class="alert alert-info shadow-sm text-center animate-fade-in" role="alert">
        <strong>Today's Brew Special ☕:</strong> Try our new Matcha Manga Latte and check out the latest arrivals in our manga library!
    </div>

    <!-- Featured Carousel -->
    <div id="featuredCarousel" class="carousel slide mb-4 shadow-lg rounded animate-fade-in" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner rounded">
            <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1543002588-bfa74002ed7e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="carousel-caption">
                    <h3>New Manga Arrivals</h3>
                    <p>Check out our latest collection of popular manga series!</p>
                    <a href="library.php" class="btn btn-light">Explore Now</a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="carousel-caption">
                    <h3>Special Menu Items</h3>
                    <p>Discover our manga-themed beverages and desserts!</p>
                    <a href="menu.php" class="btn btn-light">View Menu</a>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="carousel-caption">
                    <h3>Reading Events</h3>
                    <p>Join our weekly manga reading sessions!</p>
                    <a href="#" class="btn btn-light">Learn More</a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="row quick-stats mb-4 animate-fade-in">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-book text-primary fs-4"></i>
                <div class="stat-number">12</div>
                <div class="stat-label">Manga Read</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-cup-hot text-warning fs-4"></i>
                <div class="stat-number">8</div>
                <div class="stat-label">Orders Made</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-star text-success fs-4"></i>
                <div class="stat-number">4</div>
                <div class="stat-label">Reviews Given</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-calendar-check text-danger fs-4"></i>
                <div class="stat-number">3</div>
                <div class="stat-label">Events Attended</div>
            </div>
        </div>
    </div>

    <!-- Today's Reading Corner -->
    <div class="reading-corner mb-4 animate-fade-in">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <img src="images/manga-featured.png" alt="Featured Manga" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-9">
                <h4 class="mb-3">Today's Reading Corner 📚</h4>
                <h5 class="text-primary">Featured: "Sakamoto Days "</h5>
                <p class="mb-2">The story revolves around Taro Sakamoto, a retired legendary hitman who has settled into a quiet and mundane life as a family man. 
                    However, his peaceful life is disrupted when former enemies and colleagues from his hitman days come seeking revenge.</p>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary">Action</span>
                    <span class="badge bg-success">Comedy</span>
                    <span class="badge bg-info">Drama</span>
                </div>
                <button class="btn btn-outline-primary mt-3">Start Reading</button>
            </div>
        </div>
    </div>

    <!-- Quick Access Cards -->
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-book-half display-4 text-primary"></i>
                    <h5 class="card-title mt-3">Menu</h5>
                    <p class="card-text">Explore our café's offerings</p>
                    <a href="menu.php" class="btn btn-primary btn-sm">Go to Menu</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-collection display-4 text-success"></i>
                    <h5 class="card-title mt-3">Library</h5>
                    <p class="card-text">Browse our manga collection</p>
                    <a href="library.php" class="btn btn-success btn-sm">Browse Manga</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-receipt-cutoff display-4 text-warning"></i>
                    <h5 class="card-title mt-3">My Orders</h5>
                    <p class="card-text">Track your past & current orders</p>
                    <a href="orders.php" class="btn btn-warning btn-sm text-dark">View Orders</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-person-circle display-4 text-danger"></i>
                    <h5 class="card-title mt-3">Profile</h5>
                    <p class="card-text">Manage your account info</p>
                    <a href="profile.php" class="btn btn-danger btn-sm">View Profile</a>
                </div>
            </div>
        </div>

        <!-- New Customization Card -->
        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-sliders display-4 text-info"></i>
                    <h5 class="card-title mt-3">Customize</h5>
                    <p class="card-text">Personalize your cafe experience</p>
                    <a href="customize.php" class="btn btn-info btn-sm text-white">Customize Now</a>
                </div>
            </div>
        </div>

        <!-- New Reviews Card -->
        <div class="col-md-3">
            <div class="card card-hover shadow h-100">
                <div class="card-body">
                    <i class="bi bi-chat-square-text display-4 text-purple"></i>
                    <h5 class="card-title mt-3">Reviews</h5>
                    <p class="card-text">Read & share your experiences</p>
                    <a href="reviews.php" class="btn btn-purple btn-sm text-white" style="background-color: #6f42c1;">View Reviews</a>
                </div>
            </div>
        </div>
    </div>

</div>

<footer class="text-center text-muted py-4 small">
    &copy; 2025 MangaBrew Cafe. Brewed with passion & manga magic.
</footer>

<!-- Add Bootstrap JS and Popper.js before closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script>
    // Initialize tooltips and popovers
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to cards when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        });

        document.querySelectorAll('.card').forEach((card) => {
            observer.observe(card);
        });
    });
</script>
</body>
</html>
