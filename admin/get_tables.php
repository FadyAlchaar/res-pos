<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM restaurant_tables ORDER BY sort_order, table_number";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($tables);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>