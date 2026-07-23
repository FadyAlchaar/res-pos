<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get settings using direct queries
    $total = 60;
    $prefix = 'Table ';
    
    $stmt = $db->query("SELECT setting_value FROM restaurant_settings WHERE setting_key = 'total_tables'");
    $result = $stmt->fetch();
    if ($result) {
        $total = (int)$result['setting_value'];
    }
    
    $stmt = $db->query("SELECT setting_value FROM restaurant_settings WHERE setting_key = 'table_prefix'");
    $result = $stmt->fetch();
    if ($result) {
        $prefix = $result['setting_value'];
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Mark all tables as inactive
    $db->exec("UPDATE restaurant_tables SET is_active = 0");
    
    // Get existing table numbers
    $existing = [];
    $result = $db->query("SELECT table_number FROM restaurant_tables");
    while ($row = $result->fetch()) {
        $existing[] = $row['table_number'];
    }
    
    // Generate new tables
    $inserted = 0;
    $updated = 0;
    
    for ($i = 1; $i <= $total; $i++) {
        $table_name = $prefix . $i;
        
        if (in_array($i, $existing)) {
            // Reactivate existing table
            $stmt = $db->prepare("UPDATE restaurant_tables 
                                  SET is_active = 1, 
                                      table_name = :table_name,
                                      status = 'available'
                                  WHERE table_number = :table_number");
            $stmt->execute([
                ':table_name' => $table_name,
                ':table_number' => $i
            ]);
            $updated++;
        } else {
            // Insert new table
            $stmt = $db->prepare("INSERT INTO restaurant_tables 
                                  (table_number, table_name, capacity, status, sort_order, is_active) 
                                  VALUES 
                                  (:table_number, :table_name, 4, 'available', :sort_order, 1)");
            $stmt->execute([
                ':table_number' => $i,
                ':table_name' => $table_name,
                ':sort_order' => $i
            ]);
            $inserted++;
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Tables regenerated: $inserted new, $updated reactivated",
        'total' => $total,
        'inserted' => $inserted,
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>