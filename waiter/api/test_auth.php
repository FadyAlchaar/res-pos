<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
header('Content-Type: application/json');
echo json_encode([
    'logged_in' => isLoggedIn(),
    'role' => $_SESSION['role'] ?? 'none',
    'session_id' => session_id()
]);
?>