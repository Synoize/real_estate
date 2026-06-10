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

<section class="pt-10 pb-8">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
                    <i class="fa-solid fa-building"></i>
                    <span>Properties</span>
                </div>
                <h1 class="text-primary text-3xl md:text-4xl font-semibold leading-tight">
                    All Projects
                </h1>
                <p class="mt-3 text-sm md:text-base text-gray-500">
                    <?php echo $filterSummary ? e(implode(' | ', $filterSummary)) : 'Browse every published project from verified builders.'; ?>
                </p>
            </div>

            <div class="rounded-lg bg-white border border-gray-200 px-4 py-3 text-sm text-gray-600">
                <span class="font-black text-primary"><?php echo (int)$projectCount; ?></span>
                <?php echo $projectCount === 1 ? 'project found' : 'projects found'; ?>
            </div>
        </div>

        <form method="get" action="<?php echo BASE_URL; ?>projects" class="mt-8 grid grid-cols-1 gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto]">
            <label class="block">
                <span class="mb-2 block text-xs font-bold uppercase text-gray-500">City</span>
                <select name="city" class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-primary outline-none focus:border-primary">
                    <option value="">All cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo e($city['city']); ?>" <?php echo $filters['city'] === $city['city'] ? 'selected' : ''; ?>>
                            <?php echo e($city['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-bold uppercase text-gray-500">Project Type</span>
                <select name="type" class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-primary outline-none focus:border-primary">
                    <option value="">All types</option>
                    <?php foreach ($propertyTypes as $type): ?>
                        <option value="<?php echo e($type); ?>" <?php echo $filters['type'] === $type ? 'selected' : ''; ?>>
                            <?php echo e($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-bold uppercase text-gray-500">Budget</span>
                <select name="budget" class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-primary outline-none focus:border-primary">
                    <option value="">Any budget</option>
                    <?php foreach ($budgetOptions as $budget): ?>
                        <option value="<?php echo e($budget['value']); ?>" <?php echo $selectedBudget === $budget['value'] ? 'selected' : ''; ?>>
                            <?php echo e($budget['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-bold uppercase text-gray-500">Search</span>
                <input type="text" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Project, locality or builder" class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-primary outline-none focus:border-primary">
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="h-12 flex-1 rounded-lg bg-primary px-5 text-sm font-bold text-white hover:opacity-90 xl:flex-none">
                    <i class="fa-solid fa-filter mr-2"></i>
                    Filter
                </button>
                <a href="<?php echo BASE_URL; ?>projects" class="flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:text-primary" aria-label="Clear filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>
</section>

<section class="bg-white py-10 md:py-16">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <?php if (empty($projects)): ?>
            <div class="min-h-[360px] p-4 flex justify-center flex-col items-center text-center">
                <i class="fa-solid fa-building mb-4 text-4xl md:text-6xl text-gray-400"></i>
                <h2 class="text-xl font-bold text-gray-900">No projects found</h2>
                <p class="mt-2 text-gray-500 text-sm">Try a different city, type, budget, or search term.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $projectCardWrapperClass = 'h-full';
                    $projectCardImageClass = 'w-full h-[220px] object-cover rounded-2xl overflow-hidden';
                    require __DIR__ . '/includes/project-card.php';
                    unset($projectCardWrapperClass, $projectCardImageClass, $unitPlans);
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
