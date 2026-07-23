<?php
require_once '../../config/config.php';
require_once '../../config/language.php';  // ADD THIS for t() function
require_once '../../includes/print_functions.php';

header('Content-Type: application/json');

// Ensure language is set for t() function
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}
$GLOBALS['lang'] = $_SESSION['language'];

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
$kitchen_id = isset($data['kitchen_id']) ? (int)$data['kitchen_id'] : null;
$force = isset($data['force']) ? (bool)$data['force'] : false;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get order details
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
    $orderStmt->execute([':id' => $order_id]);
    $order = $orderStmt->fetch();
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Get order items grouped by kitchen
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
        $kit = $item['kitchen_id'];
        if (!isset($items_by_kitchen[$kit])) {
            $items_by_kitchen[$kit] = [
                'kitchen_name' => $item['kitchen_name'],
                'printer_name' => $item['printer_name'],
                'printer_ip' => $item['printer_ip'],
                'printer_port' => $item['printer_port'],
                'printer_type' => $item['printer_type'],
                'items' => []
            ];
        }
        $items_by_kitchen[$kit]['items'][] = [
            'name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'notes' => $item['notes']
        ];
    }
    
    // Check if already printed (unless forced)
    if (!$force && $kitchen_id) {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM print_jobs WHERE order_item_id IN 
                                   (SELECT id FROM order_items WHERE order_id = :order_id AND kitchen_id = :kitchen_id)");
        $checkStmt->execute([':order_id' => $order_id, ':kitchen_id' => $kitchen_id]);
        $alreadyPrinted = $checkStmt->fetchColumn() > 0;
        if ($alreadyPrinted) {
            echo json_encode(['success' => false, 'already_printed' => true, 'message' => 'This order has already been printed to this kitchen.']);
            exit;
        }
    }
    
    // If kitchen_id specified, print only that kitchen
    if ($kitchen_id) {
        if (!isset($items_by_kitchen[$kitchen_id])) {
            echo json_encode(['success' => false, 'message' => 'Kitchen not found for this order']);
            exit;
        }
        $kit = $items_by_kitchen[$kitchen_id];
        $printer = [
            'type' => $kit['printer_type'] ?: 'windows',
            'name' => $kit['printer_name'],
            'ip' => $kit['printer_ip'],
            'port' => $kit['printer_port'] ?: 9100
        ];
        // Pass $db as first argument to formatKitchenPrint
        $content = formatKitchenPrint($db, $kit['kitchen_name'], $order['order_number'], $order['table_number'], $kit['items'], true);
        $printed = sendPrintJob($printer, $content);
        
        if ($printed) {
            echo json_encode(['success' => true, 'is_reprint' => true, 'message' => 'Printed to ' . $kit['kitchen_name']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Print failed for ' . $kit['kitchen_name']]);
        }
        exit;
    }
    
    // Otherwise print to all kitchens
    $results = [];
    foreach ($items_by_kitchen as $kit_id => $kit) {
        $printer = [
            'type' => $kit['printer_type'] ?: 'windows',
            'name' => $kit['printer_name'],
            'ip' => $kit['printer_ip'],
            'port' => $kit['printer_port'] ?: 9100
        ];
        $content = formatKitchenPrint($db, $kit['kitchen_name'], $order['order_number'], $order['table_number'], $kit['items'], true);
        $printed = sendPrintJob($printer, $content);
        $results[] = ['kitchen' => $kit['kitchen_name'], 'success' => $printed];
    }
    echo json_encode(['success' => true, 'results' => $results]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>