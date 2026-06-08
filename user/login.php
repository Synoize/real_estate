<?php
/**
 * USER LOGIN
 * Production Ready Login System
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* REDIRECT IF ALREADY LOGGED IN */

if (
    isset($_SESSION['user_id']) &&
    isset($_SESSION['user_role']) &&
    $_SESSION['user_role'] === 'user'
) {
    header("Location: " . BASE_URL);
    exit;
}

/* VARIABLES */

$errors = [];

$email = '';
$password = '';

/* PROCESS LOGIN */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* GET FORM DATA */

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    /* VALIDATION */

    if (empty($email)) {
        $errors[] = 'Email address is required';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    /* LOGIN CHECK */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    uuid,
                    full_name,
                    email,
                    phone,
                    password,
                    profile_image,
                    status,
                    is_verified,
                    is_blocked
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            /* USER EXISTS CHECK */

            if (!$user) {

                $errors[] = 'Invalid email or password';

            }

            /* ACCOUNT STATUS CHECK */

            elseif ($user['status'] !== 'active') {

                $errors[] = 'Your account is inactive';

            }

            elseif ((int)$user['is_blocked'] === 1) {

                $errors[] = 'Your account has been blocked';

            }

            /* VERIFY PASSWORD */

            elseif (!password_verify($password, $user['password'])) {

                $errors[] = 'Invalid email or password';

            }

            /* LOGIN SUCCESS */

            else {

                /* GENERATE AUTH TOKEN */

                $authToken = bin2hex(random_bytes(64));

                /* UPDATE LOGIN DETAILS */

                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET
                        auth_token = :auth_token,
                        last_login_at = NOW(),
                        last_login_ip = :last_login_ip
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':auth_token'   => $authToken,
                    ':last_login_ip'=> $_SERVER['REMOTE_ADDR'] ?? NULL,
                    ':id'           => $user['id']
                ]);

                /* REGENERATE SESSION */

                session_regenerate_id(true);

                /* STORE USER SESSION */

                $_SESSION['logged_in'] = true;

                $_SESSION['user_id'] = $user['id'];

                $_SESSION['user_uuid'] = $user['uuid'];

                $_SESSION['user_name'] = $user['full_name'];

                $_SESSION['user_email'] = $user['email'];

                $_SESSION['user_phone'] = $user['phone'];

                $_SESSION['user_image'] = $user['profile_image'];

                $_SESSION['user_role'] = 'user';

                /* REMEMBER LOGIN COOKIE */

                if ($remember) {

                    setcookie(
                        'user_auth_token',
                        $authToken,
                        [
                            'expires'  => time() + (86400 * 30),
                            'path'     => '/',
                            'domain'   => '',
                            'secure'   => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                }

                /* SAVE LOGIN LOG */

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
                    ':user_type'         => 'User',
                    ':user_id'           => $user['id'],
                    ':action_title'      => 'User Login',
                    ':action_description'=> 'User logged into account',
                    ':ip_address'        => $_SERVER['REMOTE_ADDR'] ?? NULL
                ]);

                /* SUCCESS MESSAGE */

                $_SESSION['success_message'] =
                    'Welcome back, ' . $user['full_name'] . '!';

                /* REDIRECT */

                $redirect =
                    $_SESSION['redirect_after_login']
                    ?? BASE_URL;

                unset($_SESSION['redirect_after_login']);

                header("Location: " . $redirect);
                exit;
            }

        } catch (PDOException $e) {

            error_log(
                'USER LOGIN ERROR : ' . $e->getMessage()
            );

            $errors[] =
                'Something went wrong. Please try again later.';
        }
    }
}


$pageTitle = "User Login";

require_once __DIR__ . '/../includes/header.php';
?>

<section class="min-h-screen flex items-center justify-center px-4 py-12 bg-gray-50">

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
                    Welcome Back
                </h1>

                <p class="text-gray-500 mt-1">
                    Login to your account
                </p>

            </div>

            <!-- Error Messages -->

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

            <!-- Login Form -->

            <form
                method="POST"
                action=""
                class="space-y-5"
            >

                <!-- Email -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
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
                        placeholder="Enter your password"
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
                        href="<?php echo BASE_URL; ?>user/forgot-password.php"
                        class="text-sm text-accent hover:underline"
                    >
                        Forgot Password?
                    </a>

                </div>

                <!-- Button -->

                <button
                    type="submit"
                    class="w-full h-12 rounded-xl bg-accent text-black font-medium hover:opacity-90 transition"
                >
                    Login
                </button>

            </form>

            <!-- Signup -->

            <div class="mt-6 text-center">

                <p class="text-sm text-gray-600">

                    Don't have an account?

                    <a
                        href="<?php echo BASE_URL; ?>user/signup.php"
                        class="text-accent font-medium hover:underline"
                    >
                        Create Account
                    </a>

                </p>

            </div>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>