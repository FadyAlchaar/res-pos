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

// Set printer to ISO-8859-6 (code page 6)
$SET_CODEPAGE = $ESC . "t" . chr(6);

echo "<h1>Testing ISO-8859-6 (Arabic) on Rongta Printer</h1>";

// Arabic text
$arabic_header = "طلب المطبخ";
$arabic_order = "رقم الطلب: ORD-TEST-001";
$arabic_table = "طاولة: 5";
$arabic_items = "2x شيشة تفاح\n1x شيشة عنب";
$arabic_total = "إجمالي الأصناف: 3";
$arabic_footer = "نسخة المطبخ";

// Convert to ISO-8859-6
$converted_header = mb_convert_encoding($arabic_header, 'ISO-8859-6', 'UTF-8');
$converted_order = mb_convert_encoding($arabic_order, 'ISO-8859-6', 'UTF-8');
$converted_table = mb_convert_encoding($arabic_table, 'ISO-8859-6', 'UTF-8');
$converted_items = mb_convert_encoding($arabic_items, 'ISO-8859-6', 'UTF-8');
$converted_total = mb_convert_encoding($arabic_total, 'ISO-8859-6', 'UTF-8');
$converted_footer = mb_convert_encoding($arabic_footer, 'ISO-8859-6', 'UTF-8');

$content = "";
$content .= $SET_CODEPAGE;
$content .= $ALIGN_CENTER;
$content .= $BOLD_ON;
$content .= "========================================\n";
$content .= "        " . $converted_header . "\n";
$content .= "========================================\n";
$content .= $BOLD_OFF;
$content .= $ALIGN_LEFT;
$content .= $converted_order . "\n";
$content .= $converted_table . "\n";
$content .= "الوقت: " . date('d/m/Y H:i:s') . "\n";
$content .= "----------------------------------------\n";
$content .= $converted_items . "\n";
$content .= "----------------------------------------\n";
$content .= $converted_total . "\n";
$content .= "========================================\n";
$content .= "        " . $converted_footer . "\n";
$content .= "========================================\n\n";
$content .= $CUT;

$fp = fsockopen($ip, $port, $errno, $errstr, 5);
if ($fp) {
    fwrite($fp, $content);
    fclose($fp);
    echo "<p style='color:green;'>✅ Arabic test sent with ISO-8859-6!</p>";
    echo "<p>Check your printer. Arabic should now print correctly.</p>";
} else {
    echo "<p style='color:red;'>❌ Connection failed: $errstr</p>";
}

// Also show hex dump for debugging
echo "<h2>First 100 bytes of converted content (hex):</h2>";
echo "<pre>" . bin2hex(substr($converted_header, 0, 50)) . "</pre>";
?>