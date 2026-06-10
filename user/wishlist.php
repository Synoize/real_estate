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

<section class="mt-20 py-6 md:py-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
                    <i class="fa-solid fa-heart"></i> Wishlist
                </p>
                <h1 class="text-primary text-3xl md:text-4xl font-semibold leading-tight">Saved Projects</h1>
            </div>
            <a href="<?php echo BASE_URL; ?>projects" class="rounded-md bg-primary px-4 py-3 text-sm text-white">Browse Projects</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="h-[66vh] p-4 flex justify-center flex-col items-center text-center">
                <i class="fa-solid fa-heart mb-4 text-4xl md:text-6xl text-gray-400"></i>
                <h3 class="text-xl font-bold text-gray-900">No saved projects yet</h3>
                <p class="mt-2 text-gray-500 text-sm">Save projects from any projects.</p>
            </div>
        <?php else: ?>
            <div class="mt-8 md:mt-12 grid grid-cols-2 gap-2 md:gap-5 md::grid-cols-3 2xl:grid-cols-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $unitPlans = $projectUnitPlans[(int)$project['id']] ?? [];
                    $saleBadge = projectSaleBadge($project);
                    $primaryVideo = $projectVideos[(int)$project['id']] ?? [];
                    $videoUrl = trim((string)($primaryVideo['video_url'] ?? $project['youtube_video_link'] ?? ''));
                    $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']);
                    ?>
                    <article class="h-full">
                        <div class="relative h-full bg-white rounded-2xl border border-gray-200 p-2 md:p-3 transition duration-300 hover:-translate-y-1 hover:shadow-sm">
                            <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="relative block">
                                <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>"
                                    class="w-full max-h-[220px] object-cover rounded-lg md:rounded-2xl overflow-hidden" />

                                <?php if ($saleBadge): ?>
                                    <span class="absolute top-0.5 md:top-3 left-2 md:left-4 inline-flex items-center gap-2 md:bg-green-600 text-white md:px-3 py-1 md:py-1.5 rounded-lg text-xs">
                                        <span class="w-2 h-2 rounded-full bg-green-600 md:bg-white animate-pulse"></span>
                                        <p class="hidden md:inline"> <?php echo e($saleBadge); ?></p>
                                    </span>
                                <?php endif; ?>

                                <button
                                    type="button"
                                    onclick="event.preventDefault(); window.location.href='<?php echo e($videoUrl ?: BASE_URL . 'project/' . urlencode($project['slug'])); ?>';"
                                    class="absolute bottom-1.5 right-1.5 md:bottom-4 md:right-4 bg-accent w-5 h-5 md:w-10 md:h-10 rounded-full text-white flex items-center justify-center"
                                    aria-label="Play project video">
                                    <i class="fa-solid fa-play text-[8px] md:text-sm"></i>
                                </button>
                            </a>

                            <form method="post" action="<?php echo BASE_URL; ?>actions" class="absolute top-2 md:top-3.5 right-3.5 md:right-5 z-20" data-wishlist-form data-wishlist-project-id="<?php echo (int)$project['id']; ?>">
                                <input type="hidden" name="action" value="wishlist">
                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
                                <button type="submit" class="text-white text-sm md:text-xl drop-shadow" aria-label="<?php echo $savedInWishlist ? 'Saved in wishlist' : 'Add to wishlist'; ?>" data-wishlist-button>
                                    <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
                                </button>
                            </form>

                            <div class="pt-4 p-1">
                                <div class="flex flex-col xl:flex-row xl:justify-between gap-3 md:gap-4">
                                    <div>
                                        <h3 class="text-sm md:text-xl font-semibold text-primary leading-tight">
                                            <?php echo e($project['project_name']); ?>
                                        </h3>

                                        <p class="text-primary mt-2 text-[10px] md:text-sm">
                                            <i class="fa-regular fa-building mr-1"></i>
                                            <span class="underline">
                                                <?php echo e($project['company_name']); ?>
                                            </span>
                                        </p>

                                        <p class="text-primary mt-2 text-[10px] md:text-sm">
                                            <i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>
                                            <span class="underline">
                                                <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'])); ?>
                                            </span>
                                        </p>
                                    </div>

                                    <div class="xl:text-right">
                                        <h4 class="text-xs md:text-base md:text-nowrap font-semibold text-green-500 leading-tight">
                                            <?php echo e(projectPriceRange($project)); ?>
                                        </h4>

                                        <p class="text-gray-500 text-xs">(All inc)</p>
                                        <p class="text-accent mt-2 text-xs"><?php echo e($project['project_type']); ?></p>
                                        <p class="text-primary text-xs md:text-sm mt-1">
                                            <i class="fa-solid fa-expand mr-1"></i>
                                            <?php echo e(projectAreaRange($project, $unitPlans)); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 md:gap-3 mt-4 text-[10px] md:text-xs">
                                    <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex items-center justify-center gap-2 bg-primary text-white py-2 md:py-3 rounded-lg hover:opacity-90 duration-300">
                                        <div class="flex items-center gap-2 hidden md:inline">
                                            <i class="fa-solid fa-laptop"></i>
                                            <span class="text-white">|</span>
                                            <i class="fa-solid fa-car"></i>
                                        </div>
                                        <span>Tour</span>
                                    </a>

                                    <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>" class="flex items-center justify-center gap-2 bg-accent text-white py-2 md:py-3 rounded-lg hover:opacity-90 duration-300">
                                        <i class="fa-brands fa-whatsapp hidden md:inline"></i>
                                        <span>Live Chat</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>