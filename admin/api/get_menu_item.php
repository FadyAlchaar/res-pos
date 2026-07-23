<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['error' => 'Menu item ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Added print_on_controller to SELECT
    $query = "SELECT mi.*, c.name as category_name, c.kitchen_id
              FROM menu_items mi
              LEFT JOIN categories c ON mi.category_id = c.id
              WHERE mi.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();
    
    if ($item) {
        echo json_encode($item);
    } else {
        echo json_encode(['error' => 'Menu item not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>