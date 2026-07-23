<?php
$printerName = "Brother WiFi";
$tmpFile = sys_get_temp_dir() . '/test.txt';
file_put_contents($tmpFile, "Hello World\nTest line 2\nThis is a plain text test.");
$cmd = 'print /D:"' . $printerName . '" "' . $tmpFile . '"';
exec($cmd, $output, $returnCode);
unlink($tmpFile);
echo $returnCode === 0 ? "Text job sent" : "Failed";
?>