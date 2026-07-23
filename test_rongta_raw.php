<?php
$ip = '192.168.1.87';
$port = 9100;

$ESC = chr(27);
$GS = chr(29);
$CUT = $GS . "V" . chr(66) . chr(0);
$BOLD_ON = $ESC . "E" . chr(1);
$BOLD_OFF = $ESC . "E" . chr(0);
$ALIGN_CENTER = $ESC . "a" . chr(1);
$ALIGN_LEFT = $ESC . "a" . chr(0);

// Try different approaches
echo "<h1>Testing Different Approaches</h1>";

// Approach 1: No conversion, just send UTF-8
$content1 = "";
$content1 .= $ALIGN_CENTER;
$content1 .= $BOLD_ON;
$content1 .= "========================================\n";
$content1 .= "        TEST PRINT\n";
$content1 .= "========================================\n";
$content1 .= $BOLD_OFF;
$content1 .= $ALIGN_LEFT;
$content1 .= "Arabic: مرحبا\n";
$content1 .= "English: Hello\n";
$content1 .= "----------------------------------------\n";
$content1 .= $CUT;

// Approach 2: Try with Arabic code page command only
$content2 = "";
$content2 .= $ESC . "t" . chr(28); // Try different numbers: 7,8,28,6
$content2 .= $ALIGN_CENTER;
$content2 .= $BOLD_ON;
$content2 .= "========================================\n";
$content2 .= "        TEST PRINT\n";
$content2 .= "========================================\n";
$content2 .= $BOLD_OFF;
$content2 .= $ALIGN_LEFT;
$content2 .= "Arabic: مرحبا\n";
$content2 .= "English: Hello\n";
$content2 .= "----------------------------------------\n";
$content2 .= $CUT;

$fp = fsockopen($ip, $port, $errno, $errstr, 5);
if ($fp) {
    fwrite($fp, $content1);
    sleep(1);
    fwrite($fp, $content2);
    fclose($fp);
    echo "<p style='color:green;'>✅ Test prints sent!</p>";
    echo "<p>Check your printer. Did either print Arabic correctly?</p>";
} else {
    echo "<p style='color:red;'>❌ Connection failed: $errstr</p>";
}
?>