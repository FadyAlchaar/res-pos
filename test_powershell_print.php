<?php
// test_powershell_print.php
$printerName = "Brother WiFi"; // Your exact printer name

$content = "=" . str_repeat("=", 40) . "\r\n";
$content .= "        TEST RECEIPT (PowerShell)\r\n";
$content .= str_repeat("=", 42) . "\r\n";
$content .= "Order #: TEST-001\r\n";
$content .= "Table  : 2\r\n";
$content .= "Time   : " . date('H:i:s') . "\r\n";
$content .= str_repeat("-", 42) . "\r\n";
$content .= "1x Baba Ghanoush  ................ $4.50\r\n";
$content .= "1x Tabbouleh      ................ $5.00\r\n";
$content .= "1x بابا غنوج      ................ $4.50\r\n";
$content .= "1x تبولة          ................ $5.00\r\n";
$content .= str_repeat("-", 42) . "\r\n";
$content .= "TOTAL: $9.50\r\n";
$content .= str_repeat("=", 42) . "\r\n";
$content .= "Thank you!\r\n";
$content .= "\x0C"; // Form feed

// Save to temp file
$tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
$bom = "\xEF\xBB\xBF"; // UTF-8 BOM for Arabic
file_put_contents($tmpFile, $bom . $content);

// PowerShell command using Out-Printer
$psCommand = "Get-Content -Path '$tmpFile' | Out-Printer -Name '$printerName'";
$cmd = 'powershell -Command "' . $psCommand . '"';
exec($cmd, $output, $returnCode);
unlink($tmpFile);

if ($returnCode === 0) {
    echo "SUCCESS - Job sent to printer: $printerName";
} else {
    echo "FAILURE - Return code: $returnCode<br>";
    echo "Try running PowerShell as Administrator and check printer name.";
}
?>