<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Count jobs to archive
    $count_query = "SELECT COUNT(*) as count FROM print_jobs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) 
                    AND status NOT IN ('archived')";
    $count_stmt = $db->query($count_query);
    $count = $count_stmt->fetch()['count'];
    
    // Archive old jobs (mark as archived instead of deleting)
    $update = "UPDATE print_jobs SET status = 'archived' 
               WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) 
               AND status NOT IN ('archived')";
    $result = $db->exec($update);
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => "$count print jobs archived successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>