<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$layout = isset($data['layout']) ? json_encode($data['layout']) : '{}';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO restaurant_settings (setting_key, setting_value) 
              VALUES ('table_layout', :layout)
              ON DUPLICATE KEY UPDATE setting_value = :layout";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':layout' => $layout]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>