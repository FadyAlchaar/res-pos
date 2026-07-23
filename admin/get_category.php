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
    echo json_encode(['error' => 'Category ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, k.name as kitchen_name 
              FROM categories c
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              WHERE c.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        exit;
    }
    
    echo json_encode($category);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>