<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireManager();

$pageTitle = 'Projects';
$managerId = (int)$_SESSION['manager_id'];

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = 'p.assigned_manager_id = ? AND p.deleted_at IS NULL';
$params = [$managerId];

if ($statusFilter) {
    $where .= ' AND p.status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where .= ' AND (p.project_name LIKE ? OR b.company_name LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$projects = $pdo->prepare("
    SELECT p.*, b.company_name, b.company_slug,
        (SELECT COUNT(*) FROM project_unit_plans WHERE project_id = p.id) AS unit_plans,
        (SELECT COUNT(*) FROM inquiries WHERE project_id = p.id) AS enquiries
    FROM projects p
    INNER JOIN builders b ON b.id = p.builder_id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT 100
");
$projects->execute($params);
$projects = $projects->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Projects</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo count($projects); ?> projects</p>
        </div>

        <form method="get" class="mt-4 flex gap-3">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search project or builder..." class="flex-1 max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                <option value="">All Status</option>
                <?php foreach (['draft', 'pending', 'published', 'rejected'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white">Filter</button>
        </form>

        <div class="mt-4 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Project</th>
                            <th class="px-4 py-3">Builder</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">City</th>
                            <th class="px-4 py-3">Plans</th>
                            <th class="px-4 py-3">Enq.</th>
                            <th class="px-4 py-3">Views</th>
                            <th class="px-4 py-3">Featured</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium max-w-[200px] truncate">
                                    <a href="<?php echo BASE_URL . 'project/' . e($p['slug']); ?>" target="_blank" class="hover:text-accent">
                                        <?php echo e($p['project_name']); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-xs"><?php echo e($p['company_name']); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($p['project_type']); ?></td>
                                <td class="px-4 py-3"><?php echo e($p['city']); ?></td>
                                <td class="px-4 py-3"><?php echo (int)$p['unit_plans']; ?></td>
                                <td class="px-4 py-3"><?php echo (int)$p['enquiries']; ?></td>
                                <td class="px-4 py-3"><?php echo (int)$p['total_views']; ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($p['is_featured']): ?>
                                        <i class="fa-solid fa-star text-amber-500"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star text-gray-300"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                        <?php echo $p['status'] === 'published' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : ($p['status'] === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500')); ?>">
                                        <?php echo e($p['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?><tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">No projects assigned.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
