<?php
$ips = [
    'Accountant' => '192.168.105.11',
    'Kitchen'    => '192.168.103.22'
];
$port = 9100;
$test_content = "Test print\r\nLine 2\r\nLine 3\r\n\x0C";

foreach ($ips as $name => $ip) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
    if ($fp) {
        fwrite($fp, $test_content);
        fclose($fp);
        echo "$name: OK\n";
    } else {
        echo "$name: FAIL – $errstr\n";
    }
}
?>