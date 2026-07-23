<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
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