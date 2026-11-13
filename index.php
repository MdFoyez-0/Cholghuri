<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'CholoGhuri | Explore More. Live Better. - Bangladesh Travel & Tour Booking';
require_once __DIR__ . '/includes/header.php';

// Fetch top destinations
$destinations = [];
if ($conn) {
    $destinations = $conn->query("SELECT * FROM destinations WHERE status='active' ORDER BY id ASC LIMIT 6");
}

// Fetch active tour packages
$packages = [];
if ($conn) {
    $packages = $conn->query("SELECT * FROM tour_packages WHERE status='active' ORDER BY id DESC LIMIT 6");
}

// Fetch featured reviews
$reviews = [];
if ($conn) {
    $reviews = $conn->query("
        SELECT r.*, u.name as user_name, p.title as package_title 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN tour_packages p ON r.package_id = p.id 
        WHERE r.status = 'approved' 
        ORDER BY r.rating DESC, r.id DESC LIMIT 3
    ");
}
?>

<!-- Hero Section (Tailwind) -->
<section
    class="relative bg-dark bg-cover bg-center bg-no-repeat pt-32 pb-40 flex items-center min-h-[650px] lg:min-h-[750px]"
    style="background-image: url('images/destinations/sajek.jpg');">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-dark/95 via-dark/70 to-transparent"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full reveal-up">
        <div class="max-w-3xl">
            <div class="text-yellow-400 font-bold tracking-[0.2em] text-sm md:text-base mb-6 drop-shadow-md">🇧🇩 TRAVEL
                • EXPLORE • DISCOVER BANGLADESH</div>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-white leading-[1.1] mb-6 drop-shadow-lg">
                Discover Beautiful Bangladesh</h1>
            <p class="text-lg md:text-xl text-slate-200 mb-10 max-w-2xl leading-relaxed">From the world's longest
                unbroken natural sea beach in Cox's Bazar to the cloud-kissed summits of Sajek Valley and the deep
                mysteries of the Sundarbans mangrove forest.</p>

            <form action="packages.php" method="GET"
                class="bg-white/10 backdrop-blur-md p-2 rounded-full flex gap-2 max-w-2xl border border-white/20 shadow-2xl focus-within:bg-white/20 transition-all duration-300">
                <input id="packageSearch" name="search" type="text"
                    placeholder="Search destinations, tours, hills, beaches..."
                    class="flex-1 bg-transparent border-0 outline-none px-6 text-white placeholder:text-slate-200 text-lg">
                <button type="submit"
                    class="bg-gradient-to-r from-primary to-rose-500 hover:from-rose-500 hover:to-primary text-white font-bold py-4 px-8 rounded-full shadow-lg hover:shadow-primary/40 transition-all duration-300 transform hover:-translate-y-1">Explore
                    Tours</button>
            </form>
        </div>
    </div>
</section>

<!-- Featured Real Destinations Section (Tailwind) -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal-up">
            <span class="text-sky font-bold tracking-widest text-sm mb-2 block uppercase">Explore Horizons</span>
            <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Iconic Destinations</h2>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto">Authentic landscapes, misty mountains, and serene
                coastlines across Bangladesh.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($destinations && $destinations->num_rows > 0): ?>
                <?php while ($d = $destinations->fetch_assoc()): ?>
                    <a href="packages.php?destination=<?= urlencode($d['name']) ?>"
                        class="group block relative h-80 rounded-3xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 reveal-up">
                        <img src="<?= htmlspecialchars($d['image']) ?>" alt="<?= htmlspecialchars($d['name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out"
                            onerror="this.src='images/destinations/coxsbazar.jpg'">

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-dark/95 via-dark/40 to-transparent flex flex-col justify-end p-8">
                            <span class="text-xs uppercase tracking-wider text-sky font-bold mb-2">📍
                                <?= htmlspecialchars($d['division']) ?> Division</span>
                            <h3 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($d['name']) ?></h3>
                            <p class="text-slate-300 text-sm line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($d['description']) ?></p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-16 reveal-up">
            <a href="destinations.php"
                class="inline-flex items-center gap-2 border-2 border-slate-200 text-dark font-semibold py-3 px-8 rounded-full hover:border-dark hover:bg-slate-50 transition-colors">
                View All Destinations <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Popular Tour Packages Section (Tailwind) -->
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal-up">
            <span class="text-primary font-bold tracking-widest text-sm mb-2 block uppercase">Popular Picks</span>
            <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Handpicked Tour Packages</h2>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto">All-inclusive guided expeditions designed for solo explorers, couples, and family groups.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($packages && $packages->num_rows > 0): ?>
                <?php while ($p = $packages->fetch_assoc()): ?>
                    <article class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-slate-100 flex flex-col justify-between transition-all duration-300 hover:-translate-y-2 reveal-up">
                        <div>
                            <div class="relative h-56">
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover" onerror="this.src='images/destinations/coxsbazar.jpg'">
                                <span class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm text-dark px-3 py-1.5 rounded-full text-xs font-bold shadow-sm">
                                    📍 <?= htmlspecialchars($p['destination']) ?>
                                </span>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-dark mb-3 line-clamp-1"><?= htmlspecialchars($p['title']) ?></h3>
                                <div class="flex justify-between items-center text-sm font-medium text-slate-500 mb-4 pb-4 border-b border-slate-100">
                                    <span>📅 <?= htmlspecialchars($p['duration']) ?></span>
                                    <span class="text-sky bg-sky/10 px-2 py-1 rounded-md">🪑 <?= (int) $p['available_seats'] ?> seats left</span>
                                </div>
                                <p class="text-slate-500 text-sm line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($p['description']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="px-6 pb-6 mt-auto">
                            <div class="flex justify-between items-baseline mb-5">
                                <div class="text-2xl font-extrabold text-primary">৳<?= number_format($p['price']) ?> <span class="text-sm font-medium text-slate-400">/ person</span></div>
                                <span class="text-xs text-emerald font-bold bg-emerald/10 px-2 py-1 rounded">Verified Guide</span>
                            </div>
                            <div class="flex gap-3">
                                <a class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-dark font-semibold py-3 rounded-xl transition-colors text-sm" href="package-details.php?id=<?= (int) $p['id'] ?>">View Details</a>
                                <a class="bg-sky hover:bg-sky/90 text-white flex items-center justify-center px-5 rounded-xl transition-colors shadow-lg shadow-sky/30 hover:-translate-y-0.5" href="booking.php?package_id=<?= (int) $p['id'] ?>" title="Book Now">
                                    <i class="fa-solid fa-bolt"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-16 reveal-up">
            <a href="packages.php" class="inline-flex items-center gap-2 bg-dark hover:bg-slate-800 text-white font-semibold py-3 px-8 rounded-full shadow-xl hover:shadow-2xl transition-all hover:-translate-y-1">
                Browse All Tours (<?= $packages ? $packages->num_rows : 0 ?>+) <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Why CholoGhuri Feature Grid (Tailwind Powered) -->
<section class="py-24 bg-slate-50" id="about">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal-up">
            <span class="text-emerald font-bold tracking-widest text-sm mb-2 block uppercase">Why CholoGhuri?</span>
            <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Travel Made Simple & Reliable</h2>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto">Everything you need for hassle-free adventures across
                Bangladesh.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div
                class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 text-center reveal-up">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl bg-sky/10 text-sky flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-xl font-bold text-dark mb-3">Guaranteed Departure</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Fixed scheduled tours with pre-arranged AC bus
                    transports and verified resort stays.</p>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 text-center reveal-up"
                style="transition-delay: 100ms;">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl bg-emerald/10 text-emerald flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <h3 class="text-xl font-bold text-dark mb-3">Transparent Pricing</h3>
                <p class="text-slate-500 text-sm leading-relaxed">No hidden service charges. Pay securely with bKash,
                    Nagad, Card, or cash at counter.</p>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 text-center reveal-up"
                style="transition-delay: 200ms;">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl bg-orange-100 text-orange-500 flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3 class="text-xl font-bold text-dark mb-3">Authentic Real Locations</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Real photos, local licensed guides, and curated
                    indigenous cultural immersion.</p>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 text-center reveal-up"
                style="transition-delay: 300ms;">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl mb-6">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="text-xl font-bold text-dark mb-3">24/7 Helpline Support</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Dedicated on-ground conductors and emergency support
                    throughout your journey.</p>
            </div>
        </div>
    </div>
</section>

<!-- Real Traveler Reviews Section (Tailwind) -->
<?php if ($reviews && $reviews->num_rows > 0): ?>
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal-up">
                <span class="text-orange-500 font-bold tracking-widest text-sm mb-2 block uppercase">Traveler Stories</span>
                <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Loved by Explorers</h2>
                <p class="text-slate-500 text-lg max-w-2xl mx-auto">Verified reviews from travelers who experienced journeys with CholoGhuri.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($rev = $reviews->fetch_assoc()): ?>
                    <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-lg transition-all reveal-up">
                        <div class="flex justify-between items-center mb-5">
                            <span class="text-amber-400 text-lg tracking-widest"><?= str_repeat('★', (int) $rev['rating']) ?></span>
                            <small class="text-slate-400 font-medium"><?= date('d M Y', strtotime($rev['created_at'])) ?></small>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6 italic">"<?= htmlspecialchars($rev['comment']) ?>"</p>
                        <div class="flex items-center gap-4 pt-5 border-t border-slate-50">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-dark flex items-center justify-center font-bold text-lg">
                                <?= htmlspecialchars(strtoupper(substr($rev['user_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div>
                                <div class="font-bold text-dark text-sm"><?= htmlspecialchars($rev['user_name']) ?></div>
                                <small class="text-slate-400 text-xs font-medium line-clamp-1">Tour: <?= htmlspecialchars($rev['package_title']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Contact Section (Tailwind) -->
<section class="py-24 bg-slate-50" id="contact">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="mb-12 reveal-up">
            <span class="text-sky font-bold tracking-widest text-sm mb-2 block uppercase">Get In Touch</span>
            <h2 class="text-4xl md:text-5xl font-extrabold text-dark mb-4">Questions About Your Trip?</h2>
            <p class="text-slate-500 text-lg">Our travel advisors are here to help you plan custom corporate tours, family getaways, and university trips.</p>
        </div>

        <div class="bg-white p-10 md:p-14 rounded-[2rem] shadow-xl border border-slate-100 reveal-up">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left mb-10">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-sky/10 text-sky flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <h4 class="text-sm text-slate-500 font-semibold mb-1">Phone Hotline</h4>
                        <p class="text-lg font-bold text-dark">+880 1712-345678</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <h4 class="text-sm text-slate-500 font-semibold mb-1">Email Support</h4>
                        <p class="text-lg font-bold text-dark">support@chologhuri.com</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald/10 text-emerald flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-location-dot"></i></div>
                    <div>
                        <h4 class="text-sm text-slate-500 font-semibold mb-1">Office Location</h4>
                        <p class="text-lg font-bold text-dark">Panthapath, Dhaka</p>
                    </div>
                </div>
            </div>
            
            <a href="packages.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary to-rose-500 hover:from-rose-500 hover:to-primary text-white font-bold py-4 px-10 rounded-full shadow-lg hover:shadow-primary/40 transition-all hover:-translate-y-1 w-full md:w-auto justify-center">
                Book a Tour Package Now <i class="fa-solid fa-plane-departure"></i>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>