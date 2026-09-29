<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $projectId = (int)($_POST['project_id'] ?? 0);

    if ($projectId > 0 && in_array($action, ['publish_project', 'reject_project'], true)) {
        $status = $action === 'publish_project' ? 'published' : 'rejected';
        $stmt = $pdo->prepare('UPDATE projects SET status = :status, is_verified = :verified WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':verified' => $status === 'published' ? 1 : 0,
            ':id' => $projectId
        ]);
        setFlash('Project status updated.', 'success');
        redirect(ADMIN_URL);
    }
}

$stats = [
    'Users' => tableCount('users', "status <> 'deleted'"),
    'Builders' => tableCount('builders', "status != 'pending'"),
    'Pending' => tableCount('builders', "status = 'pending'"),
    'Projects' => tableCount('projects', 'deleted_at IS NULL'),
    'Employees' => tableCount('employees', "status = 'active'"),
    'Enquiries' => tableCount('inquiries'),
    'Site Visits' => tableCount('site_visit_bookings'),
];

$monthlyUsers = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
    FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at)
")->fetchAll();

$monthlyBuilders = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
    FROM builders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at)
")->fetchAll();

$cityStats = $pdo->query("
    SELECT city, COUNT(*) AS total FROM projects
    WHERE deleted_at IS NULL AND city != ''
    GROUP BY city ORDER BY total DESC LIMIT 10
")->fetchAll();

$projects = $pdo->query("
    SELECT p.*, b.company_name
    FROM projects p
    INNER JOIN builders b ON b.id = p.builder_id
    ORDER BY p.created_at DESC
    LIMIT 20
")->fetchAll();

$leads = $pdo->query("
    SELECT i.*, p.project_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    ORDER BY i.created_at DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div>
            <p class="text-xs uppercase text-accent">Admin</p>
            <h1 class="text-3xl font-semibold text-primary">Dashboard</h1>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <?php foreach ($stats as $label => $value): ?>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-black text-gray-950"><?php echo (int)$value; ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo e($label); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary">Monthly Registrations</h2>
                <div class="mt-4 flex items-end gap-2 h-40">
                    <?php
                    $months = array_column($monthlyUsers, 'month');
                    $userCounts = array_column($monthlyUsers, 'total');
                    $builderData = [];
                    foreach ($monthlyBuilders as $m) {
                        $builderData[$m['month']] = $m['total'];
                    }
                    $allVals = array_merge($userCounts, array_values($builderData));
                    $maxVal = !empty($allVals) ? max($allVals) : 1;
                    foreach ($monthlyUsers as $i => $row):
                        $uH = round(($row['total'] / $maxVal) * 160);
                        $bC = $builderData[$row['month']] ?? 0;
                        $bH = round(($bC / $maxVal) * 160);
                    ?>
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full flex flex-col items-center justify-end h-40 gap-0.5">
                                <div title="Builders: <?php echo $bC; ?>" style="height:<?php echo max($bH, 1); ?>px" class="w-4 bg-amber-400 rounded-t"></div>
                                <div title="Users: <?php echo $row['total']; ?>" style="height:<?php echo max($uH, 1); ?>px" class="w-4 bg-primary rounded-t"></div>
                            </div>
                            <span class="text-[10px] text-gray-500"><?php echo e($row['month']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2 flex gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-primary rounded"></span> Users</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-amber-400 rounded"></span> Builders</span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary">Projects by City</h2>
                <div class="mt-4 space-y-3">
                    <?php
                    $cityTotals = array_column($cityStats, 'total');
                    $cityMax = !empty($cityTotals) ? max($cityTotals) : 1;
                    ?>
                    <?php foreach ($cityStats as $city): ?>
                        <div class="flex items-center gap-3">
                            <span class="w-24 text-sm text-gray-700 truncate"><?php echo e($city['city']); ?></span>
                            <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                                <div style="width:<?php echo round(($city['total'] / $cityMax) * 100); ?>%" class="h-full bg-accent rounded-full"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-600 w-8 text-right"><?php echo (int)$city['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($cityStats)): ?><p class="text-sm text-gray-500">No projects yet.</p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-[1fr_400px] gap-6">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary">Project Moderation</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Project</th>
                                <th class="px-4 py-3">Builder</th>
                                <th class="px-4 py-3">City</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-3 font-bold"><?php echo e($project['project_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($project['company_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($project['city']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($project['status']); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <form method="post">
                                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                                <button name="action" value="publish_project" class="rounded bg-green-600 px-3 py-2 text-xs font-bold text-white">Publish</button>
                                            </form>
                                            <form method="post">
                                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                                <button name="action" value="reject_project" class="rounded bg-rose-600 px-3 py-2 text-xs font-bold text-white">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-bold text-primary">Latest Enquiries</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($leads as $lead): ?>
                        <div class="rounded-md bg-gray-50 p-4">
                            <p class="font-bold text-gray-950"><?php echo e($lead['full_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo e($lead['project_name']); ?></p>
                            <p class="mt-2 text-sm font-semibold text-accent"><?php echo e($lead['phone']); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($leads)): ?><p class="text-sm text-gray-500">No enquiries yet.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>