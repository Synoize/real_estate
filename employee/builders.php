<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireEmployee();

$pageTitle = 'Manage Builders';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_builder') {
        $company = trim($_POST['company_name'] ?? '');
        $name = trim($_POST['builder_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');

        if (empty($company)) $errors[] = 'Company name is required';
        if (empty($name)) $errors[] = 'Builder name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($phone)) $errors[] = 'Phone is required';
        if (empty($password)) $errors[] = 'Password is required';
        elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';

        $document = '';
        if (empty($errors) && isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Document upload failed (error ' . $_FILES['document']['error'] . ')';
            } else {
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    $errors[] = 'Document must be a PDF file';
                } elseif ($_FILES['document']['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Document must be under 5MB';
                } else {
                    $docName = 'bld_' . uniqid() . '.pdf';
                    $dest = __DIR__ . '/../assets/documents/' . $docName;
                    if (!move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
                        $errors[] = 'Failed to save document';
                    } else {
                        $document = $docName;
                    }
                }
            }
        }

        if (empty($errors)) {
            $slug = makeSlug($company) . '-' . substr(uniqid(), -4);
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $uuid = bin2hex(random_bytes(16));
            try {
                $stmt = $pdo->prepare("INSERT INTO builders (uuid, company_name, company_slug, builder_name, email, phone, password, document, city, state, status, created_by_employee) VALUES (:uuid, :company, :slug, :name, :email, :phone, :pass, :doc, :city, :state, 'pending', :emp)");
                $stmt->execute([
                    ':uuid' => $uuid, ':company' => $company, ':slug' => $slug,
                    ':name' => $name, ':email' => $email, ':phone' => $phone,
                    ':pass' => $hashed, ':doc' => $document ?: null, ':city' => $city, ':state' => $state,
                    ':emp' => $_SESSION['employee_id']
                ]);
                setFlash("Builder added as pending. Login: $email / $password", 'success');
                redirect(EMPLOYEE_URL . 'builders');
            } catch (PDOException $e) {
                $errors[] = $e->getCode() == 23000 ? 'Email or phone already exists' : 'Something went wrong';
                error_log($e->getMessage());
            }
        }
    }

    if ($action === 'edit_builder') {
        $id = (int)($_POST['id'] ?? 0);
        $company = trim($_POST['company_name'] ?? '');
        $name = trim($_POST['builder_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($company)) $errors[] = 'Company name is required';
        if (empty($name)) $errors[] = 'Builder name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($phone)) $errors[] = 'Phone is required';

        $existing = $pdo->prepare("SELECT document FROM builders WHERE id = ?");
        $existing->execute([$id]);
        $b = $existing->fetch();
        $document = $b ? $b['document'] : null;

        if (empty($errors) && isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Document upload failed (error ' . $_FILES['document']['error'] . ')';
            } else {
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    $errors[] = 'Document must be a PDF file';
                } elseif ($_FILES['document']['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Document must be under 5MB';
                } else {
                    $docName = 'bld_' . uniqid() . '.pdf';
                    $dest = __DIR__ . '/../assets/documents/' . $docName;
                    if (!move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
                        $errors[] = 'Failed to save document';
                    } else {
                        if ($document) @unlink(__DIR__ . '/../assets/documents/' . $document);
                        $document = $docName;
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                if ($password) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE builders SET company_name = :company, builder_name = :name, email = :email, phone = :phone, city = :city, state = :state, password = :pass, document = :doc WHERE id = :id");
                    $stmt->execute([':company' => $company, ':name' => $name, ':email' => $email, ':phone' => $phone, ':city' => $city, ':state' => $state, ':pass' => $hashed, ':doc' => $document, ':id' => $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE builders SET company_name = :company, builder_name = :name, email = :email, phone = :phone, city = :city, state = :state, document = :doc WHERE id = :id");
                    $stmt->execute([':company' => $company, ':name' => $name, ':email' => $email, ':phone' => $phone, ':city' => $city, ':state' => $state, ':doc' => $document, ':id' => $id]);
                }
                setFlash('Builder updated.', 'success');
                redirect(EMPLOYEE_URL . 'builders');
            } catch (PDOException $e) {
                $errors[] = $e->getCode() == 23000 ? 'Email or phone already exists' : 'Something went wrong';
                error_log($e->getMessage());
            }
        }
    }

    if ($action === 'delete_builder') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE builders SET deleted_at = NOW(), status = 'blocked' WHERE id = ?")->execute([$id]);
        setFlash('Builder deactivated.', 'success');
        redirect(EMPLOYEE_URL . 'builders');
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new = $_POST['status'] ?? 'active';
        $pdo->prepare("UPDATE builders SET status = ? WHERE id = ?")->execute([$new, $id]);
        setFlash('Status updated.', 'success');
        redirect(EMPLOYEE_URL . 'builders');
    }

    if ($action === 'approve_builder') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE builders SET status = 'active', approved_by_admin = (SELECT id FROM admins LIMIT 1), approved_at = NOW() WHERE id = ?")->execute([$id]);
        setFlash('Builder approved.', 'success');
        redirect(EMPLOYEE_URL . 'builders');
    }
}

$builders = $pdo->query("SELECT * FROM builders ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Builders</h1>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Add Builder</button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"><?php echo implode('<br>', array_map('e', $errors)); ?></div>
        <?php endif; ?>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">City</th>
                        <th class="px-4 py-3">Projects</th>
                        <th class="px-4 py-3">Document</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($builders as $b): $projCount = tableCount('projects', "builder_id = ? AND deleted_at IS NULL", [$b['id']]); ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium"><?php echo e($b['company_name']); ?></td>
                            <td class="px-4 py-3"><?php echo e($b['builder_name']); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo e($b['email']); ?></td>
                            <td class="px-4 py-3"><?php echo e($b['phone']); ?></td>
                            <td class="px-4 py-3"><?php echo e($b['city'] ?: '-'); ?></td>
                            <td class="px-4 py-3"><?php echo $projCount; ?></td>
                            <td class="px-4 py-3 max-w-[120px]">
                                <?php if ($b['document']): ?>
                                    <a href="<?php echo BASE_URL . 'assets/documents/' . e($b['document']); ?>" target="_blank" class="text-accent hover:underline text-xs font-medium">View PDF</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No Document</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                    <?php echo $b['status'] === 'active' ? 'bg-green-100 text-green-700' : ($b['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'); ?>">
                                    <?php echo e($b['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 flex-wrap items-center">
                                    <button type="button" onclick="openEditBld(this)"
                                        data-id="<?php echo (int)$b['id']; ?>"
                                        data-company="<?php echo e($b['company_name']); ?>"
                                        data-name="<?php echo e($b['builder_name']); ?>"
                                        data-email="<?php echo e($b['email']); ?>"
                                        data-phone="<?php echo e($b['phone']); ?>"
                                        data-city="<?php echo e($b['city']); ?>"
                                        data-state="<?php echo e($b['state']); ?>"
                                        data-document="<?php echo e($b['document']); ?>"
                                        class="text-blue-600 hover:underline text-xs">Edit</button>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                            <button name="action" value="approve_builder" class="text-green-600 hover:underline text-xs">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <button name="action" value="toggle_status" class="text-xs <?php echo $b['status'] === 'active' ? 'text-gray-500' : 'text-green-600'; ?> hover:underline">
                                            <?php echo $b['status'] === 'active' ? 'Block' : 'Activate'; ?>
                                        </button>
                                        <input type="hidden" name="status" value="<?php echo $b['status'] === 'active' ? 'blocked' : 'active'; ?>">
                                    </form>
                                    <form method="post" onsubmit="return confirm('Deactivate this builder?')">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <button name="action" value="delete_builder" class="text-rose-600 hover:underline text-xs">Deactivate</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($builders)): ?><tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No builders yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="addModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Add Builder</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="add_builder">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" name="company_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Builder Name</label>
                    <input type="text" name="builder_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Password</label>
                <input type="text" name="password" required placeholder="min 6 chars" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document (PDF)</label>
                <input type="file" name="document" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-400">Max 5MB, PDF only</p>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Add Builder</button>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Edit Builder</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="edit_builder">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" name="company_name" id="edit_company" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Builder Name</label>
                    <input type="text" name="builder_name" id="edit_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="edit_phone" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" id="edit_city" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" id="edit_state" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400 font-normal">(leave blank to keep)</span></label>
                <input type="text" name="password" placeholder="min 6 chars" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document (PDF) <span class="text-gray-400 font-normal" id="edit_doc_label">(current: none)</span></label>
                <input type="file" name="document" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-400">Max 5MB, PDF only. Upload a new file to replace existing.</p>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Update Builder</button>
        </form>
    </div>
</div>

<script>
function openEditBld(btn) {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_company').value = btn.dataset.company;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_email').value = btn.dataset.email;
    document.getElementById('edit_phone').value = btn.dataset.phone;
    document.getElementById('edit_city').value = btn.dataset.city;
    document.getElementById('edit_state').value = btn.dataset.state;
    document.getElementById('edit_doc_label').textContent = btn.dataset.document ? '(current: ' + btn.dataset.document + ')' : '(none)';
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
