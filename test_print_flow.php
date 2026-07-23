<?php
require_once 'config/config.php';

echo "<h1>🖨️ Printer System Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check kitchens with printer IPs
    echo "<h2>1. Kitchen Printer Configuration</h2>";
    $query = "SELECT id, name, printer_ip, printer_port, status FROM kitchens WHERE printer_ip IS NOT NULL";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    if (empty($kitchens)) {
        echo "<p style='color: orange;'>⚠️ No printers configured. Please set printer IPs in admin panel.</p>";
    } else {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>Kitchen</th><th>Printer IP</th><th>Port</th><th>Status</th><th>Simulator Link</th></tr>";
        foreach ($kitchens as $k) {
            $simulator_link = "http://localhost/res-pos/printer-simulator/#printer-{$k['id']}";
            echo "<tr>";
            echo "<td>{$k['name']}</td>";
            echo "<td>{$k['printer_ip']}</td>";
            echo "<td>{$k['printer_port']}</td>";
            echo "<td style='color: " . ($k['status'] == 'online' ? 'green' : 'red') . ";'>{$k['status']}</td>";
            echo "<td><a href='{$simulator_link}' target='_blank'>View in Simulator</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Check printer simulator accessibility
    echo "<h2>2. Printer Simulator Status</h2>";
    $simulator_url = "http://localhost/res-pos/printer-simulator/";
    $headers = @get_headers($simulator_url);
    if ($headers && strpos($headers[0], '200')) {
        echo "<p style='color: green;'>✅ Printer simulator is accessible</p>";
        echo "<p><a href='{$simulator_url}' target='_blank'>Open Printer Simulator</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Printer simulator not accessible. Make sure the folder exists.</p>";
    }
    
    // Test 3: Check for any pending print jobs
    echo "<h2>3. Pending Print Jobs</h2>";
    $query = "SELECT COUNT(*) as count FROM print_jobs WHERE status = 'pending'";
    $stmt = $db->query($query);
    $pending = $stmt->fetch()['count'];
    
    $query = "SELECT COUNT(*) as count FROM print_jobs WHERE status = 'failed'";
    $stmt = $db->query($query);
    $failed = $stmt->fetch()['count'];
    
    echo "<p>Pending jobs: <strong>{$pending}</strong></p>";
    echo "<p>Failed jobs: <strong>{$failed}</strong></p>";
    
    // Test 4: Quick test print
    echo "<h2>4. Send Test Print</h2>";
    echo "<form method='post'>";
    echo "<select name='kitchen_id'>";
    foreach ($kitchens as $k) {
        echo "<option value='{$k['id']}'>{$k['name']} ({$k['printer_ip']})</option>";
    }
    echo "</select>";
    echo "<button type='submit' name='send_test'>Send Test Print</button>";
    echo "</form>";
    
    if (isset($_POST['send_test']) && isset($_POST['kitchen_id'])) {
        $kitchen_id = $_POST['kitchen_id'];
        
        // Get kitchen info
        $query = "SELECT * FROM kitchens WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $kitchen_id]);
        $kitchen = $stmt->fetch();
        
        // Create test print job
        $test_content = "\n";
        $test_content .= str_repeat("=", 42) . "\n";
        $test_content .= "      MANUAL TEST PRINT\n";
        $test_content .= str_repeat("=", 42) . "\n";
        $test_content .= "Kitchen: " . $kitchen['name'] . "\n";
        $test_content .= "IP: " . $kitchen['printer_ip'] . "\n";
        $test_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $test_content .= str_repeat("-", 42) . "\n";
        $test_content .= "This is a manual test from\n";
        $test_content .= "the printer test script.\n";
        $test_content .= str_repeat("=", 42) . "\n\n";
        
        // Insert into print_jobs
        $query = "INSERT INTO print_jobs (order_item_id, kitchen_id, printer_ip, content, status) 
                  VALUES (0, :kitchen_id, :printer_ip, :content, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':kitchen_id' => $kitchen_id,
            ':printer_ip' => $kitchen['printer_ip'],
            ':content' => $test_content
        ]);
        
        echo "<p style='color: green;'>✅ Test print job created! Check the printer simulator.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>