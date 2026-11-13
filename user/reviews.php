<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$user = current_user($conn);
$error = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $comment = trim(input_string($_POST, 'comment'));

    if ($booking_id <= 0 || $rating === false || $rating < 1 || $rating > 5 || $comment === '' || strlen($comment) > 2000) {
        $error = 'Please provide both a star rating and your feedback review.';
    } else {
        if ($conn) {
            // Verify booking belongs to user and is Completed or travel_date passed
            $check = $conn->prepare("
                SELECT b.id, b.package_id, b.status 
                FROM bookings b 
                WHERE b.id = ? AND b.user_id = ? 
                AND (b.status = 'Completed' OR b.travel_date <= CURDATE())
            ");
            $check->bind_param('ii', $booking_id, $user_id);
            $check->execute();
            $b_res = $check->get_result()->fetch_assoc();

            if ($b_res) {
                $pkg_id = (int) $b_res['package_id'];
                // Check if already reviewed for this booking
                $rev_chk = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND booking_id = ?");
                $rev_chk->bind_param('ii', $user_id, $booking_id);
                $rev_chk->execute();
                if ($rev_chk->get_result()->num_rows > 0) {
                    $error = 'You have already submitted a review for this completed trip.';
                } else {
                    $ins = $conn->prepare("INSERT INTO reviews (user_id, package_id, booking_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                    $ins->bind_param('iiiis', $user_id, $pkg_id, $booking_id, $rating, $comment);
                    if ($ins->execute()) {
                        set_flash('success', 'Thank you! Your verified review has been submitted.');
                        redirect('reviews.php');
                    }
                }
            } else {
                $error = 'You can only review tours that you have successfully completed.';
            }
        }
    }
}

// Fetch completed trips eligible for review
$eligible_trips = [];
$my_reviews = [];
if ($conn) {
    // Trips eligible to review that haven't been reviewed yet
    $el_stmt = $conn->prepare("
        SELECT b.id as booking_id, b.booking_code, b.travel_date, p.id as package_id, p.title as package_title
        FROM bookings b
        JOIN tour_packages p ON b.package_id = p.id
        WHERE b.user_id = ? 
        AND (b.status = 'Completed' OR b.travel_date <= CURDATE())
        AND b.id NOT IN (SELECT booking_id FROM reviews WHERE user_id = ? AND booking_id IS NOT NULL)
    ");
    $el_stmt->bind_param('ii', $user_id, $user_id);
    $el_stmt->execute();
    $eligible_trips = $el_stmt->get_result();

    // Already submitted reviews by this user
    $rev_stmt = $conn->prepare("
        SELECT r.*, p.title as package_title, p.destination 
        FROM reviews r 
        JOIN tour_packages p ON r.package_id = p.id 
        WHERE r.user_id = ? 
        ORDER BY r.id DESC
    ");
    $rev_stmt->bind_param('i', $user_id);
    $rev_stmt->execute();
    $my_reviews = $rev_stmt->get_result();
}

$pageTitle = 'My Reviews | CholoGhuri';
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
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky-50 text-primary font-semibold transition-colors"><i
                            class="fa-solid fa-star w-5 text-center"></i> My Reviews</a></li>
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
            <h2 class="text-3xl font-extrabold text-dark mb-2">My Verified Tour Reviews</h2>
            <p class="text-slate-500 text-sm">Share your genuine journey experience with fellow Bangladeshi travelers.
            </p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-rose-50 text-rose-600 border border-rose-200 rounded-xl p-4 font-medium text-sm flex items-start gap-3 mb-6 shadow-sm reveal-up">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Write a Review Form if eligible trips exist -->
        <?php if ($eligible_trips && $eligible_trips->num_rows > 0): ?>
            <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up mb-8">
                <h3 class="text-xl font-extrabold text-dark mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-pen-to-square"></i></div> Rate a Completed Tour
                </h3>

                <form method="POST" action="reviews.php" class="sm:ml-13">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select
                                Completed Tour</label>
                            <div class="relative">
                                <select name="booking_id" required
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none pr-10">
                                    <option value="">-- Choose a trip --</option>
                                    <?php while ($trip = $eligible_trips->fetch_assoc()): ?>
                                        <option value="<?= $trip['booking_id'] ?>">
                                            <?= htmlspecialchars($trip['package_title']) ?>
                                            (<?= date('d M Y', strtotime($trip['travel_date'])) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Rating</label>
                            <div class="relative">
                                <select name="rating" required
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none pr-10">
                                    <option value="5">⭐⭐⭐⭐⭐ (Exceptional)</option>
                                    <option value="4">⭐⭐⭐⭐ (Very Good)</option>
                                    <option value="3">⭐⭐⭐ (Average)</option>
                                    <option value="2">⭐⭐ (Below Expectations)</option>
                                    <option value="1">⭐ (Poor)</option>
                                </select>
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Your Experience
                            Review</label>
                        <textarea name="comment" rows="4" required
                            placeholder="Describe hotel condition, tour guide behavior, itinerary fulfillment, food quality..."
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm resize-y"></textarea>
                    </div>

                    <button type="submit"
                        class="bg-primary hover:bg-sky-600 text-white font-bold py-3.5 px-8 rounded-xl shadow-sm shadow-primary/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- List of User's Past Reviews -->
        <h3 class="text-xl font-extrabold text-dark mb-4 flex items-center gap-3 reveal-up">
            <i class="fa-solid fa-clock-rotate-left text-sky-500"></i> Reviews You've Shared
        </h3>

        <?php if ($my_reviews && $my_reviews->num_rows > 0): ?>
            <div class="grid grid-cols-1 gap-6 reveal-up">
                <?php while ($r = $my_reviews->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                        <div
                            class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4 pb-4 border-b border-slate-50">
                            <div>
                                <h4 class="font-black text-lg text-dark mb-1"><?= htmlspecialchars($r['package_title']) ?></h4>
                                <div class="text-xs text-sky-500 font-bold flex items-center gap-1.5">
                                    <i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($r['destination']) ?>
                                </div>
                            </div>
                            <div class="text-amber-400 text-base flex gap-1 bg-amber-50 px-3 py-1.5 rounded-lg shrink-0">
                                <?= str_repeat('<i class="fa-solid fa-star"></i>', (int) $r['rating']) ?>
                                <?= str_repeat('<i class="fa-regular fa-star text-amber-200"></i>', 5 - (int) $r['rating']) ?>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-4 p-4 bg-slate-50 rounded-xl italic font-medium">
                            "<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                        <div class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar-check"></i> Submitted on
                            <?= date('d M Y', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-16 text-center border border-slate-100 shadow-sm reveal-up">
                <div
                    class="w-20 h-20 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid fa-comment-dots"></i>
                </div>
                <h3 class="text-2xl font-bold text-dark mb-3">No Reviews Yet</h3>
                <p class="text-slate-500 mb-0 max-w-md mx-auto">Once you complete a booked tour, you can write verified
                    reviews here to guide fellow travelers.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>