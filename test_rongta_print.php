<?php
$ip = '192.168.1.87';
$port = 9100;

// ESC/POS Commands
$ESC = chr(27);
$GS = chr(29);
$CUT = $GS . "V" . chr(66) . chr(0);
$BOLD_ON = $ESC . "E" . chr(1);
$BOLD_OFF = $ESC . "E" . chr(0);
$ALIGN_CENTER = $ESC . "a" . chr(1);
$ALIGN_LEFT = $ESC . "a" . chr(0);

// Test content
$content = "";
$content .= $ALIGN_CENTER;
$content .= $BOLD_ON;
$content .= "========================================\n";
$content .= "        TEST PRINT\n";
$content .= "========================================\n";
$content .= $BOLD_OFF;
$content .= $ALIGN_LEFT;
$content .= "Time: " . date('Y-m-d H:i:s') . "\n";
$content .= "Printer: Rongta 332RP\n";
$content .= "IP: $ip\n";
$content .= "----------------------------------------\n";
$content .= "Item 1 x 2\n";
$content .= "Item 2 x 1\n";
$content .= "----------------------------------------\n";
$content .= "TOTAL ITEMS: 3\n";
$content .= "========================================\n";
$content .= "        KITCHEN COPY\n";
$content .= "========================================\n\n";
$content .= $CUT;

// Send to printer
$fp = fsockopen($ip, $port, $errno, $errstr, 5);
if ($fp) {
    fwrite($fp, $content);
    fclose($fp);
    echo "<p style='color:green;'>✅ English test print sent!</p>";
    echo "<p>Check your Rongta printer.</p>";
} else {
    echo "<p style='color:red;'>❌ Failed: $errstr</p>";
}

// Show what was sent
echo "<h2>Content Sent:</h2>";
echo "<pre>" . htmlspecialchars($content) . "</pre>";
?>