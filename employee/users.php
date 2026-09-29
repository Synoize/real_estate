<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'All Users';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = "u.status <> 'deleted'";
$params = [];

if ($search) {
    $where .= " AND (u.full_name LIKE :search OR u.email LIKE :search2 OR u.phone LIKE :search3)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}
if ($status) {
    $where .= " AND u.status = :status";
    $params[':status'] = $status;
}

$stmt = $pdo->prepare("
    SELECT u.*, (SELECT COUNT(*) FROM inquiries WHERE user_id = u.id) AS total_inquiries
    FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT 200
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Users</h1>
            </div>
            <p class="text-sm text-gray-500">Total: <?php echo count($users); ?></p>
        </div>

        <form method="get" class="mt-4 flex gap-3">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search name, email or phone..." class="flex-1 max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                <option value="">All Status</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white">Filter</button>
        </form>

        <div class="mt-4 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">City</th>
                        <th class="px-4 py-3">Enquiries</th>
                        <th class="px-4 py-3">Verified</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium"><?php echo e($u['full_name']); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo e($u['email']); ?></td>
                            <td class="px-4 py-3"><?php echo e($u['phone']); ?></td>
                            <td class="px-4 py-3"><?php echo e($u['city'] ?: '-'); ?></td>
                            <td class="px-4 py-3"><?php echo (int)$u['total_inquiries']; ?></td>
                            <td class="px-4 py-3"><?php echo $u['is_verified'] ? 'Yes' : 'No'; ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-700' : ($u['status'] === 'blocked' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500'); ?>">
                                    <?php echo e($u['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No users found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
