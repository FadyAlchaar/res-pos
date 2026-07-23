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
$status = $data['status'] ?? null;
$payment_status = $data['payment_status'] ?? null;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $updates = [];
    $params = [':id' => $order_id];
    
    if ($status) {
        $updates[] = "status = :status";
        $params[':status'] = $status;
    }
    
    if ($payment_status) {
        $updates[] = "payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    $query = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>