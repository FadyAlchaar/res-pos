<?php
$ip = '192.168.1.87';
$port = 9100;

echo "<h1>Testing Rongta Printer Connection</h1>";

$fp = @fsockopen($ip, $port, $errno, $errstr, 5);

if ($fp) {
    echo "<p style='color:green;'>✅ Connection successful to $ip:$port</p>";
    fclose($fp);
} else {
    echo "<p style='color:red;'>❌ Connection failed: $errstr</p>";
}

// Also try ping
echo "<h2>Ping Test:</h2>";
echo "<pre>" . shell_exec("ping -n 2 $ip") . "</pre>";
?>