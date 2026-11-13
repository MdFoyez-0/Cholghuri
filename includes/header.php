<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$unread_notifications = 0;
if (is_logged_in() && $conn) {
    $uid = (int) $_SESSION['user_id'];
    $n_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($n_stmt) {
        $n_stmt->bind_param('i', $uid);
        $n_stmt->execute();
        $unread_notifications = (int) ($n_stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CholoGhuri | Explore More. Live Better.') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            corePlugins: {
                preflight: false,  /* Prevent Tailwind from resetting existing CSS */
            },
            theme: {
                extend: {
                    colors: {
                        primary: '#ff385c',
                        sky: '#0ea5e9',
                        emerald: '#10b981',
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body>
    <header class="navbar">
        <div class="container nav-inner">
            <a class="brand" href="<?= BASE_URL ?>index.php">
                <img src="<?= BASE_URL ?>images/logo.png" alt="CholoGhuri logo">
                <span>Cholo<span>Ghuri</span></span>
            </a>

            <button class="menu-btn" onclick="document.querySelector('.nav-links').classList.toggle('show')"
                aria-label="Toggle navigation">☰</button>

            <nav class="nav-links">
                <a href="<?= BASE_URL ?>index.php">Home</a>
                <a href="<?= BASE_URL ?>destinations.php">Destinations</a>
                <a href="<?= BASE_URL ?>packages.php">Packages</a>
                <a href="<?= BASE_URL ?>index.php#about">About</a>
                <a href="<?= BASE_URL ?>index.php#contact">Contact</a>

                <button id="theme-toggle" class="dark-mode-toggle" title="Toggle Dark/Light Mode"
                    aria-label="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>

                <?php if (is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>user/notifications.php" class="nav-icon-link" title="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="nav-badge"><?= $unread_notifications ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>user/wishlist.php" class="nav-icon-link" title="My Wishlist">
                        <i class="fa-solid fa-heart text-red"></i>
                    </a>
                    <div class="user-menu-dropdown">
                        <a href="<?= BASE_URL ?>user/dashboard.php" class="user-pill">
                            <i class="fa-solid fa-circle-user"></i>
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?></span>
                        </a>
                    </div>
                    <a class="nav-cta btn-logout" href="<?= BASE_URL ?>logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php">Login</a>
                    <a class="nav-cta" href="<?= BASE_URL ?>register.php">Join Now</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if ($flash): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: <?= json_encode($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'success'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    title: <?= json_encode($flash['type'] === 'error' ? 'Oops...' : 'Notification', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    text: <?= json_encode($flash['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    timer: 3500, showConfirmButton: false, toast: true, position: 'top-end'
                });
            });
        </script>
    <?php endif; ?>

    <main>
