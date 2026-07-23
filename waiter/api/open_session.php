<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$table_id = (int)$data['table_id'];
$customer_count = isset($data['customer_count']) ? (int)$data['customer_count'] : 1;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if table already has open session
    $check = $db->query("SELECT id FROM table_sessions WHERE table_id = $table_id AND status = 'open'")->fetch();
    
    if ($check) {
        echo json_encode([
            'success' => true, 
            'session_id' => $check['id'],
            'message' => 'Session already open'
        ]);
        exit;
    }
    
    // Generate session number
    $session_number = 'S' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $waiter_id = $_SESSION['user_id'];
    
    // Create new session
    $query = "INSERT INTO table_sessions (session_number, table_id, waiter_id, customer_count, status) 
              VALUES (:session_number, :table_id, :waiter_id, :customer_count, 'open')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':session_number' => $session_number,
        ':table_id' => $table_id,
        ':waiter_id' => $waiter_id,
        ':customer_count' => $customer_count
    ]);
    
    $session_id = $db->lastInsertId();
    
    // Update table status
    $db->exec("UPDATE restaurant_tables SET 
               session_open = 1, 
               session_opened_at = NOW(), 
               session_waiter_id = $waiter_id,
               session_customer_count = $customer_count,
               status = 'occupied'
               WHERE id = $table_id");
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'session_number' => $session_number,
        'message' => 'Session opened'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>