<?php
// test_mspaint_v2_print.php
$printerName = "Brother WiFi";

// Create a simple test image
$img = imagecreatetruecolor(550, 400);
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
imagefilledrectangle($img, 0, 0, 550, 400, $white);

// Add Arabic text
$fontFile = 'C:/Windows/Fonts/arial.ttf';
$text = "Test Receipt\n\n";
$text .= "1x Baba Ghanoush  $4.50\n";
$text .= "1x بابا غنوج      $4.50\n";
$text .= "1x Tabbouleh      $5.00\n";
$text .= "1x تبولة          $5.00\n\n";
$text .= "Total: $9.50";

$y = 20;
foreach (explode("\n", $text) as $line) {
    imagettftext($img, 12, 0, 20, $y, $black, $fontFile, $line);
    $y += 20;
}

$pngPath = sys_get_temp_dir() . '/test_print.png';
imagepng($img, $pngPath);
imagedestroy($img);

// Use mspaint to print
$cmd = 'mspaint /pt "' . $pngPath . '" "' . $printerName . '"';
exec($cmd, $output, $returnCode);
unlink($pngPath);

echo $returnCode === 0 ? "Paint print job sent" : "Failed";
?>