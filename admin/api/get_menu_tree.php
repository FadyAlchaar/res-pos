<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get all active categories sorted by name (you can change to sort_order)
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as &$cat) {
        // Get items for this category, sorted by name (or sort_order)
        $itemsStmt = $db->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY name ASC");
        $itemsStmt->execute([$cat['id']]);
        $cat['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($categories);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>