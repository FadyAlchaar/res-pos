<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Update or insert each setting
    $fields = ['restaurant_name', 'total_tables', 'table_prefix'];
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $stmt = $db->prepare("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$field, $data[$field]]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings saved']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>