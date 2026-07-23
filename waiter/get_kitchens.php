<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, printer_ip FROM kitchens WHERE is_active = 1 ORDER BY name";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($kitchens);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>