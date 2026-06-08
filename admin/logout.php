<?php
/**
 * ADMIN LOGOUT
 */

require_once __DIR__ . '/../includes/db_connect.php';

/* Remove Cookie */

if (isset($_COOKIE['admin_auth_token'])) {

    setcookie(
        'admin_auth_token',
        '',
        time() - 3600,
        '/'
    );
}

/* Destroy Session */

logoutAll();

/* Start New Session */

session_start();

/* Flash */

setFlash(
    'Admin logged out successfully.',
    'success'
);

/* Redirect */

redirect(
    ADMIN_URL . 'login.php'
);