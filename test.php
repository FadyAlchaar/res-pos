<?php
echo "<h2>System Test</h2>";

// Test 1: Check current directory
echo "<b>Current directory:</b> " . __DIR__ . "<br>";

// Test 2: Check if config file exists
$configPath = __DIR__ . '/config/config.php';
echo "<b>Config path:</b> " . $configPath . "<br>";
echo "<b>Config exists:</b> " . (file_exists($configPath) ? 'Yes' : 'No') . "<br>";

// Test 3: Try to include config
if (file_exists($configPath)) {
    require_once $configPath;
    echo "<b>Config loaded successfully!</b><br>";
    echo "<b>ROOT_PATH:</b> " . ROOT_PATH . "<br>";
} else {
    echo "<b style='color:red'>Config file not found!</b><br>";
}

// Test 4: Check database connection
if (class_exists('Database')) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        echo "<b>Database connection:</b> Successful!<br>";
    } catch (Exception $e) {
        echo "<b>Database connection:</b> Failed - " . $e->getMessage() . "<br>";
    }
}
?>