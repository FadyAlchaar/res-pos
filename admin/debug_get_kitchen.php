<?php
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config with absolute path
require_once dirname(__DIR__) . '/config/config.php';

// Set header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in', 'session' => $_SESSION]);
    exit;
}

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM kitchens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $kitchen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($kitchen) {
        echo json_encode($kitchen);
    } else {
        echo json_encode(['error' => 'Kitchen not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>