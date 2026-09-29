<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'Employee Dashboard';

$totalBuilders = tableCount('builders', "deleted_at IS NULL");
$totalUsers = tableCount('users', "status <> 'deleted'");
$totalProjects = tableCount('projects', "deleted_at IS NULL");
$totalEnquiries = tableCount('inquiries', '1=1');
$pendingBuilders = tableCount('builders', "status = 'pending'");
$newLeads = tableCount('inquiries', "inquiry_status = 'New'");
$monthlyData = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
    FROM projects WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at) ASC
")->fetchAll();

$cityStats = $pdo->query("
    SELECT city, COUNT(*) AS total
    FROM projects WHERE deleted_at IS NULL AND city != '' AND city IS NOT NULL
    GROUP BY city ORDER BY total DESC LIMIT 8
")->fetchAll();

$recentBuilders = $pdo->query("SELECT * FROM builders WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentLeads = $pdo->query("
    SELECT i.*, p.project_name FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    ORDER BY i.created_at DESC LIMIT 5
")->fetchAll();

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
                <p class="text-xs uppercase text-accent">Employee Panel</p>
                <h1 class="text-3xl font-semibold text-primary"><?php echo e($_SESSION['employee_name'] ?? 'Dashboard'); ?></h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo date('l, d M Y'); ?></p>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalBuilders; ?></p>
                <p class="text-sm text-gray-500">Total Builders</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalUsers; ?></p>
                <p class="text-sm text-gray-500">Total Users</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalProjects; ?></p>
                <p class="text-sm text-gray-500">Projects</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalEnquiries; ?></p>
                <p class="text-sm text-gray-500">Enquiries</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $pendingBuilders; ?></p>
                <p class="text-sm text-gray-500">Pending Builders</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $newLeads; ?></p>
                <p class="text-sm text-gray-500">New Leads</p>
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
                <h2 class="text-lg font-bold text-primary mb-4">Recent Builders</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Company</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBuilders as $b): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($b['company_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($b['builder_name']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $b['status'] === 'active' ? 'bg-green-100 text-green-700' : ($b['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'); ?>"><?php echo e($b['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentBuilders)): ?><tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No builders yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Leads</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Phone</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLeads as $l): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($l['full_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($l['project_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($l['phone']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLeads)): ?><tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No leads yet.</td></tr><?php endif; ?>
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
