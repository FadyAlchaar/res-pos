<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, full_name FROM users WHERE role = 'waiter' AND is_active = 1 ORDER BY full_name";
    $stmt = $db->query($query);
    $waiters = $stmt->fetchAll();
    
    echo json_encode($waiters);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>