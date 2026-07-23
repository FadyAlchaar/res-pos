<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if (!$job_id) {
    echo json_encode(['success' => false, 'message' => 'Job ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the original print job
    $query = "SELECT * FROM print_jobs WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Print job not found']);
        exit;
    }
    
    // Create a new print job with pending status
    $query = "INSERT INTO print_jobs (order_item_id, kitchen_id, printer_ip, content, status) 
              VALUES (:order_item_id, :kitchen_id, :printer_ip, :content, 'pending')";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':order_item_id' => $job['order_item_id'],
        ':kitchen_id' => $job['kitchen_id'],
        ':printer_ip' => $job['printer_ip'],
        ':content' => $job['content']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Print job retried successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to retry print job']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>