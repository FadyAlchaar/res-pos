<?php
$printerName = 'RONGTA 80mm Series Printer';  // change to one of your printers
$content = "Test print from PHP\r\nLine 2\r\nLine 3\r\n\x0C";

$tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
file_put_contents($tmpFile, $content);
$cmd = 'print /D:"' . $printerName . '" "' . $tmpFile . '"';
exec($cmd, $output, $returnCode);
unlink($tmpFile);

if ($returnCode === 0) {
    echo "Print job sent successfully to $printerName";
} else {
    echo "Failed. Return code: $returnCode";
}
?>