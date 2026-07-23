<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['table_id'])) {
    echo json_encode(['success' => false, 'message' => 'Table ID required']);
    exit;
}

$table_id = (int)$data['table_id'];
$is_reserved = isset($data['is_reserved']) ? (int)$data['is_reserved'] : 0;
$reserved_for = isset($data['reserved_for']) ? trim($data['reserved_for']) : null;
$customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : null;

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE restaurant_tables SET is_reserved = ?, reserved_for = ?, customer_name = ? WHERE id = ?");
    $stmt->execute([$is_reserved, $reserved_for, $customer_name, $table_id]);
        
    echo json_encode(['success' => true, 'message' => 'Reserved status updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>