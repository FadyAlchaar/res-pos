<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all active kitchens with printers
    $query = "SELECT id, name, printer_ip, printer_port FROM kitchens WHERE is_active = 1 AND printer_ip IS NOT NULL";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    $results = [
        'total' => count($kitchens),
        'online' => 0,
        'offline' => 0,
        'details' => []
    ];
    
    foreach ($kitchens as $kitchen) {
        // Test each printer
        $fp = @fsockopen($kitchen['printer_ip'], $kitchen['printer_port'], $errno, $errstr, 3);
        
        if ($fp) {
            fclose($fp);
            $status = 'online';
            $results['online']++;
            
            // Update status in database
            $update = "UPDATE kitchens SET status = 'online', last_checked = NOW() WHERE id = :id";
            $stmt2 = $db->prepare($update);
            $stmt2->execute([':id' => $kitchen['id']]);
            
        } else {
            $status = 'offline';
            $results['offline']++;
            
            // Update status in database
            $update = "UPDATE kitchens SET status = 'offline', last_checked = NOW() WHERE id = :id";
            $stmt2 = $db->prepare($update);
            $stmt2->execute([':id' => $kitchen['id']]);
        }
        
        $results['details'][] = [
            'kitchen' => $kitchen['name'],
            'ip' => $kitchen['printer_ip'],
            'status' => $status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'count' => $results['total'],
        'online' => $results['online'],
        'offline' => $results['offline'],
        'details' => $results['details']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>