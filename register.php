<?php
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('user/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rateLimit = rate_limit_allow('registration', 5, 3600);
    if ($rateLimit !== true) {
        http_response_code($rateLimit === false ? 429 : 503);
        $error = $rateLimit === false ? 'Too many registration attempts. Please try again later.' : 'Registration is temporarily unavailable.';
    } else {
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm_password = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        if ($name === '' || $email === '' || $password === '') {
            $error = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (
            strlen($name) > 100 || strlen($email) > 150 || strlen($phone) > 30
            || ($phone !== '' && !preg_match('/^[0-9+() .-]{7,30}$/', $phone))
        ) {
            $error = 'Please check the length and format of your details.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $error = 'Password must be between 12 and 72 bytes.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!$conn) {
            $error = 'Database connection unavailable.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'An account with this email already exists. Please login instead.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins = $conn->prepare("INSERT INTO users (name, email, phone, password, status) VALUES (?, ?, ?, ?, 'active')");
                $ins->bind_param('ssss', $name, $email, $phone, $hash);
                if ($ins->execute()) {
                    $userId = $conn->insert_id;
                    session_regenerate_id(true);
                    unset($_SESSION['csrf_token']);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    add_notification($conn, $userId, 'Welcome to CholoGhuri!', "Welcome aboard $name! Explore our handpicked tours across Bangladesh.", 'info');
                    set_flash('success', "Account created! Welcome to CholoGhuri, $name.");
                    redirect('user/dashboard.php');
                }
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Traveler Account | CholoGhuri';
require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-[calc(100vh-200px)] py-12 flex items-center bg-slate-50 relative overflow-hidden">
    <!-- Decorative background blobs -->
    <div
        class="absolute top-[20%] right-[-5%] w-96 h-96 bg-emerald/20 rounded-full mix-blend-multiply filter blur-3xl opacity-70">
    </div>
    <div
        class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-primary/20 rounded-full mix-blend-multiply filter blur-3xl opacity-70">
    </div>

    <div class="container mx-auto px-4 max-w-xl relative z-10">
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 md:p-10 border border-slate-100 shadow-2xl reveal-up">

            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 bg-emerald/10 text-emerald text-xs font-black uppercase tracking-widest rounded-full mb-3">
                    <i class="fa-solid fa-user-plus"></i> Join CholoGhuri
                </div>
                <h1 class="text-3xl font-extrabold text-dark mb-2">Create Your Account</h1>
                <p class="text-slate-500 text-sm font-medium">Join thousands of travelers exploring stunning spots in
                    Bangladesh.</p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-xl mb-6 text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Full Name</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="text" name="name"
                            value="<?= htmlspecialchars(input_string($_POST, 'name'), ENT_QUOTES, 'UTF-8') ?>" required
                            placeholder="e.g. Mahfuzur Rahman"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald/50 focus:border-emerald transition-all placeholder:text-slate-400 font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars(input_string($_POST, 'email'), ENT_QUOTES, 'UTF-8') ?>" required
                            placeholder="name@example.com"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald/50 focus:border-emerald transition-all placeholder:text-slate-400 font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Mobile Number</label>
                    <div class="relative">
                        <i class="fa-solid fa-phone absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="text" name="phone"
                            value="<?= htmlspecialchars(input_string($_POST, 'phone'), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="017xxxxxxxx"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald/50 focus:border-emerald transition-all placeholder:text-slate-400 font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-400"></i>
                            <input type="password" name="password" required minlength="12" placeholder="Min. 12 chars"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald/50 focus:border-emerald transition-all placeholder:text-slate-400 font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Confirm Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock-alert absolute left-4 top-3.5 text-slate-400"></i>
                            <input type="password" name="confirm_password" required minlength="12"
                                placeholder="Repeat password"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald/50 focus:border-emerald transition-all placeholder:text-slate-400 font-medium">
                        </div>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit"
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-base">
                        Create Free Traveler Account <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <div class="text-center text-sm text-slate-500 border-t border-slate-100 mt-8 pt-6 font-medium">
                Already have an account? <a href="login.php"
                    class="text-emerald hover:text-emerald-700 transition-colors font-bold ml-1">Sign in here</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>