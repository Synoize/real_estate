<?php

require_once __DIR__ . '/includes/app_helpers.php';

$slug = trim($_GET['slug'] ?? '');
$project = $slug !== '' ? fetchProjectBySlug($slug) : null;

if (!$project) {
    setFlash('Project not found or not published.', 'warning');
    redirect(BASE_URL);
}

$projectLocation = trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'] . ', ' . $project['state'], ', ');
$pageTitle = $project['project_name'] . ($projectLocation ? ' in ' . $projectLocation : '');

$pdo->prepare('UPDATE projects SET total_views = total_views + 1 WHERE id = ?')->execute([$project['id']]);

$amenities = array_filter(array_map('trim', explode(',', (string)($project['amenities'] ?? ''))));

$unitStmt = $pdo->prepare('SELECT * FROM project_unit_plans WHERE project_id = ? ORDER BY price ASC');
$unitStmt->execute([$project['id']]);
$unitPlans = $unitStmt->fetchAll();

$videosStmt = $pdo->prepare('SELECT * FROM project_videos WHERE project_id = ? ORDER BY id ASC');
$videosStmt->execute([$project['id']]);
$projectVideos = $videosStmt->fetchAll();
$primaryVideoUrl = trim((string)($projectVideos[0]['video_url'] ?? $project['youtube_video_link'] ?? ''));

$planImagesStmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id = ? AND image_type IN ('floor_plan', 'master_plan') ORDER BY image_type, sort_order, id");
$planImagesStmt->execute([$project['id']]);
$planImages = $planImagesStmt->fetchAll();
$floorPlanImages = [];
$masterPlanImages = [];
foreach ($planImages as $img) {
    if ($img['image_type'] === 'floor_plan') {
        $floorPlanImages[] = $img;
    } else {
        $masterPlanImages[] = $img;
    }
}

$related = fetchPublishedProjects([
    'city' => $project['city'],
    'type' => $project['project_type']
], 4);
$projectGalleries = fetchProjectGalleryImagesForProjects([$project['id']]);
$projectGalleryImages = projectGalleryImages($project, $projectGalleries);
$relatedGalleries = fetchProjectGalleryImagesForProjects(array_column($related, 'id'), 4);
$overviewText = trim(preg_replace('/\s+/', ' ', strip_tags((string)($project['overview'] ?? ''))));
$pageDescription = $overviewText !== ''
    ? substr($overviewText, 0, 155)
    : $project['project_name'] . ' by ' . $project['company_name'] . ' in ' . $projectLocation . '. Compare price, amenities, unit plans, and book a free site visit.';
$pageKeywords = implode(', ', array_filter([
    $project['project_name'],
    $project['company_name'],
    $project['project_type'],
    $project['locality'],
    $project['city'],
    'real estate project',
    'site visit'
]));
$pageCanonical = BASE_URL . 'project/' . urlencode($project['slug']);
$pageImage = $projectGalleryImages[0] ?? projectImage($project);
$pageType = 'article';

require_once __DIR__ . '/includes/header.php';
?>

<section class="mt-20 py-6">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-10">
        <a href="<?php echo BASE_URL; ?>" class="group text-sm font-semibold text-gray-500">
            <i class="fa-solid fa-arrow-left transition-transform duration-300 group-hover:-translate-x-1 mr-1"></i> Back to projects
        </a>
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-8 justify-center">
            <div class="cursor-default">
                <h1 class="text-xl md:text-4xl font-medium leading-tight"><?php echo e($project['project_name']); ?></h1>
                <div class="mt-2 flex items-center gap-4">
                    <p class="text-sm text-gray-500"><?php echo e($project['project_type']); ?> by <span class="underline text-primary "><?php echo e($project['company_name']); ?></span></p>
                    <p class="text-gray-500 text-xs">
                        <i class="fa-solid fa-location-dot text-accent"></i>
                        <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'] . ', ' . $project['state'])); ?>
                    </p>
                </div>
            </div>
            <div>
                <p class="mt-1 text-lg sm:text-xl font-medium text-accent"><?php echo e(projectPriceRange($project, $unitPlans)); ?> <span class="text-gray-500 text-xs">(All inc)</span></p>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="#inquiry" class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">
                        <!-- ICONS -->
                        <div class="flex items-center gap-2">

                            <i class="fa-solid fa-laptop text-xs "></i>

                            <span class="text-white">|</span>

                            <i class="fa-solid fa-car text-xs"></i>

                        </div>

                        <!-- TEXT -->
                        <span>
                            Tour
                        </span>
                    </a>

                    <?php
                    $phone = preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']);

                    $message = "";

                    // Property Details
                    $message .= "Property Inquiry\n\n";
                    $message .= "Property Name: " . $project['project_name'] . "\n";

                    if (!empty($project['project_location'])) {
                        $message .= "Location: " . $project['project_location'] . "\n";
                    }

                    if (!empty(projectPriceRange($project, $unitPlans))) {
                        $message .= "Starting Price: ₹" . projectPriceRange($project, $unitPlans) . "\n";
                    }

                    if (!empty($project['project_status'])) {
                        $message .= "Status: " . $project['project_status'] . "\n";
                    }

                    if (!empty($project['builder_name'])) {
                        $message .= "Builder: " . $project['builder_name'] . "\n";
                    }

                    $message .= "\nI am interested in this property. Please share more details.";

                    // Property Image (First)
                    if (!empty($projectGalleryImages[0])) {
                        $message .= "\n\nProperty Image:\n" . $projectGalleryImages[0];
                    }

                    // Current Page URL (Optional)
                    $message .= "\nProperty Link:\n" . BASE_URL . 'project/' . $project['slug'];
                    ?>

                    <a target="_blank" href="https://wa.me/<?php echo $phone; ?>?text=<?php echo urlencode($message); ?>" class="flex items-center justify-center gap-2
        bg-green-600 text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                        <!-- WHATSAPP ICON -->
                        <i class="fa-brands fa-whatsapp text-sm"></i>

                        <!-- TEXT -->
                        <span>
                            Live Chat
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8" data-project-gallery>
            <div class="grid grid-cols-1 lg:grid-cols-[1.02fr_1fr] gap-4">

                <!-- LEFT BIG IMAGE -->
                <div class="relative rounded-[8px] overflow-hidden min-h-[420px] group">
                    <img
                        src="<?php echo e($projectGalleryImages[0] ?? ''); ?>"
                        alt="<?php echo e($project['project_name']); ?>"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                        data-gallery-image>

                    <!-- PREV -->
                    <?php if (count($projectGalleryImages) > 1): ?>
                        <button type="button"
                            data-gallery-prev
                            class="absolute left-5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/20 backdrop-blur text-white hover:bg-black/40 transition">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <!-- NEXT -->
                        <button type="button"
                            data-gallery-next
                            class="absolute right-5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/20 backdrop-blur text-white hover:bg-black/40 transition">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>

                    <!-- HEART -->
                    <form method="post" action="<?php echo BASE_URL; ?>actions" data-wishlist-form data-wishlist-project-id="<?php echo (int)$project['id']; ?>">
                        <?php $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']); ?>
                        <input type="hidden" name="action" value="wishlist">
                        <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
                        <button class="absolute top-5 right-5 w-[42px] h-[42px] rounded-full bg-black/10 backdrop-blur-md text-white flex items-center justify-center text-[18px] hover:opacity-90 transition" data-wishlist-button>
                            <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
                        </button>
                    </form>

                    <!-- BOTTOM ICONS -->
                    <div class="absolute bottom-5 right-5 flex items-center gap-2">
                        <a href="#location"
                            class="w-[38px] h-[38px] rounded-full bg-primary text-white flex items-center justify-center hover:scale-110 transition">
                            <i class="fa-solid fa-expand"></i>
                        </a>

                        <a href="#video"
                            class="w-[38px] h-[38px] rounded-full bg-accent text-white flex items-center justify-center hover:scale-110 transition">
                            <i class="fa-solid fa-play"></i>
                        </a>
                    </div>
                </div>

                <!-- RIGHT THUMBNAILS -->
                <div class="grid grid-cols-2 gap-4 h-[420px]">

                    <?php
                    $totalImages = count($projectGalleryImages);
                    for ($i = 1; $i <= 4; $i++):
                        if (!isset($projectGalleryImages[$i])) continue;

                        $remaining = $totalImages - 5;
                    ?>

                        <?php if ($i == 4 && $remaining > 0): ?>
                            <!-- LAST IMAGE -->
                            <div class="relative rounded-[8px] overflow-hidden cursor-pointer"
                                data-gallery-dot="<?php echo $i; ?>">

                                <img
                                    src="<?php echo e($projectGalleryImages[$i]); ?>"
                                    class="w-full h-[200px] object-cover brightness-[0.45]"
                                    alt="">

                                <div class="absolute inset-0 flex items-center justify-center">
                                    <h3 class="text-white text-2xl font-black">
                                        <?php echo $remaining; ?>+ more
                                    </h3>
                                </div>
                            </div>

                        <?php else: ?>

                            <div class="rounded-[8px] overflow-hidden cursor-pointer group"
                                data-gallery-dot="<?php echo $i; ?>">

                                <img
                                    src="<?php echo e($projectGalleryImages[$i]); ?>"
                                    alt=""
                                    class="w-full h-[200px] object-cover group-hover:scale-105 transition duration-500">
                            </div>

                        <?php endif; ?>

                    <?php endfor; ?>

                </div>
            </div>

            <!-- Hidden Images For JS Slider -->
            <div class="hidden">
                <?php foreach ($projectGalleryImages as $imageIndex => $imageUrl): ?>
                    <img
                        src="<?php echo e($imageUrl); ?>"
                        alt="<?php echo e($project['project_name']); ?>"
                        data-gallery-image>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="mt-6">
    <!-- TOP NAV -->
    <div class="sticky top-0 z-50 bg-primary shadow-md overflow-x-auto whitespace-nowrap scrollbar-hide">

        <div class="flex min-w-max">

            <a href="#overview"
                class="nav-link active text-white text-sm px-5 md:px-7 py-4 font-medium flex-shrink-0">
                Overview
            </a>

            <a href="#location"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Location
            </a>

            <a href="#video"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Video
            </a>

            <a href="#proscons"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Pros & Cons
            </a>

            <a href="#amenities"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Amenities
            </a>

            <a href="#plans"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Master & Floor Plans
            </a>

            <a href="#pricing"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Pricing & Unit Plans
            </a>

            <a href="#payment"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Payment Scheme
            </a>

            <a href="#litigation"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Litigation
            </a>

            <a href="#legal"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Legal
            </a>

            <a href="#banks"
                class="nav-link text-white text-sm px-5 md:px-7 py-4 opacity-90 hover:opacity-100 flex-shrink-0">
                Banks
            </a>

        </div>
    </div>

    <div class="max-w-[1920px] mx-auto relative z-40 bg-white px-4 sm:px-6 lg:px-10 py-6 md:py-12 grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8">
        <div class="space-y-6">
            <!-- OVERVIEW -->
            <div id="overview" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">

                <!-- HEADER -->
                <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                    <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                        <?php echo e($project['project_name']); ?> Overview
                    </h2>

                    <?php if (!empty($project['brochure'])): ?>
                        <a
                            href="<?php echo e($project['brochure']); ?>"
                            target="_blank"
                            class="bg-green-600 hover:bg-green-700 duration-300 text-white text-[12px] md:text-sm px-3 md:px-5 py-2 rounded-lg font-semibold inline-flex items-center gap-2">

                            Brochure
                            <i class="fa-solid fa-download"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="p-3 md:p-5">
                    <!-- OVERVIEW CARDS -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e($project['total_area'] ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Land Parcel</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-regular fa-building text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo (int)($project['total_towers'] ?? 0); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Towers</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-building-circle-check text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e($project['total_floors'] ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Floors</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-table-cells-large text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold truncate">
                                    <?php foreach ($unitPlans as $plan): ?>
                                        <?php echo e($plan['bhk_type']); ?>,
                                    <?php endforeach; ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Config</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-expand text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e(projectAreaRange($project, $unitPlans)); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Carpet Area</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-id-card text-[#1f4f79] text-[18px]"></i>
                            <div class="min-w-0">
                                <h3 class="text-[#1f4f79] text-[13px] md:text-[14px] font-semibold truncate">
                                    <?php echo e($project['rera_number'] ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">RERA No.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-person-digging text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e($project['project_status'] ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Status</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-regular fa-clock text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e(date('d M Y', strtotime($project['launch_date'])) ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Launch Date</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-regular fa-calendar text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo e(date('d M Y', strtotime($project['possession_date'])) ?: 'NA'); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Possession Date</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[#d9dee3] shadow rounded-md p-3 flex items-center gap-3">
                            <i class="fa-solid fa-home text-[#1f4f79] text-[18px]"></i>
                            <div>
                                <h3 class="text-[#008d4f] text-[13px] md:text-[14px] font-semibold">
                                    <?php echo (int)($project['total_units'] ?? 0); ?>
                                </h3>
                                <p class="text-[10px] md:text-[12px] text-gray-500">Units</p>
                            </div>
                        </div>

                    </div>

                    <!-- ABOUT -->
                    <div class="mt-6 ">
                        <h3 class="text-[16px] font-semibold text-[#374151]">
                            About
                        </h3>

                        <?php
                        $overview = $project['overview'] ?: 'Project details will be updated soon.';
                        $shortOverview = mb_substr(strip_tags($overview), 0, 220);
                        ?>

                        <p id="overviewText" class="my-2 text-[12px] sm:text-[14px] text-gray-700 leading-4">
                            <?php echo nl2br(e($shortOverview)); ?>
                            <?php if (mb_strlen(strip_tags($overview)) > 220): ?>...
                        <?php endif; ?>
                        </p>

                        <?php if (mb_strlen(strip_tags($overview)) > 220): ?>
                            <button id="readMoreBtn" class="text-[#008d4f] font-semibold text-[12px]">
                                Read More...
                            </button>

                            <script>
                                const fullText = <?php echo json_encode(nl2br(e($overview))); ?>;
                                const shortText = <?php echo json_encode(nl2br(e($shortOverview)) . '...'); ?>;
                                let expanded = false;

                                document.getElementById('readMoreBtn').addEventListener('click', function() {
                                    const text = document.getElementById('overviewText');

                                    if (!expanded) {
                                        text.innerHTML = fullText;
                                        this.innerText = 'Read Less';
                                    } else {
                                        text.innerHTML = shortText;
                                        this.innerText = 'Read More...';
                                    }

                                    expanded = !expanded;
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="location" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                    <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                        Location
                    </h2>
                </div>
                <div class="p-3 md:p-5">
                    <?php if (!empty($project['location_details'])): ?>
                        <div class="text-[14px] text-gray-700 leading-7 mb-6">
                            <?= nl2br(e($project['location_details'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[14px] text-gray-500">
                            Location details not available.
                        </p>
                    <?php endif; ?>

                    <?php
                    $lat = (float)($project['latitude'] ?? 0);
                    $lng = (float)($project['longitude'] ?? 0);
                    $hasCoords = $lat != 0 && $lng != 0;
                    ?>

                    <?php if ($hasCoords): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <div class="relative rounded-2xl overflow-hidden border border-gray-300">
                                <iframe src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&output=embed&z=15"
                                    class="w-full h-[220px] md:h-[320px]" style="border:0" allowfullscreen loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>

                            <?php $streetViewUrl = "https://www.google.com/maps?q={$lat},{$lng}&output=embed&layer=c"; ?>
                            <div class="relative rounded-2xl overflow-hidden border border-gray-300 group cursor-pointer"
                                onclick="window.open('https://www.google.com/maps/@<?php echo $lat; ?>,<?php echo $lng; ?>,17.5z?entry=ttu', '_blank')">
                                <iframe src="<?php echo e($streetViewUrl); ?>"
                                    class="w-full h-[220px] md:h-[320px] pointer-events-none" style="border:0" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 transition flex items-center justify-center">
                                    <div class="w-[80px] h-[80px] rounded-full border-4 border-white flex items-center justify-center bg-white/10 backdrop-blur-sm">
                                        <div class="text-center">
                                            <h2 class="text-white text-[22px] font-bold leading-none">360°</h2>
                                            <i class="fa-solid fa-rotate text-white text-[24px] mt-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($project['pros']) || !empty($project['cons'])): ?>
                <div id="proscons" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Pros & Cons
                        </h2>
                    </div>
                    <div class="p-3 md:p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if (!empty($project['pros'])): ?>
                            <div>
                                <h3 class="text-[15px] font-semibold text-green-600 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-thumbs-up"></i> Pros
                                </h3>
                                <div class="text-[14px] text-gray-700 leading-7">
                                    <?php echo nl2br(e($project['pros'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($project['cons'])): ?>
                            <div>
                                <h3 class="text-[15px] font-semibold text-red-500 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-thumbs-down"></i> Cons
                                </h3>
                                <div class="text-[14px] text-gray-700 leading-7">
                                    <?php echo nl2br(e($project['cons'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="amenities" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                <!-- HEADER -->
                <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                    <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                        Amenities
                    </h2>
                </div>
                <div class="p-3 md:p-5 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3">
                    <?php foreach ($amenities ?: ['Verified builder', 'Direct inquiry', 'Free site visit'] as $amenity): ?>
                        <div class="rounded-md border border-gray-200 px-4 py-3 text-xs font-semibold text-gray-700 flex gap-2 justify-start items-center">
                            <i class="fa-solid fa-circle-check text-accent text-sm"></i>
                            <?php echo e($amenity); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($primaryVideoUrl) || !empty($projectVideos)): ?>
                <div id="video" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Video
                        </h2>
                    </div>
                    <div class="p-3 md:p-5 gap-4 grid sm:grid-cols-2">
                        <?php foreach ($projectVideos as $video): ?>
                            <?php $embedUrl = videoEmbedUrl($video['video_url'] ?? ''); ?>
                            <?php if ($embedUrl !== ''): ?>
                                <div class="aspect-video rounded-lg overflow-hidden bg-black">
                                    <iframe src="<?php echo e($embedUrl); ?>" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($projectVideos) && !empty($primaryVideoUrl)): ?>
                            <?php $embedUrl = videoEmbedUrl($primaryVideoUrl); ?>
                            <?php if ($embedUrl !== ''): ?>
                                <div class="aspect-video rounded-lg overflow-hidden bg-black">
                                    <iframe src="<?php echo e($embedUrl); ?>" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($floorPlanImages) || !empty($masterPlanImages)): ?>
                <div id="plans" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Master & Floor Plans
                        </h2>
                    </div>
                    <div class="p-3 md:p-5 space-y-6">
                        <?php if (!empty($masterPlanImages)): ?>
                            <div>
                                <h3 class="text-[15px] font-semibold text-primary mb-3">Master Plan</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach ($masterPlanImages as $img): ?>
                                        <a href="<?php echo e(getImageUrl($img['image'])); ?>" target="_blank" class="block rounded-lg overflow-hidden border border-gray-200 hover:opacity-90 transition">
                                            <img src="<?php echo e(getImageUrl($img['image'])); ?>" alt="Master Plan" class="w-full h-48 object-cover">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($floorPlanImages)): ?>
                            <div>
                                <h3 class="text-[15px] font-semibold text-primary mb-3">Floor Plans</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach ($floorPlanImages as $img): ?>
                                        <a href="<?php echo e(getImageUrl($img['image'])); ?>" target="_blank" class="block rounded-lg overflow-hidden border border-gray-200 hover:opacity-90 transition">
                                            <img src="<?php echo e(getImageUrl($img['image'])); ?>" alt="Floor Plan" class="w-full h-48 object-cover">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($unitPlans)): ?>
                <div id="pricing" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <!-- HEADER -->
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Pricing & Unit Plans
                        </h2>
                    </div>
                    <!-- Scroll Wrapper -->
                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[420px] text-left">

                            <!-- Header -->
                            <thead class="bg-slate-50 text-gray-700 text-[12px] md:text-sm">
                                <tr>
                                    <th class="px-4 py-2 sm:py-4 font-semibold">Unit</th>
                                    <th class="px-4 py-2 sm:py-4 font-semibold">BHK</th>
                                    <th class="px-4 py-2 sm:py-4 font-semibold">Area</th>
                                    <th class="px-4 py-2 sm:py-4 font-semibold text-right">Price</th>
                                </tr>
                            </thead>

                            <!-- Body -->
                            <tbody class="divide-y divide-gray-100 text-[12px] md:text-sm">

                                <?php foreach ($unitPlans as $plan): ?>
                                    <tr class="hover:bg-gray-50 transition duration-200">

                                        <!-- Unit -->
                                        <td class="px-4 py-2 sm:py-4">
                                            <div class="font-bold text-gray-900">
                                                <?php echo e($plan['unit_name'] ?: 'NA'); ?>
                                            </div>
                                        </td>

                                        <!-- BHK -->
                                        <td class="px-4 py-2 sm:py-4 text-gray-700 font-medium">
                                            <?php echo e($plan['bhk_type'] ?: 'NA'); ?>
                                        </td>

                                        <!-- Area -->
                                        <td class="px-4 py-2 sm:py-4 text-gray-600">
                                            <?php echo e($plan['area'] ?: 'NA'); ?>
                                        </td>

                                        <!-- Price -->
                                        <td class="px-4 py-2 sm:py-4 text-right">
                                            <span class="inline-flex rounded-full bg-[#e8f7ef] px-3 py-1 text-[#008d4f] font-bold">
                                                <?php echo e(!empty($plan['price']) ? formatCurrency($plan['price']) : 'On Request'); ?>
                                            </span>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>

                    </div>

                </div>
            <?php endif; ?>

            <?php if (!empty($project['payment_scheme'])): ?>
                <div id="payment" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Payment Scheme
                        </h2>
                    </div>
                    <div class="p-3 md:p-5">
                        <div class="text-[14px] text-gray-700 leading-7">
                            <?php echo nl2br(e($project['payment_scheme'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['litigation_details'])): ?>
                <div id="litigation" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Litigation
                        </h2>
                    </div>
                    <div class="p-3 md:p-5">
                        <div class="text-[14px] text-gray-700 leading-7">
                            <?php echo nl2br(e($project['litigation_details'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['legal_details'])): ?>
                <div id="legal" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Legal
                        </h2>
                    </div>
                    <div class="p-3 md:p-5">
                        <div class="text-[14px] text-gray-700 leading-7">
                            <?php echo nl2br(e($project['legal_details'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['bank_details'])): ?>
                <div id="banks" class="bg-white border border-gray-300 rounded-2xl overflow-hidden">
                    <div class="bg-slate-100 px-4 py-4 border-b border-gray-300 flex items-center justify-between">
                        <h2 class="text-[16px] md:text-[20px] font-bold text-primary">
                            Banks
                        </h2>
                    </div>
                    <div class="p-3 md:p-5">
                        <div class="text-[14px] text-gray-700 leading-7">
                            <?php echo nl2br(e($project['bank_details'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <div class="relative">
            <aside id="inquiry" class="sticky top-24 space-y-5">

                <!-- Contact Builder -->
                <form method="post" action="<?php echo BASE_URL; ?>actions"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

                    <input type="hidden" name="action" value="inquiry">
                    <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">

                    <h2 class="text-[18px] md:text-[20px] font-bold text-primary">
                        Contact Builder
                    </h2>

                    <div class="mt-4 space-y-3">
                        <input name="full_name" required placeholder="Full name"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">

                        <input name="phone" required placeholder="Mobile number"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">

                        <input name="email" required type="email" placeholder="Email address"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">

                        <input name="budget" placeholder="Budget"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">

                        <textarea name="message" placeholder="Message"
                            class="min-h-24 w-full rounded-lg border border-gray-200 px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>

                    <button class="mt-4 h-12 w-full rounded-lg bg-primary font-semibold text-white hover:opacity-90 duration-300">
                        Send Inquiry
                    </button>
                </form>

                <!-- Site Visit -->
                <form method="post" action="<?php echo BASE_URL; ?>actions"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

                    <input type="hidden" name="action" value="site_visit">
                    <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">

                    <h2 class="text-[18px] md:text-[20px] font-bold text-primary">
                        Book Free Site Visit
                    </h2>

                    <div class="mt-4 space-y-3">
                        <input name="full_name" required placeholder="Full name"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm">

                        <input name="phone" required placeholder="Mobile number"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm">

                        <input name="email" required type="email" placeholder="Email address"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm">

                        <input name="visit_date" required type="date"
                            min="<?php echo date('Y-m-d'); ?>"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm">

                        <input name="preferred_time" placeholder="Preferred time"
                            class="h-12 w-full rounded-lg border border-gray-200 px-3 text-sm">
                    </div>

                    <button class="mt-4 h-12 w-full rounded-lg bg-accent font-semibold text-primary hover:opacity-90 duration-300">
                        Schedule Now
                    </button>
                </form>

            </aside>
        </div>
    </div>
</section>

<!-- Related Projects -->
<section class="pt-6 pb-10">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-10">
        <!-- TOP AREA -->
        <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-8">
            Similar Projects
        </h2>

        <?php if (empty($related)): ?>
            <div class="h-[56vh] p-4 flex justify-center flex-col items-center text-center">
                <i class="fa-solid fa-building mb-4 text-4xl md:text-6xl text-gray-400"></i>
                <h3 class="text-xl font-bold text-gray-900">No projects found</h3>
                <p class="mt-2 text-gray-500 text-sm">Try a different city and budget.</p>
            </div>
        <?php else: ?>
            <div class="swiper propertySwiper overflow-visible">
                <div class="swiper-wrapper">
                    <?php foreach ($related as $project): ?>
                        <?php
                        $unitPlans = $projectUnitPlans[(int)$project['id']] ?? [];
                        $saleBadge = projectSaleBadge($project);
                        $primaryVideo = $projectVideos[(int)$project['id']] ?? [];
                        $videoUrl = trim((string)($primaryVideo['video_url'] ?? $project['youtube_video_link'] ?? ''));
                        $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']);
                        $galleryImages = projectGalleryImages($project, $projectGalleries ?? []);
                        ?>
                        <article class="swiper-slide">
                            <div class="relative bg-white rounded-2xl border border-gray-200 p-3 transition duration-300 hover:-translate-y-1 hover:shadow-sm">
                                <!-- IMAGE -->
                                <a target="_blank" href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="relative block overflow-hidden rounded-2xl" data-project-gallery>
                                    <?php foreach ($galleryImages as $imageIndex => $imageUrl): ?>
                                        <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($project['project_name']); ?>"
                                            class="w-full h-[220px] object-cover <?php echo $imageIndex === 0 ? '' : 'hidden'; ?>"
                                            data-gallery-image />
                                    <?php endforeach; ?>

                                    <?php if (count($galleryImages) > 1): ?>
                                        <button type="button" data-gallery-prev onclick="event.preventDefault(); event.stopPropagation();" class="absolute left-3 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur hover:bg-black/60" aria-label="Previous image">
                                            <i class="fa-solid fa-chevron-left text-xs"></i>
                                        </button>
                                        <button type="button" data-gallery-next onclick="event.preventDefault(); event.stopPropagation();" class="absolute right-3 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur hover:bg-black/60" aria-label="Next image">
                                            <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </button>
                                        <div class="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-1.5">
                                            <?php foreach ($galleryImages as $imageIndex => $imageUrl): ?>
                                                <button type="button" data-gallery-dot="<?php echo (int)$imageIndex; ?>" onclick="event.preventDefault(); event.stopPropagation();" class="h-1.5 rounded-full bg-white/70 transition-all <?php echo $imageIndex === 0 ? 'w-5' : 'w-1.5'; ?>" aria-label="Show image <?php echo (int)$imageIndex + 1; ?>"></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($saleBadge): ?>
                                        <button class="absolute top-3 left-4 z-20 inline-flex items-center gap-2 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs">
                                            <!-- BLINK DOT -->
                                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                            <?php echo e($saleBadge); ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- PLAY -->
                                    <button
                                        type="button"
                                        onclick="event.preventDefault(); window.location.href='<?php echo e($videoUrl ?: BASE_URL . 'project/' . urlencode($project['slug'])); ?>';"
                                        class="absolute bottom-4 right-4 z-20 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
                                        <i class="fa-solid fa-play"></i>
                                    </button>
                                </a>

                                <!-- HEART -->
                                <form method="post" action="<?php echo BASE_URL; ?>actions" class="absolute top-4.5 right-6 z-20" data-wishlist-form data-wishlist-project-id="<?php echo (int)$project['id']; ?>">
                                    <input type="hidden" name="action" value="wishlist">
                                    <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                    <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
                                    <button type="submit" class="text-white text-xl drop-shadow" aria-label="<?php echo $savedInWishlist ? 'Saved in wishlist' : 'Add to wishlist'; ?>" data-wishlist-button>
                                        <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
                                    </button>
                                </form>

                                <!-- CONTENT -->
                                <div class="pt-4 p-1">
                                    <div class="flex flex-col xl:flex-row xl:justify-between gap-4">
                                        <!-- LEFT -->
                                        <div>
                                            <h3 class="text-lg md:text-xl font-semibold text-primary leading-tight">
                                                <?php echo e($project['project_name']); ?>
                                            </h3>

                                            <p class="text-primary mt-2 text-xs md:text-sm">
                                                <i class="fa-regular fa-building mr-1"></i>
                                                <span class="underline">
                                                    <?php echo e($project['company_name']); ?>
                                                </span>
                                            </p>

                                            <p class="text-primary mt-2 text-xs md:text-sm">
                                                <i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>
                                                <span class="underline">
                                                    <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'])); ?>
                                                </span>
                                            </p>
                                        </div>

                                        <!-- RIGHT -->
                                        <div class="xl:text-right">
                                            <h4 class="text-base text-nowrap font-semibold text-green-500 leading-tight">
                                                <?php echo e(projectPriceRange($project, $unitPlans)); ?>
                                            </h4>

                                            <p class="text-gray-500 text-xs">(All inc)</p>

                                            <p class="text-xs text-accent mt-2"><?php echo e($project['project_type']); ?></p>
                                            <p class="text-primary text-sm mt-1">
                                                <i class="fa-solid fa-expand mr-1"></i>
                                                <?php echo e(projectAreaRange($project, $unitPlans)); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (!empty($unitPlans)): ?>
                                        <!-- TABLE -->
                                        <div class="mt-2 md:mt-4 border border-gray-300 rounded-lg overflow-hidden">

                                            <!-- SCROLLABLE AREA -->
                                            <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                                                <?php foreach ($unitPlans as $index => $plan): ?>
                                                    <?php
                                                    $planTitle = trim((string)($plan['bhk_type'] ?: $plan['unit_name'] ?: $project['project_type']));
                                                    $planArea = trim((string)($plan['area'] ?: $project['total_area']));
                                                    $planPrice = (float)($plan['price'] ?? 0);
                                                    ?>
                                                    <div class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            <?php echo $index < count($unitPlans) - 1 ? 'border-b border-gray-200' : ''; ?>
            text-primary font-semibold text-xs">

                                                        <span><?php echo e($planTitle); ?></span>
                                                        <span><?php echo e($planArea); ?></span>
                                                        <span><?php echo e($planPrice > 0 ? formatCurrency($planPrice) : projectPriceRange($project, $unitPlans)); ?></span>

                                                    </div>
                                                <?php endforeach; ?>

                                            </div>

                                        </div>
                                    <?php endif; ?>

                                    <!-- BUTTONS -->
                                    <div class="grid grid-cols-2 gap-3 mt-4">

                                        <!-- TOUR BUTTON -->
                                        <a target="_blank" href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                                            <!-- ICONS -->
                                            <div class="flex items-center gap-2">

                                                <i class="fa-solid fa-laptop text-xs "></i>

                                                <span class="text-white">|</span>

                                                <i class="fa-solid fa-car text-xs"></i>

                                            </div>

                                            <!-- TEXT -->
                                            <span>
                                                Tour
                                            </span>

                                        </a>

                                        <!-- LIVE CHAT BUTTON -->
                                        <a target="_blank" href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>" class="flex items-center justify-center gap-2
        bg-green-600 text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                                            <!-- WHATSAPP ICON -->
                                            <i class="fa-brands fa-whatsapp text-sm"></i>

                                            <!-- TEXT -->
                                            <span>
                                                Live Chat
                                            </span>

                                        </a>

                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>