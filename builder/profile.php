<?php
require_once __DIR__ . '/../includes/app_helpers.php';
requireBuilder();

$pageTitle = 'My Profile';
$builderId = (int)$_SESSION['builder_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $builderName = trim($_POST['builder_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $alternatePhone = trim($_POST['alternate_phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp_number'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $description = trim($_POST['company_description'] ?? '');
        $rera = trim($_POST['rera_number'] ?? '');
        $gst = trim($_POST['gst_number'] ?? '');
        $established = (int)($_POST['established_year'] ?? 0);

        if ($companyName && $email && $phone) {
            $stmt = $pdo->prepare("UPDATE builders SET builder_name=:bn, company_name=:cn, email=:em, phone=:ph, alternate_phone=:ap, whatsapp_number=:wa, website=:web, city=:city, state=:state, address=:addr, company_description=:desc, rera_number=:rera, gst_number=:gst, established_year=:est WHERE id=:id");
            $stmt->execute([
                ':bn' => $builderName, ':cn' => $companyName, ':em' => $email, ':ph' => $phone,
                ':ap' => $alternatePhone, ':wa' => $whatsapp, ':web' => $website, ':city' => $city,
                ':state' => $state, ':addr' => $address, ':desc' => $description, ':rera' => $rera,
                ':gst' => $gst, ':est' => $established ?: null, ':id' => $builderId
            ]);

            $_SESSION['builder_company_name'] = $companyName;
            $_SESSION['builder_name'] = $builderName;
            $_SESSION['builder_email'] = $email;
            $_SESSION['builder_phone'] = $phone;
            $_SESSION['builder_whatsapp'] = $whatsapp;
            $_SESSION['builder_city'] = $city;
            $_SESSION['builder_state'] = $state;

            setFlash('Profile updated.', 'success');
            redirect(BUILDER_URL . 'profile');
        } else {
            $errors[] = 'Company name, email and phone are required';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM builders WHERE id = ?");
        $stmt->execute([$builderId]);
        $row = $stmt->fetch();

        if (!password_verify($current, $row['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } elseif ($new !== $confirm) {
            $errors[] = 'Passwords do not match';
        } else {
            $pdo->prepare("UPDATE builders SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $builderId]);
            setFlash('Password changed.', 'success');
            redirect(BUILDER_URL . 'profile');
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM builders WHERE id = ?");
$stmt->execute([$builderId]);
$builder = $stmt->fetch();

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
                <div><label class="block text-sm font-medium mb-1">Company Name</label><input type="text" name="company_name" value="<?php echo e($builder['company_name']); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Builder Name</label><input type="text" name="builder_name" value="<?php echo e($builder['builder_name']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" value="<?php echo e($builder['email']); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" value="<?php echo e($builder['phone']); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Alternate Phone</label><input type="text" name="alternate_phone" value="<?php echo e($builder['alternate_phone']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">WhatsApp Number</label><input type="text" name="whatsapp_number" value="<?php echo e($builder['whatsapp_number']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Website</label><input type="text" name="website" value="<?php echo e($builder['website']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">Established Year</label><input type="number" name="established_year" value="<?php echo e($builder['established_year']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">RERA Number</label><input type="text" name="rera_number" value="<?php echo e($builder['rera_number']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">GST Number</label><input type="text" name="gst_number" value="<?php echo e($builder['gst_number']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">City</label><input type="text" name="city" value="<?php echo e($builder['city']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
                <div><label class="block text-sm font-medium mb-1">State</label><input type="text" name="state" value="<?php echo e($builder['state']); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"></div>
            </div>
            <div><label class="block text-sm font-medium mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><?php echo e($builder['address']); ?></textarea></div>
            <div><label class="block text-sm font-medium mb-1">Company Description</label><textarea name="company_description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-accent"><?php echo e($builder['company_description']); ?></textarea></div>
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
