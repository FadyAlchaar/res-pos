<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    $method = 'DELETE';
}

if ($method !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Table ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if table has orders
    $check = "SELECT COUNT(*) as count FROM orders WHERE table_id = :id";
    $stmt = $db->prepare($check);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // Instead of deleting, mark as inactive
        $query = "UPDATE restaurant_tables SET is_active = 0 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Table has been deactivated (has order history)']);
    } else {
        // Safe to delete
        $query = "DELETE FROM restaurant_tables WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Table deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete table']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>