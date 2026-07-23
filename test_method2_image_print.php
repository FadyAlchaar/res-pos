<?php
// test_method2_image_print.php
// Uses GD to create image, then prints via Windows print command

$printerName = "Brother WiFi"; // CHANGE to your exact printer name

// Content to print
$content = "=" . str_repeat("=", 40) . "=\r\n";
$content .= "        TEST RECEIPT (Method 2 - Image)\r\n";
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

// Function to convert text to PNG (standalone, uses GD)
function textToPng($text) {
    if (!extension_loaded('gd')) return false;
    
    $lines = explode("\n", str_replace("\r", "", $text));
    if (empty($lines)) return false;
    
    // Find a font
    $fontFile = 'C:/Windows/Fonts/arial.ttf';
    if (!file_exists($fontFile)) $fontFile = 'C:/Windows/Fonts/tahoma.ttf';
    if (!file_exists($fontFile)) return false;
    
    $fontSize = 12;
    $lineHeight = $fontSize + 4;
    $maxWidth = 550;
    $totalHeight = count($lines) * $lineHeight + 20;
    $img = imagecreatetruecolor($maxWidth, $totalHeight);
    if (!$img) return false;
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefilledrectangle($img, 0, 0, $maxWidth, $totalHeight, $white);
    
    $y = 10;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $y += $lineHeight;
            continue;
        }
        imagettftext($img, $fontSize, 0, 10, $y, $black, $fontFile, $line);
        $y += $lineHeight;
    }
    
    $tempFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
    if (!imagepng($img, $tempFile)) {
        imagedestroy($img);
        return false;
    }
    imagedestroy($img);
    return $tempFile;
}

$pngPath = textToPng($content);
if (!$pngPath) {
    die("Failed to create PNG. Check GD and font.");
}

// Print the PNG using Windows print command
$cmd = 'print /D:"' . $printerName . '" "' . $pngPath . '"';
exec($cmd, $output, $returnCode);
unlink($pngPath);

if ($returnCode === 0) {
    echo "SUCCESS - Image printed to: $printerName";
} else {
    echo "FAILURE - Return code: $returnCode";
}
?>