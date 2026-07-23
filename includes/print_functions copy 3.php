<?php
// includes/print_functions.php
require_once __DIR__ . '/print_helper.php';

function getAccountantSettings($db) {
    $settings = [
        'enabled' => false,
        'type' => 'network',
        'ip' => '',
        'port' => 9100,
        'name' => ''
    ];
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM restaurant_settings WHERE setting_key LIKE 'accountant_%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($rows['accountant_print_enabled'])) $settings['enabled'] = (bool)$rows['accountant_print_enabled'];
    if (isset($rows['accountant_printer_type'])) $settings['type'] = $rows['accountant_printer_type'];
    if (isset($rows['accountant_printer_ip'])) $settings['ip'] = $rows['accountant_printer_ip'];
    if (isset($rows['accountant_printer_port'])) $settings['port'] = (int)$rows['accountant_printer_port'];
    if (isset($rows['accountant_printer_name'])) $settings['name'] = $rows['accountant_printer_name'];
    return $settings;
}

function sendPrintJob($printer, $content, $max_retries = 2) {
    if ($printer['type'] === 'network') {
        return sendNetworkPrint($printer['ip'], $printer['port'], $content, $max_retries);
    } elseif ($printer['type'] === 'windows') {
        return sendWindowsPrint($printer['name'], $content);
    }
    return false;
}

function sendNetworkPrint($ip, $port, $content, $max_retries = 2) {
    $hasArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $content);
    if ($hasArabic) {
        return sendNetworkPrintImageWithTimeout($ip, $port, $content);
    } else {
        return sendNetworkPrintPlain($ip, $port, $content, $max_retries);
    }
}

function sendNetworkPrintPlain($ip, $port, $content, $max_retries = 2) {
    $log_file = __DIR__ . '/../last_print.txt';
    file_put_contents($log_file, $content);
    $attempt = 0;
    $last_error = '';
    while ($attempt < $max_retries) {
        $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
        if ($fp) {
            fwrite($fp, $content);
            fclose($fp);
            return true;
        }
        $last_error = "$errstr ($errno)";
        $attempt++;
        if ($attempt < $max_retries) usleep(500000);
    }
    error_log("Network print failed to $ip:$port after $max_retries attempts: $last_error");
    return false;
}

// ADD THIS MISSING FUNCTION
function sendNetworkPrintImageWithTimeout($ip, $port, $content) {
    try {
        $helper = new PrintHelper($ip, $port);
        return $helper->printTextAsImage($content);
    } catch (Exception $e) {
        error_log("Image print error (network): " . $e->getMessage());
        return sendNetworkPrintPlain($ip, $port, $content, 1);
    }
}

function sendWindowsPrint($printerName, $content) {
    // Log the attempt
    $logFile = __DIR__ . '/../print_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Attempt to print to: '$printerName'\n", FILE_APPEND);
    
    // Create temporary file
    $tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
    
    // Always add UTF-8 BOM to ensure proper encoding (safe for English too)
    $bom = "\xEF\xBB\xBF";
    file_put_contents($tmpFile, $bom . $content);
    
    // Use PowerShell Out-Printer for all prints (works for both English and Arabic)
    $psCommand = "Get-Content -Path '$tmpFile' | Out-Printer -Name '$printerName'";
    $cmd = 'powershell -Command "' . $psCommand . '"';
    file_put_contents($logFile, "CMD: $cmd\n", FILE_APPEND);
    
    exec($cmd, $output, $returnCode);
    file_put_contents($logFile, "Return code: $returnCode\n", FILE_APPEND);
    
    unlink($tmpFile);
    
    if ($returnCode !== 0) {
        file_put_contents($logFile, "FAILED - Return code: $returnCode\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "SUCCESS\n", FILE_APPEND);
    }
    
    return $returnCode === 0;
}

function sendWindowsPrintWithImage($printerName, $content) {
    // Re-use PNG creation logic
    $helper = new PrintHelper('', 0);
    // We need to make createPngFromText public or duplicate it.
    // For now, duplicate the essential part:
    $pngPath = createPngFromTextHelper($content);
    if (!$pngPath) return false;
    $cmd = 'print /D:"' . $printerName . '" "' . $pngPath . '"';
    exec($cmd, $output, $returnCode);
    unlink($pngPath);
    return $returnCode === 0;
}

function createPngFromTextHelper($text) {
    // Same code as PrintHelper::createPngFromText but static/standalone
    // I'll provide it if needed.
}

function formatKitchenPrint($kitchenName, $orderNumber, $tableNumber, $items, $reprint = false, $width = 48) {
    global $lang;  // language from session
    $nl = "\r\n";
    
    // RTL override for Arabic (U+202E)
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    $content = $rtl;  // start with RTL if needed

    $content .= str_repeat("=", $width) . $nl;
    $content .= str_pad(strtoupper($kitchenName), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('kitchen_order') . ($reprint ? " (" . t('reprint') . ")" : ""), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= t('order_number') . ": " . $orderNumber . $nl;
    $content .= t('table') . ": " . $tableNumber . $nl;
    $content .= t('time') . ": " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", $width) . $nl;
    
    foreach ($items as $item) {
        $content .= $item['quantity'] . "x " . $item['name'] . $nl;
        if (!empty($item['notes'])) {
            $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
        }
    }
    
    $totalItems = array_sum(array_column($items, 'quantity'));
    $content .= str_repeat("-", $width) . $nl;
    $content .= t('total_items') . ": " . $totalItems . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";  // cut
    return $content;
}

function formatAccountantPrint($orderNumber, $tableNumber, $items, $totalAmount, $waiterName = '', $sessionNumber = '', $width = 48) {
    global $lang;
    $nl = "\r\n";
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    $content = $rtl;

    $content .= str_repeat("=", $width) . $nl;
    if (!empty($sessionNumber)) {
        $content .= str_pad(t('session') . ": " . $sessionNumber, $width, ' ', STR_PAD_BOTH) . $nl;
    }
    $content .= str_pad(t('order_receipt'), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= t('order_number') . ": " . $orderNumber . $nl;
    $content .= t('table') . ": " . $tableNumber . $nl;
    if (!empty($sessionNumber)) $content .= t('session') . ": " . $sessionNumber . $nl;
    if (!empty($waiterName)) $content .= t('waiter') . ": " . $waiterName . $nl;
    $content .= t('time') . ": " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", $width) . $nl;
    
    foreach ($items as $item) {
        $line = $item['quantity'] . "x " . $item['name'];
        $itemPart = str_pad($line, $width - 8, ' ');
        $pricePart = "$" . number_format($item['subtotal'], 2);
        $content .= $itemPart . $pricePart . $nl;
        if (!empty($item['notes'])) {
            $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
        }
    }
    
    $content .= str_repeat("-", $width) . $nl;
    $content .= str_pad(t('subtotal'), $width - 12, ' ') . "$" . number_format($totalAmount, 2) . $nl;
    $content .= str_pad(t('tax'), $width - 12, ' ') . "$0.00" . $nl;
    $content .= str_repeat("-", $width) . $nl;
    $content .= str_pad(t('total'), $width - 12, ' ') . "$" . number_format($totalAmount, 2) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= str_pad(t('thank_you'), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";
    return $content;
}
?>