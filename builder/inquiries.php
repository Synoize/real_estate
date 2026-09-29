<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Inquiries';
$builderId = (int)$_SESSION['builder_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $inqId = (int)($_POST['inquiry_id'] ?? 0);
        $managerId = $_POST['manager_id'] ? (int)$_POST['manager_id'] : null;
        if ($inqId) {
            $pdo->prepare("UPDATE inquiries SET assigned_manager_id = ? WHERE id = ? AND builder_id = ?")->execute([$managerId, $inqId, $builderId]);
            setFlash('Manager assigned.', 'success');
            redirect(BUILDER_URL . 'inquiries');
        }
    }

    if ($action === 'update_status') {
        $inqId = (int)($_POST['inquiry_id'] ?? 0);
        $status = $_POST['inquiry_status'] ?? 'New';
        $allowed = ['New', 'Contacted', 'Qualified', 'Site Visit Planned', 'Booked', 'Lost'];
        if ($inqId && in_array($status, $allowed)) {
            $inqStmt = $pdo->prepare("SELECT i.user_id, p.project_name FROM inquiries i JOIN projects p ON p.id = i.project_id WHERE i.id = ? AND i.builder_id = ?");
            $inqStmt->execute([$inqId, $builderId]);
            $inq = $inqStmt->fetch(PDO::FETCH_ASSOC);
            if ($inq) {
                $pdo->prepare("UPDATE inquiries SET inquiry_status = ? WHERE id = ? AND builder_id = ?")->execute([$status, $inqId, $builderId]);
                if ($inq['user_id']) {
                    createNotification($inq['user_id'], 'inquiry', 'Inquiry Updated', 'Your inquiry for ' . $inq['project_name'] . ' is now: ' . $status, BUILDER_URL . 'inquiries');
                }
            }
            setFlash('Status updated.', 'success');
            redirect(BUILDER_URL . 'inquiries');
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$where = 'i.builder_id = ?';
$params = [$builderId];
if ($statusFilter) {
    $where .= ' AND i.inquiry_status = ?';
    $params[] = $statusFilter;
}

$inquiries = $pdo->prepare("
    SELECT i.*, p.project_name, p.slug, m.full_name AS manager_name, m.whatsapp_number AS manager_whatsapp
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    LEFT JOIN associate_managers m ON m.id = i.assigned_manager_id
    WHERE $where ORDER BY i.created_at DESC LIMIT 100
");
$inquiries->execute($params);
$inquiries = $inquiries->fetchAll();

$managers = $pdo->prepare("SELECT * FROM associate_managers WHERE builder_id = ? AND status = 'active' ORDER BY full_name ASC");
$managers->execute([$builderId]);
$managers = $managers->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Inquiries</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo count($inquiries); ?> inquiries</p>
        </div>

        <form method="get" class="mt-4 flex gap-3">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                <option value="">All Status</option>
                <?php foreach (['New', 'Contacted', 'Qualified', 'Site Visit Planned', 'Booked', 'Lost'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white">Filter</button>
        </form>

        <div class="mt-4 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Project</th>
                            <th class="px-4 py-3">Budget</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Assigned Manager</th>
                            <th class="px-4 py-3">WhatsApp</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($inq['full_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($inq['phone']); ?></td>
                                <td class="px-4 py-3 max-w-[150px] truncate">
                                    <a href="<?php echo BASE_URL . 'project/' . e($inq['slug']); ?>" target="_blank" class="hover:text-accent"><?php echo e($inq['project_name']); ?></a>
                                </td>
                                <td class="px-4 py-3"><?php echo e($inq['budget'] ?: '-'); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($inq['source']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $inq['inquiry_status'] === 'Booked' ? 'bg-green-100 text-green-700' : ($inq['inquiry_status'] === 'Lost' ? 'bg-red-100 text-red-600' : ($inq['inquiry_status'] === 'New' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')); ?>">
                                        <?php echo e($inq['inquiry_status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs"><?php echo e($inq['manager_name'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($inq['manager_whatsapp']): ?>
                                        <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $inq['manager_whatsapp'])); ?>" target="_blank" class="text-green-600 hover:underline text-xs">
                                            <i class="fa-brands fa-whatsapp"></i> Connect
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M y', strtotime($inq['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2 items-center">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['id']; ?>">
                                            <select name="inquiry_status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                                <?php foreach (['New', 'Contacted', 'Qualified', 'Site Visit Planned', 'Booked', 'Lost'] as $s): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo $inq['inquiry_status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="action" value="update_status">
                                        </form>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['id']; ?>">
                                            <select name="manager_id" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                                <option value="">Assign...</option>
                                                <?php foreach ($managers as $m): ?>
                                                    <option value="<?php echo (int)$m['id']; ?>" <?php echo $inq['assigned_manager_id'] == $m['id'] ? 'selected' : ''; ?>><?php echo e($m['full_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="action" value="assign">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inquiries)): ?><tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">No inquiries found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
