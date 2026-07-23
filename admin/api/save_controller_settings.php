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
    
    $enabled = isset($data['controller_print_enabled']) ? (int)$data['controller_print_enabled'] : 0;
    $printerName = $data['controller_printer_name'] ?? '';
    
    // Use REPLACE INTO (requires setting_key to be a primary or unique key)
    $stmt = $db->prepare("REPLACE INTO restaurant_settings (setting_key, setting_value) VALUES ('controller_print_enabled', :enabled)");
    $stmt->execute([':enabled' => $enabled]);
    
    $stmt = $db->prepare("REPLACE INTO restaurant_settings (setting_key, setting_value) VALUES ('controller_printer_name', :name)");
    $stmt->execute([':name' => $printerName]);
    
    echo json_encode(['success' => true, 'message' => 'Controller settings saved']);
} catch (Exception $e) {
    // Fallback: delete then insert (if REPLACE fails)
    try {
        $db->prepare("DELETE FROM restaurant_settings WHERE setting_key = 'controller_print_enabled'")->execute();
        $db->prepare("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('controller_print_enabled', ?)")->execute([$enabled]);
        
        $db->prepare("DELETE FROM restaurant_settings WHERE setting_key = 'controller_printer_name'")->execute();
        $db->prepare("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('controller_printer_name', ?)")->execute([$printerName]);
        
        echo json_encode(['success' => true, 'message' => 'Controller settings saved']);
    } catch (Exception $e2) {
        echo json_encode(['success' => false, 'message' => $e2->getMessage()]);
    }
}
?>