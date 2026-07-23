<?php
$ip = '192.168.1.87';
$port = 9100;

echo "<h1>Testing Printer: $ip:$port</h1>";

// Test connection
$fp = @fsockopen($ip, $port, $errno, $errstr, 5);

if ($fp) {
    echo "<p style='color:green;'>✅ Connection successful!</p>";
    
    // Test print content
    $test_content = "\n";
    $test_content .= str_repeat("=", 40) . "\n";
    $test_content .= "      PRINTER TEST\n";
    $test_content .= str_repeat("=", 40) . "\n";
    $test_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $test_content .= "IP: $ip\n";
    $test_content .= "If you can read this, the printer is working!\n";
    $test_content .= str_repeat("=", 40) . "\n\n";
    
    fwrite($fp, $test_content);
    fclose($fp);
    
    echo "<p style='color:green;'>✅ Test print sent!</p>";
    echo "<p>Check your printer - it should print a test ticket.</p>";
    
} else {
    echo "<p style='color:red;'>❌ Connection failed: $errstr</p>";
    echo "<p>Check: Printer powered on, Ethernet cable connected, IP address correct</p>";
}

// Also test from database
echo "<h2>Database Kitchen Settings:</h2>";
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, printer_ip, printer_port, status FROM kitchens WHERE is_active = 1";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8'>";
    echo "技术<th>ID</th><th>Name</th><th>Printer IP</th><th>Port</th><th>Status</th> </tr>";
    foreach ($kitchens as $k) {
        echo "处习";
        echo "处习{$k['id']}处习";
        echo "处习{$k['name']}处习";
        echo "处习{$k['printer_ip']}处习";
        echo "处习{$k['printer_port']}处习";
        echo "处习{$k['status']}处习";
        echo " </tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>