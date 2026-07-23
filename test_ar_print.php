<?php
require_once 'includes/print_functions.php';
$printerName = "Your Printer Name"; // from list_printers.php
$content = "Order #: TEST\nTable: 2\n1x بابا غنوج\n1x تبولة\nTotal: \$9.50\n";
$result = sendWindowsPrint($printerName, $content);
echo $result ? "SUCCESS" : "FAILURE";
?>