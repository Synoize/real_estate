<?php
/**
 * USER LOGOUT
 */

require_once __DIR__ . '/../includes/db_connect.php';

/* Remove User Cookie */

if (isset($_COOKIE['user_auth_token'])) {

    setcookie(
        'user_auth_token',
        '',
        time() - 3600,
        '/'
    );
}

/* Destroy Session */

logoutAll();

/* Start Fresh Session */

session_start();

/* Flash Message */

setFlash(
    'You have been logged out successfully.',
    'success'
);

/* Redirect */

redirect(
    BASE_URL . 'login'
);
