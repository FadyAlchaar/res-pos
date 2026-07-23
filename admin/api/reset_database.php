<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the raw input
$input = json_decode(file_get_contents('php://input'), true);
$confirm = isset($input['confirm']) ? $input['confirm'] : '';

if ($confirm !== 'RESET') {
    echo json_encode(['success' => false, 'message' => 'Confirmation required. Type "RESET" to confirm.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get counts before reset
    $counts = [];
    $counts['orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $counts['order_items'] = $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
    $counts['print_jobs'] = $db->query("SELECT COUNT(*) FROM print_jobs")->fetchColumn();
    $counts['printer_logs'] = $db->query("SELECT COUNT(*) FROM printer_logs")->fetchColumn();
    
    // TRUNCATE ALL DATA TABLES
    $db->exec("TRUNCATE TABLE print_jobs");
    $db->exec("TRUNCATE TABLE order_items");
    $db->exec("TRUNCATE TABLE orders");
    $db->exec("TRUNCATE TABLE printer_logs");
    
    // Reset restaurant tables to available
    $db->exec("UPDATE restaurant_tables SET status = 'available'");
    
    // OPTIONAL: Reset kitchen status (uncomment if you want)
    // $db->exec("UPDATE kitchens SET status = 'offline', last_checked = NULL");
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "✅ Database reset completed!\n\nDeleted:\n- {$counts['orders']} orders\n- {$counts['order_items']} order items\n- {$counts['print_jobs']} print jobs\n- {$counts['printer_logs']} printer logs\n\nAll tables are now empty.",
        'deleted' => $counts
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    // Re-enable foreign key checks
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {
        // Ignore
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>