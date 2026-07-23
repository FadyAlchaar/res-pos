<?php
require_once 'config/hardware_id.php';

$hardware = HardwareID::generateHardwareId();

echo "<h1>🔧 Hardware Information</h1>";
echo "<p><strong>Hardware ID:</strong> <code style='word-break:break-all;'>" . $hardware['hardware_id'] . "</code></p>";
echo "<p><strong>CPU Serial:</strong> " . $hardware['components']['cpu'] . "</p>";
echo "<p><strong>Motherboard Serial:</strong> " . $hardware['components']['motherboard'] . "</p>";
echo "<hr>";
echo "<p>Send this Hardware ID to get your license key.</p>";
?>