<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'Locations';

$cities = $pdo->query("
    SELECT city, COUNT(DISTINCT id) AS projects
    FROM projects WHERE deleted_at IS NULL AND city != ''
    GROUP BY city ORDER BY projects DESC
")->fetchAll();

$localities = $pdo->query("
    SELECT city, locality, COUNT(*) AS projects
    FROM projects WHERE deleted_at IS NULL AND city != '' AND locality != ''
    GROUP BY city, locality ORDER BY projects DESC LIMIT 100
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Locations</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo count($cities); ?> cities</p>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Cities</h2>
                <div class="space-y-2">
                    <?php foreach ($cities as $c): ?>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="font-medium"><?php echo e($c['city']); ?></span>
                            <span class="text-sm text-gray-500"><?php echo (int)$c['projects']; ?> projects</span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($cities)): ?><p class="text-sm text-gray-500">No locations found.</p><?php endif; ?>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Localities</h2>
                <div class="space-y-2 max-h-[500px] overflow-y-auto">
                    <?php foreach ($localities as $l): ?>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm"><span class="font-medium"><?php echo e($l['locality']); ?></span> <span class="text-gray-400">(<?php echo e($l['city']); ?>)</span></span>
                            <span class="text-sm text-gray-500"><?php echo (int)$l['projects']; ?> projects</span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($localities)): ?><p class="text-sm text-gray-500">No localities found.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
