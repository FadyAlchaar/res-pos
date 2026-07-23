<?php
// Simple Windows Print Test
$printer_name = 'Brother WiFi'; // Change this to your printer name

// Simple text with Arabic
$text = "========================================\r\n";
$text .= "        TEST PRINT\r\n";
$text .= "========================================\r\n";
$text .= "Time: " . date('Y-m-d H:i:s') . "\r\n";
$text .= "----------------------------------------\r\n";
$text .= "English: Hello World\r\n";
$text .= "Arabic: مرحبا\r\n";
$text .= "----------------------------------------\r\n";
$text .= "========================================\r\n";
$text .= "\x0C"; // Form feed (eject paper)

// Method 1: Save to file and use Windows print
$temp_file = sys_get_temp_dir() . '/test_print.txt';
file_put_contents($temp_file, $text);

// Try different print methods
echo "<h1>Windows Print Test</h1>";
echo "<p>Temp file: $temp_file</p>";
echo "<pre>" . htmlspecialchars($text) . "</pre>";

// Method 1: PowerShell (most reliable)
echo "<h2>Method 1: PowerShell</h2>";
$command = 'powershell -Command "Get-Content \'' . str_replace('\\', '\\\\', $temp_file) . '\' | Out-Printer -Name \'' . $printer_name . '\'"';
echo "<p>Command: $command</p>";
exec($command, $output, $return_code);
echo "<p>Return code: $return_code</p>";
if ($return_code === 0) {
    echo "<p style='color:green;'>✅ PowerShell print sent!</p>";
}

// Method 2: Use Notepad to print
echo "<h2>Method 2: Notepad</h2>";
$command2 = 'notepad /p "' . $temp_file . '"';
echo "<p>Command: $command2</p>";
exec($command2, $output2, $return_code2);
echo "<p>Return code: $return_code2</p>";
if ($return_code2 === 0) {
    echo "<p style='color:green;'>✅ Notepad print sent!</p>";
}

// Method 3: Use Windows print command
echo "<h2>Method 3: Windows Print Command</h2>";
$command3 = 'print /D:"' . $printer_name . '" "' . $temp_file . '"';
echo "<p>Command: $command3</p>";
exec($command3, $output3, $return_code3);
echo "<p>Return code: $return_code3</p>";
if ($return_code3 === 0) {
    echo "<p style='color:green;'>✅ Print command sent!</p>";
}

// Show all available printers
echo "<h2>Available Printers</h2>";
echo "<pre>" . shell_exec('wmic printer get name') . "</pre>";

echo "<p>If none worked, try printing manually:</p>";
echo "<ol>";
echo "<li>Open <strong>$temp_file</strong> in Notepad</li>";
echo "<li>File → Print</li>";
echo "<li>Select your printer</li>";
echo "<li>Click Print</li>";
echo "</ol>";
?>