<?php
require_once '../../config/config.php';
require_once '../../config/language.php';
require_once '../../includes/print_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
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
    $db = (new Database())->getConnection();
    
    // Get controller printer settings
    $controller = getControllerPrinterSettings($db);
    if (!$controller['enabled'] || empty($controller['name'])) {
        echo json_encode(['success' => false, 'message' => 'Controller printer not configured']);
        exit;
    }
    
    // Get session info
    $sessionStmt = $db->prepare("SELECT ts.*, t.table_name, t.table_number FROM table_sessions ts JOIN restaurant_tables t ON ts.table_id = t.id WHERE ts.id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();
    // Get waiter name from session opener
    $waiterName = '';
    $waiterStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $waiterStmt->execute([$session['opened_by']]);
    $waiter = $waiterStmt->fetch();
    if ($waiter) {
        $waiterName = $waiter['full_name'];
    }
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    
    $tableDisplay = $session['table_name'] ?: 'Table ' . $session['table_number'];
    
    // Get all orders in this session
    $ordersStmt = $db->prepare("SELECT id, order_number FROM orders WHERE session_id = ?");
    $ordersStmt->execute([$session_id]);
    $orders = $ordersStmt->fetchAll();
    
/*     error_log("=== DEBUG Controller Ticket ===");
    error_log("Order ID: " . $order['id']);
    error_log("Items from DB: " . json_encode($items));
 */
    $allItems = [];
    foreach ($orders as $order) {
        $itemsStmt = $db->prepare("
            SELECT oi.quantity, oi.notes, oi.cancelled, mi.name as item_name, mi.print_on_controller, k.name as kitchen_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN kitchens k ON oi.kitchen_id = k.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll();
        foreach ($items as $item) {
            if ($item['print_on_controller'] == 1) {
                $allItems[] = $item;
            }
        }
    }

    //error_log("Final allItems: " . json_encode($allItems));
    
    // Build receipt content (no prices)
    $nl = "\r\n";
    $width = 56;
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    $content = $rtl;
    
    $restaurantName = getRestaurantName($db);
    $content .= str_pad($restaurantName, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= str_pad(t('controller_ticket'), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('session') . ": " . $session['session_number'], $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('table') . ": " . $tableDisplay, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('table') . ": " . $tableDisplay, $width, ' ', STR_PAD_BOTH) . $nl;
    if (!empty($waiterName)) {
        $content .= str_pad(t('waiter') . ": " . $waiterName, $width, ' ', STR_PAD_BOTH) . $nl;
    }
    $content .= str_repeat("=", $width) . $nl;
    
    // Group by kitchen (optional, but useful)
    $kitchens = [];
    foreach ($allItems as $item) {
        $kitchens[$item['kitchen_name']][] = $item;
    }
    
    foreach ($kitchens as $kitchenName => $items) {
        $content .= strtoupper($kitchenName) . $nl;
        $content .= str_repeat("-", $width) . $nl;
        foreach ($items as $item) {
            $line = $item['quantity'] . "x " . $item['name'];
            if ($item['cancelled']) {
                $line .= " [" . t('cancelled') . "]";
            }
            $content .= $line . $nl;
            if (!empty($item['notes'])) {
                $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
            }
        }
        $content .= $nl;
    }
    
    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";
    
    $printer = ['type' => 'windows', 'name' => $controller['name']];
    $printed = sendPrintJob($printer, $content);
    
    echo json_encode(['success' => $printed, 'message' => $printed ? 'Controller ticket printed' : 'Print failed']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>