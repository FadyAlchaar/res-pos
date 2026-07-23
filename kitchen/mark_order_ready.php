<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order_item_id = isset($data['order_item_id']) ? (int)$data['order_item_id'] : 0;

if (!$order_item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order item']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update status to ready
    $query = "UPDATE order_items SET status = 'ready' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $order_item_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>