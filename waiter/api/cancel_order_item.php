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
if (!$data || empty($data['order_item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order item ID required']);
    exit;
}

$order_item_id = (int)$data['order_item_id'];
$lang = isset($data['lang']) ? $data['lang'] : 'en';
$GLOBALS['lang'] = $lang;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // --- CRITICAL: Mark the item as cancelled in the database ---
    $db->prepare("UPDATE order_items SET cancelled = 1 WHERE id = ?")->execute([$order_item_id]);
    
    // Fetch order item details for printing
    $stmt = $db->prepare("
        SELECT oi.*, mi.name as item_name, k.id as kitchen_id, k.name as kitchen_name,
               k.printer_name, k.printer_type, o.order_number, o.table_number
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN kitchens k ON oi.kitchen_id = k.id
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.id = :id
    ");
    $stmt->execute([':id' => $order_item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Order item not found']);
        exit;
    }
    
    // Build cancellation receipt with marker
    $nl = "\r\n";
    $width = 48;
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    $content = $rtl;
    $content .= str_repeat("*", $width) . $nl;
    $content .= str_pad("*** " . strtoupper(t('cancelled_item')) . " ***", $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("*", $width) . $nl;
    $content .= str_pad(strtoupper($item['kitchen_name']), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= t('order_number') . ": " . $item['order_number'] . $nl;
    $content .= t('table') . ": " . $item['table_number'] . $nl;
    $content .= t('time') . ": " . date('H:i:s') . $nl;
    $content .= str_repeat("-", $width) . $nl;
    $content .= $item['quantity'] . "x " . $item['item_name'] . $nl;
    if (!empty($item['notes'])) {
        $content .= t('note') . ": " . $item['notes'] . $nl;
    }
    $content .= str_repeat("-", $width) . $nl;
    $content .= t('cancelled_by_waiter') . $nl;
    $content .= str_repeat("*", $width) . $nl;
    $content .= "\x0C";
    
    // Print to kitchen, accountant, controller
    $kitchenPrinter = ['type' => $item['printer_type'] ?: 'windows', 'name' => $item['printer_name']];
    $kitchenPrinted = sendPrintJob($kitchenPrinter, $content);
    
    $accountantSettings = getAccountantSettings($db);
    $accountantPrinted = false;
    if ($accountantSettings['enabled'] && !empty($accountantSettings['name'])) {
        $accountantPrinter = ['type' => $accountantSettings['type'], 'name' => $accountantSettings['name'], 'ip' => $accountantSettings['ip'], 'port' => $accountantSettings['port']];
        $accountantPrinted = sendPrintJob($accountantPrinter, $content);
    }
    
    $controllerSettings = getControllerPrinterSettings($db);
    $controllerPrinted = false;
    if ($controllerSettings['enabled'] && !empty($controllerSettings['name'])) {
        $controllerPrinter = ['type' => 'windows', 'name' => $controllerSettings['name']];
        $controllerPrinted = sendPrintJob($controllerPrinter, $content);
    }
    
    $success = $kitchenPrinted || $accountantPrinted || $controllerPrinted;
    echo json_encode(['success' => $success, 'message' => t('item_cancelled')]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>