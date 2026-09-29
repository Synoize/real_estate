<?php

require_once __DIR__ . '/../includes/app_helpers.php';
requireAdmin();

$pageTitle = 'Manage Employees';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_employee') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($fullName)) $errors[] = 'Full name is required';
        if (empty($email)) $errors[] = 'Email is required';
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
                    $docName = 'emp_' . uniqid() . '.pdf';
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
            $code = makeCode('EMP');
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO employees (uuid, employee_code, full_name, email, phone, department, designation, password, document, created_by, status) VALUES (UUID(), :code, :name, :email, :phone, :dept, :desig, :pass, :doc, :by, 'active')");
                $stmt->execute([
                    ':code' => $code, ':name' => $fullName, ':email' => $email,
                    ':phone' => $phone, ':dept' => $department, ':desig' => $designation,
                    ':pass' => $hashed, ':doc' => $document ?: null, ':by' => $_SESSION['admin_id']
                ]);
                setFlash("Employee added. Code: $code, Password: $password", 'success');
                redirect(ADMIN_URL . 'employees');
            } catch (PDOException $e) {
                $errors[] = $e->getCode() == 23000 ? 'Email already exists' : 'Something went wrong';
                error_log($e->getMessage());
            }
        }
    }

    if ($action === 'edit_employee') {
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($fullName)) $errors[] = 'Full name is required';
        if (empty($email)) $errors[] = 'Email is required';

        $existing = $pdo->prepare("SELECT document FROM employees WHERE id = ?");
        $existing->execute([$id]);
        $emp = $existing->fetch();
        $document = $emp ? $emp['document'] : null;

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
                    $docName = 'emp_' . uniqid() . '.pdf';
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
                    $stmt = $pdo->prepare("UPDATE employees SET full_name = :name, email = :email, phone = :phone, department = :dept, designation = :desig, password = :pass, document = :doc WHERE id = :id");
                    $stmt->execute([':name' => $fullName, ':email' => $email, ':phone' => $phone, ':dept' => $department, ':desig' => $designation, ':pass' => $hashed, ':doc' => $document, ':id' => $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE employees SET full_name = :name, email = :email, phone = :phone, department = :dept, designation = :desig, document = :doc WHERE id = :id");
                    $stmt->execute([':name' => $fullName, ':email' => $email, ':phone' => $phone, ':dept' => $department, ':desig' => $designation, ':doc' => $document, ':id' => $id]);
                }
                setFlash('Employee updated.', 'success');
                redirect(ADMIN_URL . 'employees');
            } catch (PDOException $e) {
                $errors[] = $e->getCode() == 23000 ? 'Email already exists' : 'Something went wrong';
                error_log($e->getMessage());
            }
        }
    }

    if ($action === 'delete_employee') {
        $id = (int)($_POST['id'] ?? 0);
        $emp = $pdo->prepare("SELECT document FROM employees WHERE id = ?");
        $emp->execute([$id]);
        $row = $emp->fetch();
        if ($row && $row['document']) @unlink(__DIR__ . '/../assets/documents/' . $row['document']);
        $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);
        setFlash('Employee deleted.', 'success');
        redirect(ADMIN_URL . 'employees');
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new = $_POST['status'] ?? 'active';
        $pdo->prepare("UPDATE employees SET status = ? WHERE id = ?")->execute([$new, $id]);
        setFlash('Status updated.', 'success');
        redirect(ADMIN_URL . 'employees');
    }
}

$employees = $pdo->query("
    SELECT e.*, a.full_name AS created_by_name
    FROM employees e
    LEFT JOIN admins a ON a.id = e.created_by
    ORDER BY e.created_at DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[1400px] mx-auto">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase text-accent">Management</p>
                <h1 class="text-3xl font-semibold text-primary">Employees</h1>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="rounded-md bg-primary px-4 py-3 text-sm font-bold text-white">Add Employee</button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 font-medium"><?php echo e($success); ?></div>
        <?php endif; ?>
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
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Document</th>
                        <th class="px-4 py-3">Added By</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-mono text-xs"><?php echo e($emp['employee_code']); ?></td>
                            <td class="px-4 py-3 font-medium"><?php echo e($emp['full_name']); ?></td>
                            <td class="px-4 py-3"><?php echo e($emp['email']); ?></td>
                            <td class="px-4 py-3"><?php echo e($emp['phone'] ?: '-'); ?></td>
                            <td class="px-4 py-3"><?php echo e($emp['department'] ?: '-'); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $emp['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo e($emp['status']); ?></span>
                            </td>
                            <td class="px-4 py-3 max-w-[120px]">
                                <?php if ($emp['document']): ?>
                                    <a href="<?php echo BASE_URL . 'assets/documents/' . e($emp['document']); ?>" target="_blank" class="text-accent hover:underline text-xs font-medium">View PDF</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No Document</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo e($emp['created_by_name'] ?? '-'); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center">
                                    <button type="button" onclick="openEditEmp(this)"
                                        data-id="<?php echo (int)$emp['id']; ?>"
                                        data-name="<?php echo e($emp['full_name']); ?>"
                                        data-email="<?php echo e($emp['email']); ?>"
                                        data-phone="<?php echo e($emp['phone']); ?>"
                                        data-department="<?php echo e($emp['department']); ?>"
                                        data-designation="<?php echo e($emp['designation']); ?>"
                                        data-document="<?php echo e($emp['document']); ?>"
                                        class="text-blue-600 hover:underline text-xs">Edit</button>
                                    <form method="post" onsubmit="return confirm('Delete this employee?')">
                                        <input type="hidden" name="id" value="<?php echo (int)$emp['id']; ?>">
                                        <button name="action" value="delete_employee" class="text-rose-600 hover:underline text-xs">Delete</button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo (int)$emp['id']; ?>">
                                        <button name="action" value="toggle_status" class="text-xs <?php echo $emp['status'] === 'active' ? 'text-gray-500' : 'text-green-600'; ?> hover:underline">
                                            <?php echo $emp['status'] === 'active' ? 'Block' : 'Activate'; ?>
                                        </button>
                                        <input type="hidden" name="status" value="<?php echo $emp['status'] === 'active' ? 'blocked' : 'active'; ?>">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($employees)): ?><tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No employees yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="addModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Add Employee</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="add_employee">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <input type="text" name="department" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Password</label>
                    <input type="text" name="password" required placeholder="min 6 chars" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document (PDF)</label>
                <input type="file" name="document" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-400">Max 5MB, PDF only</p>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Add Employee</button>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-primary">Edit Employee</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="edit_employee">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="edit_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <input type="text" name="department" id="edit_department" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" id="edit_designation" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400 font-normal">(leave blank to keep)</span></label>
                    <input type="text" name="password" placeholder="min 6 chars" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document (PDF) <span class="text-gray-400 font-normal" id="edit_doc_label">(current: none)</span></label>
                <input type="file" name="document" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-400">Max 5MB, PDF only. Upload a new file to replace existing.</p>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary py-3 text-sm font-bold text-white">Update Employee</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('#addModal form').forEach(f => {
    f.addEventListener('submit', function() {
        document.getElementById('addModal').classList.add('hidden');
    });
});

function openEditEmp(btn) {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_email').value = btn.dataset.email;
    document.getElementById('edit_phone').value = btn.dataset.phone;
    document.getElementById('edit_department').value = btn.dataset.department;
    document.getElementById('edit_designation').value = btn.dataset.designation;
    document.getElementById('edit_doc_label').textContent = btn.dataset.document ? '(current: ' + btn.dataset.document + ')' : '(none)';
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
