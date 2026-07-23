<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'parking')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID required']);
    exit;
}

$id = (int)$data['id'];

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE parking_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>