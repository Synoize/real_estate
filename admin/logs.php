<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireAdmin();

$pageTitle = 'Activity Logs';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$logs = $pdo->prepare("
    SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset
");
$logs->bindValue(':limit', $perPage, PDO::PARAM_INT);
$logs->bindValue(':offset', $offset, PDO::PARAM_INT);
$logs->execute();
$logs = $logs->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Monitoring</p>
                <h1 class="text-3xl font-semibold text-primary">Activity Logs</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo $total; ?> total entries</p>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">User Type</th>
                            <th class="px-4 py-3">User ID</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                        <?php echo $log['user_type'] === 'Admin' ? 'bg-purple-100 text-purple-700' : ($log['user_type'] === 'Builder' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'); ?>">
                                        <?php echo e($log['user_type']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs"><?php echo e($log['user_id'] ?: '-'); ?></td>
                                <td class="px-4 py-3 font-medium"><?php echo e($log['action_title']); ?></td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-[300px] truncate"><?php echo e($log['action_description'] ?: '-'); ?></td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-400"><?php echo e($log['ip_address'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?><tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No logs yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex items-center justify-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-gray-100">&larr; Prev</a>
                <?php endif; ?>
                <span class="text-sm text-gray-500">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-gray-100">Next &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
