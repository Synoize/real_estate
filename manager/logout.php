<?php
/**
 * MANAGER LOGOUT
 */

require_once __DIR__ . '/../includes/db_connect.php';

/* Remove Cookie */

if (isset($_COOKIE['manager_auth_token'])) {

    setcookie(
        'manager_auth_token',
        '',
        time() - 3600,
        '/'
    );
}

/* Destroy Session */

logoutAll();

/* Start Session */

session_start();

/* Flash */

setFlash(
    'Manager logged out successfully.',
    'success'
);

/* Redirect */

redirect(
    MANAGER_URL . 'login'
);
