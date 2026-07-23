<?php
// test_method1_print_command.php
// Uses Windows print command with UTF-8 BOM for Arabic

$printerName = "Brother WiFi"; // CHANGE to your exact printer name

$content = "=" . str_repeat("=", 40) . "=\r\n";
$content .= "        TEST RECEIPT (Method 1)\r\n";
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

// Save to temp file with UTF-8 BOM
$tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
$bom = "\xEF\xBB\xBF"; // UTF-8 BOM
file_put_contents($tmpFile, $bom . $content);

// Print using Windows print command
$cmd = 'print /D:"' . $printerName . '" "' . $tmpFile . '"';
exec($cmd, $output, $returnCode);
unlink($tmpFile);

if ($returnCode === 0) {
    echo "SUCCESS - Job sent to printer: $printerName";
} else {
    echo "FAILURE - Return code: $returnCode<br>Check printer name and that printer is online.";
}
?>