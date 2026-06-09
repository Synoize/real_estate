<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireManager();

$pageTitle = 'Manager Dashboard';
$managerId = (int)$_SESSION['manager_id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_inquiry') {
        $stmt = $pdo->prepare("
            UPDATE inquiries
            SET inquiry_status = :status
            WHERE id = :id AND assigned_manager_id = :manager_id
        ");
        $stmt->execute([
            ':status' => $_POST['status'] ?? 'Contacted',
            ':id' => (int)($_POST['inquiry_id'] ?? 0),
            ':manager_id' => $managerId
        ]);
        setFlash('Lead status updated.', 'success');
        redirect(MANAGER_URL);
    }

    if ($action === 'update_visit') {
        $stmt = $pdo->prepare("
            UPDATE site_visit_bookings
            SET status = :status
            WHERE id = :id AND assigned_manager_id = :manager_id
        ");
        $stmt->execute([
            ':status' => $_POST['status'] ?? 'Contacted',
            ':id' => (int)($_POST['booking_id'] ?? 0),
            ':manager_id' => $managerId
        ]);
        setFlash('Visit status updated.', 'success');
        redirect(MANAGER_URL);
    }
}

$projects = $pdo->prepare('SELECT * FROM projects WHERE assigned_manager_id = ? ORDER BY created_at DESC');
$projects->execute([$managerId]);
$projects = $projects->fetchAll();

$leads = $pdo->prepare("
    SELECT i.*, p.project_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    WHERE i.assigned_manager_id = ?
    ORDER BY i.created_at DESC
");
$leads->execute([$managerId]);
$leads = $leads->fetchAll();

$visits = $pdo->prepare("
    SELECT v.*, p.project_name
    FROM site_visit_bookings v
    INNER JOIN projects p ON p.id = v.project_id
    WHERE v.assigned_manager_id = ?
    ORDER BY v.visit_date ASC, v.created_at DESC
");
$visits->execute([$managerId]);
$visits = $visits->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 min-h-screen py-8">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase text-accent">Associate Manager</p>
                <h1 class="text-3xl font-black text-primary"><?php echo e($_SESSION['manager_name'] ?? 'Manager'); ?></h1>
            </div>
            <a href="<?php echo MANAGER_URL; ?>logout" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Logout</a>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($projects); ?></p><p class="text-sm text-gray-500">Assigned projects</p></div>
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($leads); ?></p><p class="text-sm text-gray-500">Leads</p></div>
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($visits); ?></p><p class="text-sm text-gray-500">Site visits</p></div>
        </div>

        <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-xl font-black text-primary">Leads</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($leads as $lead): ?>
                        <div class="rounded-md border p-4">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="font-black"><?php echo e($lead['full_name']); ?> - <?php echo e($lead['phone']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo e($lead['project_name']); ?>, <?php echo e($lead['inquiry_status']); ?></p>
                                </div>
                                <form method="post" class="flex gap-2">
                                    <input type="hidden" name="action" value="update_inquiry">
                                    <input type="hidden" name="inquiry_id" value="<?php echo (int)$lead['id']; ?>">
                                    <select name="status" class="h-10 rounded-md border px-2 text-sm">
                                        <?php foreach (['New', 'Contacted', 'Qualified', 'Site Visit Planned', 'Booked', 'Lost'] as $status): ?>
                                            <option <?php echo $lead['inquiry_status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="rounded-md bg-primary px-3 text-sm font-bold text-white">Save</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($leads)): ?><p class="text-sm text-gray-500">No assigned leads yet.</p><?php endif; ?>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-xl font-black text-primary">Site Visits</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($visits as $visit): ?>
                        <div class="rounded-md border p-4">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="font-black"><?php echo e($visit['full_name']); ?> - <?php echo e($visit['phone']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo e($visit['project_name']); ?> on <?php echo e($visit['visit_date']); ?></p>
                                </div>
                                <form method="post" class="flex gap-2">
                                    <input type="hidden" name="action" value="update_visit">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$visit['id']; ?>">
                                    <select name="status" class="h-10 rounded-md border px-2 text-sm">
                                        <?php foreach (['Pending', 'Contacted', 'Scheduled', 'Visited', 'Successful', 'Cancelled'] as $status): ?>
                                            <option <?php echo $visit['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="rounded-md bg-primary px-3 text-sm font-bold text-white">Save</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($visits)): ?><p class="text-sm text-gray-500">No visits scheduled.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
