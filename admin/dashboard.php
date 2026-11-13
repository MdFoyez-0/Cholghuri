<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);

// Compute live real metrics from MySQL
$total_users = 0;
$total_packages = 0;
$total_destinations = 0;
$total_bookings = 0;
$pending_bookings = 0;
$confirmed_bookings = 0;
$completed_bookings = 0;
$cancelled_bookings = 0;
$total_revenue = 0;
$pending_payments = 0;

$monthly_bookings = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
$monthly_revenue = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
$popular_destinations = [];
$recent_bookings = [];
$recent_logs = [];

if ($conn) {
    // Users count
    $r = $conn->query("SELECT COUNT(*) as c FROM users");
    if ($r) $total_users = (int)$r->fetch_assoc()['c'];

    // Packages count
    $r = $conn->query("SELECT COUNT(*) as c FROM tour_packages");
    if ($r) $total_packages = (int)$r->fetch_assoc()['c'];

    // Destinations count
    $r = $conn->query("SELECT COUNT(*) as c FROM destinations");
    if ($r) $total_destinations = (int)$r->fetch_assoc()['c'];

    // Bookings breakdown
    $r = $conn->query("SELECT COUNT(*) as c FROM bookings");
    if ($r) $total_bookings = (int)$r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'Pending'");
    if ($r) $pending_bookings = (int)$r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'Confirmed'");
    if ($r) $confirmed_bookings = (int)$r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'Completed'");
    if ($r) $completed_bookings = (int)$r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'Cancelled'");
    if ($r) $cancelled_bookings = (int)$r->fetch_assoc()['c'];

    // Revenue
    $r = $conn->query("SELECT SUM(amount) as s FROM payments WHERE status = 'verified'");
    if ($r) $total_revenue = (float)($r->fetch_assoc()['s'] ?? 0);

    // Pending Payments
    $r = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending'");
    if ($r) $pending_payments = (int)$r->fetch_assoc()['c'];

    // Monthly bookings & revenue for current year
    $year = date('Y');
    $m_res = $conn->query("
        SELECT MONTH(travel_date) as m, COUNT(*) as cnt, SUM(total_amount) as rev 
        FROM bookings 
        WHERE YEAR(travel_date) = $year AND status NOT IN ('Cancelled', 'Rejected')
        GROUP BY MONTH(travel_date)
    ");
    if ($m_res) {
        while ($row = $m_res->fetch_assoc()) {
            $m_idx = (int)$row['m'] - 1;
            if ($m_idx >= 0 && $m_idx < 12) {
                $monthly_bookings[$m_idx] = (int)$row['cnt'];
                $monthly_revenue[$m_idx] = (float)$row['rev'];
            }
        }
    }

    // Popular destinations
    $p_res = $conn->query("
        SELECT destination, COUNT(*) as cnt 
        FROM tour_packages p 
        JOIN bookings b ON p.id = b.package_id 
        GROUP BY destination 
        ORDER BY cnt DESC LIMIT 5
    ");
    if ($p_res) {
        while ($row = $p_res->fetch_assoc()) {
            $popular_destinations[] = $row;
        }
    }

    // Recent 5 Bookings
    $recent_bookings = $conn->query("
        SELECT b.*, u.name as user_name, p.title as package_title 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN tour_packages p ON b.package_id = p.id 
        ORDER BY b.id DESC LIMIT 5
    ");

    // Recent 5 Admin Logs
    $recent_logs = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM admin_logs l 
        JOIN admins a ON l.admin_id = a.id 
        ORDER BY l.id DESC LIMIT 5
    ");
}

$pageTitle = 'Admin Dashboard | CholoGhuri';
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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 h-16 flex items-center px-6 sticky top-0 z-40 justify-between shadow-sm">
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="flex items-center gap-2">
                <img src="../images/logo.png" alt="Logo" class="h-8">
                <span class="text-xl font-black text-dark tracking-tight">Cholo<span class="text-primary">Ghuri</span> <small class="text-[10px] text-orange-600 font-extrabold bg-orange-100 px-2 py-0.5 rounded-full ml-1 align-top uppercase tracking-wider">Admin</small></span>
            </a>
            <a href="../index.php" target="_blank" class="hidden md:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Site
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
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-gauge w-5 text-center"></i> Dashboard
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
                <h1 class="text-2xl font-black text-dark mb-1">Admin Management Dashboard</h1>
                <p class="text-slate-500 text-sm font-medium">Real-time analytics and management controls powered by MySQL.</p>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-8">
                <!-- Users -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Users</div>
                        <div class="text-2xl font-black text-dark"><?= $total_users ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-primary flex items-center justify-center text-xl">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- Packages -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Active Tours</div>
                        <div class="text-2xl font-black text-dark"><?= $total_packages ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-route"></i>
                    </div>
                </div>

                <!-- Destinations -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Destinations</div>
                        <div class="text-2xl font-black text-dark"><?= $total_destinations ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                </div>

                <!-- Bookings -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Bookings</div>
                        <div class="text-2xl font-black text-dark"><?= $total_bookings ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-suitcase-rolling"></i>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between xl:col-span-1">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Revenue</div>
                        <div class="text-2xl font-black text-emerald-600">৳<?= number_format($total_revenue) ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50/50 text-emerald-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pending Bookings</div>
                        <div class="text-2xl font-black text-amber-500"><?= $pending_bookings ?></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Chart.js Visualizations -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Monthly Bookings & Revenue Bar/Line Chart -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm lg:col-span-2">
                    <h3 class="text-base font-bold text-dark mb-6 flex items-center gap-2"><i class="fa-solid fa-chart-column text-primary"></i> Monthly Trend (<?= date('Y') ?>)</h3>
                    <div class="h-72">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Popular Destinations Doughnut Chart -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col">
                    <h3 class="text-base font-bold text-dark mb-6 flex items-center gap-2"><i class="fa-solid fa-chart-pie text-orange-500"></i> Booking Distribution</h3>
                    <div class="flex-1 flex items-center justify-center min-h-[280px]">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center gap-4 bg-slate-50/50">
                    <h3 class="text-base font-bold text-dark flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Recent Booking Inquiries</h3>
                    <a href="bookings.php" class="text-xs font-bold bg-white text-slate-600 border border-slate-200 hover:border-slate-300 hover:text-dark px-3 py-1.5 rounded-lg transition-colors shadow-sm">View All Bookings</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking Code</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Travel Date</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                                <?php while ($b = $recent_bookings->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4"><span class="font-bold text-dark text-sm"><?= htmlspecialchars($b['booking_code'] ?? ('CHG-' . $b['id'])) ?></span></td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-600"><?= htmlspecialchars($b['user_name']) ?></td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-600"><?= htmlspecialchars($b['package_title']) ?></td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-500"><?= date('d M Y', strtotime($b['travel_date'])) ?></td>
                                        <td class="py-4 px-4"><span class="font-bold text-dark text-sm">৳<?= number_format($b['total_amount']) ?></span></td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider
                                                <?= strtolower($b['status']) === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : (strtolower($b['status']) === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') ?>">
                                                <?= htmlspecialchars($b['status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider
                                                <?= strtolower($b['payment_status']) === 'verified' ? 'bg-sky-100 text-sky-700' : (strtolower($b['payment_status']) === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700') ?>">
                                                <?= ucfirst(htmlspecialchars($b['payment_status'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <a href="bookings.php?id=<?= $b['id'] ?>" class="text-xs font-bold bg-white text-slate-600 border border-slate-200 hover:border-slate-300 px-3 py-1.5 rounded-lg shadow-sm transition-colors">Manage</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="py-8 text-center text-sm text-slate-500 font-medium">No bookings recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart.js Scripts -->
    <script>
        // Monthly Bookings & Revenue
        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Bookings Count',
                    data: <?= json_encode($monthly_bookings) ?>,
                    backgroundColor: 'rgba(8, 169, 223, 0.75)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Booking Status Doughnut
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Confirmed', 'Pending', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [<?= $confirmed_bookings ?>, <?= $pending_bookings ?>, <?= $completed_bookings ?>, <?= $cancelled_bookings ?>],
                    backgroundColor: ['#2e7d32', '#f59e0b', '#0288d1', '#e63946'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
