<?php
/**
 * Database Connection
 * Includes session start and database connection
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to allow redirects after some output has been generated
if (function_exists('ob_start') && !ob_get_level()) {
    ob_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';
