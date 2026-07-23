<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Mark all tables as inactive (preserve order history)
    $db->exec("UPDATE restaurant_tables SET is_active = 0");
    
    echo json_encode(['success' => true, 'message' => 'All tables have been cleared (marked as inactive)']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>