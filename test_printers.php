<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'];
    $port = (int)$_POST['port'];
    $text = "Test print from POS\n" . date('Y-m-d H:i:s') . "\n\n";
    
    $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
    if ($fp) {
        fwrite($fp, $text);
        fclose($fp);
        $result = "✅ Success – data sent to $ip:$port";
    } else {
        $result = "❌ Failed: $errstr ($errno)";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Test Printer</title></head>
<body>
    <h2>Test Network Printer</h2>
    <form method="post">
        <label>IP:</label> <input type="text" name="ip" required><br>
        <label>Port:</label> <input type="number" name="port" value="9100"><br>
        <input type="submit" value="Print Test">
    </form>
    <pre><?php echo $result ?? ''; ?></pre>
</body>
</html>