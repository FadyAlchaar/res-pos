<?php
echo "1. Current directory: " . __DIR__ . "<br>";
echo "2. Trying to include config...<br>";

$configPath = dirname(__DIR__) . '/config/config.php';
echo "3. Config path: " . $configPath . "<br>";
echo "4. File exists: " . (file_exists($configPath) ? 'Yes' : 'No') . "<br>";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "5. Config loaded successfully!<br>";
    echo "6. Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "<br>";
} else {
    echo "5. Config file not found!<br>";
}

echo "7. Testing JSON output:<br>";
$test = ['test' => 'success', 'time' => date('Y-m-d H:i:s')];
echo json_encode($test);
?>