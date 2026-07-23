<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$item_id) {
    echo json_encode([]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // You'll need to create this table if you want customizations
    $query = "SELECT id, name, additional_price 
              FROM item_customizations 
              WHERE item_id = :item_id AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':item_id' => $item_id]);
    $customizations = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($customizations);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>