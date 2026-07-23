<?php

$printer = "\\\\localhost\\RongtaPOS";

// Arabic text
$text = "مرحبا\nHello\n";

// Create temp file
$file = tempnam(sys_get_temp_dir(), "print");
file_put_contents($file, $text);

// Send to printer
copy($file, $printer);

// Cleanup
unlink($file);

echo "✅ Sent to printer via Windows driver";

?>