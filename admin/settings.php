<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $settings_to_update = [
        'site_name' => trim(input_string($_POST, 'site_name')),
        'site_tagline' => trim(input_string($_POST, 'site_tagline')),
        'contact_email' => trim(input_string($_POST, 'contact_email')),
        'contact_phone' => trim(input_string($_POST, 'contact_phone')),
        'emergency_phone' => trim(input_string($_POST, 'emergency_phone')),
        'office_address' => trim(input_string($_POST, 'office_address')),
        'bkash_merchant' => trim(input_string($_POST, 'bkash_merchant')),
        'nagad_merchant' => trim(input_string($_POST, 'nagad_merchant')),
        'currency_symbol' => trim(input_string($_POST, 'currency_symbol', '৳')),
        'cancellation_cutoff_hours' => trim(input_string($_POST, 'cancellation_cutoff_hours', '48'))
    ];

    $valid = strlen($settings_to_update['site_name']) <= 150
        && strlen($settings_to_update['site_tagline']) <= 255
        && ($settings_to_update['contact_email'] === '' || filter_var($settings_to_update['contact_email'], FILTER_VALIDATE_EMAIL))
        && strlen($settings_to_update['contact_email']) <= 150
        && strlen($settings_to_update['contact_phone']) <= 30
        && ($settings_to_update['contact_phone'] === '' || preg_match('/^[0-9+() .-]+$/', $settings_to_update['contact_phone']))
        && strlen($settings_to_update['emergency_phone']) <= 30
        && ($settings_to_update['emergency_phone'] === '' || preg_match('/^[0-9+() .-]+$/', $settings_to_update['emergency_phone']))
        && strlen($settings_to_update['office_address']) <= 500
        && strlen($settings_to_update['bkash_merchant']) <= 30
        && ($settings_to_update['bkash_merchant'] === '' || preg_match('/^[0-9+() .-]+$/', $settings_to_update['bkash_merchant']))
        && strlen($settings_to_update['nagad_merchant']) <= 30
        && ($settings_to_update['nagad_merchant'] === '' || preg_match('/^[0-9+() .-]+$/', $settings_to_update['nagad_merchant']))
        && strlen($settings_to_update['currency_symbol']) <= 8
        && ctype_digit((string) $settings_to_update['cancellation_cutoff_hours'])
        && (int) $settings_to_update['cancellation_cutoff_hours'] <= 8760;
    if (!$valid) {
        http_response_code(400);
        $error = 'Please review the settings values and try again.';
    } elseif ($conn) {
        foreach ($settings_to_update as $key => $val) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $key, $val, $val);
            $stmt->execute();
        }
        log_admin_action($conn, $admin['id'], 'Update Settings', 'Updated system preferences and contact details');
        set_flash('success', 'System settings saved successfully!');
        redirect('settings.php');
    }
}

// Load current settings
$settings = [];
if ($conn) {
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

$pageTitle = 'System Settings | CholoGhuri Admin';
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
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-chart-line w-5 text-center text-slate-400"></i> Analytics & Reports
                </a>

                <div class="text-[10px] uppercase tracking-widest text-slate-400 font-extrabold mt-4 mb-1 px-3">System
                </div>
                <a href="activity-logs.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-dark transition-colors">
                    <i class="fa-solid fa-shield-halved w-5 text-center text-slate-400"></i> Activity Logs
                </a>
                <a href="settings.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-sliders w-5 text-center"></i> Settings
                </a>
            </div>
        </aside>

        <!-- Dashboard Content -->
        <main class="flex-1 p-6 md:p-8 max-w-[1600px]">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-dark mb-1">System & Booking Settings</h1>
                <p class="text-slate-500 text-sm font-medium">Configure platform identity, public contacts, merchant
                    numbers, and policy rules.</p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 text-rose-600 border border-rose-200 rounded-xl p-4 font-medium text-sm flex items-start gap-3 mb-6 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8 max-w-3xl">
                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>

                    <div class="mb-8">
                        <h3 class="text-base font-bold text-dark mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 text-primary flex items-center justify-center"><i
                                    class="fa-solid fa-globe text-sm"></i></div> Branding & Platform Identity
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 ml-0 md:ml-10">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Website
                                    Name</label>
                                <input type="text" name="site_name"
                                    value="<?= htmlspecialchars($settings['site_name'] ?? 'CholoGhuri') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tagline</label>
                                <input type="text" name="site_tagline"
                                    value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Explore More. Live Better.') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-base font-bold text-dark mb-4 flex items-center gap-2">
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center">
                                <i class="fa-solid fa-address-book text-sm"></i></div> Public Contact Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 ml-0 md:ml-10 mb-5">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Support
                                    Email</label>
                                <input type="email" name="contact_email"
                                    value="<?= htmlspecialchars($settings['contact_email'] ?? 'support@chologhuri.com') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Hotline
                                    Number</label>
                                <input type="text" name="contact_phone"
                                    value="<?= htmlspecialchars($settings['contact_phone'] ?? '+880 1712-345678') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                            </div>
                        </div>
                        <div class="ml-0 md:ml-10">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Office
                                Address</label>
                            <input type="text" name="office_address"
                                value="<?= htmlspecialchars($settings['office_address'] ?? 'Level 5, Concord Tower, Panthapath, Dhaka-1205, Bangladesh') ?>"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-base font-bold text-dark mb-4 flex items-center gap-2">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center">
                                <i class="fa-solid fa-money-bill-transfer text-sm"></i></div> Payment & Merchant Numbers
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 ml-0 md:ml-10">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">bKash
                                    Merchant / Personal No.</label>
                                <input type="text" name="bkash_merchant"
                                    value="<?= htmlspecialchars($settings['bkash_merchant'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all font-medium text-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nagad
                                    Merchant No.</label>
                                <input type="text" name="nagad_merchant"
                                    value="<?= htmlspecialchars($settings['nagad_merchant'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all font-medium text-sm">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100 my-8">

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-primary hover:bg-sky-600 text-white font-bold py-3 px-8 rounded-xl shadow-sm shadow-primary/30 transition-all active:scale-95 flex items-center gap-2">
                            <i class="fa-solid fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>