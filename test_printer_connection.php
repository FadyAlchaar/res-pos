<?php
$ip = '192.168.1.87'; // your kitchen printer IP
$port = 9100;
$fp = @fsockopen($ip, $port, $errno, $errstr, 3);
if ($fp) {
    echo "Connected to $ip:$port\n";
    fwrite($fp, "تجربة طباعة\n\x0C");
    fclose($fp);
    echo "Test data sent.\n";
} else {
    echo "Failed: $errstr ($errno)\n";
}
?>