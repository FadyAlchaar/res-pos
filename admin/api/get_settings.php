<?php
require_once '../../config/config.php';
require_once '../../config/language.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get settings
    $settings = [];
    $result = $db->query("SELECT setting_key, setting_value FROM restaurant_settings");
    while ($row = $result->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get active tables count
    $tables_result = $db->query("SELECT COUNT(*) as count FROM restaurant_tables WHERE is_active = 1");
    $active_tables = $tables_result->fetch()['count'];
    
    // Get all active tables for preview
    //$tables = $db->query("SELECT id, table_number, table_name, status, is_reserved, reserved_for FROM restaurant_tables WHERE is_active = 1 ORDER BY table_number LIMIT 20");
    $tables = $db->query("SELECT id, table_number, table_name, status, is_reserved, reserved_for, customer_name FROM restaurant_tables WHERE is_active = 1 ORDER BY table_number LIMIT 20");
    $table_list = $tables->fetchAll();
    
    // Get today's orders count
    $orders_result = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $today_orders = $orders_result->fetch()['count'];
    
    // Get printer status
    $online_printers = $db->query("SELECT COUNT(*) as count FROM kitchens WHERE status = 'online' AND is_active = 1")->fetchColumn();
    $total_printers = $db->query("SELECT COUNT(*) as count FROM kitchens WHERE is_active = 1")->fetchColumn();
    
    // Build response (no 'success' wrapper)
    $response = [
        'restaurant_name' => $settings['restaurant_name'] ?? 'My Restaurant',
        'total_tables' => (int)($settings['total_tables'] ?? 60),
        'table_prefix' => $settings['table_prefix'] ?? 'Table ',
        'active_tables' => $active_tables,
        'tables' => $table_list,
        'today_orders' => $today_orders,
        'online_printers' => (int)$online_printers,
        'total_printers' => (int)$total_printers,
        'accountant_print_enabled' => $settings['accountant_print_enabled'] ?? '1',
        'accountant_printer_type' => $settings['accountant_printer_type'] ?? 'windows',
        'accountant_printer_name' => $settings['accountant_printer_name'] ?? '',
        'accountant_printer_ip' => $settings['accountant_printer_ip'] ?? '',
        'accountant_printer_port' => (int)($settings['accountant_printer_port'] ?? 9100),
        'controller_print_enabled' => $settings['controller_print_enabled'] ?? '0',
        'controller_printer_name' => $settings['controller_printer_name'] ?? ''
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>