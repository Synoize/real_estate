<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireManager();

$pageTitle = 'My Profile';
$managerId = (int)$_SESSION['manager_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp_number'] ?? '');
        $designation = trim($_POST['designation'] ?? '');

        if ($fullName && $email) {
            $stmt = $pdo->prepare("UPDATE associate_managers SET full_name=:fn, email=:em, phone=:ph, whatsapp_number=:wa, designation=:des WHERE id=:id");
            $stmt->execute([
                ':fn' => $fullName, ':em' => $email, ':ph' => $phone,
                ':wa' => $whatsapp, ':des' => $designation, ':id' => $managerId
            ]);

            $_SESSION['manager_name'] = $fullName;
            $_SESSION['manager_email'] = $email;
            $_SESSION['manager_phone'] = $phone;
            $_SESSION['manager_whatsapp'] = $whatsapp;
            $_SESSION['manager_designation'] = $designation;

            setFlash('Profile updated.', 'success');
            redirect(MANAGER_URL . 'profile');
        } else {
            $errors[] = 'Name and email are required';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM associate_managers WHERE id = ?");
        $stmt->execute([$managerId]);
        $row = $stmt->fetch();

        if (!password_verify($current, $row['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } elseif ($new !== $confirm) {
            $errors[] = 'Passwords do not match';
        } else {
            $pdo->prepare("UPDATE associate_managers SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $managerId]);
            setFlash('Password changed.', 'success');
            redirect(MANAGER_URL . 'profile');
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM associate_managers WHERE id = ?");
$stmt->execute([$managerId]);
$manager = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<section class="min-h-screen bg-gray-50 p-6 mt-20">
    <div class="max-w-[900px] mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-xs uppercase text-accent">Settings</p>
                <h1 class="text-3xl font-semibold text-primary">My Profile</h1>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"><?php echo implode('<br>', array_map('e', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" class="rounded-lg border bg-white p-6 space-y-4">
            <input type="hidden" name="action" value="update_profile">
            <h2 class="text-lg font-bold text-primary">General Info</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Full Name</label><input type="text" name="full_name" value="<?php echo e($manager['full_name']); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" value="<?php echo e($manager['email']); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" value="<?php echo e($manager['phone']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">WhatsApp Number</label><input type="text" name="whatsapp_number" value="<?php echo e($manager['whatsapp_number']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Designation</label><input type="text" name="designation" value="<?php echo e($manager['designation']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Manager Code</label><input type="text" value="<?php echo e($manager['manager_code']); ?>" disabled class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500"></div>
            </div>
            <button type="submit" class="rounded-lg bg-primary px-6 py-3 text-sm font-bold text-white">Update Profile</button>
        </form>

        <form method="post" class="mt-6 rounded-lg border bg-white p-6 space-y-4">
            <input type="hidden" name="action" value="change_password">
            <h2 class="text-lg font-bold text-primary">Change Password</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium mb-1">Current Password</label><input type="password" name="current_password" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">New Password</label><input type="password" name="new_password" required minlength="6" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Confirm Password</label><input type="password" name="confirm_password" required minlength="6" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>
            <button type="submit" class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-primary">Change Password</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
