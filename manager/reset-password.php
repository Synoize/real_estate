<?php

require_once __DIR__ . '/../includes/db_connect.php';

/* Redirect */

if (isManager()) {
    redirect(MANAGER_URL);
}

/* Variables */

$token = trim($_GET['token'] ?? '');

$errors = [];

$manager = null;

/* Validate Token */

if (!empty($token)) {

    try {

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                reset_token,
                reset_token_expiry,
                status
            FROM associate_managers
            WHERE reset_token = :token
            AND reset_token_expiry > NOW()
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token
        ]);

        $manager = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$manager) {

            $errors[] =
                'Invalid or expired reset token';
        }

        elseif ($manager['status'] !== 'active') {

            $errors[] =
                'Your account is inactive';
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
    && $manager
) {

    $password =
        trim($_POST['password'] ?? '');

    $confirmPassword =
        trim($_POST['confirm_password'] ?? '');

    /* Validation */

    if (empty($password)) {

        $errors[] =
            'Password is required';
    }

    elseif (strlen($password) < 6) {

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
                UPDATE associate_managers
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
                    $manager['id']
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
                    'Manager',
                    :user_id,
                    'Password Reset',
                    'Manager reset account password',
                    :ip
                )
            ");

            $logStmt->execute([

                ':user_id' =>
                    $manager['id'],

                ':ip' =>
                    $_SERVER['REMOTE_ADDR'] ?? NULL
            ]);

            setFlash(
                'Password reset successful',
                'success'
            );

            redirect(
                MANAGER_URL . 'login'
            );

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Failed to reset password';
        }
    }
}

$pageTitle = "Manager Reset Password";

require_once __DIR__ . '/../includes/header.php';

?>

<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-md">

        <div class="bg-white border rounded-2xl shadow-sm p-6 md:p-8">

            <!-- Logo -->

            <div class="text-center mb-8">

                <img
                    src="<?php echo ASSETS_URL; ?>public/logo.png"
                    class="h-20 mx-auto mb-4"
                    alt="Logo"
                >

                <h1 class="text-2xl font-bold text-gray-900">
                    Manager Reset Password
                </h1>

                <p class="text-sm text-gray-500 mt-1">
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

            <?php if ($manager) : ?>

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
                            class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent"
                        >

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
                            class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent"
                        >

                    </div>

                    <button
                        type="submit"
                        class="w-full h-12 rounded-xl bg-accent text-black font-medium"
                    >
                        Reset Password
                    </button>

                </form>

            <?php endif; ?>

            <div class="mt-6 text-center">

                <a
                    href="<?php echo MANAGER_URL; ?>login"
                    class="text-sm text-accent hover:underline"
                >
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</section>
