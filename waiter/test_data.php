<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - Received data: " . $input . "\n", FILE_APPEND);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Just test if we can insert
    $test_query = "SELECT COUNT(*) as count FROM kitchens";
    $result = $db->query($test_query);
    $row = $result->fetch();
    
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - Kitchens count: " . $row['count'] . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Data processed',
        'kitchens' => $row['count'],
        'received' => $data
    ]);
    
} catch (Exception $e) {
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>