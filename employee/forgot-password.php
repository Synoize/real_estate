<?php

require_once __DIR__ . '/../includes/db_connect.php';

$errors = [];
$success = '';
$resetLink = '';

$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier = trim($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $errors[] = 'Employee ID or email is required';
    }

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT id, employee_code, email, full_name
                FROM employees
                WHERE (employee_code = :code OR email = :email)
                  AND status = 'active'
                LIMIT 1
            ");

            $stmt->execute([
                ':code' => $identifier,
                ':email' => $identifier
            ]);

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {

                $errors[] = 'No active employee found with that ID or email';
            } else {

                $token = bin2hex(random_bytes(32));

                $expiry = date(
                    'Y-m-d H:i:s',
                    strtotime('+1 hour')
                );

                $updateStmt = $pdo->prepare("
                    UPDATE employees
                    SET reset_token = :token,
                        reset_token_expiry = :expiry
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':token'  => $token,
                    ':expiry' => $expiry,
                    ':id'     => $employee['id']
                ]);

                $resetLink = EMPLOYEE_URL . 'reset-password?token=' . $token;

                $success = 'Reset link generated for ' . e($employee['full_name']);
            }
        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] = 'Something went wrong';
        }
    }
}

$pageTitle = "Employee Forgot Password";

require_once __DIR__ . '/includes/head.php';

?>

<body class="min-h-screen flex items-center justify-center px-6 py-12">

    <section class="w-full max-w-sm">

        <div class="text-center mb-8">

            <h1 class="text-2xl font-medium text-gray-900">
                Forgot Password
            </h1>

            <p class="text-[12px] text-gray-500 mt-2">
                Enter your Employee ID or registered email
            </p>

        </div>

        <?php if (!empty($success)) : ?>

            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 space-y-2">

                <p class="text-green-600 text-sm">
                    <?php echo htmlspecialchars($success); ?>
                </p>

                <a href="<?php echo htmlspecialchars($resetLink); ?>"
                    class="block text-sm text-green-700 font-medium break-all hover:underline">
                    <?php echo htmlspecialchars($resetLink); ?>
                </a>

                <p class="text-xs text-green-500">This link expires in 1 hour.</p>

            </div>

        <?php endif; ?>

        <?php if (!empty($errors)) : ?>

            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">

                <ul class="space-y-1">

                    <?php foreach ($errors as $error) : ?>

                        <li class="text-red-600 text-sm">
                            • <?php echo htmlspecialchars($error); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <form method="POST" class="space-y-3">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Employee ID or Email
                </label>

                <input type="text" name="identifier"
                    value="<?php echo htmlspecialchars($identifier); ?>"
                    required
                    class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

            </div>

            <button type="submit"
                class="w-full h-12 rounded-xl bg-accent text-black font-medium hover:opacity-90 transition">
                Send Reset Link
            </button>

        </form>

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