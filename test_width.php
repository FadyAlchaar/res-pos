<?php
require_once 'includes/print_helper.php';
$helper = new PrintHelper('', 0);
$content = "Order #: 123\nTable: 2\nبابا غنوج\nتبولة\nTotal: \$9.50";
$png = $helper->createPngFromText($content, 20, 576, true, true);
if ($png) {
    echo "PNG created: $png\n";
    // Optionally, display or print it
} else {
    echo "Failed";
}
?>