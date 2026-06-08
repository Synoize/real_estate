<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Redirect If Already Logged In */

if (
    isset($_SESSION['builder_id']) &&
    $_SESSION['builder_role'] === 'builder'
) {
    header("Location: " . BUILDER_URL);
    exit;
}

/* Variables */

$errors = [];

$builderId = '';
$password = '';

/* Process Login */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Get Form Data */

    $builderId = trim($_POST['builder_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    /* Validation */

    if (empty($builderId)) {
        $errors[] = 'Builder ID is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    /* Login Check */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    uuid,
                    company_slug,
                    company_name,
                    builder_name,
                    email,
                    phone,
                    whatsapp_number,
                    password,
                    company_logo,
                    city,
                    state,
                    status,
                    is_verified
                FROM builders
                WHERE company_slug = :company_slug
                LIMIT 1
            ");

            $stmt->execute([
                ':company_slug' => $builderId
            ]);

            $builder = $stmt->fetch(PDO::FETCH_ASSOC);

            /* Builder Exists */

            if (!$builder) {

                $errors[] = 'Invalid Builder ID or password';

            }

            /* Status Check */

            elseif ($builder['status'] !== 'active') {

                $errors[] = 'Your account is inactive';

            }

            /* Verify Account */

            elseif ((int)$builder['is_verified'] !== 1) {

                $errors[] = 'Your account is not verified';

            }

            /* Verify Password */

            elseif (!password_verify(
                $password,
                $builder['password']
            )) {

                $errors[] = 'Invalid Builder ID or password';

            }

            /* Login Success */

            else {

                /* Generate Auth Token */

                $authToken = bin2hex(random_bytes(64));

                /* Update Login Details */

                $updateStmt = $pdo->prepare("
                    UPDATE builders
                    SET
                        auth_token = :auth_token,
                        last_login_at = NOW(),
                        last_login_ip = :last_login_ip
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':auth_token'    => $authToken,
                    ':last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? NULL,
                    ':id'            => $builder['id']
                ]);

                /* Regenerate Session */

                session_regenerate_id(true);

                /* Store Session */

                $_SESSION['logged_in'] = true;

                $_SESSION['builder_id'] =
                    $builder['id'];

                $_SESSION['builder_uuid'] =
                    $builder['uuid'];

                $_SESSION['builder_code'] =
                    $builder['company_slug'];

                $_SESSION['builder_company_name'] =
                    $builder['company_name'];

                $_SESSION['builder_name'] =
                    $builder['builder_name'];

                $_SESSION['builder_email'] =
                    $builder['email'];

                $_SESSION['builder_phone'] =
                    $builder['phone'];

                $_SESSION['builder_whatsapp'] =
                    $builder['whatsapp_number'];

                $_SESSION['builder_logo'] =
                    $builder['company_logo'];

                $_SESSION['builder_city'] =
                    $builder['city'];

                $_SESSION['builder_state'] =
                    $builder['state'];

                $_SESSION['builder_role'] =
                    'builder';

                /* Remember Login */

                if ($remember) {

                    setcookie(
                        'builder_auth_token',
                        $authToken,
                        [
                            'expires'  => time() + (86400 * 30),
                            'path'     => '/',
                            'secure'   => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                }

                /* Save Login Log */

                $logStmt = $pdo->prepare("
                    INSERT INTO activity_logs (
                        user_type,
                        user_id,
                        action_title,
                        action_description,
                        ip_address
                    ) VALUES (
                        :user_type,
                        :user_id,
                        :action_title,
                        :action_description,
                        :ip_address
                    )
                ");

                $logStmt->execute([
                    ':user_type'          => 'Builder',
                    ':user_id'            => $builder['id'],
                    ':action_title'       => 'Builder Login',
                    ':action_description' => 'Builder logged into dashboard',
                    ':ip_address'         => $_SERVER['REMOTE_ADDR'] ?? NULL
                ]);

                /* Success Message */

                $_SESSION['success_message'] =
                    'Welcome back, ' .
                    $builder['company_name'];

                /* Redirect */

                header("Location: " . BUILDER_URL);
                exit;
            }

        } catch (PDOException $e) {

            error_log(
                'BUILDER LOGIN ERROR : ' .
                $e->getMessage()
            );

            $errors[] =
                'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = "Builder Login";

require_once __DIR__ . '/../includes/header.php';

?>

<section class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-10">

    <div class="w-full max-w-md">

        <div class="bg-white rounded-2xl shadow-sm border p-6 md:p-8">

            <!-- Logo -->

            <div class="text-center mb-8">

                <img
                    src="<?php echo ASSETS_URL; ?>/public/logo.png"
                    alt="Logo"
                    class="h-20 mx-auto mb-4"
                >

                <h1 class="text-2xl font-bold text-gray-900">
                    Builder Login
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Login using Builder ID
                </p>

            </div>

            <!-- Errors -->

            <?php if (!empty($errors)) : ?>

                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">

                    <ul class="space-y-1">

                        <?php foreach ($errors as $error) : ?>

                            <li class="text-sm text-red-600">
                                • <?php echo htmlspecialchars($error); ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <!-- Form -->

            <form method="POST" class="space-y-5">

                <!-- Builder ID -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Builder ID
                    </label>

                    <input
                        type="text"
                        name="builder_id"
                        value="<?php echo htmlspecialchars($builderId); ?>"
                        placeholder="Enter Builder ID"
                        required
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Password -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter Password"
                        required
                        class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Remember -->

                <div class="flex items-center justify-between">

                    <label class="flex items-center gap-2">

                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4"
                        >

                        <span class="text-sm text-gray-600">
                            Remember me
                        </span>

                    </label>

                    <a
                        href="<?php echo BUILDER_URL; ?>forgot-password.php"
                        class="text-sm text-accent hover:underline"
                    >
                        Forgot Password?
                    </a>

                </div>

                <!-- Submit -->

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium hover:opacity-90 transition"
                >
                    Login
                </button>

            </form>

        </div>

    </div>

</section>
