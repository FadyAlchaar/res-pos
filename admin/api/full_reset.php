<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$confirm = isset($input['confirm']) ? $input['confirm'] : '';

if ($confirm !== 'FULL_RESET') {
    echo json_encode(['success' => false, 'message' => 'Confirmation required. Type "FULL_RESET" to confirm complete wipe.']);
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
    $counts = [
        'kitchens' => $db->query("SELECT COUNT(*) FROM kitchens")->fetchColumn(),
        'categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'menu_items' => $db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn(),
        'orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'order_items' => $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn(),
        'print_jobs' => $db->query("SELECT COUNT(*) FROM print_jobs")->fetchColumn(),
        'printer_logs' => $db->query("SELECT COUNT(*) FROM printer_logs")->fetchColumn(),
        'restaurant_tables' => $db->query("SELECT COUNT(*) FROM restaurant_tables")->fetchColumn(),
        'users' => $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn()
    ];
    
    // TRUNCATE ALL TABLES
    $db->exec("TRUNCATE TABLE print_jobs");
    $db->exec("TRUNCATE TABLE order_items");
    $db->exec("TRUNCATE TABLE orders");
    $db->exec("TRUNCATE TABLE printer_logs");
    $db->exec("TRUNCATE TABLE menu_items");
    $db->exec("TRUNCATE TABLE categories");
    $db->exec("TRUNCATE TABLE restaurant_tables");
    
    // Delete non-admin users (keep admin)
    $db->exec("DELETE FROM users WHERE role != 'admin'");
    
    // Reset auto-increment counters
    $db->exec("ALTER TABLE print_jobs AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE printer_logs AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE menu_items AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE restaurant_tables AUTO_INCREMENT = 1");
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "✅ COMPLETE DATABASE RESET completed!\n\nDeleted:\n- {$counts['kitchens']} kitchens\n- {$counts['categories']} categories\n- {$counts['menu_items']} menu items\n- {$counts['orders']} orders\n- {$counts['order_items']} order items\n- {$counts['print_jobs']} print jobs\n- {$counts['printer_logs']} printer logs\n- {$counts['restaurant_tables']} tables\n- {$counts['users']} non-admin users\n\nAll tables are now empty. You need to run setup again.",
        'deleted' => $counts
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {}
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>