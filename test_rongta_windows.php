<?php
// Use the Windows printer name (adjust as needed)
$printer_name = 'RONGTA 80mm Series Printer'; // Change to actual name from above

$test_content = "============================\r\n";
$test_content .= "TEST PRINT VIA WINDOWS DRIVER\r\n";
$test_content .= "Time: " . date('Y-m-d H:i:s') . "\r\n";
$test_content .= "============================\r\n";
$test_content .= "\r\n";
$test_content .= "English: Hello World\r\n";
$test_content .= "Arabic: مرحبا\r\n";
$test_content .= "============================\r\n";
$test_content .= "\x0C";

$temp_file = sys_get_temp_dir() . '/test_windows.txt';
file_put_contents($temp_file, $test_content);

// Use PowerShell (same as Brother WiFi)
$command = 'powershell -Command "Get-Content \'' . str_replace('\\', '\\\\', $temp_file) . '\' | Out-Printer -Name \'' . $printer_name . '\'"';
exec($command, $output, $return_code);

unlink($temp_file);

echo "<h1>Test Windows Printer</h1>";
echo "<p>Printer: $printer_name</p>";
echo "<p>Return code: $return_code</p>";

if ($return_code === 0) {
    echo "<p style='color:green;'>✅ Print sent via Windows driver!</p>";
    echo "<p>Check your Rongta printer. Arabic should print correctly because Windows handles it.</p>";
} else {
    echo "<p style='color:red;'>❌ Failed. Output:</p>";
    echo "<pre>" . print_r($output, true) . "</pre>";
}
?>