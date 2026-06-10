<?php

require_once __DIR__ . '/includes/app_helpers.php';

$slug = trim($_GET['slug'] ?? 'about-us');

$pages = [
    'about-us' => [
        'title' => 'About Us',
        'eyebrow' => 'Direct builder home buying',
        'body' => [
            '1HousingKey helps buyers discover verified residential projects, compare builder pricing, and book free site visits without brokerage.',
            'The platform connects users, builders, associate managers, employees, and admins in one simple CRM-backed workflow.'
        ]
    ],
    'contact-us' => [
        'title' => 'Contact Us',
        'eyebrow' => 'We are here to help',
        'body' => [
            'Email: support@1housingkey.com',
            'Phone: +91 99999 99999',
            'For project inquiries, open any project detail page and use Contact Builder or Book Free Site Visit.'
        ]
    ],
    'calculator' => [
        'title' => 'Home Loan Calculator',
        'eyebrow' => 'Estimate your EMI',
        'body' => [
            'Use this quick calculator as a planning guide before shortlisting homes.'
        ],
        'calculator' => true
    ],
    'privacy-policy' => [
        'title' => 'Privacy Policy',
        'eyebrow' => 'Your data matters',
        'body' => [
            'We collect buyer details only to process inquiries, site visits, account access, and CRM follow-ups.',
            'Contact information may be shared with the relevant builder or assigned manager for the selected project.'
        ]
    ],
    'terms' => [
        'title' => 'Terms',
        'eyebrow' => 'Platform usage',
        'body' => [
            'Project prices, availability, and offers are subject to builder confirmation.',
            'Users should verify RERA, legal, and financial details before making a purchase decision.'
        ]
    ],
    'disclaimer' => [
        'title' => 'Disclaimer',
        'eyebrow' => 'Important information',
        'body' => [
            '1HousingKey is a listing and lead management platform. Final transaction terms are between the buyer and builder.',
            'Images, prices, possession dates, and amenities are provided by builders or project teams and may change.'
        ]
    ],
    'blogs' => [
        'title' => 'Blogs',
        'eyebrow' => 'Real estate insights',
        'body' => [
            'Buyer guides, project updates, locality notes, and home loan explainers will appear here.',
            'For now, browse live projects from the home page.'
        ]
    ],
    'careers' => [
        'title' => 'Careers',
        'eyebrow' => 'Build with us',
        'body' => [
            'We are building a practical real estate CRM and direct-builder buying platform.',
            'For opportunities, contact support@1housingkey.com with your profile.'
        ]
    ],
    'help' => [
        'title' => 'Help',
        'eyebrow' => 'Support',
        'body' => [
            'Buyers can search projects, send inquiries, save projects, and book site visits.',
            'Builders and internal teams can use their role dashboards to manage projects, leads, and visits.'
        ]
    ],
];

$page = $pages[$slug] ?? null;

if (!$page) {
    http_response_code(404);
    $page = [
        'title' => 'Page Not Found',
        'eyebrow' => '404',
        'body' => ['The page you are looking for is not available.']
    ];
}

$pageTitle = $page['title'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="mt-20 pt-6 md:pt-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
        <div class="flex flex-col items-start justify-start">

            <div class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
                <i class="fa-solid fa-building"></i>
                <span><?php echo e($page['eyebrow']); ?></span>
            </div>
            <h1 class="text-primary text-3xl md:text-4xl font-semibold leading-tight">
                <?php echo e($page['title']); ?>
            </h1>

            <p class="mt-3 text-sm md:text-base text-gray-500">
                <?php foreach ($page['body'] as $paragraph): ?>
            <p><?php echo e($paragraph); ?></p>
        <?php endforeach; ?>
        </p>
        </div>
    </div>
</section>


<section class="py-6 md:py-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">

        <?php if (!empty($page['calculator'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
                <div class="h-full">
                    <?php require_once __DIR__ . '/includes/tools/calculator.php'; ?>
                </div>

                <div class="h-full">
                    <?php require_once __DIR__ . '/includes/tools/area-converter.php'; ?>
                </div>

                <div class="h-full">
                    <?php require_once __DIR__ . '/includes/tools/emi.php'; ?>
                </div>
            </div>


        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>