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
if (!$data || empty($data['table_id'])) {
    echo json_encode(['success' => false, 'message' => 'Table ID required']);
    exit;
}

$table_id = (int)$data['table_id'];
$waiter_name = $_SESSION['full_name'];
$lang = isset($data['lang']) ? $data['lang'] : 'en';
$print_bill = isset($data['print_bill']) ? (bool)$data['print_bill'] : true;
$GLOBALS['lang'] = $lang;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if table is reserved and get reserved_for name
    $tableStmt = $db->prepare("SELECT is_reserved, reserved_for, customer_name, table_name, table_number FROM restaurant_tables WHERE id = ?");
    $tableStmt->execute([$table_id]);
    $tableInfo = $tableStmt->fetch();    $isReserved = $tableInfo['is_reserved'];
    $reserved_for = $tableInfo['reserved_for'];
    $table_display = $tableInfo['table_name'] ?: 'Table ' . $tableInfo['table_number'];
    
    // Find open session
    $stmt = $db->prepare("SELECT id, session_number FROM table_sessions WHERE table_id = ? AND status = 'open'");
    $stmt->execute([$table_id]);
    $session = $stmt->fetch();
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'No open session found for this table']);
        exit;
    }
    $session_id = $session['id'];
    $session_number = $session['session_number'];
    
    // Collect all items from all orders in this session
    $ordersStmt = $db->prepare("SELECT id FROM orders WHERE session_id = ?");
    $ordersStmt->execute([$session_id]);
    $orders = $ordersStmt->fetchAll();
    
    $itemsMap = [];
    $totalAmount = 0;
    foreach ($orders as $order) {
        $itemsStmt = $db->prepare("
            SELECT oi.*, mi.name as item_name, mi.id as menu_item_id, mi.price as unit_price
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ? AND oi.cancelled = 0
        ");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll();
        foreach ($items as $item) {
            $key = $item['menu_item_id'];
            if (!isset($itemsMap[$key])) {
                $itemsMap[$key] = [
                    'name' => $item['item_name'],
                    'quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => 0,
                    'notes' => []
                ];
            }
            $itemsMap[$key]['quantity'] += $item['quantity'];
            $itemsMap[$key]['subtotal'] += $item['subtotal'];
            if (!empty($item['notes'])) {
                $itemsMap[$key]['notes'][] = $item['notes'];
            }
            $totalAmount += $item['subtotal'];
        }
    }
    $allItems = [];
    foreach ($itemsMap as $item) {
        $allItems[] = [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
            'notes' => !empty($item['notes']) ? implode('; ', $item['notes']) : ''
        ];
    }
    
    // Print bill only if requested, there are items, AND table is NOT reserved
    if ($print_bill && !empty($allItems) && !$isReserved) {
        $accountantSettings = getAccountantSettings($db);
        if ($accountantSettings['enabled']) {
            $nl = "\r\n";
            $width = 56;
            $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
            $content = $rtl;
            
            $restaurantName = getRestaurantName($db);
            $content .= str_pad($restaurantName, $width, ' ', STR_PAD_BOTH) . $nl;
            $content .= str_repeat("=", $width) . $nl;
            $content .= str_pad(t('session') . ": " . $session_number, $width, ' ', STR_PAD_BOTH) . $nl;
            $content .= str_pad(t('closed_by') . ": " . $waiter_name, $width, ' ', STR_PAD_BOTH) . $nl;
            $content .= str_pad(t('bill_details'), $width, ' ', STR_PAD_BOTH) . $nl;
            $content .= str_repeat("=", $width) . $nl;
            $content .= t('table') . ": " . $table_display . $nl;
            if (!empty($tableInfo['customer_name'])) {
                $content .= t('customer_name') . ": " . $tableInfo['customer_name'] . $nl;
            }
            $content .= t('time') . ": " . date('d/m/Y H:i:s') . $nl;
            $content .= str_repeat("-", $width) . $nl;
            
            // Headers
            $headerQty = t('qty');
            $headerItem = t('item');
            $headerTotal = t('total');
            $content .= str_pad($headerQty, 4) . " " . str_pad($headerItem, 26) . " " . $headerTotal . $nl;
            $content .= str_repeat("-", $width) . $nl;
            
            foreach ($allItems as $item) {
                $qty = $item['quantity'];
                $name = $item['name'];
                $unitPrice = $item['unit_price'];
                $total = $item['subtotal'];
                $line = str_pad($qty, 4) . " " . str_pad(mb_substr($name, 0, 26), 26) . " " . formatPriceReceipt($total, $lang);
                $content .= $line . $nl;
                if (!empty($item['notes'])) {
                    $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
                }
            }
            
            $content .= str_repeat("-", $width) . $nl;
            $left = t('total') . ":";
            $right = formatPriceReceipt($totalAmount, $lang);
            $dotLen = $width - strlen($left) - strlen($right) - 1;
            $dots = str_repeat('.', max($dotLen, 1));
            $content .= $left . " " . $dots . " " . $right . $nl;
            $content .= str_repeat("=", $width) . $nl;
            $content .= str_pad(t('thank_you'), $width, ' ', STR_PAD_BOTH) . $nl;
            $content .= str_repeat("=", $width) . $nl;
            $content .= "\x0C";
            
            $printer = [
                'type' => $accountantSettings['type'],
                'name' => $accountantSettings['name'],
                'ip' => $accountantSettings['ip'],
                'port' => $accountantSettings['port']
            ];
            sendPrintJob($printer, $content);
        }
    } elseif ($isReserved && $print_bill && !empty($allItems)) {
        // Reserved table - still print a bill? The requirement says skip, but we log.
        error_log("Bill skipped for reserved table ID: $table_id (Reserved for: $reserved_for)");
    }
    
    // Close the session and free the table
    $update = $db->prepare("UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE id = ?");
    $update->execute([$session_id]);
    
    $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE id = ?")->execute([$table_id]);
    
    $msg = $print_bill ? (empty($allItems) ? 'Session closed (no items to print)' : 'Session closed and bill printed') : 'Session closed without printing';
    echo json_encode(['success' => true, 'total' => $totalAmount, 'message' => $msg]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>