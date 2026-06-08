<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Redirect If Already Logged In */

if (
    isset($_SESSION['admin_id']) &&
    $_SESSION['admin_role'] === 'admin'
) {
    header("Location: " . ADMIN_URL);
    exit;
}

/* Variables */

$errors = [];

$email = '';
$password = '';

/* Process Login */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Get Form Data */

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    /* Validation */

    if (empty($email)) {
        $errors[] = 'Email is required';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
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
                    full_name,
                    email,
                    password,
                    profile_image,
                    role,
                    status
                FROM admins
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            /* Admin Exists */

            if (!$admin) {

                $errors[] = 'Invalid email or password';

            }

            /* Status Check */

            elseif ($admin['status'] !== 'active') {

                $errors[] = 'Your account is inactive';

            }

            /* Verify Password */

            elseif (!password_verify($password, $admin['password'])) {

                $errors[] = 'Invalid email or password';

            }

            /* Login Success */

            else {

                /* Generate Token */

                $authToken = bin2hex(random_bytes(64));

                /* Update Login Details */

                $updateStmt = $pdo->prepare("
                    UPDATE admins
                    SET
                        auth_token = :auth_token,
                        last_login_at = NOW(),
                        last_login_ip = :last_login_ip
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':auth_token'    => $authToken,
                    ':last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? NULL,
                    ':id'            => $admin['id']
                ]);

                /* Regenerate Session */

                session_regenerate_id(true);

                /* Store Session */

                $_SESSION['logged_in'] = true;

                $_SESSION['admin_id'] = $admin['id'];

                $_SESSION['admin_uuid'] = $admin['uuid'];

                $_SESSION['admin_name'] = $admin['full_name'];

                $_SESSION['admin_email'] = $admin['email'];

                $_SESSION['admin_image'] = $admin['profile_image'];

                $_SESSION['admin_role'] = 'admin';

                $_SESSION['admin_type'] = $admin['role'];

                /* Remember Cookie */

                if ($remember) {

                    setcookie(
                        'admin_auth_token',
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
                    ':user_type'          => 'Admin',
                    ':user_id'            => $admin['id'],
                    ':action_title'       => 'Admin Login',
                    ':action_description' => 'Admin logged into dashboard',
                    ':ip_address'         => $_SERVER['REMOTE_ADDR'] ?? NULL
                ]);

                /* Success Message */

                $_SESSION['success_message'] =
                    'Welcome back, ' . $admin['full_name'];

                /* Redirect */

                header("Location: " . ADMIN_URL);
                exit;
            }

        } catch (PDOException $e) {

            error_log(
                'ADMIN LOGIN ERROR : ' . $e->getMessage()
            );

            $errors[] =
                'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = "Admin Login";

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
                    Admin Login
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Login to admin dashboard
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

                <!-- Email -->

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

                <!-- Password -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
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
                        href="<?php echo ADMIN_URL; ?>forgot-password.php"
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>