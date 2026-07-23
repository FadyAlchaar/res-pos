<?php
echo "<h1>Testing Printers API</h1>";

// Test the API
$url = 'http://localhost/res-pos/admin/api/get_windows_printers.php';
$response = file_get_contents($url);
echo "<h2>API Response:</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
if ($data && !isset($data['error'])) {
    echo "<h2>Printers Found:</h2>";
    echo "<ul>";
    foreach ($data as $printer) {
        echo "<li>" . htmlspecialchars($printer['name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Error: " . ($data['error'] ?? 'Unknown error') . "</p>";
}

// Also show raw WMIC output
echo "<h2>Raw WMIC Output:</h2>";
echo "<pre>" . shell_exec('wmic printer get name') . "</pre>";
?>