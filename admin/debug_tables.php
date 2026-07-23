<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "<h1>🔍 Database Structure Debug</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check restaurant_tables structure
    echo "<h2>restaurant_tables Structure:</h2>";
    $stmt = $db->query("DESCRIBE restaurant_tables");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check restaurant_settings structure
    echo "<h2>restaurant_settings Structure:</h2>";
    $stmt = $db->query("DESCRIBE restaurant_settings");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show current data
    echo "<h2>Current restaurant_tables data (first 5):</h2>";
    $stmt = $db->query("SELECT * FROM restaurant_tables LIMIT 5");
    $data = $stmt->fetchAll();
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    echo "<h2>Current restaurant_settings data:</h2>";
    $stmt = $db->query("SELECT * FROM restaurant_settings");
    $data = $stmt->fetchAll();
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>