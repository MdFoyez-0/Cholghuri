<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);

// Approve, Hide, Delete review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['review_id']) && $conn) {
    verify_csrf();
    $r_id = (int)$_POST['review_id'];
    $act = $_POST['action'];

    if ($act === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $r_id);
        $stmt->execute();
        log_admin_action($conn, $admin['id'], 'Approve Review', "Approved review ID $r_id");
        set_flash('success', 'Review approved and published.');
    } elseif ($act === 'hide') {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'hidden' WHERE id = ?");
        $stmt->bind_param('i', $r_id);
        $stmt->execute();
        log_admin_action($conn, $admin['id'], 'Hide Review', "Hid review ID $r_id");
        set_flash('warning', 'Review hidden from public view.');
    } elseif ($act === 'delete') {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param('i', $r_id);
        $stmt->execute();
        log_admin_action($conn, $admin['id'], 'Delete Review', "Deleted review ID $r_id");
        set_flash('success', 'Review deleted permanently.');
    }
    redirect('reviews.php');
}

$reviews = [];
if ($conn) {
    $reviews = $conn->query("
        SELECT r.*, u.name as user_name, u.email as user_email, p.title as package_title, p.destination
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN tour_packages p ON r.package_id = p.id
        ORDER BY r.id DESC
    ");
}

$pageTitle = 'Manage Reviews | CholoGhuri Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center px-6 sticky top-0 z-40 justify-between shadow-sm">
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="flex items-center gap-2">
                <img src="../images/logo.png" alt="Logo" class="h-8">
                <span class="text-xl font-black text-dark tracking-tight">Cholo<span class="text-primary">Ghuri</span> <small class="text-[10px] text-orange-600 font-extrabold bg-orange-100 px-2 py-0.5 rounded-full ml-1 align-top uppercase tracking-wider">Admin</small></span>
            </a>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-dark leading-tight"><?= htmlspecialchars($admin['name']) ?></div>
                <div class="text-xs font-semibold text-slate-400"><?= htmlspecialchars($admin['role']) ?></div>
            </div>
            <a href="logout.php" class="w-9 h-9 flex items-center justify-center rounded-lg bg-rose-50 text-rose-500 border border-rose-100 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Logout">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-64px)]">
        <!-- Admin Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 p-4 hidden lg:block shrink-0 shadow-sm z-30">
            <div class="flex flex-col gap-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-gauge w-5 text-center text-slate-400"></i> Dashboard
                </a>
                
                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">Management</div>
                <a href="users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-users w-5 text-center text-slate-400"></i> Users
                </a>
                <a href="destinations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-map-location-dot w-5 text-center text-slate-400"></i> Destinations
                </a>
                <a href="packages.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-route w-5 text-center text-slate-400"></i> Packages
                </a>
                <a href="bookings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-suitcase-rolling w-5 text-center text-slate-400"></i> Bookings
                </a>
                <a href="payments.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-credit-card w-5 text-center text-slate-400"></i> Payments
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-star w-5 text-center"></i> Reviews
                </a>
                <a href="cancellations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-ban w-5 text-center text-slate-400"></i> Cancellation Requests
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">Reports & Analytics</div>
                <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-chart-line w-5 text-center text-emerald-400"></i> Analytics & Reports
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">System</div>
                <a href="activity-logs.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-shield-halved w-5 text-center text-slate-400"></i> Activity Logs
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-sliders w-5 text-center text-slate-400"></i> Settings
                </a>
            </div>
        </aside>

        <!-- Dashboard Content -->
        <main class="flex-1 p-6 md:p-8 max-w-[1600px]">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-dark mb-1">Customer Reviews & Ratings</h1>
                <p class="text-slate-500 text-sm font-medium">Moderate and review authentic feedback submitted by travelers who completed tours.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tour Package</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Traveler</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rating</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-1/3">Customer Feedback</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($reviews && $reviews->num_rows > 0): ?>
                                <?php while ($r = $reviews->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4 min-w-[200px]">
                                            <div class="text-sm font-bold text-dark line-clamp-1"><?= htmlspecialchars($r['package_title']) ?></div>
                                            <div class="text-xs font-medium text-slate-500 mt-0.5"><i class="fa-solid fa-map-pin text-slate-400 mr-1"></i><?= htmlspecialchars($r['destination']) ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm font-bold text-dark whitespace-nowrap"><?= htmlspecialchars($r['user_name']) ?></div>
                                            <div class="text-xs font-medium text-slate-500 whitespace-nowrap"><?= htmlspecialchars($r['user_email']) ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-1.5 text-sm font-bold text-amber-500 whitespace-nowrap">
                                                <div class="flex gap-0.5">
                                                    <?= str_repeat('<i class="fa-solid fa-star text-[10px]"></i>', (int)$r['rating']) ?>
                                                </div>
                                                <span class="text-slate-600">(<?= $r['rating'] ?>/5)</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-sm text-slate-600 font-medium max-w-xs break-words">
                                            <?= htmlspecialchars($r['comment']) ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $stClass = $r['status'] === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $stClass ?>">
                                                <?= ucfirst(htmlspecialchars($r['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <?php if ($r['status'] !== 'approved'): ?>
                                                    <form method="POST" action="reviews.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="approve"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-500 hover:text-white transition-colors" title="Approve">
                                                            <i class="fa-solid fa-check text-xs"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="reviews.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="hide"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:border-slate-300 hover:text-dark transition-colors" title="Hide">
                                                            <i class="fa-solid fa-eye-slash text-xs"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="reviews.php" onsubmit="return confirm('Delete this review permanently?')" class="inline-block">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-rose-500 hover:border-rose-300 hover:bg-rose-50 transition-colors" title="Delete">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="py-12 text-center text-sm text-slate-500 font-medium">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3"><i class="fa-regular fa-comment-dots"></i></div>
                                    <div class="block">No reviews submitted yet.</div>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
