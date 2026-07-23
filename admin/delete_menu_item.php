<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle both DELETE method and POST with _method=DELETE
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    $method = 'DELETE';
}

if ($method !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get ID from query string or POST data
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if (!$id) {
    http_response_code(400);
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
        // Instead of deleting, just mark as unavailable
        $query = "UPDATE menu_items SET is_available = 0 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Menu item has been ordered before. It has been marked as unavailable instead of deleted.']);
    } else {
        // Safe to delete
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