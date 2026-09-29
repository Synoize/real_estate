<?php

require_once __DIR__ . '/../includes/db_connect.php';

/* Redirect */

if (isBuilder()) {
    redirect(BUILDER_URL);
}

/* Variables */

$token = trim($_GET['token'] ?? '');

$errors = [];

$builder = null;

/* Validate Token */

if (!empty($token)) {

    try {

        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT
                id,
                company_name,
                email,
                reset_token,
                reset_token_expiry,
                status
            FROM builders
            WHERE reset_token = :token
            AND reset_token_expiry > :now
            AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token,
            ':now'   => $now
        ]);

        $builder = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$builder) {

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
        'Invalid reset request';
}

/* Process Reset */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $builder
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
            'Password must be minimum 6 characters';
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

            $stmt = $pdo->prepare("
                UPDATE builders
                SET
                    password = :password,
                    reset_token = NULL,
                    reset_token_expiry = NULL
                WHERE id = :id
            ");

            $stmt->execute([

                ':password' =>
                $hashedPassword,

                ':id' =>
                $builder['id']
            ]);

            /* Log */

            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs (
                    user_type,
                    user_id,
                    action_title,
                    action_description,
                    ip_address
                ) VALUES (
                    'Builder',
                    :user_id,
                    'Password Reset',
                    'Builder reset account password',
                    :ip
                )
            ");

            $logStmt->execute([

                ':user_id' =>
                $builder['id'],

                ':ip' =>
                $_SERVER['REMOTE_ADDR'] ?? NULL
            ]);

            setFlash(
                'Password reset successful',
                'success'
            );

            redirect(
                BUILDER_URL . 'login'
            );
        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Failed to reset password';
        }
    }
}

$pageTitle = "Builder Reset Password";

require_once __DIR__ . '/includes/head.php';

?>

<body class="min-h-screen flex items-center justify-center px-6 py-12">

    <section class="w-full max-w-sm">

        <div class="text-center mb-8">

            <h1 class="text-2xl font-medium text-gray-900">
                Builder Reset Password
            </h1>

            <p class="text-[12px] text-gray-500 mt-2">
                Create your new password
            </p>

        </div>

        <!-- Errors -->

        <?php if (!empty($errors)) : ?>

            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">

                <ul class="space-y-1">

                    <?php foreach ($errors as $error) : ?>

                        <li class="text-red-600 text-sm">
                            • <?php echo e($error); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <!-- Form -->

        <?php if ($builder) : ?>

            <form method="POST" class="space-y-5">

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        minlength="6"
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

                </div>

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        required
                        minlength="6"
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium">
                    Reset Password
                </button>

            </form>

        <?php endif; ?>

        <div class="mt-6 text-center">

            <a
                href="<?php echo BUILDER_URL; ?>login"
                class="text-sm text-accent hover:underline">
                Back to Login
            </a>

        </div>

    </section>

    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>

</html>