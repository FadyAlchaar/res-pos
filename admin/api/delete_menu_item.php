<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Menu item ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if item has been ordered
    $check = "SELECT COUNT(*) as count FROM order_items WHERE menu_item_id = :id";
    $stmt = $db->prepare($check);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // Instead of deleting, mark as unavailable
        $query = "UPDATE menu_items SET is_available = 0 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Menu item has been marked as unavailable (has order history)']);
    } else {
        $query = "DELETE FROM menu_items WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Menu item deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete menu item']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>