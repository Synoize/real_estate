<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'Contacts & Enquiries';

$filter = $_GET['filter'] ?? '';

if ($filter === 'site_visits') {
    require_once __DIR__ . '/includes/header.php';
    $visits = $pdo->query("
        SELECT sv.*, p.project_name, b.company_name
        FROM site_visit_bookings sv
        INNER JOIN projects p ON p.id = sv.project_id
        INNER JOIN builders b ON b.id = p.builder_id
        ORDER BY sv.created_at DESC LIMIT 100
    ")->fetchAll();
    ?>
    <section class="min-h-screen bg-gray-50 p-6 mt-20">
        <div class="max-w-[1400px] mx-auto">
            <div class="flex items-center justify-between">
                <div><p class="text-xs uppercase text-accent">Management</p><h1 class="text-3xl font-semibold text-primary">Site Visit Bookings</h1></div>
                <a href="<?php echo EMPLOYEE_URL; ?>contacts" class="text-sm text-accent hover:underline">Back to Enquiries</a>
            </div>
            <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Builder</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $v): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium"><?php echo e($v['full_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($v['phone']); ?></td>
                                <td class="px-4 py-3"><?php echo e($v['project_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($v['company_name']); ?></td>
                                <td class="px-4 py-3"><?php echo e($v['visit_date'] ?: '-'); ?></td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700"><?php echo e($v['status']); ?></span></td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($v['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($visits)): ?><tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No site visits yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/includes/footer.php'; exit;
}

$where = '1=1';
if ($filter === 'complaints') $where = "i.message IS NOT NULL AND i.message != ''";

$enquiries = $pdo->query("
    SELECT i.*, p.project_name, b.company_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    INNER JOIN builders b ON b.id = i.builder_id
    WHERE $where
    ORDER BY i.created_at DESC LIMIT 200
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Enquiries & Complaints</h1>
            </div>
            <div class="flex gap-2">
                <a href="<?php echo EMPLOYEE_URL; ?>contacts" class="rounded-md px-4 py-2.5 text-sm font-bold <?php echo !$filter ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'; ?>">All</a>
                <a href="<?php echo EMPLOYEE_URL; ?>contacts?filter=complaints" class="rounded-md px-4 py-2.5 text-sm font-bold <?php echo $filter === 'complaints' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'; ?>">Complaints</a>
                <a href="<?php echo EMPLOYEE_URL; ?>contacts?filter=site_visits" class="rounded-md px-4 py-2.5 text-sm font-bold <?php echo $filter === 'site_visits' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'; ?>">Site Visits</a>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Builder</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enquiries as $inq): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium"><?php echo e($inq['full_name']); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo e($inq['email']); ?></td>
                            <td class="px-4 py-3"><?php echo e($inq['phone']); ?></td>
                            <td class="px-4 py-3"><?php echo e($inq['project_name']); ?></td>
                            <td class="px-4 py-3"><?php echo e($inq['company_name']); ?></td>
                            <td class="px-4 py-3 max-w-[200px] truncate" title="<?php echo e($inq['message'] ?? ''); ?>"><?php echo e($inq['message'] ?: '-'); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $inq['inquiry_status'] === 'New' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'; ?>">
                                    <?php echo e($inq['inquiry_status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($inq['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($enquiries)): ?><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No enquiries yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
