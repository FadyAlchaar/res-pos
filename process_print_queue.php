<?php
// process_print_queue.php – run this every 5 seconds via Windows Task Scheduler
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/print_functions.php';
require_once __DIR__ . '/includes/print_helper.php';

// Prevent running from web browser
if (php_sapi_name() !== 'cli') {
    die("CLI only");
}

$database = new Database();
$db = $database->getConnection();

// Get pending jobs, oldest first, limit 5 per run
$stmt = $db->prepare("SELECT * FROM print_queue WHERE status = 'pending' AND retries < 3 ORDER BY created_at LIMIT 5");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    // Mark as processing
    $update = $db->prepare("UPDATE print_queue SET status = 'processing', processed_at = NOW() WHERE id = :id");
    $update->execute([':id' => $job['id']]);
    
    $success = false;
    $errorMsg = '';
    
    try {
        // Build printer array
        $printer = [
            'type' => $job['printer_type'],
            'ip' => $job['printer_ip'],
            'port' => $job['printer_port'],
            'name' => $job['printer_name']
        ];
        
        // Render content as image if it contains Arabic or if it's a kitchen/accountant job with Arabic
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $job['content']);
        
        if ($hasArabic && $job['printer_type'] === 'network') {
            // Convert content to HTML image
            $helper = new PrintHelper($job['printer_ip'], $job['printer_port']);
            $pngPath = $helper->htmlToPng(nl2br(htmlspecialchars($job['content'])));
            if ($pngPath) {
                $success = $helper->printPng($pngPath);
                unlink($pngPath);
            } else {
                $errorMsg = "Failed to convert HTML to PNG";
                $success = false;
            }
        } else {
            // Plain text printing (for non-Arabic or Windows printers)
            if ($job['printer_type'] === 'network') {
                $success = sendNetworkPrintPlain($job['printer_ip'], $job['printer_port'], $job['content'], 1);
            } else {
                $success = sendWindowsPrint($job['printer_name'], $job['content']);
            }
        }
        
        if (!$success && empty($errorMsg)) {
            $errorMsg = "Print command sent but no success confirmation";
        }
        
    } catch (Exception $e) {
        $success = false;
        $errorMsg = $e->getMessage();
    }
    
    // Update job status
    $newStatus = $success ? 'completed' : 'failed';
    $retries = $job['retries'] + ($success ? 0 : 1);
    $finalError = $success ? null : ($errorMsg ?: "Unknown error");
    
    $finalUpdate = $db->prepare("
        UPDATE print_queue 
        SET status = :status, retries = :retries, error_message = :error 
        WHERE id = :id
    ");
    $finalUpdate->execute([
        ':status' => $newStatus,
        ':retries' => $retries,
        ':error' => $finalError,
        ':id' => $job['id']
    ]);
    
    // Log failure for monitoring
    if (!$success) {
        error_log("Print job {$job['id']} failed: $finalError");
    }
}
?>