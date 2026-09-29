<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireAdmin();

$pageTitle = 'Manage Ads';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_ad' || $action === 'edit_ad') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $position = $_POST['position'] ?? 'top_banner';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if (empty($title)) $errors[] = 'Title is required';

        $imageDesktop = $_POST['existing_desktop'] ?? '';
        $imageMobile = $_POST['existing_mobile'] ?? '';

        $uploadDir = UPLOADS_PATH . 'ads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!empty($_FILES['image_desktop']['name'])) {
            $ext = pathinfo($_FILES['image_desktop']['name'], PATHINFO_EXTENSION);
            $name = 'ad_d_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_desktop']['tmp_name'], $uploadDir . $name)) {
                $imageDesktop = 'assets/images/uploads/ads/' . $name;
            }
        }
        if (!empty($_FILES['image_mobile']['name'])) {
            $ext = pathinfo($_FILES['image_mobile']['name'], PATHINFO_EXTENSION);
            $name = 'ad_m_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_mobile']['tmp_name'], $uploadDir . $name)) {
                $imageMobile = 'assets/images/uploads/ads/' . $name;
            }
        }

        if (empty($errors)) {
            try {
                if ($action === 'add_ad') {
                    $stmt = $pdo->prepare("INSERT INTO ads (title, link_url, image_desktop, image_mobile, position, sort_order, status) VALUES (:title, :link, :desk, :mob, :pos, :sort, :status)");
                } else {
                    $stmt = $pdo->prepare("UPDATE ads SET title = :title, link_url = :link, image_desktop = :desk, image_mobile = :mob, position = :pos, sort_order = :sort, status = :status WHERE id = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                }
                $stmt->bindValue(':title', $title);
                $stmt->bindValue(':link', $linkUrl);
                $stmt->bindValue(':desk', $imageDesktop);
                $stmt->bindValue(':mob', $imageMobile);
                $stmt->bindValue(':pos', $position);
                $stmt->bindValue(':sort', $sortOrder, PDO::PARAM_INT);
                $stmt->bindValue(':status', $status);
                $stmt->execute();
                setFlash('Ad saved.', 'success');
                redirect(ADMIN_URL . 'ads');
            } catch (PDOException $e) {
                $errors[] = 'Something went wrong';
                error_log($e->getMessage());
            }
        }
    }

    if ($action === 'delete_ad') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
        setFlash('Ad deleted.', 'success');
        redirect(ADMIN_URL . 'ads');
    }
}

$editAd = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editAd = $stmt->fetch(PDO::FETCH_ASSOC);
}

$ads = $pdo->query("SELECT * FROM ads ORDER BY sort_order ASC, created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Ads / Banners</h1>
            </div>
            <a href="<?php echo ADMIN_URL; ?>ads" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white"><?php echo $editAd ? 'Cancel' : 'Add New'; ?></a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"><?php echo implode('<br>', array_map('e', $errors)); ?></div>
        <?php endif; ?>

        <?php if ($editAd || isset($_GET['new'])): ?>
            <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 max-w-2xl">
                <h3 class="text-lg font-bold text-primary mb-4"><?php echo $editAd ? 'Edit Ad' : 'New Ad'; ?></h3>
                <form method="post" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $editAd ? 'edit_ad' : 'add_ad'; ?>">
                    <?php if ($editAd): ?><input type="hidden" name="id" value="<?php echo (int)$editAd['id']; ?>"><?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" required value="<?php echo e($editAd['title'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                        <input type="url" name="link_url" value="<?php echo e($editAd['link_url'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desktop Image</label>
                            <input type="file" name="image_desktop" accept="image/*" class="w-full text-sm">
                            <?php if ($editAd && $editAd['image_desktop']): ?>
                                <input type="hidden" name="existing_desktop" value="<?php echo e($editAd['image_desktop']); ?>">
                                <img src="<?php echo BASE_URL . $editAd['image_desktop']; ?>" class="mt-2 h-16 rounded border">
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Image</label>
                            <input type="file" name="image_mobile" accept="image/*" class="w-full text-sm">
                            <?php if ($editAd && $editAd['image_mobile']): ?>
                                <input type="hidden" name="existing_mobile" value="<?php echo e($editAd['image_mobile']); ?>">
                                <img src="<?php echo BASE_URL . $editAd['image_mobile']; ?>" class="mt-2 h-16 rounded border">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <select name="position" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                                <option value="top_banner" <?php echo ($editAd['position'] ?? '') === 'top_banner' ? 'selected' : ''; ?>>Top Banner</option>
                                <option value="sidebar" <?php echo ($editAd['position'] ?? '') === 'sidebar' ? 'selected' : ''; ?>>Sidebar</option>
                                <option value="between_projects" <?php echo ($editAd['position'] ?? '') === 'between_projects' ? 'selected' : ''; ?>>Between Projects</option>
                                <option value="popup" <?php echo ($editAd['position'] ?? '') === 'popup' ? 'selected' : ''; ?>>Popup</option>
                                <option value="bottom_banner" <?php echo ($editAd['position'] ?? '') === 'bottom_banner' ? 'selected' : ''; ?>>Bottom Banner</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" value="<?php echo (int)($editAd['sort_order'] ?? 0); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                                <option value="active" <?php echo ($editAd['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editAd['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-white">Save</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Desktop</th>
                        <th class="px-4 py-3">Mobile</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Clicks</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ads as $ad): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium"><?php echo e($ad['title']); ?></td>
                            <td class="px-4 py-3">
                                <?php if ($ad['image_desktop']): ?><img src="<?php echo BASE_URL . $ad['image_desktop']; ?>" class="h-10 rounded border"><?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($ad['image_mobile']): ?><img src="<?php echo BASE_URL . $ad['image_mobile']; ?>" class="h-10 rounded border"><?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs"><?php echo e($ad['position']); ?></td>
                            <td class="px-4 py-3"><?php echo (int)$ad['sort_order']; ?></td>
                            <td class="px-4 py-3"><?php echo (int)$ad['clicks']; ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $ad['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo e($ad['status']); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="<?php echo ADMIN_URL; ?>ads?edit=<?php echo (int)$ad['id']; ?>" class="text-xs text-accent hover:underline">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this ad?')">
                                        <input type="hidden" name="id" value="<?php echo (int)$ad['id']; ?>">
                                        <button name="action" value="delete_ad" class="text-xs text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ads)): ?><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No ads yet. <a href="<?php echo ADMIN_URL; ?>ads?new=1" class="text-accent hover:underline">Add one</a>.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
