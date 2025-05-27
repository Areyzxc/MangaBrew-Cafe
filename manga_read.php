<?php
// read_manga.php
session_start();
require 'db_connection.php';

// Sample manga list (Replace with DB fetch later)
$manga_list = [
    [
        'title' => 'One Cup Hero',
        'cover' => 'img/onecuphero.jpg',
        'description' => 'A barista by day, hero by night. Adventures brewed to perfection!',
        'chapters' => ['Chapter 1: Espresso Encounter', 'Chapter 2: Latte Legacy']
    ],
    [
        'title' => 'The Matcha Chronicles',
        'cover' => 'img/matchachronicles.jpg',
        'description' => 'An ancient tale of mystical teas and rival cafés.',
        'chapters' => ['Chapter 1: The Green Awakens', 'Chapter 2: Whisked Destiny']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Read Manga | MangaBrew Café</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.manga-cover {
    height: 200px;
    object-fit: cover;
}
.page-img {
    width: 100%;
    margin-bottom: 15px;
    border-radius: 5px;
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
                <li class="nav-item"><a class="nav-link active" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4 text-center">📚 Manga Library</h2>

    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach ($manga_list as $index => $manga): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="<?php echo $manga['cover']; ?>" class="card-img-top manga-cover" alt="<?php echo $manga['title']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $manga['title']; ?></h5>
                        <p class="card-text"><?php echo $manga['description']; ?></p>

                        <!-- Button trigger chapters modal -->
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#chaptersModal<?php echo $index; ?>">
                            📖 Read Chapters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chapters Modal -->
            <div class="modal fade" id="chaptersModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="chaptersModalLabel<?php echo $index; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="chaptersModalLabel<?php echo $index; ?>"><?php echo $manga['title']; ?> - Chapters</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">

                            <?php foreach ($manga['chapters'] as $chapterIndex => $chapter): ?>
                                <button class="btn btn-outline-secondary w-100 mb-2" 
                                        onclick="openChapter('<?php echo addslashes($manga['title']); ?>', '<?php echo addslashes($chapter); ?>')">
                                    📄 <?php echo $chapter; ?>
                                </button>
                            <?php endforeach; ?>

                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <!-- Manga Reader Modal (Lightbox style) -->
    <div class="modal fade" id="readerModal" tabindex="-1" aria-labelledby="readerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="readerModalLabel">Manga Reader</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="mangaPages">
                    <!-- Manga pages will be dynamically injected -->
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simulated pages (Replace with DB later)
const samplePages = [
    'img/sample_page1.jpg',
    'img/sample_page2.jpg',
    'img/sample_page3.jpg'
];

function openChapter(mangaTitle, chapterTitle) {
    const readerLabel = document.getElementById('readerModalLabel');
    const mangaPages = document.getElementById('mangaPages');

    // Set title
    readerLabel.textContent = `${mangaTitle} - ${chapterTitle}`;

    // Clear existing pages
    mangaPages.innerHTML = '';

    // Dynamically load sample pages (simulate)
    samplePages.forEach(page => {
        const img = document.createElement('img');
        img.src = page;
        img.className = 'page-img shadow';
        mangaPages.appendChild(img);
    });

    // Show reader modal
    const readerModal = new bootstrap.Modal(document.getElementById('readerModal'));
    readerModal.show();
}
</script>

</body>
</html>
