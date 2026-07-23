<?php
// Enable error display for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug file (relative to project root)
$debug_file = __DIR__ . '/../debug_order.txt';
$debug_dir = dirname($debug_file);
if (!file_exists($debug_dir)) {
    mkdir($debug_dir, 0777, true);
}
file_put_contents($debug_file, date('Y-m-d H:i:s') . " ===== START =====\n", FILE_APPEND);

require_once '../config/config.php';
require_once '../config/language.php';
require_once ROOT_PATH . '/includes/print_functions.php';

header('Content-Type: application/json');

// Log start
file_put_contents($debug_file, date('Y-m-d H:i:s') . " Request started\n", FILE_APPEND);

// Authentication
if (!isLoggedIn()) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Not logged in\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents($debug_file, date('Y-m-d H:i:s') . " Received data: " . json_encode($data) . "\n", FILE_APPEND);

if (!$data || empty($data['items'])) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Invalid order data\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

if (empty($data['table_id'])) {
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " No table selected\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a table']);
    exit;
}

$printing_errors = []; // collect any print failures for debugging

try {
    $database = new Database();
    $db = $database->getConnection();
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " DB connected\n", FILE_APPEND);

    $db->beginTransaction();

    // Create order
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $waiter_id = (int)$_SESSION['user_id'];
    $table_id = (int)$data['table_id'];
    $table_number = $db->quote($data['table_number']);

    $query = "INSERT INTO orders (order_number, waiter_id, table_id, table_number, status) 
              VALUES ('$order_number', $waiter_id, $table_id, $table_number, 'pending')";
    $db->exec($query);
    $order_id = $db->lastInsertId();
    $total_amount = 0;

    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Order created: $order_id\n", FILE_APPEND);

    // Check for existing session
    $session = $db->query("SELECT id FROM table_sessions WHERE table_id = $table_id AND status = 'open'")->fetch();
    if ($session) {
        $db->exec("UPDATE orders SET session_id = {$session['id']} WHERE id = $order_id");
        // Update session total later after items are added
    }

    // Update table status
    $db->exec("UPDATE restaurant_tables SET status = 'occupied' WHERE id = $table_id");

    $items_by_kitchen = [];

    // Insert order items and group by kitchen
    foreach ($data['items'] as $item) {
        $menu_item_id = (int)$item['id'];
        $kitchen_id = (int)$item['kitchen_id'];
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];
        $subtotal = $price * $quantity;
        $total_amount += $subtotal;
        $notes = $db->quote($item['notes'] ?? '');

        $query = "INSERT INTO order_items 
                  (order_id, menu_item_id, kitchen_id, quantity, unit_price, subtotal, notes) 
                  VALUES 
                  ($order_id, $menu_item_id, $kitchen_id, $quantity, $price, $subtotal, $notes)";
        $db->exec($query);
        $order_item_id = $db->lastInsertId();

        // Get item name for receipts
        $nameQuery = "SELECT name FROM menu_items WHERE id = $menu_item_id";
        $nameResult = $db->query($nameQuery);
        $itemName = $nameResult->fetch()['name'];

        if (!isset($items_by_kitchen[$kitchen_id])) {
            $items_by_kitchen[$kitchen_id] = [];
        }

        $items_by_kitchen[$kitchen_id][] = [
            'order_item_id' => $order_item_id,
            'name' => $itemName,
            'quantity' => $quantity,
            'notes' => $item['notes'] ?? '',
            'price' => $price
        ];
    }

    // Update order total
    $db->exec("UPDATE orders SET total_amount = $total_amount WHERE id = $order_id");

    // Update session total if session exists
    if ($session) {
        $session_total = $db->query("SELECT SUM(total_amount) FROM orders WHERE session_id = {$session['id']}")->fetchColumn();
        $db->exec("UPDATE table_sessions SET total_amount = $session_total WHERE id = {$session['id']}");
    }

    $db->commit();
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Transaction committed\n", FILE_APPEND);

    // ========== PRINTING SECTION ==========
    $kitchenPrintSuccess = [];
    $accountantPrinted = false;

    try {
        // Get accountant printer settings
        $accountantSettings = getAccountantSettings($db);

        // Retrieve waiter name
        $waiterName = $_SESSION['full_name'];

        // Retrieve session number if any
        $sessionNumber = '';
        if ($session) {
            $sessionData = $db->query("SELECT session_number FROM table_sessions WHERE id = {$session['id']}")->fetch();
            if ($sessionData) {
                $sessionNumber = $sessionData['session_number'];
            }
        }

        // Prepare items for accountant receipt (including subtotal)
        $accountantItems = [];
        foreach ($data['items'] as $item) {
            $accountantItems[] = [
                'name' => $item['name'] ?? '',
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity'],
                'notes' => $item['notes'] ?? ''
            ];
        }

        // 1. Print to each kitchen
        foreach ($items_by_kitchen as $kitchen_id => $kitchenItems) {
            $kitchenStmt = $db->prepare("SELECT * FROM kitchens WHERE id = :id");
            $kitchenStmt->execute([':id' => $kitchen_id]);
            $kitchen = $kitchenStmt->fetch();

            if (!$kitchen || (empty($kitchen['printer_ip']) && empty($kitchen['printer_name']))) {
                $kitchenPrintSuccess[] = [
                    'kitchen' => $kitchen['name'] ?? 'Unknown',
                    'success' => false,
                    'message' => 'No printer configured'
                ];
                continue;
            }

            $printer = [
                'type' => (!empty($kitchen['printer_name'])) ? 'windows' : 'network',
                'name' => $kitchen['printer_name'] ?? '',
                'ip' => $kitchen['printer_ip'] ?? '',
                'port' => $kitchen['printer_port'] ?? 9100
            ];

            $content = formatKitchenPrint(
                $kitchen['name'],
                $order_number,
                $data['table_number'],
                $kitchenItems,
                false
            );

            $printed = sendPrintJob($printer, $content);

            // Log print job
            $first_item = $kitchenItems[0];
            $status = $printed ? 'sent' : 'failed';
            $errorMsg = $printed ? '' : 'Print failed';
            $stmt = $db->prepare("INSERT INTO print_jobs (order_item_id, kitchen_id, printer_ip, content, status, error_message) 
                                  VALUES (:order_item_id, :kitchen_id, :printer_ip, :content, :status, :error_msg)");
            $stmt->execute([
                ':order_item_id' => $first_item['order_item_id'],
                ':kitchen_id' => $kitchen_id,
                ':printer_ip' => $printer['ip'],
                ':content' => $content,
                ':status' => $status,
                ':error_msg' => $errorMsg
            ]);

            $kitchenPrintSuccess[] = [
                'kitchen' => $kitchen['name'],
                'success' => $printed,
                'message' => $printed ? 'Printed' : 'Failed'
            ];
        }

        // 2. Print to accountant printer if enabled
        if ($accountantSettings['enabled']) {
            $accountantPrinter = [
                'type' => $accountantSettings['type'],
                'name' => $accountantSettings['name'],
                'ip' => $accountantSettings['ip'],
                'port' => $accountantSettings['port']
            ];

            $content = formatAccountantPrint(
                $order_number,
                $data['table_number'],
                $accountantItems,
                $total_amount,
                $waiterName,
                $sessionNumber
            );

            $accountantPrinted = sendPrintJob($accountantPrinter, $content);

            if (!$accountantPrinted) {
                $printing_errors[] = "Accountant print failed for order $order_number";
                file_put_contents($debug_file, date('Y-m-d H:i:s') . " Accountant print failed\n", FILE_APPEND);
            } else {
                file_put_contents($debug_file, date('Y-m-d H:i:s') . " Accountant print succeeded\n", FILE_APPEND);
            }
        }

    } catch (Exception $e) {
        $printing_errors[] = "Printing error: " . $e->getMessage();
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " Printing error: " . $e->getMessage() . "\n", FILE_APPEND);
        // Do not re-throw – we want order to be considered successful even if printing fails
    }

    // Send final response (only one JSON)
    echo json_encode([
        'success' => true,
        'order_number' => $order_number,
        'message' => 'Order received!',
        'kitchen_prints' => $kitchenPrintSuccess,
        'accountant_printed' => $accountantPrinted,
        'printing_errors' => $printing_errors
    ]);
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Response sent\n", FILE_APPEND);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " Stack: " . $e->getTraceAsString() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>