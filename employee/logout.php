<?php
/**
 * EMPLOYEE LOGOUT
 */

require_once __DIR__ . '/../includes/db_connect.php';

/* Remove Cookie */

if (isset($_COOKIE['employee_auth_token'])) {

    setcookie(
        'employee_auth_token',
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
    'Employee logged out successfully.',
    'success'
);

/* Redirect */

redirect(
    EMPLOYEE_URL . 'login'
);
