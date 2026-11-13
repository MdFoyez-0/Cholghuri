<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);
$error = '';
$success = '';

// Add / Edit Package POST Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];

    if (in_array($action, ['add', 'edit'], true)) {
        $dest_id = (int)($_POST['destination_id'] ?? 0);
        $title = trim(input_string($_POST, 'title'));
        $destination = trim(input_string($_POST, 'destination'));
        $duration = trim(input_string($_POST, 'duration'));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $seats = (int)($_POST['available_seats'] ?? 20);
        $image = trim(input_string($_POST, 'image', 'images/destinations/coxsbazar.jpg'));
        $desc = trim(input_string($_POST, 'description'));
        $itinerary = trim(input_string($_POST, 'itinerary'));
        $included = trim(input_string($_POST, 'included_services'));
        $excluded = trim(input_string($_POST, 'excluded_services'));
        $status = input_string($_POST, 'status', 'active');
        $allowedImages = [
            'images/destinations/coxsbazar.jpg', 'images/destinations/sajek.jpg',
            'images/destinations/sundarbans.jpg', 'images/destinations/saintmartin.jpg',
            'images/destinations/sreemangal.jpg', 'images/destinations/bandarban.jpg',
            'images/destinations/rangamati.jpg', 'images/destinations/kuakata.jpg',
            'images/destinations/tanguar.jpg'
        ];

        if ($title === '' || $destination === '' || $duration === '' || $price === false || $price <= 0
            || $dest_id < 1 || $seats < 1 || $seats > 100 || strlen($title) > 150
            || strlen($destination) > 100 || strlen($duration) > 50 || !in_array($status, ['active', 'inactive'], true)
            || !in_array($image, $allowedImages, true) || strlen($desc) > 10000
            || strlen($itinerary) > 10000 || strlen($included) > 5000 || strlen($excluded) > 5000) {
            $error = 'Please provide valid package details.';
        } else {
            if ($action === 'add' && $conn) {
                $stmt = $conn->prepare("
                    INSERT INTO tour_packages (destination_id, title, destination, duration, price, available_seats, image, description, itinerary, included_services, excluded_services, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('isssdissssss', $dest_id, $title, $destination, $duration, $price, $seats, $image, $desc, $itinerary, $included, $excluded, $status);
                if ($stmt->execute()) {
                    log_admin_action($conn, $admin['id'], 'Add Package', "Created tour package '$title'");
                    set_flash('success', "Package '$title' created successfully!");
                    redirect('packages.php');
                } else {
                    $error = 'Failed to create package.';
                }
            } elseif ($action === 'edit' && $conn) {
                $pkg_id = (int)$_POST['package_id'];
                $stmt = $conn->prepare("
                    UPDATE tour_packages 
                    SET destination_id = ?, title = ?, destination = ?, duration = ?, price = ?, available_seats = ?, image = ?, description = ?, itinerary = ?, included_services = ?, excluded_services = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param('isssdissssssi', $dest_id, $title, $destination, $duration, $price, $seats, $image, $desc, $itinerary, $included, $excluded, $status, $pkg_id);
                if ($stmt->execute()) {
                    log_admin_action($conn, $admin['id'], 'Edit Package', "Updated tour package ID $pkg_id");
                    set_flash('success', 'Package updated successfully!');
                    redirect('packages.php');
                } else {
                    $error = 'Failed to update package.';
                }
            }
        }
    } elseif (in_array($action, ['toggle', 'delete'], true) && $conn) {
        $packageId = (int)($_POST['package_id'] ?? 0);
        if ($action === 'toggle') {
            $stmt = $conn->prepare("UPDATE tour_packages SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
            $stmt->bind_param('i', $packageId);
            $stmt->execute();
            log_admin_action($conn, $admin['id'], 'Toggle Package Status', "Toggled status for package ID $packageId");
            set_flash('success', 'Package status updated.');
        } else {
            $stmt = $conn->prepare("DELETE FROM tour_packages WHERE id = ?");
            $stmt->bind_param('i', $packageId);
            $stmt->execute();
            log_admin_action($conn, $admin['id'], 'Delete Package', "Deleted package ID $packageId");
            set_flash('success', 'Package removed.');
        }
        redirect('packages.php');
    }
}

// Fetch all packages & destinations
$packages = [];
$destinations = [];
if ($conn) {
    $packages = $conn->query("SELECT * FROM tour_packages ORDER BY id DESC");
    $destinations = $conn->query("SELECT id, name FROM destinations WHERE status = 'active' ORDER BY name ASC");
}

$pageTitle = 'Manage Tour Packages | CholoGhuri Admin';
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
            <a href="../packages.php" target="_blank" class="hidden md:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Public Tours Page
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
                <a href="packages.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-route w-5 text-center"></i> Packages
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
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark mb-1">Tour Package Management</h1>
                    <p class="text-slate-500 text-sm font-medium">Add, edit, and publish dynamic tour packages with authentic Bangladesh destination images.</p>
                </div>
                <button onclick="document.getElementById('addPackageForm').classList.toggle('hidden'); window.scrollTo(0,0);" class="bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Add New Package
                </button>
            </div>

            <?php if ($error): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-600 px-5 py-4 rounded-xl mb-8 font-semibold flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Add Package Modal / Form -->
            <div id="addPackageForm" class="hidden bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8 mb-8 transition-all">
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-dark flex items-center gap-2"><i class="fa-solid fa-circle-plus text-primary"></i> Create Dynamic Tour Package</h3>
                    <button type="button" onclick="document.getElementById('addPackageForm').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-dark transition-colors" title="Close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="packages.php" class="space-y-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Package Title</label>
                            <input type="text" name="title" required placeholder="e.g. Sajek Valley Cloud Kingdom"
                                   class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Destination</label>
                            <select name="destination_id" required class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none"
                                    onchange="document.getElementById('destName').value = this.options[this.selectedIndex].text">
                                <option value="">-- Choose Destination --</option>
                                <?php if ($destinations): while ($d = $destinations->fetch_assoc()): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                            <input type="hidden" name="destination" id="destName" value="Cox's Bazar">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Duration</label>
                            <input type="text" name="duration" required placeholder="e.g. 3 Days / 2 Nights"
                                   class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Price per Person (৳ BDT)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400 font-bold">৳</span>
                                <input type="number" name="price" required min="500" step="50" placeholder="5500"
                                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl pl-8 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Available Seats</label>
                            <input type="number" name="available_seats" value="20" min="1" max="100"
                                   class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Authentic Destination Image URL / Path</label>
                        <div class="relative flex items-center gap-3">
                            <select name="image" id="packageImageSelect" class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none" onchange="document.getElementById('packageImagePreview').src = '../' + this.value;">
                                <option value="images/destinations/coxsbazar.jpg">Cox's Bazar Sea Beach & Marine Drive (Authentic Photo)</option>
                                <option value="images/destinations/sajek.jpg">Sajek Valley Clouds & Mountain Peaks (Authentic Photo)</option>
                                <option value="images/destinations/sundarbans.jpg">Sundarbans Mangrove Safari Forest (Authentic Photo)</option>
                                <option value="images/destinations/saintmartin.jpg">Saint Martin's Coral Island & Blue Water (Authentic Photo)</option>
                                <option value="images/destinations/sreemangal.jpg">Sreemangal Rolling Tea Gardens (Authentic Photo)</option>
                                <option value="images/destinations/bandarban.jpg">Bandarban Nilgiri & Golden Temple (Authentic Photo)</option>
                                <option value="images/destinations/rangamati.jpg">Rangamati Kaptai Lake & Bridge (Authentic Photo)</option>
                                <option value="images/destinations/kuakata.jpg">Kuakata Sunset Beach (Authentic Photo)</option>
                                <option value="images/destinations/tanguar.jpg">Tanguar Haor Luxury Houseboat (Authentic Photo)</option>
                            </select>
                            <img id="packageImagePreview" src="../images/destinations/coxsbazar.jpg" class="w-12 h-12 rounded-lg object-cover shadow-sm bg-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Detailed Tour Description</label>
                        <textarea name="description" rows="3" required placeholder="Describe the highlights and atmosphere of the tour..."
                                  class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Included Services</label>
                            <textarea name="included_services" rows="2" placeholder="Transportation, Hotel stay, Daily breakfast, Guide"
                                      class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Excluded Services</label>
                            <textarea name="excluded_services" rows="2" placeholder="Personal shopping, Extra food orders"
                                      class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm"></textarea>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-dark hover:bg-slate-800 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-sm w-full md:w-auto">
                            <i class="fa-solid fa-floppy-disk text-emerald-400"></i> Publish Package to Public Site
                        </button>
                    </div>
                </form>
            </div>

            <!-- Packages Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Destination</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Duration</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Price</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Seats</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($packages && $packages->num_rows > 0): ?>
                                <?php while ($p = $packages->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-3">
                                                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="thumbnail"
                                                     class="w-10 h-10 rounded-lg object-cover shadow-sm bg-slate-100 border border-slate-200">
                                                <strong class="text-sm font-bold text-dark"><?= htmlspecialchars($p['title']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                                                <i class="fa-solid fa-map-pin mr-1 text-slate-400"></i> <?= htmlspecialchars($p['destination']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-500"><i class="fa-regular fa-clock mr-1 text-slate-400"></i> <?= htmlspecialchars($p['duration']) ?></td>
                                        <td class="py-4 px-4 font-bold text-dark text-sm">৳<?= number_format($p['price']) ?></td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-600"><?= (int)$p['available_seats'] ?></td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $p['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                                <?= htmlspecialchars(ucfirst($p['status']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="../package-details.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener noreferrer" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:border-slate-300 hover:text-dark shadow-sm transition-colors" title="Preview Live">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </a>
                                                <form method="POST" action="packages.php" class="inline-block">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
                                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:border-slate-300 hover:text-dark shadow-sm transition-colors" title="Toggle Status"><i class="fa-solid fa-power-off text-xs"></i></button>
                                                </form>
                                                <form method="POST" action="packages.php" onsubmit="return confirm('Are you sure you want to delete this package?')" class="inline-block">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
                                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-rose-500 border border-slate-200 hover:border-rose-300 hover:bg-rose-50 shadow-sm transition-colors" title="Delete Package">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="py-8 text-center text-sm text-slate-500 font-medium">No packages found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
