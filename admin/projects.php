<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireAdmin();

$pageTitle = 'Manage Projects';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $allowed = ['draft', 'pending', 'published', 'rejected'];
        if ($id && in_array($newStatus, $allowed)) {
            $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            setFlash('Project status updated to ' . $newStatus, 'success');
            redirect(ADMIN_URL . 'projects');
        }
    }

    if ($action === 'delete') {
        if ($id) {
            $pdo->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
            setFlash('Project deleted.', 'success');
            redirect(ADMIN_URL . 'projects');
        }
    }

    if ($action === 'toggle_featured') {
        if ($id) {
            $current = $pdo->prepare("SELECT is_featured FROM projects WHERE id = ?");
            $current->execute([$id]);
            $val = (int)$current->fetchColumn();
            $pdo->prepare("UPDATE projects SET is_featured = ? WHERE id = ?")->execute([$val ? 0 : 1, $id]);
            setFlash('Featured status toggled.', 'success');
            redirect(ADMIN_URL . 'projects');
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = 'p.deleted_at IS NULL';
$params = [];

if ($statusFilter) {
    $where .= ' AND p.status = :status';
    $params[':status'] = $statusFilter;
}
if ($search) {
    $where .= ' AND (p.project_name LIKE :search OR b.company_name LIKE :search2)';
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
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

        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="mt-4 rounded-lg border px-4 py-3 text-sm font-medium <?php echo $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-600'; ?>">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form method="get" class="mt-4 flex gap-3">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search project or builder..." class="flex-1 max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                <option value="">All Status</option>
                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                            <th class="px-4 py-3">Action</th>
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
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button name="action" value="toggle_featured" class="text-sm <?php echo $p['is_featured'] ? 'text-amber-500' : 'text-gray-300'; ?> hover:text-amber-600">
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                        <?php echo $p['status'] === 'published' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : ($p['status'] === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500')); ?>">
                                        <?php echo e($p['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2 items-center">
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <input type="hidden" name="status" value="published">
                                                <button name="action" value="update_status" class="text-green-600 hover:underline text-xs font-medium">Approve</button>
                                            </form>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button name="action" value="update_status" class="text-red-600 hover:underline text-xs font-medium">Reject</button>
                                            </form>
                                        <?php elseif ($p['status'] === 'published'): ?>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <input type="hidden" name="status" value="draft">
                                                <button name="action" value="update_status" class="text-gray-500 hover:underline text-xs font-medium">Unpublish</button>
                                            </form>
                                        <?php elseif ($p['status'] === 'draft' || $p['status'] === 'rejected'): ?>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <input type="hidden" name="status" value="published">
                                                <button name="action" value="update_status" class="text-green-600 hover:underline text-xs font-medium">Publish</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" onsubmit="return confirm('Delete this project?')" class="inline">
                                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                            <button name="action" value="delete" class="text-rose-600 hover:underline text-xs font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?><tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">No projects found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
