<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$user = current_user($conn);

// Mark all as read action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all']) && $conn) {
    verify_csrf();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    set_flash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

// Fetch all notifications
$notifications = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $notifications = $stmt->get_result();
}

$pageTitle = 'Notifications | CholoGhuri';
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
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky-50 text-primary font-semibold transition-colors"><i
                            class="fa-solid fa-bell w-5 text-center"></i> Notifications</a></li>
                <li><a href="reviews.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-star w-5 text-center text-slate-400"></i> My Reviews</a></li>
                <li><a href="change-password.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-key w-5 text-center text-slate-400"></i> Change Password</a></li>
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
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 reveal-up">
            <div>
                <h2 class="text-3xl font-extrabold text-dark mb-2">Notifications Center</h2>
                <p class="text-slate-500 text-sm">Updates regarding your bookings, payments, and trip schedules.</p>
            </div>
            <form method="POST" action="notifications.php">
                <?= csrf_field() ?>
                <input type="hidden" name="mark_all" value="1">
                <button type="submit"
                    class="text-slate-600 hover:text-dark font-bold text-sm text-center py-2.5 px-5 border border-slate-200 hover:border-slate-300 rounded-xl transition-all hover:bg-slate-50 flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-check-double"></i> Mark all as read
                </button>
            </form>
        </div>

        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div class="flex flex-col gap-4 reveal-up">
                <?php while ($n = $notifications->fetch_assoc()): ?>
                    <div
                        class="rounded-2xl p-5 border transition-all duration-300 flex items-start gap-4 sm:gap-6 hover:-translate-y-0.5 <?= $n['is_read'] ? 'bg-white border-slate-100 shadow-sm' : 'bg-sky-50/50 border-sky-100 shadow-[0_2px_15px_rgba(14,165,233,0.06)]' ?>">
                        <div
                            class="w-12 h-12 rounded-full shrink-0 flex items-center justify-center text-lg <?= $n['is_read'] ? 'bg-slate-50' : 'bg-white shadow-sm' ?>">
                            <?php if ($n['type'] === 'booking'): ?>
                                <i class="fa-solid fa-suitcase-rolling text-blue-500"></i>
                            <?php elseif ($n['type'] === 'payment'): ?>
                                <i class="fa-solid fa-credit-card text-emerald-500"></i>
                            <?php elseif ($n['type'] === 'warning'): ?>
                                <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-bell text-sky-500"></i>
                            <?php endif; ?>
                        </div>
                        <div class="grow">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-1 mb-1">
                                <h4 class="text-base font-black text-dark <?= !$n['is_read'] ? 'text-primary' : '' ?>">
                                    <?= htmlspecialchars($n['title']) ?></h4>
                                <small
                                    class="text-slate-400 font-semibold text-[11px] uppercase tracking-wider flex items-center gap-1">
                                    <i class="fa-regular fa-clock"></i> <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                                </small>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <div
                                class="w-2.5 h-2.5 rounded-full bg-primary shrink-0 self-center hidden sm:block shadow-[0_0_8px_rgba(14,165,233,0.4)]">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-16 text-center border border-slate-100 shadow-sm reveal-up">
                <div
                    class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <h3 class="text-2xl font-bold text-dark mb-3">No Notifications</h3>
                <p class="text-slate-500 mb-0">You have no travel notifications right now.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>