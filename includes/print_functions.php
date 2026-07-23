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

function sendPrintJob($printer, $content, $max_retries = 1) {
    // Only Windows printers are supported now
    if ($printer['type'] !== 'windows') {
        error_log("Unsupported printer type: {$printer['type']}");
        return false;
    }
    
    $mainPrinter = $printer['name'];
    $fallback = $printer['fallback_name'] ?? null;
    $printerIp = $printer['ip'] ?? null;
    $printerPort = $printer['port'] ?? 9100;
    
    // Check if main printer is reachable (if IP is available)
    $mainReachable = true;
    if (!empty($printerIp)) {
        $mainReachable = isPrinterReachable($printerIp, $printerPort);
    }
    
    if (!$mainReachable && !empty($fallback)) {
        error_log("Main printer '$mainPrinter' (IP: $printerIp) unreachable. Using fallback: $fallback");
        return sendWindowsPrint($fallback, $content);
    }
    
    // Main printer is reachable (or no IP to check) – try to print
    return sendWindowsPrint($mainPrinter, $content);
}

/**
 * Check if a printer is reachable via TCP/IP (port 9100)
 */
function isPrinterReachable($ip, $port = 9100, $timeout = 2) {
    if (empty($ip)) return true; // no IP, assume reachable
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
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
    $tmpFile = sys_get_temp_dir() . '/print_' . uniqid() . '.txt';
    $bom = "\xEF\xBB\xBF";
    file_put_contents($tmpFile, $bom . $content);
    
    //$exePath = 'G:\\xampp\\htdocs\\res-pos\\bin\\TextPrinter.exe';
    $exePath = dirname(__DIR__) . '/bin/TextPrinter.exe'; // dynamic path
    $cmd = '"' . $exePath . '" "' . $printerName . '" "' . $tmpFile . '" 2>&1';
    
    exec($cmd, $output, $returnCode);
    unlink($tmpFile);
    
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

function formatKitchenPrint($db, $kitchenName, $orderNumber, $tableNumber, $items, $reprint = false, $width = 56, $waiterName = '', $orderTime = null, $cross_summary = '') {
    global $lang;
    $nl = "\r\n";
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    $content = $rtl;

    $restaurantName = getRestaurantName($db);
    $content .= str_pad($restaurantName, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(strtoupper($kitchenName), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('kitchen_order') . ($reprint ? " (" . t('reprint') . ")" : ""), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= t('order_number') . ": " . $orderNumber . $nl;
    $content .= t('table') . ": " . $tableNumber . $nl;
    if (!empty($waiterName)) {
        $content .= t('waiter') . ": " . $waiterName . $nl;
    }
    $timeStr = $orderTime ? date('d/m/Y H:i:s', strtotime($orderTime)) : date('d/m/Y H:i:s');
    $content .= t('time') . ": " . $timeStr . $nl;
    $content .= str_repeat("-", $width) . $nl;

    foreach ($items as $item) {
        $line = $item['quantity'] . "x " . $item['name'];
        $content .= $line . $nl;
        if (!empty($item['notes'])) {
            $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
        }
    }

    $totalItems = array_sum(array_column($items, 'quantity'));
    $content .= str_repeat("-", $width) . $nl;
    $content .= t('total_items') . ": " . $totalItems . $nl;

    // Cross‑kitchen summary (other kitchens) – plain text with separators
    if (!empty($cross_summary)) {
        $content .= str_repeat("-", $width) . $nl;
        $content .= "*** " . t('other_kitchens') . " ***" . $nl;
        $content .= $cross_summary . $nl;
        $content .= str_repeat("-", $width) . $nl;
    }

    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";
    return $content;
}

function formatAccountantPrint($db, $orderNumber, $tableNumber, $items, $totalAmount, $waiterName = '', $sessionNumber = '', $customer_name = '', $width = 56) {
    global $lang;
    $nl = "\r\n";
    $content = '';  // no global RTL override

    $restaurantName = getRestaurantName($db);
    $content .= str_pad($restaurantName, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    if (!empty($sessionNumber)) {
        $content .= str_pad(t('session') . ": " . $sessionNumber, $width, ' ', STR_PAD_BOTH) . $nl;
    }
    $content .= str_pad(t('order_receipt'), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= t('order_number') . ": " . $orderNumber . $nl;
    $content .= t('table') . ": " . $tableNumber . $nl;
    if (!empty($customer_name)) {
        $content .= t('customer_name') . ": " . $customer_name . $nl;
    }
    if (!empty($sessionNumber)) $content .= t('session') . ": " . $sessionNumber . $nl;
    if (!empty($waiterName)) $content .= t('waiter') . ": " . $waiterName . $nl;
    $content .= t('time') . ": " . date('d/m/Y H:i:s') . $nl;
    $content .= str_repeat("-", $width) . $nl;

    // Items with dot leaders
    foreach ($items as $item) {
        $left = $item['quantity'] . "x " . $item['name'];
        $right = formatPriceReceipt($item['subtotal'], $lang);
        $dotLen = $width - strlen($left) - strlen($right) - 1;
        $dots = str_repeat('.', max($dotLen, 1));
        $content .= $left . " " . $dots . " " . $right . $nl;
        /* if (!empty($item['notes'])) {
            $content .= "   " . t('note') . ": " . $item['notes'] . $nl;
        } */
    }

    // Separator before totals
    $content .= str_repeat("*", $width) . $nl;

    // Subtotal
    $left = t('subtotal') . ":";
    $right = formatPriceReceipt($totalAmount, $lang);
    $dotLen = $width - strlen($left) - strlen($right) - 1;
    $dots = str_repeat('.', max($dotLen, 1));
    $content .= $left . " " . $dots . " " . $right . $nl;

    // Tax
    $left = t('tax') . ":";
    $right = formatPriceReceipt(0, $lang);
    $dotLen = $width - strlen($left) - strlen($right) - 1;
    $dots = str_repeat('.', max($dotLen, 1));
    $content .= $left . " " . $dots . " " . $right . $nl;

    // Total
    $left = t('total') . ":";
    $right = formatPriceReceipt($totalAmount, $lang);
    $dotLen = $width - strlen($left) - strlen($right) - 1;
    $dots = str_repeat('.', max($dotLen, 1));
    $content .= $left . " " . $dots . " " . $right . $nl;

    $content .= str_repeat("=", $width) . $nl;
    $content .= str_pad(t('thank_you'), $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";
    return $content;
}

function getRestaurantName($db) {
    $stmt = $db->prepare("SELECT setting_value FROM restaurant_settings WHERE setting_key = 'restaurant_name'");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : 'My Restaurant';
}

function formatPriceReceipt($amount, $lang) {
    $formatted = number_format($amount, 2);
    if ($lang === 'ar') {
        return $formatted . ' ' . t('currency');
    } else {
        return t('currency') . $formatted;
    }
}

function getControllerPrinterSettings($db) {
    $stmt = $db->prepare("SELECT setting_value FROM restaurant_settings WHERE setting_key = 'controller_printer_name'");
    $stmt->execute();
    $name = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT setting_value FROM restaurant_settings WHERE setting_key = 'controller_print_enabled'");
    $stmt->execute();
    $enabled = $stmt->fetchColumn();
    return [
        'enabled' => (bool)$enabled,
        'name' => $name
    ];
}

function formatControllerTicket($db, $items_by_kitchen, $orderNumber, $tableNumber, $sessionNumber = '', $waiterName = '', $orderTime = null) {
    global $lang;
    $nl = "\r\n";
    $width = 56;
    $rtl = ($lang === 'ar') ? "\xE2\x80\xAE" : '';
    
    // Filter items that should be printed on controller
    $filteredKitchens = [];
    $kitchenStmt = $db->prepare("SELECT name FROM kitchens WHERE id = ?");
    
    foreach ($items_by_kitchen as $kitchen_id => $kitchenItems) {
        $filteredItems = [];
        foreach ($kitchenItems as $item) {
            if (isset($item['print_on_controller']) && $item['print_on_controller'] == 1) {
                $filteredItems[] = $item;
            }
        }
        if (!empty($filteredItems)) {
            $kitchenStmt->execute([$kitchen_id]);
            $kitchenName = $kitchenStmt->fetchColumn();
            if (!$kitchenName) $kitchenName = "Kitchen #$kitchen_id";
            $filteredKitchens[$kitchen_id] = [
                'name' => $kitchenName,
                'items' => $filteredItems
            ];
        }
    }
    
    if (empty($filteredKitchens)) {
        return '';
    }
    
    $content = $rtl;
    $restaurantName = getRestaurantName($db);
    $content .= str_pad($restaurantName, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    $content .= str_pad(t('controller_ticket'), $width, ' ', STR_PAD_BOTH) . $nl;
    if (!empty($sessionNumber)) {
        $content .= str_pad(t('session') . ": " . $sessionNumber, $width, ' ', STR_PAD_BOTH) . $nl;
    }
    $content .= str_pad(t('order_number') . ": " . $orderNumber, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_pad(t('table') . ": " . $tableNumber, $width, ' ', STR_PAD_BOTH) . $nl;
    if (!empty($waiterName)) {
        $content .= str_pad(t('waiter') . ": " . $waiterName, $width, ' ', STR_PAD_BOTH) . $nl;
    }
    $timeStr = $orderTime ? date('d/m/Y H:i:s', strtotime($orderTime)) : date('d/m/Y H:i:s');
    $content .= str_pad(t('time') . ": " . $timeStr, $width, ' ', STR_PAD_BOTH) . $nl;
    $content .= str_repeat("=", $width) . $nl;
    
    foreach ($filteredKitchens as $kitchen) {
        $content .= strtoupper($kitchen['name']) . $nl;
        $content .= str_repeat("-", $width) . $nl;
        foreach ($kitchen['items'] as $item) {
            $line = $item['quantity'] . "x " . $item['name'];
            if (!empty($item['notes'])) {
                $line .= " (" . $item['notes'] . ")";
            }
            $content .= $line . $nl;
        }
        $content .= $nl;
    }
    
    $content .= str_repeat("=", $width) . $nl;
    $content .= "\x0C";
    return $content;
}
?>