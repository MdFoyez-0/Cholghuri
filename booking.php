<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$packageId = (int) ($_GET['package_id'] ?? $_POST['package_id'] ?? 0);
$error = '';
$package = null;
$user = current_user($conn);
if (!$user) {
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

if ($conn && $packageId > 0) {
    $stmt = $conn->prepare('SELECT * FROM tour_packages WHERE id = ? AND status = "active"');
    $stmt->bind_param('i', $packageId);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();
}

if (!$package) {
    redirect('packages.php');
}
$bkashMerchant = get_setting($conn, 'bkash_merchant', '');
$nagadMerchant = get_setting($conn, 'nagad_merchant', '');

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rateLimit = rate_limit_allow('booking', 10, 900);
    if ($rateLimit !== true) {
        http_response_code($rateLimit === false ? 429 : 503);
        $error = $rateLimit === false ? 'Too many booking attempts. Please try again later.' : 'Booking is temporarily unavailable.';
    } else {
        $travelDate = is_string($_POST['travel_date'] ?? null) ? $_POST['travel_date'] : '';
        $people = filter_var($_POST['people'] ?? null, FILTER_VALIDATE_INT);
        $specialRequests = is_string($_POST['special_requests'] ?? null) ? trim($_POST['special_requests']) : '';
        $paymentMethod = is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : '';
        $transactionId = is_string($_POST['transaction_id'] ?? null) ? trim($_POST['transaction_id']) : '';
        $senderNumber = is_string($_POST['sender_number'] ?? null) ? trim($_POST['sender_number']) : '';
        $travelerNames = $_POST['traveler_name'] ?? [];
        $travelerAges = $_POST['traveler_age'] ?? [];
        $travelerGenders = $_POST['traveler_gender'] ?? [];
        $travelerPhones = $_POST['traveler_phone'] ?? [];
        $parsedTravelDate = $travelDate !== '' ? DateTime::createFromFormat('!Y-m-d', $travelDate) : false;

        if ($people === false || $people < 1 || $people > min(100, (int) $package['available_seats'])) {
            $error = 'Please choose a valid number of travelers within the available seats.';
        } elseif (!$parsedTravelDate || $parsedTravelDate->format('Y-m-d') !== $travelDate || $travelDate < date('Y-m-d')) {
            $error = 'Please choose a valid future travel date.';
        } elseif (
            strlen($specialRequests) > 2000 || !in_array($paymentMethod, ['Cash', 'bKash', 'Nagad', 'Card'], true)
            || strlen($transactionId) > 100 || strlen($senderNumber) > 30
            || ($senderNumber !== '' && !preg_match('/^[0-9+() .-]{7,30}$/', $senderNumber))
            || !is_array($travelerNames) || !is_array($travelerAges) || !is_array($travelerGenders) || !is_array($travelerPhones)
            || count($travelerNames) < $people || count($travelerAges) < $people
            || count($travelerGenders) < $people || count($travelerPhones) < $people
        ) {
            $error = 'Please provide valid booking and traveler details.';
        } else {
            $validTravelers = true;
            for ($i = 0; $i < $people; $i++) {
                $tName = is_string($travelerNames[$i] ?? null) ? trim($travelerNames[$i]) : '';
                $tPhone = is_string($travelerPhones[$i] ?? null) ? trim($travelerPhones[$i]) : '';
                $tAge = filter_var($travelerAges[$i] ?? null, FILTER_VALIDATE_INT);
                $tGender = $travelerGenders[$i] ?? '';
                if (
                    $tName === '' || strlen($tName) > 100 || strlen($tPhone) > 30
                    || ($tPhone !== '' && !preg_match('/^[0-9+() .-]{7,30}$/', $tPhone))
                    || $tAge === false || $tAge < 1 || $tAge > 100
                    || !is_string($tGender) || !in_array($tGender, ['Male', 'Female', 'Other'], true)
                ) {
                    $validTravelers = false;
                    break;
                }
            }
            if (!$validTravelers) {
                $error = 'Please provide valid details for every traveler.';
            } else {
                $userId = (int) $_SESSION['user_id'];
                $totalAmount = (float) $package['price'] * $people;
                $bookingCode = generate_booking_code($conn);
                $conn->begin_transaction();
                try {
                    $insertStmt = $conn->prepare("
                        INSERT INTO bookings (booking_code, user_id, package_id, travel_date, number_of_travelers, total_amount, status, payment_status, special_requests)
                        VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'pending', ?)
                    ");
                    $insertStmt->bind_param('siisids', $bookingCode, $userId, $packageId, $travelDate, $people, $totalAmount, $specialRequests);
                    $insertStmt->execute();
                    $bookingId = $conn->insert_id;

                    $travelerStmt = $conn->prepare("INSERT INTO booking_travelers (booking_id, name, phone, email, age, gender) VALUES (?, ?, ?, ?, ?, ?)");
                    for ($i = 0; $i < $people; $i++) {
                        $tName = trim($travelerNames[$i]);
                        $tPhone = trim($travelerPhones[$i]);
                        $tAge = (int) $travelerAges[$i];
                        $tGender = $travelerGenders[$i];
                        $tEmail = $i === 0 ? $user['email'] : '';
                        $travelerStmt->bind_param('isssis', $bookingId, $tName, $tPhone, $tEmail, $tAge, $tGender);
                        $travelerStmt->execute();
                    }

                    if ($transactionId !== '') {
                        $paymentStmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, sender_number, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                        $paymentStmt->bind_param('iidsss', $bookingId, $userId, $totalAmount, $paymentMethod, $transactionId, $senderNumber);
                        $paymentStmt->execute();
                    }
                    add_notification($conn, $userId, 'Booking Received!', "Your reservation $bookingCode for {$package['title']} has been recorded. E-Voucher generated.", 'booking');
                    $conn->commit();
                    set_flash('success', "Booking $bookingCode received. Your tour voucher is ready.");
                    redirect("user/booking-details.php?id=$bookingId");
                } catch (Throwable $e) {
                    $conn->rollback();
                    error_log('Booking transaction failed: ' . get_class($e));
                    $error = 'Booking could not be completed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Book ' . htmlspecialchars($package['title']) . ' | CholoGhuri';
require_once __DIR__ . '/includes/header.php';
?>

<section class="py-12 md:py-20 bg-slate-50 min-h-[calc(100vh-100px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center md:text-left reveal-up">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 bg-orange-100 text-orange-600 text-xs font-black uppercase tracking-widest rounded-full mb-3">
                <i class="fa-solid fa-ticket"></i> Tour Reservation
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-dark mb-2">Complete Your Tour Booking</h1>
            <p class="text-slate-500 font-medium">Enter traveler details and payment information to receive your instant
                confirmation.</p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-rose-50 border border-rose-100 text-rose-600 px-5 py-4 rounded-xl mb-8 font-semibold flex items-center gap-3 reveal-up">
                <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start reveal-up">
            <!-- Booking Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl p-6 md:p-10 border border-slate-100 shadow-xl">
                    <form method="POST" action="booking.php" id="bookingForm" class="space-y-12">
                        <?= csrf_field() ?>
                        <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">

                        <!-- Step 1: Dates & Number of Travelers -->
                        <div>
                            <h3
                                class="text-xl font-bold text-dark mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <span
                                    class="w-8 h-8 rounded-full bg-sky text-white flex items-center justify-center text-sm">1</span>
                                Schedule & Headcount
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Travel Departure
                                        Date</label>
                                    <input type="date" name="travel_date" required min="<?= date('Y-m-d') ?>"
                                        value="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all font-medium">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Number of
                                        Travelers</label>
                                    <input type="number" id="peopleInput" name="people" min="1"
                                        max="<?= (int) $package['available_seats'] ?>" value="1" required
                                        onchange="updateTravelerFields(this.value); updatePriceTotal(this.value);"
                                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all font-medium">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Traveler Details -->
                        <div>
                            <h3
                                class="text-xl font-bold text-dark mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <span
                                    class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm">2</span>
                                Traveler Information
                            </h3>
                            <div id="travelersContainer" class="space-y-5">
                                <!-- Lead Traveler 1 -->
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 transition-all">
                                    <div class="text-sm font-extrabold text-slate-400 uppercase tracking-wider mb-4">
                                        Traveler #1 <span class="text-emerald-500">(Primary Contact)</span></div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-600 mb-2">Full Name</label>
                                            <div class="relative">
                                                <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-400"></i>
                                                <input type="text" name="traveler_name[]"
                                                    value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                                                    class="w-full bg-white border border-slate-200 text-dark rounded-lg pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-2">Age</label>
                                            <input type="number" name="traveler_age[]" value="28" min="1" max="99"
                                                required
                                                class="w-full bg-white border border-slate-200 text-dark rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-600 mb-2">Gender</label>
                                            <select name="traveler_gender[]"
                                                class="w-full bg-white border border-slate-200 text-dark rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium appearance-none">
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-600 mb-2">Phone
                                                Number</label>
                                            <div class="relative">
                                                <i class="fa-solid fa-phone absolute left-3.5 top-3 text-slate-400"></i>
                                                <input type="text" name="traveler_phone[]"
                                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                    placeholder="017xxxxxxxx"
                                                    class="w-full bg-white border border-slate-200 text-dark rounded-lg pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Special Requests -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2"><i
                                    class="fa-solid fa-clipboard-list text-amber-500"></i> Special Requests / Food
                                Preferences (Optional)</label>
                            <textarea name="special_requests" rows="3"
                                placeholder="e.g. Need twin beds, vegetarian meal, or infant seat..."
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all placeholder:text-slate-400 font-medium"></textarea>
                        </div>

                        <!-- Step 4: Payment Simulation -->
                        <div>
                            <h3
                                class="text-xl font-bold text-dark mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <span
                                    class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm">3</span>
                                Payment Method
                            </h3>
                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-3xl p-6 md:p-8">
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="payment_method" value="bKash" checked
                                            onchange="updatePayInstructions(this.value)" class="peer sr-only">
                                        <div
                                            class="p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm transition-all peer-checked:border-pink-500 peer-checked:ring-4 peer-checked:ring-pink-500/20 text-center font-bold text-slate-600 peer-checked:text-pink-600 hover:shadow-md">
                                            <i class="fa-solid fa-mobile-screen-button text-xl mb-1 block"></i> bKash
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="payment_method" value="Nagad"
                                            onchange="updatePayInstructions(this.value)" class="peer sr-only">
                                        <div
                                            class="p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm transition-all peer-checked:border-orange-500 peer-checked:ring-4 peer-checked:ring-orange-500/20 text-center font-bold text-slate-600 peer-checked:text-orange-600 hover:shadow-md">
                                            <i class="fa-solid fa-mobile-screen-button text-xl mb-1 block"></i> Nagad
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="payment_method" value="Card"
                                            onchange="updatePayInstructions(this.value)" class="peer sr-only">
                                        <div
                                            class="p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm transition-all peer-checked:border-blue-600 peer-checked:ring-4 peer-checked:ring-blue-600/20 text-center font-bold text-slate-600 peer-checked:text-blue-700 hover:shadow-md">
                                            <i class="fa-solid fa-credit-card text-xl mb-1 block"></i> Card
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="payment_method" value="Cash"
                                            onchange="updatePayInstructions(this.value)" class="peer sr-only">
                                        <div
                                            class="p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm transition-all peer-checked:border-emerald-600 peer-checked:ring-4 peer-checked:ring-emerald-600/20 text-center font-bold text-slate-600 peer-checked:text-emerald-700 hover:shadow-md">
                                            <i class="fa-solid fa-money-bill-wave text-xl mb-1 block"></i> Cash
                                        </div>
                                    </label>
                                </div>

                                <div id="paymentInstructions"
                                    class="bg-white border border-indigo-100 rounded-xl p-4 text-sm font-medium text-slate-600 mb-6 shadow-sm">
                                    <?= $bkashMerchant !== ''
                                        ? 'Send payment to the configured bKash recipient: <strong class="text-dark">' . htmlspecialchars($bkashMerchant, ENT_QUOTES, 'UTF-8') . '</strong>. Confirm the details before paying.'
                                        : 'A verified payment recipient has not been configured. Contact support before sending payment.' ?>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Sender
                                            Mobile Number</label>
                                        <div class="relative">
                                            <i class="fa-solid fa-phone absolute left-3.5 top-3.5 text-slate-400"></i>
                                            <input type="text" name="sender_number"
                                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                placeholder="017xxxxxxxx"
                                                class="w-full bg-white border border-slate-200 text-dark rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-medium text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Transaction
                                            ID (TrxID)</label>
                                        <div class="relative">
                                            <i class="fa-solid fa-hashtag absolute left-3.5 top-3.5 text-slate-400"></i>
                                            <input type="text" name="transaction_id" placeholder="e.g. BK892X0194"
                                                class="w-full bg-white border border-slate-200 text-dark rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all font-medium text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-dark hover:bg-slate-800 text-white font-bold py-5 px-6 rounded-2xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-lg">
                            <i class="fa-solid fa-lock text-emerald-400"></i> Confirm & Complete Reservation
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sticky Tour Summary Sidebar -->
            <div class="lg:col-span-1 lg:sticky lg:top-28">
                <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-2xl">
                    <div class="relative rounded-2xl overflow-hidden mb-6 group">
                        <img src="<?= htmlspecialchars($package['image']) ?>"
                            alt="<?= htmlspecialchars($package['title']) ?>"
                            class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500"
                            onerror="this.src='images/destinations/coxsbazar.jpg'">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark/80 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 right-4">
                            <div class="text-xs font-bold text-emerald-400 tracking-wider uppercase mb-1">
                                <i class="fa-solid fa-location-dot"></i>
                                <?= htmlspecialchars($package['destination']) ?>
                            </div>
                            <h3 class="text-white font-bold text-lg leading-tight drop-shadow-md line-clamp-2">
                                <?= htmlspecialchars($package['title']) ?>
                            </h3>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div class="pb-4 space-y-3 text-sm font-medium">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Rate per person</span>
                                <span class="text-dark">৳<?= number_format($package['price']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Total Travelers</span>
                                <span class="text-dark bg-slate-100 px-2 py-0.5 rounded font-bold"
                                    id="travelerCountDisplay">1</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Duration</span>
                                <span class="text-dark"><?= htmlspecialchars($package['duration']) ?></span>
                            </div>
                        </div>

                        <div class="pt-4 pb-2">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Total
                                    Amount</span>
                                <div class="text-3xl font-black text-primary" id="totalPriceDisplay">
                                    ৳<?= number_format($package['price']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 bg-emerald-50 rounded-xl p-4 text-xs font-bold text-emerald-700 text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-shield-check text-emerald-500 text-base"></i> <span>Free cancellation up
                            to 48 hours prior</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    const basePrice = <?= (float) $package['price'] ?>;

    function updatePriceTotal(count) {
        const c = Math.max(1, parseInt(count) || 1);
        const total = c * basePrice;
        document.getElementById('travelerCountDisplay').innerText = c;
        document.getElementById('totalPriceDisplay').innerText = '৳' + total.toLocaleString();
    }

    function updateTravelerFields(count) {
        const c = Math.max(1, parseInt(count) || 1);
        const container = document.getElementById('travelersContainer');
        container.innerHTML = '';

        for (let i = 1; i <= c; i++) {
            const div = document.createElement('div');
            div.className = 'bg-slate-50 border border-slate-100 rounded-2xl p-6 transition-all';

            div.innerHTML = `
            <div class="text-sm font-extrabold text-slate-400 uppercase tracking-wider mb-4">Traveler #${i} ${i === 1 ? '<span class="text-emerald-500">(Primary Contact)</span>' : ''}</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-2">Full Name</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-400"></i>
                        <input type="text" name="traveler_name[]" placeholder="Full legal name" required
                               class="w-full bg-white border border-slate-200 text-dark rounded-lg pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">Age</label>
                    <input type="number" name="traveler_age[]" value="25" min="1" max="99" required
                           class="w-full bg-white border border-slate-200 text-dark rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2">Gender</label>
                    <select name="traveler_gender[]" class="w-full bg-white border border-slate-200 text-dark rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all text-sm font-medium appearance-none">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        `;
            container.appendChild(div);
        }
    }

    const paymentRecipients = {
        bKash: <?= json_encode($bkashMerchant, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        Nagad: <?= json_encode($nagadMerchant, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    function updatePayInstructions(method) {
        const box = document.getElementById('paymentInstructions');
        if (method === 'bKash') {
            box.innerHTML = paymentRecipients.bKash
                ? 'Send payment to the configured bKash recipient: <strong class="text-dark">' + paymentRecipients.bKash + '</strong>. Confirm the details before paying.'
                : 'A verified bKash recipient has not been configured. Contact support before sending payment.';
        } else if (method === 'Nagad') {
            box.innerHTML = paymentRecipients.Nagad
                ? 'Send payment to the configured Nagad recipient: <strong class="text-dark">' + paymentRecipients.Nagad + '</strong>. Confirm the details before paying.'
                : 'A verified Nagad recipient has not been configured. Contact support before sending payment.';
        } else if (method === 'Card') {
            box.innerHTML = 'Card processing is not configured. Do not enter card details; choose another method or contact support.';
        } else {
            box.innerHTML = 'Confirm the cash payment location and terms with support before paying.';
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>