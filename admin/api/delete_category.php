<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Category ID required']);
    exit;
}

$category_id = (int)$data['id'];

try {
    $db = (new Database())->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // First, get all menu_item IDs in this category
    $stmt = $db->prepare("SELECT id FROM menu_items WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $menu_item_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($menu_item_ids)) {
        // Detach order items: set menu_item_id to NULL for those items
        $placeholders = implode(',', array_fill(0, count($menu_item_ids), '?'));
        $stmt = $db->prepare("UPDATE order_items SET menu_item_id = NULL WHERE menu_item_id IN ($placeholders)");
        $stmt->execute($menu_item_ids);
        
        // Now delete menu items (category_id will be nullified automatically if ON DELETE SET NULL,
        // but we are about to delete the category, so we must delete menu items explicitly)
        $stmt = $db->prepare("DELETE FROM menu_items WHERE category_id = ?");
        $stmt->execute([$category_id]);
    }
    
    // Delete the category
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>