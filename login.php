<?php
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('user/dashboard.php');
}

$error = '';
$redirect = safe_redirect_target($_GET['redirect'] ?? $_POST['redirect'] ?? $_SESSION['redirect_after_login'] ?? 'user/dashboard.php');
unset($_SESSION['redirect_after_login']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rateLimit = rate_limit_allow('user-login', 10, 900);
    if ($rateLimit !== true) {
        http_response_code($rateLimit === false ? 429 : 503);
        $error = $rateLimit === false ? 'Too many attempts. Please try again in 15 minutes.' : 'Authentication is temporarily unavailable.';
    } else {
        $email = trim(input_string($_POST, 'email'));
        $password = input_string($_POST, 'password');
        $redirect = safe_redirect_target($_POST['redirect'] ?? 'user/dashboard.php');

        if ($email === '' || $password === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter both your email address and password.';
        } else {
            if ($conn) {
                $stmt = $conn->prepare("SELECT id, name, email, password, status FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if ($user && ($user['status'] ?? 'active') === 'active' && password_verify($password, $user['password'])) {
                    // Session fixation security
                    session_regenerate_id(true);
                    unset($_SESSION['csrf_token']);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    set_flash('success', "Welcome back, {$user['name']}!");
                    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update->bind_param('si', $hash, $user['id']);
                        $update->execute();
                    }
                    redirect($redirect);
                } else {
                    $error = 'Invalid email or password combination.';
                }
            } else {
                $error = 'Database connection error. Please try again.';
            }
        }
    }
}

$pageTitle = 'Login to Your Account | CholoGhuri';
require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-[calc(100vh-200px)] py-12 flex items-center bg-slate-50 relative overflow-hidden">
    <!-- Decorative background blobs -->
    <div
        class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-sky/20 rounded-full mix-blend-multiply filter blur-3xl opacity-70">
    </div>
    <div
        class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-primary/20 rounded-full mix-blend-multiply filter blur-3xl opacity-70">
    </div>

    <div class="container mx-auto px-4 max-w-lg relative z-10">
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 md:p-10 border border-slate-100 shadow-2xl reveal-up">

            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 bg-sky/10 text-sky text-xs font-black uppercase tracking-widest rounded-full mb-3">
                    <i class="fa-solid fa-plane-arrival"></i> Welcome Back
                </div>
                <h1 class="text-3xl font-extrabold text-dark mb-2">Login to CholoGhuri</h1>
                <p class="text-slate-500 text-sm font-medium">Manage your tours, track reservations, and view vouchers.
                </p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-xl mb-6 text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars(input_string($_POST, 'email'), ENT_QUOTES, 'UTF-8') ?>" required
                            placeholder="name@example.com"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 font-medium">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-slate-700">Password</label>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 font-medium">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-primary to-rose-500 hover:from-rose-500 hover:to-primary text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-primary/30 transition-all active:scale-95 flex items-center justify-center gap-2 text-base">
                        Sign In to Account <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </button>
                </div>
            </form>

            <div class="text-center text-sm text-slate-500 border-t border-slate-100 mt-8 pt-6 font-medium">
                Don't have an account yet? <a href="register.php"
                    class="text-sky hover:text-primary transition-colors font-bold ml-1">Create Account</a>
            </div>

            <div class="text-center mt-4">
                <a href="admin/login.php"
                    class="text-xs text-slate-400 hover:text-slate-600 transition-colors font-semibold flex items-center justify-center gap-1">
                    <i class="fa-solid fa-shield-halved"></i> Administrator Access
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>