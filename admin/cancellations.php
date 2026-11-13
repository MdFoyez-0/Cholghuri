<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);

// Approve or Reject Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $conn) {
    verify_csrf();
    $req_id = (int)($_POST['req_id'] ?? 0);
    $act = input_string($_POST, 'action');
    $note = trim(input_string($_POST, 'admin_note'));
    if (strlen($note) > 2000 || !in_array($act, ['approve', 'reject'], true)) {
        http_response_code(400);
        exit('Invalid cancellation action.');
    }

    $requestStmt = $conn->prepare("SELECT booking_id, user_id, status FROM cancellation_requests WHERE id = ?");
    $requestStmt->bind_param('i', $req_id);
    $requestStmt->execute();
    $cancellation = $requestStmt->get_result()->fetch_assoc();
    if (!$cancellation || $cancellation['status'] !== 'pending') {
        http_response_code(400);
        exit('Cancellation request is no longer available.');
    }
    $booking_id = (int)$cancellation['booking_id'];

    $conn->begin_transaction();
    try {
        $newStatus = $act === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE cancellation_requests SET status = ?, admin_note = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('ssi', $newStatus, $note, $req_id);
        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            throw new RuntimeException('Cancellation request was already processed.');
        }
        if ($act === 'approve') {
            $bookingStmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
            $bookingStmt->bind_param('i', $booking_id);
            if (!$bookingStmt->execute()) {
                throw new RuntimeException('Unable to update the booking.');
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Cancellation action failed: ' . get_class($e));
        set_flash('error', 'The cancellation request could not be processed. Please try again.');
        redirect('cancellations.php');
    }

    if ($act === 'approve') {
        add_notification($conn, (int)$cancellation['user_id'], 'Cancellation Approved', "Your cancellation request was approved. Any payment refund requires separate processing. Note: $note", 'info');
        log_admin_action($conn, $admin['id'], 'Approve Cancellation', "Approved cancellation request ID $req_id for booking ID $booking_id");
        set_flash('success', 'Cancellation request approved.');
    } else {
        add_notification($conn, (int)$cancellation['user_id'], 'Cancellation Declined', "Your cancellation request could not be approved. Note: $note", 'warning');
        log_admin_action($conn, $admin['id'], 'Reject Cancellation', "Rejected cancellation request ID $req_id");
        set_flash('warning', 'Cancellation request rejected.');
    }
    redirect('cancellations.php');
}

$requests = [];
if ($conn) {
    $requests = $conn->query("
        SELECT c.*, b.booking_code, b.travel_date, b.total_amount, u.name as user_name, u.email as user_email, p.title as package_title
        FROM cancellation_requests c
        JOIN bookings b ON c.booking_id = b.id
        JOIN users u ON c.user_id = u.id
        JOIN tour_packages p ON b.package_id = p.id
        ORDER BY c.id DESC
    ");
}

$pageTitle = 'Cancellation Requests | CholoGhuri Admin';
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
                <a href="cancellations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-ban w-5 text-center"></i> Cancellation Requests
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
                <h1 class="text-2xl font-black text-dark mb-1">Booking Cancellation Requests</h1>
                <p class="text-slate-500 text-sm font-medium">Review traveler cancellation requests, approve refund policies, or provide admin notes.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Booking Code</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Cancellation Reason</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Requested Date</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Admin Note</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($requests && $requests->num_rows > 0): ?>
                                <?php while ($req = $requests->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4 text-sm font-bold text-slate-700 whitespace-nowrap"><?= htmlspecialchars($req['booking_code']) ?></td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm font-bold text-dark whitespace-nowrap"><?= htmlspecialchars($req['user_name']) ?></div>
                                            <div class="text-xs font-medium text-slate-500 whitespace-nowrap"><?= htmlspecialchars($req['user_email']) ?></div>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-bold text-dark min-w-[200px]"><?= htmlspecialchars($req['package_title']) ?></td>
                                        <td class="py-4 px-4 text-sm text-slate-600 font-medium max-w-[250px] break-words">
                                            <?= htmlspecialchars($req['reason']) ?>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $stClass = '';
                                            if ($req['status'] === 'approved') $stClass = 'bg-emerald-100 text-emerald-700';
                                            elseif ($req['status'] === 'pending') $stClass = 'bg-amber-100 text-amber-700';
                                            else $stClass = 'bg-rose-100 text-rose-700';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $stClass ?>">
                                                <?= ucfirst(htmlspecialchars($req['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-xs font-medium text-slate-400 italic max-w-[150px]">
                                            <?= htmlspecialchars($req['admin_note'] ?: 'None') ?>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <div class="flex justify-end gap-2 whitespace-nowrap">
                                                    <button type="button" class="px-3 h-8 flex items-center justify-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-600 font-bold text-xs hover:border-emerald-300 hover:bg-emerald-500 hover:text-white transition-colors" 
                                                            onclick="processCancellation(<?= $req['id'] ?>, <?= $req['booking_id'] ?>, 'approve')">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                    <button type="button" class="px-3 h-8 flex items-center justify-center gap-1.5 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 font-bold text-xs hover:border-rose-300 hover:bg-rose-500 hover:text-white transition-colors" 
                                                            onclick="processCancellation(<?= $req['id'] ?>, <?= $req['booking_id'] ?>, 'reject')">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-slate-50 text-slate-400 text-xs font-bold border border-slate-200">
                                                    Resolved
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="py-12 text-center text-sm text-slate-500 font-medium">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3"><i class="fa-solid fa-ban"></i></div>
                                    <div class="block">No cancellation requests pending.</div>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    function processCancellation(reqId, bookingId, action) {
        Swal.fire({
            title: action === 'approve' ? 'Approve Cancellation?' : 'Reject Cancellation?',
            input: 'text',
            inputLabel: 'Admin Note / Refund Instructions',
            inputPlaceholder: 'e.g. Refund initiated via bKash / Non-refundable within 24h',
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#10b981' : '#f43f5e',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: action === 'approve' ? 'Approve & Refund' : 'Reject Request',
            customClass: {
                title: 'text-xl font-bold font-sans',
                htmlContainer: 'font-sans',
                confirmButton: 'font-sans font-bold',
                cancelButton: 'font-sans font-bold',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cancellations.php';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_token';
                csrf.value = '<?= csrf_token() ?>';
                form.appendChild(csrf);

                const aInput = document.createElement('input');
                aInput.type = 'hidden';
                aInput.name = 'action';
                aInput.value = action;
                form.appendChild(aInput);

                const rInput = document.createElement('input');
                rInput.type = 'hidden';
                rInput.name = 'req_id';
                rInput.value = reqId;
                form.appendChild(rInput);

                const bInput = document.createElement('input');
                bInput.type = 'hidden';
                bInput.name = 'booking_id';
                bInput.value = bookingId;
                form.appendChild(bInput);

                const nInput = document.createElement('input');
                nInput.type = 'hidden';
                nInput.name = 'admin_note';
                nInput.value = result.value || '';
                form.appendChild(nInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
</body>
</html>
