<?php
require_once __DIR__ . '/app_helpers.php';
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$basePath = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
$relativePath = $basePath && strpos($currentPath, $basePath) === 0
    ? trim(substr($currentPath, strlen($basePath)), '/')
    : $currentPath;

$navLinks = [
    ['label' => 'Home', 'url' => BASE_URL],
    ['label' => 'My Profile', 'url' => BASE_URL . 'profile'],
    ['label' => 'Projects', 'url' => BASE_URL . 'projects'],
    ['label' => 'About Us', 'url' => BASE_URL . 'about-us'],
    ['label' => 'Contact Us', 'url' => BASE_URL . 'contact-us'],
    ['label' => 'Calculator', 'url' => BASE_URL . 'calculator'],
    ['label' => 'Privacy Policy', 'url' => BASE_URL . 'privacy-policy'],
    ['label' => 'Terms & Condition', 'url' => BASE_URL . 'terms'],
    ['label' => 'Blogs', 'url' => BASE_URL . 'blogs'],
    ['label' => 'Careers', 'url' => BASE_URL . 'careers'],
];

$availableCities = fetchAvailableProjectCities();
$currentSearchCity = $filters['city'] ?? '';
$currentSearchQuery = $filters['q'] ?? '';

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

                <form
                    id="desktopSearchBar"
                    action="<?php echo BASE_URL; ?>"
                    method="get"
                    data-city-search
                    data-current-city="<?php echo e($currentSearchCity); ?>"
                    class="w-[60%] hidden md:flex items-center gap-4
    opacity-0 pointer-events-none -translate-y-5
    transition-all duration-300">

                    <!-- LOCATION DROPDOWN -->
                    <div class="relative hidden lg:block">

                        <!-- BUTTON -->
                        <button
                            type="button"
                            id="locationBtn"
                            class="flex items-center gap-2 h-12 px-4 rounded-lg bg-gray-100 text-primary text-sm font-medium">

                            <!-- LOCATION ICON -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />

                            </svg>

                            <span id="selectedLocation" class="min-w-[60px] text-start">
                                <?php echo $currentSearchCity ?: 'Location'; ?>
                            </span>

                            <!-- ARROW -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                id="arrowIcon"
                                class="ml-3 w-4 h-4 transition-transform duration-300"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 15l7-7 7 7" />

                            </svg>

                        </button>

                        <!-- HIDDEN SELECT -->
                        <select
                            name="city"
                            id="citySelect"
                            data-city-select
                            class="hidden">

                            <option value="">All cities</option>

                            <?php foreach ($availableCities as $city): ?>
                                <option
                                    value="<?php echo e($city['city']); ?>"
                                    <?php echo $currentSearchCity === $city['city'] ? 'selected' : ''; ?>>
                                    <?php echo e($city['city']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <!-- DROPDOWN -->
                        <div
                            id="locationDropdown"
                            class="absolute left-0 top-14 min-w-[180px] bg-white rounded-lg shadow-sm border border-gray-100
    scale-y-0 opacity-0 origin-top transition-all duration-300 z-50
    max-h-[260px] overflow-y-auto custom-scroll">

                            <ul class="py-1 text-xs text-gray-700">
                                <li>
                                    <button
                                        type="button"
                                        data-city=""
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-50 transition">
                                        All Cities
                                    </button>
                                </li>

                                <?php foreach ($availableCities as $city): ?>
                                    <li>
                                        <button
                                            type="button"
                                            data-city="<?php echo e($city['city']); ?>"
                                            class="locationOption w-full text-left px-4 py-2 hover:bg-gray-50 transition">

                                            <?php echo e($city['city']); ?>

                                        </button>
                                    </li>
                                <?php endforeach; ?>

                            </ul>

                        </div>

                    </div>

                    <!-- SEARCH BOX -->
                    <div
                        class="group flex items-center w-full h-12 rounded-lg overflow-hidden border border-transparent bg-gray-100 focus-within:border-gray-200 transition-all duration-300">

                        <input
                            type="text"
                            name="q"
                            value="<?php echo e($currentSearchQuery); ?>"
                            placeholder="Search for Project, locality or builder"
                            class="flex-1 h-full px-4 bg-transparent outline-none text-sm text-primary placeholder:text-gray-500">

                        <button
                            type="submit"
                            class="w-12 h-full flex items-center justify-center text-gray-400 hover:text-gray-500 transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <circle cx="11" cy="11" r="7" />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20 20l-3.5-3.5" />

                            </svg>

                        </button>

                    </div>

                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const desktopSearchBar = document.getElementById('desktopSearchBar');

                        window.addEventListener('scroll', () => {

                            if (window.scrollY > 580) {

                                desktopSearchBar.classList.remove(
                                    'opacity-0',
                                    'pointer-events-none',
                                    '-translate-y-5'
                                );

                                desktopSearchBar.classList.add(
                                    'opacity-100',
                                    'translate-y-0'
                                );

                            } else {

                                desktopSearchBar.classList.add(
                                    'opacity-0',
                                    'pointer-events-none',
                                    '-translate-y-5'
                                );

                                desktopSearchBar.classList.remove(
                                    'opacity-100',
                                    'translate-y-0'
                                );
                            }

                        });


                        const btn = document.getElementById('locationBtn');
                        const dropdown = document.getElementById('locationDropdown');
                        const arrow = document.getElementById('arrowIcon');
                        const selectedLocation = document.getElementById('selectedLocation');
                        const citySelect = document.getElementById('citySelect');
                        const options = document.querySelectorAll('.locationOption');
                        const slugify = (value) => value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

                        if (!btn) return;

                        btn.addEventListener('click', (e) => {

                            e.stopPropagation();

                            dropdown.classList.toggle('scale-y-0');
                            dropdown.classList.toggle('opacity-0');

                            dropdown.classList.toggle('scale-y-100');
                            dropdown.classList.toggle('opacity-100');

                            arrow.classList.toggle('rotate-180');
                        });

                        options.forEach(option => {

                            option.addEventListener('click', () => {

                                const city = option.dataset.city;
                                const searchInput = citySelect.closest('form').querySelector('input[name="q"]');
                                const currentCity = citySelect.closest('form').dataset.currentCity || '';
                                const cityChanged = city && slugify(currentCity) !== slugify(city);

                                selectedLocation.textContent =
                                    city === '' ? 'Location' : city;

                                citySelect.value = city;

                                if ((currentCity && !city) || cityChanged) {
                                    searchInput.value = '';
                                }

                                dropdown.classList.add('scale-y-0', 'opacity-0');
                                dropdown.classList.remove('scale-y-100', 'opacity-100');

                                arrow.classList.remove('rotate-180');

                                // Auto submit form
                                citySelect.closest('form').submit();
                            });

                        });

                        document.addEventListener('click', (e) => {

                            if (!btn.contains(e.target) &&
                                !dropdown.contains(e.target)) {

                                dropdown.classList.add('scale-y-0', 'opacity-0');
                                dropdown.classList.remove('scale-y-100', 'opacity-100');

                                arrow.classList.remove('rotate-180');
                            }

                        });

                    });
                </script>
                <script>
                    function toggleNotifications() {
                        var panel = document.getElementById('notifPanel');
                        if (!panel) return;
                        panel.classList.toggle('hidden');
                    }
                    document.addEventListener('click', function(e) {
                        var dd = document.getElementById('notifDropdown');
                        var panel = document.getElementById('notifPanel');
                        if (dd && panel && !dd.contains(e.target) && !panel.classList.contains('hidden')) {
                            panel.classList.add('hidden');
                        }
                    });

                    function markRead(id) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '<?php echo BASE_URL; ?>user/notifications', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status === 200) location.reload();
                        };
                        xhr.send('action=mark_read&id=' + id);
                    }

                    function markAllRead() {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '<?php echo BASE_URL; ?>user/notifications', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (xhr.status === 200) location.reload();
                        };
                        xhr.send('action=mark_all_read');
                    }
                </script>

                <!-- RIGHT -->
                <div class="flex items-center gap-4 md:gap-6 shrink-0">
                    <!-- HEART -->
                    <?php if (getWishlistCount() > 0): ?>
                        <a href="<?= BASE_URL . (isLoggedIn() ? 'wishlist' : 'login'); ?>"
                            class="relative flex items-center justify-center group">

                            <!-- ICON -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6 text-gray-500 transition-all duration-300 group-hover:scale-110 group-hover:text-red-500"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">

                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21s-6.716-4.35-9.193-8.036
            C.33 9.278 1.09 5.09 4.636 3.636
            7.09 2.636 9.636 3.5 12 6
            c2.364-2.5 4.91-3.364 7.364-2.364
            3.545 1.454 4.306 5.642 1.829 9.328
            C18.716 16.65 12 21 12 21z" />
                            </svg>

                            <!-- COUNT -->
                            <span data-wishlist-count
                                class="absolute -top-1 -right-1 min-w-[18px] h-[18px]
            px-1 rounded-full bg-red-500 text-white text-[10px]
            font-semibold flex items-center justify-center
            leading-none shadow-md border border-white">
                                <?= getWishlistCount(); ?>
                            </span>


                        </a>
                    <?php endif; ?>

                    <?php if (isLoggedIn()): ?>
                        <?php $notifCount = unreadNotificationCount((int)$_SESSION['user_id']); ?>
                        <div class="relative" id="notifDropdown">
                            <button type="button" onclick="toggleNotifications()" class="relative flex items-center justify-center group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500 transition-all duration-300 group-hover:scale-110 group-hover:text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <?php if ($notifCount > 0): ?>
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-amber-500 text-white text-[10px] font-semibold flex items-center justify-center leading-none shadow-md border border-white"><?php echo $notifCount; ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notifPanel" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl border border-gray-200 shadow-lg z-50 hidden">
                                <div class="p-3 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-900">Notifications</span>
                                    <?php if ($notifCount > 0): ?>
                                        <button type="button" onclick="markAllRead()" class="text-xs text-amber-600 hover:underline">Mark all read</button>
                                    <?php endif; ?>
                                </div>
                                <div class="max-h-80 overflow-y-auto" id="notifList">
                                    <?php $notifs = fetchNotifications((int)$_SESSION['user_id'], 8); ?>
                                    <?php if (empty($notifs)): ?>
                                        <div class="p-6 text-center text-gray-400 text-sm">No notifications yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($notifs as $n): ?>
                                            <a href="<?php echo e($n['link'] ?? '#'); ?>" onclick="<?php echo $n['is_read'] ? '' : "markRead({$n['id']});"; ?>" class="flex gap-3 px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition <?php echo $n['is_read'] ? '' : 'bg-amber-50/50'; ?>">
                                                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm <?php echo $n['type'] === 'site_visit' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'; ?>">
                                                    <i class="fa-solid <?php echo $n['type'] === 'site_visit' ? 'fa-calendar-check' : 'fa-message'; ?>"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo e($n['title']); ?></p>
                                                    <p class="text-xs text-gray-500 truncate"><?php echo e($n['message']); ?></p>
                                                    <p class="text-[10px] text-gray-400 mt-0.5"><?php echo date('d M h:i A', strtotime($n['created_at'])); ?></p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo BASE_URL; ?>user/notifications" class="block p-3 text-center text-xs text-amber-600 hover:bg-gray-50 rounded-b-xl font-medium">View all notifications</a>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 cursor-default">
                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </div>

                            <span class="hidden sm:inline">My Account</span>
                        </div>

                    <?php else: ?>
                        <a href="<?php echo BASE_URL . 'login'; ?>" class="flex items-center justify-center gap-2 h-10 font-medium text-sm whitespace-nowrap px-4 bg-green-100 text-green-600 rounded-md">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 20 20">
                                <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
                            </svg>

                            <span>Sign In</span>
                        </a>
                    <?php endif; ?>

                    <!-- MENU BUTTON -->
                    <button id="menuBtn" type="button" class="hidden md:flex items-center justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-600" fill="none"
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

    <aside id="mobileMenu" class="fixed top-0 right-[-320px] w-[280px] h-screen bg-white flex flex-col justify-between z-[999] transition-all duration-300 shadow-2xl">

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
            <?php foreach ($navLinks as $link): ?>
                <a href="<?php echo e($link['url']); ?>" class="block px-6 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-primary">
                    <?php echo e($link['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <a href="<?= BASE_URL . (isLoggedIn() ? 'logout' : 'login'); ?>"
            class="flex h-14 px-6 items-center gap-2 text-sm font-semibold transition-all duration-300
    <?= isLoggedIn()
        ? 'text-red-600 bg-red-100'
        : 'bg-green-100 text-green-700'; ?>">

            <?php if (isLoggedIn()): ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6" />
                </svg>
                Logout
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="currentColor"
                    viewBox="0 0 24 24">
                    <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
                </svg>
                Sign In
            <?php endif; ?>

        </a>

    </aside>

    <!-- MOBILE BOTTOM NAV -->
    <div class="fixed bottom-0 left-0 right-0 z-[888] md:hidden">
        <div class="bg-primary h-[75px] flex items-center relative rounded-t-3xl px-4">

            <!-- Left Side -->
            <div class="flex w-1/2 justify-around pr-8">

                <!-- Home -->
                <a href="<?php echo BASE_URL; ?>"" class=" flex flex-col items-center gap-1 text-white text-[12px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 10.5L12 3l9 7.5M5.25 9.75V21h13.5V9.75" />
                    </svg>
                    <span>Home</span>
                </a>

                <!-- Launches -->
                <a href="<?php echo BASE_URL . 'projects'; ?>" target="_blank" class="flex flex-col items-center gap-1 text-white text-[12px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 21h18M5 21V7a2 2 0 012-2h3v16M14 21V3a2 2 0 012-2h3v20M9 9h1m-1 4h1m-1 4h1m7-8h1m-1 4h1m-1 4h1" />
                    </svg>
                    <span>Launches</span>
                </a>
            </div>

            <!-- Center Circle -->
            <a href="<?php echo BASE_URL; ?>"
                class="absolute left-1/2 -translate-x-1/2 -top-5 z-10">

                <div class="relative flex items-center justify-center">

                    <!-- Ripple Wave -->
                    <span class="absolute inline-flex h-full w-full rounded-full bg-accent opacity-30 animate-ping"></span>

                    <!-- Main Circle -->
                    <div class="relative w-[64px] h-[64px] rounded-full border-4 border-accent bg-primary/95 overflow-hidden">
                        <span class="text-white text-[11px] text-center leading-tight bg-primary animate-pulse w-full h-full flex items-center justify-center ">
                            Online<br>Visit
                        </span>
                    </div>

                </div>
            </a>

            <!-- Right Side -->
            <div class="flex w-1/2 justify-around pl-8">

                <!-- Profile -->
                <a href="<?php echo BASE_URL . 'profile'; ?>" class="flex flex-col items-center gap-1 text-white text-[12px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.5 20.25a7.5 7.5 0 0115 0" />
                    </svg>
                    <span>Profile</span>
                </a>

                <!-- Menu -->
                <button id="menuBtnMobile" type="button" class="flex flex-col items-center gap-1 text-white text-[12px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span>Menu</span>
                </button>

            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('menuBtn');
            const closeMenu = document.getElementById('closeMenu');
            const mobileMenu = document.getElementById('mobileMenu');
            const menuOverlay = document.getElementById('menuOverlay');
            const flashClose = document.querySelector('[data-close-flash]');

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
            document.getElementById('menuBtnMobile')?.addEventListener('click', openMenu);
            closeMenu?.addEventListener('click', closeSidebar);
            menuOverlay?.addEventListener('click', closeSidebar);
            window.lucide?.createIcons();
        });
    </script>

    <main>