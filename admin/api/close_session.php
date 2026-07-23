<?php
require_once '../../config/config.php';
require_once '../../config/language.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['session_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

$session_id = (int)$data['session_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if session exists and is open
    $stmt = $db->prepare("SELECT id, table_id FROM table_sessions WHERE id = :id AND status = 'open'");
    $stmt->execute([':id' => $session_id]);
    $session = $stmt->fetch();
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or already closed']);
        exit;
    }
    
    // Close the session
    $update = $db->prepare("UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE id = :id");
    $update->execute([':id' => $session_id]);
    
    // Optionally, free the table (set status to 'available')
    $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE id = :table_id")->execute([':table_id' => $session['table_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Session closed']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>