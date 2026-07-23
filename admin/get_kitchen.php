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
    echo json_encode(['error' => 'Kitchen ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM kitchens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $kitchen = $stmt->fetch();
    
    if (!$kitchen) {
        http_response_code(404);
        echo json_encode(['error' => 'Kitchen not found']);
        exit;
    }
    
    // Return the kitchen data
    echo json_encode($kitchen);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>