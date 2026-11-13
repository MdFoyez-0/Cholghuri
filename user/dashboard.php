<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$user = current_user($conn);

// Compute statistics dynamically from MySQL
$total_bookings = 0;
$upcoming_trips = 0;
$completed_trips = 0;
$cancelled_trips = 0;
$wishlist_count = 0;

if ($conn) {
    $today = date('Y-m-d');
    $stats = $conn->prepare("
        SELECT COUNT(*) AS total,
               SUM(travel_date >= ? AND status NOT IN ('Cancelled', 'Rejected')) AS upcoming,
               SUM(status = 'Completed' OR (travel_date < ? AND status = 'Confirmed')) AS completed,
               SUM(status IN ('Cancelled', 'Rejected')) AS cancelled
        FROM bookings WHERE user_id = ?
    ");
    $stats->bind_param('ssi', $today, $today, $user_id);
    $stats->execute();
    $counts = $stats->get_result()->fetch_assoc();
    $total_bookings = (int)($counts['total'] ?? 0);
    $upcoming_trips = (int)($counts['upcoming'] ?? 0);
    $completed_trips = (int)($counts['completed'] ?? 0);
    $cancelled_trips = (int)($counts['cancelled'] ?? 0);

    $wishlistStmt = $conn->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id = ?");
    $wishlistStmt->bind_param('i', $user_id);
    $wishlistStmt->execute();
    $wishlist_count = (int)($wishlistStmt->get_result()->fetch_assoc()['c'] ?? 0);

    // Recent Bookings
    $bookings_stmt = $conn->prepare("
        SELECT b.*, p.title, p.destination, p.duration, p.image 
        FROM bookings b 
        JOIN tour_packages p ON b.package_id = p.id 
        WHERE b.user_id = ? 
        ORDER BY b.id DESC LIMIT 5
    ");
    $bookings_stmt->bind_param('i', $user_id);
    $bookings_stmt->execute();
    $recent_bookings = $bookings_stmt->get_result();

    // Recent notifications
    $notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 4");
    $notif_stmt->bind_param('i', $user_id);
    $notif_stmt->execute();
    $recent_notifications = $notif_stmt->get_result();
}

$pageTitle = 'My Dashboard | CholoGhuri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- User Sidebar -->
    <aside class="col-span-1">
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 reveal-up sticky top-28">
            <div class="text-center pb-6 mb-6 border-b border-slate-100">
                <div
                    class="w-20 h-20 rounded-full mx-auto bg-gradient-to-tr from-sky to-primary text-white text-3xl font-bold flex items-center justify-center mb-4 shadow-lg shadow-primary/20">
                    <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h3 class="text-lg font-bold text-dark m-0"><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                <p class="text-sm text-slate-500 m-0 mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                <span
                    class="inline-block mt-3 bg-emerald/10 text-emerald text-xs font-bold px-3 py-1.5 rounded-full">Active
                    Traveler</span>
            </div>

            <ul class="flex flex-col gap-2">
                <li><a href="dashboard.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky/10 text-sky font-semibold transition-colors"><i
                            class="fa-solid fa-gauge w-5 text-center"></i> Dashboard</a></li>
                <li><a href="profile.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-user-pen w-5 text-center text-slate-400"></i> My Profile</a></li>
                <li><a href="bookings.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-suitcase-rolling w-5 text-center text-slate-400"></i> My Bookings</a>
                </li>
                <li><a href="wishlist.php"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i
                            class="fa-solid fa-heart w-5 text-center text-slate-400"></i> Wishlist
                        (<?= $wishlist_count ?>)</a></li>
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
        <div class="reveal-up">
            <h2 class="text-3xl font-extrabold text-dark mb-2">Welcome back,
                <?= htmlspecialchars($user['name'] ?? 'Traveler') ?>! 👋</h2>
            <p class="text-slate-500 text-sm">Here is the summary of your journey bookings and travel activity across
                Bangladesh.</p>
        </div>

        <!-- 5 Key Dynamic Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 reveal-up shadow-sm">
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col justify-between hover:-translate-y-1 transition-all duration-300 group">
                <div
                    class="w-10 h-10 rounded-xl bg-sky/10 text-sky flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-calendar-check"></i></div>
                <div>
                    <div class="text-2xl font-black text-dark leading-none"><?= $total_bookings ?></div>
                    <div class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Total Bookings</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col justify-between hover:-translate-y-1 transition-all duration-300 group"
                style="transition-delay: 100ms;">
                <div
                    class="w-10 h-10 rounded-xl bg-orange-100 text-orange-500 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-plane-departure"></i></div>
                <div>
                    <div class="text-2xl font-black text-dark leading-none"><?= $upcoming_trips ?></div>
                    <div class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Upcoming</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col justify-between hover:-translate-y-1 transition-all duration-300 group"
                style="transition-delay: 200ms;">
                <div
                    class="w-10 h-10 rounded-xl bg-emerald/10 text-emerald flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="text-2xl font-black text-dark leading-none"><?= $completed_trips ?></div>
                    <div class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Completed</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col justify-between hover:-translate-y-1 transition-all duration-300 group"
                style="transition-delay: 300ms;">
                <div
                    class="w-10 h-10 rounded-xl bg-rose-100 text-rose-500 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-ban"></i></div>
                <div>
                    <div class="text-2xl font-black text-dark leading-none"><?= $cancelled_trips ?></div>
                    <div class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Cancelled</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col justify-between hover:-translate-y-1 transition-all duration-300 group shadow-sm col-span-2 md:col-span-1"
                style="transition-delay: 400ms;">
                <div
                    class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-heart"></i></div>
                <div>
                    <div class="text-2xl font-black text-dark leading-none"><?= $wishlist_count ?></div>
                    <div class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Wishlist</div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-dark flex items-center gap-2"><i
                        class="fa-solid fa-clock-rotate-left text-sky"></i> Recent Bookings</h3>
                <a href="bookings.php"
                    class="text-sm font-semibold text-slate-500 hover:text-dark px-4 py-2 border border-slate-200 rounded-full hover:bg-slate-50 transition-colors">View
                    All <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>

            <?php if (isset($recent_bookings) && $recent_bookings->num_rows > 0): ?>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking ID
                                </th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th
                                    class="py-4 px-5 pr-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while ($b = $recent_bookings->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-5 font-bold text-dark text-sm bg-slate-50/20">
                                        <?= htmlspecialchars($b['booking_code'] ?? ('CHG-' . $b['id'])) ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-sm text-dark leading-tight mb-0.5">
                                            <?= htmlspecialchars($b['title']) ?></div>
                                        <div class="text-xs text-slate-400 font-medium"><i
                                                class="fa-solid fa-location-dot text-sky/70"></i>
                                            <?= htmlspecialchars($b['destination']) ?></div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-sm font-semibold text-dark">
                                            <?= date('d M Y', strtotime($b['travel_date'])) ?></div>
                                        <div class="text-xs text-slate-400 font-medium">
                                            <?= (int) ($b['number_of_travelers'] ?? $b['people']) ?> Persons</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <?php
                                        // Handle Status Styling
                                        $st = strtolower($b['status']);
                                        $badgeClass = 'bg-slate-100 text-slate-600';
                                        if ($st == 'confirmed')
                                            $badgeClass = 'bg-emerald/10 text-emerald';
                                        elseif ($st == 'pending')
                                            $badgeClass = 'bg-orange-100 text-orange-600';
                                        elseif ($st == 'cancelled' || $st == 'rejected')
                                            $badgeClass = 'bg-rose-100 text-rose-600';
                                        ?>
                                        <span
                                            class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wide <?= $badgeClass ?>">
                                            <?= htmlspecialchars($b['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right">
                                        <a href="booking-details.php?id=<?= $b['id'] ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 rounded-lg transition-colors">
                                            <i class="fa-solid fa-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-10 px-6">
                    <div
                        class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 text-3xl mx-auto mb-4">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <p class="text-slate-500 mb-6 font-medium">You haven't booked any tours yet.<br>Beautiful Bangladesh is
                        waiting for you!</p>
                    <a href="../packages.php"
                        class="inline-block bg-dark hover:bg-slate-800 text-white font-bold py-2.5 px-6 rounded-full transition-colors text-sm shadow-md hover:-translate-y-0.5">Explore
                        Tour Packages</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications -->
        <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm reveal-up mb-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-dark flex items-center gap-2"><i
                        class="fa-solid fa-bell text-orange-500"></i> Latest Notifications</h3>
                <a href="notifications.php"
                    class="text-sm font-semibold text-slate-500 hover:text-dark px-4 py-2 border border-slate-200 rounded-full hover:bg-slate-50 transition-colors">All
                    <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>

            <?php if (isset($recent_notifications) && $recent_notifications->num_rows > 0): ?>
                <div class="flex flex-col gap-3">
                    <?php while ($n = $recent_notifications->fetch_assoc()): ?>
                        <?php $isRead = $n['is_read']; ?>
                        <div
                            class="flex gap-4 items-start p-4 rounded-2xl border <?= $isRead ? 'bg-white border-slate-100 shadow-sm' : 'bg-sky/5 border-sky/20 shadow-md' ?> transition-all">
                            <div
                                class="w-10 h-10 rounded-full flex shrink-0 items-center justify-center <?= $isRead ? 'bg-slate-100 text-slate-400' : 'bg-primary text-white shadow-lg shadow-primary/20' ?>">
                                <i class="fa-solid fa-bell text-sm"></i>
                            </div>
                            <div class="flex-1 pt-0.5">
                                <div class="font-bold text-sm <?= $isRead ? 'text-dark' : 'text-dark' ?>">
                                    <?= htmlspecialchars($n['title']) ?></div>
                                <div
                                    class="text-sm mt-1 leading-relaxed <?= $isRead ? 'text-slate-500' : 'text-slate-600 font-medium' ?>">
                                    <?= htmlspecialchars($n['message']) ?></div>
                                <div class="text-[11px] font-bold text-slate-400 mt-2 uppercase tracking-wide">
                                    <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-6 text-slate-500 text-sm font-medium">No new notifications.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>