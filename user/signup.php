<?php
/**
 * USER SIGNUP PAGE
 * Production Ready
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Redirect If Logged In */

if (isLoggedIn()) {
    redirect(BASE_URL);
}

/* Variables */

$errors = [];

$fullName = '';
$email = '';
$phone = '';
$password = '';
$confirmPassword = '';

/* Process Signup */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Get Form Data */

    $fullName = trim($_POST['full_name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    $termsAccepted =
        isset($_POST['terms']);

    /* Clean Mobile */

    $phone = preg_replace('/\s+/', '', $phone);

    /* Validation */

    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }

    if (strlen($fullName) < 3) {
        $errors[] =
            'Full name must be at least 3 characters';
    }

    if (empty($email)) {

        $errors[] = 'Email address is required';

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $errors[] = 'Invalid email address';
    }

    if (empty($phone)) {

        $errors[] = 'Phone number is required';

    } elseif (
        !preg_match('/^\+[1-9]\d{7,14}$/', $phone)
    ) {

        $errors[] =
            'Enter valid mobile number with country code';
    }

    if (empty($password)) {

        $errors[] = 'Password is required';

    } elseif (strlen($password) < 6) {

        $errors[] =
            'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {

        $errors[] =
            'Confirm password does not match';
    }

    if (!$termsAccepted) {

        $errors[] =
            'Please accept Terms & Privacy Policy';
    }

    /* Register User */

    if (empty($errors)) {

        try {

            /* Check Email */

            $emailStmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = :email
                LIMIT 1
            ");

            $emailStmt->execute([
                ':email' => $email
            ]);

            if ($emailStmt->fetch()) {

                $errors[] =
                    'Email already registered';
            }

            /* Check Phone */

            $phoneStmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE phone = :phone
                LIMIT 1
            ");

            $phoneStmt->execute([
                ':phone' => $phone
            ]);

            if ($phoneStmt->fetch()) {

                $errors[] =
                    'Phone number already registered';
            }

            /* Create Account */

            if (empty($errors)) {

                /* Generate UUID */

                $uuid = bin2hex(random_bytes(16));

                /* Hash Password */

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    );

                /* Insert User */

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (

                        uuid,
                        full_name,
                        email,
                        phone,
                        password,
                        terms_accepted,
                        privacy_accepted,
                        status,
                        created_at

                    ) VALUES (

                        :uuid,
                        :full_name,
                        :email,
                        :phone,
                        :password,
                        :terms_accepted,
                        :privacy_accepted,
                        :status,
                        NOW()

                    )
                ");

                $insertStmt->execute([

                    ':uuid'              => $uuid,
                    ':full_name'         => $fullName,
                    ':email'             => $email,
                    ':phone'             => $phone,
                    ':password'          => $hashedPassword,
                    ':terms_accepted'    => 1,
                    ':privacy_accepted'  => 1,
                    ':status'            => 'active'

                ]);

                /* User ID */

                $userId = $pdo->lastInsertId();

                /* Generate Auth Token */

                $authToken =
                    bin2hex(random_bytes(64));

                /* Update Token */

                $tokenStmt = $pdo->prepare("
                    UPDATE users
                    SET auth_token = :auth_token
                    WHERE id = :id
                ");

                $tokenStmt->execute([
                    ':auth_token' => $authToken,
                    ':id'         => $userId
                ]);

                /* Set Cookie */

                setcookie(
                    'user_auth_token',
                    $authToken,
                    [
                        'expires'  => time() + (86400 * 30),
                        'path'     => '/',
                        'secure'   => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );

                /* Regenerate Session */

                session_regenerate_id(true);

                /* Store Session */

                $_SESSION['logged_in'] = true;

                $_SESSION['user_id'] = $userId;

                $_SESSION['user_uuid'] = $uuid;

                $_SESSION['user_name'] = $fullName;

                $_SESSION['user_email'] = $email;

                $_SESSION['user_phone'] = $phone;

                $_SESSION['user_role'] = 'user';

                /* Save Activity Log */

                $logStmt = $pdo->prepare("
                    INSERT INTO activity_logs (

                        user_type,
                        user_id,
                        action_title,
                        action_description,
                        ip_address,
                        created_at

                    ) VALUES (

                        :user_type,
                        :user_id,
                        :action_title,
                        :action_description,
                        :ip_address,
                        NOW()

                    )
                ");

                $logStmt->execute([

                    ':user_type'          => 'User',
                    ':user_id'            => $userId,
                    ':action_title'       => 'User Registration',
                    ':action_description' => 'New user account created',
                    ':ip_address'         =>
                        $_SERVER['REMOTE_ADDR'] ?? NULL

                ]);

                /* Flash Message */

                setFlash(
                    'Account created successfully',
                    'success'
                );

                /* Redirect */

                redirect(BASE_URL);
                exit;
            }

        } catch (PDOException $e) {

            error_log(
                'USER SIGNUP ERROR : ' .
                $e->getMessage()
            );

            $errors[] =
                'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = "Create Account";

require_once __DIR__ . '/../includes/header.php';

?>

<section class="min-h-screen bg-gray-100 py-10 px-4">

    <div class="max-w-md mx-auto">

        <div class="bg-white rounded-2xl shadow-sm border p-6 md:p-8">

            <!-- Logo -->

            <div class="text-center mb-8">

                <img
                    src="<?php echo ASSETS_URL; ?>/public/logo.png"
                    alt="Logo"
                    class="h-20 mx-auto mb-4"
                >

                <h1 class="text-2xl font-bold text-gray-900">
                    Create Account
                </h1>

                <p class="text-sm text-gray-500 mt-2">
                    Join our real estate platform
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

            <form
                method="POST"
                id="signupForm"
                class="space-y-5"
            >

                <!-- Full Name -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?php echo e($fullName); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Email -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        value="<?php echo e($email); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Mobile -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mobile Number
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        required
                        maxlength="16"
                        pattern="\+[0-9]{8,15}"
                        value="<?php echo e($phone); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                    <p class="text-xs text-gray-500 mt-1">
                        Example: +919876543210
                    </p>

                </div>

                <!-- Password -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        minlength="6"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Confirm Password -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl outline-none focus:border-accent"
                    >

                </div>

                <!-- Terms -->

                <div class="flex items-start">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        class="w-4 h-4 mt-1"
                    >

                    <label
                        for="terms"
                        class="ml-2 text-sm text-gray-600"
                    >
                        I agree to the

                        <a
                            href="<?php echo BASE_URL; ?>terms.php"
                            class="text-accent hover:underline"
                        >
                            Terms of Service
                        </a>

                        and

                        <a
                            href="<?php echo BASE_URL; ?>privacy-policy.php"
                            class="text-accent hover:underline"
                        >
                            Privacy Policy
                        </a>
                    </label>

                </div>

                <!-- Submit -->

                <button
                    type="submit"
                    class="w-full bg-accent text-black py-3 rounded-xl font-medium hover:opacity-90 transition"
                >
                    Create Account
                </button>

            </form>

            <!-- Login -->

            <div class="mt-6 text-center">

                <p class="text-sm text-gray-600">

                    Already have an account?

                    <a
                        href="<?php echo BASE_URL; ?>user/login.php"
                        class="text-accent hover:underline"
                    >
                        Login
                    </a>

                </p>

            </div>

        </div>

    </div>

</section>

<script>

/* Mobile Validation */

document.querySelector(
    'input[name="phone"]'
).addEventListener('input', function() {

    this.value = this.value.replace(/\s+/g, '');

    if (
        this.value.length > 0 &&
        this.value[0] !== '+'
    ) {

        this.value =
            '+' +
            this.value.replace(/[^0-9]/g, '');

    } else {

        this.value =
            '+' +
            this.value
                .substring(1)
                .replace(/[^0-9]/g, '');
    }

    this.value = this.value.slice(0, 16);
});

/* Password Match */

document.getElementById(
    'signupForm'
).addEventListener('submit', function(e) {

    const password =
        document.querySelector(
            'input[name="password"]'
        ).value;

    const confirmPassword =
        document.querySelector(
            'input[name="confirm_password"]'
        ).value;

    if (password !== confirmPassword) {

        e.preventDefault();

        alert('Passwords do not match');

        return false;
    }
});

</script>