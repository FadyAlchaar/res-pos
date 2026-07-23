<?php
require_once '../config/config.php';

header('Content-Type: application/json');

file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - Starting\n", FILE_APPEND);

try {
    $database = new Database();
    $db = $database->getConnection();
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - DB connected\n", FILE_APPEND);
    
    echo json_encode(['success' => true, 'message' => 'DB connected']);
    
} catch (Exception $e) {
    file_put_contents('C:\\xampp\\htdocs\\res-pos\\debug_test.txt', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>