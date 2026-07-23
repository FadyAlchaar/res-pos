<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Enable error reporting but log instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if (!$category_id) {
    echo json_encode([]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, description, price, preparation_time 
              FROM menu_items 
              WHERE category_id = $category_id AND is_available = 1
              ORDER BY sort_order, name";
    
    $stmt = $db->query($query);
    $items = $stmt->fetchAll();
    
    echo json_encode($items);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>