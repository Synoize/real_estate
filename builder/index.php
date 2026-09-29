<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Builder Dashboard';
$builderId = (int)$_SESSION['builder_id'];

$totalProjects = tableCount('projects', 'builder_id = ? AND deleted_at IS NULL', [$builderId]);
$totalManagers = tableCount('associate_managers', 'builder_id = ?', [$builderId]);
$totalLeads = tableCount('inquiries', 'builder_id = ?', [$builderId]);
$totalVisits = tableCount('site_visit_bookings', 'builder_id = ?', [$builderId]);
$successfulVisits = tableCount('site_visit_bookings', 'builder_id = ? AND status = ?', [$builderId, 'Successful']);

$monthlyData = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
    FROM projects WHERE builder_id = ? AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at) ASC
");
$monthlyData->execute([$builderId]);
$monthlyData = $monthlyData->fetchAll();

$cityStats = $pdo->prepare("
    SELECT city, COUNT(*) AS total
    FROM projects WHERE builder_id = ? AND deleted_at IS NULL AND city != '' AND city IS NOT NULL
    GROUP BY city ORDER BY total DESC LIMIT 8
");
$cityStats->execute([$builderId]);
$cityStats = $cityStats->fetchAll();

$recentProjects = $pdo->prepare("SELECT * FROM projects WHERE builder_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
$recentProjects->execute([$builderId]);
$recentProjects = $recentProjects->fetchAll();

$recentLeads = $pdo->prepare("
    SELECT i.*, p.project_name FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    WHERE i.builder_id = ? ORDER BY i.created_at DESC LIMIT 5
");
$recentLeads->execute([$builderId]);
$recentLeads = $recentLeads->fetchAll();

$monthLabels = [];
$monthCounts = [];
foreach ($monthlyData as $m) {
    $monthLabels[] = $m['month'];
    $monthCounts[] = (int)$m['total'];
}

$cityLabels = [];
$cityCounts = [];
foreach ($cityStats as $c) {
    $cityLabels[] = $c['city'];
    $cityCounts[] = (int)$c['total'];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Builder Panel</p>
                <h1 class="text-3xl font-semibold text-primary"><?php echo e($_SESSION['builder_company_name'] ?? 'Dashboard'); ?></h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo date('l, d M Y'); ?></p>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalProjects; ?></p>
                <p class="text-sm text-gray-500">Total Projects</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalManagers; ?></p>
                <p class="text-sm text-gray-500">Associate Managers</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalLeads; ?></p>
                <p class="text-sm text-gray-500">Total Inquiries</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalVisits; ?></p>
                <p class="text-sm text-gray-500">Total Site Visits</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $successfulVisits; ?></p>
                <p class="text-sm text-gray-500">Successful Visits</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Projects Growth (12 months)</h2>
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Projects by City</h2>
                <canvas id="cityChart" height="200"></canvas>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Projects</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Project</th><th class="px-4 py-3">City</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentProjects as $p): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($p['project_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($p['city']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $p['status'] === 'published' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'); ?>"><?php echo e($p['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentProjects)): ?><tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No projects yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Inquiries</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLeads as $l): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($l['full_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($l['project_name']); ?></td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700"><?php echo e($l['inquiry_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLeads)): ?><tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No inquiries yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthLabels ?: ['Jan']); ?>,
        datasets: [{
            label: 'Projects',
            data: <?php echo json_encode($monthCounts ?: [0]); ?>,
            backgroundColor: '#2B1C5A',
            borderRadius: 4
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
new Chart(document.getElementById('cityChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($cityLabels ?: ['N/A']); ?>,
        datasets: [{
            label: 'Projects',
            data: <?php echo json_encode($cityCounts ?: [0]); ?>,
            backgroundColor: '#EC4B02',
            borderRadius: 4
        }]
    },
    options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
