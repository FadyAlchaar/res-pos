<?php
require_once '../../config/config.php';
require_once '../../includes/session_helper.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$session_id = (int)$data['session_id'];
$new_table = (int)$data['new_table'];

if (!$session_id || !$new_table) {
    echo json_encode(['success' => false, 'message' => 'Session ID and new table required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Find new table ID
    $table = $db->query("SELECT id FROM restaurant_tables WHERE table_number = $new_table")->fetch();
    if (!$table) {
        echo json_encode(['success' => false, 'message' => 'Table not found']);
        exit;
    }
    
    $result = transferSession($db, $session_id, $table['id'], $_SESSION['user_id']);
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>