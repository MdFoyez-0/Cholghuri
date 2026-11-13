<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$user = current_user($conn);

// Remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove']) && $conn) {
    verify_csrf();
    $pkg_id = (int) $_POST['remove'];
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND package_id = ?");
    $del->bind_param('ii', $user_id, $pkg_id);
    $del->execute();
    set_flash('success', 'Package removed from your wishlist.');
    redirect('wishlist.php');
}

// Fetch wishlist packages
$wishlist_items = [];
if ($conn) {
    $stmt = $conn->prepare("
        SELECT p.*, w.created_at as saved_at 
        FROM wishlist w 
        JOIN tour_packages p ON w.package_id = p.id 
        WHERE w.user_id = ? 
        ORDER BY w.id DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wishlist_items = $stmt->get_result();
}

$pageTitle = 'My Wishlist | CholoGhuri';
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
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky-50 text-primary font-semibold transition-colors"><i
                            class="fa-solid fa-heart w-5 text-center"></i> Wishlist</a></li>
                <li><a href="notifications.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-bell w-5 text-center text-slate-400"></i> Notifications</a></li>
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
        <div class="reveal-up mb-6">
            <h2 class="text-3xl font-extrabold text-dark mb-2">My Saved Tours (Wishlist)</h2>
            <p class="text-slate-500 text-sm">Keep track of dream adventures across Bangladesh and book whenever you're
                ready.</p>
        </div>

        <?php if ($wishlist_items && $wishlist_items->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 reveal-up">
                <?php while ($p = $wishlist_items->fetch_assoc()): ?>
                    <article
                        class="bg-white rounded-2xl border border-slate-100 shadow-[0_4px_18px_rgba(11,85,117,0.08)] overflow-hidden flex flex-col group hover:-translate-y-1 transition-all">
                        <div class="relative h-48 overflow-hidden">
                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                src="../<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"
                                onerror="this.src='../images/destinations/coxsbazar.jpg'">
                            <span
                                class="absolute top-3 left-3 bg-white/95 backdrop-blur text-dark px-2.5 py-1 rounded-md text-[11px] font-black tracking-wide flex items-center gap-1 shadow-sm">
                                <i class="fa-solid fa-map-pin text-primary"></i> <?= htmlspecialchars($p['destination']) ?>
                            </span>
                            <form method="POST" action="wishlist.php" class="absolute top-3 right-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="remove" value="<?= (int) $p['id'] ?>">
                                <button type="submit" title="Remove from wishlist"
                                    class="w-8 h-8 rounded-full bg-white text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-colors shadow-sm">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </form>
                        </div>
                        <div class="p-5 flex flex-col grow">
                            <h3
                                class="font-black text-lg text-dark mb-3 line-clamp-2 leading-tight group-hover:text-primary transition-colors">
                                <?= htmlspecialchars($p['title']) ?></h3>
                            <div class="flex items-center gap-4 text-xs font-bold text-slate-500 mb-4">
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-sky-500"></i>
                                    <?= htmlspecialchars($p['duration']) ?></span>
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-couch text-sky-500"></i>
                                    <?= (int) $p['available_seats'] ?> seats</span>
                            </div>
                            <div class="mt-auto pt-4 border-t border-slate-50 mb-5">
                                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Price</div>
                                <div class="font-black text-xl text-primary mt-0.5">৳<?= number_format($p['price']) ?> <span
                                        class="text-slate-400 text-xs font-semibold normal-case">/ person</span></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-auto">
                                <a class="bg-primary hover:bg-sky-600 text-white font-bold py-2.5 px-4 rounded-xl text-sm text-center shadow-sm shadow-primary/30 transition-all flex items-center justify-center"
                                    href="../booking.php?package_id=<?= (int) $p['id'] ?>">
                                    Book Now
                                </a>
                                <a class="text-slate-600 hover:text-dark font-bold text-sm text-center py-2 px-4 border border-slate-200 hover:border-slate-300 rounded-xl transition-all hover:bg-slate-50 flex items-center justify-center"
                                    href="../package-details.php?id=<?= (int) $p['id'] ?>">
                                    Details
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-16 text-center border border-slate-100 shadow-sm reveal-up">
                <div
                    class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid fa-heart-crack"></i>
                </div>
                <h3 class="text-2xl font-bold text-dark mb-3">Your Wishlist is Empty</h3>
                <p class="text-slate-500 mb-8 max-w-md mx-auto">Browse our authentic tour packages and tap the heart icon to
                    save your favorites.</p>
                <a href="../packages.php"
                    class="bg-primary hover:bg-sky-600 text-white font-bold py-3.5 px-8 rounded-xl shadow-sm shadow-primary/30 transition-all active:scale-95 inline-block">
                    Discover Tour Packages
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>