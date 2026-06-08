<?php
/**
 * =========================================================
 * DATABASE CONFIGURATION
 * REAL ESTATE CRM PROJECT
 * =========================================================
 */

/* =========================================================
| START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
| DATABASE CONFIGURATION
========================================================= */

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'real_estate_db');

/* =========================================================
| BASE URL
========================================================= */

define('BASE_URL', 'http://localhost/real_estate/');

/* =========================================================
| ROLE URLS
========================================================= */

define('ADMIN_URL', BASE_URL . 'admin/');
define('EMPLOYEE_URL', BASE_URL . 'employee/');
define('BUILDER_URL', BASE_URL . 'builder/');
define('MANAGER_URL', BASE_URL . 'manager/');
define('USER_URL', BASE_URL . 'user/');

/* =========================================================
| ASSET URLS
========================================================= */

define('ASSETS_URL', BASE_URL . 'assets/');
define('PUBLIC_URL', ASSETS_URL . 'public/');
define('IMAGES_URL', ASSETS_URL . 'images/');
define('UPLOADS_URL', IMAGES_URL . 'uploads/');
define('PROJECTS_URL', IMAGES_URL . 'projects/');
define('CATEGORIES_URL', IMAGES_URL . 'categories/');

/* =========================================================
| PHYSICAL PATHS
========================================================= */

define('ROOT_PATH', dirname(__DIR__) . '/');

define('ASSETS_PATH', ROOT_PATH . 'assets/');

define('IMAGES_PATH', ASSETS_PATH . 'images/');

define('UPLOADS_PATH', IMAGES_PATH . 'uploads/');

define('PROJECTS_PATH', IMAGES_PATH . 'projects/');

define('CATEGORIES_PATH', IMAGES_PATH . 'categories/');

/* =========================================================
| DATABASE CONNECTION
========================================================= */

try {

    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {

        throw new PDOException(
            'PDO MYSQL extension not enabled'
        );
    }

    $dsn =
        "mysql:host=" . DB_HOST .
        ";dbname=" . DB_NAME .
        ";charset=utf8mb4";

    $options = [

        PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE =>
            PDO::FETCH_ASSOC,

        PDO::ATTR_EMULATE_PREPARES =>
            false
    ];

    $pdo = new PDO(
        $dsn,
        DB_USERNAME,
        DB_PASSWORD,
        $options
    );

} catch (PDOException $e) {

    die(
        "Database Connection Failed : " .
        $e->getMessage()
    );
}

/* =========================================================
| GET DATABASE
========================================================= */

function getDB()
{
    global $pdo;
    return $pdo;
}

/* =========================================================
| ESCAPE STRING
========================================================= */

function e($string)
{
    return htmlspecialchars(
        $string,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================================
| REDIRECT
========================================================= */

function redirect($url)
{
    if (!headers_sent()) {

        header("Location: " . $url);
        exit;
    }

    echo '
        <script>
            window.location.href="' . e($url) . '";
        </script>
    ';

    exit;
}

/* =========================================================
| FLASH MESSAGE
========================================================= */

function setFlash($message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;

    $_SESSION['flash_type'] = $type;
}

function getFlash()
{
    if (isset($_SESSION['flash_message'])) {

        $data = [

            'message' =>
                $_SESSION['flash_message'],

            'type' =>
                $_SESSION['flash_type']
        ];

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        return $data;
    }

    return null;
}

/* =========================================================
| FORMAT CURRENCY
========================================================= */

function formatCurrency($amount)
{
    return '₹' . number_format($amount, 2);
}

/* =========================================================
| IMAGE URL
========================================================= */

function getImageUrl($image, $type = 'projects')
{
    if (empty($image)) {

        return IMAGES_URL .
            'placeholder.png';
    }

    if (strpos($image, 'http') === 0) {

        return $image;
    }

    switch ($type) {

        case 'projects':
            return PROJECTS_URL . $image;

        case 'categories':
            return CATEGORIES_URL . $image;

        case 'uploads':
            return UPLOADS_URL . $image;

        default:
            return IMAGES_URL . $image;
    }
}

/* =========================================================
| LOGIN CHECKS
========================================================= */

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['admin_id']);
}

function isEmployee()
{
    return isset($_SESSION['employee_id']);
}

function isBuilder()
{
    return isset($_SESSION['builder_id']);
}

function isManager()
{
    return isset($_SESSION['manager_id']);
}

function isAnyLoggedIn()
{
    return (
        isLoggedIn() ||
        isAdmin() ||
        isEmployee() ||
        isBuilder() ||
        isManager()
    );
}

/* =========================================================
| GET ROLE
========================================================= */

function getLoggedInRole()
{
    if (isAdmin()) {
        return 'admin';
    }

    if (isEmployee()) {
        return 'employee';
    }

    if (isBuilder()) {
        return 'builder';
    }

    if (isManager()) {
        return 'manager';
    }

    if (isLoggedIn()) {
        return 'user';
    }

    return null;
}

/* =========================================================
| REQUIRE LOGIN
========================================================= */

function requireLogin()
{
    if (!isLoggedIn()) {

        setFlash(
            'Please login first',
            'warning'
        );

        redirect(USER_URL . 'login.php');
    }
}

function requireAdmin()
{
    if (!isAdmin()) {

        setFlash(
            'Admin access required',
            'danger'
        );

        redirect(ADMIN_URL . 'login.php');
    }
}

function requireEmployee()
{
    if (!isEmployee()) {

        setFlash(
            'Employee access required',
            'danger'
        );

        redirect(EMPLOYEE_URL . 'login.php');
    }
}

function requireBuilder()
{
    if (!isBuilder()) {

        setFlash(
            'Builder access required',
            'danger'
        );

        redirect(BUILDER_URL . 'login.php');
    }
}

function requireManager()
{
    if (!isManager()) {

        setFlash(
            'Manager access required',
            'danger'
        );

        redirect(MANAGER_URL . 'login.php');
    }
}

/* =========================================================
| LOGOUT
========================================================= */

function logoutAll()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/* =========================================================
| WISHLIST
========================================================= */

function isInWishlist($projectId)
{
    global $pdo;

    if (!isLoggedIn()) {
        return false;
    }

    try {

        $stmt = $pdo->prepare("
            SELECT id
            FROM wishlist
            WHERE user_id = ?
            AND project_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $projectId
        ]);

        return $stmt->fetch() ? true : false;

    } catch (PDOException $e) {

        return false;
    }
}

function getWishlistCount()
{
    global $pdo;

    if (!isLoggedIn()) {
        return 0;
    }

    try {

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM wishlist
            WHERE user_id = ?
        ");

        $stmt->execute([
            $_SESSION['user_id']
        ]);

        $row = $stmt->fetch();

        return (int)$row['total'];

    } catch (PDOException $e) {

        return 0;
    }
}

/* =========================================================
| CURRENT URL
========================================================= */

function getCurrentPageUrl()
{
    return
        (
            isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on'
        ? 'https://' : 'http://'
        ) .
        $_SERVER['HTTP_HOST'] .
        $_SERVER['REQUEST_URI'];
}