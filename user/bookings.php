<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$user = current_user($conn);

// Cancellation Request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    verify_csrf();
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $reason = trim(input_string($_POST, 'reason'));

    if ($booking_id <= 0 || $reason === '' || strlen($reason) > 2000 || !$conn) {
        set_flash('error', 'Please provide a valid cancellation reason.');
    } else {
        // Verify booking belongs to user
        $check = $conn->prepare("SELECT id, booking_code, status FROM bookings WHERE id = ? AND user_id = ?");
        $check->bind_param('ii', $booking_id, $user_id);
        $check->execute();
        $b_res = $check->get_result()->fetch_assoc();

        if ($b_res && !in_array($b_res['status'], ['Cancelled', 'Completed', 'Rejected'], true)) {
            $pending = $conn->prepare("SELECT id FROM cancellation_requests WHERE booking_id = ? AND status = 'pending' LIMIT 1");
            $pending->bind_param('i', $booking_id);
            $pending->execute();
            if ($pending->get_result()->num_rows > 0) {
                set_flash('warning', 'A cancellation request is already pending for this booking.');
                redirect('bookings.php');
            }
            // Insert cancellation request
            $c_stmt = $conn->prepare("INSERT INTO cancellation_requests (booking_id, user_id, reason, status) VALUES (?, ?, ?, 'pending')");
            $c_stmt->bind_param('iis', $booking_id, $user_id, $reason);
            if ($c_stmt->execute()) {
                add_notification($conn, $user_id, 'Cancellation Request Received', "Your cancellation request for booking {$b_res['booking_code']} is under admin review.", 'warning');
                set_flash('success', 'Cancellation request submitted. Our team will review and process your request within 24 hours.');
            }
        } else {
            set_flash('error', 'This booking cannot be cancelled through this request.');
        }
    }
    redirect('bookings.php');
}

// Fetch all bookings for user
$bookings = [];
if ($conn) {
    $stmt = $conn->prepare("
        SELECT b.*, p.title, p.destination, p.duration, p.image,
               (SELECT status FROM cancellation_requests WHERE booking_id = b.id ORDER BY id DESC LIMIT 1) as cancel_status
        FROM bookings b 
        JOIN tour_packages p ON b.package_id = p.id 
        WHERE b.user_id = ? 
        ORDER BY b.id DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $bookings = $stmt->get_result();
}

$pageTitle = 'My Bookings | CholoGhuri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- User Sidebar -->
    <aside class="col-span-1">
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 reveal-up sticky top-28">
            <div class="text-center pb-6 mb-6 border-b border-slate-100">
                <div class="w-20 h-20 rounded-full mx-auto bg-gradient-to-tr from-sky-400 to-primary text-white text-3xl font-bold flex items-center justify-center mb-4 shadow-lg shadow-primary/20">
                    <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h3 class="text-lg font-bold text-dark m-0"><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                <p class="text-sm text-slate-500 m-0 mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            </div>

            <ul class="flex flex-col gap-2">
                <li><a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-gauge w-5 text-center text-slate-400"></i> Dashboard</a></li>
                <li><a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-user-pen w-5 text-center text-slate-400"></i> My Profile</a></li>
                <li><a href="bookings.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-sky-50 text-primary font-semibold transition-colors"><i class="fa-solid fa-suitcase-rolling w-5 text-center"></i> My Bookings</a></li>
                <li><a href="wishlist.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-heart w-5 text-center text-slate-400"></i> Wishlist</a></li>
                <li><a href="notifications.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-bell w-5 text-center text-slate-400"></i> Notifications</a></li>
                <li><a href="reviews.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-star w-5 text-center text-slate-400"></i> My Reviews</a></li>
                <li><a href="change-password.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 font-medium hover:bg-slate-50 hover:text-dark transition-colors"><i class="fa-solid fa-key w-5 text-center text-slate-400"></i> Change Password</a></li>
                <li class="mt-4 pt-4 border-t border-slate-100">
                    <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-rose-500 font-bold hover:bg-rose-50 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i> Logout</a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content Area -->
    <section class="col-span-1 lg:col-span-3 space-y-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 reveal-up">
            <div>
                <h2 class="text-3xl font-extrabold text-dark mb-2">My Tour Bookings</h2>
                <p class="text-slate-500 text-sm">View your booked itineraries, download travel confirmation vouchers, and check payment status.</p>
            </div>
            <a href="../packages.php" class="bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-sm shadow-primary/30 transition-all active:scale-95 flex items-center justify-center gap-2 whitespace-nowrap text-sm shrink-0">
                <i class="fa-solid fa-plus"></i> Book New Tour
            </a>
        </div>

        <?php if ($bookings && $bookings->num_rows > 0): ?>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden reveal-up">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking Code</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tour Package</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Travel Date</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Travelers</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking Status</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                                <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-5">
                                        <span class="font-mono font-bold text-primary bg-sky-50 px-2 py-1 rounded">
                                            <?= htmlspecialchars($b['booking_code'] ?? ('CHG-' . $b['id'])) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-sm text-dark whitespace-nowrap"><?= htmlspecialchars($b['title']) ?></div>
                                        <div class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-1"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($b['destination']) ?></div>
                                    </td>
                                    <td class="py-4 px-5 text-sm font-medium text-slate-600 whitespace-nowrap"><?= date('d M Y', strtotime($b['travel_date'])) ?></td>
                                    <td class="py-4 px-5 text-sm font-bold text-slate-700 text-center"><?= (int)($b['number_of_travelers'] ?? $b['people']) ?></td>
                                    <td class="py-4 px-5 text-sm font-extrabold text-dark whitespace-nowrap">৳<?= number_format($b['total_amount'] ?? $b['total_price']) ?></td>
                                    <td class="py-4 px-5 text-sm font-medium">
                                        <?php
                                        $sObj = [
                                            'Pending' => 'bg-amber-100 text-amber-700',
                                            'Confirmed' => 'bg-sky-100 text-primary',
                                            'Completed' => 'bg-emerald-100 text-emerald-700',
                                            'Cancelled' => 'bg-slate-100 text-slate-600',
                                            'Rejected' => 'bg-rose-100 text-rose-700'
                                        ][$b['status']] ?? 'bg-slate-100 text-slate-600';
                                        ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $sObj ?>">
                                            <?= htmlspecialchars($b['status']) ?>
                                        </span>
                                        <?php if ($b['cancel_status'] === 'pending'): ?>
                                            <div class="text-[10px] text-amber-600 font-bold mt-1">Cancellation Requested</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-5 text-sm font-medium">
                                        <?php
                                        $pst = strtolower($b['payment_status'] ?? 'pending');
                                        $pc = $pst === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($pst === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                                        ?>
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $pc ?>">
                                            <?= ucfirst(htmlspecialchars($b['payment_status'] ?? 'pending')) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="booking-details.php?id=<?= $b['id'] ?>" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-dark font-bold text-xs transition-colors" title="View details and download voucher">
                                                <i class="fa-solid fa-file-invoice mr-1"></i> Voucher
                                            </a>
                                            <?php if (!in_array($b['status'], ['Cancelled', 'Rejected', 'Completed']) && $b['cancel_status'] !== 'pending'): ?>
                                                <button type="button" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs transition-colors"
                                                        onclick="openCancelModal(<?= (int)$b['id'] ?>, <?= htmlspecialchars(json_encode((string)($b['booking_code'] ?? ('CHG-' . $b['id'])), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-16 text-center border border-slate-100 shadow-sm reveal-up">
                <div class="w-20 h-20 bg-sky-50 text-primary rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid fa-suitcase-rolling"></i>
                </div>
                <h3 class="text-2xl font-bold text-dark mb-3">No Bookings Found</h3>
                <p class="text-slate-500 mb-8 max-w-md mx-auto">You haven't made any travel bookings yet. Explore our handpicked tour packages across Bangladesh.</p>
                <a href="../packages.php" class="bg-primary hover:bg-sky-600 text-white font-bold py-3.5 px-8 rounded-xl shadow-sm shadow-primary/30 transition-all active:scale-95 inline-block">
                    Explore Tour Packages
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Cancel Request Modal (SweetAlert2 Trigger) -->
<script>
function openCancelModal(bookingId, bookingCode) {
    Swal.fire({
        title: 'Cancel Booking ' + bookingCode + '?',
        html: `
            <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">Please explain why you need to cancel this trip. Our team will review your refund according to our policy.</p>
            <textarea id="swalReason" class="swal2-textarea" placeholder="Please state your cancellation reason..." style="width: 100%; box-sizing: border-box;"></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e63946',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Submit Cancellation Request',
        preConfirm: () => {
            const reason = document.getElementById('swalReason').value;
            if (!reason.trim()) {
                Swal.showValidationMessage('Please provide a reason for cancellation.');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'bookings.php';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= csrf_token() ?>';
            form.appendChild(csrf);

            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'cancel_booking';
            form.appendChild(action);

            const bId = document.createElement('input');
            bId.type = 'hidden';
            bId.name = 'booking_id';
            bId.value = bookingId;
            form.appendChild(bId);

            const rField = document.createElement('input');
            rField.type = 'hidden';
            rField.name = 'reason';
            rField.value = result.value;
            form.appendChild(rField);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
