<?php
require_once 'includes/print_functions.php';
$result = sendWindowsPrint('Brother DCP-L2540DW Procurement', "Test print\nPizza printer test\n");
echo $result ? "Success" : "Failure";
?>