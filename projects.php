<?php

require_once __DIR__ . '/includes/app_helpers.php';

$pageTitle = 'All Projects';
$cities = fetchAvailableProjectCities(100);

$filters = [
    'city' => trim($_GET['city'] ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'q' => trim($_GET['q'] ?? '')
];

$propertyTypes = fetchAvailableProjectTypes(['city' => $filters['city']], 30);

if ($filters['type'] !== '' && !in_array($filters['type'], $propertyTypes, true)) {
    $filters['type'] = '';
}

$selectedBudget = '';
$selectedBudgetLabel = '';
$requestedBudget = trim($_GET['budget'] ?? '');

if ($requestedBudget !== '' && preg_match('/^\d+(\.\d+)?$/', $requestedBudget) && (float)$requestedBudget > 0) {
    $selectedBudget = rtrim(rtrim(number_format((float)$requestedBudget, 2, '.', ''), '0'), '.');
    $selectedBudgetLabel = formatCurrency((float)$requestedBudget);
    $filters['budget_max'] = (float)$requestedBudget;
}

$budgetOptions = fetchAvailableProjectBudgets([
    'city' => $filters['city'],
    'type' => $filters['type']
], 30);

$projects = fetchPublishedProjects($filters, null);
$projectIds = array_column($projects, 'id');
$projectUnitPlans = fetchProjectUnitPlansForProjects($projectIds, 5);
$projectVideos = fetchProjectPrimaryVideosForProjects($projectIds);
$projectCount = count($projects);

$filterSummary = [];

if ($filters['city'] !== '') {
    $filterSummary[] = $filters['city'];
}

if ($filters['type'] !== '') {
    $filterSummary[] = $filters['type'];
}

if ($filters['q'] !== '') {
    $filterSummary[] = '"' . $filters['q'] . '"';
}

if ($selectedBudgetLabel !== '') {
    $filterSummary[] = 'Up to ' . $selectedBudgetLabel;
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="mt-20 py-6 md:py-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <div>
            <div class="flex gap-5 flex-row items-end justify-between">
                <div>
                    <div class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
                        <i class="fa-solid fa-building"></i>
                        <span>Properties</span>
                    </div>
                    <h1 class="text-primary text-3xl md:text-4xl font-semibold leading-tight">
                        All Projects
                    </h1>
                    <p class="mt-3 text-sm md:text-base text-gray-500">
                        <?php echo $filterSummary ? e(implode(' | ', $filterSummary)) : 'Browse every published projects.'; ?>
                    </p>
                </div>

                <div class="text-nowrap self-center text-sm text-gray-600">
                    <span class="font-semibold text-gray-800"><?php echo (int)$projectCount; ?></span>
                    <?php echo $projectCount === 1 ? 'project found' : 'projects found'; ?>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between md:hidden">
                <button type="button" data-project-filter-open class="inline-flex h-11 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-white">
                    <i class="fa-solid fa-filter"></i>
                    Filter
                </button>
                <a href="<?php echo BASE_URL; ?>projects" class="text-sm font-semibold text-gray-500 hover:text-primary">
                    Clear
                </a>
            </div>

            <div data-project-filter-overlay class="fixed inset-0 z-40 invisible bg-black/40 opacity-0 transition-opacity duration-300 md:hidden"></div>

            <form method="get" action="<?php echo BASE_URL; ?>projects" data-project-filter-panel class="fixed inset-y-0 left-0 z-50 flex w-[86vw] max-w-sm -translate-x-full flex-col gap-6 overflow-y-auto bg-white p-5 shadow-2xl transition-transform duration-300 ease-out md:static md:z-auto md:mt-8 md:grid md:w-auto md:max-w-none md:translate-x-0 md:grid-cols-2 md:gap-8 md:overflow-visible md:bg-transparent md:p-0 md:shadow-none xl:grid-cols-[180px_180px_180px_1fr_auto]">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 md:hidden">
                    <div>
                        <p class="text-xs uppercase text-gray-500">Project Filters</p>
                        <p class="mt-1 text-lg font-semibold text-primary">Find your match</p>
                    </div>
                    <button type="button" data-project-filter-close class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500" aria-label="Close filters">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <label class="block">
                    <span class="mb-2 block text-xs uppercase text-gray-500">City</span>
                    <select name="city" class="h-12 w-full border-b border-gray-200 text-sm text-primary outline-none focus:border-primary">
                        <option value="">All cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo e($city['city']); ?>" <?php echo $filters['city'] === $city['city'] ? 'selected' : ''; ?>>
                                <?php echo e($city['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs uppercase text-gray-500">Project Type</span>
                    <select name="type" class="h-12 w-full border-b border-gray-200 text-sm text-primary outline-none focus:border-primary">
                        <option value="">All types</option>
                        <?php foreach ($propertyTypes as $type): ?>
                            <option value="<?php echo e($type); ?>" <?php echo $filters['type'] === $type ? 'selected' : ''; ?>>
                                <?php echo e($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs uppercase text-gray-500">Budget</span>
                    <select name="budget" class="h-12 w-full border-b border-gray-200 text-sm text-primary outline-none focus:border-primary">
                        <option value="">Any budget</option>
                        <?php foreach ($budgetOptions as $budget): ?>
                            <option value="<?php echo e($budget['value']); ?>" <?php echo $selectedBudget === $budget['value'] ? 'selected' : ''; ?>>
                                <?php echo e($budget['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs uppercase text-gray-500">Search</span>
                    <input type="text" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Project, locality or builder" class="h-12 w-full border-b border-gray-200 text-sm text-primary outline-none focus:border-primary">
                </label>

                <div class="flex items-end gap-2">
                    <button type="submit" class="h-12 flex-1 rounded-lg bg-primary px-5 text-sm font-semibold text-white hover:opacity-90 xl:flex-none">
                        <i class="fa-solid fa-filter mr-2"></i>
                        Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>projects" class="flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:text-primary" aria-label="Clear filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <?php if (empty($projects)): ?>
            <div class="h-[56vh] p-4 flex justify-center flex-col items-center text-center">
                <i class="fa-solid fa-building mb-4 text-4xl md:text-6xl text-gray-400"></i>
                <h3 class="text-xl font-bold text-gray-900">No projects found</h3>
                <p class="mt-2 text-gray-500 text-sm">Try a different city and budget.</p>
            </div>
        <?php else: ?>
            <div class="mt-8 md:mt-12 grid grid-cols-2 gap-2 md:gap-5 md::grid-cols-3 2xl:grid-cols-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $unitPlans = $projectUnitPlans[(int)$project['id']] ?? [];
                    $saleBadge = projectSaleBadge($project);
                    $primaryVideo = $projectVideos[(int)$project['id']] ?? [];
                    $videoUrl = trim((string)($primaryVideo['video_url'] ?? $project['youtube_video_link'] ?? ''));
                    $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']);
                    ?>
                    <article class="h-full">
                        <div class="relative h-full bg-white rounded-2xl border border-gray-200 p-2 md:p-3 transition duration-300 hover:-translate-y-1 hover:shadow-sm">
                            <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="relative block">
                                <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>"
                                    class="w-full max-h-[220px] object-cover rounded-lg md:rounded-2xl overflow-hidden" />

                                <?php if ($saleBadge): ?>
                                    <span class="absolute top-0.5 md:top-3 left-2 md:left-4 inline-flex items-center gap-2 md:bg-green-600 text-white md:px-3 py-1 md:py-1.5 rounded-lg text-xs">
                                        <span class="w-2 h-2 rounded-full bg-green-600 md:bg-white animate-pulse"></span>
                                        <p class="hidden md:inline"> <?php echo e($saleBadge); ?></p>
                                    </span>
                                <?php endif; ?>

                                <button
                                    type="button"
                                    onclick="event.preventDefault(); window.location.href='<?php echo e($videoUrl ?: BASE_URL . 'project/' . urlencode($project['slug'])); ?>';"
                                    class="absolute bottom-1.5 right-1.5 md:bottom-4 md:right-4 bg-accent w-5 h-5 md:w-10 md:h-10 rounded-full text-white flex items-center justify-center"
                                    aria-label="Play project video">
                                    <i class="fa-solid fa-play text-[8px] md:text-sm"></i>
                                </button>
                            </a>

                            <form method="post" action="<?php echo BASE_URL; ?>actions" class="absolute top-2 md:top-3.5 right-3.5 md:right-5 z-20" data-wishlist-form data-wishlist-project-id="<?php echo (int)$project['id']; ?>">
                                <input type="hidden" name="action" value="wishlist">
                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
                                <button type="submit" class="text-white text-sm md:text-xl drop-shadow" aria-label="<?php echo $savedInWishlist ? 'Saved in wishlist' : 'Add to wishlist'; ?>" data-wishlist-button>
                                    <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
                                </button>
                            </form>

                            <div class="pt-4 p-1">
                                <div class="flex flex-col xl:flex-row xl:justify-between gap-3 md:gap-4">
                                    <div>
                                        <h3 class="text-sm md:text-xl font-semibold text-primary leading-tight">
                                            <?php echo e($project['project_name']); ?>
                                        </h3>

                                        <p class="text-primary mt-2 text-[10px] md:text-sm">
                                            <i class="fa-regular fa-building mr-1"></i>
                                            <span class="underline">
                                                <?php echo e($project['company_name']); ?>
                                            </span>
                                        </p>

                                        <p class="text-primary mt-2 text-[10px] md:text-sm">
                                            <i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>
                                            <span class="underline">
                                                <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'])); ?>
                                            </span>
                                        </p>
                                    </div>

                                    <div class="xl:text-right">
                                        <h4 class="text-xs md:text-base md:text-nowrap font-semibold text-green-500 leading-tight">
                                            <?php echo e(projectPriceRange($project)); ?>
                                        </h4>

                                        <p class="text-gray-500 text-xs">(All inc)</p>
                                        <p class="text-accent mt-2 text-xs"><?php echo e($project['project_type']); ?></p>
                                        <p class="text-primary text-xs md:text-sm mt-1">
                                            <i class="fa-solid fa-expand mr-1"></i>
                                            <?php echo e(projectAreaRange($project, $unitPlans)); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if (!empty($unitPlans)): ?>
                                    <div class="mt-3 md:mt-4 border border-gray-300 rounded-lg overflow-hidden">
                                        <div class="max-h-[60px] md:max-h-[80px] overflow-y-auto scrollbar-thin ">
                                            <?php foreach ($unitPlans as $index => $plan): ?>
                                                <?php
                                                $planTitle = trim((string)($plan['bhk_type'] ?: $plan['unit_name'] ?: $project['project_type']));
                                                $planArea = trim((string)($plan['area'] ?: $project['total_area']));
                                                $planPrice = (float)($plan['price'] ?? 0);
                                                ?>
                                                <div class="flex justify-between items-center gap-3 p-2 md:px-4 md:py-3 bg-primary-50 <?php echo $index < count($unitPlans) - 1 ? 'border-b border-gray-200' : ''; ?> text-primary font-semibold text-[8px] md:text-xs">
                                                    <span><?php echo e($planTitle); ?></span>
                                                    <span><?php echo e($planArea); ?></span>
                                                    <span><?php echo e($planPrice > 0 ? formatCurrency($planPrice) : projectPriceRange($project)); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-2 gap-2 md:gap-3 mt-4 text-[10px] md:text-xs">
                                    <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex items-center justify-center gap-2 bg-primary text-white py-2 md:py-3 rounded-lg hover:opacity-90 duration-300">
                                        <div class="flex items-center gap-2 hidden md:inline">
                                            <i class="fa-solid fa-laptop"></i>
                                            <span class="text-white">|</span>
                                            <i class="fa-solid fa-car"></i>
                                        </div>
                                        <span>Tour</span>
                                    </a>

                                    <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>" class="flex items-center justify-center gap-2 bg-accent text-white py-2 md:py-3 rounded-lg hover:opacity-90 duration-300">
                                        <i class="fa-brands fa-whatsapp hidden md:inline"></i>
                                        <span>Live Chat</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const openButton = document.querySelector('[data-project-filter-open]');
        const closeButton = document.querySelector('[data-project-filter-close]');
        const panel = document.querySelector('[data-project-filter-panel]');
        const overlay = document.querySelector('[data-project-filter-overlay]');

        if (!openButton || !panel || !overlay) {
            return;
        }

        function openFilters() {
            panel.classList.remove('-translate-x-full');
            panel.classList.add('translate-x-0');
            overlay.classList.remove('invisible', 'opacity-0');
            overlay.classList.add('opacity-100');
            document.body.classList.add('overflow-hidden');
        }

        function closeFilters() {
            panel.classList.add('-translate-x-full');
            panel.classList.remove('translate-x-0');
            overlay.classList.add('invisible', 'opacity-0');
            overlay.classList.remove('opacity-100');
            document.body.classList.remove('overflow-hidden');
        }

        openButton.addEventListener('click', openFilters);
        closeButton?.addEventListener('click', closeFilters);
        overlay.addEventListener('click', closeFilters);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeFilters();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeFilters();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>