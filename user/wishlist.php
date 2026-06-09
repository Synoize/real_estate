<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireLogin();

$pageTitle = 'Saved Projects';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND project_id = ?');
    $stmt->execute([$_SESSION['user_id'], (int)($_POST['project_id'] ?? 0)]);
    setFlash('Project removed from wishlist.', 'success');
    redirect(BASE_URL . 'wishlist');
}

$stmt = $pdo->prepare("
    SELECT p.*, b.company_name, b.phone AS builder_phone, b.whatsapp_number
    FROM wishlist w
    INNER JOIN projects p ON p.id = w.project_id
    INNER JOIN builders b ON b.id = p.builder_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 py-10 min-h-screen">
    <div class="mx-auto max-w-[1200px] px-4 sm:px-6 lg:px-10">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-black uppercase text-accent">Wishlist</p>
                <h1 class="text-3xl font-black text-primary">Saved Projects</h1>
            </div>
            <a href="<?php echo BASE_URL; ?>" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Browse Projects</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="mt-8 rounded-lg border border-gray-200 bg-white p-8 text-center">
                <h2 class="text-xl font-black text-gray-900">No saved projects yet</h2>
                <p class="mt-2 text-gray-500">Save projects from any project detail page.</p>
            </div>
        <?php else: ?>
            <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($projects as $project): ?>
                    <article class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>" class="h-48 w-full object-cover">
                        <div class="p-5">
                            <p class="text-xs font-black uppercase text-accent"><?php echo e($project['company_name']); ?></p>
                            <h2 class="mt-1 text-lg font-black text-gray-950"><?php echo e($project['project_name']); ?></h2>
                            <p class="mt-2 text-sm text-gray-500"><?php echo e($project['locality'] ? $project['locality'] . ', ' . $project['city'] : $project['city']); ?></p>
                            <p class="mt-3 font-black text-primary"><?php echo e(projectPriceRange($project)); ?></p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="rounded-md bg-primary px-3 py-3 text-center text-sm font-bold text-white">View</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                    <button class="h-full w-full rounded-md border border-gray-200 text-sm font-bold text-gray-600">Remove</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
