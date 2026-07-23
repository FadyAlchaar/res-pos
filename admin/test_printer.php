<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
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
    
    // Get kitchen printer info
    $query = "SELECT * FROM kitchens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $kitchen_id]);
    $kitchen = $stmt->fetch();
    
    if (!$kitchen) {
        echo json_encode(['success' => false, 'message' => 'Kitchen not found']);
        exit;
    }
    
    if (empty($kitchen['printer_ip'])) {
        echo json_encode(['success' => false, 'message' => 'No printer IP configured for this kitchen']);
        exit;
    }
    
    // Format test page content
    $test_content = "\n";
    $test_content .= str_repeat("=", 42) . "\n";
    $test_content .= "      PRINTER TEST PAGE\n";
    $test_content .= str_repeat("=", 42) . "\n";
    $test_content .= "Kitchen: " . $kitchen['name'] . "\n";
    $test_content .= "IP: " . $kitchen['printer_ip'] . "\n";
    $test_content .= "Port: " . $kitchen['printer_port'] . "\n";
    $test_content .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $test_content .= str_repeat("-", 42) . "\n";
    $test_content .= "This is a test print from admin panel\n";
    $test_content .= str_repeat("=", 42) . "\n\n";
    
    // For simulator IPs (192.168.1.x) - consider them online
    if (preg_match('/^192\.168\.1\./', $kitchen['printer_ip'])) {
        $status = 'online';
        $message = 'Printer is online (simulator mode)';
        
        // Instead of inserting into print_jobs with order_item_id=0,
        // we'll just log it in printer_logs
        $log = "INSERT INTO printer_logs (kitchen_id, printer_ip, status, message) 
                VALUES (:kitchen_id, :printer_ip, :status, :message)";
        $stmt = $db->prepare($log);
        $stmt->execute([
            ':kitchen_id' => $kitchen_id,
            ':printer_ip' => $kitchen['printer_ip'],
            ':status' => $status,
            ':message' => 'Test print simulated'
        ]);
        
    } else {
        // Try to connect to real printer
        $fp = @fsockopen($kitchen['printer_ip'], $kitchen['printer_port'], $errno, $errstr, 5);
        
        if ($fp) {
            // Send test content to printer
            fwrite($fp, $test_content);
            fclose($fp);
            $status = 'online';
            $message = 'Printer is online and test page sent successfully';
        } else {
            $status = 'offline';
            $message = "Printer offline: $errstr";
        }
    }
    
    // Update kitchen status in database
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