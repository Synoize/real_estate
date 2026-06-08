<?php

require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Redirect If Already Logged In */

if (
    isset($_SESSION['employee_id']) &&
    $_SESSION['employee_role'] === 'employee'
) {
    header("Location: " . EMPLOYEE_URL);
    exit;
}

/* Variables */

$errors = [];

$employeeCode = '';
$password = '';

/* Process Login */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Get Form Data */

    $employeeCode = trim($_POST['employee_code'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    /* Validation */

    if (empty($employeeCode)) {
        $errors[] = 'Employee ID is required';
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
                    employee_code,
                    full_name,
                    email,
                    phone,
                    password,
                    profile_image,
                    department,
                    designation,
                    status
                FROM employees
                WHERE employee_code = :employee_code
                LIMIT 1
            ");

            $stmt->execute([
                ':employee_code' => $employeeCode
            ]);

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            /* Employee Exists */

            if (!$employee) {

                $errors[] = 'Invalid employee ID or password';

            }

            /* Status Check */

            elseif ($employee['status'] !== 'active') {

                $errors[] = 'Your account is inactive';

            }

            /* Verify Password */

            elseif (!password_verify(
                $password,
                $employee['password']
            )) {

                $errors[] = 'Invalid employee ID or password';

            }

            /* Login Success */

            else {

                /* Generate Auth Token */

                $authToken = bin2hex(random_bytes(64));

                /* Update Login Details */

                $updateStmt = $pdo->prepare("
                    UPDATE employees
                    SET
                        auth_token = :auth_token,
                        last_login_at = NOW(),
                        last_login_ip = :last_login_ip
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':auth_token'    => $authToken,
                    ':last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? NULL,
                    ':id'            => $employee['id']
                ]);

                /* Regenerate Session */

                session_regenerate_id(true);

                /* Store Session */

                $_SESSION['logged_in'] = true;

                $_SESSION['employee_id'] =
                    $employee['id'];

                $_SESSION['employee_uuid'] =
                    $employee['uuid'];

                $_SESSION['employee_code'] =
                    $employee['employee_code'];

                $_SESSION['employee_name'] =
                    $employee['full_name'];

                $_SESSION['employee_email'] =
                    $employee['email'];

                $_SESSION['employee_phone'] =
                    $employee['phone'];

                $_SESSION['employee_image'] =
                    $employee['profile_image'];

                $_SESSION['employee_department'] =
                    $employee['department'];

                $_SESSION['employee_designation'] =
                    $employee['designation'];

                $_SESSION['employee_role'] =
                    'employee';

                /* Remember Login */

                if ($remember) {

                    setcookie(
                        'employee_auth_token',
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
                    ':user_type'          => 'Employee',
                    ':user_id'            => $employee['id'],
                    ':action_title'       => 'Employee Login',
                    ':action_description' => 'Employee logged into dashboard',
                    ':ip_address'         => $_SERVER['REMOTE_ADDR'] ?? NULL
                ]);

                /* Success Message */

                $_SESSION['success_message'] =
                    'Welcome back, ' .
                    $employee['full_name'];

                /* Redirect */

                header("Location: " . EMPLOYEE_URL);
                exit;
            }

        } catch (PDOException $e) {

            error_log(
                'EMPLOYEE LOGIN ERROR : ' .
                $e->getMessage()
            );

            $errors[] =
                'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = "Employee Login";

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
                    Employee Login
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Login using Employee ID
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

                <!-- Employee ID -->

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Employee ID
                    </label>

                    <input
                        type="text"
                        name="employee_code"
                        value="<?php echo htmlspecialchars($employeeCode); ?>"
                        placeholder="Enter Employee ID"
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
                        href="<?php echo EMPLOYEE_URL; ?>forgot-password.php"
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
