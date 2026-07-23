<?php
$printer_name = 'RONGTA 80mm Series Printer';

$test_content = "========================================\r\n";
$test_content .= "        TEST PRINT\r\n";
$test_content .= "========================================\r\n";
$test_content .= "Time: " . date('Y-m-d H:i:s') . "\r\n";
$test_content .= "----------------------------------------\r\n";
$test_content .= "English: Hello World\r\n";
$test_content .= "Arabic: مرحبا\r\n";
$test_content .= "----------------------------------------\r\n";
$test_content .= "========================================\r\n";
$test_content .= "\x0C";

$temp_file = sys_get_temp_dir() . '/test_notepad.txt';
file_put_contents($temp_file, $test_content);

$command = 'notepad /p "' . $temp_file . '"';
exec($command, $output, $return_code);

unlink($temp_file);

echo "<h1>Notepad Print Test</h1>";
echo "<p>Return code: $return_code</p>";
if ($return_code === 0) {
    echo "<p style='color:green;'>✅ Print sent via Notepad! Check your Rongta printer.</p>";
    echo "<p>Arabic should print correctly because Notepad uses Windows graphics rendering.</p>";
} else {
    echo "<p style='color:red;'>❌ Failed</p>";
}
?>