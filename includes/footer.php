</main>
<?php
require_once __DIR__ . '/app_helpers.php';

$footerLocalities = fetchAvailableProjectLocalities(6);
$footerCities = fetchAvailableProjectCities(6);
$footerCityNames = array_map(static function ($city) {
    return $city['city'];
}, $footerCities);

$footerLinks = [
    'Company' => [
        'About us' => BASE_URL . 'about-us',
        'Contact us' => BASE_URL . 'contact-us',
        'Careers' => BASE_URL . 'careers',
        'Blogs' => BASE_URL . 'blogs',
    ],
    'Tools' => [
        'Calculator' => BASE_URL . 'calculator',
        'Privacy Policy' => BASE_URL . 'privacy-policy',
        'Terms' => BASE_URL . 'terms',
        'Disclaimer' => BASE_URL . 'disclaimer',
    ]
];
?>
<footer class="bg-primary text-white">
    <div class="mx-auto max-w-[1920px] px-4 py-12 sm:px-6 lg:px-10">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.25fr_2fr]">
            <div>
                <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-md border-2 border-white text-lg font-black">1H</span>
                    <span>
                        <span class="block text-2xl font-black">1HousingKey</span>
                        <span class="block text-xs font-bold uppercase tracking-[2px] text-white/60">Home Buying Simplified</span>
                    </span>
                </a>
                <p class="mt-5 max-w-md text-sm leading-7 text-white/75">
                    Verified direct-builder projects, no brokerage assistance, online presentations, and free site visit booking.
                </p>
                <div class="mt-5 space-y-2 text-sm text-white/80">
                    <p><i class="fa-solid fa-envelope mr-2 text-accent"></i> support@1housingkey.com</p>
                    <p><i class="fa-solid fa-location-dot mr-2 text-accent"></i> <?php echo e($footerCityNames ? implode(', ', $footerCityNames) : 'Verified project cities'); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="col-span-2">

                    <h3 class="text-base font-black">
                        Top Localities
                    </h3>

                    <ul class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3">

                        <?php foreach ($footerLocalities as $locality): ?>

                            <li>

                                <a
                                    href="<?php echo e(cityUrl($locality['city'], ['q' => $locality['locality'], '_locality_path' => true])); ?>"
                                    class="text-sm text-white/70 hover:text-white transition-colors duration-300">

                                    <?php echo e($locality['locality']); ?> Projects

                                </a>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

                <?php foreach ($footerLinks as $heading => $links): ?>
                    <div>
                        <h3 class="text-base font-black"><?php echo e($heading); ?></h3>
                        <ul class="mt-4 space-y-3">
                            <?php foreach ($links as $label => $url): ?>
                                <li><a href="<?php echo e($url); ?>" class="text-sm text-white/70 hover:text-white"><?php echo e($label); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-sm text-white/60 md:flex-row md:items-center md:justify-between">
            <p>&copy; <?php echo date('Y'); ?> 1HousingKey. All rights reserved.</p>
            <div class="flex gap-4">
                <a href="<?php echo BASE_URL; ?>terms" class="hover:text-white">Terms</a>
                <a href="<?php echo BASE_URL; ?>privacy-policy" class="hover:text-white">Privacy</a>
                <a href="<?php echo BASE_URL; ?>contact-us" class="hover:text-white">Contact</a>
            </div>
        </div>
    </div>
</footer>
<script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>

</html>