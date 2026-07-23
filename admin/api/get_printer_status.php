<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT k.id, k.name as kitchen_name, k.printer_ip, k.printer_port, 
                     k.printer_model, k.status, k.last_checked,
                     (SELECT COUNT(*) FROM print_jobs WHERE kitchen_id = k.id AND status = 'failed') as failed_jobs
              FROM kitchens k
              WHERE k.is_active = 1
              ORDER BY k.name";
    $stmt = $db->query($query);
    $printers = $stmt->fetchAll();
    
    echo json_encode($printers);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>