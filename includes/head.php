<?php
$siteName = '1HousingKey';
$defaultPageTitle = 'Buy Homes Directly With Builders';
$defaultDescription = 'Discover verified residential projects, compare builder prices, and book free site visits directly with builders on 1HousingKey.';
$defaultKeywords = 'real estate, property, homes, apartments, builders, residential projects, site visit, 1HousingKey';
$defaultImage = 'https://i.ibb.co/HfXRT0Wc/housiey-logo.webp';

$escapeMeta = static function ($value) {
    return function_exists('e')
        ? e((string)$value)
        : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$absoluteUrl = static function ($url) {
    $url = trim((string)$url);

    if ($url === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }

    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }

    return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
};

$currentUrl = function_exists('getCurrentPageUrl')
    ? getCurrentPageUrl()
    : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') .
        ($_SERVER['HTTP_HOST'] ?? parse_url(BASE_URL, PHP_URL_HOST)) .
        ($_SERVER['REQUEST_URI'] ?? parse_url(BASE_URL, PHP_URL_PATH)));

$metaTitle = trim((string)($pageTitle ?? $defaultPageTitle));
$metaFullTitle = $metaTitle !== '' && $metaTitle !== $siteName ? $metaTitle . ' - ' . $siteName : $siteName;
$metaDescription = trim((string)($pageDescription ?? $defaultDescription));
$metaKeywords = trim((string)($pageKeywords ?? $defaultKeywords));
$metaAuthor = trim((string)($pageAuthor ?? $siteName));
$metaRobots = trim((string)($pageRobots ?? 'index, follow, max-image-preview:large'));
$metaCanonical = $absoluteUrl($pageCanonical ?? $currentUrl);
$metaImage = $absoluteUrl($pageImage ?? $defaultImage);
$metaType = trim((string)($pageType ?? 'website'));
$metaLocale = trim((string)($pageLocale ?? 'en_IN'));
$metaThemeColor = trim((string)($pageThemeColor ?? '#2B1C5A'));
?>
<!DOCTYPE html>
<html lang="<?php echo $escapeMeta($pageLanguage ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $escapeMeta($metaDescription); ?>">
    <meta name="keywords" content="<?php echo $escapeMeta($metaKeywords); ?>">
    <meta name="author" content="<?php echo $escapeMeta($metaAuthor); ?>">
    <meta name="robots" content="<?php echo $escapeMeta($metaRobots); ?>">
    <meta name="theme-color" content="<?php echo $escapeMeta($metaThemeColor); ?>">
    <meta name="application-name" content="<?php echo $escapeMeta($siteName); ?>">
    <meta name="apple-mobile-web-app-title" content="<?php echo $escapeMeta($siteName); ?>">

    <meta property="og:site_name" content="<?php echo $escapeMeta($siteName); ?>">
    <meta property="og:title" content="<?php echo $escapeMeta($metaFullTitle); ?>">
    <meta property="og:description" content="<?php echo $escapeMeta($metaDescription); ?>">
    <meta property="og:type" content="<?php echo $escapeMeta($metaType); ?>">
    <meta property="og:url" content="<?php echo $escapeMeta($metaCanonical); ?>">
    <meta property="og:image" content="<?php echo $escapeMeta($metaImage); ?>">
    <meta property="og:locale" content="<?php echo $escapeMeta($metaLocale); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $escapeMeta($metaFullTitle); ?>">
    <meta name="twitter:description" content="<?php echo $escapeMeta($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo $escapeMeta($metaImage); ?>">

    <link rel="canonical" href="<?php echo $escapeMeta($metaCanonical); ?>">
    <title><?php echo $escapeMeta($metaFullTitle); ?></title>
    <link rel="icon" href="<?php echo ASSETS_URL; ?>public/favicon.ico">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- SWIPER JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- SWIPER CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
                            700: '#1E1A3C',
                            900: '#0E0B1D'
                        },
                        accent: {
                            DEFAULT: '#EC4B02',
                            600: '#C53F1D'
                        }
                    },
                    fontFamily: {
                        sans: ['Open Sans', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>
