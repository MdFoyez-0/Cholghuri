<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);

// Status Change Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id']) && $conn) {
    verify_csrf();
    $action = $_POST['action'];
    $b_id = (int)$_POST['booking_id'];
    $new_status = '';
    $expected_status = '';

    if ($action === 'confirm') { $new_status = 'Confirmed'; $expected_status = 'Pending'; }
    elseif ($action === 'reject') { $new_status = 'Rejected'; $expected_status = 'Pending'; }
    elseif ($action === 'complete') { $new_status = 'Completed'; $expected_status = 'Confirmed'; }
    elseif ($action === 'cancel') { $new_status = 'Cancelled'; $expected_status = 'Confirmed'; }

    if ($new_status) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND status = ?");
        $stmt->bind_param('sis', $new_status, $b_id, $expected_status);
        if ($stmt->execute() && $stmt->affected_rows === 1) {
            // Fetch booking info to notify user
            $info = $conn->prepare("SELECT user_id, booking_code FROM bookings WHERE id = ?");
            $info->bind_param('i', $b_id);
            $info->execute();
            $b_info = $info->get_result()->fetch_assoc();
            if ($b_info) {
                add_notification($conn, (int)$b_info['user_id'], "Booking $new_status", "Your booking {$b_info['booking_code']} status was changed to $new_status by administration.", 'booking');
            }
            log_admin_action($conn, $admin['id'], 'Update Booking', "Booking ID $b_id status changed to $new_status");
            set_flash('success', "Booking status updated to $new_status.");
        } else {
            set_flash('warning', 'The booking status changed or this action is no longer available.');
        }
    }
    redirect('bookings.php');
}

// Filters
$filter_status = input_string($_GET, 'status');
$search = substr(trim(input_string($_GET, 'search')), 0, 200);
if ($filter_status !== '' && !in_array($filter_status, ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'], true)) {
    http_response_code(400);
    exit('Invalid booking status.');
}

$sql = "
    SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, p.title as package_title, p.destination
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN tour_packages p ON b.package_id = p.id
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($filter_status)) {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $sql .= " AND (b.booking_code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR p.title LIKE ?)";
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= 'ssss';
}

$sql .= " ORDER BY b.id DESC";

$bookings = [];
if ($conn) {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $bookings = $stmt->get_result();
    } else {
        $bookings = $conn->query($sql);
    }
}

$pageTitle = 'Manage Bookings | CholoGhuri Admin';
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
                <a href="bookings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-suitcase-rolling w-5 text-center"></i> Bookings
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
                <h1 class="text-2xl font-black text-dark mb-1">Tour Bookings Management</h1>
                <p class="text-slate-500 text-sm font-medium">Review traveler reservations, verify dates, and confirm tour itineraries.</p>
            </div>

            <!-- Filter & Search Bar -->
            <form method="GET" action="bookings.php" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-8 flex flex-col md:flex-row gap-4 items-center">
                <div class="relative flex-1 w-full">
                    <i class="fa-solid fa-search absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search by Code, Customer, Package..." value="<?= htmlspecialchars($search) ?>" 
                           class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                </div>
                
                <div class="w-full md:w-auto min-w-[200px]">
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $filter_status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <button type="submit" class="bg-dark hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2 text-sm w-full md:w-auto flex-1">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="bookings.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-5 rounded-xl transition-all text-sm w-full md:w-auto text-center">Reset</a>
                </div>
            </form>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Code</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tour Package</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Travel Date</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Travelers</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Total</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($bookings && $bookings->num_rows > 0): ?>
                                <?php while ($b = $bookings->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="py-4 px-4 text-sm font-bold text-slate-700 whitespace-nowrap"><?= htmlspecialchars($b['booking_code'] ?? ('CHG-' . $b['id'])) ?></td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm font-bold text-dark whitespace-nowrap"><?= htmlspecialchars($b['customer_name']) ?></div>
                                            <div class="text-xs font-medium text-slate-500 whitespace-nowrap"><?= htmlspecialchars($b['customer_phone'] ?: $b['customer_email']) ?></div>
                                        </td>
                                        <td class="py-4 px-4 min-w-[200px]">
                                            <div class="text-sm font-bold text-dark line-clamp-1"><?= htmlspecialchars($b['package_title']) ?></div>
                                            <div class="text-xs font-medium text-slate-500"><i class="fa-solid fa-map-pin mr-1 text-slate-400"></i><?= htmlspecialchars($b['destination']) ?></div>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-600 whitespace-nowrap"><?= date('d M Y', strtotime($b['travel_date'])) ?></td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 w-8 h-8 rounded-full text-xs font-bold"><?= (int)($b['number_of_travelers'] ?? $b['people']) ?></span>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-bold text-dark whitespace-nowrap">৳<?= number_format($b['total_amount'] ?? $b['total_price']) ?></td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $statusObj = [
                                                'Pending' => 'bg-amber-100 text-amber-700',
                                                'Confirmed' => 'bg-sky-100 text-primary',
                                                'Completed' => 'bg-emerald-100 text-emerald-700',
                                                'Cancelled' => 'bg-slate-100 text-slate-600',
                                                'Rejected' => 'bg-rose-100 text-rose-700',
                                            ][$b['status']] ?? 'bg-slate-100 text-slate-600';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $statusObj ?>">
                                                <?= htmlspecialchars($b['status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $payStatus = ucfirst(htmlspecialchars($b['payment_status'] ?? 'pending'));
                                            $payClass = strtolower($payStatus) === 'paid' ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50';
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border border-transparent <?= $payClass ?>">
                                                <?= $payStatus ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <?php if ($b['status'] === 'Pending'): ?>
                                                    <form method="POST" action="bookings.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="confirm"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors" title="Confirm Booking"><i class="fa-solid fa-check text-xs"></i></button>
                                                    </form>
                                                    <form method="POST" action="bookings.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="reject"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition-colors" title="Reject Booking"><i class="fa-solid fa-xmark text-xs"></i></button>
                                                    </form>
                                                <?php elseif ($b['status'] === 'Confirmed'): ?>
                                                    <form method="POST" action="bookings.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="complete"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" class="px-2 h-8 flex items-center justify-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 text-emerald-600 font-bold text-xs hover:border-emerald-300 hover:bg-emerald-50 transition-colors" title="Mark Tour Completed"><i class="fa-solid fa-flag-checkered"></i> Done</button>
                                                    </form>
                                                    <form method="POST" action="bookings.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-rose-500 hover:border-rose-300 hover:bg-rose-50 transition-colors" title="Cancel"><i class="fa-solid fa-ban text-xs"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="../user/booking-details.php?id=<?= $b['id'] ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:border-slate-300 hover:text-dark transition-colors" title="View Full Details / Voucher">
                                                    <i class="fa-solid fa-file-lines text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="py-12 text-center text-sm text-slate-500 font-medium">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3"><i class="fa-solid fa-magnifying-glass"></i></div>
                                    <div class="block">No bookings match your filter criteria.</div>
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
