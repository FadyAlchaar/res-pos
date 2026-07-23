<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT mi.*, c.name as category_name, c.kitchen_id
              FROM menu_items mi
              LEFT JOIN categories c ON mi.category_id = c.id
              WHERE mi.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Menu item not found']);
        exit;
    }
    
    echo json_encode($item);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>