<?php
require_once 'config/config.php';
require_once 'includes/session_helper.php';

echo "<h1>Test Session Helper</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test create session function
    $session = createSession($db, 1, 1, 2);
    echo "<p style='color:green;'>✅ Session created: " . $session['session_number'] . "</p>";
    
    // Test get active session
    $active = getActiveSession($db, 1);
    echo "<pre>";
    print_r($active);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>