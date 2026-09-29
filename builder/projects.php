<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Manage Projects';
$builderId = (int)$_SESSION['builder_id'];
$errors = [];

/* AJAX handler for edit modal */
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
    $stmt->execute([$id, $builderId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
    $stmtA = $pdo->prepare("SELECT * FROM project_amenities WHERE project_id = ?");
    $stmtA->execute([$id]);
    $project['amenities'] = $stmtA->fetchAll();
    $stmtU = $pdo->prepare("SELECT * FROM project_unit_plans WHERE project_id = ?");
    $stmtU->execute([$id]);
    $project['unit_plans'] = $stmtU->fetchAll();
    $stmtG = $pdo->prepare("SELECT id, image FROM project_images WHERE project_id = ? AND image_type = 'gallery' ORDER BY id ASC");
    $stmtG->execute([$id]);
    $project['gallery'] = $stmtG->fetchAll();
    echo json_encode($project);
    exit;
}

function uploadProjectFile($file, $prefix, $existing = null) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return $existing;
    if ($file['error'] !== UPLOAD_ERR_OK) return '__error__';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = $prefix . '_' . uniqid() . '.' . $ext;
    $dest = __DIR__ . '/../assets/images/projects/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return '__error__';
    if ($existing && !str_starts_with($existing, 'http') && file_exists(__DIR__ . '/../assets/images/projects/' . $existing)) {
        @unlink(__DIR__ . '/../assets/images/projects/' . $existing);
    }
    return $name;
}

function handleGalleryUploads($files, $projectId, $urls = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO project_images (project_id, image, image_type) VALUES (?, ?, 'gallery')");
    $urls = trim($urls);
    if ($urls !== '') {
        foreach (preg_split('/[\r\n,]+/', $urls) as $url) {
            $url = trim($url);
            if ($url !== '' && preg_match('~^https?://~i', $url)) {
                $stmt->execute([$projectId, $url]);
            }
        }
    }
    if (!empty($files['name'][0])) {
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif','avif'])) continue;
            $fname = 'gal_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$i], __DIR__ . '/../assets/images/projects/' . $fname)) {
                $stmt->execute([$projectId, $fname]);
            }
        }
    }
}

function handleAmenities($amenityNames, $amenityIcons, $projectId, $amenityIconUrls = []) {
    global $pdo;
    $pdo->prepare("DELETE FROM project_amenities WHERE project_id = ?")->execute([$projectId]);
    $stmt = $pdo->prepare("INSERT INTO project_amenities (project_id, amenity_name, amenity_icon) VALUES (?, ?, ?)");
    foreach ($amenityNames as $i => $name) {
        $name = trim($name);
        if ($name === '') continue;
        $icon = '';
        $url = trim($amenityIconUrls[$i] ?? '');
        if ($url !== '') {
            $icon = $url;
        } elseif (!empty($amenityIcons['name'][$i]) && $amenityIcons['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($amenityIcons['name'][$i], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif','svg','avif'])) {
                $fname = 'amenity_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($amenityIcons['tmp_name'][$i], __DIR__ . '/../assets/images/projects/' . $fname)) {
                    $icon = $fname;
                }
            }
        }
        $stmt->execute([$projectId, $name, $icon]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['project_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $type = trim($_POST['project_type'] ?? 'Apartment');

        if ($name && $city && $state) {
            $imageUrl = trim($_POST['image_url'] ?? '');
            $thumbnail = null; $featured = null;

            if ($imageUrl) {
                $thumbnail = $featured = $imageUrl;
            } else {
                $thumbFile = $_FILES['thumbnail_image'] ?? null;
                $featFile = $_FILES['featured_image'] ?? null;
                if ($thumbFile && $thumbFile['error'] === UPLOAD_ERR_OK) {
                    $r = uploadProjectFile($thumbFile, 'thumb');
                    if ($r === '__error__') { $errors[] = 'Invalid thumbnail'; } else { $thumbnail = $r; }
                }
                if ($featFile && $featFile['error'] === UPLOAD_ERR_OK) {
                    $r = uploadProjectFile($featFile, 'feat');
                    if ($r === '__error__') { $errors[] = 'Invalid featured image'; } else { $featured = $r; }
                }
                if (!$thumbnail) $thumbnail = $featured;
                if (!$featured) $featured = $thumbnail;
            }

            $brochure = null;
            $brochureFile = $_FILES['brochure_file'] ?? null;
            if ($brochureFile && $brochureFile['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($brochureFile['name'], PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $bn = 'brochure_' . uniqid() . '.pdf';
                    move_uploaded_file($brochureFile['tmp_name'], __DIR__ . '/../assets/images/projects/' . $bn);
                    $brochure = $bn;
                } else { $errors[] = 'Brochure must be PDF'; }
            }

            if (empty($errors)) {
                $slug = makeSlug($name) . '-' . random_int(100, 999);
                $stmt = $pdo->prepare("
                    INSERT INTO projects (uuid, builder_id, assigned_manager_id, project_type,
                        project_name, slug, project_code, rera_number, city, state, locality, address,
                        latitude, longitude, overview, location_details, pros, cons,
                        legal_details, litigation_details, bank_details, payment_scheme,
                        brochure_file, thumbnail_image, featured_image, youtube_video_link,
                        total_towers, total_units, total_floors, total_area,
                        possession_date, launch_date,
                        is_verified, project_status, status)
                    VALUES (UUID(), :bid, :mid, :ptype,
                        :pname, :slug, :pcode, :rera, :city, :state, :loc, :addr,
                        :lat, :lng, :overview, :locdet, :pros, :cons,
                        :legal, :litig, :bank, :payment,
                        :brochure, :thumb, :feat, :youtube,
                        :towers, :units, :floors, :area,
                        :possession, :launch,
                        0, :pstatus, 'pending')
                ");
                $stmt->execute([
                    ':bid' => $builderId,
                    ':mid' => $_POST['assigned_manager_id'] ?: null,
                    ':ptype' => $type,
                    ':pname' => $name,
                    ':slug' => $slug,
                    ':pcode' => makeCode('PRJ'),
                    ':rera' => trim($_POST['rera_number'] ?? ''),
                    ':city' => $city,
                    ':state' => $state,
                    ':loc' => trim($_POST['locality'] ?? ''),
                    ':addr' => trim($_POST['address'] ?? ''),
                    ':lat' => $_POST['latitude'] ?: null,
                    ':lng' => $_POST['longitude'] ?: null,
                    ':overview' => trim($_POST['overview'] ?? ''),
                    ':locdet' => trim($_POST['location_details'] ?? ''),
                    ':pros' => trim($_POST['pros'] ?? ''),
                    ':cons' => trim($_POST['cons'] ?? ''),
                    ':legal' => trim($_POST['legal_details'] ?? ''),
                    ':litig' => trim($_POST['litigation_details'] ?? ''),
                    ':bank' => trim($_POST['bank_details'] ?? ''),
                    ':payment' => trim($_POST['payment_scheme'] ?? ''),
                    ':brochure' => $brochure,
                    ':thumb' => $thumbnail,
                    ':feat' => $featured,
                    ':youtube' => trim($_POST['video_url'] ?? ''),
                    ':towers' => (int)($_POST['total_towers'] ?? 0),
                    ':units' => (int)($_POST['total_units'] ?? 0),
                    ':floors' => (int)($_POST['total_floors'] ?? 0),
                    ':area' => trim($_POST['total_area'] ?? ''),
                    ':possession' => $_POST['possession_date'] ?: null,
                    ':launch' => $_POST['launch_date'] ?: null,
                    ':pstatus' => $_POST['project_status'] ?? 'Upcoming'
                ]);

                $projectId = (int)$pdo->lastInsertId();

                handleGalleryUploads($_FILES['gallery_images'] ?? [], $projectId, $_POST['gallery_urls'] ?? '');

                $videoUrl = trim($_POST['video_url'] ?? '');
                if ($videoUrl !== '') {
                    $pdo->prepare("INSERT INTO project_videos (project_id, video_title, video_url) VALUES (?, ?, ?)")->execute([$projectId, $name . ' Video', $videoUrl]);
                }

                $amenityNames = $_POST['amenity_name'] ?? [];
                $amenityIcons = $_FILES['amenity_icon'] ?? ['name'=>[],'error'=>[],'tmp_name'=>[]];
                $amenityIconUrls = $_POST['amenity_icon_url'] ?? [];
                handleAmenities($amenityNames, $amenityIcons, $projectId, $amenityIconUrls);

                $unitNames = $_POST['unit_name'] ?? [];
                $bhkTypes = $_POST['bhk_type'] ?? [];
                $areas = $_POST['area'] ?? [];
                $prices = $_POST['price'] ?? [];
                $facings = $_POST['facing'] ?? [];
                $bookingAmounts = $_POST['booking_amount'] ?? [];
                $descriptions = $_POST['unit_description'] ?? [];
                $planStmt = $pdo->prepare("INSERT INTO project_unit_plans (project_id, unit_name, bhk_type, area, price, facing, booking_amount, description) VALUES (:pid, :un, :bhk, :area, :price, :facing, :ba, :desc)");
                foreach ($bhkTypes as $i => $bhk) {
                    $un = trim($unitNames[$i] ?? '');
                    $bhk = trim($bhk);
                    $area = trim($areas[$i] ?? '');
                    $price = (float)($prices[$i] ?? 0);
                    $facing = trim($facings[$i] ?? '');
                    $ba = (float)($bookingAmounts[$i] ?? 0);
                    $desc = trim($descriptions[$i] ?? '');
                    if ($un === '' && $bhk === '' && $area === '' && $price <= 0 && $facing === '' && $ba <= 0 && $desc === '') continue;
                    $planStmt->execute([':pid' => $projectId, ':un' => $un, ':bhk' => $bhk, ':area' => $area, ':price' => $price, ':facing' => $facing ?: null, ':ba' => $ba ?: null, ':desc' => $desc ?: null]);
                }

                $pdo->prepare("UPDATE builders SET total_projects = total_projects + 1 WHERE id = ?")->execute([$builderId]);

                setFlash('Project submitted for admin approval.', 'success');
                redirect(BUILDER_URL . 'projects');
            }
        } else {
            $errors[] = 'Name, city and state are required';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['project_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');

        if ($id && $name && $city && $state) {
            $existing = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
            $existing->execute([$id, $builderId]);
            $row = $existing->fetch();
            if (!$row) { $errors[] = 'Project not found'; }
            else {
                $imageUrl = trim($_POST['image_url'] ?? '');
                $thumbnail = $row['thumbnail_image'];
                $featured = $row['featured_image'];
                $brochure = $row['brochure_file'];

                if ($imageUrl) {
                    $thumbnail = $featured = $imageUrl;
                } else {
                    $r = uploadProjectFile($_FILES['thumbnail_image'] ?? null, 'thumb', $thumbnail);
                    if ($r === '__error__') { $errors[] = 'Invalid thumbnail'; } else { $thumbnail = $r ?? $thumbnail; }
                    $r = uploadProjectFile($_FILES['featured_image'] ?? null, 'feat', $featured);
                    if ($r === '__error__') { $errors[] = 'Invalid featured image'; } else { $featured = $r ?? $featured; }
                }

                $brochureFile = $_FILES['brochure_file'] ?? null;
                if ($brochureFile && $brochureFile['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($brochureFile['name'], PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        if ($brochure && file_exists(__DIR__ . '/../assets/images/projects/' . $brochure)) @unlink(__DIR__ . '/../assets/images/projects/' . $brochure);
                        $bn = 'brochure_' . uniqid() . '.pdf';
                        move_uploaded_file($brochureFile['tmp_name'], __DIR__ . '/../assets/images/projects/' . $bn);
                        $brochure = $bn;
                    } else { $errors[] = 'Brochure must be PDF'; }
                }

                if (empty($errors)) {
                    $pdo->prepare("
                        UPDATE projects SET project_name=:pname, project_type=:ptype, city=:city, state=:state,
                            locality=:loc, address=:addr, latitude=:lat, longitude=:lng,
                            overview=:overview, location_details=:locdet, pros=:pros, cons=:cons,
                            legal_details=:legal, litigation_details=:litig, bank_details=:bank,
                            payment_scheme=:payment, brochure_file=:brochure,
                            thumbnail_image=:thumb, featured_image=:feat, youtube_video_link=:youtube,
                            total_towers=:towers, total_units=:units, total_floors=:floors, total_area=:area,
                            possession_date=:possession, launch_date=:launch, rera_number=:rera,
                            project_status=:pstatus, assigned_manager_id=:mid
                        WHERE id=:id AND builder_id=:bid
                    ")->execute([
                        ':pname' => $name, ':ptype' => trim($_POST['project_type'] ?? 'Apartment'),
                        ':city' => $city, ':state' => $state,
                        ':loc' => trim($_POST['locality'] ?? ''), ':addr' => trim($_POST['address'] ?? ''),
                        ':lat' => $_POST['latitude'] ?: null, ':lng' => $_POST['longitude'] ?: null,
                        ':overview' => trim($_POST['overview'] ?? ''),
                        ':locdet' => trim($_POST['location_details'] ?? ''),
                        ':pros' => trim($_POST['pros'] ?? ''), ':cons' => trim($_POST['cons'] ?? ''),
                        ':legal' => trim($_POST['legal_details'] ?? ''),
                        ':litig' => trim($_POST['litigation_details'] ?? ''),
                        ':bank' => trim($_POST['bank_details'] ?? ''),
                        ':payment' => trim($_POST['payment_scheme'] ?? ''),
                        ':brochure' => $brochure, ':thumb' => $thumbnail, ':feat' => $featured,
                        ':youtube' => trim($_POST['video_url'] ?? ''),
                        ':towers' => (int)($_POST['total_towers'] ?? 0),
                        ':units' => (int)($_POST['total_units'] ?? 0),
                        ':floors' => (int)($_POST['total_floors'] ?? 0),
                        ':area' => trim($_POST['total_area'] ?? ''),
                        ':possession' => $_POST['possession_date'] ?: null,
                        ':launch' => $_POST['launch_date'] ?: null,
                        ':rera' => trim($_POST['rera_number'] ?? ''),
                        ':pstatus' => $_POST['project_status'] ?? 'Upcoming',
                        ':mid' => $_POST['assigned_manager_id'] ?: null,
                        ':id' => $id, ':bid' => $builderId
                    ]);

                    handleGalleryUploads($_FILES['gallery_images'] ?? [], $id, $_POST['gallery_urls'] ?? '');

                    $amenityNames = $_POST['amenity_name'] ?? [];
                    $amenityIcons = $_FILES['amenity_icon'] ?? ['name'=>[],'error'=>[],'tmp_name'=>[]];
                    $amenityIconUrls = $_POST['amenity_icon_url'] ?? [];
                    handleAmenities($amenityNames, $amenityIcons, $id, $amenityIconUrls);

                    $videoUrl = trim($_POST['video_url'] ?? '');
                    if ($videoUrl !== '') {
                        $existingVid = $pdo->prepare("SELECT id FROM project_videos WHERE project_id = ? LIMIT 1");
                        $existingVid->execute([$id]);
                        if ($existingVid->fetch()) {
                            $pdo->prepare("UPDATE project_videos SET video_url = ? WHERE project_id = ?")->execute([$videoUrl, $id]);
                        } else {
                            $pdo->prepare("INSERT INTO project_videos (project_id, video_title, video_url) VALUES (?, ?, ?)")->execute([$id, $name . ' Video', $videoUrl]);
                        }
                    }

                    $pdo->prepare("DELETE FROM project_unit_plans WHERE project_id = ?")->execute([$id]);
                    $unitNames = $_POST['unit_name'] ?? [];
                    $bhkTypes = $_POST['bhk_type'] ?? [];
                    $areas = $_POST['area'] ?? [];
                    $prices = $_POST['price'] ?? [];
                    $facings = $_POST['facing'] ?? [];
                    $bookingAmounts = $_POST['booking_amount'] ?? [];
                    $descriptions = $_POST['unit_description'] ?? [];
                    $planStmt = $pdo->prepare("INSERT INTO project_unit_plans (project_id, unit_name, bhk_type, area, price, facing, booking_amount, description) VALUES (:pid, :un, :bhk, :area, :price, :facing, :ba, :desc)");
                    foreach ($bhkTypes as $i => $bhk) {
                        $un = trim($unitNames[$i] ?? '');
                        $bhk = trim($bhk);
                        $area = trim($areas[$i] ?? '');
                        $price = (float)($prices[$i] ?? 0);
                        $facing = trim($facings[$i] ?? '');
                        $ba = (float)($bookingAmounts[$i] ?? 0);
                        $desc = trim($descriptions[$i] ?? '');
                        if ($un === '' && $bhk === '' && $area === '' && $price <= 0 && $facing === '' && $ba <= 0 && $desc === '') continue;
                        $planStmt->execute([':pid' => $id, ':un' => $un, ':bhk' => $bhk, ':area' => $area, ':price' => $price, ':facing' => $facing ?: null, ':ba' => $ba ?: null, ':desc' => $desc ?: null]);
                    }

                    setFlash('Project updated.', 'success');
                    redirect(BUILDER_URL . 'projects');
                }
            }
        }
    }

    if ($action === 'delete_gallery') {
        $imgId = (int)($_POST['img_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM project_images WHERE id = ? AND image_type = 'gallery' AND project_id IN (SELECT id FROM projects WHERE builder_id = ?)");
        $stmt->execute([$imgId, $builderId]);
        $img = $stmt->fetch();
        if ($img) {
            if (!str_starts_with($img['image'], 'http') && file_exists(__DIR__ . '/../assets/images/projects/' . $img['image'])) @unlink(__DIR__ . '/../assets/images/projects/' . $img['image']);
            $pdo->prepare("DELETE FROM project_images WHERE id = ?")->execute([$imgId]);
        }
        setFlash('Gallery image deleted.', 'success');
        redirect(BUILDER_URL . 'projects');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = ? AND builder_id = ?")->execute([$id, $builderId]);
            $pdo->prepare("UPDATE builders SET total_projects = GREATEST(total_projects - 1, 0) WHERE id = ?")->execute([$builderId]);
            setFlash('Project deleted.', 'success');
            redirect(BUILDER_URL . 'projects');
        }
    }
}

$managers = $pdo->prepare("SELECT * FROM associate_managers WHERE builder_id = ? ORDER BY created_at DESC");
$managers->execute([$builderId]);
$managers = $managers->fetchAll();

$projects = $pdo->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM project_unit_plans WHERE project_id = p.id) AS unit_plans,
        (SELECT COUNT(*) FROM inquiries WHERE project_id = p.id) AS enquiries,
        (SELECT COUNT(*) FROM site_visit_bookings WHERE project_id = p.id) AS visits,
        (SELECT COUNT(*) FROM project_images WHERE project_id = p.id AND image_type = 'gallery') AS gallery_count,
        (SELECT COUNT(*) FROM project_amenities WHERE project_id = p.id) AS amenity_count
    FROM projects p WHERE p.builder_id = ? AND p.deleted_at IS NULL ORDER BY p.created_at DESC
");
$projects->execute([$builderId]);
$projects = $projects->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Projects</h1>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Add Project</button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"><?php echo implode('<br>', array_map('e', $errors)); ?></div>
        <?php endif; ?>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">City</th>
                        <th class="px-4 py-3">Plans</th>
                        <th class="px-4 py-3">Gal.</th>
                        <th class="px-4 py-3">Am.</th>
                        <th class="px-4 py-3">Enq.</th>
                        <th class="px-4 py-3">Visits</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium"><?php echo e($p['project_name']); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo e($p['project_type']); ?></td>
                            <td class="px-4 py-3"><?php echo e($p['city']); ?></td>
                            <td class="px-4 py-3"><?php echo (int)$p['unit_plans']; ?></td>
                            <td class="px-4 py-3">
                                <a href="#" onclick="event.preventDefault();openEdit(<?php echo (int)$p['id']; ?>)" class="hover:text-accent text-xs underline"><?php echo (int)$p['gallery_count']; ?></a>
                            </td>
                            <td class="px-4 py-3"><?php echo (int)$p['amenity_count']; ?></td>
                            <td class="px-4 py-3"><?php echo (int)$p['enquiries']; ?></td>
                            <td class="px-4 py-3"><?php echo (int)$p['visits']; ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $p['status'] === 'published' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : ($p['status'] === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500')); ?>"><?php echo e($p['status']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center">
                                    <button type="button" onclick="openEdit(<?php echo (int)$p['id']; ?>)"
                                        class="text-blue-600 hover:underline text-xs">Edit</button>
                                    <a href="<?php echo BASE_URL . 'project/' . e($p['slug']); ?>" target="_blank" class="text-gray-500 hover:underline text-xs">View</a>
                                    <form method="post" onsubmit="return confirm('Delete this project?')" class="inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button name="action" value="delete" class="text-rose-600 hover:underline text-xs">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($projects)): ?><tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">No projects yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="addModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Add Project</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="create">

            <h4 class="font-bold text-primary border-b pb-1">Basic Info</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Project Name</label><input type="text" name="project_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Type</label><select name="project_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><?php foreach (['Apartment','Plot','Villa','Commercial'] as $t): ?><option><?php echo $t; ?></option><?php endforeach; ?></select></div>
                <div><label class="block text-sm font-medium mb-1">City</label><input type="text" name="city" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">State</label><input type="text" name="state" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Locality</label><input type="text" name="locality" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Project Status</label><select name="project_status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><option>Upcoming</option><option>Ongoing</option><option>Completed</option></select></div>
                <div><label class="block text-sm font-medium mb-1">Assigned Manager</label><select name="assigned_manager_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><option value="">None</option><?php foreach ($managers as $m): ?><option value="<?php echo (int)$m['id']; ?>"><?php echo e($m['full_name']); ?></option><?php endforeach; ?></select></div>
                <div><label class="block text-sm font-medium mb-1">RERA Number</label><input type="text" name="rera_number" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Address & Location</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Latitude</label><input type="text" name="latitude" placeholder="28.6139" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Longitude</label><input type="text" name="longitude" placeholder="77.2090" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Project Details</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Total Towers</label><input type="number" name="total_towers" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Units</label><input type="number" name="total_units" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Floors</label><input type="number" name="total_floors" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Area</label><input type="text" name="total_area" placeholder="e.g. 2.5 Acres" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Possession Date</label><input type="date" name="possession_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Launch Date</label><input type="date" name="launch_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Description</h4>
            <div class="space-y-3">
                <div><label class="block text-sm font-medium mb-1">Overview</label><textarea name="overview" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium mb-1">Pros</label><textarea name="pros" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                    <div><label class="block text-sm font-medium mb-1">Cons</label><textarea name="cons" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Location Details</label><textarea name="location_details" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Legal & Financial</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Legal Details</label><textarea name="legal_details" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Litigation Details</label><textarea name="litigation_details" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Bank Details</label><textarea name="bank_details" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Payment Scheme</label><textarea name="payment_scheme" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Media</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Image URL <span class="text-gray-400">(or upload below)</span></label><input type="text" name="image_url" placeholder="https://..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Thumbnail Image</label><input type="file" name="thumbnail_image" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div><label class="block text-sm font-medium mb-1">Featured Image</label><input type="file" name="featured_image" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Gallery URLs <span class="text-gray-400">(one per line or comma-separated)</span></label><textarea name="gallery_urls" rows="2" placeholder="https://..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Gallery Images <span class="text-gray-400">(or upload multiple)</span></label><input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Brochure <span class="text-gray-400">(PDF only)</span></label><input type="file" name="brochure_file" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Video URL</label><input type="text" name="video_url" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Amenities <span class="text-gray-400 font-normal text-xs">(name + icon)</span></h4>
            <div id="amenityContainer" class="space-y-2">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="flex flex-wrap gap-2 items-start amenity-row">
                        <input name="amenity_name[]" placeholder="Amenity name" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">
                        <input type="text" name="amenity_icon_url[]" placeholder="Icon URL (or upload)" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">
                        <input type="file" name="amenity_icon[]" accept="image/*" class="h-10 text-xs border border-gray-300 rounded-lg px-2 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs">
                        <button type="button" onclick="this.closest('.amenity-row').remove()" class="text-red-400 hover:text-red-600 px-2 h-10">&times;</button>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" onclick="addAmenityRow()" class="text-sm text-accent hover:underline">+ Add amenity</button>

            <h4 class="font-bold text-primary border-b pb-1 mt-4">Unit Plans</h4>
            <div id="unitPlanContainer" class="space-y-3">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="unit-plan-row rounded-lg border border-gray-200 p-3 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-gray-500">Unit <?php echo $i + 1; ?></span>
                            <button type="button" onclick="this.closest('.unit-plan-row').remove()" class="text-red-400 hover:text-red-600 text-sm">&times;</button>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <input name="unit_name[]" placeholder="Unit name" class="h-10 rounded-md border px-3 text-sm">
                            <input name="bhk_type[]" placeholder="BHK type" class="h-10 rounded-md border px-3 text-sm">
                            <input name="area[]" placeholder="Area (sq.ft)" class="h-10 rounded-md border px-3 text-sm">
                            <input name="price[]" type="number" step="0.01" placeholder="Price" class="h-10 rounded-md border px-3 text-sm">
                            <input name="facing[]" placeholder="Facing (e.g. East)" class="h-10 rounded-md border px-3 text-sm">
                            <input name="booking_amount[]" type="number" step="0.01" placeholder="Booking amount" class="h-10 rounded-md border px-3 text-sm">
                        </div>
                        <textarea name="unit_description[]" placeholder="Description (optional)" rows="1" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" onclick="addUnitPlanRow()" class="text-sm text-accent hover:underline">+ Add unit plan</button>

            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Submit for Approval</button>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Edit Project</h3>
            <button onclick="closeEdit()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">

            <h4 class="font-bold text-primary border-b pb-1">Basic Info</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Project Name</label><input type="text" name="project_name" id="edit_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Type</label><select name="project_type" id="edit_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><?php foreach (['Apartment','Plot','Villa','Commercial'] as $t): ?><option><?php echo $t; ?></option><?php endforeach; ?></select></div>
                <div><label class="block text-sm font-medium mb-1">City</label><input type="text" name="city" id="edit_city" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">State</label><input type="text" name="state" id="edit_state" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Locality</label><input type="text" name="locality" id="edit_locality" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Project Status</label><select name="project_status" id="edit_pstatus" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><option>Upcoming</option><option>Ongoing</option><option>Completed</option></select></div>
                <div><label class="block text-sm font-medium mb-1">Assigned Manager</label><select name="assigned_manager_id" id="edit_manager" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><option value="">None</option><?php foreach ($managers as $m): ?><option value="<?php echo (int)$m['id']; ?>"><?php echo e($m['full_name']); ?></option><?php endforeach; ?></select></div>
                <div><label class="block text-sm font-medium mb-1">RERA Number</label><input type="text" name="rera_number" id="edit_rera" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Address & Location</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Address</label><textarea name="address" id="edit_address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Latitude</label><input type="text" name="latitude" id="edit_lat" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Longitude</label><input type="text" name="longitude" id="edit_lng" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Project Details</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Total Towers</label><input type="number" name="total_towers" id="edit_towers" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Units</label><input type="number" name="total_units" id="edit_units" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Floors</label><input type="number" name="total_floors" id="edit_floors" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Total Area</label><input type="text" name="total_area" id="edit_area" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Possession Date</label><input type="date" name="possession_date" id="edit_possession" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Launch Date</label><input type="date" name="launch_date" id="edit_launch" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Description</h4>
            <div class="space-y-3">
                <div><label class="block text-sm font-medium mb-1">Overview</label><textarea name="overview" id="edit_overview" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium mb-1">Pros</label><textarea name="pros" id="edit_pros" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                    <div><label class="block text-sm font-medium mb-1">Cons</label><textarea name="cons" id="edit_cons" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Location Details</label><textarea name="location_details" id="edit_locdet" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Legal & Financial</h4>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Legal Details</label><textarea name="legal_details" id="edit_legal" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Litigation Details</label><textarea name="litigation_details" id="edit_litig" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Bank Details</label><textarea name="bank_details" id="edit_bank" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Payment Scheme</label><textarea name="payment_scheme" id="edit_payment" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Media</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Image URL <span class="text-gray-400">(leave blank to keep existing or upload)</span></label><input type="text" name="image_url" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Thumbnail <span class="text-gray-400">(leave blank to keep)</span></label><input type="file" name="thumbnail_image" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium">
                    <div id="editThumbnailPreview" class="mt-1"></div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Featured <span class="text-gray-400">(leave blank to keep)</span></label><input type="file" name="featured_image" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium">
                    <div id="editFeaturedPreview" class="mt-1"></div>
                </div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Gallery URLs <span class="text-gray-400">(one per line, add more)</span></label><textarea name="gallery_urls" rows="2" placeholder="https://..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></textarea></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Gallery Uploads <span class="text-gray-400">(add more)</span></label><input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Existing Gallery Images <span class="text-gray-400">(click &times; to delete)</span></label>
                    <div id="editGalleryGrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2"></div>
                </div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Brochure <span class="text-gray-400">(PDF)</span></label><input type="file" name="brochure_file" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium"></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Video URL</label><input type="text" name="video_url" id="edit_video" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>

            <h4 class="font-bold text-primary border-b pb-1">Amenities <span class="text-gray-400 font-normal text-xs">(name + icon URL or upload)</span></h4>
            <div id="editAmenityContainer" class="space-y-2"></div>
            <button type="button" onclick="addEditAmenityRow()" class="text-sm text-accent hover:underline">+ Add amenity</button>

            <h4 class="font-bold text-primary border-b pb-1 mt-4">Unit Plans</h4>
            <div id="editUnitPlanContainer" class="space-y-3"></div>
            <button type="button" onclick="addEditUnitPlanRow()" class="text-sm text-accent hover:underline">+ Add unit plan</button>

            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Update Project</button>
        </form>
    </div>
</div>

<script>
function addAmenityRow() {
    var c = document.getElementById('amenityContainer');
    var row = document.createElement('div');
    row.className = 'flex flex-wrap gap-2 items-start amenity-row';
    row.innerHTML = '<input name="amenity_name[]" placeholder="Amenity name" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">' +
        '<input type="text" name="amenity_icon_url[]" placeholder="Icon URL (or upload)" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">' +
        '<input type="file" name="amenity_icon[]" accept="image/*" class="h-10 text-xs border border-gray-300 rounded-lg px-2 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs">' +
        '<button type="button" onclick="this.closest(\'.amenity-row\').remove()" class="text-red-400 hover:text-red-600 px-2 h-10">&times;</button>';
    c.appendChild(row);
}

function addEditAmenityRow(name, icon) {
    var c = document.getElementById('editAmenityContainer');
    var row = document.createElement('div');
    row.className = 'flex flex-wrap gap-2 items-start amenity-row';
    var iconUrl = (icon && icon.indexOf('http') === 0) ? icon : '';
    var iconFile = (icon && icon.indexOf('http') !== 0) ? icon : '';
    var iconHtml = iconFile ? '<span class="text-xs text-gray-400 self-center">current: ' + iconFile + '</span>' : '';
    row.innerHTML = '<input name="amenity_name[]" placeholder="Amenity name" value="' + (name || '') + '" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">' +
        '<input type="text" name="amenity_icon_url[]" placeholder="Icon URL (or upload)" value="' + iconUrl + '" class="flex-1 min-w-[120px] h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-accent">' +
        '<input type="file" name="amenity_icon[]" accept="image/*" class="h-10 text-xs border border-gray-300 rounded-lg px-2 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs">' +
        iconHtml +
        '<button type="button" onclick="this.closest(\'.amenity-row\').remove()" class="text-red-400 hover:text-red-600 px-2 h-10">&times;</button>';
    c.appendChild(row);
}

function openEdit(id) {
    fetch('<?php echo BUILDER_URL; ?>projects?ajax=1&id=' + id)
        .then(r => r.json())
        .then(d => {
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_name').value = d.project_name;
            document.getElementById('edit_type').value = d.project_type;
            document.getElementById('edit_city').value = d.city;
            document.getElementById('edit_state').value = d.state;
            document.getElementById('edit_locality').value = d.locality || '';
            document.getElementById('edit_pstatus').value = d.project_status;
            document.getElementById('edit_manager').value = d.assigned_manager_id || '';
            document.getElementById('edit_rera').value = d.rera_number || '';
            document.getElementById('edit_address').value = d.address || '';
            document.getElementById('edit_lat').value = d.latitude || '';
            document.getElementById('edit_lng').value = d.longitude || '';
            document.getElementById('edit_towers').value = d.total_towers || 0;
            document.getElementById('edit_units').value = d.total_units || 0;
            document.getElementById('edit_floors').value = d.total_floors || 0;
            document.getElementById('edit_area').value = d.total_area || '';
            document.getElementById('edit_possession').value = d.possession_date || '';
            document.getElementById('edit_launch').value = d.launch_date || '';
            document.getElementById('edit_overview').value = d.overview || '';
            document.getElementById('edit_pros').value = d.pros || '';
            document.getElementById('edit_cons').value = d.cons || '';
            document.getElementById('edit_locdet').value = d.location_details || '';
            document.getElementById('edit_legal').value = d.legal_details || '';
            document.getElementById('edit_litig').value = d.litigation_details || '';
            document.getElementById('edit_bank').value = d.bank_details || '';
            document.getElementById('edit_payment').value = d.payment_scheme || '';
            document.getElementById('edit_video').value = d.youtube_video_link || '';

            var thumb = document.getElementById('editThumbnailPreview');
            thumb.innerHTML = '';
            if (d.thumbnail_image) {
                var url = d.thumbnail_image.indexOf('http') === 0 ? d.thumbnail_image : '<?php echo PROJECTS_URL; ?>' + d.thumbnail_image;
                thumb.innerHTML = '<div class="relative inline-block"><img src="' + url + '" class="w-20 h-16 object-cover rounded border" onerror="this.style.display=\'none\'">' +
                    '<label class="block text-xs text-gray-500 mt-0.5">Current thumbnail</label></div>';
            }
            var feat = document.getElementById('editFeaturedPreview');
            feat.innerHTML = '';
            if (d.featured_image) {
                var url2 = d.featured_image.indexOf('http') === 0 ? d.featured_image : '<?php echo PROJECTS_URL; ?>' + d.featured_image;
                feat.innerHTML = '<div class="relative inline-block"><img src="' + url2 + '" class="w-20 h-16 object-cover rounded border" onerror="this.style.display=\'none\'">' +
                    '<label class="block text-xs text-gray-500 mt-0.5">Current featured</label></div>';
            }

            var ac = document.getElementById('editAmenityContainer');
            ac.innerHTML = '';
            (d.amenities || []).forEach(function(a) {
                addEditAmenityRow(a.amenity_name, a.amenity_icon);
            });
            if (!d.amenities || d.amenities.length === 0) addEditAmenityRow();

            var uc = document.getElementById('editUnitPlanContainer');
            uc.innerHTML = '';
            (d.unit_plans || []).forEach(function(u) {
                addEditUnitPlanRow(u);
            });
            if (!d.unit_plans || d.unit_plans.length === 0) addEditUnitPlanRow();

            var gg = document.getElementById('editGalleryGrid');
            gg.innerHTML = '';
            (d.gallery || []).forEach(function(g) {
                if (!g.image) return;
                var div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = '<img src="' + (g.image.indexOf('http') === 0 ? g.image : '<?php echo PROJECTS_URL; ?>' + g.image) + '" class="w-full h-20 object-cover rounded border" onerror="this.src=\'https://via.placeholder.com/100?text=No+Image\'">' +
                    '<button type="button" onclick="deleteGalleryImage(' + g.id + ', this)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">&times;</button>';
                gg.appendChild(div);
            });

            document.getElementById('editModal').classList.remove('hidden');
        });
}

function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}

function deleteGalleryImage(imgId, btn) {
    if (!confirm('Delete this image?')) return;
    var form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="img_id" value="' + imgId + '">';
    document.body.appendChild(form);
    form.submit();
}

function addUnitPlanRow() {
    var c = document.getElementById('unitPlanContainer');
    var row = document.createElement('div');
    row.className = 'unit-plan-row rounded-lg border border-gray-200 p-3 space-y-2';
    row.innerHTML = '<div class="flex justify-between items-center">' +
        '<span class="text-xs font-medium text-gray-500">Unit ' + (c.children.length + 1) + '</span>' +
        '<button type="button" onclick="this.closest(\'.unit-plan-row\').remove()" class="text-red-400 hover:text-red-600 text-sm">&times;</button></div>' +
        '<div class="grid grid-cols-2 md:grid-cols-3 gap-2">' +
        '<input name="unit_name[]" placeholder="Unit name" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="bhk_type[]" placeholder="BHK type" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="area[]" placeholder="Area (sq.ft)" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="price[]" type="number" step="0.01" placeholder="Price" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="facing[]" placeholder="Facing (e.g. East)" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="booking_amount[]" type="number" step="0.01" placeholder="Booking amount" class="h-10 rounded-md border px-3 text-sm"></div>' +
        '<textarea name="unit_description[]" placeholder="Description (optional)" rows="1" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>';
    c.appendChild(row);
}

function addEditUnitPlanRow(data) {
    var c = document.getElementById('editUnitPlanContainer');
    var row = document.createElement('div');
    row.className = 'unit-plan-row rounded-lg border border-gray-200 p-3 space-y-2';
    var d = data || {};
    row.innerHTML = '<div class="flex justify-between items-center">' +
        '<span class="text-xs font-medium text-gray-500">Unit ' + (c.children.length + 1) + '</span>' +
        '<button type="button" onclick="this.closest(\'.unit-plan-row\').remove()" class="text-red-400 hover:text-red-600 text-sm">&times;</button></div>' +
        '<div class="grid grid-cols-2 md:grid-cols-3 gap-2">' +
        '<input name="unit_name[]" value="' + (d.unit_name || '') + '" placeholder="Unit name" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="bhk_type[]" value="' + (d.bhk_type || '') + '" placeholder="BHK type" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="area[]" value="' + (d.area || '') + '" placeholder="Area (sq.ft)" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="price[]" value="' + (d.price || '') + '" type="number" step="0.01" placeholder="Price" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="facing[]" value="' + (d.facing || '') + '" placeholder="Facing (e.g. East)" class="h-10 rounded-md border px-3 text-sm">' +
        '<input name="booking_amount[]" value="' + (d.booking_amount || '') + '" type="number" step="0.01" placeholder="Booking amount" class="h-10 rounded-md border px-3 text-sm"></div>' +
        '<textarea name="unit_description[]" placeholder="Description (optional)" rows="1" class="w-full rounded-md border px-3 py-2 text-sm">' + (d.description || '') + '</textarea>';
    c.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
