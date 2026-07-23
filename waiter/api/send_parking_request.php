<?php
// Absolute path to avoid path issues
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/language.php';
header('Content-Type: application/json');

// Debug log (optional, remove in production)
error_log("send_parking_request.php called. Session ID: " . session_id());

if (!isLoggedIn()) {
    error_log("send_parking_request.php: Not logged in");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'waiter') {
    error_log("send_parking_request.php: Invalid role: " . $_SESSION['role']);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['session_id']) || empty($data['lot_numbers'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID and lot numbers required']);
    exit;
}

$session_id = (int)$data['session_id'];
$lot_numbers = $data['lot_numbers'];

try {
    $db = (new Database())->getConnection();

    // Get table number from session
    $stmt = $db->prepare("SELECT t.table_number FROM table_sessions ts JOIN restaurant_tables t ON ts.table_id = t.id WHERE ts.id = ?");
    $stmt->execute([$session_id]);
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$table) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    $table_number = $table['table_number'];

    // Insert into parking_requests
    $stmt = $db->prepare("INSERT INTO parking_requests (session_id, table_number, lot_numbers) VALUES (?, ?, ?)");
    $stmt->execute([$session_id, $table_number, $lot_numbers]);

    echo json_encode(['success' => true, 'message' => 'Parking request sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>