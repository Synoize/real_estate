<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Builder Dashboard';
$builderId = (int)$_SESSION['builder_id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_manager') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name && $email && $phone) {
            $stmt = $pdo->prepare("
                INSERT INTO associate_managers (
                    uuid, builder_id, manager_code, full_name, email, phone,
                    whatsapp_number, password, status, created_by
                ) VALUES (
                    UUID(), :builder_id, :manager_code, :full_name, :email, :phone,
                    :phone, :password, 'active', :created_by
                )
            ");
            $stmt->execute([
                ':builder_id' => $builderId,
                ':manager_code' => makeCode('MGR'),
                ':full_name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => password_hash('Password@123', PASSWORD_DEFAULT),
                ':created_by' => $builderId
            ]);
            setFlash('Associate manager created with default password Password@123.', 'success');
            redirect(BUILDER_URL);
        }
    }

    if ($action === 'create_project') {
        $name = trim($_POST['project_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $type = trim($_POST['project_type'] ?? 'Apartment');

        if ($name && $city && $state) {
            $slug = makeSlug($name) . '-' . random_int(100, 999);
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    uuid, builder_id, assigned_manager_id, project_type,
                    project_name, slug, project_code, city, state, locality,
                    overview, amenities, thumbnail_image, featured_image,
                    youtube_video_link, is_verified, project_status, status
                ) VALUES (
                    UUID(), :builder_id, :manager_id, :project_type,
                    :project_name, :slug, :project_code, :city, :state, :locality,
                    :overview, :amenities, :thumbnail_image, :featured_image,
                    :youtube_video_link, 0, :project_status, 'pending'
                )
            ");
            $stmt->execute([
                ':builder_id' => $builderId,
                ':manager_id' => $_POST['assigned_manager_id'] ?: null,
                ':project_type' => $type,
                ':project_name' => $name,
                ':slug' => $slug,
                ':project_code' => makeCode('PRJ'),
                ':city' => $city,
                ':state' => $state,
                ':locality' => trim($_POST['locality'] ?? ''),
                ':overview' => trim($_POST['overview'] ?? ''),
                ':amenities' => trim($_POST['amenities'] ?? ''),
                ':thumbnail_image' => trim($_POST['image'] ?? ''),
                ':featured_image' => trim($_POST['image'] ?? ''),
                ':youtube_video_link' => trim($_POST['video_url'] ?? ''),
                ':project_status' => $_POST['project_status'] ?? 'Upcoming'
            ]);

            $projectId = (int)$pdo->lastInsertId();
            $videoUrl = trim($_POST['video_url'] ?? '');

            if ($videoUrl !== '') {
                $pdo->prepare("
                    INSERT INTO project_videos (project_id, video_title, video_url)
                    VALUES (?, ?, ?)
                ")->execute([$projectId, $name . ' Video', $videoUrl]);
            }

            $unitNames = $_POST['unit_name'] ?? [];
            $bhkTypes = $_POST['bhk_type'] ?? [];
            $areas = $_POST['area'] ?? [];
            $prices = $_POST['price'] ?? [];
            $planStmt = $pdo->prepare("
                INSERT INTO project_unit_plans (
                    project_id, unit_name, bhk_type, area, price
                ) VALUES (
                    :project_id, :unit_name, :bhk_type, :area, :price
                )
            ");

            foreach ($bhkTypes as $index => $bhkType) {
                $unitName = trim($unitNames[$index] ?? '');
                $bhkType = trim($bhkType);
                $area = trim($areas[$index] ?? '');
                $price = (float)($prices[$index] ?? 0);

                if ($unitName === '' && $bhkType === '' && $area === '' && $price <= 0) {
                    continue;
                }

                $planStmt->execute([
                    ':project_id' => $projectId,
                    ':unit_name' => $unitName,
                    ':bhk_type' => $bhkType,
                    ':area' => $area,
                    ':price' => $price
                ]);
            }

            setFlash('Project submitted for admin approval.', 'success');
            redirect(BUILDER_URL);
        }
    }
}

$managers = $pdo->prepare('SELECT * FROM associate_managers WHERE builder_id = ? ORDER BY created_at DESC');
$managers->execute([$builderId]);
$managers = $managers->fetchAll();

$projects = $pdo->prepare('SELECT * FROM projects WHERE builder_id = ? AND deleted_at IS NULL ORDER BY created_at DESC');
$projects->execute([$builderId]);
$projects = $projects->fetchAll();

$leads = $pdo->prepare("
    SELECT i.*, p.project_name
    FROM inquiries i
    INNER JOIN projects p ON p.id = i.project_id
    WHERE i.builder_id = ?
    ORDER BY i.created_at DESC
    LIMIT 10
");
$leads->execute([$builderId]);
$leads = $leads->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="mt-20 bg-gray-50 min-h-screen py-8">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase text-accent">Builder</p>
                <h1 class="text-3xl font-black text-primary"><?php echo e($_SESSION['builder_company_name']); ?></h1>
            </div>
            <a href="<?php echo BUILDER_URL; ?>logout" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Logout</a>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($projects); ?></p><p class="text-sm text-gray-500">Projects</p></div>
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($managers); ?></p><p class="text-sm text-gray-500">Managers</p></div>
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo count($leads); ?></p><p class="text-sm text-gray-500">Recent leads</p></div>
            <div class="rounded-lg border bg-white p-5"><p class="text-2xl font-black"><?php echo tableCount('site_visit_bookings', 'builder_id = ?', [$builderId]); ?></p><p class="text-sm text-gray-500">Visits</p></div>
        </div>

        <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
            <form method="post" class="rounded-lg border bg-white p-5">
                <input type="hidden" name="action" value="create_project">
                <h2 class="text-xl font-black text-primary">Add Project</h2>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input name="project_name" required placeholder="Project name" class="h-12 rounded-md border px-3 text-sm">
                    <select name="project_type" class="h-12 rounded-md border px-3 text-sm">
                        <?php foreach (['Apartment', 'Plot', 'Villa', 'Commercial'] as $type): ?><option><?php echo e($type); ?></option><?php endforeach; ?>
                    </select>
                    <input name="city" required placeholder="City" class="h-12 rounded-md border px-3 text-sm">
                    <input name="state" required placeholder="State" class="h-12 rounded-md border px-3 text-sm">
                    <input name="locality" placeholder="Locality" class="h-12 rounded-md border px-3 text-sm">
                    <select name="assigned_manager_id" class="h-12 rounded-md border px-3 text-sm">
                        <option value="">No manager assigned</option>
                        <?php foreach ($managers as $manager): ?><option value="<?php echo (int)$manager['id']; ?>"><?php echo e($manager['full_name']); ?></option><?php endforeach; ?>
                    </select>
                    <input name="image" placeholder="Image URL" class="h-12 rounded-md border px-3 text-sm md:col-span-2">
                    <input name="video_url" placeholder="Video URL" class="h-12 rounded-md border px-3 text-sm md:col-span-2">
                    <textarea name="overview" placeholder="Overview" class="min-h-24 rounded-md border px-3 py-3 text-sm md:col-span-2"></textarea>
                    <input name="amenities" placeholder="Amenities comma separated" class="h-12 rounded-md border px-3 text-sm md:col-span-2">
                    <div class="md:col-span-2 rounded-md border border-gray-200 bg-gray-50 p-3">
                        <p class="text-sm font-bold text-primary">Unit Plans</p>
                        <div class="mt-3 space-y-2">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                    <input name="unit_name[]" placeholder="Unit name" class="h-11 rounded-md border px-3 text-sm">
                                    <input name="bhk_type[]" placeholder="BHK / unit type" class="h-11 rounded-md border px-3 text-sm">
                                    <input name="area[]" placeholder="Area e.g. 908 sqft" class="h-11 rounded-md border px-3 text-sm">
                                    <input name="price[]" type="number" placeholder="Price" class="h-11 rounded-md border px-3 text-sm">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <button class="mt-4 h-12 rounded-md bg-primary px-5 font-bold text-white">Submit for Approval</button>
            </form>

            <div class="space-y-6">
                <form method="post" class="rounded-lg border bg-white p-5">
                    <input type="hidden" name="action" value="create_manager">
                    <h2 class="text-xl font-black text-primary">Add Associate Manager</h2>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input name="full_name" required placeholder="Name" class="h-12 rounded-md border px-3 text-sm">
                        <input name="email" required type="email" placeholder="Email" class="h-12 rounded-md border px-3 text-sm">
                        <input name="phone" required placeholder="Phone" class="h-12 rounded-md border px-3 text-sm">
                    </div>
                    <button class="mt-4 h-12 rounded-md bg-accent px-5 font-bold text-primary">Create Manager</button>
                </form>

                <div class="rounded-lg border bg-white p-5">
                    <h2 class="text-xl font-black text-primary">Latest Leads</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($leads as $lead): ?>
                            <div class="rounded-md bg-gray-50 p-4">
                                <p class="font-bold"><?php echo e($lead['full_name']); ?> - <?php echo e($lead['phone']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo e($lead['project_name']); ?>, <?php echo e($lead['inquiry_status']); ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?><p class="text-sm text-gray-500">No leads yet.</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-lg border bg-white p-5">
            <h2 class="text-xl font-black text-primary">Projects</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($projects as $project): ?>
                    <div class="rounded-md border p-4">
                        <p class="font-black"><?php echo e($project['project_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo e($project['city']); ?> - <?php echo e($project['status']); ?></p>
                        <p class="mt-2 text-sm font-bold text-accent"><?php echo e(projectPriceRange($project)); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
