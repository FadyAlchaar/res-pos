<?php
require_once 'config/config.php';

echo "<h1>🖨️ Printer System Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check if kitchens have printer IPs
    echo "<h2>1. Kitchen Printer Configuration:</h2>";
    $query = "SELECT id, name, printer_ip, printer_port, status FROM kitchens WHERE is_active = 1";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Kitchen</th><th>Printer IP</th><th>Port</th><th>Status</th><th>Test</th></tr>";
    foreach ($kitchens as $k) {
        $testUrl = "admin/test_printer.php?kitchen_id=" . $k['id'];
        echo "<tr>";
        echo "<td>{$k['name']}</td>";
        echo "<td>{$k['printer_ip']}</td>";
        echo "<td>{$k['printer_port']}</td>";
        echo "<td>{$k['status']}</td>";
        echo "<td><a href='$testUrl' target='_blank'>Test Now</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 2: Check for pending print jobs
    echo "<h2>2. Pending Print Jobs:</h2>";
    $query = "SELECT COUNT(*) as count FROM print_jobs WHERE status = 'pending'";
    $stmt = $db->query($query);
    $count = $stmt->fetch()['count'];
    echo "<p>Pending jobs: <strong>$count</strong></p>";
    
    // Test 3: Try to send a test print
    echo "<h2>3. Send Test Print:</h2>";
    echo "<form method='post'>";
    echo "<select name='kitchen_id'>";
    foreach ($kitchens as $k) {
        echo "<option value='{$k['id']}'>{$k['name']}</option>";
    }
    echo "</select>";
    echo "<button type='submit' name='send_test'>Send Test Print</button>";
    echo "</form>";
    
    if (isset($_POST['send_test'])) {
        $kitchen_id = $_POST['kitchen_id'];
        
        // Get kitchen info
        $query = "SELECT * FROM kitchens WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $kitchen_id]);
        $kitchen = $stmt->fetch();
        
        if ($kitchen && $kitchen['printer_ip']) {
            $test_content = "\n";
            $test_content .= str_repeat("=", 40) . "\n";
            $test_content .= "      TEST PRINT\n";
            $test_content .= str_repeat("=", 40) . "\n";
            $test_content .= "Kitchen: " . $kitchen['name'] . "\n";
            $test_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $test_content .= "This is a manual test print\n";
            $test_content .= str_repeat("=", 40) . "\n\n";
            
            // For simulator IPs, just create a print job
            if (strpos($kitchen['printer_ip'], '192.168.1.') === 0) {
                // Create a dummy order for testing
                $order_number = 'TEST-' . date('Ymd') . '-' . rand(1000, 9999);
                
                $query = "INSERT INTO print_jobs (order_item_id, kitchen_id, printer_ip, content, status) 
                          VALUES (0, :kitchen_id, :printer_ip, :content, 'pending')";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':kitchen_id' => $kitchen_id,
                    ':printer_ip' => $kitchen['printer_ip'],
                    ':content' => $test_content
                ]);
                
                echo "<p style='color: green;'>✅ Test print job created! Check printer simulator.</p>";
            } else {
                // Try real printer
                $fp = @fsockopen($kitchen['printer_ip'], $kitchen['printer_port'], $errno, $errstr, 5);
                if ($fp) {
                    fwrite($fp, $test_content);
                    fclose($fp);
                    echo "<p style='color: green;'>✅ Test print sent successfully!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed: $errstr</p>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>