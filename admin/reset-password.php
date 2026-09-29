<?php

require_once __DIR__ . '/../includes/db_connect.php';

/* Redirect */

if (isAdmin()) {
    redirect(ADMIN_URL);
}

/* Variables */

$token = trim($_GET['token'] ?? '');

$errors = [];

$admin = null;

/* Validate Token */

if (!empty($token)) {

    try {

        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                reset_token,
                reset_token_expiry,
                status
            FROM admins
            WHERE reset_token = :token
            AND reset_token_expiry > :now
            AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token,
            ':now'   => $now
        ]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {

            $errors[] =
                'Invalid or expired reset token';
        }
    } catch (PDOException $e) {

        error_log($e->getMessage());

        $errors[] =
            'Something went wrong';
    }
} else {

    $errors[] =
        'Invalid password reset request';
}

/* Process Reset */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $admin
) {

    $password =
        trim($_POST['password'] ?? '');

    $confirmPassword =
        trim($_POST['confirm_password'] ?? '');

    /* Validation */

    if (empty($password)) {

        $errors[] =
            'Password is required';
    } elseif (strlen($password) < 6) {

        $errors[] =
            'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {

        $errors[] =
            'Passwords do not match';
    }

    /* Update Password */

    if (empty($errors)) {

        try {

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_BCRYPT
                );

            $updateStmt = $pdo->prepare("
                UPDATE admins
                SET
                    password = :password,
                    reset_token = NULL,
                    reset_token_expiry = NULL
                WHERE id = :id
            ");

            $updateStmt->execute([

                ':password' =>
                $hashedPassword,

                ':id' =>
                $admin['id']
            ]);

            setFlash(
                'Password reset successful',
                'success'
            );

            redirect(
                ADMIN_URL . 'login'
            );
        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Failed to reset password';
        }
    }
}

$pageTitle = "Admin Reset Password";

require_once __DIR__ . '/includes/head.php';

?>

<body class="min-h-screen flex items-center justify-center px-6 py-12">

    <section class="w-full max-w-sm">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-medium text-gray-900">
                Reset Password
            </h1>

            <p class="text-[12px] text-gray-500 mt-2">Enter your new password</p>
        </div>

        <?php if (!empty($errors)) : ?>

            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">

                <?php foreach ($errors as $error) : ?>

                    <p class="text-sm text-red-600">
                        • <?php echo e($error); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <?php if ($admin) : ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password (min 6 characters)</label>
                    <input type="password"
                        name="password"
                        placeholder="New Password"
                        required
                        minlength="6"
                        class="w-full h-12 px-4 border rounded-xl outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password"
                        name="confirm_password"
                        placeholder="Confirm Password"
                        required
                        minlength="6"
                        class="w-full h-12 px-4 border rounded-xl outline-none focus:border-accent transition">
                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium">
                    Reset Password
                </button>

            </form>

        <?php endif; ?>

    </section>

    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>

</html>