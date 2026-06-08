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
            AND reset_token_expiry > NOW()
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token
        ]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {

            $errors[] =
                'Invalid or expired reset token';
        }

        elseif ($admin['status'] !== 'active') {

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
    }

    elseif (strlen($password) < 6) {

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
                ADMIN_URL . 'login.php'
            );

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Failed to reset password';
        }
    }
}

$pageTitle = "Admin Reset Password";

require_once __DIR__ . '/../includes/header.php';

?>

<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-md">

        <div class="bg-white border rounded-2xl shadow-sm p-6 md:p-8">

            <div class="text-center mb-8">

                <img
                    src="<?php echo ASSETS_URL; ?>public/logo.png"
                    class="h-20 mx-auto mb-4"
                    alt="Logo"
                >

                <h1 class="text-2xl font-bold">
                    Admin Reset Password
                </h1>

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

                    <input
                        type="password"
                        name="password"
                        placeholder="New Password"
                        required
                        class="w-full h-12 px-4 border rounded-xl"
                    >

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm Password"
                        required
                        class="w-full h-12 px-4 border rounded-xl"
                    >

                    <button
                        type="submit"
                        class="w-full h-12 rounded-xl bg-accent"
                    >
                        Reset Password
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</section>