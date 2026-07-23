<?php
require_once 'includes/print_helper.php';
$helper = new PrintHelper('192.168.105.11', 9100);
$content = "Test Arabic: مرحبا\nLine 2";
$result = $helper->printTextAsImage($content);
echo $result ? "OK" : "FAIL";
?>