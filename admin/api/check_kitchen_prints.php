<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$order_id = (int)$data['order_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get distinct kitchens that have print jobs for this order
    $stmt = $db->prepare("SELECT DISTINCT k.name 
                          FROM print_jobs pj
                          JOIN kitchens k ON pj.kitchen_id = k.id
                          WHERE pj.order_id = ?");
    $stmt->execute([$order_id]);
    $kitchens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'has_prints' => !empty($kitchens),
        'kitchens_with_prints' => $kitchens
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>