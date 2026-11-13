<?php
require_once __DIR__ . '/includes/db.php';

$id = (int) ($_GET['id'] ?? $_POST['package_id'] ?? 0);
$package = null;
$reviews = [];
$avg_rating = 5.0;
$in_wishlist = false;

if ($conn && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();

    // Check wishlist status for logged-in user
    if (is_logged_in()) {
        $u_id = (int) $_SESSION['user_id'];
        $w_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND package_id = ?");
        $w_stmt->bind_param('ii', $u_id, $id);
        $w_stmt->execute();
        if ($w_stmt->get_result()->num_rows > 0) {
            $in_wishlist = true;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_toggle'])) {
            verify_csrf();
            if ($in_wishlist) {
                $toggle = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND package_id = ?");
                $toggle->bind_param('ii', $u_id, $id);
                $toggle->execute();
                set_flash('info', 'Package removed from your wishlist.');
            } else {
                $toggle = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, package_id) VALUES (?, ?)");
                $toggle->bind_param('ii', $u_id, $id);
                $toggle->execute();
                set_flash('success', 'Package added to your wishlist!');
            }
            redirect("package-details.php?id=$id");
        }
    }

    // Fetch reviews
    $r_stmt = $conn->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.package_id = ? AND r.status = 'approved' 
        ORDER BY r.id DESC
    ");
    $r_stmt->bind_param('i', $id);
    $r_stmt->execute();
    $reviews = $r_stmt->get_result();

    // Average rating
    $avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_r FROM reviews WHERE package_id = ? AND status = 'approved'");
    $avg_stmt->bind_param('i', $id);
    $avg_stmt->execute();
    $avg_res = $avg_stmt->get_result();
    if ($row = $avg_res->fetch_assoc()) {
        if ($row['avg_r'])
            $avg_rating = round((float) $row['avg_r'], 1);
    }
}

if (!$package) {
    http_response_code(404);
    $pageTitle = 'Package Not Found | CholoGhuri';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container empty"><p>Package not found or inactive.</p><a href="packages.php" class="btn">Explore Tours</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $package['title'] . ' | CholoGhuri';
$supportPhone = get_setting($conn, 'contact_phone', '');
require_once __DIR__ . '/includes/header.php';
?>

<section class="py-12 md:py-20 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex text-sm text-slate-500 mb-8 font-medium" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="index.php" class="hover:text-dark transition-colors">Home</a>
                </li>
                <li><span class="mx-2 text-slate-300">/</span></li>
                <li><a href="packages.php" class="hover:text-dark transition-colors">Packages</a></li>
                <li><span class="mx-2 text-slate-300">/</span></li>
                <li><a href="packages.php?destination=<?= urlencode($package['destination']) ?>"
                        class="hover:text-dark transition-colors"><?= htmlspecialchars($package['destination']) ?></a>
                </li>
                <li><span class="mx-2 text-slate-300">/</span></li>
                <li class="text-sky font-bold md:truncate max-w-xs" aria-current="page">
                    <?= htmlspecialchars($package['title']) ?></li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 xl:gap-12 items-start">
            <!-- Main Package Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Real Destination Hero Image -->
                <div class="relative rounded-[2rem] overflow-hidden shadow-lg border border-slate-100 group">
                    <img src="<?= htmlspecialchars($package['image']) ?>"
                        alt="<?= htmlspecialchars($package['title']) ?>"
                        class="w-full h-80 md:h-[450px] object-cover group-hover:scale-105 transition-transform duration-700 ease-in-out"
                        onerror="this.src='images/destinations/coxsbazar.jpg'">

                    <span
                        class="absolute top-6 left-6 bg-white/95 backdrop-blur-md text-dark text-sm font-extrabold px-5 py-2 rounded-full shadow-sm">
                        📍 <?= htmlspecialchars($package['destination']) ?>
                    </span>

                    <?php if (is_logged_in()): ?>
                        <form method="POST" action="package-details.php?id=<?= (int)$package['id'] ?>" class="absolute top-6 right-6">
                            <?= csrf_field() ?><input type="hidden" name="package_id" value="<?= (int)$package['id'] ?>"><input type="hidden" name="wishlist_toggle" value="1">
                            <button class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-xl <?= $in_wishlist ? 'text-rose-500 hover:text-slate-400' : 'text-slate-300 hover:text-rose-500' ?> shadow-lg transition-colors hover:scale-110 transform"
                                title="<?= $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>"><i class="fa-solid fa-heart"></i></button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="reveal-up">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-dark mb-4 leading-tight">
                        <?= htmlspecialchars($package['title']) ?></h1>
                    <div class="flex flex-wrap gap-4 md:gap-6 text-sm font-semibold text-slate-500 items-center">
                        <span class="flex items-center gap-2 bg-sky/10 text-sky px-3 py-1.5 rounded-lg"><i
                                class="fa-solid fa-clock"></i> <?= htmlspecialchars($package['duration']) ?></span>
                        <span class="flex items-center gap-2 bg-emerald/10 text-emerald px-3 py-1.5 rounded-lg"><i
                                class="fa-solid fa-chair"></i> <?= (int) $package['available_seats'] ?> Seats
                            Available</span>
                        <span class="flex items-center gap-2 bg-amber-100 text-amber-600 px-3 py-1.5 rounded-lg"><i
                                class="fa-solid fa-star text-amber-500"></i> <?= $avg_rating ?> Rating
                            (<?= $reviews ? $reviews->num_rows : 0 ?> reviews)</span>
                    </div>
                </div>

                <!-- Tour Description -->
                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up">
                    <h3 class="text-xl font-bold text-dark mb-4 flex items-center gap-3"><i
                            class="fa-solid fa-align-left text-sky"></i> About This Tour</h3>
                    <p class="text-slate-600 leading-relaxed text-sm md:text-base">
                        <?= nl2br(htmlspecialchars($package['description'])) ?></p>
                </div>

                <!-- Day-by-Day Itinerary -->
                <?php if (!empty($package['itinerary'])): ?>
                    <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up">
                        <h3 class="text-xl font-bold text-dark mb-4 flex items-center gap-3"><i
                                class="fa-solid fa-calendar-days text-primary"></i> Tour Itinerary</h3>
                        <div
                            class="text-slate-600 leading-relaxed text-sm md:text-base whitespace-pre-line border-l-2 border-slate-100 pl-4 py-2">
                            <?= htmlspecialchars($package['itinerary']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inclusions & Exclusions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 reveal-up">
                    <div
                        class="bg-emerald-50 border border-emerald-100 rounded-3xl p-8 transition-transform hover:-translate-y-1">
                        <h4 class="text-lg font-bold text-emerald-800 mb-4 flex items-center gap-2"><i
                                class="fa-solid fa-circle-check text-emerald-500 text-xl"></i> What's Included</h4>
                        <div class="text-emerald-700 text-sm leading-loose whitespace-pre-line font-medium">
                            <?= htmlspecialchars($package['included_services'] ?: "• AC Bus Transportation\n• Hotel / Resort Accommodation\n• Daily Breakfast\n• Certified Local Tour Guide") ?>
                        </div>
                    </div>

                    <div
                        class="bg-rose-50 border border-rose-100 rounded-3xl p-8 transition-transform hover:-translate-y-1">
                        <h4 class="text-lg font-bold text-rose-800 mb-4 flex items-center gap-2"><i
                                class="fa-solid fa-circle-xmark text-rose-500 text-xl"></i> What's Excluded</h4>
                        <div class="text-rose-700 text-sm leading-loose whitespace-pre-line font-medium">
                            <?= htmlspecialchars($package['excluded_services'] ?: "• Extra personal food orders\n• Water sports & personal activities\n• Personal shopping & tips") ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Reviews for this tour -->
                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up mb-10">
                    <h3 class="text-xl font-bold text-dark mb-6 flex items-center gap-3"><i
                            class="fa-solid fa-comments text-amber-500"></i> Traveler Reviews</h3>

                    <?php if ($reviews && $reviews->num_rows > 0): ?>
                        <div class="space-y-6">
                            <?php while ($r = $reviews->fetch_assoc()): ?>
                                <div class="border-b border-slate-100 pb-6 last:border-0 last:pb-0">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold">
                                                <?= htmlspecialchars(strtoupper(substr($r['user_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <strong
                                                class="text-dark font-semibold text-sm"><?= htmlspecialchars($r['user_name']) ?></strong>
                                        </div>
                                        <span
                                            class="text-amber-400 tracking-widest text-sm"><?= str_repeat('★', (int) $r['rating']) ?></span>
                                    </div>
                                    <p class="text-slate-600 text-sm leading-relaxed italic">
                                        "<?= htmlspecialchars($r['comment']) ?>"</p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <i class="fa-solid fa-ranking-star text-4xl text-slate-200 mb-3"></i>
                            <p class="text-slate-500 text-sm font-medium">Be the first verified traveler to complete this
                                tour and share a review!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Sticky Sidebar -->
            <div class="lg:col-span-1 lg:sticky lg:top-28">
                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-xl reveal-up">
                    <div class="mb-6">
                        <span
                            class="text-[11px] font-extrabold uppercase text-slate-400 tracking-widest block mb-1">Tour
                            Package Price</span>
                        <div class="text-4xl text-dark font-extrabold flex items-baseline gap-1">
                            ৳<?= number_format($package['price']) ?> <span class="text-sm font-medium text-slate-400">/
                                person</span>
                        </div>
                    </div>

                    <div
                        class="bg-slate-50 rounded-2xl p-5 mb-8 text-sm font-medium text-slate-600 space-y-4 border border-slate-100">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Duration</span>
                            <strong class="text-dark"><?= htmlspecialchars($package['duration']) ?></strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Available Seats</span>
                            <strong
                                class="text-sky bg-sky/10 px-2 py-1 rounded-md"><?= (int) $package['available_seats'] ?>
                                remaining</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Confirmation</span>
                            <strong class="text-emerald font-bold flex items-center gap-1"><i
                                    class="fa-solid fa-bolt"></i> Instant E-Voucher</strong>
                        </div>
                    </div>

                    <?php if (is_logged_in()): ?>
                        <a href="booking.php?package_id=<?= (int) $package['id'] ?>"
                            class="block w-full text-center bg-gradient-to-r from-primary to-rose-500 hover:from-rose-500 hover:to-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all hover:-translate-y-1 mb-3 text-sm md:text-base">
                            <i class="fa-solid fa-plane-departure me-1"></i> Book This Tour Now
                        </a>
                        <form method="POST" action="package-details.php?id=<?= (int)$package['id'] ?>">
                            <?= csrf_field() ?><input type="hidden" name="package_id" value="<?= (int)$package['id'] ?>"><input type="hidden" name="wishlist_toggle" value="1">
                            <button class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3.5 rounded-xl transition-colors text-sm">
                                <i class="fa-solid fa-heart <?= $in_wishlist ? 'text-rose-500' : 'text-slate-400' ?> me-1"></i>
                                <?= $in_wishlist ? 'Saved in Wishlist' : 'Add to Wishlist' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="mb-4 text-center">
                            <p class="text-sm text-slate-500 font-medium mb-4">Please sign in or create an account to book
                                this journey.</p>
                            <a href="login.php?redirect=booking.php?package_id=<?= (int) $package['id'] ?>"
                                class="block w-full text-center bg-dark hover:bg-slate-800 text-white font-bold py-4 rounded-xl shadow-md transition-all hover:-translate-y-1 mb-3 text-sm">
                                <i class="fa-regular fa-user me-1"></i> Login to Book
                            </a>
                            <a href="register.php"
                                class="block w-full text-center bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-dark font-bold py-3.5 rounded-xl transition-colors text-sm">
                                Create Free Account
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($supportPhone !== ''): ?>
                        <div class="mt-8 pt-6 border-t border-slate-100 text-center text-sm font-medium text-slate-500">
                            <i class="fa-solid fa-headset text-sky mb-1 text-lg block"></i> Need help? Contact
                            <span class="text-dark font-bold"><?= htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>