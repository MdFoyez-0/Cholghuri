<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$user = current_user($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current_pass = input_string($_POST, 'current_password');
    $new_pass = input_string($_POST, 'new_password');
    $confirm_pass = input_string($_POST, 'confirm_password');

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = 'All fields are required.';
    } elseif ($new_pass !== $confirm_pass) {
        $error = 'New password and confirmation do not match.';
    } elseif (strlen($new_pass) < 12 || strlen($new_pass) > 72) {
        $error = 'New password must be between 12 and 72 bytes.';
    } else {
        if ($conn) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if ($row && password_verify($current_pass, $row['password'])) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param('si', $new_hash, $user_id);
                if ($update->execute()) {
                    set_flash('success', 'Password successfully updated! Please keep your login credentials safe.');
                    redirect('dashboard.php');
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

$pageTitle = 'Change Password | CholoGhuri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- User Sidebar -->
    <aside class="col-span-1">
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 reveal-up sticky top-28">
            <div class="text-center pb-6 mb-6 border-b border-slate-100">
                <div
                    class="w-20 h-20 rounded-full mx-auto bg-gradient-to-tr from-sky-400 to-primary text-white text-3xl font-bold flex items-center justify-center mb-4 shadow-lg shadow-primary/20">
                    <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h3 class="text-lg font-bold text-dark m-0"><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                <p class="text-sm text-slate-500 m-0 mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            </div>

            <ul class="flex flex-col gap-2">
                <li><a href="dashboard.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-gauge w-5 text-center text-slate-400"></i> Dashboard</a></li>
                <li><a href="profile.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-user-pen w-5 text-center text-slate-400"></i> My Profile</a></li>
                <li><a href="bookings.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-suitcase-rolling w-5 text-center text-slate-400"></i> My Bookings</a>
                </li>
                <li><a href="wishlist.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-heart w-5 text-center text-slate-400"></i> Wishlist</a></li>
                <li><a href="notifications.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-bell w-5 text-center text-slate-400"></i> Notifications</a></li>
                <li><a href="reviews.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-star w-5 text-center text-slate-400"></i> My Reviews</a></li>
                <li><a href="change-password.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky-50 text-primary font-semibold transition-colors"><i
                            class="fa-solid fa-key w-5 text-center"></i> Change Password</a></li>
                <li class="mt-4 pt-4 border-t border-slate-100">
                    <a href="../logout.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-rose-500 font-bold hover:bg-rose-50 transition-colors"><i
                            class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i> Logout</a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content Area -->
    <section class="col-span-1 lg:col-span-3 space-y-8">
        <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up max-w-xl">
            <h2 class="text-2xl font-extrabold text-dark mb-2 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-shield-halved"></i></div> Change Password
            </h2>
            <p class="text-slate-500 text-sm mb-8 ml-13">Ensure your account remains safe with a strong, distinct
                password.</p>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 text-rose-600 border border-rose-200 rounded-xl p-4 font-medium text-sm flex items-start gap-3 mb-6 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="change-password.php" class="ml-0 sm:ml-13">
                <?= csrf_field() ?>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Current
                        Password</label>
                    <input type="password" name="current_password" required
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">New Password
                        <span
                            class="text-[10px] text-slate-400 normal-case bg-white border border-slate-200 px-1.5 py-0.5 rounded ml-2">Min
                            12 chars</span></label>
                    <input type="password" name="new_password" required minlength="12"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confirm New
                        Password</label>
                    <input type="password" name="confirm_password" required minlength="12"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>

                <button type="submit"
                    class="w-full sm:w-auto bg-dark hover:bg-slate-800 text-white font-bold py-3.5 px-8 rounded-xl shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock"></i> Update Password
                </button>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>