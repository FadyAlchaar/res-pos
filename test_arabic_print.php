<?php
require_once 'includes/print_helper.php';
$helper = new PrintHelper('192.168.105.11', 9100); // your cold kitchen IP
$content = "Order #: TEST-001\r\nTable: 2\r\n\r\n1x Baba Ghanoush\r\n1x Tabbouleh\r\n\r\nTotal: \$9.50\r\n";
$success = $helper->printTextAsImage($content);
echo $success ? "SUCCESS" : "FAILURE";
?>