<?php
// No authentication required – secret API for manager
require_once '../../config/config.php';
require_once '../../config/language.php';
header('Content-Type: application/json');

$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$date_filter = '';

switch ($period) {
    case 'today': $date_filter = "DATE(created_at) = CURDATE()"; break;
    case 'week': $date_filter = "YEARWEEK(created_at) = YEARWEEK(CURDATE())"; break;
    case 'month': $date_filter = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"; break;
    default: $date_filter = "1=1";
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM parking_requests WHERE $date_filter");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->prepare("SELECT id, table_number, lot_numbers, created_at FROM parking_requests WHERE $date_filter ORDER BY created_at DESC");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'total' => (int)$total, 'requests' => $requests]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>