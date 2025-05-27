<?php
// reviews.php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch user info from session
$username = htmlspecialchars($_SESSION['username']);
$full_name = htmlspecialchars($_SESSION['full_name']);
$user_id = $_SESSION['user_id'];

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'submit_review':
                // Validate input
                $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
                $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
                $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
                $categories = $_POST['categories'] ?? [];

                if (!$rating || !$title || !$content) {
                    throw new Exception('Please fill in all required fields.');
                }

                if ($rating < 1 || $rating > 5) {
                    throw new Exception('Invalid rating value.');
                }

                // Start transaction
                $conn->beginTransaction();

                // Insert review
                $stmt = $conn->prepare("
                    INSERT INTO reviews (user_id, rating, title, content, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param('iiss', $user_id, $rating, $title, $content);
                $stmt->execute();
                $review_id = $conn->insert_id;

                // Insert categories
                if (!empty($categories)) {
                    $stmt = $conn->prepare("
                        INSERT INTO review_category_mappings (review_id, category_id)
                        SELECT ?, category_id FROM review_categories WHERE name = ?
                    ");
                    foreach ($categories as $category) {
                        $stmt->bind_param('is', $review_id, $category);
                        $stmt->execute();
                    }
                }

                // Handle photo uploads
                if (!empty($_FILES['photos']['name'][0])) {
                    $upload_dir = 'uploads/reviews/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO review_photos (review_id, photo_url, display_order)
                        VALUES (?, ?, ?)
                    ");

                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_name = uniqid() . '_' . basename($_FILES['photos']['name'][$key]);
                            $file_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $stmt->bind_param('isi', $review_id, $file_path, $key);
                                $stmt->execute();
                            }
                        }
                    }
                }

                $conn->commit();
                $_SESSION['success'] = 'Your review has been submitted and is pending approval.';
                break;

            case 'submit_reply':
                $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
                $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
                $parent_reply_id = filter_input(INPUT_POST, 'parent_reply_id', FILTER_VALIDATE_INT);

                if (!$review_id || !$content) {
                    throw new Exception('Invalid reply data.');
                }

                $stmt = $conn->prepare("
                    INSERT INTO review_replies (review_id, user_id, parent_reply_id, content)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param('iiis', $review_id, $user_id, $parent_reply_id, $content);
                $stmt->execute();

                $_SESSION['success'] = 'Your reply has been posted.';
                break;

            case 'add_reaction':
                $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
                $emoji = filter_input(INPUT_POST, 'emoji', FILTER_SANITIZE_STRING);

                if (!$review_id || !$emoji) {
                    throw new Exception('Invalid reaction data.');
                }

                // Check if reaction already exists
                $stmt = $conn->prepare("
                    SELECT reaction_id FROM review_reactions
                    WHERE review_id = ? AND user_id = ? AND emoji = ?
                ");
                $stmt->bind_param('iis', $review_id, $user_id, $emoji);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Remove reaction
                    $stmt = $conn->prepare("
                        DELETE FROM review_reactions
                        WHERE review_id = ? AND user_id = ? AND emoji = ?
                    ");
                    $stmt->bind_param('iis', $review_id, $user_id, $emoji);
                    $stmt->execute();
                    echo json_encode(['status' => 'removed']);
                } else {
                    // Add reaction
                    $stmt = $conn->prepare("
                        INSERT INTO review_reactions (review_id, user_id, emoji)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->bind_param('iis', $review_id, $user_id, $emoji);
                    $stmt->execute();
                    echo json_encode(['status' => 'added']);
                }
                exit;

            case 'like_review':
                $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
                
                if (!$review_id) {
                    throw new Exception('Invalid review ID.');
                }

                $stmt = $conn->prepare("
                    UPDATE reviews 
                    SET likes_count = likes_count + 1
                    WHERE review_id = ?
                ");
                $stmt->bind_param('i', $review_id);
                $stmt->execute();

                echo json_encode(['status' => 'success']);
                exit;
        }

        // Redirect after successful POST actions (except AJAX calls)
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}

// Fetch reviews with filters
$where_conditions = ['r.status = "approved"'];
$params = [];
$types = '';

// Parameters and types for WHERE clause filters
$filter_params = [];
$filter_types = '';

if (isset($_GET['rating']) && $_GET['rating'] > 0) {
    $where_conditions[] = 'r.rating >= ?';
    $filter_params[] = $_GET['rating'];
    $filter_types .= 'i';
}

if (isset($_GET['category']) && $_GET['category'] !== 'all') {
    $where_conditions[] = 'rc.name = ?';
    $filter_params[] = $_GET['category'];
    $filter_types .= 's';
}

if (isset($_GET['time_period'])) {
    switch ($_GET['time_period']) {
        case 'month':
            $where_conditions[] = 'r.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            break;
        case 'quarter':
            $where_conditions[] = 'r.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
            break;
        case 'year':
            $where_conditions[] = 'r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get sort order
$sort_order = match($_GET['sort'] ?? '') {
    'highest_rated' => 'r.rating DESC',
    'most_liked' => 'r.likes_count DESC',
    default => 'r.created_at DESC'
};

// Fetch reviews
$query = "
    SELECT r.*, 
           u.username, 
           u.avatar,
           GROUP_CONCAT(DISTINCT rc.name) as categories,
           GROUP_CONCAT(DISTINCT rp.photo_url) as photos,
           COUNT(DISTINCT rr.reply_id) as reply_count,
           COUNT(DISTINCT re.reaction_id) as reaction_count,
           EXISTS(SELECT 1 FROM review_reactions WHERE review_id = r.review_id AND user_id = ?) as user_reacted
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN review_category_mappings rcm ON r.review_id = rcm.review_id
    LEFT JOIN review_categories rc ON rcm.category_id = rc.category_id
    LEFT JOIN review_photos rp ON r.review_id = rp.review_id
    LEFT JOIN review_replies rr ON r.review_id = rr.review_id
    LEFT JOIN review_reactions re ON r.review_id = re.review_id
    $where_clause
    GROUP BY r.review_id
    ORDER BY $sort_order
    LIMIT 10 OFFSET ?
";

$offset = isset($_GET['page']) ? ($_GET['page'] - 1) * 10 : 0;
$params = array_merge($filter_params, [$user_id], [$offset]);
$types = $filter_types . 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);

// Fetch review statistics
$stats_query = "
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews,
        COUNT(DISTINCT user_id) as active_reviewers,
        SUM(likes_count) as total_likes
    FROM reviews
    WHERE status = 'approved'
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Fetch categories for filter
$categories_query = "SELECT name FROM review_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews | MangaBrew Cafe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f5f1;
        }
        .navbar-brand {
            font-family: 'Georgia', serif;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .review-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .review-card:hover {
            transform: translateY(-5px);
        }
        .rating-stars {
            color: #ffc107;
        }
        .review-category {
            background: #e9ecef;
            border-radius: 20px;
            padding: 5px 15px;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .review-form {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe4cc 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .rating-input {
            display: none;
        }
        .rating-label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #dee2e6;
        }
        .rating-input:checked ~ .rating-label,
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #ffc107;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #6c757d;
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .review-photos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .review-photo {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .review-photo:hover {
            transform: scale(1.05);
        }
        .reply-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .reply-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        .photo-preview {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .photo-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .photo-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .photo-preview-item .remove-photo {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }
        .emoji-reactions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        .emoji-reaction {
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .emoji-reaction:hover {
            background-color: #e9ecef;
        }
        .emoji-reaction.active {
            background-color: #e9ecef;
        }
        .photo-modal .modal-content {
            background-color: rgba(0, 0, 0, 0.9);
        }
        .photo-modal .modal-body {
            padding: 0;
        }
        .photo-modal img {
            max-width: 100%;
            max-height: 80vh;
            margin: auto;
            display: block;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-warning shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand text-dark" href="dashboard.php">MangaBrew Cafe ☕📚</a>
        <div class="d-flex">
            <span class="navbar-text me-3 text-dark">
                Welcome, <strong><?= $full_name; ?></strong>!
            </span>
            <a href="dashboard.php" class="btn btn-outline-dark me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-dark">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container my-5">
    <h2 class="mb-4">Customer Reviews</h2>

    <!-- Review Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-star-fill text-warning display-6"></i>
                <div class="stats-number">4.8</div>
                <div class="text-muted">Average Rating</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-chat-square-text-fill text-primary display-6"></i>
                <div class="stats-number">156</div>
                <div class="text-muted">Total Reviews</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-people-fill text-success display-6"></i>
                <div class="stats-number">89</div>
                <div class="text-muted">Active Reviewers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-heart-fill text-danger display-6"></i>
                <div class="stats-number">1.2k</div>
                <div class="text-muted">Total Likes</div>
            </div>
        </div>
    </div>

    <!-- Review Filters -->
    <div class="filter-section mb-4">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label">Sort By</label>
                <select class="form-select">
                    <option>Most Recent</option>
                    <option>Highest Rated</option>
                    <option>Most Liked</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Rating</label>
                <select class="form-select">
                    <option>All Ratings</option>
                    <option>5 Stars</option>
                    <option>4+ Stars</option>
                    <option>3+ Stars</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-select">
                    <option>All Categories</option>
                    <option>Atmosphere</option>
                    <option>Service</option>
                    <option>Manga Collection</option>
                    <option>Drinks</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Time Period</label>
                <select class="form-select">
                    <option>All Time</option>
                    <option>Last Month</option>
                    <option>Last 3 Months</option>
                    <option>Last Year</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Write Review Form -->
    <div class="review-form mb-4">
        <h4 class="mb-3">Write Your Review</h4>
        <form id="reviewForm">
            <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="rating">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" value="<?= $i ?>" class="rating-input" id="star<?= $i ?>">
                    <label for="star<?= $i ?>" class="rating-label"><i class="bi bi-star-fill"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" placeholder="Give your review a title">
            </div>
            <div class="mb-3">
                <label class="form-label">Your Review</label>
                <textarea class="form-control" rows="4" placeholder="Share your experience at MangaBrew Cafe..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Categories (Select all that apply)</label>
                <div class="d-flex flex-wrap gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Atmosphere">
                        <label class="form-check-label">Atmosphere</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Service">
                        <label class="form-check-label">Service</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Manga Collection">
                        <label class="form-check-label">Manga Collection</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Drinks">
                        <label class="form-check-label">Drinks</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Add Photos (Optional)</label>
                <input type="file" class="form-control" id="reviewPhotos" accept="image/*" multiple>
                <div class="photo-preview" id="photoPreview"></div>
                <small class="text-muted">You can upload up to 4 photos. Max size: 5MB each.</small>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </div>

    <!-- Reviews List -->
    <div class="reviews-list">
        <?php foreach($reviews as $review): ?>
        <div class="review-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center">
                    <img src="<?= htmlspecialchars($review['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1, 70)) ?>" 
                         alt="User Avatar" class="user-avatar me-3">
                    <div>
                        <h5 class="mb-1"><?= htmlspecialchars($review['username']) ?></h5>
                        <div class="rating-stars">
                            <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                            <?php for($i = $review['rating']; $i < 5; $i++): ?>
                                <i class="bi bi-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
            </div>
            <h5 class="mb-2"><?= htmlspecialchars($review['title']) ?></h5>
            <p class="mb-3"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
            <?php if ($review['categories']): ?>
            <div class="d-flex flex-wrap mb-3">
                <?php foreach(explode(',', $review['categories']) as $category): ?>
                    <span class="review-category"><?= htmlspecialchars($category) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($review['photos']): ?>
            <div class="review-photos">
                <?php foreach(explode(',', $review['photos']) as $photo): ?>
                <img src="<?= htmlspecialchars($photo) ?>" alt="Review Photo" class="review-photo" onclick="openPhotoModal(this.src)">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="emoji-reactions mb-3">
                <span class="emoji-reaction <?= $review['user_reacted'] ? 'active' : '' ?>" 
                      onclick="addReaction(this, '👍', <?= $review['review_id'] ?>)" 
                      title="Like">👍</span>
                <span class="emoji-reaction" 
                      onclick="addReaction(this, '❤️', <?= $review['review_id'] ?>)" 
                      title="Love">❤️</span>
                <span class="emoji-reaction" 
                      onclick="addReaction(this, '😊', <?= $review['review_id'] ?>)" 
                      title="Happy">😊</span>
                <span class="emoji-reaction" 
                      onclick="addReaction(this, '👏', <?= $review['review_id'] ?>)" 
                      title="Clap">👏</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-primary btn-sm" 
                        onclick="likeReview(this, <?= $review['review_id'] ?>)">
                    <i class="bi bi-heart"></i> Like (<span class="likes-count"><?= $review['likes_count'] ?></span>)
                </button>
                <div>
                    <button class="btn btn-outline-secondary btn-sm me-2" 
                            onclick="toggleReplyForm(this)">
                        <i class="bi bi-reply"></i> Reply
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="shareReview(this, <?= $review['review_id'] ?>)">
                        <i class="bi bi-share"></i> Share
                    </button>
                </div>
            </div>

            <!-- Reply Form -->
            <div class="reply-form mt-3" style="display: none;">
                <form onsubmit="submitReply(event, this, <?= $review['review_id'] ?>)">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Write a reply...">
                        <button class="btn btn-primary" type="submit">Reply</button>
                    </div>
                </form>
            </div>

            <!-- Replies Section -->
            <?php if ($review['reply_count'] > 0): ?>
            <div class="reply-section">
                <h6 class="mb-3">Replies (<?= $review['reply_count'] ?>)</h6>
                <?php
                $replies_query = "
                    SELECT rr.*, u.username, u.avatar
                    FROM review_replies rr
                    JOIN users u ON rr.user_id = u.user_id
                    WHERE rr.review_id = ? AND rr.status = 'active'
                    ORDER BY rr.created_at ASC
                ";
                $stmt = $conn->prepare($replies_query);
                $stmt->bind_param('i', $review['review_id']);
                $stmt->execute();
                $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach($replies as $reply):
                ?>
                <div class="reply-card">
                    <div class="d-flex align-items-center mb-2">
                        <img src="<?= htmlspecialchars($reply['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1, 70)) ?>" 
                             alt="User Avatar" class="user-avatar me-2" style="width: 30px; height: 30px;">
                        <div>
                            <strong><?= htmlspecialchars($reply['username']) ?></strong>
                            <small class="text-muted ms-2"><?= date('M d, Y H:i', strtotime($reply['created_at'])) ?></small>
                        </div>
                    </div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Load More Button -->
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary px-4" onclick="loadMoreReviews()">
            Load More Reviews
        </button>
    </div>
</div>

<footer class="text-center text-muted py-4 small">
    &copy; 2025 MangaBrew Cafe. Brewed with passion & manga magic.
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script>
    // Like review functionality
    function likeReview(button, reviewId) {
        fetch('reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=like_review&review_id=${reviewId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const likesCount = button.querySelector('.likes-count');
                likesCount.textContent = parseInt(likesCount.textContent) + 1;
                button.disabled = true;
                button.classList.add('text-danger');
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Share review functionality
    function shareReview(button, reviewId) {
        const reviewUrl = `${window.location.origin}${window.location.pathname}?review=${reviewId}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'MangaBrew Cafe Review',
                text: 'Check out this review on MangaBrew Cafe!',
                url: reviewUrl
            })
            .catch(error => console.error('Error sharing:', error));
        } else {
            // Fallback for browsers that don't support Web Share API
            const dummy = document.createElement('input');
            document.body.appendChild(dummy);
            dummy.value = reviewUrl;
            dummy.select();
            document.execCommand('copy');
            document.body.removeChild(dummy);
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i> Copied!';
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 2000);
        }
    }

    // Load more reviews
    function loadMoreReviews() {
        // Implement load more functionality
        alert('Loading more reviews...');
    }

    // Form submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'submit_review');
        
        // Validate photos
        const photos = document.getElementById('reviewPhotos').files;
        if (photos.length > 4) {
            alert('You can only upload up to 4 photos.');
            return;
        }
        
        fetch('reviews.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            location.reload(); // Refresh to show new review
        })
        .catch(error => console.error('Error:', error));
    });

    // Rating hover effect
    document.querySelectorAll('.rating-label').forEach(label => {
        label.addEventListener('mouseover', function() {
            const rating = this.previousElementSibling.value;
            document.querySelectorAll('.rating-label').forEach(l => {
                if (l.previousElementSibling.value <= rating) {
                    l.style.color = '#ffc107';
                }
            });
        });

        label.addEventListener('mouseout', function() {
            document.querySelectorAll('.rating-label').forEach(l => {
                if (!l.previousElementSibling.checked) {
                    l.style.color = '#dee2e6';
                }
            });
        });
    });

    // Photo preview functionality
    document.getElementById('reviewPhotos').addEventListener('change', function(e) {
        const preview = document.getElementById('photoPreview');
        preview.innerHTML = '';
        
        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                if (index >= 4) return; // Limit to 4 photos
                
                const reader = new FileReader();
                const div = document.createElement('div');
                div.className = 'photo-preview-item';
                
                reader.onload = function(e) {
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <span class="remove-photo" onclick="removePhoto(this)">×</span>
                    `;
                }
                
                reader.readAsDataURL(file);
                preview.appendChild(div);
            });
        }
    });

    function removePhoto(element) {
        element.parentElement.remove();
    }

    // Photo modal functionality
    function openPhotoModal(src) {
        const modal = new bootstrap.Modal(document.getElementById('photoModal'));
        document.getElementById('modalPhoto').src = src;
        modal.show();
    }

    // Reply functionality
    function toggleReplyForm(button) {
        const replyForm = button.closest('.review-card').querySelector('.reply-form');
        replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
    }

    function submitReply(event, form, reviewId) {
        event.preventDefault();
        const input = form.querySelector('input');
        const content = input.value.trim();
        
        if (content) {
            fetch('reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=submit_reply&review_id=${reviewId}&content=${encodeURIComponent(content)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    location.reload(); // Refresh to show new reply
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // Emoji reaction functionality
    function addReaction(element, emoji, reviewId) {
        fetch('reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=add_reaction&review_id=${reviewId}&emoji=${emoji}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added') {
                element.classList.add('active');
            } else if (data.status === 'removed') {
                element.classList.remove('active');
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>

<!-- Add Photo Modal -->
<div class="modal fade photo-modal" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <img src="" alt="Review Photo" id="modalPhoto">
            </div>
        </div>
    </div>
</div>
</body>
</html> 