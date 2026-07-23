<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/');
    session_start();
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Beirut'); // Adjust to your timezone

// Base URL - Update this to match your setup
define('BASE_URL', 'http://localhost/res-pos');

// Printer settings
define('PRINTER_TIMEOUT', 5); // seconds
define('MAX_PRINT_RETRIES', 3);
define('PRINT_RETRY_DELAY', 30); // seconds

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('LOG_PATH', ROOT_PATH . '/logs');

// Create logs directory if not exists
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}

// Include database class
require_once ROOT_PATH . '/config/database.php';

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>