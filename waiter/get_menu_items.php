<?php
require_once '../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if (!$category_id) {
    echo json_encode(['error' => 'Category ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, check if the category exists
    $checkQuery = "SELECT id, name FROM categories WHERE id = :category_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':category_id' => $category_id]);
    $category = $checkStmt->fetch();
    
    if (!$category) {
        echo json_encode(['error' => 'Category not found']);
        exit;
    }
    
    // Get menu items for this category
    $query = "SELECT id, name, description, price, preparation_time, is_available 
              FROM menu_items 
              WHERE category_id = :category_id 
              ORDER BY sort_order, name";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':category_id' => $category_id]);
    $items = $stmt->fetchAll();
    
    // If no items found, return empty array with a message
    if (empty($items)) {
        echo json_encode([]);
    } else {
        echo json_encode($items);
    }
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode(['error' => $e->getMessage()]);
}
?>