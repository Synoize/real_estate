<?php
/**
 * BUILDER LOGOUT
 */

require_once __DIR__ . '/../includes/db_connect.php';

/* Remove Cookie */

if (isset($_COOKIE['builder_auth_token'])) {

    setcookie(
        'builder_auth_token',
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
    'Builder logged out successfully.',
    'success'
);

/* Redirect */

redirect(
    BUILDER_URL . 'login'
);
