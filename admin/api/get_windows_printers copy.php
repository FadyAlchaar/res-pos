<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $printers = [];
    
    // Method 1: PowerShell (most reliable)
    $cmd = 'powershell -Command "Get-Printer | Select-Object -ExpandProperty Name"';
    $output = shell_exec($cmd);
    
    if ($output && strlen(trim($output)) > 0) {
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $printer = trim($line);
            if (!empty($printer)) {
                // Exclude virtual printers
                if (strpos($printer, 'Microsoft') === false && 
                    strpos($printer, 'OneNote') === false &&
                    strpos($printer, 'Fax') === false &&
                    strpos($printer, 'PDF') === false &&
                    strpos($printer, 'XPS') === false &&
                    strpos($printer, 'Evernote') === false &&
                    strpos($printer, 'AnyDesk') === false &&
                    strpos($printer, 'Adobe') === false &&
                    strpos($printer, 'RustDesk') === false) {
                    $printers[] = ['name' => $printer];
                }
            }
        }
    } else {
        // Fallback: WMIC
        $output = shell_exec('wmic printer get name 2>&1');
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                $printer = trim($line);
                if (!empty($printer) && $printer !== 'Name' && strpos($printer, 'Name') === false) {
                    if (strpos($printer, 'Microsoft') === false && 
                        strpos($printer, 'OneNote') === false &&
                        strpos($printer, 'Fax') === false &&
                        strpos($printer, 'PDF') === false &&
                        strpos($printer, 'XPS') === false &&
                        strpos($printer, 'Evernote') === false &&
                        strpos($printer, 'AnyDesk') === false &&
                        strpos($printer, 'Adobe') === false &&
                        strpos($printer, 'RustDesk') === false) {
                        $printers[] = ['name' => $printer];
                    }
                }
            }
        }
    }
    
    echo json_encode($printers);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>