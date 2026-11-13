<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Top Destinations in Bangladesh | CholoGhuri';
require_once __DIR__ . '/includes/header.php';

$destinations = [];
if ($conn) {
    $destinations = $conn->query("
        SELECT d.*, 
               (SELECT COUNT(*) FROM tour_packages WHERE destination = d.name AND status = 'active') as tour_count 
        FROM destinations d 
        WHERE d.status = 'active' 
        ORDER BY d.name ASC
    ");
}
?>

<section class="py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal-up">
            <span
                class="inline-block py-1 px-3 rounded-full bg-sky-50 text-primary text-xs font-bold tracking-widest uppercase mb-3 border border-sky-100">Explore
                Bangladesh</span>
            <h1 class="text-4xl md:text-5xl font-black text-dark mb-4 tracking-tight">Explore Majestic Destinations</h1>
            <p class="text-slate-500 text-lg font-medium">Discover the natural wonders of Bangladesh with authentic
                photographs, travel guides, and scheduled packages.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($destinations && $destinations->num_rows > 0): ?>
                <?php while ($d = $destinations->fetch_assoc()): ?>
                    <div id="<?= htmlspecialchars($d['slug']) ?>"
                        class="bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-[0_4px_20px_rgba(11,85,117,0.06)] flex flex-col justify-between group hover:-translate-y-1 transition-all duration-300 reveal-up">
                        <div>
                            <div class="relative h-60 overflow-hidden">
                                <img src="<?= htmlspecialchars($d['image']) ?>" alt="<?= htmlspecialchars($d['name']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out"
                                    onerror="this.src='images/destinations/coxsbazar.jpg'">
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-dark/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                </div>
                                <span
                                    class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm text-dark px-3 py-1.5 rounded-lg text-xs font-bold tracking-wide shadow-sm flex items-center gap-1.5">
                                    <i class="fa-solid fa-map-pin text-primary"></i> <?= htmlspecialchars($d['division']) ?>
                                    Division
                                </span>
                            </div>

                            <div class="p-6">
                                <h3 class="text-2xl font-black text-dark mb-2 group-hover:text-primary transition-colors">
                                    <?= htmlspecialchars($d['name']) ?></h3>
                                <p class="text-sm font-semibold text-sky-500 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-compass"></i> <?= htmlspecialchars($d['location']) ?>
                                </p>
                                <p class="text-slate-500 text-sm leading-relaxed line-clamp-3">
                                    <?= htmlspecialchars($d['description']) ?>
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 border-t border-slate-50 bg-slate-50/50 flex justify-between items-center mt-2 group-hover:bg-sky-50/30 transition-colors">
                            <span class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center shrink-0"><i
                                        class="fa-solid fa-route"></i></span>
                                <?= (int) $d['tour_count'] ?> Active Tours
                            </span>
                            <a href="packages.php?destination=<?= urlencode($d['name']) ?>"
                                class="text-sm font-bold text-primary hover:text-sky-700 flex items-center gap-2 transition-colors">
                                View Packages <i
                                    class="fa-solid fa-arrow-right-long group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-2 lg:col-span-3 py-20 text-center">
                    <div
                        class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-mountain-sun"></i></div>
                    <h3 class="text-xl font-bold text-dark mb-2">No Destinations Found</h3>
                    <p class="text-slate-500">We are currently updating our destination list. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>