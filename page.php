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

<section class="mt-20 bg-primary text-white">
    <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-6 lg:px-10">
        <p class="text-sm font-black uppercase tracking-[2px] text-accent"><?php echo e($page['eyebrow']); ?></p>
        <h1 class="mt-3 text-4xl font-black md:text-5xl"><?php echo e($page['title']); ?></h1>
    </div>
</section>

<section class="bg-gray-50 py-12">
    <div class="mx-auto max-w-[1200px] px-4 sm:px-6 lg:px-10">
        <div class="rounded-lg border border-gray-200 bg-white p-6 md:p-8">
            <div class="space-y-4 text-base leading-8 text-gray-600">
                <?php foreach ($page['body'] as $paragraph): ?>
                    <p><?php echo e($paragraph); ?></p>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($page['calculator'])): ?>
                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <label class="block text-sm font-bold text-gray-700">
                        Loan Amount
                        <input id="loanAmount" type="number" value="5000000" class="mt-2 h-12 w-full rounded-md border border-gray-200 px-3">
                    </label>
                    <label class="block text-sm font-bold text-gray-700">
                        Interest %
                        <input id="loanRate" type="number" step="0.1" value="8.5" class="mt-2 h-12 w-full rounded-md border border-gray-200 px-3">
                    </label>
                    <label class="block text-sm font-bold text-gray-700">
                        Years
                        <input id="loanYears" type="number" value="20" class="mt-2 h-12 w-full rounded-md border border-gray-200 px-3">
                    </label>
                    <div class="rounded-md bg-primary p-4 text-white">
                        <p class="text-sm text-white/70">Estimated EMI</p>
                        <p id="emiResult" class="mt-2 text-2xl font-black">Rs. 0</p>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const amount = document.getElementById('loanAmount');
                        const rate = document.getElementById('loanRate');
                        const years = document.getElementById('loanYears');
                        const result = document.getElementById('emiResult');

                        function calculateEmi() {
                            const p = Number(amount.value || 0);
                            const r = Number(rate.value || 0) / 12 / 100;
                            const n = Number(years.value || 0) * 12;
                            const emi = r > 0 && n > 0 ? p * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1) : 0;
                            result.textContent = 'Rs. ' + Math.round(emi).toLocaleString('en-IN');
                        }

                        [amount, rate, years].forEach(input => input.addEventListener('input', calculateEmi));
                        calculateEmi();
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
