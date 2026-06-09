<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'Employee Dashboard';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create_builder') {
    $company = trim($_POST['company_name'] ?? '');
    $name = trim($_POST['builder_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');

    if ($company && $name && $email && $phone) {
        $stmt = $pdo->prepare("
            INSERT INTO builders (
                uuid, company_name, company_slug, builder_name,
                email, phone, whatsapp_number, password, city, state,
                status, created_by_employee
            ) VALUES (
                UUID(), :company_name, :company_slug, :builder_name,
                :email, :phone, :phone, :password, :city, :state,
                'pending', :employee_id
            )
        ");
        $stmt->execute([
            ':company_name' => $company,
            ':company_slug' => makeSlug($company) . '-' . random_int(100, 999),
            ':builder_name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':password' => password_hash('Password@123', PASSWORD_DEFAULT),
            ':city' => $city,
            ':state' => $state,
            ':employee_id' => $_SESSION['employee_id']
        ]);
        setFlash('Builder added as pending. Default password is Password@123.', 'success');
        redirect(EMPLOYEE_URL);
    }
}

$stats = [
    'Pending Builders' => tableCount('builders', "status = 'pending'"),
    'Published Projects' => tableCount('projects', "status = 'published'"),
    'New Leads' => tableCount('inquiries', "inquiry_status = 'New'"),
    'Pending Visits' => tableCount('site_visit_bookings', "status = 'Pending'")
];

$builders = $pdo->query('SELECT * FROM builders ORDER BY created_at DESC LIMIT 12')->fetchAll();
$leads = $pdo->query("
    SELECT i.*, p.project_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    ORDER BY i.created_at DESC
    LIMIT 12
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 min-h-screen py-8">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase text-accent">Employee</p>
                <h1 class="text-3xl font-black text-primary"><?php echo e($_SESSION['employee_name'] ?? 'Operations'); ?></h1>
            </div>
            <a href="<?php echo EMPLOYEE_URL; ?>logout" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Logout</a>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($stats as $label => $value): ?>
                <div class="rounded-lg border bg-white p-5">
                    <p class="text-2xl font-black"><?php echo (int)$value; ?></p>
                    <p class="text-sm text-gray-500"><?php echo e($label); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 grid grid-cols-1 xl:grid-cols-[420px_1fr] gap-6">
            <form method="post" class="rounded-lg border bg-white p-5">
                <input type="hidden" name="action" value="create_builder">
                <h2 class="text-xl font-black text-primary">Onboard Builder</h2>
                <div class="mt-4 space-y-3">
                    <input name="company_name" required placeholder="Company name" class="h-12 w-full rounded-md border px-3 text-sm">
                    <input name="builder_name" required placeholder="Contact person" class="h-12 w-full rounded-md border px-3 text-sm">
                    <input name="email" required type="email" placeholder="Email" class="h-12 w-full rounded-md border px-3 text-sm">
                    <input name="phone" required placeholder="Phone" class="h-12 w-full rounded-md border px-3 text-sm">
                    <input name="city" placeholder="City" class="h-12 w-full rounded-md border px-3 text-sm">
                    <input name="state" placeholder="State" class="h-12 w-full rounded-md border px-3 text-sm">
                </div>
                <button class="mt-4 h-12 w-full rounded-md bg-primary font-bold text-white">Create Builder</button>
            </form>

            <div class="rounded-lg border bg-white p-5">
                <h2 class="text-xl font-black text-primary">Recent Builders</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">City</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($builders as $builder): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-3 font-bold"><?php echo e($builder['company_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($builder['builder_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($builder['city']); ?></td>
                                    <td class="px-4 py-3"><?php echo e($builder['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-lg border bg-white p-5">
            <h2 class="text-xl font-black text-primary">Recent Leads</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($leads as $lead): ?>
                    <div class="rounded-md border p-4">
                        <p class="font-black"><?php echo e($lead['full_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo e($lead['project_name']); ?></p>
                        <p class="mt-2 text-sm font-bold text-accent"><?php echo e($lead['phone']); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($leads)): ?><p class="text-sm text-gray-500">No leads yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
