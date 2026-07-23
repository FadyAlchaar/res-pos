<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

if (empty($data['table_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a table']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert order with table_id
    $query = "INSERT INTO orders (order_number, waiter_id, table_id, table_number, status) 
              VALUES (:order_number, :waiter_id, :table_id, :table_number, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':order_number' => $order_number,
        ':waiter_id' => $_SESSION['user_id'],
        ':table_id' => $data['table_id'],
        ':table_number' => $data['table_number']
    ]);
    
    $order_id = $db->lastInsertId();
    $total_amount = 0;
    $print_results = [];
    
    // Update table status to occupied
    $updateTable = "UPDATE restaurant_tables SET status = 'occupied' WHERE id = :id";
    $stmt = $db->prepare($updateTable);
    $stmt->execute([':id' => $data['table_id']]);
    
    // Group items by kitchen
    $items_by_kitchen = [];
    
    // Insert order items
    foreach ($data['items'] as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total_amount += $subtotal;
        
        $query = "INSERT INTO order_items 
                  (order_id, menu_item_id, kitchen_id, quantity, unit_price, subtotal, notes) 
                  VALUES 
                  (:order_id, :menu_item_id, :kitchen_id, :quantity, :unit_price, :subtotal, :notes)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':order_id' => $order_id,
            ':menu_item_id' => $item['id'],
            ':kitchen_id' => $item['kitchen_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $item['price'],
            ':subtotal' => $subtotal,
            ':notes' => $item['notes'] ?? null
        ]);
        
        $order_item_id = $db->lastInsertId();
        
        // Group for printing by kitchen
        if (!isset($items_by_kitchen[$item['kitchen_id']])) {
            $items_by_kitchen[$item['kitchen_id']] = [];
        }
        
        $items_by_kitchen[$item['kitchen_id']][] = [
            'order_item_id' => $order_item_id,
            'name' => getItemName($db, $item['id']),
            'quantity' => $item['quantity'],
            'notes' => $item['notes'] ?? '',
            'price' => $item['price']
        ];
    }
    
    // Update order total
    $query = "UPDATE orders SET total_amount = :total WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':total' => $total_amount, ':id' => $order_id]);
    
    // Send to printers - ONE JOB PER KITCHEN (not per item)
    foreach ($items_by_kitchen as $kitchen_id => $kitchen_items) {
        $print_result = sendToKitchenPrinter($db, $kitchen_id, $order_number, $data['table_number'], $kitchen_items);
        $print_results[] = $print_result;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'order_number' => $order_number,
        'print_jobs' => $print_results
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Helper function to get item name by ID
 */
function getItemName($db, $item_id) {
    $query = "SELECT name FROM menu_items WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $item_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Unknown Item';
}

/**
 * Send a single print job for all items in a kitchen
 * This creates ONE print job per kitchen, not one per item
 */
function sendToKitchenPrinter($db, $kitchen_id, $order_number, $table_number, $items) {
    // Get kitchen printer info
    $query = "SELECT name, printer_ip, printer_port FROM kitchens WHERE id = :id AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $kitchen_id]);
    $kitchen = $stmt->fetch();
    
    if (!$kitchen || !$kitchen['printer_ip']) {
        error_log("No printer configured for kitchen ID: " . $kitchen_id);
        return [
            'kitchen' => 'Unknown',
            'status' => 'failed',
            'message' => 'No printer configured'
        ];
    }
    
    // Format ONE print job for ALL items in this kitchen
    $content = formatKitchenPrintContent($kitchen['name'], $order_number, $table_number, $items);
    
    // Create ONE print job for this kitchen (using the first item's ID as reference)
    // All items from this kitchen will be in this single print job
    $first_item = $items[0];
    
    $query = "INSERT INTO print_jobs 
              (order_item_id, kitchen_id, printer_ip, content, status) 
              VALUES 
              (:order_item_id, :kitchen_id, :printer_ip, :content, 'pending')";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':order_item_id' => $first_item['order_item_id'],
        ':kitchen_id' => $kitchen_id,
        ':printer_ip' => $kitchen['printer_ip'],
        ':content' => $content
    ]);
    
    if ($result) {
        return [
            'kitchen' => $kitchen['name'],
            'status' => 'pending',
            'message' => 'Print job created',
            'item_count' => count($items)
        ];
    } else {
        return [
            'kitchen' => $kitchen['name'],
            'status' => 'failed',
            'message' => 'Failed to create print job'
        ];
    }
}

/**
 * Format the print content with all items from the same kitchen
 * This creates a single ticket with multiple items
 */
function formatKitchenPrintContent($kitchen_name, $order_number, $table_number, $items) {
    $content = "\n";
    $content .= str_repeat("=", 42) . "\n";
    $content .= "        " . strtoupper($kitchen_name) . "\n";
    $content .= str_repeat("=", 42) . "\n";
    $content .= "Order #: " . $order_number . "\n";
    $content .= "Table  : " . $table_number . "\n";
    $content .= "Time   : " . date('d/m/Y H:i:s') . "\n";
    $content .= "Server : " . $_SESSION['full_name'] . "\n";
    $content .= str_repeat("-", 42) . "\n";
    $content .= "ITEMS ORDERED:\n";
    $content .= str_repeat("-", 42) . "\n";
    
    $total_items = 0;
    foreach ($items as $index => $item) {
        $total_items += $item['quantity'];
        $content .= $item['quantity'] . "x " . $item['name'] . "\n";
        if (!empty($item['notes'])) {
            $content .= "   📝 " . $item['notes'] . "\n";
        }
        // Add separator between items except for the last one
        if ($index < count($items) - 1) {
            $content .= str_repeat("-", 42) . "\n";
        }
    }
    
    $content .= str_repeat("=", 42) . "\n";
    $content .= "TOTAL ITEMS: " . $total_items . "\n";
    $content .= str_repeat("=", 42) . "\n";
    $content .= "        KITCHEN COPY\n";
    $content .= str_repeat("=", 42) . "\n\n";
    
    return $content;
}
?>