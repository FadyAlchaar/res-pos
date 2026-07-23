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
    echo json_encode(['success' => false, 'message' => 'Kitchen ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if kitchen has categories
    $check = "SELECT COUNT(*) as count FROM categories WHERE kitchen_id = :id";
    $stmt = $db->prepare($check);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete kitchen with existing categories. Please reassign or delete categories first.']);
        exit;
    }
    
    // Delete kitchen
    $query = "DELETE FROM kitchens WHERE id = :id";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kitchen deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete kitchen']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>