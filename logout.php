<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    set_flash('info', 'You have been successfully logged out.');
    redirect('index.php');
}

// Already logged out — redirect to home
if (!is_logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Sign Out | CholoGhuri';
require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-[calc(100vh-200px)] flex items-center justify-center py-16 bg-slate-50 relative overflow-hidden">
    <!-- Decorative blobs -->
    <div class="absolute top-[-5%] right-[-5%] w-80 h-80 bg-rose-500/10 rounded-full filter blur-3xl opacity-70"></div>
    <div class="absolute bottom-[-5%] left-[-5%] w-80 h-80 bg-sky/10 rounded-full filter blur-3xl opacity-70"></div>

    <div class="container mx-auto px-4 max-w-md relative z-10">
        <div
            class="bg-white/80 backdrop-blur-xl rounded-3xl p-10 border border-slate-100 shadow-2xl text-center reveal-up">

            <div
                class="w-20 h-20 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-6 text-4xl text-rose-500 shadow-inner">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>

            <h1 class="text-2xl font-extrabold text-dark mb-2">Ready to Leave?</h1>
            <p class="text-slate-500 text-sm font-medium mb-8">
                You are signed in as <strong
                    class="text-dark"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Traveler') ?></strong>.<br>
                Are you sure you want to sign out of your CholoGhuri account?
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <form method="POST" action="logout.php" class="flex-1">
                    <?= csrf_field() ?>
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-rose-500 to-primary hover:from-primary hover:to-rose-500 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-rose-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-from-bracket"></i> Yes, Sign Out
                    </button>
                </form>
                <a href="user/dashboard.php"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3.5 px-6 rounded-xl transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-house"></i> Stay Logged In
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>