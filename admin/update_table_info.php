<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];
$name = $data['name'];
$capacity = (int)$data['capacity'];
$status = $data['status'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE restaurant_tables 
              SET table_name = :name, capacity = :capacity, status = :status 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':capacity' => $capacity,
        ':status' => $status
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>