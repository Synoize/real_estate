<?php
// Ensure session and database are loaded
require_once __DIR__ . '/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>1HousingKey</title>
    <link rel="icon" href="<?= ASSETS_URL; ?>/public/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">


    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- SWIPER JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- SWIPER CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#2B1C5A',
                            50: '#F3F2F8',
                            100: '#E2E0EE',
                            200: '#C5C2DD',
                            300: '#A39EC8',
                            400: '#8179B2',
                            500: '#2D2857',
                            600: '#26214A',
                            700: '#1E1A3C',
                            800: '#17132E',
                            900: '#0E0B1D',
                        },

                        accent: {
                            DEFAULT: '#EC4B02',
                            50: '#FFF4F1',
                            100: '#FFE4DC',
                            200: '#FFC7B8',
                            300: '#FFA58C',
                            400: '#FF8360',
                            500: '#E84C23',
                            600: '#C53F1D',
                            700: '#9B3217',
                            800: '#712411',
                            900: '#47170B',
                        },

                        gold: {
                            DEFAULT: '#6C8FA3', // Muted steel gold
                            500: '#6C8FA3',
                            600: '#567284',
                        },

                        neutral: {
                            light: '#F8FAFC', // Background light
                            dark: '#000000', // Dark text tone from logo
                        }
                    },

                    fontFamily: {
                        sans: ['Open Sans'],
                        luckiest: ['Luckiest Guy', 'cursive'],
                    },
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="font-sans">

    <!-- Flash Messages -->
    <?php $flash = getFlash();
    if ($flash): ?>
        <?php
        $alertColors = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'danger'  => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            'info'    => 'border-sky-200 bg-sky-50 text-sky-700'
        ];

        $alertIcons = [
            'success' => 'fa-circle-check',
            'danger'  => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            'info'    => 'fa-circle-info'
        ];

        $alertClass = $alertColors[$flash['type']] ?? $alertColors['info'];
        $alertIcon  = $alertIcons[$flash['type']] ?? $alertIcons['info'];
        ?>

        <!-- Flash Wrapper -->
        <div
            id="flashWrapper"
            class="fixed bottom-4 left-0 w-full max-w-md px-4 z-50">

            <!-- Flash Message -->
            <div
                id="flashMessage"
                class="
                flex items-center justify-between gap-3
                rounded-2xl border
                px-4 py-2
                text-sm shadow-xl
                backdrop-blur-md
                transition-all duration-500 ease-out
                opacity-0 translate-y-10
                <?= $alertClass; ?>
            ">

                <!-- Left Content -->
                <div class="flex items-center gap-3 flex-1 min-w-0 text-sm">

                    <!-- Icon -->
                    <div class="shrink-0">
                        <i class="fas <?= $alertIcon; ?> text-base"></i>
                    </div>

                    <!-- Message -->
                    <span class="flex-1 font-medium leading-relaxed break-words">
                        <?= e($flash['message']); ?>
                    </span>

                </div>

                <!-- Close Button -->
                <button
                    id="closeFlash"
                    class="
                    shrink-0 rounded-full
                    p-1.5 text-lg
                    opacity-70 transition
                    hover:bg-black/5 hover:opacity-100
                ">
                    <i class="fas fa-xmark"></i>
                </button>

            </div>

        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {

                const flash = document.getElementById("flashMessage");
                const closeBtn = document.getElementById("closeFlash");

                if (!flash) return;

                // Show animation
                setTimeout(() => {
                    flash.classList.remove("opacity-0", "translate-y-10");
                    flash.classList.add("opacity-100", "translate-y-0");
                }, 100);

                // Auto hide after 5 sec
                setTimeout(() => {
                    hideFlash();
                }, 5000);

                // Manual close
                closeBtn.addEventListener("click", hideFlash);

                function hideFlash() {
                    flash.classList.remove("opacity-100", "translate-y-0");
                    flash.classList.add("opacity-0", "translate-y-10");

                    setTimeout(() => {
                        flash.remove();
                    }, 500);
                }

            });
        </script>

    <?php endif; ?>

    <header class="fixed top-0 left-0 w-full z-40 bg-white border-b shadow-sm">

        <div class="max-w-[1920px] mx-auto px-4 sm:px-6">

            <div class="h-20 flex items-center justify-between gap-3">


                <!-- LOGO -->
                <a href="/" class="shrink-0">

                    <img
                        src="https://i.ibb.co/HfXRT0Wc/housiey-logo.webp"
                        alt="logo"
                        class="h-10 md:h-12 w-auto object-contain">

                </a>


                <div id="desktopSearchBar"
                    class="w-[60%] hidden md:flex items-center gap-4
    opacity-0 pointer-events-none -translate-y-5
    transition-all duration-300">

                    <!-- LOCATION DROPDOWN -->
                    <div class="relative hidden lg:block">

                        <!-- BUTTON -->
                        <button id="locationBtn"
                            class="flex items-center gap-2 h-12 px-4 rounded-lg 
            bg-gray-100 text-primary text-sm font-medium">

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

                            <!-- SELECTED LOCATION -->
                            <span id="selectedLocation">Location</span>

                            <!-- ARROW -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="ml-4 w-4 h-4 transition-transform duration-300"
                                id="arrowIcon"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 15l7-7 7 7" />

                            </svg>

                        </button>

                        <!-- DROPDOWN -->
                        <div id="locationDropdown"
                            class="absolute left-0 top-14 w-40 bg-white rounded-md shadow-md border overflow-hidden scale-y-0 opacity-0 origin-top transition-all duration-300 z-50">

                            <ul class="py-2 text-xs text-gray-800">

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Pune
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Mumbai
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Bangalore
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Ahmedabad
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Hyderabad
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Gurugram
                                    </button>
                                </li>

                                <li>
                                    <button type="button"
                                        class="locationOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                                        Chennai
                                    </button>
                                </li>

                            </ul>

                        </div>

                    </div>

                    <!-- SEARCH -->
                    <div
                        class="group flex items-center w-full h-12 rounded-lg overflow-hidden
  border border-transparent bg-gray-100
  focus-within:border-gray-200 transition-all duration-300">

                        <!-- INPUT -->
                        <input
                            type="text"
                            placeholder="Search for Project, locality or builder"
                            class="flex-1 h-full px-5 bg-transparent
    outline-none text-sm text-primary-900
    placeholder:text-gray-500" />

                        <!-- SEARCH BUTTON -->
                        <button
                            class="w-16 h-full flex items-center justify-center
    text-gray-400">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <circle cx="11" cy="11" r="7" />

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20 20l-3.5-3.5" />

                            </svg>

                        </button>

                    </div>

                </div>

                <script>
                    const desktopSearchBar = document.getElementById('desktopSearchBar');

                    window.addEventListener('scroll', () => {

                        if (window.scrollY > 340) {

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

                    // DROPDOWN FUNCTIONALITY
                    const btn = document.getElementById('locationBtn');
                    const dropdown = document.getElementById('locationDropdown');
                    const arrow = document.getElementById('arrowIcon');
                    const selectedLocation = document.getElementById('selectedLocation');
                    const options = document.querySelectorAll('.locationOption');

                    // TOGGLE DROPDOWN
                    btn.addEventListener('click', (e) => {

                        e.stopPropagation();

                        dropdown.classList.toggle('scale-y-0');
                        dropdown.classList.toggle('opacity-0');

                        dropdown.classList.toggle('scale-y-100');
                        dropdown.classList.toggle('opacity-100');

                        arrow.classList.toggle('rotate-90');

                    });

                    // SELECT LOCATION
                    options.forEach(option => {

                        option.addEventListener('click', () => {

                            selectedLocation.textContent = option.textContent.trim();

                            dropdown.classList.add('scale-y-0', 'opacity-0');
                            dropdown.classList.remove('scale-y-100', 'opacity-100');

                            arrow.classList.remove('rotate-90');

                        });

                    });

                    // CLOSE OUTSIDE CLICK
                    document.addEventListener('click', (e) => {

                        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {

                            dropdown.classList.add('scale-y-0', 'opacity-0');
                            dropdown.classList.remove('scale-y-100', 'opacity-100');

                            arrow.classList.remove('rotate-90');

                        }

                    });
                </script>

                <!-- RIGHT -->
                <div class="flex items-center gap-4 md:gap-6 shrink-0">

                    <!-- HEART -->
                    <button
                        class="relative flex items-center justify-center group">

                        <!-- ICON -->
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6 text-gray-500 transition-all duration-300 group-hover:text-red-500 group-hover:scale-105"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round">

                            <path
                                d="M12 21s-6.716-4.35-9.193-8.036
    C.33 9.278 1.09 5.09 4.636 3.636
    7.09 2.636 9.636 3.5 12 6
    c2.364-2.5 4.91-3.364 7.364-2.364
    3.545 1.454 4.306 5.642 1.829 9.328
    C18.716 16.65 12 21 12 21z" />

                        </svg>

                        <!-- COUNT -->
                        <span
                            class="absolute -top-1 -right-1
        min-w-[18px] h-[18px]
        px-1 rounded-full
        bg-red-500 text-white
        text-[10px] font-semibold
        flex items-center justify-center
        leading-none shadow-md border border-white">
                            4
                        </span>

                    </button>

                    <!-- SIGN IN -->
                    <button class="flex items-center gap-1 md:gap-2 bg-green-100 text-green-600 px-3 md:px-5 h-11 rounded-md font-medium text-xs md:text-sm whitespace-nowrap">

                        <span class="sm:block">
                            Sign in
                        </span>

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4 md:w-5 md:h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20">
                            <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
                        </svg>

                    </button>

                    <!-- MENU BUTTON -->
                    <button id="menuBtn" class="flex items-center justify-center">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-7 h-7 md:w-8 md:h-8 text-gray-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>

                    </button>

                </div>

            </div>

        </div>

    </header>

    <!-- MOBILE MENU -->

    <!-- OVERLAY -->
    <div id="menuOverlay"
        class="fixed inset-0 bg-black/40 z-40 opacity-0 invisible transition-all duration-300">
    </div>

    <!-- SIDEBAR -->
    <div id="mobileMenu"
        class="fixed top-0 right-[-100%] w-[300px] h-screen bg-white flex flex-col justify-between z-50 transition-all duration-300 shadow-2xl">

        <!-- TOP -->
        <div class="flex items-start justify-between px-3 py-5 border-b">

            <!-- LOGO -->
            <img
                src="https://i.ibb.co/HfXRT0Wc/housiey-logo.webp"
                class="h-10 object-contain"
                alt="">

            <!-- CLOSE -->
            <button id="closeMenu"
                class="w-8 h-8 border rounded-md flex items-center justify-center text-gray-500 text-2xl">
                ×
            </button>

        </div>

        <!-- MENU LINKS -->
        <div class="mt-4 flex-1 overflow-y-auto text-sm font-medium text-gray-500
            [&::-webkit-scrollbar]:w-1
            [&::-webkit-scrollbar-thumb]:bg-gray-300
            [&::-webkit-scrollbar-thumb]:rounded-full">

            <div class="flex flex-col pb-6">

                <a href="#" class="px-6 py-3 hover:bg-gray-100">Home</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">About Us</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Calculator</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Contact Us</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Privacy Policy</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Disclaimer</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Blogs</a>
                <a href="#" class="px-6 py-3 hover:bg-gray-100">Careers</a>

            </div>
        </div>

        <!-- SIGN IN -->
        <button class="flex items-center gap-1 md:gap-2 bg-green-100 text-green-600 px-3 md:px-5 h-16 rounded-md font-medium text-sm whitespace-nowrap">

            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 md:w-5 md:h-5"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
            </svg>

            <span class="sm:block">
                Sign in
            </span>

        </button>

    </div>

    <!-- SCRIPT -->
    <script>
        const menuBtn = document.getElementById("menuBtn");
        const mobileMenu = document.getElementById("mobileMenu");
        const menuOverlay = document.getElementById("menuOverlay");
        const closeMenu = document.getElementById("closeMenu");

        menuBtn.addEventListener("click", () => {

            mobileMenu.classList.remove("right-[-100%]");
            mobileMenu.classList.add("right-0");

            menuOverlay.classList.remove("opacity-0", "invisible");
            menuOverlay.classList.add("opacity-100", "visible");

        });

        closeMenu.addEventListener("click", closeSidebar);
        menuOverlay.addEventListener("click", closeSidebar);

        function closeSidebar() {

            mobileMenu.classList.remove("right-0");
            mobileMenu.classList.add("right-[-100%]");

            menuOverlay.classList.remove("opacity-100", "visible");
            menuOverlay.classList.add("opacity-0", "invisible");

        }
    </script>

    <main>