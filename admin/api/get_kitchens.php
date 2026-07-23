<?php
require_once '../../config/config.php';
require_once '../../config/language.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT k.*, 
                     (SELECT COUNT(*) FROM categories WHERE kitchen_id = k.id) as category_count
              FROM kitchens k 
              WHERE k.is_active = 1
              ORDER BY k.name";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    echo json_encode($kitchens);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>