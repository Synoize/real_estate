<?php

/**
 * Header Template
 * Included on all frontend pages
 */

// Ensure session and database are loaded
require_once __DIR__ . '/db_connect.php';

// Get all categories for navigation
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT id, category_name AS name, category_icon AS image
        FROM project_categories
        ORDER BY category_name ASC
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Categories table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Earthence</title>
    <link rel="icon" href="<?= ASSETS_URL; ?>/public/favicon.ico">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/public/css/styles.css">


    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Luckiest+Guy&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Outfit:wght@100..900&family=PT+Serif:ital,wght@0,400;0,700;1,400;1,700&family=Palanquin+Dark:wght@400;500;600;700&family=Patrick+Hand+SC&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Protest+Revolution&family=Roboto:ital,wght@0,100..900;1,100..900&family=Rubik:ital,wght@0,300..900;1,300..900&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#56B4E2',
                            50: '#f0faff',
                            100: '#d9f3fc',
                            200: '#b7e7f8',
                            300: '#8dd7f1',
                            400: '#6ac6ea',
                            500: '#56B4E2',
                            600: '#3fa4d4',
                            700: '#2f86b3',
                            800: '#256b8f',
                            900: '#163f4d      ',
                        },
                        accent: {
                            DEFAULT: '#FBC02D',
                            50: '#fffde7',
                            100: '#fff9c4',
                            200: '#fff59d',
                            300: '#fff176',
                            400: '#ffee58',
                            500: '#ffeb3b',
                            600: '#fdd835',
                            700: '#fbc02d',
                            800: '#f9a825',
                            900: '#f57f17',
                        },
                        spice: {
                            DEFAULT: '#D84315',
                            500: '#D84315',
                            600: '#BF360C',
                        },
                        neutral: {
                            light: '#F9FBF7',
                            dark: '#1F2937',
                        }
                    },

                    fontFamily: {
                        sans: ['sans-serif'],
                        luckiest: ['Luckiest Guy', 'cursive'],
                    },

                    keyframes: {

                        // Smooth Pop Animation
                        pop: {
                            '0%': {
                                transform: 'scale(0.75)',
                                opacity: '0',
                                filter: 'blur(6px)',
                            },
                            '60%': {
                                transform: 'scale(1.06)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                            '100%': {
                                transform: 'scale(1)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                        },

                        // Floating Animation
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0px)',
                            },
                            '50%': {
                                transform: 'translateY(-14px)',
                            },
                        },

                        // Slide From Left
                        slideLeft: {
                            '0%': {
                                transform: 'translateX(-120px) scale(0.95)',
                                opacity: '0',
                                filter: 'blur(4px)',
                            },
                            '100%': {
                                transform: 'translateX(0) scale(1)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                        },

                        // Slide From Right
                        slideRight: {
                            '0%': {
                                transform: 'translateX(120px) scale(0.95)',
                                opacity: '0',
                                filter: 'blur(4px)',
                            },
                            '100%': {
                                transform: 'translateX(0) scale(1)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                        },

                        // Slide From Top
                        slideTop: {
                            '0%': {
                                transform: 'translateY(-120px) scale(0.95)',
                                opacity: '0',
                                filter: 'blur(4px)',
                            },
                            '100%': {
                                transform: 'translateY(0) scale(1)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                        },

                        // Slide From Bottom
                        slideBottom: {
                            '0%': {
                                transform: 'translateY(120px) scale(0.95)',
                                opacity: '0',
                                filter: 'blur(4px)',
                            },
                            '100%': {
                                transform: 'translateY(0) scale(1)',
                                opacity: '1',
                                filter: 'blur(0px)',
                            },
                        },

                        // Fade In Smooth
                        fadeIn: {
                            '0%': {
                                opacity: '0',
                            },
                            '100%': {
                                opacity: '1',
                            },
                        },

                        // Rotate Soft
                        rotateSoft: {
                            '0%': {
                                transform: 'rotate(-8deg) scale(0.95)',
                                opacity: '0',
                            },
                            '100%': {
                                transform: 'rotate(0deg) scale(1)',
                                opacity: '1',
                            },
                        },

                        // Pulse Glow
                        pulseGlow: {
                            '0%, 100%': {
                                boxShadow: '0 0 0px rgba(255,255,255,0)',
                            },
                            '50%': {
                                boxShadow: '0 0 25px rgba(255,255,255,0.35)',
                            },
                        },
                    },

                    animation: {

                        // Main Animations
                        pop: 'pop 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards',
                        float: 'float 4s ease-in-out infinite',

                        // Sliding Animations
                        'slide-left': 'slideLeft 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards',
                        'slide-right': 'slideRight 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards',
                        'slide-top': 'slideTop 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards',
                        'slide-bottom': 'slideBottom 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards',

                        // Extra Premium Animations
                        fade: 'fadeIn 1s ease forwards',
                        rotate: 'rotateSoft 1s ease-out forwards',
                        glow: 'pulseGlow 3s ease-in-out infinite',

                        // Combined Animation
                        'hero-image': 'pop 1s ease-out forwards, float 4s ease-in-out infinite',
                    },

                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>

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

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-white shadow-sm z-40 md:animate-slide-top">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="md:hidden text-gray-400 hover:text-gray-500 text-xl relative z-50">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>" class="flex items-center">
                    <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20">
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8 text-sm">
                    <a href="<?php echo BASE_URL; ?>" class="text-gray-700 hover:text-primary font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'text-primary-700 hover:hover:text-primary-700' : ''; ?>">
                        Home
                    </a>
                    <a href="<?php echo BASE_URL; ?>shop" class="text-gray-700 hover:text-primary font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'text-primary-700 hover:hover:text-primary-700' : ''; ?>">
                        Shop
                    </a>

                    <!-- Categories Dropdown -->
                    <div class="relative group">

                        <!-- Button -->
                        <button class="text-gray-700 hover:text-primary font-medium flex items-center gap-1">
                            Categories
                            <i class="fas fa-chevron-up text-xs transition-transform duration-300 group-hover:rotate-90"></i>
                        </button>

                        <!-- Dropdown -->
                        <div class="
        absolute left-0 mt-3 w-54
        bg-white rounded-lg border border-gray-100
        
        opacity-0 invisible translate-y-3
        transition-all duration-300 ease-out
        
        group-hover:opacity-100 
        group-hover:visible 
        group-hover:translate-y-0
    ">

                            <div class="py-2">
                                <?php foreach ($categories as $category): ?>
                                    <a
                                        href="<?php echo BASE_URL; ?>shop?category=<?php echo $category['id']; ?>"
                                        class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition">
                                        <?php if ($category['image']): ?>
                                            <img
                                                src="<?php echo getImageUrl($category['image'], 'categories'); ?>"
                                                class="w-8 h-8 mr-3 object-contain">
                                        <?php endif; ?>

                                        <span class="text-sm font-medium hover:text-primary <?php echo (basename($_SERVER['PHP_SELF']) === 'shop.php' && isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'text-primary-700 hover:text-primary-700' : ''; ?>">
                                            <?php echo e($category['name']); ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>

                    <a href="<?php echo BASE_URL; ?>about-us" class="text-gray-700 hover:text-primary font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'about-us.php' ? 'text-primary-700 hover:text-primary-700' : ''; ?>">
                        About Us
                    </a>
                    <a href="<?php echo BASE_URL; ?>contact-us" class="text-gray-700 hover:text-primary font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'contact-us.php' ? 'text-primary-700 hover:text-primary-700' : ''; ?>">
                        Contact Us
                    </a>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-5">
                    <!-- Search -->
                    <form action="<?php echo BASE_URL; ?>shop" method="GET" class="hidden md:flex items-center">

                        <div class="
        flex items-center w-64 h-10
        bg-gray-50 border 
        rounded-full px-2
        focus-within:bg-white
        focus-within:border-primary
        transition-all duration-300
    ">

                            <!-- Input -->
                            <input
                                type="search"
                                name="search"
                                placeholder="Search makhana, spices..."
                                value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>"

                                class="
            flex-1 h-full px-3
            bg-transparent text-sm text-gray-700
            placeholder-gray-400
            outline-none
        ">

                            <!-- Button -->
                            <button
                                type="submit"
                                class="
            h-8 w-8 flex items-center justify-center
            bg-primary text-white rounded-full
            hover:bg-primary-600
            shadow-sm hover:shadow-md
            transition-all duration-200
        ">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </button>

                        </div>

                    </form>

                    <!-- Wishlist -->
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>wishlist"
                            class="relative text-red-500 hover:text-red-400 transition hidden md:block">

                            <i class="fas fa-heart text-xl"></i>

                            <?php
                            $wishlistCount = getWishlistCount();
                            if ($wishlistCount > 0):
                            ?>
                                <span class="
                absolute -top-1.5 -right-1.5 
                text-red-500 bg-white 
                text-[10px] font-semibold 
                rounded-full h-5 min-w-[20px] px-1
                flex items-center justify-center
                shadow">
                                    <?php echo $wishlistCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- User Account -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group hidden md:block">

                            <!-- User Button -->
                            <button class="flex items-center gap-2 text-gray-700 hover:text-primary-600 transition">

                                <img
                                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>&background=3fa4d4&color=fff"
                                    class="w-8 h-8 rounded-full object-cover border border-gray-200">

                                <span class="hidden sm:block text-sm font-medium">
                                    <?php echo e($_SESSION['user_name'] ?? 'User'); ?>
                                </span>

                                <i class="fas fa-chevron-up text-xs transition-transform duration-300 group-hover:rotate-90"></i>
                            </button>

                            <!-- Dropdown -->
                            <div class="
                absolute right-0 mt-3 w-52 
                bg-white rounded-lg border border-gray-100
                
                opacity-0 invisible translate-y-3
                transition-all duration-300 ease-out
                
                group-hover:opacity-100 
                group-hover:visible 
                group-hover:translate-y-0
            ">

                                <div class="py-2 text-sm">

                                    <!-- My Profile -->
                                    <a href="<?php echo BASE_URL; ?>profile"
                                        class="flex items-center px-4 py-2 transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php'
                            ? 'bg-primary-50 text-primary-600 font-medium'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600'; ?>">
                                        <i class="fas fa-user mr-3"></i> My Profile
                                    </a>

                                    <!-- Wishlist -->
                                    <a href="<?php echo BASE_URL; ?>wishlist"
                                        class="relative flex items-center px-4 py-2 transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'wishlist.php'
                            ? 'bg-red-50 text-red-500 font-medium'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-red-500'; ?>">
                                        <i class="fas fa-heart mr-3 text-red-500"></i> Wishlist

                                        <?php
                                        $wishlistCount = getWishlistCount();
                                        if ($wishlistCount > 0):
                                        ?>
                                            <span class="absolute top-1 left-6 text-red-500 bg-white text-[8px] font-semibold rounded-full h-3 min-w-3 px-1 flex items-center justify-center shadow">
                                                <?php echo $wishlistCount; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>

                                    <!-- Checkout -->
                                    <a href="<?php echo BASE_URL; ?>checkout"
                                        class="flex items-center px-4 py-2 transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'checkout.php'
                            ? 'bg-primary-50 text-primary-600 font-medium'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600'; ?>">
                                        <i class="fas fa-shopping-cart mr-3"></i> Checkout
                                    </a>

                                    <!-- Help -->
                                    <a href="<?php echo BASE_URL; ?>help"
                                        class="flex items-center px-4 py-2 transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'help.php'
                            ? 'bg-primary-50 text-primary-600 font-medium'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600'; ?>">
                                        <i class="fas fa-question-circle mr-3"></i> Help & Support
                                    </a>

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <!-- Logout -->
                                    <a href="<?php echo BASE_URL; ?>logout"
                                        class="flex items-center px-4 py-2 text-red-500 hover:bg-red-50 transition">
                                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                                    </a>

                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Login Button -->
                        <a href="<?php echo BASE_URL; ?>login"
                            class="hidden md:block
           bg-primary-500 hover:bg-primary-600 
           text-white px-5 py-2.5 rounded-full 
           text-sm font-medium 
           shadow-sm hover:shadow-md
           transition-all
           "> Register/Login
                        </a>

                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenuOverlay" class="fixed inset-0 bg-black/50 z-40 opacity-0 invisible transition-opacity duration-300 md:hidden"></div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="fixed top-0 left-0 w-72 max-w-[85vw] h-full bg-white shadow-2xl z-50 transform -translate-x-full transition-transform duration-300 ease-out md:hidden">
            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-16">
                <button id="mobileMenuClose" class="text-gray-500 hover:text-gray-700 p-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="px-2 py-4 space-y-1 overflow-y-auto h-[calc(100%-65px)] text-base">

                <!-- Home -->
                <a href="<?php echo BASE_URL; ?>"
                    class="flex items-center p-3 rounded-lg transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'index.php'
            ? 'bg-primary-50 text-primary-700 font-medium'
            : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i class="fas fa-home w-8"></i> Home
                </a>

                <!-- Shop -->
                <a href="<?php echo BASE_URL; ?>shop"
                    class="flex items-center p-3 rounded-lg transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' && !isset($_GET['category'])
            ? 'bg-primary-50 text-primary-700 font-medium'
            : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i class="fas fa-store w-8"></i> Shop
                </a>

                <!-- Mobile Categories -->
                <?php if (!empty($categories)): ?>
                    <div>

                        <button id="mobileCategoriesToggle"
                            class="flex items-center justify-start gap-4 w-full px-1 py-3 rounded-lg transition
                <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' && isset($_GET['category'])
                        ? 'text-primary-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-50'; ?>">

                            <span>
                                <i class="fas fa-th-large w-8"></i> Categories
                            </span>

                            <i class="fas fa-chevron-up text-sm transition-transform duration-300"
                                id="mobileCategoriesIcon"></i>
                        </button>

                        <div id="mobileCategoriesList" class="hidden pl-4 mt-1 space-y-1">

                            <?php foreach ($categories as $category): ?>

                                <a href="<?php echo BASE_URL; ?>shop?category=<?php echo $category['id']; ?>"
                                    class="flex items-center px-4 py-2 rounded-lg text-sm transition
                        <?php echo (basename($_SERVER['PHP_SELF']) === 'shop.php'
                                    && isset($_GET['category'])
                                    && $_GET['category'] == $category['id'])
                                    ? 'bg-primary-50 text-primary-700 font-medium'
                                    : 'text-gray-600 hover:text-primary-500 hover:bg-gray-50'; ?>">

                                    <?php if ($category['image']): ?>
                                        <img src="<?php echo getImageUrl($category['image'], 'categories'); ?>"
                                            class="w-6 mr-2 object-contain">
                                    <?php endif; ?>

                                    <?php echo e($category['name']); ?>

                                </a>

                            <?php endforeach; ?>

                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mobile Auth Links -->
                <?php if (isLoggedIn()): ?>
                    <div>

                        <!-- Profile -->
                        <a href="<?php echo BASE_URL; ?>profile"
                            class="flex items-center p-3 rounded-lg transition
                <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php'
                        ? 'bg-primary-50 text-primary-600 font-medium'
                        : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <i class="fas fa-user w-8"></i> My Profile
                        </a>

                        <!-- Wishlist -->
                        <a href="<?php echo BASE_URL; ?>wishlist"
                            class="relative flex items-center p-3 rounded-lg transition
                <?php echo basename($_SERVER['PHP_SELF']) === 'wishlist.php'
                        ? 'bg-red-50 text-red-500 font-medium'
                        : 'text-gray-700 hover:bg-gray-50 hover:text-red-500'; ?>">

                            <i class="fas fa-heart w-8 text-red-500"></i> Wishlist

                            <?php
                            $wishlistCount = getWishlistCount();
                            if ($wishlistCount > 0):
                            ?>
                                <span class="absolute top-3 left-5 text-red-500 bg-white text-[8px] font-semibold rounded-full h-3 min-w-3 px-1 flex items-center justify-center shadow">
                                    <?php echo $wishlistCount; ?>
                                </span>
                            <?php endif; ?>

                        </a>

                        <!-- Checkout -->
                        <a href="<?php echo BASE_URL; ?>checkout"
                            class="flex items-center p-3 rounded-lg transition
                <?php echo basename($_SERVER['PHP_SELF']) === 'checkout.php'
                        ? 'bg-primary-50 text-primary-600 font-medium'
                        : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <i class="fas fa-shopping-cart w-8"></i> Checkout
                        </a>

                        <!-- Help -->
                        <a href="<?php echo BASE_URL; ?>help"
                            class="flex items-center p-3 rounded-lg transition
                <?php echo basename($_SERVER['PHP_SELF']) === 'help.php'
                        ? 'bg-primary-50 text-primary-600 font-medium'
                        : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <i class="fas fa-question-circle w-8"></i> Help & Support
                        </a>
                    </div>

                <?php endif; ?>


                <!-- About -->
                <a href="<?php echo BASE_URL; ?>about-us"
                    class="flex items-center p-3 rounded-lg transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'about-us.php'
            ? 'bg-primary-50 text-primary-600 font-medium'
            : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i class="fas fa-info-circle w-8"></i> About Us
                </a>

                <!-- Contact -->
                <a href="<?php echo BASE_URL; ?>contact-us"
                    class="flex items-center p-3 rounded-lg transition
        <?php echo basename($_SERVER['PHP_SELF']) === 'contact-us.php'
            ? 'bg-primary-50 text-primary-600 font-medium'
            : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i class="fas fa-envelope w-8"></i> Contact Us
                </a>

                <?php if (isLoggedIn()): ?>
                    <!-- Logout -->
                    <a href="<?php echo BASE_URL; ?>logout"
                        class="flex items-center p-3 text-red-500 hover:bg-red-50 rounded-lg transition">
                        <i class="fas fa-sign-out-alt w-8"></i> Logout
                    </a>

                <?php else: ?>
                    <div class="border-t border-gray-100 pt-2 mt-2">
                        <a href="<?php echo BASE_URL; ?>login"
                            class="flex items-center justify-center p-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition mt-2">
                            Login / Register
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div class="h-16"></div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const mobileCategoriesToggle = document.getElementById('mobileCategoriesToggle');
        const mobileCategoriesList = document.getElementById('mobileCategoriesList');
        const mobileCategoriesIcon = document.getElementById('mobileCategoriesIcon');

        function openMobileMenu() {
            mobileMenu.classList.remove('-translate-x-full');
            mobileMenuOverlay.classList.remove('opacity-0', 'invisible');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            mobileMenu.classList.add('-translate-x-full');
            mobileMenuOverlay.classList.add('opacity-0', 'invisible');
            document.body.style.overflow = '';
        }

        mobileMenuBtn?.addEventListener('click', openMobileMenu);
        mobileMenuClose?.addEventListener('click', closeMobileMenu);
        mobileMenuOverlay?.addEventListener('click', closeMobileMenu);

        // Mobile categories toggle
        mobileCategoriesToggle?.addEventListener('click', function() {
            mobileCategoriesList.classList.toggle('hidden');
            mobileCategoriesIcon.classList.toggle('rotate-90');
        });
    </script>
