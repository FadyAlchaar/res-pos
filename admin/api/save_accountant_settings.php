<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $settings = [
        'accountant_print_enabled' => $data['accountant_print_enabled'] ?? '1',
        'accountant_printer_type' => $data['accountant_printer_type'] ?? 'windows',
    ];
    
    if ($data['accountant_printer_type'] === 'windows') {
        $settings['accountant_printer_name'] = $data['accountant_printer_name'] ?? '';
        $settings['accountant_printer_ip'] = '';
        $settings['accountant_printer_port'] = '';
    } else {
        $settings['accountant_printer_ip'] = $data['accountant_printer_ip'] ?? '';
        $settings['accountant_printer_port'] = $data['accountant_printer_port'] ?? 9100;
        $settings['accountant_printer_name'] = '';
    }
    
    foreach ($settings as $key => $value) {
        $value_quoted = $db->quote($value);
        $db->exec("INSERT INTO restaurant_settings (setting_key, setting_value) 
                   VALUES ('$key', $value_quoted)
                   ON DUPLICATE KEY UPDATE setting_value = $value_quoted");
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>