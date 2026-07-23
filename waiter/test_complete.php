<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Log start
file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " ========== START ==========\n", FILE_APPEND);

$input = file_get_contents('php://input');
file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " Input: " . $input . "\n", FILE_APPEND);

$data = json_decode($input, true);

try {
    $database = new Database();
    $db = $database->getConnection();
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " DB Connected\n", FILE_APPEND);
    
    $order_number = 'TEST-' . date('Ymd') . '-' . rand(100, 999);
    $table_id = $data['table_id'] ?? 1;
    $waiter_id = (int)$_SESSION['user_id'] ?? 1;
    $table_number = $db->quote($data['table_number'] ?? 'Test');
    
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " Creating order...\n", FILE_APPEND);
    
    // Insert order
    $query = "INSERT INTO orders (order_number, waiter_id, table_id, table_number, status) 
              VALUES ('$order_number', $waiter_id, $table_id, $table_number, 'pending')";
    $db->exec($query);
    $order_id = $db->lastInsertId();
    
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " Order created: $order_id\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'order_number' => $order_number,
        'order_id' => $order_id,
        'message' => 'Test order created'
    ]);
    
} catch (Exception $e) {
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug.txt', date('Y-m-d H:i:s') . " ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>