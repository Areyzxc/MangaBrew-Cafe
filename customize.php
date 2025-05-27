<?php
// customize.php
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
    <title>Customize Your Experience | MangaBrew Cafe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f5f1;
        }
        .customization-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .customization-card:hover {
            transform: translateY(-5px);
        }
        .theme-preview {
            width: 100%;
            height: 100px;
            border-radius: 10px;
            margin: 10px 0;
            cursor: pointer;
            border: 3px solid transparent;
        }
        .theme-preview.active {
            border-color: #0d6efd;
        }
        .preference-toggle {
            cursor: pointer;
        }
        .color-picker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
            cursor: pointer;
        }
        .navbar-brand {
            font-family: 'Georgia', serif;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .customization-section {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe4cc 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
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
    <h2 class="mb-4">Customize Your MangaBrew Experience</h2>
    
    <!-- Reading Preferences -->
    <div class="customization-section">
        <h3 class="mb-4">📚 Reading Preferences</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-book"></i> Reading Theme</h5>
                    <div class="theme-preview bg-light" style="background: #f8f5f1;" data-theme="light"></div>
                    <div class="theme-preview bg-dark" style="background: #2c3e50;" data-theme="dark"></div>
                    <div class="theme-preview" style="background: #e8f4f8;" data-theme="manga"></div>
                    <div class="mt-3">
                        <label class="form-label">Font Size</label>
                        <input type="range" class="form-range" min="12" max="24" value="16">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-bell"></i> Reading Notifications</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="newChapters">
                        <label class="form-check-label" for="newChapters">Notify me about new chapters</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="readingReminders">
                        <label class="form-check-label" for="readingReminders">Daily reading reminders</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="readingGoals">
                        <label class="form-check-label" for="readingGoals">Reading goal updates</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cafe Experience -->
    <div class="customization-section">
        <h3 class="mb-4">☕ Cafe Experience</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-cup-hot"></i> Drink Preferences</h5>
                    <div class="mb-3">
                        <label class="form-label">Favorite Drink Type</label>
                        <select class="form-select">
                            <option>Coffee</option>
                            <option>Tea</option>
                            <option>Specialty Drinks</option>
                            <option>All of the above</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custom Drink Combinations</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" placeholder="Drink name">
                            <button class="btn btn-outline-primary">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-geo-alt"></i> Seating Preferences</h5>
                    <div class="mb-3">
                        <label class="form-label">Preferred Seating Area</label>
                        <select class="form-select">
                            <option>Window Seat</option>
                            <option>Quiet Corner</option>
                            <option>Group Table</option>
                            <option>No Preference</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="autoReserve">
                        <label class="form-check-label" for="autoReserve">Auto-reserve favorite seat</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Customization -->
    <div class="customization-section">
        <h3 class="mb-4">👤 Profile Customization</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-palette"></i> Profile Appearance</h5>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Theme Color</label>
                        <div class="d-flex gap-2">
                            <div class="color-picker" style="background: #0d6efd;"></div>
                            <div class="color-picker" style="background: #198754;"></div>
                            <div class="color-picker" style="background: #dc3545;"></div>
                            <div class="color-picker" style="background: #6f42c1;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="customization-card">
                    <h5><i class="bi bi-trophy"></i> Reading Goals</h5>
                    <div class="mb-3">
                        <label class="form-label">Monthly Reading Goal (chapters)</label>
                        <input type="number" class="form-control" value="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Favorite Genres</label>
                        <select class="form-select" multiple>
                            <option>Action</option>
                            <option>Comedy</option>
                            <option>Drama</option>
                            <option>Fantasy</option>
                            <option>Romance</option>
                            <option>Slice of Life</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="text-center mt-4">
        <button class="btn btn-primary btn-lg px-5" onclick="savePreferences()">
            <i class="bi bi-save"></i> Save All Preferences
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
    // Theme selection
    document.querySelectorAll('.theme-preview').forEach(theme => {
        theme.addEventListener('click', function() {
            document.querySelectorAll('.theme-preview').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            // Apply theme logic here
        });
    });

    // Color picker selection
    document.querySelectorAll('.color-picker').forEach(picker => {
        picker.addEventListener('click', function() {
            document.querySelectorAll('.color-picker').forEach(p => p.style.border = '2px solid #ddd');
            this.style.border = '2px solid #000';
            // Apply color logic here
        });
    });

    // Save preferences
    function savePreferences() {
        // Collect all preferences
        const preferences = {
            theme: document.querySelector('.theme-preview.active')?.dataset.theme,
            fontSize: document.querySelector('input[type="range"]').value,
            notifications: {
                newChapters: document.getElementById('newChapters').checked,
                readingReminders: document.getElementById('readingReminders').checked,
                readingGoals: document.getElementById('readingGoals').checked
            },
            // Add more preferences here
        };

        // Send to server (to be implemented)
        console.log('Saving preferences:', preferences);
        alert('Preferences saved successfully!');
    }
</script>
</body>
</html> 