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

function formatKitchenPrint($kitchenName, $orderNumber, $tableNumber, $items, $reprint = false) {
    $nl = "\r\n";
    $content = $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "        " . strtoupper($kitchenName) . $nl;
    $content .= "        KITCHEN ORDER" . ($reprint ? " (REPRINT)" : "") . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "Order #: " . $orderNumber . $nl;
    $content .= "Table  : " . $tableNumber . $nl;
    $content .= "Time   : " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", 42) . $nl;
    foreach ($items as $item) {
        $content .= $item['quantity'] . "x " . $item['name'] . $nl;
        if (!empty($item['notes'])) {
            $note = str_replace("📝", "Note:", $item['notes']);
            $content .= "   " . $note . $nl;
        }
    }
    $totalItems = array_sum(array_column($items, 'quantity'));
    $content .= str_repeat("-", 42) . $nl;
    $content .= "TOTAL ITEMS: " . $totalItems . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "\x0C";
    return $content;
}

function formatAccountantPrint($orderNumber, $tableNumber, $items, $totalAmount, $waiterName = '', $sessionNumber = '') {
    $nl = "\r\n";
    $content = $nl;
    $content .= str_repeat("=", 42) . $nl;
    if (!empty($sessionNumber)) $content .= "     SESSION: " . $sessionNumber . $nl;
    $content .= "         ORDER RECEIPT" . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "Order #: " . $orderNumber . $nl;
    $content .= "Table  : " . $tableNumber . $nl;
    if (!empty($sessionNumber)) $content .= "Session: " . $sessionNumber . $nl;
    if (!empty($waiterName)) $content .= "Waiter : " . $waiterName . $nl;
    $content .= "Time   : " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", 56) . $nl;
    foreach ($items as $item) {
        $line = $item['quantity'] . "x " . $item['name'];
        if (strlen($line) < 32) $line = str_pad($line, 32, ' ');
        $content .= $line . " $" . number_format($item['subtotal'], 2) . $nl;
        if (!empty($item['notes'])) {
            $note = str_replace("📝", "Note:", $item['notes']);
            $content .= "   " . $note . $nl;
        }
    }
    $content .= str_repeat("-", 42) . $nl;
    $content .= "SUBTOTAL     : $" . number_format($totalAmount, 2) . $nl;
    $content .= "TAX (if any) : $0.00" . $nl;
    $content .= str_repeat("-", 56) . $nl;
    $content .= "TOTAL        : $" . number_format($totalAmount, 2) . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "      Thank you, please come again!" . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "\x0C";
    return $content;
}
?>