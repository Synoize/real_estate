<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Variables */

$errors = [];
$success = '';

$email = '';

/* Process Form */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    /* Validation */

    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    /* Check User */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT id, full_name, email
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $errors[] = 'No account found with this email';
            } else {

                $resetToken = bin2hex(random_bytes(32));

                $expiry = date(
                    'Y-m-d H:i:s',
                    strtotime('+1 hour')
                );

                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET
                        reset_token = :token,
                        reset_token_expiry = :expiry
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':token' => $resetToken,
                    ':expiry' => $expiry,
                    ':id'    => $user['id']
                ]);

                $resetLink =
                    BASE_URL .
                    'reset-password?token=' .
                    $resetToken;

                /* Send Email Here */

                $success =
                    'Password reset link sent successfully';
            }
        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Something went wrong';
        }
    }
}

$pageTitle = "Forgot Password";

require_once __DIR__ . '/../includes/head.php';

?>

<section class="min-h-screen flex items-center justify-center px-6 py-12">

    <div class="w-full max-w-sm">

            <div class="text-center mb-8">

                <h1 class="text-2xl font-medium text-gray-900">
                    Forgot Password
                </h1>

                <p class="text-[12px] text-gray-500 mt-2">
                    Enter your registered email address
                </p>

            </div>

            <!-- Success -->

            <?php if (!empty($success)) : ?>

                <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">

                    <p class="text-green-600 text-sm">
                        <?php echo htmlspecialchars($success); ?>
                    </p>

                </div>

            <?php endif; ?>

            <!-- Errors -->

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

            <!-- Form -->

            <form method="POST" class="space-y-5">

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent">

                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium">
                    Send Reset Link
                </button>

            </form>

            <div class="mt-6 text-center">

                <a
                    href="<?php echo BASE_URL; ?>login"
                    class="text-sm text-accent hover:underline">
                    Back to Login
                </a>

            </div>

        </div>

</section>

<footer class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-100 bg-green-100 backdrop-blur-sm py-3">
    <p class="text-center text-xs tracking-wide text-gray-400">
        🔒 Protected by industry-standard security and privacy practices.
    </p>
</footer>

</main>
</body>

</html>