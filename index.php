<?php

require_once __DIR__ . '/includes/app_helpers.php';

$pageTitle = 'Buy Homes Directly With Builders';

$cities = fetchAvailableProjectCities(8);

$citySlug = str_replace('_', '-', trim($_GET['city_slug'] ?? ''));
$localitySlug = str_replace('_', '-', trim($_GET['locality_slug'] ?? ''));
$cityFromPath = '';
$localityFromPath = '';

if (($_SERVER['QUERY_STRING'] ?? '') === '' && str_ends_with($_SERVER['REQUEST_URI'] ?? '', '?')) {
    redirect(strtok(getCurrentPageUrl(), '?'));
}

if ($citySlug !== '') {
    foreach ($cities as $cityRow) {
        if (makeSlug($cityRow['city']) === $citySlug) {
            $cityFromPath = $cityRow['city'];
            break;
        }
    }

    if ($cityFromPath === '') {
        $cityFromPath = ucwords(str_replace(['-', '_'], ' ', $citySlug));
    }
}

if ($cityFromPath !== '' && $localitySlug !== '') {
    $localityStmt = $pdo->prepare("
        SELECT locality
        FROM projects
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND city = ?
          AND locality IS NOT NULL
          AND locality <> ''
        GROUP BY locality
    ");
    $localityStmt->execute([$cityFromPath]);

    foreach ($localityStmt->fetchAll() as $localityRow) {
        if (makeSlug($localityRow['locality']) === makeSlug($localitySlug)) {
            $localityFromPath = $localityRow['locality'];
            break;
        }
    }

    if ($localityFromPath === '') {
        $params = $_GET;
        unset($params['city_slug'], $params['locality_slug']);
        $params['q'] = ucwords(str_replace(['-', '_'], ' ', $localitySlug));
        redirect(cityUrl($cityFromPath, $params));
    }
}

if ($citySlug === '' && !empty($_GET['city'])) {
    $params = $_GET;
    $cityForRedirect = trim($params['city']);
    unset($params['city'], $params['city_slug']);
    redirect(cityUrl($cityForRedirect, $params));
}

$cleanParams = $_GET;
unset($cleanParams['city_slug'], $cleanParams['locality_slug']);
$removedEmptyParam = false;

foreach ($cleanParams as $key => $value) {
    if (is_array($value)) {
        continue;
    }

    if (trim((string)$value) === '') {
        unset($cleanParams[$key]);
        $removedEmptyParam = true;
    }
}

if ($removedEmptyParam) {
    if ($localityFromPath) {
        $cleanParams['q'] = $localityFromPath;
        $cleanParams['_locality_path'] = true;
    }

    if ($cityFromPath) {
        redirect(cityUrl($cityFromPath, $cleanParams));
    }

    redirect(BASE_URL . ($cleanParams ? '?' . http_build_query($cleanParams) : ''));
}

$filters = [
    'city' => $cityFromPath ?: trim($_GET['city'] ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'q' => $localityFromPath ?: trim($_GET['q'] ?? '')
];

if ($filters['city'] && $localitySlug === '' && !empty($_GET['q'])) {
    $localityMatchStmt = $pdo->prepare("
        SELECT locality
        FROM projects
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND city = ?
          AND locality IS NOT NULL
          AND locality <> ''
        GROUP BY locality
    ");
    $localityMatchStmt->execute([$filters['city']]);

    foreach ($localityMatchStmt->fetchAll() as $localityRow) {
        if (makeSlug($localityRow['locality']) === makeSlug($_GET['q'])) {
            $params = $_GET;
            unset($params['city_slug'], $params['locality_slug']);
            $params['q'] = $localityRow['locality'];
            $params['_locality_path'] = true;
            redirect(cityUrl($filters['city'], $params));
        }
    }
}

$propertyTypes = fetchAvailableProjectTypes(['city' => $filters['city']]);

if ($filters['type'] !== '' && !in_array($filters['type'], $propertyTypes, true)) {
    $params = $_GET;
    unset($params['type'], $params['city_slug'], $params['locality_slug']);

    if ($localityFromPath) {
        $params['q'] = $localityFromPath;
        $params['_locality_path'] = true;
    }

    if ($filters['city']) {
        redirect(cityUrl($filters['city'], $params));
    }

    redirect(BASE_URL . ($params ? '?' . http_build_query($params) : ''));
}

$selectedBudget = '';
$selectedBudgetLabel = '';
$requestedBudget = trim($_GET['budget'] ?? '');

if ($requestedBudget !== '' && preg_match('/^\d+(\.\d+)?$/', $requestedBudget) && (float)$requestedBudget > 0) {
    $selectedBudget = rtrim(rtrim(number_format((float)$requestedBudget, 2, '.', ''), '0'), '.');
    $selectedBudgetLabel = formatCurrency((float)$requestedBudget);
    $filters['budget_max'] = (float)$requestedBudget;
}

if ($filters['q'] !== '' && $localityFromPath === '') {
    $searchCheckFilters = $filters;

    if (empty(fetchPublishedProjects($searchCheckFilters, 1))) {
        $params = $_GET;
        unset($params['q'], $params['city_slug'], $params['locality_slug']);

        if ($filters['city']) {
            redirect(cityUrl($filters['city'], $params));
        }

        redirect(BASE_URL . ($params ? '?' . http_build_query($params) : ''));
    }
}

$budgetOptions = fetchAvailableProjectBudgets([
    'city' => $filters['city'],
    'type' => $filters['type']
]);

if ($filters['city']) {
    $pageTitle = 'Projects in ' . $filters['city'];
}

$projects = fetchPublishedProjects($filters, 9);
$projectIds = array_column($projects, 'id');
$projectUnitPlans = fetchProjectUnitPlansForProjects($projectIds, 5);
$projectVideos = fetchProjectPrimaryVideosForProjects($projectIds);
$testimonialVideos = array_values(array_filter(fetchHomepageProjectVideos(8), static function ($video) {
    return videoEmbedUrl($video['video_url'] ?? '') !== '';
}));
$featured = $projects[0] ?? null;
$projectCount = $filters['city']
    ? tableCount('projects', "status = 'published' AND deleted_at IS NULL AND city = ?", [$filters['city']])
    : tableCount('projects', "status = 'published' AND deleted_at IS NULL");
$builderCount = $filters['city']
    ? tableCount('builders', "status = 'active' AND city = ?", [$filters['city']])
    : tableCount('builders', "status = 'active'");

$localities = [];

if ($filters['city']) {
    $localityStmt = $pdo->prepare("
        SELECT locality, COUNT(*) AS total
        FROM projects
        WHERE status = 'published'
          AND deleted_at IS NULL
          AND city = ?
          AND locality IS NOT NULL
          AND locality <> ''
        GROUP BY locality
        ORDER BY total DESC, locality ASC
        LIMIT 8
    ");
    $localityStmt->execute([$filters['city']]);
    $localities = $localityStmt->fetchAll();
}

$builderSql = "
    SELECT b.company_name, b.company_slug, b.city, b.company_logo, b.established_year, COUNT(p.id) AS total_projects
    FROM builders b
    LEFT JOIN projects p ON p.builder_id = b.id AND p.status = 'published'
    WHERE b.status = 'active'
";
$builderParams = [];

if ($filters['city']) {
    $builderSql .= " AND b.city = ?";
    $builderParams[] = $filters['city'];
}

$builderSql .= "
    GROUP BY b.id
    ORDER BY total_projects DESC, b.company_name ASC
    LIMIT 6
";
$builderStmt = $pdo->prepare($builderSql);
$builderStmt->execute($builderParams);
$builders = $builderStmt->fetchAll();

$projectsPageParams = array_filter([
    'city' => $filters['city'],
    'type' => $filters['type'],
    'q' => $filters['q'],
    'budget' => $selectedBudget
], static function ($value) {
    return $value !== null && $value !== '';
});

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative mt-20">

    <!-- BACKGROUND IMAGE -->
    <img src="https://i.ibb.co/gZfMz1nR/Gemini-Generated-Image-othiv4othiv4othi.png" alt="Real Estate"
        class="absolute inset-0 w-full min-h-[78%] max-h-[88%] object-cover" />

    <!-- CONTENT -->
    <div class="relative h-full z-10 max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">

        <!-- HERO CONTENT -->
        <div class="pt-12 md:pt-16 h-full">

            <!-- TEXT -->
            <div class="max-w-5xl">

                <!-- BADGE -->
                <div
                    class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/10 px-4 py-2 rounded-full mb-6">

                    <span class="w-2.5 h-2.5 rounded-full bg-accent animate-pulse"></span>

                    <p class="text-white text-xs sm:text-sm font-medium">
                        India's Trusted Luxury Property Platform
                    </p>

                </div>

                <!-- HEADING -->
                <h1 class="text-white font-semibold
  text-4xl sm:text-5xl lg:text-7xl
  tracking-[-2px] md:tracking-[-4px]
  leading-tight lg:leading-[1.2]">

                    Buy Homes
                    <br />
                    <span class="text-accent">
                        Directly With
                    </span>
                    Builders

                </h1>

            </div>

            <!-- SEARCH AREA -->
            <form method="get" action="<?php echo $filters['city'] ? cityUrl($filters['city']) : BASE_URL; ?>" data-city-search data-current-city="<?php echo e($filters['city']); ?>" class="mt-12 md:mt-20" data-home-search>

                <!-- TOP TABS -->
                <div class="max-w-full flex gap-1 overflow-x-auto font-medium text-sm">
                    <button
                        type="button"
                        data-search-type=""
                        class="shrink-0 rounded-2xl rounded-b-none bg-primary min-w-[80px] px-5 py-3 text-white transition <?php echo !$filters['type'] ? 'border-b-[3px] border-accent-400' : 'text-white/80 hover:text-white'; ?>">
                        All
                    </button>
                    <?php foreach ($propertyTypes as $type): ?>
                        <?php $isActiveType = $filters['type'] === $type; ?>
                        <button
                            type="button"
                            data-search-type="<?php echo e($type); ?>"
                            class="shrink-0 rounded-2xl rounded-b-none bg-primary min-w-[140px] px-5 py-3 text-white transition <?php echo $isActiveType ? 'border-b-[3px] border-accent-400' : 'text-white/80 hover:text-white'; ?>">
                            <?php echo e($type === 'Plot' ? 'Plots' : $type); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="type" value="<?php echo e($filters['type']); ?>" data-search-type-input>
                <input type="hidden" name="budget" value="<?php echo e($selectedBudget); ?>" data-search-budget-input>

                <!-- MAIN SEARCH BOX -->
                <div class="max-w-5xl w-full bg-white shadow-sm border rounded-2xl rounded-tl-none">

                    <div
                        class="flex flex-col lg:flex-row items-stretch border border-transparent rounded-2xl rounded-tl-none">

                        <!-- FILTERS -->
                        <div class="flex flex-col sm:flex-row items-stretch divide-y sm:divide-y-0 sm:divide-x divide-gray-200 bg-white rounded-xl">

                            <!-- LOCATION -->
                            <div class="relative w-full sm:w-[190px]">

                                <!-- LABEL -->
                                <label
                                    class="absolute left-5 top-3 text-xs text-gray-400 font-medium pointer-events-none z-20">
                                    Location
                                </label>

                                <!-- BUTTON -->
                                <button type="button" data-dropdown-trigger="city"
                                    class="w-full h-16 bg-white pt-6 pb-2 px-5 pr-12 text-sm font-semibold text-gray-800 text-left outline-none">

                                    <span data-dropdown-label="city"><?php echo $filters['city'] ? e($filters['city']) : 'Select City'; ?></span>

                                </button>

                                <select name="city" data-city-select class="hidden">
                                    <option value="">All cities</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo e($city['city']); ?>" <?php echo $filters['city'] === $city['city'] ? 'selected' : ''; ?>>
                                            <?php echo e($city['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- ARROW -->
                                <div class="absolute right-4 top-10 -translate-y-1/2 pointer-events-none">

                                    <svg data-dropdown-arrow="city"
                                        class="w-4 h-4 text-gray-500 transition-transform duration-300"
                                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 9l-7 7-7-7" />

                                    </svg>

                                </div>

                                <!-- DROPDOWN -->
                                <div data-dropdown-menu="city"
                                    class="absolute left-0 top-16 w-full bg-white border shadow-sm rounded-b-lg opacity-0 origin-top scale-y-0 invisible transition-all duration-300 ease-out z-50 max-h-[200px] overflow-y-auto custom-scroll">

                                    <ul class="py-2 text-xs text-gray-700">

                                        <li>
                                            <button type="button" data-dropdown-option="city" data-value=""
                                                class="w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                                All cities
                                            </button>
                                        </li>

                                        <?php foreach ($cities as $city): ?>
                                            <li>
                                                <button type="button" data-dropdown-option="city" data-value="<?php echo e($city['city']); ?>"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                                    <?php echo e($city['city']); ?>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>

                                    </ul>

                                </div>

                            </div>

                            <!-- BUDGET -->
                            <div class="relative w-full sm:w-[190px]">

                                <!-- LABEL -->
                                <label
                                    class="absolute left-5 top-3 text-xs text-gray-400 font-medium pointer-events-none z-20">
                                    Budget
                                </label>

                                <!-- BUTTON -->
                                <button type="button" data-dropdown-trigger="budget"
                                    class="w-full h-16 bg-white pt-6 pb-2 px-5 pr-12 text-sm font-semibold text-gray-800 text-left outline-none">

                                    <span data-dropdown-label="budget"><?php echo $selectedBudget ? 'Up to ' . e($selectedBudgetLabel) : 'Select Budget'; ?></span>

                                </button>

                                <!-- ARROW -->
                                <div class="absolute right-4 top-10 -translate-y-1/2 pointer-events-none">

                                    <svg data-dropdown-arrow="budget"
                                        class="w-4 h-4 text-gray-500 transition-transform duration-300"
                                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 9l-7 7-7-7" />

                                    </svg>

                                </div>

                                <!-- DROPDOWN -->
                                <div data-dropdown-menu="budget"
                                    class="absolute left-0 top-16 w-full bg-white border shadow-sm rounded-b-lg opacity-0 origin-top scale-y-0 invisible transition-all duration-300 ease-out z-50 max-h-[200px] overflow-y-auto custom-scroll">

                                    <ul class="py-2 text-xs text-gray-700">

                                        <li>
                                            <button type="button" data-dropdown-option="budget" data-value="" data-label="Select Budget"
                                                class="w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                                Any budget
                                            </button>
                                        </li>

                                        <?php foreach ($budgetOptions as $budget): ?>
                                            <li>
                                                <button type="button" data-dropdown-option="budget" data-value="<?php echo e($budget['value']); ?>" data-label="Up to <?php echo e($budget['label']); ?>"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                                    Up to <?php echo e($budget['label']); ?>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>

                                    </ul>

                                </div>

                            </div>

                        </div>

                        <!-- SEARCH SECTION -->
                        <div class="flex-1 p-4">

                            <!-- SEARCH INPUT -->
                            <div class="w-full flex items-center h-12 px-5 rounded-xl bg-gray-100 border border-transparent
  focus-within:border-gray-300
  transition-all duration-300">

                                <input type="text" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Search for Project or locality" class="w-full h-full bg-transparent outline-none
            text-sm text-gray-700
            placeholder:text-gray-500">

                                <button type="submit" class="flex-shrink-0 text-gray-400 hover:text-gray-500 transition" aria-label="Search">
                                    <svg class="w-5 h-5" fill="none"
                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">

                                        <circle cx="11" cy="11" r="7" />
                                        <path d="M20 20l-3.5-3.5" />
                                    </svg>
                                </button>

                            </div>

                            <!-- POPULAR LOCALITIES -->
                            <div class="flex flex-wrap items-center gap-3 mt-4">

                                <!-- TITLE -->
                                <div class="flex items-center gap-1">

                                    <span class="text-sm font-semibold text-black">
                                        <?php echo $filters['city'] && !empty($localities) ? 'Popular Localities' : 'Popular Cities'; ?>
                                    </span>

                                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7" />

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h9v9" />
                                    </svg>

                                </div>

                                <!-- TAGS -->
                                <?php if ($filters['city'] && !empty($localities)): ?>
                                    <?php foreach (array_slice($localities, 0, 3) as $index => $locality): ?>
                                        <?php $isActiveLocality = makeSlug($filters['q']) === makeSlug($locality['locality']); ?>
                                        <a href="<?php echo cityUrl($filters['city'], ['q' => $locality['locality'], '_locality_path' => true, 'type' => $filters['type'], 'budget' => $selectedBudget]); ?>"
                                            class="px-4 py-2 rounded-full <?php echo $isActiveLocality || ($filters['q'] === '' && $index === 0) ? 'bg-accent text-white' : 'bg-gray-200 text-accent hover:bg-gray-300'; ?> text-[10px] font-medium transition">
                                            <?php echo e($locality['locality']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach (array_slice($cities, 0, 3) as $index => $city): ?>
                                        <a href="<?php echo cityUrl($city['city'], ['type' => $filters['type'], 'budget' => $selectedBudget]); ?>"
                                            class="px-4 py-2 rounded-full <?php echo $index === 0 ? 'bg-accent text-white' : 'bg-gray-200 text-accent hover:bg-gray-300'; ?> text-[10px] font-medium transition">
                                            <?php echo e($city['city']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = document.querySelector('[data-home-search]');

                    if (!form) {
                        return;
                    }

                    const slugify = (value) => value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

                    const closeDropdown = (key) => {
                        const menu = form.querySelector(`[data-dropdown-menu="${key}"]`);
                        const arrow = form.querySelector(`[data-dropdown-arrow="${key}"]`);

                        menu?.classList.add('scale-y-0', 'opacity-0', 'invisible');
                        arrow?.classList.remove('-rotate-90');
                    };

                    form.querySelectorAll('[data-dropdown-trigger]').forEach((trigger) => {
                        const key = trigger.dataset.dropdownTrigger;

                        trigger.addEventListener('click', (event) => {
                            const menu = form.querySelector(`[data-dropdown-menu="${key}"]`);
                            const arrow = form.querySelector(`[data-dropdown-arrow="${key}"]`);
                            const isClosed = menu?.classList.contains('invisible');

                            event.preventDefault();
                            event.stopPropagation();

                            form.querySelectorAll('[data-dropdown-menu]').forEach((openMenu) => {
                                const openKey = openMenu.dataset.dropdownMenu;

                                if (openKey !== key) {
                                    closeDropdown(openKey);
                                }
                            });

                            menu?.classList.toggle('scale-y-0', !isClosed);
                            menu?.classList.toggle('opacity-0', !isClosed);
                            menu?.classList.toggle('invisible', !isClosed);
                            arrow?.classList.toggle('-rotate-90', isClosed);
                        });
                    });

                    form.querySelectorAll('[data-dropdown-option]').forEach((option) => {
                        option.addEventListener('click', () => {
                            const key = option.dataset.dropdownOption;
                            const value = option.dataset.value || '';
                            const label = option.dataset.label || option.textContent.trim();

                            if (key === 'city') {
                                const select = form.querySelector('[data-city-select]');
                                const labelNode = form.querySelector('[data-dropdown-label="city"]');
                                const searchInput = form.querySelector('input[name="q"]');
                                const currentCity = form.dataset.currentCity || '';
                                const cityChanged = value && slugify(currentCity) !== slugify(value);

                                select.value = value;
                                labelNode.textContent = value || 'Select City';

                                if ((currentCity && !value) || cityChanged) {
                                    searchInput.value = '';
                                }
                            }

                            if (key === 'budget') {
                                const input = form.querySelector('[data-search-budget-input]');
                                const labelNode = form.querySelector('[data-dropdown-label="budget"]');

                                input.value = value;
                                labelNode.textContent = value ? label : 'Select Budget';
                            }

                            closeDropdown(key);

                            if (key === 'city' || key === 'budget') {
                                form.requestSubmit();
                            }
                        });
                    });

                    form.querySelectorAll('[data-search-type]').forEach((tab) => {
                        tab.addEventListener('click', () => {
                            form.querySelector('[data-search-type-input]').value = tab.dataset.searchType || '';

                            form.querySelectorAll('[data-search-type]').forEach((typeTab) => {
                                typeTab.classList.remove('border-b-[3px]', 'border-accent-400');
                                typeTab.classList.add('text-white/80', 'hover:text-white');
                            });

                            tab.classList.add('border-b-[3px]', 'border-accent-400');
                            tab.classList.remove('text-white/80', 'hover:text-white');

                            form.requestSubmit();
                        });
                    });

                    document.addEventListener('click', (event) => {
                        if (!form.contains(event.target)) {
                            form.querySelectorAll('[data-dropdown-menu]').forEach((menu) => {
                                closeDropdown(menu.dataset.dropdownMenu);
                            });
                        }
                    });
                });
            </script>

        </div>

    </div>

</section>

<!-- Why Choose Us -->
<section class="py-12 md:py-16">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <!-- TOP -->
        <div class="mb-10">
            <div class="flex items-center gap-2 text-accent uppercase font-semibold text-xs md:text-sm mb-3">
                <i class="fa-regular fa-building"></i>
                <span>Advantages</span>
            </div>

            <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
                Why Choose Us?
            </h2>

            <p class="text-gray-500 text-xs md:text-base">
                Discover the key advantages of investing with us.
            </p>
        </div>

        <!-- GRID -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-5">
            <div class="bg-white rounded-3xl px-3 py-6 md:px-6 md:py-12 min-h-[200px] duration-300 hover:-translate-y-1 border">
                <div class="mb-4 md:mb-8 flex justify-center">
                    <div class="text-3xl md:text-6xl flex items-center justify-center text-primary">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <h3 class="text-sm md:text-xl font-semibold text-primary mb-4 text-center">Bottom Rate Guarantee</h3>
                <p class="text-gray-700 text-[11px] md:text-sm text-center leading-[20px]">
                    Housiey guarantees the bottom rate or refunds double the difference.
                </p>
            </div>

            <div class="bg-white rounded-3xl px-3 py-6 md:px-6 md:py-12 min-h-[200px] duration-300 hover:-translate-y-1 border">
                <div class="mb-4 md:mb-8 flex justify-center">
                    <div class="text-3xl md:text-6xl flex items-center justify-center text-primary">
                        <i class="fa-solid fa-display"></i>
                    </div>
                </div>
                <h3 class="text-sm md:text-xl font-semibold text-primary mb-4 text-center">Online Site Visit</h3>
                <p class="text-gray-700 text-[11px] md:text-sm text-center leading-[20px]">
                    Visit projects from home with Housiey's Online Site Visit concept.
                </p>
            </div>

            <div class="bg-white rounded-3xl px-3 py-6 md:px-6 md:py-12 min-h-[200px] duration-300 hover:-translate-y-1 border">
                <div class="mb-4 md:mb-8 flex justify-center">
                    <div class="text-3xl md:text-6xl flex items-center justify-center text-primary">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                </div>
                <h3 class="text-sm md:text-xl font-semibold text-primary mb-4 text-center">Free Site Visit</h3>
                <p class="text-gray-700 text-[11px] md:text-sm text-center leading-[20px]">
                    Free pickup & drop for unlimited site visits across the city.
                </p>
            </div>

            <div class="bg-white rounded-3xl px-3 py-6 md:px-6 md:py-12 min-h-[200px] duration-300 hover:-translate-y-1 border">
                <div class="mb-4 md:mb-8 flex justify-center">
                    <div class="text-3xl md:text-6xl flex items-center justify-center text-primary">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                </div>
                <h3 class="text-sm md:text-xl font-semibold text-primary mb-4 text-center">No Brokerage Charges</h3>
                <p class="text-gray-700 text-[11px] md:text-sm text-center leading-[20px]">
                    Get personalized RM managing everything from site visit to booking.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Projects -->
<section class="py-12 md:py-16 overflow-hidden bg-gray-50">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <!-- TOP AREA -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6 mb-8">
            <!-- LEFT -->
            <div>
                <div
                    class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
                    <i class="fa-solid fa-building"></i>
                    <span>Properties</span>
                </div>

                <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
                    <?php echo $filters['city'] ? 'Top New Launches In ' . e($filters['city']) : 'Top New Launches'; ?>
                </h2>

                <p class="text-gray-500 text-xs md:text-base">
                    <?php echo $filters['city'] ? 'Discover the latest real estate projects in ' . e($filters['city']) : 'Discover the latest real estate projects from live inventory'; ?>
                </p>
            </div>

            <!-- RIGHT -->
            <div class="flex items-center justify-between md:flex-col md:items-end gap-4">
                <a href="<?php echo BASE_URL; ?>projects<?php echo $projectsPageParams ? '?' . http_build_query($projectsPageParams) : ''; ?>"
                    class="bg-primary-50 text-primary-500 px-5 md:px-6 py-3 rounded-xl font-medium text-xs md:text-sm hover:opacity-90 duration-300">
                    See All Projects
                </a>

                <div class="flex gap-3">
                    <button
                        class="property-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button
                        class="property-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($projects)): ?>
            <div class="h-[56vh] p-4 flex justify-center flex-col items-center text-center">
                <i class="fa-solid fa-building mb-4 text-4xl md:text-6xl text-gray-400"></i>
                <h3 class="text-xl font-bold text-gray-900">No projects found</h3>
                <p class="mt-2 text-gray-500 text-sm">Try a different city and budget.</p>
            </div>
        <?php else: ?>
            <div class="swiper propertySwiper overflow-visible">
                <div class="swiper-wrapper">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $unitPlans = $projectUnitPlans[(int)$project['id']] ?? [];
                        $saleBadge = projectSaleBadge($project);
                        $primaryVideo = $projectVideos[(int)$project['id']] ?? [];
                        $videoUrl = trim((string)($primaryVideo['video_url'] ?? $project['youtube_video_link'] ?? ''));
                        $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']);
                        ?>
                        <article class="swiper-slide">
                            <div class="relative bg-white rounded-2xl border border-gray-200 p-3 transition duration-300 hover:-translate-y-1 hover:shadow-sm">
                                <!-- IMAGE -->
                                <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="relative">
                                    <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>"
                                        class="w-full h-[220px] object-cover rounded-2xl overflow-hidden" />

                                    <?php if ($saleBadge): ?>
                                        <button class="absolute top-3 left-4 inline-flex items-center gap-2 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs">
                                            <!-- BLINK DOT -->
                                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                            <?php echo e($saleBadge); ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- PLAY -->
                                    <button
                                        type="button"
                                        onclick="event.preventDefault(); window.location.href='<?php echo e($videoUrl ?: BASE_URL . 'project/' . urlencode($project['slug'])); ?>';"
                                        class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
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
                                                <?php echo e(projectPriceRange($project)); ?>
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
                                                        <span><?php echo e($planPrice > 0 ? formatCurrency($planPrice) : projectPriceRange($project)); ?></span>

                                                    </div>
                                                <?php endforeach; ?>

                                            </div>

                                        </div>
                                    <?php endif; ?>

                                    <!-- BUTTONS -->
                                    <div class="grid grid-cols-2 gap-3 mt-4">

                                        <!-- TOUR BUTTON -->
                                        <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex items-center justify-center gap-2
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
                                        <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>" class="flex items-center justify-center gap-2
        bg-accent text-white
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

<!-- Top Developers Projects -->
<section class="py-12 md:py-16">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <!-- TOP BAR -->
        <div class="flex items-center justify-between">
            <!-- TITLE -->
            <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight">
                Top Developers Projects
            </h2>

            <!-- NAVIGATION -->
            <div class="flex items-center gap-3">
                <button
                    class="developer-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button
                    class="developer-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- SWIPER -->
        <div class="swiper developerSwiper py-8">
            <div class="swiper-wrapper">
                <?php foreach ($builders as $builder): ?>
                    <?php
                    $logoUrl = !empty($builder['company_logo']) ? getImageUrl($builder['company_logo'], 'uploads') : '';
                    $establishedYear = (int)($builder['established_year'] ?? 0);
                    $developerYears = $establishedYear > 0 ? max(0, (int)date('Y') - $establishedYear) : null;
                    $nameParts = preg_split('/\s+/', trim((string)$builder['company_name']));
                    $initials = '';

                    foreach ($nameParts as $part) {
                        if ($part !== '') {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }

                        if (strlen($initials) >= 2) {
                            break;
                        }
                    }
                    ?>
                    <!-- CARD -->
                    <div class="swiper-slide">
                        <div class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-sm">

                            <?php if ($developerYears !== null): ?>
                                <!-- YEARS -->
                                <h3 class="text-red-500 text-3xl md:text-4xl font-semibold leading-none">
                                    <?php echo (int)$developerYears; ?>y+
                                </h3>
                            <?php endif; ?>

                            <!-- IMAGE -->
                            <div class="flex justify-center items-center px-8 py-12 min-h-[150px]">
                                <?php if ($logoUrl): ?>
                                    <img src="<?php echo e($logoUrl); ?>"
                                        alt="<?php echo e($builder['company_name']); ?>" class="max-h-20 object-contain mx-auto transition duration-300 group-hover:scale-105" />
                                <?php else: ?>
                                    <span class="h-20 w-20 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-black transition duration-300 group-hover:scale-105">
                                        <?php echo e($initials ?: substr($builder['company_name'], 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- NAME -->
                            <!-- <h3 class="text-primary text-lg font-semibold leading-tight mb-4">
                                <?php echo e($builder['company_name']); ?>
                            </h3> -->

                            <!-- BOTTOM -->
                            <div class="w-full flex items-center justify-between">

                                <!-- TEXT -->
                                <div>

                                    <span class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                                        <?php echo e($builder['city'] ?: 'Total'); ?>

                                    </span>

                                    <p class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                                        Projects

                                    </p>

                                </div>

                                <!-- NUMBER -->
                                <span class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                                    <?php echo (int)$builder['total_projects']; ?>

                                </span>

                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</section>

<!-- SCHEDULE NOW -->
<section
    class="relative bg-cover bg-center bg-no-repeat overflow-hidden py-12"
    style="background-image:url('https://i.ibb.co/fVyWWqgx/online-Presentation.webp');">

    <div class="relative z-10 max-w-[1450px] mx-auto px-4 sm:px-6 lg:px-10">

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_0.4fr] gap-8 items-center">

            <!-- LEFT -->
            <div class="max-w-4xl">

                <!-- HEADING -->
                <h2 class="text-white
        text-4xl md:text-6xl
        leading-[1.15]
        font-semibold
        tracking-[-1px]">

                    Discover Your Dream Home

                </h2>

                <!-- SUBTEXT -->
                <p class="mt-3 md:mt-4
        text-white/90
        text-xs sm:text-sm md:text-base
        leading-[1.7]">

                    Directly by Builder | Exclusive Offers | Live Virtual Tour
                </p>

                <div class="mt-10 md:mt-20 grid grid-cols-3 gap-2 md:gap-4 max-w-2xl ">

                    <?php foreach (
                        [
                            'Search verified projects',
                            'Book free visit',
                            'Deal directly with builder'
                        ] as $index => $step
                    ): ?>

                        <div
                            class="bg-white border rounded-2xl p-3 md:p-5">

                            <div
                                class="h-8 w-8 md:h-11 md:w-11 rounded-full bg-accent text-white flex items-center justify-center font-semibold">
                                <?php echo $index + 1; ?>
                            </div>

                            <p class="mt-4 text-gray-800 text-[10px] md:text-sm">
                                <?php echo e($step); ?>
                            </p>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- RIGHT FORM -->
            <div
                class="bg-white rounded-2xl p-6 md:p-8 shadow-sm">

                <h3 class="text-xl md:text-2xl font-medium text-gray-900">
                    Book Free Site Visit
                </h3>

                <p class="mt-2 text-gray-500 text-xs md:text-sm">
                    Fill your details and we will contact you shortly.
                </p>

                <form
                    method="post"
                    action="<?php echo BASE_URL; ?>actions"
                    class="mt-6">

                    <!-- HIDDEN INPUT -->
                    <input type="hidden" name="project_id" id="project_id" required>

                    <div class="relative w-full">

                        <!-- BUTTON -->
                        <button
                            type="button"
                            id="dropdownBtn"
                            class="w-full h-10 md:h-11 px-4 rounded-lg border border-gray-200 bg-white
        flex items-center justify-between
        focus:border-green-500
        transition-all duration-300">

                            <span
                                id="selectedText"
                                class="text-xs md:text-sm text-gray-500">
                                Select Project
                            </span>

                            <svg
                                id="dropdownArrow"
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 transition-transform duration-300"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7" />

                            </svg>

                        </button>

                        <!-- DROPDOWN -->
                        <div
                            id="dropdownMenu"
                            class="absolute left-0 top-full mt-2 w-full
        bg-white rounded-xl border border-gray-200
        shadow-sm overflow-hidden z-50

        max-h-0 opacity-0 -translate-y-2

        transition-all duration-500 ease-in-out">

                            <div class="max-h-54 overflow-y-auto">

                                <?php foreach (fetchPublishedProjects($filters['city'] ? ['city' => $filters['city']] : [], 20) as $project): ?>

                                    <div
                                        class="px-4 py-3 text-xs cursor-pointer
                    hover:bg-green-50
                    hover:text-green-600
                    transition-colors duration-200"

                                        onclick="selectProject(
                    '<?php echo (int)$project['id']; ?>',
                    '<?php echo htmlspecialchars($project['project_name'], ENT_QUOTES); ?>'
                )">

                                        <?php echo e($project['project_name']); ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                    <script>
                        const dropdownBtn = document.getElementById("dropdownBtn");
                        const dropdownMenu = document.getElementById("dropdownMenu");
                        const dropdownArrow = document.getElementById("dropdownArrow");
                        const selectedText = document.getElementById("selectedText");
                        const projectInput = document.getElementById("project_id");

                        let isOpen = false;

                        // OPEN & CLOSE
                        dropdownBtn.addEventListener("click", function(e) {

                            e.stopPropagation();

                            if (isOpen) {

                                closeDropdown();

                            } else {

                                openDropdown();

                            }

                        });

                        // OPEN
                        function openDropdown() {

                            dropdownMenu.classList.remove(
                                "max-h-0",
                                "opacity-0",
                                "-translate-y-2"
                            );

                            dropdownMenu.classList.add(
                                "max-h-72",
                                "opacity-100",
                                "translate-y-0"
                            );

                            dropdownArrow.classList.add("rotate-90");

                            isOpen = true;

                        }

                        // CLOSE
                        function closeDropdown() {

                            dropdownMenu.classList.remove(
                                "max-h-72",
                                "opacity-100",
                                "translate-y-0"
                            );

                            dropdownMenu.classList.add(
                                "max-h-0",
                                "opacity-0",
                                "-translate-y-2"
                            );

                            dropdownArrow.classList.remove("rotate-90");

                            isOpen = false;

                        }

                        // SELECT
                        function selectProject(id, name) {

                            projectInput.value = id;
                            selectedText.innerText = name;

                            closeDropdown();

                        }

                        // CLICK OUTSIDE
                        document.addEventListener("click", function(e) {

                            if (
                                !dropdownBtn.contains(e.target) &&
                                !dropdownMenu.contains(e.target)
                            ) {
                                closeDropdown();
                            }

                        });

                        // ESC CLOSE
                        document.addEventListener("keydown", function(e) {

                            if (e.key === "Escape") {
                                closeDropdown();
                            }

                        });
                    </script>

                    <div class="grid grid-cols-2 gap-3 mt-4 text-xs md:text-sm">

                        <input
                            name="full_name"
                            required
                            placeholder="Full Name"
                            class="h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                        <input
                            name="phone"
                            required
                            placeholder="Mobile Number"
                            class="h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="Email Address"
                            class="col-span-2 h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                        <p class="col-span-2 text-xs">Booking Date & Time</p>
                        <input
                            type="time"
                            name="preferred_time"
                            required
                            class="h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                        <input
                            type="date"
                            name="visit_date"
                            min="<?php echo date('Y-m-d'); ?>"
                            required
                            class="h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                        <input
                            name="budget"
                            placeholder="Budget"
                            class="col-span-2 h-10 md:h-11 rounded-lg border border-gray-200 px-4 outline-none focus:border-green-500">

                    </div>

                    <button
                        class="mt-3 md:mt-6 w-full h-10 md:h-12 rounded-lg bg-green-600 hover:bg-green-700 transition text-white font-semibold text-xs md:text-sm">

                        Book Free Site Visit

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<!-- Client Testimonials -->
<?php if (!empty($testimonialVideos)): ?>
    <section class="py-12 md:py-16 overflow-hidden">

        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">

            <!-- TOP BAR -->
            <div class="flex items-center justify-between mb-8">

                <div>
                    <div
                        class="flex items-center gap-2 text-red-500 uppercase font-semibold text-xs md:text-sm mb-3">
                        <i class="fa-solid fa-video"></i>
                        <span>Client Testimonials</span>
                    </div>

                    <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
                        Hear What Our Happy Clients Say
                    </h2>

                    <p class="text-gray-500 text-xs md:text-base">
                        Watch real experiences and success stories shared by our valued clients
                    </p>
                </div>

                <!-- NAVIGATION -->
                <div class="hidden md:flex items-center gap-3">

                    <button
                        class="testimonial-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button
                        class="testimonial-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                </div>

            </div>

            <!-- SWIPER -->
            <div class="swiper testimonialSwiper overflow-visible">

                <div class="swiper-wrapper">

                    <?php foreach ($testimonialVideos as $video): ?>
                        <?php
                        $embedUrl = videoEmbedUrl($video['video_url']);

                        if ($embedUrl === '') {
                            continue;
                        }
                        ?>
                        <div class="swiper-slide">

                            <div
                                class="overflow-hidden
    rounded-2xl
    w-full
    h-[360px]
    sm:h-[400px]
    md:h-[440px]
    lg:h-[460px]
    bg-black
    border border-gray-200
    shadow-lg">

                                <iframe class="w-full h-full" src="<?php echo e($embedUrl); ?>"
                                    title="<?php echo e($video['video_title'] ?: $video['project_name']); ?>" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>

                            </div>

                        </div>

                        <div class="swiper-slide">

                            <div
                                class="overflow-hidden
    rounded-2xl
    w-full
    h-[360px]
    sm:h-[400px]
    md:h-[440px]
    lg:h-[460px]
    bg-black
    border border-gray-200
    shadow-lg">

                                <iframe class="w-full h-full" src="<?php echo e($embedUrl); ?>"
                                    title="<?php echo e($video['video_title'] ?: $video['project_name']); ?>" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>

                            </div>

                        </div>

                        <div class="swiper-slide">

                            <div
                                class="overflow-hidden
    rounded-2xl
    w-full
    h-[360px]
    sm:h-[400px]
    md:h-[440px]
    lg:h-[460px]
    bg-black
    border border-gray-200
    shadow-lg">

                                <iframe class="w-full h-full" src="<?php echo e($embedUrl); ?>"
                                    title="<?php echo e($video['video_title'] ?: $video['project_name']); ?>" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>

                            </div>

                        </div>

                        <div class="swiper-slide">

                            <div
                                class="overflow-hidden
    rounded-2xl
    w-full
    h-[360px]
    sm:h-[400px]
    md:h-[440px]
    lg:h-[460px]
    bg-black
    border border-gray-200
    shadow-lg">

                                <iframe class="w-full h-full" src="<?php echo e($embedUrl); ?>"
                                    title="<?php echo e($video['video_title'] ?: $video['project_name']); ?>" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>

                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>
<?php endif; ?>

<!-- FAQ's -->
<section class="mx-auto max-w-3xl px-4 pb-12 md:pb-16">

    <!-- Heading -->
    <h2 class="text-primary text-center text-2xl md:text-3xl font-semibold leading-tight">
        Frequently
        <span class="relative inline-block border-b-2 border-accent border-solid rounded-sm pb-3">
            Asked
        </span>
        Questions
    </h2>

    <!-- FAQ Container -->
    <div class="mt-10 space-y-4">

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                What products do you offer on your platform?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    We offer electronics, fashion, home essentials, beauty products,
                    lifestyle accessories, and many trending collections from trusted sellers.
                </p>

            </div>

        </div>

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                How long does delivery take?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    Delivery usually takes between 2–7 business days depending on your
                    location and shipping option selected.
                </p>

            </div>

        </div>

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                What payment methods are available?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    We support UPI, debit cards, credit cards, net banking, wallets,
                    and cash on delivery for eligible orders.
                </p>

            </div>

        </div>

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                What products do you offer on your platform?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    We offer electronics, fashion, home essentials, beauty products,
                    lifestyle accessories, and many trending collections from trusted sellers.
                </p>

            </div>

        </div>

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                How long does delivery take?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    Delivery usually takes between 2–7 business days depending on your
                    location and shipping option selected.
                </p>

            </div>

        </div>

        <!-- ITEM -->
        <div class="faq-item border rounded-xl overflow-hidden bg-white">

            <button
                class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

                What payment methods are available?

                <!-- SVG ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="faq-icon w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />

                </svg>

            </button>

            <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

                <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
                    We support UPI, debit cards, credit cards, net banking, wallets,
                    and cash on delivery for eligible orders.
                </p>

            </div>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>