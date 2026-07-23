<?php
require_once '../../config/config.php';
require_once '../../config/language.php';
require_once '../../includes/print_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'waiter')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['session_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

$session_id = (int)$data['session_id'];
$lang = isset($data['lang']) ? $data['lang'] : 'en';
$GLOBALS['lang'] = $lang;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get session details and all orders in that session
    $sessionStmt = $db->prepare("
        SELECT ts.*, t.table_number, u.full_name as waiter_name
        FROM table_sessions ts
        JOIN restaurant_tables t ON ts.table_id = t.id
        LEFT JOIN users u ON ts.opened_by = u.id
        WHERE ts.id = :id
    ");
    $sessionStmt->execute([':id' => $session_id]);
    $session = $sessionStmt->fetch();
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    
    // Get all orders in this session
    $ordersStmt = $db->prepare("
        SELECT o.*, u.full_name as waiter_name
        FROM orders o
        LEFT JOIN users u ON o.waiter_id = u.id
        WHERE o.session_id = :session_id
        ORDER BY o.created_at
    ");
    $ordersStmt->execute([':session_id' => $session_id]);
    $orders = $ordersStmt->fetchAll();
    
    // Collect all items for the accountant receipt
    $allItems = [];
    $totalAmount = 0;
    foreach ($orders as $order) {
        $itemsStmt = $db->prepare("
            SELECT oi.*, mi.name as item_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = :order_id
        ");
        $itemsStmt->execute([':order_id' => $order['id']]);
        $items = $itemsStmt->fetchAll();
        foreach ($items as $item) {
            $allItems[] = [
                'name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
                'notes' => $item['notes']
            ];
            $totalAmount += $item['subtotal'];
        }
    }
    
    // Use the existing accountant printer settings
    $accountantSettings = getAccountantSettings($db);
    if (!$accountantSettings['enabled']) {
        echo json_encode(['success' => false, 'message' => 'Accountant printer not enabled']);
        exit;
    }
    
    $printer = [
        'type' => $accountantSettings['type'],
        'name' => $accountantSettings['name'],
        'ip' => $accountantSettings['ip'],
        'port' => $accountantSettings['port']
    ];
    
    $content = formatAccountantPrint(
        $db,
        'SESSION BILL', // order number placeholder
        $session['table_number'],
        $allItems,
        $totalAmount,
        $session['waiter_name'],
        $session['session_number']
    );
    
    $printed = sendPrintJob($printer, $content);
    
    if ($printed) {
        echo json_encode(['success' => true, 'message' => 'Bill printed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Print failed']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>