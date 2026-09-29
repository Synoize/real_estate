<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Activity Logs';
$builderId = (int)$_SESSION['builder_id'];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$managerIds = $pdo->prepare("SELECT id FROM associate_managers WHERE builder_id = ?");
$managerIds->execute([$builderId]);
$managerIds = array_column($managerIds->fetchAll(), 'id');

$countTotal = $pdo->prepare("
    SELECT COUNT(*) FROM activity_logs
    WHERE (user_type = 'Builder' AND user_id = ?)
       OR (user_type = 'Manager' AND user_id IN (" . ($managerIds ? implode(',', $managerIds) : 'NULL') . "))
");
$countTotal->execute([$builderId]);
$totalRows = (int)$countTotal->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$logs = $pdo->prepare("
    SELECT *, user_type AS log_user_type FROM activity_logs
    WHERE (user_type = 'Builder' AND user_id = ?)
       OR (user_type = 'Manager' AND user_id IN (" . ($managerIds ? implode(',', $managerIds) : 'NULL') . "))
    ORDER BY created_at DESC LIMIT $perPage OFFSET $offset
");
$logs->execute([$builderId]);
$logs = $logs->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Logs</p>
                <h1 class="text-3xl font-semibold text-primary">Activity Logs</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo $totalRows; ?> entries</p>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium <?php echo $l['log_user_type'] === 'Builder' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700'; ?>">
                                    <?php echo e($l['log_user_type']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium"><?php echo e($l['action_title']); ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e($l['action_description']); ?></td>
                            <td class="px-4 py-3 font-mono text-xs"><?php echo e($l['ip_address'] ?: '-'); ?></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y h:i A', strtotime($l['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?><tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No activity logs.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex justify-center gap-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="rounded-md px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
