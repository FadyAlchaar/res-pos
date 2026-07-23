<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "<h1>🖨️ Initialize Printer Statuses</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all kitchens
    $query = "SELECT id, name, printer_ip FROM kitchens WHERE is_active = 1";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    echo "<p>Found " . count($kitchens) . " kitchens</p>";
    echo "<ul>";
    
    foreach ($kitchens as $kitchen) {
        // For simulator IPs (192.168.1.x), set status to online
        if (preg_match('/^192\.168\.1\./', $kitchen['printer_ip'])) {
            $status = 'online';
            $message = 'Simulator printer - set to online';
        } 
        // For empty IP, set to offline
        elseif (empty($kitchen['printer_ip'])) {
            $status = 'offline';
            $message = 'No printer IP configured';
        }
        // For other IPs, try to test connection
        else {
            $fp = @fsockopen($kitchen['printer_ip'], 9100, $errno, $errstr, 2);
            if ($fp) {
                fclose($fp);
                $status = 'online';
                $message = 'Printer responded';
            } else {
                $status = 'offline';
                $message = "Connection failed: $errstr";
            }
        }
        
        // Update status
        $update = "UPDATE kitchens SET status = :status, last_checked = NOW() WHERE id = :id";
        $stmt2 = $db->prepare($update);
        $stmt2->execute([':status' => $status, ':id' => $kitchen['id']]);
        
        echo "<li>{$kitchen['name']} ({$kitchen['printer_ip']}) - <strong>{$status}</strong> - {$message}</li>";
    }
    
    echo "</ul>";
    echo "<p style='color: green; margin-top: 20px;'>✅ Printer statuses initialized!</p>";
    echo "<p><a href='dashboard.php'>Return to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>