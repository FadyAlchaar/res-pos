<?php
echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if database exists
    $stmt = $db->query("SELECT DATABASE()");
    $dbname = $stmt->fetchColumn();
    echo "<p>Connected to database: <strong>{$dbname}</strong></p>";
    
    // List all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
}
?>