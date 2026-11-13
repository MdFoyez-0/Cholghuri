<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);

// Verify or Reject Payment Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['payment_id']) && $conn) {
    verify_csrf();
    $action = $_POST['action'];
    $p_id = (int)$_POST['payment_id'];

    if ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE payments SET status = 'verified' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $p_id);
        if ($stmt->execute() && $stmt->affected_rows === 1) {
            // Also update linked booking payment_status to verified
            $pay_stmt = $conn->prepare("SELECT booking_id, user_id, amount, transaction_id FROM payments WHERE id = ?");
            $pay_stmt->bind_param('i', $p_id);
            $pay_stmt->execute();
            $pay_info = $pay_stmt->get_result()->fetch_assoc();
            if ($pay_info) {
                $b_id = (int)$pay_info['booking_id'];
                $booking_stmt = $conn->prepare("UPDATE bookings SET payment_status = 'verified' WHERE id = ?");
                $booking_stmt->bind_param('i', $b_id);
                $booking_stmt->execute();
                add_notification($conn, (int)$pay_info['user_id'], 'Payment Verified', "Your payment of ৳" . number_format($pay_info['amount']) . " (TrxID: {$pay_info['transaction_id']}) has been verified successfully.", 'payment');
            }
            log_admin_action($conn, $admin['id'], 'Verify Payment', "Payment ID $p_id marked as verified");
            set_flash('success', 'Payment verified successfully!');
        } else {
            set_flash('warning', 'This payment was already processed or could not be found.');
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE payments SET status = 'failed' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $p_id);
        if ($stmt->execute() && $stmt->affected_rows === 1) {
            log_admin_action($conn, $admin['id'], 'Reject Payment', "Payment ID $p_id marked as failed");
            set_flash('warning', 'Payment marked as rejected/failed.');
        } else {
            set_flash('warning', 'This payment was already processed or could not be found.');
        }
    }
    redirect('payments.php');
}

// Fetch all payments with customer & booking info
$payments = [];
if ($conn) {
    $payments = $conn->query("
        SELECT p.*, u.name as user_name, u.email as user_email, b.booking_code, pkg.title as package_title
        FROM payments p
        JOIN users u ON p.user_id = u.id
        JOIN bookings b ON p.booking_id = b.id
        JOIN tour_packages pkg ON b.package_id = pkg.id
        ORDER BY p.id DESC
    ");
}

$pageTitle = 'Manage Payments | CholoGhuri Admin';
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
                <a href="payments.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-credit-card w-5 text-center"></i> Payments
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
                <h1 class="text-2xl font-black text-dark mb-1">Payment Verification Management</h1>
                <p class="text-slate-500 text-sm font-medium">Verify customer transactions across bKash, Nagad, Card, and Cash deposits.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Method</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking Code</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Submitted</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($payments && $payments->num_rows > 0): ?>
                                <?php while ($p = $payments->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <code class="bg-slate-100 px-2 py-1 rounded-md font-mono text-sm font-bold text-primary tracking-tight">
                                                <?= htmlspecialchars($p['transaction_id']) ?>
                                            </code>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm font-bold text-dark capitalize"><?= htmlspecialchars($p['payment_method']) ?></div>
                                            <?php if (!empty($p['sender_number'])): ?>
                                                <div class="text-xs font-medium text-slate-500"><?= htmlspecialchars($p['sender_number']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-bold text-slate-700 whitespace-nowrap"><?= htmlspecialchars($p['booking_code']) ?></td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm font-bold text-dark whitespace-nowrap"><?= htmlspecialchars($p['user_name']) ?></div>
                                            <div class="text-xs font-medium text-slate-500 whitespace-nowrap"><?= htmlspecialchars($p['user_email']) ?></div>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-black text-dark whitespace-nowrap">৳<?= number_format($p['amount']) ?></td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $stClass = '';
                                            $st = strtolower($p['status']);
                                            if ($st === 'verified') $stClass = 'bg-emerald-100 text-emerald-700';
                                            elseif ($st === 'failed') $stClass = 'bg-rose-100 text-rose-700';
                                            else $stClass = 'bg-amber-100 text-amber-700';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $stClass ?>">
                                                <?= ucfirst(htmlspecialchars($p['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-xs font-medium text-slate-500 whitespace-nowrap"><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
                                        <td class="py-4 px-4 text-right">
                                            <?php if ($p['status'] === 'pending'): ?>
                                                <div class="flex justify-end gap-2">
                                                    <form method="POST" action="payments.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="px-2 h-8 flex items-center justify-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-600 font-bold text-xs hover:border-emerald-300 hover:bg-emerald-500 hover:text-white transition-colors" title="Approve & Verify">
                                                            <i class="fa-solid fa-check"></i> Verify
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="payments.php" class="inline-block">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-rose-500 hover:border-rose-300 hover:bg-rose-50 transition-colors" title="Mark Invalid">
                                                            <i class="fa-solid fa-xmark text-xs"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-slate-50 text-slate-400 text-xs font-bold border border-slate-200">
                                                    Processed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="py-12 text-center text-sm text-slate-500 font-medium">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3"><i class="fa-solid fa-credit-card"></i></div>
                                    <div class="block">No payment records found.</div>
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
