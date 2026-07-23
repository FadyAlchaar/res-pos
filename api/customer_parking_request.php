<?php
require_once '../config/config.php';
require_once '../config/language.php';
require_once '../includes/print_functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['lot_numbers'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parking lot numbers']);
    exit;
}

$lot_numbers = $data['lot_numbers'];
$lang = isset($data['lang']) ? $data['lang'] : 'en';
$GLOBALS['lang'] = $lang;

try {
    $db = (new Database())->getConnection();
    
    // Insert parking request without table number (table_number = 0 or NULL)
    // We'll use table_number = 0 to indicate it's from the generic QR.
    $stmt = $db->prepare("INSERT INTO parking_requests (session_id, table_number, lot_numbers) VALUES (NULL, 0, ?)");
    $stmt->execute([$lot_numbers]);
    
    echo json_encode(['success' => true, 'message' => t('request_sent')]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>