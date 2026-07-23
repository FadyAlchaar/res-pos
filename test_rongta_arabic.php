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

// Arabic text
$arabic_text = "مرحبا";
$order_number = "ORD-TEST-001";
$table_number = "5";

// Test different code pages
$code_pages = [
    'CP864' => $ESC . "t" . chr(8),   // Arabic (864)
    'CP720' => $ESC . "t" . chr(7),   // Arabic (720)
    'CP1256' => $ESC . "t" . chr(28), // Windows Arabic
    'ISO-8859-6' => $ESC . "t" . chr(6), // ISO Arabic
];

echo "<h1>Testing Arabic on Rongta Printer</h1>";

foreach ($code_pages as $name => $code_page) {
    echo "<h2>Testing: $name</h2>";
    
    // Convert Arabic to target encoding
    $converted_arabic = mb_convert_encoding($arabic_text, $name, 'UTF-8');
    $converted_order = mb_convert_encoding("رقم الطلب: " . $order_number, $name, 'UTF-8');
    $converted_table = mb_convert_encoding("طاولة: " . $table_number, $name, 'UTF-8');
    
    $content = "";
    $content .= $code_page;  // Set code page
    $content .= $ALIGN_CENTER;
    $content .= $BOLD_ON;
    $content .= "========================================\n";
    $content .= "        " . mb_convert_encoding("طلب المطبخ", $name, 'UTF-8') . "\n";
    $content .= "========================================\n";
    $content .= $BOLD_OFF;
    $content .= $ALIGN_LEFT;
    $content .= $converted_order . "\n";
    $content .= $converted_table . "\n";
    $content .= "الوقت: " . date('d/m/Y H:i:s') . "\n";
    $content .= "----------------------------------------\n";
    $content .= "2x " . mb_convert_encoding("شيشة تفاح", $name, 'UTF-8') . "\n";
    $content .= "1x " . mb_convert_encoding("شيشة عنب", $name, 'UTF-8') . "\n";
    $content .= "----------------------------------------\n";
    $content .= mb_convert_encoding("إجمالي الأصناف: 3", $name, 'UTF-8') . "\n";
    $content .= "========================================\n";
    $content .= "        " . mb_convert_encoding("نسخة المطبخ", $name, 'UTF-8') . "\n";
    $content .= "========================================\n\n";
    $content .= $CUT;
    
    $fp = fsockopen($ip, $port, $errno, $errstr, 5);
    if ($fp) {
        fwrite($fp, $content);
        fclose($fp);
        echo "<p style='color:green;'>✅ $name test sent!</p>";
        sleep(2);
    } else {
        echo "<p style='color:red;'>❌ Connection failed: $errstr</p>";
    }
}

echo "<p>Check your printer. Which code page printed Arabic correctly?</p>";
?>