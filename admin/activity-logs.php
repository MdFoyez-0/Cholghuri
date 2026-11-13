<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$logs = [];
if ($conn) {
    $logs = $conn->query("
        SELECT l.*, a.name as admin_name, a.email as admin_email
        FROM admin_logs l
        JOIN admins a ON l.admin_id = a.id
        ORDER BY l.id DESC LIMIT 100
    ");
}

$pageTitle = 'Activity Logs | CholoGhuri Admin';
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
                <a href="reviews.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-star w-5 text-center text-slate-400"></i> Reviews
                </a>
                <a href="cancellations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-ban w-5 text-center text-slate-400"></i> Cancellation Requests
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">Reports & Analytics</div>
                <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-chart-line w-5 text-center text-slate-400"></i> Analytics & Reports
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">System</div>
                <a href="activity-logs.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-shield-halved w-5 text-center"></i> Activity Logs
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-sliders w-5 text-center text-slate-400"></i> Settings
                </a>
            </div>
        </aside>

        <!-- Dashboard Content -->
        <main class="flex-1 p-6 md:p-8 max-w-[1600px]">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-dark mb-1">Admin Activity & Audit Logs</h1>
                <p class="text-slate-500 text-sm font-medium">Immutable security audit trail of administrative modifications across the system.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-16">#</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Admin User</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Details</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">IP Address</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($logs && $logs->num_rows > 0): ?>
                                <?php while ($l = $logs->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 px-4 text-xs font-bold text-slate-400">#<?= $l['id'] ?></td>
                                        <td class="py-3 px-4 text-sm font-bold text-slate-700 whitespace-nowrap"><?= htmlspecialchars($l['admin_name']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sky-100 text-sky-700">
                                                <?= htmlspecialchars($l['action']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm font-medium text-slate-600 line-clamp-2" title="<?= htmlspecialchars($l['description']) ?>">
                                            <?= htmlspecialchars($l['description']) ?>
                                        </td>
                                        <td class="py-3 px-4 text-center text-xs font-mono font-medium text-slate-500">
                                            <span class="bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($l['ip_address'] ?: '127.0.0.1') ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-xs font-medium text-slate-500 whitespace-nowrap text-right">
                                            <?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="py-12 text-center text-sm text-slate-500 font-medium">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3"><i class="fa-solid fa-server"></i></div>
                                    <div class="block">No logs recorded.</div>
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
