<?php
require_once __DIR__ . '/../includes/db_connect.php';

requireLogin();

$errors = [];
$successMessage = '';

try {
    $stmt = $pdo->prepare("SELECT id, full_name, phone, email FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        setFlash('Account not found or inactive.', 'danger');
        redirect(BASE_URL . 'login');
    }
} catch (PDOException $e) {
    $errors[] = 'Failed to load profile. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    $phone = preg_replace('/\s+/', '', $phone);

    if ($fullName === '') $errors[] = 'Full name is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if ($phone === '') $errors[] = 'Phone is required';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email is already used by another account';
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to validate email. Please try again.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?');
            $stmt->execute([$fullName, $phone, $email, $_SESSION['user_id']]);

            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $fullName;

            $successMessage = 'Profile updated successfully.';

            $stmt = $pdo->prepare("SELECT id, full_name, phone, email FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="h-[calc(100vh-80px)] py-12 md:py-20 px-4">
    <div class="max-w-md mx-auto pb-8">
        <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8 text-sm">
            <div class="mb-6">
                <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20 mx-auto mb-2">
                <h3 class="text-xl text-gray-900">My Profile</h3>
                <p class="text-gray-500">Update your account details</p>
            </div>

            <?php if ($successMessage): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo e($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="mb-0 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" required
                           value="<?php echo e($currentUser['full_name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required
                           value="<?php echo e($currentUser['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" required
                           value="<?php echo e($currentUser['phone'] ?? ''); ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-accent transition">
                </div>

                <button type="submit" class="w-full bg-accent hover:bg-accent-700/90 text-black py-3 rounded-lg transition hover:shadow-sm">
                    Save Changes
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="<?php echo BASE_URL; ?>" class="text-gray-500 hover:text-gray-700 inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Website
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
