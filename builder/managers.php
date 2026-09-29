<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'Associate Managers';
$builderId = (int)$_SESSION['builder_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp_number'] ?? '') ?: $phone;
        $desig = trim($_POST['designation'] ?? 'Associate Manager');
        $password = trim($_POST['password'] ?? 'Password@123');

        if ($name && $email && $phone) {
            $managerCode = makeCode('MGR');
            $stmt = $pdo->prepare("INSERT INTO associate_managers (uuid, builder_id, manager_code, full_name, email, phone, whatsapp_number, designation, password, status, created_by) VALUES (UUID(), :bid, :code, :name, :email, :phone, :wa, :desig, :pass, 'active', :cb)");
            $stmt->execute([
                ':bid' => $builderId, ':code' => $managerCode, ':name' => $name,
                ':email' => $email, ':phone' => $phone, ':wa' => $whatsapp,
                ':desig' => $desig, ':pass' => password_hash($password, PASSWORD_DEFAULT),
                ':cb' => $builderId
            ]);
            setFlash("Manager created. Code: $managerCode, Password: $password", 'success');
            redirect(BUILDER_URL . 'managers');
        } else {
            $errors[] = 'Name, email and phone are required';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp_number'] ?? '') ?: $phone;
        $desig = trim($_POST['designation'] ?? 'Associate Manager');
        $password = trim($_POST['password'] ?? '');

        if ($id && $name && $email && $phone) {
            if ($password) {
                $pdo->prepare("UPDATE associate_managers SET full_name=:name, email=:email, phone=:phone, whatsapp_number=:wa, designation=:desig, password=:pass WHERE id=:id AND builder_id=:bid")
                    ->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':wa' => $whatsapp, ':desig' => $desig, ':pass' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id, ':bid' => $builderId]);
            } else {
                $pdo->prepare("UPDATE associate_managers SET full_name=:name, email=:email, phone=:phone, whatsapp_number=:wa, designation=:desig WHERE id=:id AND builder_id=:bid")
                    ->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':wa' => $whatsapp, ':desig' => $desig, ':id' => $id, ':bid' => $builderId]);
            }
            setFlash('Manager updated.', 'success');
            redirect(BUILDER_URL . 'managers');
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new = $_POST['status'] ?? 'active';
        if ($id) {
            $pdo->prepare("UPDATE associate_managers SET status = ? WHERE id = ? AND builder_id = ?")->execute([$new, $id, $builderId]);
            setFlash('Status updated.', 'success');
            redirect(BUILDER_URL . 'managers');
        }
    }
}

$managers = $pdo->prepare("
    SELECT m.*, (SELECT COUNT(*) FROM projects WHERE assigned_manager_id = m.id AND deleted_at IS NULL) AS project_count
    FROM associate_managers m WHERE m.builder_id = ? ORDER BY m.created_at DESC
");
$managers->execute([$builderId]);
$managers = $managers->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Associate Managers</h1>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Add Manager</button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"><?php echo implode('<br>', array_map('e', $errors)); ?></div>
        <?php endif; ?>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">WhatsApp</th>
                        <th class="px-4 py-3">Designation</th>
                        <th class="px-4 py-3">Projects</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managers as $m): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-mono text-xs"><?php echo e($m['manager_code']); ?></td>
                            <td class="px-4 py-3 font-medium"><?php echo e($m['full_name']); ?></td>
                            <td class="px-4 py-3"><?php echo e($m['email']); ?></td>
                            <td class="px-4 py-3"><?php echo e($m['phone']); ?></td>
                            <td class="px-4 py-3"><?php echo e($m['whatsapp_number'] ?: '-'); ?></td>
                            <td class="px-4 py-3"><?php echo e($m['designation']); ?></td>
                            <td class="px-4 py-3"><?php echo (int)$m['project_count']; ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $m['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo e($m['status']); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center">
                                    <button type="button" onclick="openEdit(this)"
                                        data-id="<?php echo (int)$m['id']; ?>"
                                        data-name="<?php echo e($m['full_name']); ?>"
                                        data-email="<?php echo e($m['email']); ?>"
                                        data-phone="<?php echo e($m['phone']); ?>"
                                        data-whatsapp="<?php echo e($m['whatsapp_number']); ?>"
                                        data-desig="<?php echo e($m['designation']); ?>"
                                        class="text-blue-600 hover:underline text-xs">Edit</button>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                        <button name="action" value="toggle_status" class="text-xs <?php echo $m['status'] === 'active' ? 'text-gray-500' : 'text-green-600'; ?> hover:underline">
                                            <?php echo $m['status'] === 'active' ? 'Block' : 'Activate'; ?>
                                        </button>
                                        <input type="hidden" name="status" value="<?php echo $m['status'] === 'active' ? 'blocked' : 'active'; ?>">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($managers)): ?><tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No managers yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="addModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Add Manager</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Full Name</label><input type="text" name="full_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">WhatsApp</label><input type="text" name="whatsapp_number" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Designation</label><input type="text" name="designation" value="Associate Manager" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Default Password</label><input type="text" name="password" value="Password@123" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Create Manager</button>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Edit Manager</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Full Name</label><input type="text" name="full_name" id="edit_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" id="edit_email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" id="edit_phone" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">WhatsApp</label><input type="text" name="whatsapp_number" id="edit_whatsapp" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Designation</label><input type="text" name="designation" id="edit_desig" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">New Password <span class="text-gray-400">(optional)</span></label><input type="text" name="password" placeholder="Leave blank to keep" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Update Manager</button>
        </form>
    </div>
</div>

<script>
function openEdit(btn) {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_email').value = btn.dataset.email;
    document.getElementById('edit_phone').value = btn.dataset.phone;
    document.getElementById('edit_whatsapp').value = btn.dataset.whatsapp;
    document.getElementById('edit_desig').value = btn.dataset.desig;
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
