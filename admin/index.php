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
    'Builders' => tableCount('builders'),
    'Projects' => tableCount('projects', 'deleted_at IS NULL'),
    'Leads' => tableCount('inquiries'),
    'Site Visits' => tableCount('site_visit_bookings')
];

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

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 min-h-screen py-8">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase text-accent">Admin</p>
                <h1 class="text-3xl font-black text-primary">Dashboard</h1>
            </div>
            <a href="<?php echo ADMIN_URL; ?>logout" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Logout</a>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php foreach ($stats as $label => $value): ?>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-2xl font-black text-gray-950"><?php echo (int)$value; ?></p>
                    <p class="text-sm text-gray-500"><?php echo e($label); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h2 class="text-xl font-black text-primary">Project Moderation</h2>
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
                <h2 class="text-xl font-black text-primary">Latest Leads</h2>
                <div class="mt-4 space-y-3">
                    <?php foreach ($leads as $lead): ?>
                        <div class="rounded-md bg-gray-50 p-4">
                            <p class="font-bold text-gray-950"><?php echo e($lead['full_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo e($lead['project_name']); ?></p>
                            <p class="mt-2 text-sm font-semibold text-accent"><?php echo e($lead['phone']); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($leads)): ?>
                        <p class="text-sm text-gray-500">No leads yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
