<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$printers = [];

// Use wmic with full path
$cmd = 'C:\\Windows\\System32\\wbem\\wmic.exe printer get name';
exec($cmd, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    foreach ($output as $line) {
        $printer = trim($line);
        if (!empty($printer) && $printer !== 'Name' && strpos($printer, 'Name') === false) {
            // Exclude virtual printers
            if (preg_match('/Microsoft|OneNote|Fax|PDF|XPS|Evernote|AnyDesk|Adobe|RustDesk/i', $printer)) {
                continue;
            }
            $printers[] = ['name' => $printer];
        }
    }
}

// If still empty, try PowerShell
if (empty($printers)) {
    $cmd = 'powershell -Command "Get-Printer | Select-Object -ExpandProperty Name"';
    exec($cmd, $output, $returnCode);
    if ($returnCode === 0) {
        foreach ($output as $line) {
            $printer = trim($line);
            if (!empty($printer) && strpos($printer, 'Get-Printer') === false) {
                if (preg_match('/Microsoft|OneNote|Fax|PDF|XPS|Evernote|AnyDesk|Adobe|RustDesk/i', $printer)) {
                    continue;
                }
                $printers[] = ['name' => $printer];
            }
        }
    }
}

// If no printers found, return error with debug info
if (empty($printers)) {
    echo json_encode(['error' => 'No printers found. Check that the Apache user has permission to enumerate printers.']);
} else {
    echo json_encode($printers);
}
?>