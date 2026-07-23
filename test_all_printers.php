<?php
echo "<h1>Testing All Virtual Printers</h1>";

$printers = [
    ['name' => 'Main Kitchen', 'ip' => '127.0.0.1', 'port' => 9100],
    ['name' => 'Grill Station', 'ip' => '127.0.0.1', 'port' => 9101],
    ['name' => 'Pizza Station', 'ip' => '127.0.0.1', 'port' => 9102],
    ['name' => 'Cold Kitchen', 'ip' => '127.0.0.1', 'port' => 9103],
    ['name' => 'Beverage Station', 'ip' => '127.0.0.1', 'port' => 9104],
];

foreach ($printers as $printer) {
    $test_content = "\n";
    $test_content .= "========================================\n";
    $test_content .= "        TEST PRINT\n";
    $test_content .= "========================================\n";
    $test_content .= "Printer: " . $printer['name'] . "\n";
    $test_content .= "Port: " . $printer['port'] . "\n";
    $test_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $test_content .= "========================================\n\n";
    
    $fp = @fsockopen($printer['ip'], $printer['port'], $errno, $errstr, 2);
    
    if ($fp) {
        fwrite($fp, $test_content);
        fclose($fp);
        echo "<p style='color: green;'>✅ {$printer['name']} - Test print sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ {$printer['name']} - Failed: $errstr</p>";
    }
}
?>