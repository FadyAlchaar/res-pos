<?php
// Simplified image printing - uses larger font for better readability
$printer_ip = '192.168.1.87';
$printer_port = 9100;

$order_content = "
========================================
        MAIN KITCHEN
        KITCHEN ORDER
========================================
Order #: ORD-TEST-001
Table  : 5
Time   : " . date('d/m/Y H:i:s') . "
----------------------------------------
2x شيشة تفاح (Extra strong)
1x شيشة عنب (Double apple)
----------------------------------------
TOTAL ITEMS: 3
========================================
        KITCHEN COPY
========================================
";

echo "<h1>Simple Arabic Image Print Test</h1>";
echo "<pre>" . htmlspecialchars($order_content) . "</pre>";

// Create image
$image = imagecreate(500, 400);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Try to find a font
$font_paths = [
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/tahoma.ttf',
    'C:/Windows/Fonts/consola.ttf',
    'C:/Windows/Fonts/msgothic.ttc'
];

$font_path = null;
foreach ($font_paths as $path) {
    if (file_exists($path)) {
        $font_path = $path;
        break;
    }
}

if (!$font_path) {
    die("No font found. Please specify a valid font path.");
}

// Draw text
$lines = explode("\n", $order_content);
$y = 20;
foreach ($lines as $line) {
    imagettftext($image, 9, 0, 10, $y, $black, $font_path, $line);
    $y += 15;
}

// Save image for debugging
imagepng($image, 'print_image.png');
echo "<p>Image saved as: <a href='print_image.png'>print_image.png</a></p>";

// Convert to simple bitmap
$width = imagesx($image);
$height = imagesy($image);

$ESC = chr(27);
$GS = chr(29);

// Simple print command
$content = "";
$content .= $ESC . "@"; // Initialize printer
$content .= $ESC . "2"; // Set line spacing

// Print line by line (simpler)
for ($y = 0; $y < $height; $y++) {
    $line_data = '';
    for ($x = 0; $x < $width; $x += 8) {
        $byte = 0;
        for ($b = 0; $b < 8; $b++) {
            if ($x + $b < $width) {
                $pixel = imagecolorat($image, $x + $b, $y);
                $r = ($pixel >> 16) & 0xFF;
                $g = ($pixel >> 8) & 0xFF;
                $b = $pixel & 0xFF;
                if ($r + $g + $b < 384) {
                    $byte = ($byte << 1) | 1;
                } else {
                    $byte = ($byte << 1) | 0;
                }
            } else {
                $byte = ($byte << 1) | 0;
            }
        }
        $line_data .= chr($byte);
    }
    $content .= $line_data . "\n";
}

$content .= $GS . "V" . chr(66) . chr(0); // Cut paper

$fp = fsockopen($printer_ip, $printer_port, $errno, $errstr, 10);
if ($fp) {
    fwrite($fp, $content);
    fclose($fp);
    echo "<p style='color:green;'>✅ Image print sent!</p>";
} else {
    echo "<p style='color:red;'>❌ Failed: $errstr</p>";
}

imagedestroy($image);
?>