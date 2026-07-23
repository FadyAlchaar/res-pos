<?php
require_once '../../config/config.php';
require_once '../../includes/print_functions.php';

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
$force = isset($data['force']) && $data['force'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get order details
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$order_id]);
    $order = $orderStmt->fetch();
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Get items grouped by kitchen
    $itemsQuery = "SELECT oi.*, mi.name as item_name, oi.kitchen_id, k.name as kitchen_name, 
                          k.printer_name, k.printer_ip, k.printer_port, k.printer_type
                   FROM order_items oi
                   JOIN menu_items mi ON oi.menu_item_id = mi.id
                   LEFT JOIN kitchens k ON oi.kitchen_id = k.id
                   WHERE oi.order_id = :order_id";
    $stmt = $db->prepare($itemsQuery);
    $stmt->execute([':order_id' => $order_id]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items found for this order']);
        exit;
    }
    
    // Group by kitchen
    $items_by_kitchen = [];
    foreach ($items as $item) {
        $kid = $item['kitchen_id'];
        if (!isset($items_by_kitchen[$kid])) {
            $items_by_kitchen[$kid] = [
                'kitchen_name' => $item['kitchen_name'],
                'printer_name' => $item['printer_name'],
                'printer_ip' => $item['printer_ip'],
                'printer_port' => $item['printer_port'],
                'printer_type' => $item['printer_type'],
                'items' => []
            ];
        }
        $items_by_kitchen[$kid]['items'][] = [
            'name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'notes' => $item['notes']
        ];
    }
    
    $details = [];
    $all_success = true;
    
    foreach ($items_by_kitchen as $kid => $kit) {
        // Check if already printed (unless force)
        if (!$force) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM print_jobs WHERE order_id = ? AND kitchen_id = ?");
            $checkStmt->execute([$order_id, $kid]);
            if ($checkStmt->fetchColumn() > 0) {
                $details[] = [
                    'kitchen' => $kit['kitchen_name'],
                    'item_count' => count($kit['items']),
                    'message' => 'Already printed (skipped)'
                ];
                continue;
            }
        }
        
        $printer = [
            'type' => $kit['printer_type'] ?: 'windows',
            'name' => $kit['printer_name'],
            'ip' => $kit['printer_ip'],
            'port' => $kit['printer_port'] ?: 9100
        ];
        
        $content = formatKitchenPrint($kit['kitchen_name'], $order['order_number'], $order['table_number'], $kit['items'], true);
        $printed = sendPrintJob($printer, $content);
        
        if ($printed) {
            $logStmt = $db->prepare("INSERT INTO print_jobs (order_id, kitchen_id, printer_name, content, status, created_at) 
                                     VALUES (?, ?, ?, ?, 'reprinted', NOW())");
            $logStmt->execute([$order_id, $kid, $printer['name'], $content]);
            $details[] = [
                'kitchen' => $kit['kitchen_name'],
                'item_count' => count($kit['items']),
                'message' => 'Printed'
            ];
        } else {
            $all_success = false;
            $details[] = [
                'kitchen' => $kit['kitchen_name'],
                'item_count' => count($kit['items']),
                'message' => 'Failed'
            ];
        }
    }
    
    echo json_encode([
        'success' => $all_success,
        'message' => $all_success ? 'All kitchens printed successfully' : 'Some kitchens failed',
        'details' => $details
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>