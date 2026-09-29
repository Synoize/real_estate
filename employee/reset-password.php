<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (isEmployee()) {
    redirect(EMPLOYEE_URL);
}

$token = trim($_GET['token'] ?? '');

$errors = [];

$employee = null;

if (!empty($token)) {

    try {

        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT id, full_name, email,
                   reset_token, reset_token_expiry, status
            FROM employees
            WHERE reset_token = :token
              AND reset_token_expiry > :now
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ':token' => $token,
            ':now'   => $now
        ]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            $errors[] = 'Invalid or expired reset token';
        }
    } catch (PDOException $e) {

        error_log($e->getMessage());

        $errors[] = 'Something went wrong';
    }
} else {

    $errors[] = 'Invalid reset request';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee) {

    $password = trim($_POST['password'] ?? '');

    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be minimum 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {

        try {

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                UPDATE employees
                SET password = :password,
                    reset_token = NULL,
                    reset_token_expiry = NULL
                WHERE id = :id
            ");

            $stmt->execute([
                ':password' => $hashedPassword,
                ':id'       => $employee['id']
            ]);

            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs (user_type, user_id, action_title, action_description, ip_address)
                VALUES ('Employee', :user_id, 'Password Reset', 'Employee reset account password', :ip)
            ");

            $logStmt->execute([
                ':user_id' => $employee['id'],
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            setFlash('Password reset successful. Please login with your new password.', 'success');

            redirect(EMPLOYEE_URL . 'login');

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] = 'Failed to reset password';
        }
    }
}

$pageTitle = "Employee Reset Password";

require_once __DIR__ . '/includes/head.php';

?>

<body class="min-h-screen flex items-center justify-center px-6 py-12">

    <section class="w-full max-w-sm">

        <div class="text-center mb-8">

            <h1 class="text-2xl font-medium text-gray-900">
                Reset Password
            </h1>

            <p class="text-[12px] text-gray-500 mt-2">
                Create your new password
            </p>

        </div>

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

        <?php if ($employee) : ?>

            <form method="POST" class="space-y-5">

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>

                    <input type="password" name="password" required minlength="6"
                           class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

                </div>

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password
                    </label>

                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

                </div>

                <button type="submit"
                        class="w-full h-12 rounded-xl bg-accent text-black font-medium hover:opacity-90 transition">
                    Reset Password
                </button>

            </form>

        <?php endif; ?>

        <div class="mt-6 text-center">

            <a href="<?php echo EMPLOYEE_URL; ?>login"
               class="text-sm text-accent hover:underline">
                Back to Login
            </a>

        </div>

    </section>

    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>

</html>
