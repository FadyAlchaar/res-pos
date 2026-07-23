<?php
require_once 'includes/print_helper.php';
$helper = new PrintHelper('192.168.105.11', 9100);
$success = $helper->printTextAsImage("Test line 1\nTest line 2\nEnd.");
echo $success ? "SUCCESS" : "FAILURE";
?>