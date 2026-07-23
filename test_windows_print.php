<?php
require_once 'includes/print_functions.php';

// Use the EXACT printer name from list_printers.php
$printerName = "Brother MFC-L2805DW Printer"; // Change to your printer's name

$content = "=" . str_repeat("=", 40) . "=\r\n";
$content .= "        TEST RECEIPT\r\n";
$content .= str_repeat("=", 42) . "\r\n";
$content .= "Order #: TEST-001\r\n";
$content .= "Table  : 2\r\n";
$content .= "Time   : " . date('H:i:s') . "\r\n";
$content .= str_repeat("-", 42) . "\r\n";
$content .= "1x Baba Ghanoush  ................ $4.50\r\n";
$content .= "1x Tabbouleh      ................ $5.00\r\n";
$content .= str_repeat("-", 42) . "\r\n";
$content .= "TOTAL: $9.50\r\n";
$content .= str_repeat("=", 42) . "\r\n";
$content .= "Thank you!\r\n";
$content .= "\x0C"; // Form feed

$result = sendWindowsPrint($printerName, $content);
echo $result ? "SUCCESS - Check printer" : "FAILURE - Check error log";
?>