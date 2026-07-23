<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$table_id = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
if (!$table_id) {
    echo json_encode(['success' => false, 'message' => 'Table ID required']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT id FROM table_sessions WHERE table_id = ? AND status = 'open'");
    $stmt->execute([$table_id]);
    $session = $stmt->fetch();
    if ($session) {
        echo json_encode(['success' => true, 'session_id' => $session['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No open session for this table']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>