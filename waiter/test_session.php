<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $table_id = $data['table_id'] ?? 1;
    $waiter_id = (int)$_SESSION['user_id'] ?? 1;
    
    // Check if session table exists
    $table_check = $db->query("SHOW TABLES LIKE 'sessions'")->fetch();
    if (!$table_check) {
        echo json_encode(['success' => false, 'message' => 'Sessions table does not exist']);
        exit;
    }
    
    // Try to create session
    $session_number = 'TEST-' . date('Ymd') . '-' . rand(100, 999);
    $insert = "INSERT INTO sessions (session_number, table_id, customer_count, waiter_id, status) 
               VALUES ('$session_number', $table_id, 1, $waiter_id, 'open')";
    $db->exec($insert);
    $session_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Session created',
        'session_id' => $session_id,
        'session_number' => $session_number
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>