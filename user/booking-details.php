<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int) $_SESSION['user_id'];
$booking_id = (int) ($_GET['id'] ?? 0);

if ($booking_id <= 0 || !$conn) {
    redirect('bookings.php');
}

// Fetch booking with package details
$stmt = $conn->prepare("
    SELECT b.*, p.title as package_title, p.destination, p.duration, p.image as package_image, 
           p.itinerary, p.included_services, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM bookings b
    JOIN tour_packages p ON b.package_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    set_flash('error', 'Booking not found or unauthorized.');
    redirect('bookings.php');
}

// Fetch travelers
$t_stmt = $conn->prepare("SELECT * FROM booking_travelers WHERE booking_id = ?");
$t_stmt->bind_param('i', $booking_id);
$t_stmt->execute();
$travelers = $t_stmt->get_result();

// Fetch payments
$p_stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC");
$p_stmt->bind_param('i', $booking_id);
$p_stmt->execute();
$payments = $p_stmt->get_result();

$pageTitle = 'Booking Confirmation ' . htmlspecialchars($booking['booking_code'] ?? '') . ' | CholoGhuri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Printable Voucher Container -->
    <div id="printableVoucher"
        class="bg-white rounded-3xl border border-slate-100 shadow-[0_4px_25px_rgba(11,85,117,0.08)] overflow-hidden reveal-up">

        <!-- Header Banner -->
        <div
            class="bg-gradient-to-br from-primary to-sky-400 p-8 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 print:bg-white print:text-dark">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <img src="../images/logo.png" alt="CholoGhuri Logo"
                        class="h-12 bg-white p-1 rounded-xl shadow-sm print:shadow-none print:border print:border-slate-200">
                    <h2 class="text-3xl font-black text-white m-0 tracking-tight print:text-dark">Cholo<span
                            class="text-sky-100 print:text-sky-500">Ghuri</span></h2>
                </div>
                <p class="m-0 text-sm font-medium text-sky-50 print:text-slate-500">Official Tour Booking Confirmation &
                    E-Ticket Voucher</p>
            </div>
            <div
                class="md:text-right w-full md:w-auto p-4 md:p-0 bg-white/10 md:bg-transparent rounded-xl md:rounded-none">
                <span
                    class="text-xs uppercase tracking-wider font-bold text-sky-100 block mb-1 print:text-slate-400">Booking
                    Reference</span>
                <span
                    class="font-mono text-2xl font-black bg-white/20 print:bg-slate-100 print:text-dark px-4 py-1.5 rounded-lg inline-block tracking-widest shadow-sm">
                    <?= htmlspecialchars($booking['booking_code'] ?? ('CHG-' . $booking['id'])) ?>
                </span>
            </div>
        </div>

        <div class="p-8">
            <!-- Status Row -->
            <div class="flex flex-wrap items-center justify-between gap-6 border-b border-slate-100 pb-6 mb-8">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Booking
                        Status</span>
                    <?php
                    $b_status = strtolower($booking['status']);
                    $b_classes = match ($b_status) {
                        'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        'cancelled' => 'bg-rose-50 text-rose-600 border-rose-200',
                        'confirmed' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        default => 'bg-sky-50 text-sky-600 border-sky-200'
                    };
                    ?>
                    <span
                        class="px-3 py-1.5 rounded-lg text-sm font-bold border <?= $b_classes ?> inline-flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($booking['status']) ?>
                    </span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Payment
                        Status</span>
                    <?php
                    $p_status = strtolower($booking['payment_status'] ?? 'pending');
                    $p_classes = match ($p_status) {
                        'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                        'failed' => 'bg-rose-50 text-rose-600 border-rose-200',
                        default => 'bg-amber-50 text-amber-600 border-amber-200'
                    };
                    ?>
                    <span
                        class="px-3 py-1.5 rounded-lg text-sm font-bold border <?= $p_classes ?> inline-flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-credit-card"></i>
                        <?= ucfirst(htmlspecialchars($booking['payment_status'] ?? 'pending')) ?>
                    </span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Issue
                        Date</span>
                    <strong
                        class="text-dark font-black"><?= date('d F Y', strtotime($booking['created_at'])) ?></strong>
                </div>
                <div class="no-print w-full sm:w-auto">
                    <button onclick="window.print()"
                        class="w-full sm:w-auto bg-slate-800 hover:bg-dark text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Print Voucher
                    </button>
                </div>
            </div>

            <!-- Customer & Trip Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user-astronaut text-slate-400"></i> Lead Traveler Information</h4>
                    <p class="text-base font-black text-dark mb-2"><?= htmlspecialchars($booking['customer_name']) ?>
                    </p>
                    <p class="text-sm text-slate-600 mb-1.5 flex items-center gap-2.5"><i
                            class="fa-solid fa-envelope w-4 text-center text-slate-400"></i>
                        <?= htmlspecialchars($booking['customer_email']) ?></p>
                    <p class="text-sm text-slate-600 flex items-center gap-2.5"><i
                            class="fa-solid fa-phone w-4 text-center text-slate-400"></i>
                        <?= htmlspecialchars($booking['customer_phone'] ?: 'Not specified') ?></p>
                </div>

                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-slate-400"></i> Trip Details</h4>
                    <p class="text-base font-black text-dark mb-2 leading-tight">
                        <?= htmlspecialchars($booking['package_title']) ?></p>
                    <p class="text-sm text-slate-600 mb-1.5 flex items-center gap-2.5"><i
                            class="fa-solid fa-location-dot w-4 text-center text-sky-500"></i>
                        <?= htmlspecialchars($booking['destination']) ?></p>
                    <p class="text-sm text-slate-600 flex items-center gap-2.5"><i
                            class="fa-solid fa-calendar-day w-4 text-center text-sky-500"></i>
                        <?= date('d M Y (l)', strtotime($booking['travel_date'])) ?></p>
                </div>
            </div>

            <!-- Travelers Table -->
            <h4 class="text-lg font-extrabold text-dark mb-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-500 flex items-center justify-center"><i
                        class="fa-solid fa-users"></i></div>
                Registered Travelers (<?= (int) ($booking['number_of_travelers'] ?? $booking['people']) ?>)
            </h4>
            <div class="overflow-x-auto rounded-2xl border border-slate-100 mb-8 shadow-sm">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th
                                class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 w-16">
                                #</th>
                            <th
                                class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                Traveler Name</th>
                            <th
                                class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                Contact</th>
                            <th
                                class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                Age</th>
                            <th
                                class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                Gender</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if ($travelers && $travelers->num_rows > 0):
                            $idx = 1; ?>
                            <?php while ($t = $travelers->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-4 text-sm font-medium text-slate-500"><?= $idx++ ?></td>
                                    <td class="px-5 py-4 text-sm font-bold text-dark flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">
                                            <i class="fa-solid fa-user"></i></div>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-600 font-medium">
                                        <?= htmlspecialchars($t['phone'] ?: $booking['customer_phone']) ?></td>
                                    <td class="px-5 py-4 text-sm text-slate-600 font-medium"><?= (int) $t['age'] ?> yrs</td>
                                    <td class="px-5 py-4 text-sm text-slate-600 font-medium">
                                        <?= htmlspecialchars($t['gender']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 text-sm font-medium text-slate-500">1</td>
                                <td class="px-5 py-4 text-sm font-bold text-dark flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">
                                        <i class="fa-solid fa-user"></i></div>
                                    <?= htmlspecialchars($booking['customer_name']) ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600 font-medium">
                                    <?= htmlspecialchars($booking['customer_phone']) ?></td>
                                <td class="px-5 py-4 text-sm text-slate-600 font-medium">25+ yrs</td>
                                <td class="px-5 py-4 text-sm text-slate-600 font-medium">Adult</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Price Breakdown -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 mb-8 max-w-sm ml-auto">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-semibold text-slate-500">Package Price <span
                            class="text-xs font-normal opacity-75">(x<?= (int) ($booking['number_of_travelers'] ?? $booking['people']) ?>)</span></span>
                    <span
                        class="text-sm font-bold text-dark">৳<?= number_format($booking['total_amount'] ?? $booking['total_price']) ?></span>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-semibold text-slate-500">Service & VAT</span>
                    <span
                        class="text-xs font-black uppercase tracking-wider text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded">Included</span>
                </div>
                <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                    <span class="text-base font-extrabold text-dark uppercase tracking-wider">Total Amount</span>
                    <span
                        class="text-2xl font-black text-primary">৳<?= number_format($booking['total_amount'] ?? $booking['total_price']) ?></span>
                </div>
            </div>

            <!-- Payment Records -->
            <?php if ($payments && $payments->num_rows > 0): ?>
                <h4 class="text-lg font-extrabold text-dark mb-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center"><i
                            class="fa-solid fa-receipt"></i></div>
                    Payment Transactions
                </h4>
                <div class="overflow-x-auto rounded-2xl border border-slate-100 mb-8 shadow-sm">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th
                                    class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                    Method</th>
                                <th
                                    class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                    Transaction ID</th>
                                <th
                                    class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                    Amount</th>
                                <th
                                    class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                    Status</th>
                                <th
                                    class="px-5 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-right">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while ($p = $payments->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-4 text-sm font-bold text-dark flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">
                                            <i class="fa-solid fa-money-bill-wave"></i></div>
                                        <?= htmlspecialchars($p['payment_method']) ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <code
                                            class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md tracking-wider border border-slate-200"><?= htmlspecialchars($p['transaction_id']) ?></code>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-black text-dark">৳<?= number_format($p['amount']) ?></td>
                                    <td class="px-5 py-4">
                                        <?php
                                        $ps = strtolower($p['status']);
                                        $psc = match ($ps) {
                                            'completed', 'success' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                                            'failed' => 'bg-rose-50 text-rose-600 border-rose-200',
                                            default => 'bg-amber-50 text-amber-600 border-amber-200'
                                        };
                                        ?>
                                        <span
                                            class="px-2.5 py-1 rounded text-xs font-bold border <?= $psc ?> inline-block uppercase tracking-wider"><?= htmlspecialchars($p['status']) ?></span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-500 font-medium text-right">
                                        <?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Important Instructions -->
            <div class="bg-amber-50 border-l-4 border-amber-400 rounded-r-xl p-5 mb-2">
                <h5 class="text-sm font-black text-amber-800 mb-2 flex items-center gap-2 uppercase tracking-wide"><i
                        class="fa-solid fa-circle-info"></i> Travel Day Instructions</h5>
                <ul class="list-disc pl-5 m-0 text-sm font-medium text-amber-700/90 space-y-1.5 marker:text-amber-400">
                    <li>Please report at the designated departure counter 30 minutes prior to scheduled bus/ship
                        boarding time.</li>
                    <li>Each traveler must carry an authentic government photo ID or student card for checkpoints.</li>
                    <li>For instant assistance on tour day, call our 24/7 hotline at <strong
                            class="font-black">01712-345678</strong>.</li>
                </ul>
            </div>
        </div>

        <div
            class="bg-slate-50 border-t border-slate-100 py-4 px-8 text-center text-xs font-semibold text-slate-400 print:bg-white print:border-t-2 print:border-slate-800 print:text-dark">
            CholoGhuri Travel & Tour Management • Level 5, Concord Tower, Panthapath, Dhaka • support@chologhuri.com
        </div>
    </div>

    <div class="text-center mt-8 no-print reveal-up">
        <a href="bookings.php"
            class="inline-flex items-center gap-2 text-slate-500 hover:text-dark font-bold text-sm py-2 px-5 bg-white border border-slate-200 hover:border-slate-300 rounded-xl transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to My Bookings
        </a>
    </div>
</div>

<style>
    @media print {

        .navbar,
        footer,
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #printableVoucher {
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 0 !important;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>