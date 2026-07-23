<?php
// includes/print_functions.php

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
    
    if (isset($rows['accountant_print_enabled'])) {
        $settings['enabled'] = (bool)$rows['accountant_print_enabled'];
    }
    if (isset($rows['accountant_printer_type'])) {
        $settings['type'] = $rows['accountant_printer_type'];
    }
    if (isset($rows['accountant_printer_ip'])) {
        $settings['ip'] = $rows['accountant_printer_ip'];
    }
    if (isset($rows['accountant_printer_port'])) {
        $settings['port'] = (int)$rows['accountant_printer_port'];
    }
    if (isset($rows['accountant_printer_name'])) {
        $settings['name'] = $rows['accountant_printer_name'];
    }
    
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
    // Log the content to a file for debugging (especially for Arabic)
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
        if ($attempt < $max_retries) {
            usleep(500000); // wait 0.5 seconds
        }
    }
    error_log("Network print failed to $ip:$port after $max_retries attempts: $last_error");
    return false;
}

function sendWindowsPrint($printerName, $content) {
    if (function_exists('printer_open')) {
        $handle = printer_open($printerName);
        if ($handle) {
            printer_write($handle, $content);
            printer_close($handle);
            return true;
        }
        return false;
    }
    // Fallback: command line print
    $tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
    file_put_contents($tmpFile, $content);
    $cmd = 'print /D:"' . $printerName . '" "' . $tmpFile . '"';
    exec($cmd, $output, $returnCode);
    unlink($tmpFile);
    return $returnCode === 0;
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
    $content .= "\x0C"; // Form feed – forces print and paper cut
    return $content;
}

function formatAccountantPrint($orderNumber, $tableNumber, $items, $totalAmount, $waiterName = '', $sessionNumber = '') {
    $nl = "\r\n";
    $content = $nl;
    $content .= str_repeat("=", 42) . $nl;
    
    if (!empty($sessionNumber)) {
        $content .= "     SESSION: " . $sessionNumber . $nl;
    }
    
    $content .= "         ORDER RECEIPT" . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "Order #: " . $orderNumber . $nl;
    $content .= "Table  : " . $tableNumber . $nl;
    if (!empty($sessionNumber)) {
        $content .= "Session: " . $sessionNumber . $nl;
    }
    if (!empty($waiterName)) {
        $content .= "Waiter : " . $waiterName . $nl;
    }
    $content .= "Time   : " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", 42) . $nl;
    
    foreach ($items as $item) {
        $line = $item['quantity'] . "x " . $item['name'];
        if (strlen($line) < 32) {
            $line = str_pad($line, 32, ' ');
        }
        $content .= $line . " $" . number_format($item['subtotal'], 2) . $nl;
        if (!empty($item['notes'])) {
            $note = str_replace("📝", "Note:", $item['notes']);
            $content .= "   " . $note . $nl;
        }
    }
    
    $content .= str_repeat("-", 42) . $nl;
    $content .= "SUBTOTAL     : $" . number_format($totalAmount, 2) . $nl;
    $content .= "TAX (if any) : $0.00" . $nl;
    $content .= str_repeat("-", 42) . $nl;
    $content .= "TOTAL        : $" . number_format($totalAmount, 2) . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "      Thank you, please come again!" . $nl;
    $content .= str_repeat("=", 42) . $nl;
    $content .= "\x0C"; // Form feed
    return $content;
}
?>