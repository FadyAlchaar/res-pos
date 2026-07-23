<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$kitchen_id = isset($_GET['kitchen_id']) ? (int)$_GET['kitchen_id'] : 0;

if (!$kitchen_id) {
    echo json_encode(['success' => false, 'message' => 'Kitchen ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM kitchens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $kitchen_id]);
    $kitchen = $stmt->fetch();
    
    if (!$kitchen) {
        echo json_encode(['success' => false, 'message' => 'Kitchen not found']);
        exit;
    }
    
    if (empty($kitchen['printer_ip'])) {
        echo json_encode(['success' => false, 'message' => 'No printer IP configured']);
        exit;
    }
    
    $status = 'offline';
    $message = '';
    
    // For simulator IPs, consider online
    if (preg_match('/^192\.168\.1\./', $kitchen['printer_ip'])) {
        $status = 'online';
        $message = 'Simulator printer - online';
        
        // Create a test print job WITHOUT order_item_id (set to NULL instead of 0)
        $test_content = "\n";
        $test_content .= str_repeat("=", 42) . "\n";
        $test_content .= "      PRINTER TEST\n";
        $test_content .= str_repeat("=", 42) . "\n";
        $test_content .= "Kitchen: " . $kitchen['name'] . "\n";
        $test_content .= "IP: " . $kitchen['printer_ip'] . "\n";
        $test_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $test_content .= "Test successful!\n";
        $test_content .= str_repeat("=", 42) . "\n\n";
        
        // Use NULL for order_item_id instead of 0
        $content_quoted = $db->quote($test_content);
        $ip_quoted = $db->quote($kitchen['printer_ip']);
        
        $db->exec("INSERT INTO print_jobs (order_item_id, kitchen_id, printer_ip, content, status) 
                  VALUES (NULL, {$kitchen['id']}, $ip_quoted, $content_quoted, 'pending')");
        
    } else {
        // Try real printer connection
        $fp = @fsockopen($kitchen['printer_ip'], $kitchen['printer_port'] ?? 9100, $errno, $errstr, 3);
        if ($fp) {
            fclose($fp);
            $status = 'online';
            $message = 'Printer is online and responding';
        } else {
            $message = "Connection failed: $errstr";
        }
    }
    
    // Update kitchen status
    $update = "UPDATE kitchens SET status = :status, last_checked = NOW() WHERE id = :id";
    $stmt = $db->prepare($update);
    $stmt->execute([':status' => $status, ':id' => $kitchen_id]);
    
    echo json_encode([
        'success' => true,
        'status' => $status,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>