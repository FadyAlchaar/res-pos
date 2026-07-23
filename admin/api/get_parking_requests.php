<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'parking')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM parking_requests WHERE id > ? AND status = 'pending' ORDER BY id ASC");
    $stmt->execute([$last_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>