<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'All Tour Packages | CholoGhuri';
require_once __DIR__ . '/includes/header.php';

// Search and filter parameters
$search = trim(input_string($_GET, 'search'));
$dest_filter = trim(input_string($_GET, 'destination'));
$duration_filter = trim(input_string($_GET, 'duration'));
$sort = input_string($_GET, 'sort', 'newest');
$search = substr($search, 0, 200);
$dest_filter = substr($dest_filter, 0, 100);
$duration_filter = substr($duration_filter, 0, 50);
if (!in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) {
    $sort = 'newest';
}

$sql = "SELECT * FROM tour_packages WHERE status = 'active'";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR destination LIKE ? OR description LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sss';
}

if (!empty($dest_filter)) {
    $sql .= " AND destination = ?";
    $params[] = $dest_filter;
    $types .= 's';
}

if (!empty($duration_filter)) {
    $sql .= " AND duration LIKE ?";
    $params[] = "%$duration_filter%";
    $types .= 's';
}

// Sorting
if ($sort === 'price_asc') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY price DESC";
} else {
    $sql .= " ORDER BY id DESC";
}

$packages = [];
$destinations_list = [];
if ($conn) {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $packages = $stmt->get_result();
    } else {
        $packages = $conn->query($sql);
    }
    $destinations_list = $conn->query("SELECT DISTINCT destination FROM tour_packages WHERE status='active' ORDER BY destination ASC");
}
?>

<section class="py-16 md:py-24 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-12 reveal-up text-center md:text-left">
            <div class="text-orange-500 font-bold tracking-widest text-sm mb-2 uppercase">Tour Catalog</div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Explore Bangladesh Tour Packages</h1>
            <p class="text-slate-500 text-lg max-w-3xl">Discover scheduled group tours, adventure expeditions, and
                weekend escapes.</p>
        </div>

        <!-- Filter & Search Controls -->
        <form method="GET" action="packages.php"
            class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm mb-12 flex flex-col md:flex-row flex-wrap gap-5 reveal-up items-end">
            <div class="flex-grow w-full md:w-auto">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Search
                    Keyword</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search Cox's Bazar, Sajek..."
                        value="<?= htmlspecialchars($search) ?>"
                        class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-11 py-3 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all placeholder:text-slate-400">
                </div>
            </div>

            <div class="w-full md:w-48">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Destination</label>
                <select name="destination"
                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all appearance-none cursor-pointer">
                    <option value="">All Destinations</option>
                    <?php if ($destinations_list):
                        while ($d = $destinations_list->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($d['destination']) ?>" <?= $dest_filter === $d['destination'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['destination']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                </select>
            </div>

            <div class="w-full md:w-40">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Duration</label>
                <select name="duration"
                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all appearance-none cursor-pointer">
                    <option value="">Any Duration</option>
                    <option value="2 Days" <?= $duration_filter === '2 Days' ? 'selected' : '' ?>>2 Days Tour</option>
                    <option value="3 Days" <?= $duration_filter === '3 Days' ? 'selected' : '' ?>>3 Days Tour</option>
                </select>
            </div>

            <div class="w-full md:w-40">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sort By</label>
                <select name="sort"
                    class="w-full bg-slate-50 border border-slate-200 text-dark rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-sky/50 focus:border-sky transition-all appearance-none cursor-pointer">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Added</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </div>

            <div class="flex gap-3 w-full md:w-auto mt-2 md:mt-0">
                <button type="submit"
                    class="flex-1 md:flex-none bg-primary hover:bg-rose-500 text-white font-bold py-3 px-6 rounded-xl shadow-md hover:shadow-primary/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="packages.php"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-6 rounded-xl transition-all active:scale-95 flex items-center justify-center">Reset</a>
            </div>
        </form>

        <!-- Tour Cards Grid -->
        <?php if ($packages && $packages->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php while ($p = $packages->fetch_assoc()): ?>
                    <article
                        class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-slate-100 flex flex-col justify-between transition-all duration-300 hover:-translate-y-2 reveal-up">
                        <div>
                            <div class="relative h-56">
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"
                                    class="w-full h-full object-cover" onerror="this.src='images/destinations/coxsbazar.jpg'">
                                <span
                                    class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm text-dark px-3 py-1.5 rounded-full text-xs font-bold shadow-sm">
                                    📍 <?= htmlspecialchars($p['destination']) ?>
                                </span>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-dark mb-3 line-clamp-1"><?= htmlspecialchars($p['title']) ?>
                                </h3>
                                <div
                                    class="flex justify-between items-center text-sm font-medium text-slate-500 mb-4 pb-4 border-b border-slate-100">
                                    <span>📅 <?= htmlspecialchars($p['duration']) ?></span>
                                    <span class="text-sky bg-sky/10 px-2 py-1 rounded-md">🪑 <?= (int) $p['available_seats'] ?>
                                        left</span>
                                </div>
                                <p class="text-slate-500 text-sm line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($p['description']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="px-6 pb-6 mt-auto">
                            <div class="flex justify-between items-baseline mb-5">
                                <div class="text-2xl font-extrabold text-primary">৳<?= number_format($p['price']) ?> <span
                                        class="text-sm font-medium text-slate-400">/ person</span></div>
                            </div>
                            <div class="flex gap-3">
                                <a class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-dark font-semibold py-3 rounded-xl transition-colors text-sm"
                                    href="package-details.php?id=<?= (int) $p['id'] ?>">Details</a>
                                <a class="bg-sky hover:bg-sky/90 text-white flex items-center justify-center px-5 rounded-xl transition-colors shadow-lg shadow-sky/30 hover:-translate-y-0.5"
                                    href="booking.php?package_id=<?= (int) $p['id'] ?>" title="Book Now">
                                    <i class="fa-solid fa-bolt"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl border border-slate-100 p-16 text-center shadow-sm reveal-up">
                <div
                    class="w-24 h-24 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center text-4xl mx-auto mb-6">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                </div>
                <h3 class="text-2xl font-bold text-dark mb-3">No Tour Packages Found</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-8">We couldn't find any packages matching your filters. Try
                    adjusting your search criteria or resetting the filters.</p>
                <a href="packages.php"
                    class="inline-flex bg-dark hover:bg-slate-800 text-white font-bold py-3 px-8 rounded-full shadow-lg transition-all hover:-translate-y-1 items-center gap-2">View
                    All Tours <i class="fa-solid fa-rotate-right"></i></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>