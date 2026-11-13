<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$report_type = $_GET['report_type'] ?? 'bookings';
if (!in_array($report_type, ['bookings', 'revenue'], true)) {
    http_response_code(400);
    exit('Invalid report type.');
}
$dateValue = static function ($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
};
$from_date = isset($_GET['from_date']) ? $dateValue($_GET['from_date']) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $dateValue($_GET['to_date']) : date('Y-m-d');
if ($from_date === null || $to_date === null) {
    http_response_code(400);
    exit('Invalid report date.');
}
if ($from_date > $to_date) {
    http_response_code(400);
    exit('Invalid report date range.');
}

// CSV Export Action
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $conn) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="chologhuri_' . $report_type . '_report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    $writeCsvRow = static function ($row) use ($output) {
        foreach ($row as &$value) {
            if (is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value)) {
                $value = "'" . $value;
            }
        }
        unset($value);
        fputcsv($output, $row);
    };

    if ($report_type === 'bookings') {
        fputcsv($output, ['Booking Code', 'Customer', 'Customer Email', 'Customer Phone', 'Tour Package', 'Travel Date', 'Travelers', 'Total Amount (BDT)', 'Booking Status', 'Payment Status', 'Created Date']);
        $stmt = $conn->prepare("
            SELECT b.booking_code, u.name, u.email, u.phone, p.title, b.travel_date, b.number_of_travelers, b.total_amount, b.status, b.payment_status, b.created_at
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN tour_packages p ON b.package_id = p.id
            WHERE DATE(b.created_at) BETWEEN ? AND ?
            ORDER BY b.id DESC
        ");
        $stmt->bind_param('ss', $from_date, $to_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $writeCsvRow($row);
        }
    } elseif ($report_type === 'revenue') {
        fputcsv($output, ['Transaction ID', 'Booking Code', 'Customer', 'Payment Method', 'Amount (BDT)', 'Status', 'Paid Date']);
        $stmt = $conn->prepare("
            SELECT p.transaction_id, b.booking_code, u.name, p.payment_method, p.amount, p.status, p.created_at
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON p.user_id = u.id
            WHERE DATE(p.created_at) BETWEEN ? AND ?
            ORDER BY p.id DESC
        ");
        $stmt->bind_param('ss', $from_date, $to_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $writeCsvRow($row);
        }
    }
    fclose($output);
    exit;
}

// Fetch preview data
$report_rows = [];
$total_sum = 0;
if ($conn) {
    if ($report_type === 'bookings') {
        $stmt = $conn->prepare("
            SELECT b.*, u.name as customer_name, p.title as package_title, p.destination
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN tour_packages p ON b.package_id = p.id
            WHERE DATE(b.created_at) BETWEEN ? AND ?
            ORDER BY b.id DESC
        ");
        $stmt->bind_param('ss', $from_date, $to_date);
        $stmt->execute();
        $report_rows = $stmt->get_result();
    } elseif ($report_type === 'revenue') {
        $stmt = $conn->prepare("
            SELECT p.*, b.booking_code, u.name as customer_name, pkg.title as package_title
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON p.user_id = u.id
            JOIN tour_packages pkg ON b.package_id = pkg.id
            WHERE DATE(p.created_at) BETWEEN ? AND ?
            ORDER BY p.id DESC
        ");
        $stmt->bind_param('ss', $from_date, $to_date);
        $stmt->execute();
        $report_rows = $stmt->get_result();
    }
}

$pageTitle = 'Reports & Analytics | CholoGhuri Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
    <header
        class="bg-white border-b border-slate-200 h-16 flex items-center px-6 sticky top-0 z-40 justify-between shadow-sm">
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="flex items-center gap-2">
                <img src="../images/logo.png" alt="Logo" class="h-8">
                <span class="text-xl font-black text-dark tracking-tight">Cholo<span class="text-primary">Ghuri</span>
                    <small
                        class="text-[10px] text-orange-600 font-extrabold bg-orange-100 px-2 py-0.5 rounded-full ml-1 align-top uppercase tracking-wider">Admin</small></span>
            </a>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-dark leading-tight"><?= htmlspecialchars($admin['name']) ?></div>
                <div class="text-xs font-semibold text-slate-400"><?= htmlspecialchars($admin['role']) ?></div>
            </div>
            <a href="logout.php"
                class="w-9 h-9 flex items-center justify-center rounded-lg bg-rose-50 text-rose-500 border border-rose-100 hover:bg-rose-500 hover:text-white transition-all shadow-sm"
                title="Logout">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-64px)]">
        <!-- Admin Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 p-4 hidden lg:block shrink-0 shadow-sm z-30">
            <div class="flex flex-col gap-1">
                <a href="dashboard.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-gauge w-5 text-center text-slate-400"></i> Dashboard
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">
                    Management</div>
                <a href="users.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-users w-5 text-center text-slate-400"></i> Users
                </a>
                <a href="destinations.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-map-location-dot w-5 text-center text-slate-400"></i> Destinations
                </a>
                <a href="packages.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-route w-5 text-center text-slate-400"></i> Packages
                </a>
                <a href="bookings.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-suitcase-rolling w-5 text-center text-slate-400"></i> Bookings
                </a>
                <a href="payments.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-credit-card w-5 text-center text-slate-400"></i> Payments
                </a>
                <a href="reviews.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-star w-5 text-center text-slate-400"></i> Reviews
                </a>
                <a href="cancellations.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-ban w-5 text-center text-slate-400"></i> Cancellation Requests
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">Reports
                    & Analytics</div>
                <a href="reports.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-chart-line w-5 text-center"></i> Analytics & Reports
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">System
                </div>
                <a href="activity-logs.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-shield-halved w-5 text-center text-slate-400"></i> Activity Logs
                </a>
                <a href="settings.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-sliders w-5 text-center text-slate-400"></i> Settings
                </a>
            </div>
        </aside>

        <!-- Dashboard Content -->
        <main class="flex-1 p-6 md:p-8 max-w-[1600px]">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-dark mb-1">System Reports & CSV Export</h1>
                <p class="text-slate-500 text-sm font-medium">Generate filtered financial and operational reports with
                    one-click spreadsheet download.</p>
            </div>

            <!-- Filter Controls -->
            <form method="GET" action="reports.php"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-8 flex flex-col lg:flex-row gap-5 items-end">
                <div class="w-full lg:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Report
                        Category</label>
                    <select name="report_type"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none">
                        <option value="bookings" <?= $report_type === 'bookings' ? 'selected' : '' ?>>Bookings Report
                        </option>
                        <option value="revenue" <?= $report_type === 'revenue' ? 'selected' : '' ?>>Revenue & Payments
                        </option>
                    </select>
                </div>

                <div class="w-full lg:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">From
                        Date</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>

                <div class="w-full lg:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">To Date</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>

                <div class="w-full lg:w-1/4 flex gap-3">
                    <button type="submit"
                        class="flex-1 bg-dark hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-xl shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-filter"></i> Run Report
                    </button>
                    <a href="reports.php?<?= htmlspecialchars(http_build_query(['export' => 'csv', 'report_type' => $report_type, 'from_date' => $from_date, 'to_date' => $to_date]), ENT_QUOTES, 'UTF-8') ?>"
                        class="flex-1 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white font-bold py-3 px-4 rounded-xl border border-emerald-200 hover:border-emerald-500 transition-all active:scale-95 flex items-center justify-center gap-2 text-sm text-center">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </form>

            <!-- Table Preview -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <?php if ($report_type === 'bookings'): ?>
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking
                                        Code</th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer
                                    </th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Package
                                    </th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Travel
                                        Date</th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Travelers</th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                        Total (BDT)</th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                        Status</th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                        Date Booked</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if ($report_rows && $report_rows->num_rows > 0): ?>
                                    <?php while ($r = $report_rows->fetch_assoc()):
                                        $total_sum += $r['total_amount']; ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 text-sm font-bold text-slate-700 whitespace-nowrap">
                                                <?= htmlspecialchars($r['booking_code'] ?? ('CHG-' . $r['id'])) ?></td>
                                            <td class="py-3 px-4 text-sm font-medium text-dark whitespace-nowrap">
                                                <?= htmlspecialchars($r['customer_name']) ?></td>
                                            <td class="py-3 px-4 text-sm font-medium text-slate-600 line-clamp-1 max-w-[200px]"
                                                title="<?= htmlspecialchars($r['package_title']) ?>">
                                                <?= htmlspecialchars($r['package_title']) ?></td>
                                            <td class="py-3 px-4 text-sm font-medium text-slate-500 whitespace-nowrap">
                                                <?= date('d M Y', strtotime($r['travel_date'])) ?></td>
                                            <td class="py-3 px-4 text-sm font-bold text-slate-600 text-center">
                                                <?= (int) ($r['number_of_travelers'] ?? $r['people']) ?></td>
                                            <td class="py-3 px-4 text-sm font-bold text-dark whitespace-nowrap text-right">
                                                ৳<?= number_format($r['total_amount']) ?></td>
                                            <td class="py-3 px-4 text-center">
                                                <?php
                                                $sObj = [
                                                    'Pending' => 'bg-amber-100 text-amber-700',
                                                    'Confirmed' => 'bg-sky-100 text-primary',
                                                    'Completed' => 'bg-emerald-100 text-emerald-700',
                                                    'Cancelled' => 'bg-slate-100 text-slate-600',
                                                    'Rejected' => 'bg-rose-100 text-rose-700'
                                                ][$r['status']] ?? 'bg-slate-100 text-slate-600';
                                                ?>
                                                <span
                                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $sObj ?>"><?= htmlspecialchars($r['status']) ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-xs font-medium text-slate-500 whitespace-nowrap text-right">
                                                <?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="py-12 text-center text-sm text-slate-500 font-medium">
                                            <div
                                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3">
                                                <i class="fa-regular fa-folder-open"></i></div>
                                            <div class="block">No bookings found in this date window.</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        <?php else: ?>
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Trx ID
                                    </th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking
                                        Code</th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer
                                    </th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Method
                                    </th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                        Amount</th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                        Status</th>
                                    <th
                                        class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                        Payment Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if ($report_rows && $report_rows->num_rows > 0): ?>
                                    <?php while ($r = $report_rows->fetch_assoc()):
                                        $total_sum += $r['amount']; ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4"><code
                                                    class="bg-slate-100 px-2 py-1 rounded-md font-mono text-sm font-bold text-primary tracking-tight"><?= htmlspecialchars($r['transaction_id']) ?></code>
                                            </td>
                                            <td class="py-3 px-4 text-sm font-bold text-slate-700 whitespace-nowrap">
                                                <?= htmlspecialchars($r['booking_code']) ?></td>
                                            <td class="py-3 px-4 text-sm font-medium text-dark whitespace-nowrap">
                                                <?= htmlspecialchars($r['customer_name']) ?></td>
                                            <td class="py-3 px-4 text-sm font-medium text-slate-600 capitalize">
                                                <?= htmlspecialchars($r['payment_method']) ?></td>
                                            <td class="py-3 px-4 text-sm font-bold text-dark whitespace-nowrap text-right">
                                                ৳<?= number_format($r['amount']) ?></td>
                                            <td class="py-3 px-4 text-center">
                                                <?php
                                                $st = strtolower($r['status']);
                                                $c = $st === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($st === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                                                ?>
                                                <span
                                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $c ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-xs font-medium text-slate-500 whitespace-nowrap text-right">
                                                <?= date('d M Y, h:ia', strtotime($r['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-sm text-slate-500 font-medium">
                                            <div
                                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3">
                                                <i class="fa-regular fa-folder-open"></i></div>
                                            <div class="block">No payment records in this date window.</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <div
                    class="bg-white px-6 py-4 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between min-w-[280px]">
                    <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Period Subtotal</span>
                    <span class="text-2xl font-black text-primary">৳<?= number_format($total_sum) ?></span>
                </div>
            </div>
        </main>
    </div>
</body>

</html>