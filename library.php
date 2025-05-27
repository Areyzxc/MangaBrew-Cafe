<?php
// library.php
session_start();
require 'db_connection.php';

// Simulated static data (can be replaced w/ DB fetch later)
$manga_items = [
    ['genre' => 'Shonen', 'title' => 'Naruto', 'author' => 'Masashi Kishimoto', 'synopsis' => 'A ninja\'s journey to become Hokage.', 'cover' => 'naruto.jpg'],
    ['genre' => 'Shojo', 'title' => 'Fruits Basket', 'author' => 'Natsuki Takaya', 'synopsis' => 'A girl discovers a family\'s zodiac secret.', 'cover' => 'fruits_basket.jpg'],
    ['genre' => 'Seinen', 'title' => 'Berserk', 'author' => 'Kentaro Miura', 'synopsis' => 'A dark tale of revenge and destiny.', 'cover' => 'berserk.jpg'],
    ['genre' => 'Shonen', 'title' => 'One Piece', 'author' => 'Eiichiro Oda', 'synopsis' => 'Pirate adventures in search of the One Piece.', 'cover' => 'one_piece.jpg'],
    ['genre' => 'Josei', 'title' => 'Nana', 'author' => 'Ai Yazawa', 'synopsis' => 'Two women chasing dreams and love in Tokyo.', 'cover' => 'nana.jpg']
];

// Extract unique genres
$genres = array_unique(array_column($manga_items, 'genre'));
sort($genres);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library | MangaBrew Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .manga-card img { height: 200px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar (consistent layout) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">MangaBrew Café</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link active" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4 text-center">📖 Manga Library</h2>

    <!-- Genre Filter (Bootstrap Nav Pills) -->
    <ul class="nav nav-pills justify-content-center mb-4" id="genreTabs">
        <li class="nav-item"><a class="nav-link active" data-genre="All" href="#">All</a></li>
        <?php foreach ($genres as $g): ?>
            <li class="nav-item"><a class="nav-link" data-genre="<?php echo $g; ?>" href="#"><?php echo $g; ?></a></li>
        <?php endforeach; ?>
    </ul>

    <!-- Search Bar -->
    <div class="mb-4 text-center">
        <input type="text" class="form-control w-50 mx-auto" id="searchInput" placeholder="Search manga titles...">
    </div>

    <!-- Manga Items Grid -->
    <div class="row" id="libraryGrid">
        <?php foreach ($manga_items as $manga): ?>
            <div class="col-md-4 mb-4 library-item" data-genre="<?php echo $manga['genre']; ?>">
                <div class="card manga-card shadow-sm h-100">
                    <img src="manga/<?php echo $manga['cover']; ?>" class="card-img-top" alt="<?php echo $manga['title']; ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo $manga['title']; ?></h5>
                        <p class="card-text small text-muted">Author: <?php echo $manga['author']; ?></p>
                        <p class="card-text small"><?php echo $manga['synopsis']; ?></p>
                        <a href="manga_read.php?title=<?php echo urlencode($manga['title']); ?>" class="btn btn-success mt-auto">Read</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Genre filter
const genreTabs = document.querySelectorAll('#genreTabs a');
const libraryItems = document.querySelectorAll('.library-item');
const searchInput = document.getElementById('searchInput');

genreTabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
        e.preventDefault();
        genreTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const genre = tab.getAttribute('data-genre');
        libraryItems.forEach(item => {
            item.style.display = (genre === 'All' || item.getAttribute('data-genre') === genre) ? 'block' : 'none';
        });
    });
});

// Search filtering
searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    libraryItems.forEach(item => {
        const title = item.querySelector('.card-title').textContent.toLowerCase();
        item.style.display = title.includes(query) ? 'block' : 'none';
    });
});
</script>

</body>
</html>
