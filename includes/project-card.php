<?php
$projectCardWrapperClass = $projectCardWrapperClass ?? '';
$projectCardImageClass = $projectCardImageClass ?? 'w-full h-[220px] object-cover rounded-2xl overflow-hidden';
$unitPlans = $unitPlans ?? ($projectUnitPlans[(int)$project['id']] ?? []);
$saleBadge = projectSaleBadge($project);
$primaryVideo = $projectVideos[(int)$project['id']] ?? [];
$videoUrl = trim((string)($primaryVideo['video_url'] ?? $project['youtube_video_link'] ?? ''));
$savedInWishlist = isLoggedIn() && isInWishlist((int)$project['id']);
?>
<article class="<?php echo e($projectCardWrapperClass); ?>">
    <div class="relative h-full bg-white rounded-2xl border border-gray-200 p-3 md:p-4 transition duration-300 hover:-translate-y-1 hover:shadow-sm">
        <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="relative block">
            <img src="<?php echo e(projectImage($project)); ?>" alt="<?php echo e($project['project_name']); ?>"
                class="<?php echo e($projectCardImageClass); ?>" />

            <?php if ($saleBadge): ?>
                <span class="absolute top-0 left-4 inline-flex items-center gap-2 mt-3 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    <?php echo e($saleBadge); ?>
                </span>
            <?php endif; ?>

            <button
                type="button"
                onclick="event.preventDefault(); window.location.href='<?php echo e($videoUrl ?: BASE_URL . 'project/' . urlencode($project['slug'])); ?>';"
                class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center"
                aria-label="Play project video">
                <i class="fa-solid fa-play"></i>
            </button>
        </a>

        <form method="post" action="<?php echo BASE_URL; ?>actions" class="absolute top-2 right-4 z-20" data-wishlist-form>
            <input type="hidden" name="action" value="wishlist">
            <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
            <input type="hidden" name="redirect_to" value="<?php echo e(getCurrentPageUrl()); ?>">
            <button type="submit" class="text-white text-xl md:text-2xl drop-shadow" aria-label="<?php echo $savedInWishlist ? 'Saved in wishlist' : 'Add to wishlist'; ?>" data-wishlist-button>
                <i class="<?php echo $savedInWishlist ? 'fa-solid text-red-500' : 'fa-regular'; ?> fa-heart" data-wishlist-icon></i>
            </button>
        </form>

        <div class="pt-4 p-1">
            <div class="flex flex-col xl:flex-row xl:justify-between gap-4">
                <div>
                    <h3 class="text-lg md:text-xl font-semibold text-primary leading-tight">
                        <?php echo e($project['project_name']); ?>
                    </h3>

                    <p class="text-primary mt-2 text-xs md:text-sm">
                        <i class="fa-regular fa-building mr-1"></i>
                        <span class="underline">
                            <?php echo e($project['company_name']); ?>
                        </span>
                    </p>

                    <p class="text-primary mt-2 text-xs md:text-sm">
                        <i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>
                        <span class="underline">
                            <?php echo e(trim(($project['locality'] ? $project['locality'] . ', ' : '') . $project['city'])); ?>
                        </span>
                    </p>
                </div>

                <div class="xl:text-right">
                    <h4 class="text-base text-nowrap font-semibold text-green-500 leading-tight">
                        <?php echo e(projectPriceRange($project)); ?>
                    </h4>

                    <p class="text-gray-500 text-xs">(All inc)</p>

                    <p class="text-xs text-accent mt-2"><?php echo e($project['project_type']); ?></p>
                    <p class="text-primary text-sm mt-1">
                        <i class="fa-solid fa-expand mr-1"></i>
                        <?php echo e(projectAreaRange($project, $unitPlans)); ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($unitPlans)): ?>
                <div class="mt-5 border border-gray-300 rounded-lg overflow-hidden">
                    <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                        <?php foreach ($unitPlans as $index => $plan): ?>
                            <?php
                            $planTitle = trim((string)($plan['bhk_type'] ?: $plan['unit_name'] ?: $project['project_type']));
                            $planArea = trim((string)($plan['area'] ?: $project['total_area']));
                            $planPrice = (float)($plan['price'] ?? 0);
                            ?>
                            <div class="flex justify-between items-center gap-3 px-4 py-3 bg-primary-50 <?php echo $index < count($unitPlans) - 1 ? 'border-b border-gray-200' : ''; ?> text-primary font-semibold text-xs">
                                <span><?php echo e($planTitle); ?></span>
                                <span><?php echo e($planArea); ?></span>
                                <span><?php echo e($planPrice > 0 ? formatCurrency($planPrice) : projectPriceRange($project)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <a href="<?php echo BASE_URL . 'project/' . urlencode($project['slug']); ?>" class="flex items-center justify-center gap-2 bg-primary text-white py-3 rounded-lg text-sm hover:opacity-90 duration-300">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-laptop text-xs"></i>
                        <span class="text-white">|</span>
                        <i class="fa-solid fa-car text-xs"></i>
                    </div>
                    <span>Tour</span>
                </a>

                <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $project['whatsapp_number'] ?: $project['builder_phone']); ?>?text=<?php echo urlencode('I am interested in ' . $project['project_name']); ?>" class="flex items-center justify-center gap-2 bg-accent text-white py-3 rounded-lg text-sm hover:opacity-90 duration-300">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Live Chat</span>
                </a>
            </div>
        </div>
    </div>
</article>
