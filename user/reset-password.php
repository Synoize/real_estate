<?php
/**
 * Reset Password Page
 */

require_once __DIR__ . '/../includes/db_connect.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$errors = [];
$success = false;
$user = null;
$debug = '';  // Debug info

// Validate token
if (!empty($token)) {
    try {
        // Use PHP time to avoid timezone issues with MySQL NOW()
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > ? AND status = 1");
        $stmt->execute([$token, $now]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Check if token exists at all
            $checkStmt = $pdo->prepare("SELECT id, reset_expires, reset_token, status FROM users WHERE reset_token = ?");
            $checkStmt->execute([$token]);
            $checkUser = $checkStmt->fetch();
            
            if ($checkUser) {
                $debug .= "Token found. Expires: " . $checkUser['reset_expires'] . " | PHP Time: " . date('Y-m-d H:i:s');
                $isExpired = strtotime($checkUser['reset_expires']) < time();
                $debug .= " | Expired: " . ($isExpired ? 'YES' : 'NO');
                
                if ($isExpired) {
                    $errors[] = 'Reset token has expired. Tokens are valid for 1 hour only. Please request a new password reset.';
                } elseif ($checkUser['status'] != 1) {
                    $errors[] = 'Your account is inactive. Please contact support.';
                } else {
                    $errors[] = 'Token validation failed. Please request a new password reset.';
                }
            } else {
                $errors[] = 'Invalid reset token. Please request a new password reset.';
                $debug .= "Token not found in database.";
            }
        }
    } catch (PDOException $e) {
        // Check if it's a missing column error
        if (strpos($e->getMessage(), 'reset_token') !== false || strpos($e->getMessage(), 'reset_expires') !== false) {
            $errors[] = 'Database setup incomplete. Please run: ALTER TABLE users ADD COLUMN reset_token VARCHAR(255), ADD COLUMN reset_expires DATETIME;';
        } else {
            $errors[] = 'Database error. Please try again.';
        }
    }
} else {
    $errors[] = 'No reset token provided.';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validation
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            // Hash new password and clear reset token
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            $success = true;
            setFlash('Password reset successful! Please login with your new password.', 'success');
            redirect(BASE_URL . 'user/login.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to reset password. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="h-[calc(100vh-80px)] py-12 md:py-20 px-4">
    <div class="max-w-md mx-auto">
        <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8 text-sm">
            <div class="mb-6">
                <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20 mx-auto mb-2">
                <h3 class="text-xl text-gray-900">Reset Password</h3>
                <p class="text-sm text-gray-500">Enter your new password</p>
            </div>
                
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="mb-0 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($debug): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-6 text-sm">
                <strong>Debug:</strong> <?php echo e($debug); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($user): ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password (min 6 characters)</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-accent transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-accent transition">
                </div>
                
                <button type="submit" class="w-full bg-accent hover:bg-accent-700/90 text-black py-3 rounded-lg transition hover:shadow-sm">
                    <i class="fas fa-save mr-2"></i>Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-6">
                <a href="<?php echo BASE_URL; ?>user/login.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

