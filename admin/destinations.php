<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_admin();

$admin = current_admin($conn);
$error = '';

// Add Destination Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();
    $name = trim(input_string($_POST, 'name'));
    $division = trim(input_string($_POST, 'division', 'Chittagong'));
    $location = trim(input_string($_POST, 'location'));
    $desc = trim(input_string($_POST, 'description'));
    $image = trim(input_string($_POST, 'image', 'images/destinations/coxsbazar.jpg'));
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    $allowedDivisions = ['Chittagong', 'Sylhet', 'Khulna', 'Barisal', 'Dhaka', 'Rajshahi', 'Rangpur', 'Mymensingh'];
    $allowedImages = [
        'images/destinations/coxsbazar.jpg',
        'images/destinations/sajek.jpg',
        'images/destinations/sundarbans.jpg',
        'images/destinations/saintmartin.jpg',
        'images/destinations/sreemangal.jpg',
        'images/destinations/bandarban.jpg',
        'images/destinations/rangamati.jpg',
        'images/destinations/kuakata.jpg',
        'images/destinations/tanguar.jpg'
    ];
    if (
        $name === '' || $desc === '' || strlen($name) > 120 || strlen($division) > 80
        || strlen($location) > 150 || strlen($desc) > 10000 || !in_array($division, $allowedDivisions, true)
        || !in_array($image, $allowedImages, true)
    ) {
        $error = 'Please provide valid destination details.';
    } else {
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO destinations (name, slug, division, location, description, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param('ssssss', $name, $slug, $division, $location, $desc, $image);
            if ($stmt->execute()) {
                log_admin_action($conn, $admin['id'], 'Add Destination', "Added destination $name");
                set_flash('success', "Destination '$name' created successfully!");
                redirect('destinations.php');
            } else {
                $error = 'Failed to add destination. Name may already exist.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['toggle', 'delete'], true) && $conn) {
    verify_csrf();
    $destinationId = (int) ($_POST['destination_id'] ?? 0);
    if ($_POST['action'] === 'toggle') {
        $stmt = $conn->prepare("UPDATE destinations SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
        $stmt->bind_param('i', $destinationId);
        $stmt->execute();
        set_flash('success', 'Destination status updated.');
    } else {
        $stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
        $stmt->bind_param('i', $destinationId);
        $stmt->execute();
        set_flash('success', 'Destination removed.');
    }
    redirect('destinations.php');
}

$destinations = [];
if ($conn) {
    $destinations = $conn->query("SELECT * FROM destinations ORDER BY name ASC");
}

$pageTitle = 'Manage Destinations | CholoGhuri Admin';
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
            <a href="../index.php" target="_blank"
                class="hidden md:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Site
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
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold bg-sky-50 text-primary transition-colors">
                    <i class="fa-solid fa-map-location-dot w-5 text-center"></i> Destinations
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
                    <i class="fa-solid fa-chart-line w-5 text-center text-emerald-400"></i> Analytics & Reports
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
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark mb-1">Destination Management</h1>
                    <p class="text-slate-500 text-sm font-medium">Manage authentic Bangladesh tourist places,
                        descriptions, and cover imagery.</p>
                </div>
                <button onclick="document.getElementById('addDestForm').classList.toggle('hidden')"
                    class="bg-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Add Destination
                </button>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-rose-50 border border-rose-100 text-rose-600 px-5 py-4 rounded-xl mb-8 font-semibold flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Add Destination Form -->
            <div id="addDestForm"
                class="hidden bg-white rounded-2xl border border-slate-100 shadow-sm p-6 md:p-8 mb-8 transition-all">
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-dark flex items-center gap-2"><i
                            class="fa-solid fa-map-location-dot text-primary"></i> Create Tourist Destination</h3>
                    <button type="button" onclick="document.getElementById('addDestForm').classList.add('hidden')"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-dark transition-colors"
                        title="Close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="destinations.php" class="space-y-5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Destination
                                Name</label>
                            <input type="text" name="name" required placeholder="e.g. Kuakata"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Division</label>
                            <select name="division"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none">
                                <option value="Chittagong">Chittagong</option>
                                <option value="Sylhet">Sylhet</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Barisal">Barisal</option>
                                <option value="Dhaka">Dhaka</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Mymensingh">Mymensingh</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Location /
                            Region Details</label>
                        <input type="text" name="location" placeholder="e.g. Patuakhali, Southern Coastal Bay of Bengal"
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Authentic
                            Destination Image URL / Path</label>
                        <div class="relative flex items-center gap-3">
                            <select name="image" id="imageSelect"
                                class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm appearance-none"
                                onchange="document.getElementById('imagePreview').src = '../' + this.value;">
                                <option value="images/destinations/coxsbazar.jpg">Cox's Bazar Sea Beach</option>
                                <option value="images/destinations/sajek.jpg">Sajek Valley Clouds</option>
                                <option value="images/destinations/sundarbans.jpg">Sundarbans Mangrove Safari</option>
                                <option value="images/destinations/saintmartin.jpg">Saint Martin's Island & Coral
                                </option>
                                <option value="images/destinations/sreemangal.jpg">Sreemangal Tea Gardens</option>
                                <option value="images/destinations/bandarban.jpg">Bandarban Nilgiri Hill Tracts</option>
                                <option value="images/destinations/rangamati.jpg">Rangamati Kaptai Lake</option>
                                <option value="images/destinations/kuakata.jpg">Kuakata Sunset Beach</option>
                                <option value="images/destinations/tanguar.jpg">Tanguar Haor Luxury Houseboat</option>
                            </select>
                            <img id="imagePreview" src="../images/destinations/coxsbazar.jpg"
                                class="w-12 h-12 rounded-lg object-cover shadow-sm bg-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">About
                            Destination</label>
                        <textarea name="description" rows="3" required
                            placeholder="Describe landscape, culture, natural attractions..."
                            class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all font-medium text-sm"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="bg-dark hover:bg-slate-800 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-sm w-full md:w-auto">
                            <i class="fa-solid fa-save text-emerald-400"></i> Save Destination
                        </button>
                    </div>
                </form>
            </div>

            <!-- Destinations Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Destination</th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Division
                                </th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Location
                                </th>
                                <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                                </th>
                                <th
                                    class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if ($destinations && $destinations->num_rows > 0): ?>
                                <?php while ($d = $destinations->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-3">
                                                <img src="../<?= htmlspecialchars($d['image']) ?>" alt="thumbnail"
                                                    class="w-10 h-10 rounded-lg object-cover shadow-sm bg-slate-100 border border-slate-200">
                                                <strong
                                                    class="text-sm font-bold text-dark"><?= htmlspecialchars($d['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                                                <i class="fa-solid fa-map-pin mr-1 text-slate-400"></i>
                                                <?= htmlspecialchars($d['division']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-medium text-slate-500">
                                            <?= htmlspecialchars($d['location']) ?></td>
                                        <td class="py-4 px-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $d['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                                <?= htmlspecialchars(ucfirst($d['status']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="../destinations.php#<?= rawurlencode($d['slug']) ?>" target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:border-slate-300 hover:text-dark shadow-sm transition-colors"
                                                    title="View Public Page"><i class="fa-solid fa-eye text-xs"></i></a>
                                                <form method="POST" action="destinations.php" class="inline-block">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="destination_id" value="<?= (int) $d['id'] ?>">
                                                    <button type="submit"
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:border-slate-300 hover:text-dark shadow-sm transition-colors"
                                                        title="Toggle status"><i
                                                            class="fa-solid fa-power-off text-xs"></i></button>
                                                </form>
                                                <form method="POST" action="destinations.php"
                                                    onsubmit="return confirm('Delete this destination? Linked packages may also be affected.')"
                                                    class="inline-block">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="destination_id" value="<?= (int) $d['id'] ?>">
                                                    <button type="submit"
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-rose-500 border border-slate-200 hover:border-rose-300 hover:bg-rose-50 shadow-sm transition-colors"
                                                        title="Delete">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-slate-500 font-medium">No
                                        destinations found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>