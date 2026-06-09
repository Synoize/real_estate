<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success = '';

$builderId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $builderId =
        trim($_POST['builder_id'] ?? '');

    if (empty($builderId)) {
        $errors[] = 'Builder ID is required';
    }

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT id, company_slug, email
                FROM builders
                WHERE company_slug = :company_slug
                LIMIT 1
            ");

            $stmt->execute([
                ':company_slug' => $builderId
            ]);

            $builder = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$builder) {

                $errors[] = 'Builder account not found';

            } else {

                $token = bin2hex(random_bytes(32));

                $expiry = date(
                    'Y-m-d H:i:s',
                    strtotime('+1 hour')
                );

                $updateStmt = $pdo->prepare("
                    UPDATE builders
                    SET
                        reset_token = :token,
                        reset_token_expiry = :expiry
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':token' => $token,
                    ':expiry'=> $expiry,
                    ':id'    => $builder['id']
                ]);

                $success =
                    'Reset link sent successfully';
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] =
                'Something went wrong';
        }
    }
}

$pageTitle = "Builder Forgot Password";

require_once __DIR__ . '/../includes/header.php';

?>


<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-md">

        <div class="bg-white rounded-2xl shadow-sm border p-6 md:p-8">

            <!-- Logo -->

            <div class="text-center mb-8">

                <img
                    src="<?php echo ASSETS_URL; ?>/public/logo.png"
                    class="h-20 mx-auto mb-4"
                    alt="Logo"
                >

                <h1 class="text-2xl font-bold text-gray-900">
                    Forgot Password
                </h1>

                <p class="text-sm text-gray-500 mt-2">
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
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium"
                >
                    Send Reset Link
                </button>

            </form>

            <div class="mt-6 text-center">

                <a
                    href="<?php echo BUILDER_URL; ?>login"
                    class="text-sm text-accent hover:underline"
                >
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</section>
