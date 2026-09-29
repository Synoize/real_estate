<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireManager();

$pageTitle = 'Manager Dashboard';
$managerId = (int)$_SESSION['manager_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_inquiry') {
        $stmt = $pdo->prepare("UPDATE inquiries SET inquiry_status = :status WHERE id = :id AND assigned_manager_id = :mid");
        $stmt->execute([
            ':status' => $_POST['status'] ?? 'Contacted',
            ':id' => (int)($_POST['inquiry_id'] ?? 0),
            ':mid' => $managerId
        ]);
        setFlash('Inquiry status updated.', 'success');
        redirect(MANAGER_URL);
    }

    if ($action === 'update_visit') {
        $stmt = $pdo->prepare("UPDATE site_visit_bookings SET status = :status WHERE id = :id AND assigned_manager_id = :mid");
        $stmt->execute([
            ':status' => $_POST['status'] ?? 'Contacted',
            ':id' => (int)($_POST['booking_id'] ?? 0),
            ':mid' => $managerId
        ]);
        setFlash('Visit status updated.', 'success');
        redirect(MANAGER_URL);
    }
}

$totalProjects = tableCount('projects', 'assigned_manager_id = ? AND deleted_at IS NULL', [$managerId]);

$inquiries = $pdo->prepare("
    SELECT i.*, p.project_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    WHERE i.assigned_manager_id = ?
    ORDER BY i.created_at DESC
");
$inquiries->execute([$managerId]);
$inquiries = $inquiries->fetchAll();

$visits = $pdo->prepare("
    SELECT v.*, p.project_name
    FROM site_visit_bookings v
    INNER JOIN projects p ON p.id = v.project_id
    WHERE v.assigned_manager_id = ?
    ORDER BY v.visit_date ASC, v.created_at DESC
");
$visits->execute([$managerId]);
$visits = $visits->fetchAll();

$totalInquiries = count($inquiries);
$totalVisits = count($visits);
$successfulVisits = 0;
foreach ($visits as $v) {
    if ($v['status'] === 'Successful') $successfulVisits++;
}

$monthlyData = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
    FROM projects
    WHERE assigned_manager_id = ? AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at) ASC
");
$monthlyData->execute([$managerId]);
$monthlyData = $monthlyData->fetchAll();

$cityStats = $pdo->prepare("
    SELECT city, COUNT(*) AS total
    FROM projects WHERE assigned_manager_id = ? AND deleted_at IS NULL AND city != '' AND city IS NOT NULL
    GROUP BY city ORDER BY total DESC LIMIT 8
");
$cityStats->execute([$managerId]);
$cityStats = $cityStats->fetchAll();

$recentInquiries = array_slice($inquiries, 0, 5);
$recentVisits = array_slice($visits, 0, 5);

$recentLogs = $pdo->prepare("
    SELECT * FROM activity_logs
    WHERE user_type = 'Manager' AND user_id = ?
    ORDER BY created_at DESC LIMIT 5
");
$recentLogs->execute([$managerId]);
$recentLogs = $recentLogs->fetchAll();

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
                <p class="text-xs uppercase text-accent">Associate Manager Panel</p>
                <h1 class="text-3xl font-semibold text-primary"><?php echo e($_SESSION['manager_name'] ?? 'Dashboard'); ?></h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo date('l, d M Y'); ?></p>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalProjects; ?></p>
                <p class="text-sm text-gray-500">Assigned Projects</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalInquiries; ?></p>
                <p class="text-sm text-gray-500">Total Inquiries</p>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <p class="text-2xl font-black"><?php echo $totalVisits; ?></p>
                <p class="text-sm text-gray-500">Site Visits</p>
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

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Inquiries</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInquiries as $inq): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($inq['full_name']); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($inq['project_name']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $inq['inquiry_status'] === 'Booked' ? 'bg-green-100 text-green-700' : ($inq['inquiry_status'] === 'Lost' ? 'bg-red-100 text-red-600' : ($inq['inquiry_status'] === 'New' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')); ?>">
                                        <?php echo e($inq['inquiry_status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="update_inquiry">
                                        <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <?php foreach (['New', 'Contacted', 'Qualified', 'Site Visit Planned', 'Booked', 'Lost'] as $s): ?>
                                                <option value="<?php echo $s; ?>" <?php echo $inq['inquiry_status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentInquiries)): ?><tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No inquiries yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <a href="<?php echo MANAGER_URL; ?>inquiries" class="mt-3 inline-block text-sm text-accent hover:underline">View all inquiries →</a>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Site Visits</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentVisits as $v): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($v['full_name']); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($v['project_name']); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo $v['visit_date'] ? date('d M Y', strtotime($v['visit_date'])) : '-'; ?></td>
                                <td class="px-4 py-3">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="update_visit">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$v['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <?php foreach (['Pending', 'Contacted', 'Scheduled', 'Visited', 'Successful', 'Cancelled'] as $s): ?>
                                                <option value="<?php echo $s; ?>" <?php echo $v['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentVisits)): ?><tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No visits scheduled.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <a href="<?php echo MANAGER_URL; ?>bookings" class="mt-3 inline-block text-sm text-accent hover:underline">View all bookings →</a>
            </div>
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-lg font-bold text-primary mb-4">Recent Activity</h2>
                <div class="space-y-3">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="border-l-4 border-primary/30 pl-3 py-1">
                            <p class="text-xs font-medium"><?php echo e($log['action_title']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo e($log['action_description']); ?></p>
                            <p class="text-[10px] text-gray-400"><?php echo date('d M Y h:i A', strtotime($log['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentLogs)): ?><p class="text-sm text-gray-500">No recent activity.</p><?php endif; ?>
                </div>
                <a href="<?php echo MANAGER_URL; ?>logs" class="mt-3 inline-block text-sm text-accent hover:underline">View all logs →</a>
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
