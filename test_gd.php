<?php
echo "GD: " . (extension_loaded('gd') ? "OK" : "MISSING") . "\n";
$font = 'C:/Windows/Fonts/arial.ttf';
echo "Arial: " . (file_exists($font) ? "Found" : "Not found") . "\n";
$im = imagecreatetruecolor(100, 30);
$white = imagecolorallocate($im, 255,255,255);
$black = imagecolorallocate($im, 0,0,0);
imagefilledrectangle($im,0,0,100,30,$white);
imagettftext($im, 10, 0, 5, 20, $black, $font, "Test");
imagepng($im, 'test.png');
echo "PNG created: " . (file_exists('test.png') ? "Yes" : "No");
imagedestroy($im);
?>