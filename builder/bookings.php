<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Site Visit Bookings';
$builderId = (int)$_SESSION['builder_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $allowed = ['Pending', 'Contacted', 'Scheduled', 'Visited', 'Successful', 'Cancelled'];
        if ($id && in_array($status, $allowed)) {
            $bStmt = $pdo->prepare("SELECT b.user_id, p.project_name FROM site_visit_bookings b JOIN projects p ON p.id = b.project_id WHERE b.id = ? AND b.builder_id = ?");
            $bStmt->execute([$id, $builderId]);
            $b = $bStmt->fetch(PDO::FETCH_ASSOC);
            if ($b) {
                $pdo->prepare("UPDATE site_visit_bookings SET status = ? WHERE id = ? AND builder_id = ?")->execute([$status, $id, $builderId]);
                if ($b['user_id']) {
                    createNotification($b['user_id'], 'site_visit', 'Site Visit Updated', 'Your site visit for ' . $b['project_name'] . ' is now: ' . $status, BUILDER_URL . 'bookings');
                }
            }
            setFlash('Booking status updated.', 'success');
            redirect(BUILDER_URL . 'bookings');
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$where = 'b.builder_id = ?';
$params = [$builderId];
if ($statusFilter) {
    $where .= ' AND b.status = ?';
    $params[] = $statusFilter;
}

$bookings = $pdo->prepare("
    SELECT b.*, p.project_name, p.slug, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone,
        m.full_name AS manager_name, m.whatsapp_number AS manager_whatsapp
    FROM site_visit_bookings b
    INNER JOIN projects p ON p.id = b.project_id
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN associate_managers m ON m.id = b.assigned_manager_id
    WHERE $where ORDER BY b.created_at DESC LIMIT 100
");
$bookings->execute($params);
$bookings = $bookings->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Site Visit Bookings</h1>
            </div>
            <p class="text-sm text-gray-500"><?php echo count($bookings); ?> bookings</p>
        </div>

        <form method="get" class="mt-4 flex gap-3">
            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                <option value="">All Status</option>
                <?php foreach (['Pending', 'Contacted', 'Scheduled', 'Visited', 'Successful', 'Cancelled'] as $s): ?>
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
                            <th class="px-4 py-3">Booking ID</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Project</th>
                            <th class="px-4 py-3">Visit Date</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Manager</th>
                            <th class="px-4 py-3">WhatsApp</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-mono text-xs"><?php echo e($b['booking_id']); ?></td>
                                <td class="px-4 py-3 font-medium"><?php echo e($b['full_name'] ?: $b['user_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($b['phone'] ?: $b['user_phone']); ?></td>
                                <td class="px-4 py-3 max-w-[150px] truncate">
                                    <a href="<?php echo BASE_URL . 'project/' . e($b['slug']); ?>" target="_blank" class="hover:text-accent"><?php echo e($b['project_name']); ?></a>
                                </td>
                                <td class="px-4 py-3 text-xs"><?php echo $b['visit_date'] ? date('d M Y', strtotime($b['visit_date'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($b['preferred_time'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-xs"><?php echo e($b['manager_name'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($b['manager_whatsapp']): ?>
                                        <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $b['manager_whatsapp'])); ?>" target="_blank" class="text-green-600 hover:underline text-xs">
                                            <i class="fa-brands fa-whatsapp"></i> Connect
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $b['status'] === 'Successful' ? 'bg-green-100 text-green-700' : ($b['status'] === 'Cancelled' ? 'bg-red-100 text-red-600' : ($b['status'] === 'Visited' ? 'bg-blue-100 text-blue-700' : ($b['status'] === 'Scheduled' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'))); ?>">
                                        <?php echo e($b['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <?php foreach (['Pending', 'Contacted', 'Scheduled', 'Visited', 'Successful', 'Cancelled'] as $s): ?>
                                                <option value="<?php echo $s; ?>" <?php echo $b['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?><tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">No bookings found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
