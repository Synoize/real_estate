<?php
require_once __DIR__ . '/../../includes/app_helpers.php';
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$basePath = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
$relativePath = $basePath && strpos($currentPath, $basePath) === 0
    ? trim(substr($currentPath, strlen($basePath)), '/')
    : $currentPath;

$navLinks = [
    ['label' => 'Dashboard', 'url' => EMPLOYEE_URL, 'icon' => 'fa-gauge-high'],
    ['label' => 'Projects', 'url' => EMPLOYEE_URL . 'projects', 'icon' => 'fa-building'],
    ['label' => 'Builders', 'url' => EMPLOYEE_URL . 'builders', 'icon' => 'fa-helmet-safety'],
    ['label' => 'Users', 'url' => EMPLOYEE_URL . 'users', 'icon' => 'fa-user'],
    ['label' => 'Contacts', 'url' => EMPLOYEE_URL . 'contacts', 'icon' => 'fa-message'],
    ['label' => 'Ads', 'url' => EMPLOYEE_URL . 'ads', 'icon' => 'fa-rectangle-ad'],
    ['label' => 'Locations', 'url' => EMPLOYEE_URL . 'locations', 'icon' => 'fa-location-dot'],
    ['label' => 'Logs', 'url' => EMPLOYEE_URL . 'logs', 'icon' => 'fa-clock-rotate-left'],
];

$roleLinks = [
    ['label' => 'User', 'url' => BASE_URL . ''],
    ['label' => 'Admin', 'url' => ADMIN_URL . 'login'],
    ['label' => 'Builder', 'url' => BUILDER_URL . 'login'],
    ['label' => 'Employee', 'url' => EMPLOYEE_URL . 'login'],
    ['label' => 'Manager', 'url' => MANAGER_URL . 'login'],
];

$isActive = static function ($url) use ($relativePath) {
    $page = trim(parse_url($url, PHP_URL_PATH), '/');
    $base = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
    $urlPath = $base ? trim(substr($page, strlen($base)), '/') : $page;
    return $urlPath === $relativePath || $relativePath === rtrim($urlPath, '/');
};

require_once __DIR__ . '/head.php';

?>

<body class="font-sans bg-white text-gray-900">
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <?php
        $alertColors = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            'info' => 'border-sky-200 bg-sky-50 text-sky-700'
        ];
        $alertClass = $alertColors[$flash['type']] ?? $alertColors['info'];
        ?>
        <div id="flashMessage" class="fixed bottom-4 left-4 right-4 md:left-4 md:right-auto z-[9999] md:max-w-lg rounded-xl border px-4 py-3 md:py-2 text-xs shadow-sm transition-all duration-500 ease-out <?php echo $alertClass; ?>">
            <div class="flex items-center gap-3">
                <span class="flex-1"><?php echo e($flash['message']); ?></span>
                <button type="button" data-close-flash class="rounded p-1 hover:bg-black/5">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <script>
            (function(){
                var el = document.getElementById('flashMessage');
                if (!el) return;
                var isMobile = window.innerWidth < 768;
                var start = isMobile ? 'translateY(120%)' : 'translateX(-120%)';
                var end = isMobile ? 'translateY(0)' : 'translateX(0)';
                el.style.transform = start;
                el.style.opacity = '0';
                requestAnimationFrame(function(){
                    el.style.transform = end;
                    el.style.opacity = '1';
                });
                var hide = function(){
                    el.style.transform = start;
                    el.style.opacity = '0';
                    setTimeout(function(){ el.remove(); }, 500);
                };
                el.querySelector('[data-close-flash]')?.addEventListener('click', function(e){
                    e.preventDefault();
                    hide();
                });
                setTimeout(hide, 4000);
            })();
        </script>
    <?php endif; ?>

    <header class="fixed top-0 left-0 w-full z-40 bg-white border-b shadow-sm">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6">
            <div class="h-20 flex items-center justify-between gap-3">

                <!-- LOGO -->
                <a href="<?php echo BASE_URL; ?>" class="shrink-0">
                    <img src="https://i.ibb.co/HfXRT0Wc/housiey-logo.webp" alt="logo"
                        class="h-8 md:h-12 w-auto object-contain">
                </a>

                <!-- RIGHT -->
                <div class="flex items-center gap-4 md:gap-6 shrink-0">

                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">
                            <?= strtoupper(substr($_SESSION['employee_name'] ?? 'E', 0, 1)); ?>
                        </div>

                        <span class="hidden sm:inline"><?php echo e($_SESSION['employee_name'] ?? 'Employee'); ?></span>
                    </div>

                    <!-- MENU BUTTON -->
                    <button id="menuBtn" type="button" class="flex items-center justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 md:w-8 md:h-8 text-gray-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>

                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- OVERLAY -->
    <div id="menuOverlay" class="fixed inset-0 bg-black/40 z-40 opacity-0 invisible transition-all duration-300">
    </div>

    <aside id="mobileMenu" class="fixed top-0 right-[-320px] w-[300px] h-screen bg-white flex flex-col justify-between z-50 transition-all duration-300 shadow-2xl">

        <!-- TOP -->
        <div class="flex items-start justify-between px-3 py-5 border-b h-20">

            <!-- LOGO -->
            <a href="<?php echo BASE_URL; ?>">
                <img src="https://i.ibb.co/HfXRT0Wc/housiey-logo.webp" class="h-8 md:h-10 object-contain" alt="">
            </a>

            <!-- CLOSE -->
            <button id="closeMenu" type="button"
                aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.2">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

        </div>

        <nav class="flex-1 overflow-y-auto my-2">
            <?php foreach (
                array_merge([
                    ['label' => 'Calculator', 'url' => BASE_URL . 'calculator'],
                    ['label' => 'Privacy Policy', 'url' => BASE_URL . 'privacy-policy'],
                    ['label' => 'Terms', 'url' => BASE_URL . 'terms'],
                    ['label' => 'Blogs', 'url' => BASE_URL . 'blogs'],
                    ['label' => 'Careers', 'url' => BASE_URL . 'careers'],
                ]) as $link
            ): ?>
                <a href="<?php echo e($link['url']); ?>" class="block px-6 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-primary">
                    <?php echo e($link['label']); ?>
                </a>
            <?php endforeach; ?>

            <div class="mt-2 border-t pt-4">
                <p class="px-6 pb-2 text-[10px] uppercase tracking-[1.5px] text-gray-400">Role Login</p>
                <?php foreach ($roleLinks as $link): ?>
                    <a href="<?php echo e($link['url']); ?>" class="block px-6 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-primary">
                        <?php echo e($link['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <a href="<?= EMPLOYEE_URL . 'logout'; ?>"
            class="flex h-14 px-6 items-center gap-2 text-sm font-semibold text-red-600 bg-red-100 transition-all duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6" />
            </svg>
            Logout

        </a>

    </aside>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('menuBtn');
            const closeMenu = document.getElementById('closeMenu');
            const mobileMenu = document.getElementById('mobileMenu');
            const menuOverlay = document.getElementById('menuOverlay');
            function slugify(value) {
                return value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            document.querySelectorAll('[data-city-search]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const citySelect = form.querySelector('[data-city-select]');
                    const currentCity = form.dataset.currentCity || '';
                    const selectedCity = citySelect?.value || '';
                    const cityChanged = selectedCity && slugify(currentCity) !== slugify(selectedCity);
                    let hasQueryValue = false;

                    form.querySelectorAll('input, select').forEach((field) => {
                        if (!field.name || field === citySelect) {
                            return;
                        }

                        if (cityChanged && field.name === 'q') {
                            field.disabled = true;
                            return;
                        }

                        if (!field.value || !field.value.trim()) {
                            field.disabled = true;
                        } else {
                            hasQueryValue = true;
                        }
                    });

                    if (!citySelect || !citySelect.value) {
                        citySelect && !citySelect.value && (citySelect.disabled = true);
                        form.action = '<?php echo BASE_URL; ?>';

                        if (!hasQueryValue) {
                            event.preventDefault();
                            window.location.href = form.action;
                        }

                        return;
                    }

                    form.action = '<?php echo BASE_URL; ?>' + slugify(citySelect.value);
                    citySelect.disabled = true;

                    if (!hasQueryValue) {
                        event.preventDefault();
                        window.location.href = form.action;
                    }
                });
            });

            function openMenu() {
                mobileMenu.classList.remove('right-[-320px]');
                mobileMenu.classList.add('right-0');
                menuOverlay.classList.remove('invisible', 'opacity-0');
                menuOverlay.classList.add('opacity-100');
            }

            function closeSidebar() {
                mobileMenu.classList.add('right-[-320px]');
                mobileMenu.classList.remove('right-0');
                menuOverlay.classList.add('invisible', 'opacity-0');
                menuOverlay.classList.remove('opacity-100');
            }

            menuBtn?.addEventListener('click', openMenu);
            closeMenu?.addEventListener('click', closeSidebar);
            menuOverlay?.addEventListener('click', closeSidebar);
            window.lucide?.createIcons();
        });
    </script>

    <main class="grid grid-cols-[240px_1fr]">
        <aside class="sticky top-20 left-0 w-[240px] h-[calc(100vh-5rem)] border-r bg-white overflow-y-auto z-30">
            <nav class="flex flex-col py-4">
                <?php foreach ($navLinks as $link): ?>
                    <a href="<?php echo e($link['url']); ?>"
                        class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors <?php echo $isActive($link['url']) ? 'bg-accent/10 text-accent border-r-2 border-accent' : 'text-gray-600 hover:bg-gray-50 hover:text-primary'; ?>">
                        <i class="fa-solid <?php echo e($link['icon']); ?> w-5 text-center text-[15px]"></i>
                        <?php echo e($link['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

        </aside>