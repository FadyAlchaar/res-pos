<?php
class ErrorHandler {
    private $logFile;
    
    public function __construct() {
        $this->logFile = LOG_PATH . '/error.log';
    }
    
    public function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[$timestamp] ERROR: $message$contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $this->logFile);
    }
    
    public function handlePrinterError($printerIp, $error) {
        $this->logError("Printer error at $printerIp: $error");
        
        // You could also send email notification or create alert in database
        $this->createPrinterAlert($printerIp, $error);
    }
    
    private function createPrinterAlert($printerIp, $error) {
        // Store in database for admin dashboard
        global $db;
        
        $query = "INSERT INTO system_alerts (type, message, severity, created_at) 
                  VALUES ('printer', :message, 'error', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([':message' => "Printer $printerIp: $error"]);
    }
}

// Global error handler
function handleException($exception) {
    $handler = new ErrorHandler();
    $handler->logError($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // Return JSON for API requests
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'An internal error occurred']);
    } else {
        // Show user-friendly error page
        include 'error_page.php';
    }
}

set_exception_handler('handleException');
?>