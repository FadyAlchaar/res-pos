<?php
// test_rundll32_print.php
$printerName = "Brother WiFi";

// First, create an image from your receipt text (using your existing PrintHelper logic)
require_once 'includes/print_helper.php';
$helper = new PrintHelper('', 0);
$content = "=" . str_repeat("=", 40) . "\r\n";
$content .= "        TEST RECEIPT (rundll32)\r\n";
$content .= str_repeat("=", 42) . "\r\n";
$content .= "Order #: TEST-002\r\n";
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

// Create PNG from text
$pngPath = $helper->createPngFromText($content);
if (!$pngPath) {
    die("Failed to create PNG");
}

// Use rundll32 to print the image
$cmd = 'rundll32 C:\Windows\System32\shimgvw.dll,ImageView_PrintTo "' . $pngPath . '" "' . $printerName . '"';
exec($cmd, $output, $returnCode);

unlink($pngPath);

if ($returnCode === 0) {
    echo "SUCCESS - Image sent to printer: $printerName";
} else {
    echo "FAILURE - Return code: $returnCode";
}
?>