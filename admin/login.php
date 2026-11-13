<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rateLimit = rate_limit_allow('admin-login', 8, 900);
    if ($rateLimit !== true) {
        http_response_code($rateLimit === false ? 429 : 503);
        $error = $rateLimit === false ? 'Too many attempts. Please try again in 15 minutes.' : 'Authentication is temporarily unavailable.';
    } else {
        $email = trim(input_string($_POST, 'email'));
        $password = input_string($_POST, 'password');

        if ($email === '' || $password === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide both admin email and password.';
        } else {
            if ($conn) {
                $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active' LIMIT 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();

                if ($admin && password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);
                    unset($_SESSION['csrf_token']);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    if (password_needs_rehash($admin['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                        $update->bind_param('si', $hash, $admin['id']);
                        $update->execute();
                    }

                    log_admin_action($conn, $admin['id'], 'Admin Login', "Administrator {$admin['name']} logged in");
                    set_flash('success', "Welcome back, {$admin['name']}!");
                    redirect('dashboard.php');
                } else {
                    $error = 'Invalid administrative credentials.';
                }
            } else {
                $error = 'Database connection unavailable.';
            }
        }
    }
}

$pageTitle = 'Admin Portal Login | CholoGhuri';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { primary: '#0ea5e9', dark: '#0f172a' }
                }
            }
        }
    </script>
</head>

<body class="bg-dark min-h-screen flex items-center justify-center p-6 relative overflow-hidden font-sans">

    <!-- Ambient glowing backgrounds for dark mode effect -->
    <div class="absolute top-[-20%] left-[-10%] w-[500px] h-[500px] bg-primary/20 rounded-full blur-[100px] opacity-60">
    </div>
    <div
        class="absolute bottom-[-20%] right-[-10%] w-[500px] h-[500px] bg-indigo-500/20 rounded-full blur-[100px] opacity-60">
    </div>

    <div
        class="bg-slate-900/80 backdrop-blur-xl w-full max-w-[440px] rounded-3xl p-8 md:p-10 border border-slate-800 shadow-2xl relative z-10 mx-auto">
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex items-center justify-center gap-3 mb-6">
                <img src="../images/logo.png" alt="CholoGhuri Logo" class="h-10">
                <span class="text-2xl font-extrabold text-white">Cholo<span class="text-primary">Ghuri</span></span>
            </a>
            <h2 class="text-xl font-bold text-white mb-1">Administrator Portal</h2>
            <p class="text-slate-400 text-sm font-medium">Secure management gateway for bookings & tours</p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-rose-500/10 border border-rose-500/30 text-rose-400 px-4 py-3.5 rounded-xl mb-6 text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-5">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Admin Email</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-500"></i>
                    <input type="email" name="email"
                        value="<?= htmlspecialchars(input_string($_POST, 'email'), ENT_QUOTES, 'UTF-8') ?>" required
                        class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-500 font-medium text-sm">
                </div>
            </div>

            <div class="mb-2">
                <label class="block text-sm font-bold text-slate-300 mb-2">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-500"></i>
                    <input type="password" name="password" placeholder="••••••••" required
                        class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-500 font-medium text-sm">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-gradient-to-r from-primary to-indigo-500 hover:from-indigo-500 hover:to-primary text-white font-bold py-3.5 px-6 rounded-xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-sm">
                    <i class="fa-solid fa-shield-halved"></i> Access Admin Dashboard
                </button>
            </div>
        </form>

        <div class="text-center mt-8 pt-6 border-t border-slate-800">
            <a href="../index.php"
                class="text-slate-400 hover:text-white transition-colors text-sm font-semibold flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Return to Public Website
            </a>
        </div>
    </div>
</body>

</html>