<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_password = $_POST['test_password'];
    $test_hash = $_POST['test_hash'];
    
    $result = password_verify($test_password, $test_hash);
    
    echo "<h3>Password Test Result:</h3>";
    echo "Password: " . $test_password . "<br>";
    echo "Hash: " . $test_hash . "<br>";
    echo "Verification: " . ($result ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "<br>";
    
    if (!$result) {
        echo "<br><b>Try generating a new hash:</b><br>";
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "New hash for '" . $test_password . "': " . $new_hash;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Password Verification</title>
</head>
<body>
    <h2>Test Password Hash Verification</h2>
    <form method="POST">
        <div>
            <label>Password to test:</label>
            <input type="text" name="test_password" required>
        </div>
        <div>
            <label>Hash from database:</label>
            <input type="text" name="test_hash" required size="60">
        </div>
        <button type="submit">Test</button>
    </form>
    
    <h3>Instructions:</h3>
    <ol>
        <li>Go to phpMyAdmin and copy the password hash from your users table</li>
        <li>Paste it in the "Hash from database" field</li>
        <li>Enter the password you want to test</li>
        <li>Click "Test" to see if they match</li>
    </ol>
</body>
</html>