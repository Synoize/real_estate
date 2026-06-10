<?php

require_once __DIR__ . '/includes/app_helpers.php';

$slug = trim($_GET['slug'] ?? '');
$project = $slug !== '' ? fetchProjectBySlug($slug) : null;

if (!$project) {
    setFlash('Project not found or not published.', 'warning');
    redirect(BASE_URL);
}

$pageTitle = $project['project_name'];

$pdo->prepare('UPDATE projects SET total_views = total_views + 1 WHERE id = ?')->execute([$project['id']]);

$amenities = array_filter(array_map('trim', explode(',', (string)($project['amenities'] ?? ''))));

$unitStmt = $pdo->prepare('SELECT * FROM project_unit_plans WHERE project_id = ? ORDER BY price ASC');
$unitStmt->execute([$project['id']]);
$unitPlans = $unitStmt->fetchAll();

$related = fetchPublishedProjects([
    'city' => $project['city'],
    'type' => $project['project_type']
], 4);

require_once __DIR__ . '/includes/header.php';
?>

<section class="mt-20 bg-primary text-white">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <a href="<?php echo BASE_URL; ?>" class="text-sm font-semibold text-white/75 hover:text-white">
            <i class="fa-solid fa-arrow-left"></i> Back to projects
        </a>
        <div class="mt-5 grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-8">
            <div>
                <p class="text-sm font-bold uppercase text-accent"><?php echo e($project['project_type']); ?> by <?php echo e($project['company_name']); ?></p>
                <h1 class="mt-2 text-3xl md:text-5xl font-black leading-tight"><?php echo e($project['project_name']); ?></h1>
                <p class="mt-3 text-white/80">
                    <i class="fa-solid fa-location-dot text-accent"></i>
                    <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'] . ', ' . $project['state'])); ?>
                </p>
            </div>
            <div class="rounded-lg bg-white/10 p-5">
                <p class="text-sm text-white/70">Starting price</p>
                <p class="mt-1 text-3xl font-black text-accent"><?php echo e(projectPriceRange($project)); ?></p>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <a href="#inquiry" class="rounded-md bg-accent px-4 py-3 text-center text-sm font-black text-primary">
                        Contact Builder
                    </a>
                    <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>"
                       class="rounded-md bg-green-600 px-4 py-3 text-center text-sm font-black text-white">
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>" class="h-[320px] md:h-[520px] w-full rounded-lg object-cover">
    </div>
</section>

<section class="bg-gray-50 py-10">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10 grid grid-cols-1 lg:grid-cols-[1fr_390px] gap-8">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-2xl font-black text-primary">Project Overview</h2>
                <p class="mt-4 leading-7 text-gray-600"><?php echo nl2br(e($project['overview'] ?: 'Project details will be updated soon.')); ?></p>
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="rounded-md bg-gray-50 p-4">
                        <p class="text-xs text-gray-500">Status</p>
                        <p class="font-bold text-gray-900"><?php echo e($project['project_status']); ?></p>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <p class="text-xs text-gray-500">Units</p>
                        <p class="font-bold text-gray-900"><?php echo (int)$project['total_units']; ?></p>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <p class="text-xs text-gray-500">Area</p>
                        <p class="font-bold text-gray-900"><?php echo e($project['total_area'] ?: 'NA'); ?></p>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <p class="text-xs text-gray-500">RERA</p>
                        <p class="font-bold text-gray-900"><?php echo e($project['rera_number'] ?: 'NA'); ?></p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-2xl font-black text-primary">Amenities</h2>
                <div class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($amenities ?: ['Verified builder', 'Direct inquiry', 'Free site visit'] as $amenity): ?>
                        <div class="rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">
                            <i class="fa-solid fa-circle-check text-accent"></i>
                            <?php echo e($amenity); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($unitPlans)): ?>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-2xl font-black text-primary">Unit Plans</h2>
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Unit</th>
                                    <th class="px-4 py-3">BHK</th>
                                    <th class="px-4 py-3">Area</th>
                                    <th class="px-4 py-3">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unitPlans as $plan): ?>
                                    <tr class="border-t">
                                        <td class="px-4 py-3 font-bold"><?php echo e($plan['unit_name']); ?></td>
                                        <td class="px-4 py-3"><?php echo e($plan['bhk_type']); ?></td>
                                        <td class="px-4 py-3"><?php echo e($plan['area']); ?></td>
                                        <td class="px-4 py-3"><?php echo e(formatCurrency($plan['price'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside id="inquiry" class="space-y-5">
            <form method="post" action="<?php echo BASE_URL; ?>actions" class="rounded-lg border border-gray-200 bg-white p-5">
                <input type="hidden" name="action" value="inquiry">
                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                <h2 class="text-xl font-black text-primary">Contact Builder</h2>
                <div class="mt-4 space-y-3">
                    <input name="full_name" required placeholder="Full name" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="phone" required placeholder="Mobile number" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="email" required type="email" placeholder="Email address" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="budget" placeholder="Budget" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <textarea name="message" placeholder="Message" class="min-h-24 w-full rounded-md border border-gray-200 px-3 py-3 text-sm"></textarea>
                </div>
                <button class="mt-4 h-12 w-full rounded-md bg-primary font-black text-white">Send Inquiry</button>
            </form>

            <form method="post" action="<?php echo BASE_URL; ?>actions" class="rounded-lg border border-gray-200 bg-white p-5">
                <input type="hidden" name="action" value="site_visit">
                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                <h2 class="text-xl font-black text-primary">Book Free Site Visit</h2>
                <div class="mt-4 space-y-3">
                    <input name="full_name" required placeholder="Full name" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="phone" required placeholder="Mobile number" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="email" required type="email" placeholder="Email address" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="visit_date" required type="date" min="<?php echo date('Y-m-d'); ?>" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                    <input name="preferred_time" placeholder="Preferred time" class="h-12 w-full rounded-md border border-gray-200 px-3 text-sm">
                </div>
                <button class="mt-4 h-12 w-full rounded-md bg-accent font-black text-primary">Schedule Now</button>
            </form>

            <form method="post" action="<?php echo BASE_URL; ?>actions" data-wishlist-form data-wishlist-project-id="<?php echo (int)$project['id']; ?>">
                <?php $savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']); ?>
                <input type="hidden" name="action" value="wishlist">
                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
                <button class="h-12 w-full rounded-md border border-gray-200 bg-white font-bold text-primary" data-wishlist-button>
                    <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
                    <span data-wishlist-label><?php echo $savedInWishlist ? 'Saved Project' : 'Save Project'; ?></span>
                </button>
            </form>
        </aside>
    </div>
</section>

<?php if (!empty($related)): ?>
    <section class="bg-white py-10">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10">
            <h2 class="text-2xl font-black text-primary">Similar Projects</h2>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php foreach ($related as $item): ?>
                    <?php if ((int)$item['id'] === (int)$project['id']) { continue; } ?>
                    <a href="<?php echo BASE_URL . 'project/' . urlencode($item['slug']); ?>" class="rounded-lg border border-gray-200 bg-white p-4 hover:border-accent transition">
                        <img src="<?php echo e(projectImage($item)); ?>" alt="<?php echo e($item['project_name']); ?>" class="h-32 w-full rounded-md object-cover">
                        <p class="mt-3 font-black text-gray-950"><?php echo e($item['project_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo e(projectPriceRange($item)); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
